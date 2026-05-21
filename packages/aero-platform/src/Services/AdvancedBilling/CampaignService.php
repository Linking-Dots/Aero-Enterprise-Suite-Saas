<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\AuditService;
use Aero\Platform\Models\CouponCampaign;
use Aero\Platform\Models\CouponRedemption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    /**
     * @return LengthAwarePaginator<CouponCampaign>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return CouponCampaign::query()
            ->withCount('coupons')
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate(20);
    }

    public function create(array $data, int $actorId): CouponCampaign
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['created_by'] = $actorId;

            /** @var CouponCampaign $campaign */
            $campaign = CouponCampaign::create($data);

            $this->audit->log(
                AuditEventType::CAMPAIGN_CREATED->value,
                'campaign.create',
                $campaign,
                "Campaign [{$campaign->name}] created."
            );

            return $campaign;
        });
    }

    public function launch(CouponCampaign $campaign, int $actorId): CouponCampaign
    {
        return DB::transaction(function () use ($campaign) {
            $campaign->update(['status' => CouponCampaign::STATUS_ACTIVE]);

            $this->audit->log(
                AuditEventType::CAMPAIGN_LAUNCHED->value,
                'campaign.launch',
                $campaign,
                "Campaign [{$campaign->name}] launched."
            );

            return $campaign->refresh();
        });
    }

    public function end(CouponCampaign $campaign, int $actorId): CouponCampaign
    {
        return DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status' => CouponCampaign::STATUS_ENDED,
                'ends_at' => now(),
            ]);

            $this->audit->log(
                AuditEventType::CAMPAIGN_ENDED->value,
                'campaign.end',
                $campaign,
                "Campaign [{$campaign->name}] ended."
            );

            return $campaign->refresh();
        });
    }

    /**
     * @return Collection<int, CouponRedemption>
     */
    public function redemptions(CouponCampaign $campaign): Collection
    {
        return CouponRedemption::query()
            ->whereHas('coupon', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->with(['coupon:id,code,name', 'subscribable'])
            ->latest('redeemed_at')
            ->get();
    }
}
