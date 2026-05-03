<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Plan;

class PlanCanonicalService
{
    /**
     * @param  array<int, array{key: string, value: mixed, unit?: ?string, metadata?: array<string, mixed>}>  $quotas
     * @return array<string, mixed>
     */
    public function projectQuotasToLegacyLimits(array $quotas): array
    {
        $limits = [];

        foreach ($quotas as $quota) {
            if (! isset($quota['key'])) {
                continue;
            }

            $limits[(string) $quota['key']] = $quota['value'] ?? null;
        }

        return $limits;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDto(Plan $plan): array
    {
        $quotas = $this->resolveCanonicalQuotas($plan);

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'tier' => $plan->tier,
            'plan_type' => $plan->plan_type,
            'monthly_price' => $plan->monthly_price,
            'yearly_price' => $plan->yearly_price,
            'trial_days' => $plan->trial_days,
            'is_active' => (bool) $plan->is_active,
            'is_featured' => (bool) $plan->is_featured,
            'visibility' => $plan->visibility,
            'features' => $this->normalizeArray($plan->features),
            'limits' => $this->resolveLimitsMap($plan, $quotas),
            'quotas' => $quotas,
            'modules' => $plan->relationLoaded('modules')
                ? $plan->modules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'code' => $module->code,
                        'name' => $module->name,
                        'description' => $module->description ?? null,
                        'is_core' => (bool) ($module->is_core ?? false),
                    ];
                })->values()->all()
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function syncCanonicalQuotas(Plan $plan, array $validated): void
    {
        $incomingQuotas = $this->extractIncomingQuotas($validated);

        if ($incomingQuotas === null) {
            return;
        }

        if ($incomingQuotas === []) {
            $plan->planQuotas()->delete();

            return;
        }

        $existingByKey = $plan->planQuotas()->get()->keyBy('key');
        $incomingKeys = [];

        foreach ($incomingQuotas as $index => $quota) {
            $key = $quota['key'];
            $incomingKeys[] = $key;

            $payload = [
                'value' => $quota['value'],
                'unit' => $quota['unit'],
                'metadata' => $quota['metadata'],
                'sort_order' => $index,
            ];

            $existing = $existingByKey->get($key);
            if ($existing) {
                $existing->update($payload);
            } else {
                $plan->planQuotas()->create([
                    'key' => $key,
                    ...$payload,
                ]);
            }
        }

        $plan->planQuotas()
            ->whereNotIn('key', $incomingKeys)
            ->delete();
    }

    /**
     * @return array<int, array{key: string, value: mixed, unit: ?string, metadata: array<string, mixed>}>
     */
    public function resolveCanonicalQuotas(Plan $plan): array
    {
        $fromRows = $plan->relationLoaded('planQuotas')
            ? $plan->planQuotas
            : $plan->planQuotas()->orderBy('sort_order')->get();

        $rowQuotas = $fromRows
            ->map(function ($quota) {
                return [
                    'key' => (string) $quota->key,
                    'value' => $quota->value,
                    'unit' => $quota->unit,
                    'metadata' => is_array($quota->metadata) ? $quota->metadata : [],
                ];
            })
            ->values()
            ->all();

        if (! empty($rowQuotas)) {
            return $rowQuotas;
        }

        return $this->projectLegacyQuotasFromPlan($plan);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLimitsMap(Plan $plan, array $quotas): array
    {
        $limits = $this->normalizeArray($plan->limits);

        foreach ($quotas as $quota) {
            $limits[$quota['key']] = $quota['value'];
        }

        return $limits;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{key: string, value: mixed, unit: ?string, metadata: array<string, mixed>}>|null
     */
    private function extractIncomingQuotas(array $validated): ?array
    {
        if (array_key_exists('quotas', $validated)) {
            $quotas = is_array($validated['quotas']) ? $validated['quotas'] : [];

            return $this->normalizeQuotaEntries($quotas);
        }

        $limits = is_array($validated['limits'] ?? null) ? $validated['limits'] : [];

        if (array_key_exists('max_users', $validated)) {
            $limits['max_users'] = $validated['max_users'];
        }
        if (array_key_exists('max_storage_gb', $validated)) {
            $limits['max_storage_gb'] = $validated['max_storage_gb'];
        }

        if ($limits === []) {
            return null;
        }

        return $this->normalizeQuotaEntries($this->projectLegacyQuotasFromArray($limits));
    }

    /**
     * @param  array<int, mixed>  $quotas
     * @return array<int, array{key: string, value: mixed, unit: ?string, metadata: array<string, mixed>}>
     */
    private function normalizeQuotaEntries(array $quotas): array
    {
        $normalized = collect($quotas)
            ->map(function ($quota) {
                if (! is_array($quota) || ! isset($quota['key'])) {
                    return null;
                }

                $key = trim((string) $quota['key']);
                if ($key === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'value' => $quota['value'] ?? null,
                    'unit' => isset($quota['unit']) ? (string) $quota['unit'] : $this->inferQuotaUnit($key),
                    'metadata' => is_array($quota['metadata'] ?? null) ? $quota['metadata'] : [],
                ];
            })
            ->filter()
            ->unique('key')
            ->values()
            ->all();

        return $normalized;
    }

    /**
     * @return array<int, array{key: string, value: mixed, unit: ?string, metadata: array<string, mixed>}>
     */
    private function projectLegacyQuotasFromPlan(Plan $plan): array
    {
        $limits = $this->normalizeArray($plan->limits);

        if (! array_key_exists('max_users', $limits) && $plan->max_users !== null) {
            $limits['max_users'] = $plan->max_users;
        }

        if (! array_key_exists('max_storage_gb', $limits) && $plan->max_storage_gb !== null) {
            $limits['max_storage_gb'] = $plan->max_storage_gb;
        }

        return $this->projectLegacyQuotasFromArray($limits);
    }

    /**
     * @param  array<string, mixed>  $limits
     * @return array<int, array{key: string, value: mixed, unit: ?string, metadata: array<string, mixed>}>
     */
    private function projectLegacyQuotasFromArray(array $limits): array
    {
        $quotas = [];

        foreach ($limits as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $quotas[] = [
                'key' => (string) $key,
                'value' => $value,
                'unit' => $this->inferQuotaUnit((string) $key),
                'metadata' => [],
            ];
        }

        return $quotas;
    }

    private function inferQuotaUnit(string $key): ?string
    {
        return match (true) {
            str_ends_with($key, '_gb') => 'gb',
            str_ends_with($key, '_days') => 'days',
            str_contains($key, 'api_calls') => 'requests',
            default => 'count',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
