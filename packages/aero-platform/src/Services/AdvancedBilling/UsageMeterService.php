<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\UsageEvent;
use Aero\Platform\Models\UsageMeter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UsageMeterService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<UsageMeter>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return UsageMeter::query()
            ->withCount('events')
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', (bool) $filters['is_active'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('code', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate(20);
    }

    public function create(array $data, int $actorId): UsageMeter
    {
        return DB::transaction(function () use ($data) {
            /** @var UsageMeter $meter */
            $meter = UsageMeter::create($data);

            $this->audit->log(
                AuditEventType::USAGE_METER_CREATED->value,
                'usage_meter.create',
                $meter,
                "Usage meter [{$meter->code}] created."
            );

            return $meter;
        });
    }

    public function configure(UsageMeter $meter, array $data, int $actorId): UsageMeter
    {
        return DB::transaction(function () use ($meter, $data) {
            $meter->update($data);

            $this->audit->log(
                AuditEventType::USAGE_METER_CONFIGURED->value,
                'usage_meter.configure',
                $meter,
                "Usage meter [{$meter->code}] configured."
            );

            return $meter->refresh();
        });
    }

    /**
     * @return LengthAwarePaginator<UsageEvent>
     */
    public function events(UsageMeter $meter, array $filters = []): LengthAwarePaginator
    {
        return UsageEvent::query()
            ->where('meter_id', $meter->id)
            ->when(
                isset($filters['tenant_id']),
                fn ($q) => $q->where('tenant_id', $filters['tenant_id'])
            )
            ->when(
                isset($filters['from']),
                fn ($q) => $q->where('occurred_at', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn ($q) => $q->where('occurred_at', '<=', $filters['to'])
            )
            ->latest('occurred_at')
            ->paginate(50);
    }

    /**
     * Return platform-level pay-as-you-go configuration from settings.
     */
    public function payAsYouGoConfig(): array
    {
        return [
            'enabled' => (bool) config('aero-platform.payg.enabled', false),
            'billing_threshold' => config('aero-platform.payg.billing_threshold', 10.00),
            'auto_invoice' => (bool) config('aero-platform.payg.auto_invoice', false),
        ];
    }

    /**
     * Persist pay-as-you-go configuration to platform settings.
     */
    public function updatePayAsYouGoConfig(array $data, int $actorId): array
    {
        return DB::transaction(function () use ($data) {
            config([
                'aero-platform.payg.enabled' => $data['enabled'] ?? false,
                'aero-platform.payg.billing_threshold' => $data['billing_threshold'] ?? 10.00,
                'aero-platform.payg.auto_invoice' => $data['auto_invoice'] ?? false,
            ]);

            $this->audit->log(
                AuditEventType::PAYG_CONFIG_UPDATED->value,
                'payg.configure',
                null,
                'Pay-as-you-go configuration updated.'
            );

            return $this->payAsYouGoConfig();
        });
    }
}
