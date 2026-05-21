<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\TaxProvider;
use Aero\Platform\Models\TaxRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaxService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<TaxRate>
     */
    public function listRates(array $filters = []): LengthAwarePaginator
    {
        return TaxRate::query()
            ->when(isset($filters['country_code']), fn ($q) => $q->where('country_code', $filters['country_code']))
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('country_code')
            ->orderBy('region_code')
            ->paginate(50);
    }

    public function upsertRate(array $data, int $actorId): TaxRate
    {
        return DB::transaction(function () use ($data): TaxRate {
            $rate = TaxRate::updateOrCreate(
                [
                    'country_code' => $data['country_code'],
                    'region_code' => $data['region_code'] ?? null,
                    'type' => $data['type'],
                ],
                [
                    'name' => $data['name'],
                    'rate' => $data['rate'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            $this->audit->log(
                event: AuditEventType::TAX_RATE_UPSERTED->value,
                action: $rate->wasRecentlyCreated ? 'created' : 'updated',
                subject: $rate,
                description: "Tax rate upserted: {$rate->country_code} / {$rate->type} @ {$rate->rate}",
            );

            return $rate;
        });
    }

    /**
     * @return Collection<int, TaxProvider>
     */
    public function listProviders(): Collection
    {
        return TaxProvider::query()->orderBy('code')->get();
    }

    public function configureProvider(string $providerCode, array $config, int $actorId): TaxProvider
    {
        return DB::transaction(function () use ($providerCode, $config): TaxProvider {
            $provider = TaxProvider::firstOrNew(['code' => $providerCode]);
            $provider->fill([
                'name' => $config['name'] ?? $providerCode,
                'config' => $config['config'] ?? null,
                'is_active' => $config['is_active'] ?? false,
            ]);
            $provider->save();

            $this->audit->log(
                event: AuditEventType::TAX_PROVIDER_CONFIGURED->value,
                action: 'configured',
                subject: $provider,
                description: "Tax provider configured: {$providerCode}",
            );

            return $provider;
        });
    }

    /**
     * Validate a tax ID via the active provider.
     * Returns ['valid' => bool, 'message' => string].
     */
    public function validateTaxId(string $countryCode, string $taxId): array
    {
        // Stub: real providers (Stripe Tax, Avalara) would be called here via adapter.
        // Returns valid=true for well-formed EU VAT numbers as a placeholder.
        $valid = preg_match('/^[A-Z]{2}[A-Z0-9+*]{2,12}$/', strtoupper(trim($taxId))) === 1;

        return [
            'valid' => $valid,
            'message' => $valid ? 'Tax ID format is valid.' : 'Tax ID format is invalid.',
        ];
    }

    /**
     * Build a tax report for the given period (e.g. '2026-Q1', '2026').
     */
    public function generateReport(string $period, int $actorId): array
    {
        $this->audit->log(
            event: AuditEventType::TAX_REPORT_GENERATED->value,
            action: 'generated',
            subject: null,
            description: "Tax report generated for period: {$period}",
            metadata: ['period' => $period],
        );

        // Stub implementation — real aggregation queries would run here.
        return [
            'period' => $period,
            'generated_at' => now()->toISOString(),
            'rows' => [],
        ];
    }

    public function exportReport(string $period, int $actorId): string
    {
        $this->audit->log(
            event: AuditEventType::TAX_REPORT_EXPORTED->value,
            action: 'exported',
            subject: null,
            description: "Tax report exported for period: {$period}",
            metadata: ['period' => $period],
        );

        // Returns CSV content string (stub).
        return "country_code,type,rate,period\n";
    }

    public function generateW9Form(string $tenantId, int $actorId): string
    {
        $this->audit->log(
            event: AuditEventType::TAX_W9_GENERATED->value,
            action: 'generated',
            subject: null,
            description: "W-9 form generated for tenant: {$tenantId}",
            metadata: ['tenant_id' => $tenantId],
        );

        // Returns a stub PDF path; real implementation uses a PDF renderer.
        return storage_path("app/w9/{$tenantId}.pdf");
    }
}
