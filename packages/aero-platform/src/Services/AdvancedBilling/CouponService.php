<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Coupon;
use Aero\Platform\Models\CouponCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<Coupon>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Coupon::query()
            ->with(['campaign:id,name', 'creator:id,name'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('code', 'like', '%'.$filters['search'].'%')
                    ->orWhere('name', 'like', '%'.$filters['search'].'%')
            )
            ->when(
                isset($filters['campaign_id']),
                fn ($q) => $q->where('campaign_id', $filters['campaign_id'])
            )
            ->latest('created_at')
            ->paginate(20);
    }

    public function create(array $data, int $actorId): Coupon
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['created_by'] = $actorId;
            $data['code'] = strtoupper($data['code'] ?? Str::upper(Str::random(8)));

            /** @var Coupon $coupon */
            $coupon = Coupon::create($data);

            $this->audit->log(
                AuditEventType::COUPON_CREATED->value,
                'coupon.create',
                $coupon,
                "Coupon [{$coupon->code}] created."
            );

            return $coupon;
        });
    }

    public function update(Coupon $coupon, array $data, int $actorId): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            $coupon->update($data);

            $this->audit->log(
                AuditEventType::COUPON_UPDATED->value,
                'coupon.update',
                $coupon,
                "Coupon [{$coupon->code}] updated."
            );

            return $coupon->refresh();
        });
    }

    public function archive(Coupon $coupon, int $actorId): Coupon
    {
        return DB::transaction(function () use ($coupon) {
            $coupon->update(['status' => Coupon::STATUS_ARCHIVED]);

            $this->audit->log(
                AuditEventType::COUPON_ARCHIVED->value,
                'coupon.archive',
                $coupon,
                "Coupon [{$coupon->code}] archived."
            );

            return $coupon->refresh();
        });
    }

    /**
     * Bulk-generate coupons for a campaign.
     *
     * @return Coupon[]
     */
    public function bulkGenerate(
        CouponCampaign $campaign,
        string $prefix,
        int $count,
        array $options,
        int $actorId
    ): array {
        return DB::transaction(function () use ($campaign, $prefix, $count, $options, $actorId) {
            $coupons = [];

            for ($i = 0; $i < $count; $i++) {
                $code = strtoupper($prefix.'-'.Str::upper(Str::random(6)));

                /** @var Coupon $coupon */
                $coupon = Coupon::create([
                    'code' => $code,
                    'name' => $options['name'] ?? $campaign->name.' — '.$code,
                    'type' => $options['type'] ?? Coupon::TYPE_PERCENT,
                    'value' => $options['value'] ?? 0,
                    'currency' => $options['currency'] ?? null,
                    'duration' => $options['duration'] ?? Coupon::DURATION_ONCE,
                    'duration_months' => $options['duration_months'] ?? null,
                    'max_redemptions' => $options['max_redemptions'] ?? null,
                    'expires_at' => $options['expires_at'] ?? null,
                    'status' => Coupon::STATUS_ACTIVE,
                    'campaign_id' => $campaign->id,
                    'created_by' => $actorId,
                ]);

                $coupons[] = $coupon;
            }

            $this->audit->log(
                AuditEventType::COUPON_BULK_GENERATED->value,
                'coupon.bulk_generate',
                $campaign,
                "Bulk generated {$count} coupons with prefix [{$prefix}] for campaign [{$campaign->name}]."
            );

            return $coupons;
        });
    }
}
