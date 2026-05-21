<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PartnerCommission;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<ResellerPartner>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return ResellerPartner::query()
            ->withCount('commissions')
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn ($q) => $q->where(function ($q2) use ($filters) {
                $q2->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%');
            }))
            ->orderBy('name')
            ->paginate(25);
    }

    public function create(array $data, int $actorId): ResellerPartner
    {
        return DB::transaction(function () use ($data): ResellerPartner {
            $partner = ResellerPartner::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'commission_rate' => $data['commission_rate'] ?? 0.10,
                'status' => ResellerPartner::STATUS_PENDING,
                'portal_slug' => $data['portal_slug'] ?? null,
            ]);

            $this->audit->log(
                event: AuditEventType::PARTNER_CREATED->value,
                action: 'created',
                subject: $partner,
                description: "Reseller partner created: {$partner->name}",
            );

            return $partner;
        });
    }

    public function update(ResellerPartner $partner, array $data, int $actorId): ResellerPartner
    {
        return DB::transaction(function () use ($partner, $data): ResellerPartner {
            $before = $partner->only(['name', 'email', 'commission_rate']);

            $partner->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'commission_rate' => $data['commission_rate'] ?? null,
                'portal_slug' => $data['portal_slug'] ?? null,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                event: AuditEventType::PARTNER_UPDATED->value,
                action: 'updated',
                subject: $partner,
                description: "Reseller partner updated: {$partner->name}",
                before: $before,
                after: $partner->only(['name', 'email', 'commission_rate']),
            );

            return $partner->fresh();
        });
    }

    public function approve(ResellerPartner $partner, int $actorId): ResellerPartner
    {
        return DB::transaction(function () use ($partner, $actorId): ResellerPartner {
            $partner->update([
                'status' => ResellerPartner::STATUS_ACTIVE,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::PARTNER_APPROVED->value,
                action: 'approved',
                subject: $partner,
                description: "Reseller partner approved: {$partner->name}",
            );

            return $partner->fresh();
        });
    }

    public function suspend(ResellerPartner $partner, int $actorId): ResellerPartner
    {
        return DB::transaction(function () use ($partner): ResellerPartner {
            $partner->update(['status' => ResellerPartner::STATUS_SUSPENDED]);

            $this->audit->log(
                event: AuditEventType::PARTNER_SUSPENDED->value,
                action: 'suspended',
                subject: $partner,
                description: "Reseller partner suspended: {$partner->name}",
            );

            return $partner->fresh();
        });
    }

    /**
     * @return LengthAwarePaginator<PartnerCommission>
     */
    public function listCommissions(ResellerPartner $partner, array $filters = []): LengthAwarePaginator
    {
        return PartnerCommission::query()
            ->where('partner_id', $partner->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    /**
     * Pay out all PENDING commissions for the given partner.
     * Transitions them to 'paid' atomically and stamps paid_at.
     */
    public function processCommissionPayout(ResellerPartner $partner, int $actorId): int
    {
        return DB::transaction(function () use ($partner): int {
            $pending = PartnerCommission::query()
                ->where('partner_id', $partner->id)
                ->where('status', PartnerCommission::STATUS_PENDING)
                ->get();

            $count = $pending->count();

            foreach ($pending as $commission) {
                $commission->update([
                    'status' => PartnerCommission::STATUS_PAID,
                    'paid_at' => now(),
                ]);
            }

            $this->audit->log(
                event: AuditEventType::PARTNER_COMMISSION_PAYOUT_PROCESSED->value,
                action: 'payout_processed',
                subject: $partner,
                description: "Commission payout processed for {$partner->name}: {$count} commissions paid.",
                metadata: ['partner_id' => $partner->id, 'count' => $count],
            );

            return $count;
        });
    }

    /**
     * List tenants associated with a reseller partner.
     *
     * @return Collection<int, Tenant>
     */
    public function listPartnerTenants(ResellerPartner $partner): Collection
    {
        return Tenant::query()
            ->where('reseller_partner_id', $partner->id)
            ->get();
    }

    public function reassignTenant(string $tenantId, int $newPartnerId, int $actorId): Tenant
    {
        return DB::transaction(function () use ($tenantId, $newPartnerId): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::findOrFail($tenantId);
            $tenant->update(['reseller_partner_id' => $newPartnerId]);

            $this->audit->log(
                event: AuditEventType::PARTNER_TENANT_REASSIGNED->value,
                action: 'tenant_reassigned',
                subject: $tenant,
                description: "Tenant {$tenantId} reassigned to partner #{$newPartnerId}.",
                metadata: ['tenant_id' => $tenantId, 'new_partner_id' => $newPartnerId],
            );

            return $tenant->fresh();
        });
    }

    public function updatePortalConfig(ResellerPartner $partner, array $config, int $actorId): ResellerPartner
    {
        return DB::transaction(function () use ($partner, $config): ResellerPartner {
            $partner->update(['portal_config' => $config]);

            $this->audit->log(
                event: AuditEventType::PARTNER_PORTAL_CONFIGURED->value,
                action: 'portal_configured',
                subject: $partner,
                description: "Partner portal configured for {$partner->name}.",
            );

            return $partner->fresh();
        });
    }
}
