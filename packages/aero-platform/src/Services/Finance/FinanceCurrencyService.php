<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PlatformCurrency;
use Aero\Platform\Models\RegionalPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinanceCurrencyService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<PlatformCurrency>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return PlatformCurrency::query()
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('code')
            ->paginate(50);
    }

    public function upsertCurrency(array $data, int $actorId): PlatformCurrency
    {
        return DB::transaction(function () use ($data): PlatformCurrency {
            $currency = PlatformCurrency::updateOrCreate(
                ['code' => strtoupper($data['code'])],
                [
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'exchange_rate_to_usd' => $data['exchange_rate_to_usd'] ?? 1,
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            $this->audit->log(
                event: AuditEventType::CURRENCY_UPSERTED->value,
                action: $currency->wasRecentlyCreated ? 'created' : 'updated',
                subject: $currency,
                description: "Currency upserted: {$currency->code}",
            );

            return $currency;
        });
    }

    /**
     * Bulk-update exchange rates for all active currencies from a provider.
     * In production, this calls a live rates API; here we accept the rates map directly.
     *
     * @param  array<string, float>  $ratesMap  ['EUR' => 0.92, 'GBP' => 0.79, ...]
     */
    public function syncExchangeRates(array $ratesMap, int $actorId): int
    {
        return DB::transaction(function () use ($ratesMap): int {
            $updated = 0;

            PlatformCurrency::query()
                ->where('is_active', true)
                ->get()
                ->each(function (PlatformCurrency $currency) use ($ratesMap, &$updated): void {
                    $code = strtoupper($currency->code);
                    if (isset($ratesMap[$code])) {
                        $currency->update([
                            'exchange_rate_to_usd' => $ratesMap[$code],
                            'rate_updated_at' => now(),
                        ]);
                        $updated++;
                    }
                });

            $this->audit->log(
                event: AuditEventType::EXCHANGE_RATES_SYNCED->value,
                action: 'synced',
                subject: null,
                description: "Exchange rates synced — {$updated} currencies updated.",
                metadata: ['updated_count' => $updated],
            );

            return $updated;
        });
    }

    public function setManualRate(PlatformCurrency $currency, float $rate, int $actorId): PlatformCurrency
    {
        return DB::transaction(function () use ($currency, $rate): PlatformCurrency {
            $before = ['exchange_rate_to_usd' => (float) $currency->exchange_rate_to_usd];

            $currency->update([
                'exchange_rate_to_usd' => $rate,
                'rate_updated_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::EXCHANGE_RATE_SET_MANUAL->value,
                action: 'manual_rate_set',
                subject: $currency,
                description: "Manual exchange rate set for {$currency->code}: {$rate}",
                before: $before,
                after: ['exchange_rate_to_usd' => $rate],
            );

            return $currency->fresh();
        });
    }

    /**
     * @return LengthAwarePaginator<RegionalPrice>
     */
    public function listRegionalPricing(array $filters = []): LengthAwarePaginator
    {
        return RegionalPrice::query()
            ->with('priceable')
            ->when(isset($filters['currency_code']), fn ($q) => $q->where('currency_code', $filters['currency_code']))
            ->when(isset($filters['priceable_type']), fn ($q) => $q->where('priceable_type', $filters['priceable_type']))
            ->when(isset($filters['priceable_id']), fn ($q) => $q->where('priceable_id', $filters['priceable_id']))
            ->paginate(50);
    }

    public function upsertRegionalPrice(array $data, int $actorId): RegionalPrice
    {
        return DB::transaction(function () use ($data): RegionalPrice {
            $price = RegionalPrice::updateOrCreate(
                [
                    'priceable_type' => $data['priceable_type'],
                    'priceable_id' => $data['priceable_id'],
                    'currency_code' => strtoupper($data['currency_code']),
                ],
                [
                    'price_monthly' => $data['price_monthly'],
                    'price_annual' => $data['price_annual'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            $this->audit->log(
                event: AuditEventType::REGIONAL_PRICE_UPSERTED->value,
                action: $price->wasRecentlyCreated ? 'created' : 'updated',
                subject: $price,
                description: "Regional price upserted for {$data['priceable_type']} #{$data['priceable_id']} in {$price->currency_code}",
            );

            return $price;
        });
    }
}
