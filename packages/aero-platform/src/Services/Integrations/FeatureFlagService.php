<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Integrations;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Experiment;
use Aero\Platform\Models\FeatureFlag;
use Aero\Platform\Models\FeatureFlagTenantOverride;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * P-7 — Feature Flag Service.
 */
class FeatureFlagService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return FeatureFlag::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")
                ->orWhere('code', 'like', "%{$v}%"))
            ->when(isset($filters['archived']), fn ($q) => $q->where('is_archived', (bool) $filters['archived']))
            ->when(! isset($filters['archived']), fn ($q) => $q->where('is_archived', false))
            ->withCount('tenantOverrides')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): FeatureFlag
    {
        return DB::transaction(function () use ($data): FeatureFlag {
            $flag = FeatureFlag::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'rollout_pct' => $data['rollout_pct'] ?? 0,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_CREATED->value,
                action: 'create',
                subject: $flag,
                description: "Feature flag created: {$flag->code}",
            );

            return $flag;
        });
    }

    public function update(int|string $id, array $data): FeatureFlag
    {
        return DB::transaction(function () use ($id, $data): FeatureFlag {
            $flag = FeatureFlag::findOrFail($id);
            $before = $flag->only(['name', 'description', 'is_active', 'rollout_pct']);

            $flag->update(array_intersect_key($data, array_flip([
                'name', 'description', 'is_active', 'rollout_pct',
            ])));

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_UPDATED->value,
                action: 'update',
                subject: $flag,
                description: "Feature flag updated: {$flag->code}",
                before: $before,
                after: $flag->fresh()->only(['name', 'description', 'is_active', 'rollout_pct']),
            );

            return $flag->refresh();
        });
    }

    public function archive(int|string $id): FeatureFlag
    {
        return DB::transaction(function () use ($id): FeatureFlag {
            $flag = FeatureFlag::findOrFail($id);
            $flag->update(['is_archived' => true, 'is_active' => false]);

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_ARCHIVED->value,
                action: 'archive',
                subject: $flag,
                description: "Feature flag archived: {$flag->code}",
            );

            return $flag->refresh();
        });
    }

    public function toggle(int|string $id): FeatureFlag
    {
        return DB::transaction(function () use ($id): FeatureFlag {
            $flag = FeatureFlag::findOrFail($id);
            $flag->update(['is_active' => ! $flag->is_active]);

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_TOGGLED->value,
                action: 'toggle',
                subject: $flag,
                description: 'Feature flag toggled to '.($flag->is_active ? 'active' : 'inactive').": {$flag->code}",
            );

            return $flag->refresh();
        });
    }

    public function tenantOverrides(int|string $flagId, array $filters = []): LengthAwarePaginator
    {
        return FeatureFlagTenantOverride::where('flag_id', $flagId)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function setTenantOverride(
        int|string $flagId,
        string $tenantId,
        bool $isActive,
        ?DateTime $expiresAt = null
    ): FeatureFlagTenantOverride {
        return DB::transaction(function () use ($flagId, $tenantId, $isActive, $expiresAt): FeatureFlagTenantOverride {
            $flag = FeatureFlag::findOrFail($flagId);
            $override = FeatureFlagTenantOverride::updateOrCreate(
                ['flag_id' => $flag->id, 'tenant_id' => $tenantId],
                [
                    'is_active' => $isActive,
                    'expires_at' => $expiresAt,
                    'set_by' => auth('landlord')->id(),
                ],
            );

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_OVERRIDE_SET->value,
                action: 'set-override',
                subject: $flag,
                description: "Tenant override set for flag '{$flag->code}', tenant '{$tenantId}': ".($isActive ? 'enabled' : 'disabled'),
            );

            return $override;
        });
    }

    public function removeTenantOverride(int|string $overrideId): void
    {
        DB::transaction(function () use ($overrideId): void {
            $override = FeatureFlagTenantOverride::with('flag')->findOrFail($overrideId);
            $flag = $override->flag;

            $override->delete();

            $this->audit->log(
                event: AuditEventType::FEATURE_FLAG_OVERRIDE_REMOVED->value,
                action: 'remove-override',
                subject: $flag,
                description: "Tenant override removed for flag '{$flag?->code}', tenant '{$override->tenant_id}'",
            );
        });
    }

    public function listExperiments(array $filters = []): LengthAwarePaginator
    {
        return Experiment::query()
            ->with('flag:id,code,name')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function startExperiment(array $data): Experiment
    {
        return DB::transaction(function () use ($data): Experiment {
            FeatureFlag::findOrFail($data['flag_id']);

            $experiment = Experiment::create([
                'name' => $data['name'],
                'flag_id' => $data['flag_id'],
                'control_pct' => $data['control_pct'] ?? 50,
                'variant_pct' => $data['variant_pct'] ?? 50,
                'started_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::EXPERIMENT_STARTED->value,
                action: 'start',
                subject: $experiment,
                description: "Experiment started: {$experiment->name}",
            );

            return $experiment;
        });
    }

    public function stopExperiment(int|string $id, ?string $winner = null): Experiment
    {
        return DB::transaction(function () use ($id, $winner): Experiment {
            $experiment = Experiment::findOrFail($id);
            $experiment->update(['stopped_at' => now(), 'winner' => $winner]);

            $this->audit->log(
                event: AuditEventType::EXPERIMENT_STOPPED->value,
                action: 'stop',
                subject: $experiment,
                description: "Experiment stopped: {$experiment->name}".($winner ? " — winner: {$winner}" : ''),
            );

            return $experiment->refresh();
        });
    }
}
