<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PartnerCommission;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * Everything the Partners command centre needs in one payload:
     * mapped partner roster, the full commission ledger, KPI stats,
     * status mix, MRR leaderboard, payouts-due queue and the
     * earned-vs-paid trend.
     */
    public function overview(): array
    {
        $conn = central_connection();
        $partners = ResellerPartner::query()->orderBy('name')->get();
        $commissions = PartnerCommission::query()->orderByDesc('created_at')->get();

        // Partner-managed tenants + their live plan MRR (same MRR definition
        // as Billing/Analytics: active+trialing subscriptions × plan price).
        $tenantRows = DB::connection($conn)->table('tenants')
            ->whereNotNull('reseller_partner_id')->whereNull('deleted_at')
            ->get(['id', 'name', 'subdomain', 'status', 'reseller_partner_id', 'created_at']);
        $mrrByTenant = $tenantRows->isEmpty() ? collect() : DB::connection($conn)->table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereNull('s.deleted_at')->whereIn('s.status', ['active', 'trialing'])
            ->whereIn('s.tenant_id', $tenantRows->pluck('id'))
            ->selectRaw('s.tenant_id, SUM(p.price_monthly) mrr')
            ->groupBy('s.tenant_id')->pluck('mrr', 'tenant_id');

        $tenantsByPartner = $tenantRows->groupBy('reseller_partner_id');
        $commByPartner = $commissions->groupBy('partner_id');
        $unpaid = [PartnerCommission::STATUS_PENDING, PartnerCommission::STATUS_APPROVED];

        $mapped = $partners->map(function (ResellerPartner $p) use ($tenantsByPartner, $commByPartner, $mrrByTenant, $unpaid): array {
            $tenants = $tenantsByPartner->get($p->id, collect());
            $comm = $commByPartner->get($p->id, collect());
            $owedRows = $comm->whereIn('status', $unpaid);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email,
                'status' => $p->status,
                'rate_pct' => round((float) $p->commission_rate * 100, 2),
                'portal_slug' => $p->portal_slug,
                'portal_config' => $p->portal_config ?? [],
                'portal_url' => $p->portal_slug ? 'https://partners.aeos365.com/'.$p->portal_slug : null,
                'tenants_count' => $tenants->count(),
                'mrr' => round($tenants->sum(fn ($t) => (float) ($mrrByTenant[$t->id] ?? 0)), 2),
                'owed' => round((float) $owedRows->sum('amount'), 2),
                'owed_entries' => $owedRows->count(),
                'paid_ltd' => round((float) $comm->where('status', PartnerCommission::STATUS_PAID)->sum('amount'), 2),
                'entries' => $comm->count(),
                'approved_at' => $p->approved_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $tenantNames = DB::connection($conn)->table('tenants')
            ->whereIn('id', $commissions->pluck('tenant_id')->unique())
            ->pluck('name', 'id');
        $partnerNames = $partners->pluck('name', 'id');

        $ledger = $commissions->map(fn (PartnerCommission $c): array => [
            'id' => $c->id,
            'partner_id' => $c->partner_id,
            'partner' => $partnerNames[$c->partner_id] ?? '—',
            'tenant_id' => $c->tenant_id,
            'tenant' => $tenantNames[$c->tenant_id] ?? $c->tenant_id,
            'invoice' => $c->invoice_id ? sprintf('INV-%04d', $c->invoice_id) : null,
            'amount' => (float) $c->amount,
            'status' => $c->status,
            'created_at' => $c->created_at?->toIso8601String(),
            'paid_at' => $c->paid_at?->toIso8601String(),
        ])->values()->all();

        $byStatus = $partners->groupBy('status')->map->count();
        $owedAll = $commissions->whereIn('status', $unpaid);
        $paidAll = $commissions->where('status', PartnerCommission::STATUS_PAID);

        $stats = [
            'total' => $partners->count(),
            'active' => (int) ($byStatus[ResellerPartner::STATUS_ACTIVE] ?? 0),
            'pending' => (int) ($byStatus[ResellerPartner::STATUS_PENDING] ?? 0),
            'suspended' => (int) ($byStatus[ResellerPartner::STATUS_SUSPENDED] ?? 0),
            'managed_tenants' => $tenantRows->count(),
            'channel_mrr' => round((float) $mrrByTenant->sum(), 2),
            'owed' => round((float) $owedAll->sum('amount'), 2),
            'owed_entries' => $owedAll->count(),
            'paid_ltd' => round((float) $paidAll->sum('amount'), 2),
            'paid_entries' => $paidAll->count(),
            'paid_90d' => round((float) $paidAll->filter(fn ($c) => $c->paid_at && $c->paid_at->gte(now()->subDays(90)))->sum('amount'), 2),
        ];

        $statusMix = [
            ['status' => 'active', 'label' => 'Active', 'count' => $stats['active']],
            ['status' => 'pending', 'label' => 'Pending', 'count' => $stats['pending']],
            ['status' => 'suspended', 'label' => 'Suspended', 'count' => $stats['suspended']],
        ];

        $top = collect($mapped)->where('status', ResellerPartner::STATUS_ACTIVE)
            ->sortByDesc('mrr')->take(6)
            ->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'], 'mrr' => $p['mrr'], 'tenants' => $p['tenants_count'], 'paid' => $p['paid_ltd']])
            ->values()->all();

        $queue = collect($mapped)->filter(fn ($p) => $p['owed'] > 0 && $p['status'] === ResellerPartner::STATUS_ACTIVE)
            ->sortByDesc('owed')
            ->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'], 'owed' => $p['owed'], 'entries' => $p['owed_entries']])
            ->values()->all();

        return [
            'partners' => $mapped,
            'commissions' => $ledger,
            'stats' => $stats,
            'statusMix' => $statusMix,
            'top' => $top,
            'queue' => $queue,
            'trend' => $this->earnedVsPaidTrend($commissions),
            'sparks' => [
                'partners' => $this->weeklyCounts($partners, 'created_at'),
                'earned' => $this->weeklySums($commissions, 'created_at'),
                'paid' => $this->weeklySums($paidAll, 'paid_at'),
            ],
        ];
    }

    /**
     * Commission earned (by created month) vs paid (by paid month) — 6 months.
     */
    private function earnedVsPaidTrend(Collection $commissions): array
    {
        $labels = [];
        $earned = [];
        $paid = [];
        $now = CarbonImmutable::now()->startOfMonth();

        for ($i = 5; $i >= 0; $i--) {
            $m = $now->subMonths($i);
            $labels[] = $m->format('M');
            $earned[] = round((float) $commissions->filter(fn ($c) => $c->created_at && $c->created_at->format('Y-m') === $m->format('Y-m'))->sum('amount'), 2);
            $paid[] = round((float) $commissions->filter(fn ($c) => $c->status === PartnerCommission::STATUS_PAID && $c->paid_at && $c->paid_at->format('Y-m') === $m->format('Y-m'))->sum('amount'), 2);
        }

        return ['labels' => $labels, 'earned' => $earned, 'paid' => $paid];
    }

    /** Row counts per week over the last 8 weeks (oldest → newest). */
    private function weeklyCounts(Collection $rows, string $field): array
    {
        return $this->weeklyAgg($rows, $field, fn ($bucket) => $bucket->count());
    }

    /** Amount sums per week over the last 8 weeks (oldest → newest). */
    private function weeklySums(Collection $rows, string $field): array
    {
        return $this->weeklyAgg($rows, $field, fn ($bucket) => round((float) $bucket->sum('amount'), 2));
    }

    private function weeklyAgg(Collection $rows, string $field, callable $agg): array
    {
        $out = [];
        $now = CarbonImmutable::now();
        for ($i = 7; $i >= 0; $i--) {
            $start = $now->subWeeks($i)->startOfWeek();
            $end = $start->endOfWeek();
            $out[] = $agg($rows->filter(fn ($r) => $r->{$field} && $r->{$field}->betweenIncluded($start, $end)));
        }

        return $out;
    }

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
     * Pay out every unpaid (pending OR approved) commission for the partner.
     * Transitions them to 'paid' atomically and stamps paid_at. Approved
     * entries used to be stranded here — a payout must settle both states.
     */
    public function processCommissionPayout(ResellerPartner $partner, int $actorId): int
    {
        return DB::transaction(function () use ($partner): int {
            $unpaid = PartnerCommission::query()
                ->where('partner_id', $partner->id)
                ->whereIn('status', [PartnerCommission::STATUS_PENDING, PartnerCommission::STATUS_APPROVED])
                ->get();

            $count = $unpaid->count();
            $total = (float) $unpaid->sum('amount');

            foreach ($unpaid as $commission) {
                $commission->update([
                    'status' => PartnerCommission::STATUS_PAID,
                    'paid_at' => now(),
                ]);
            }

            $this->audit->log(
                event: AuditEventType::PARTNER_COMMISSION_PAYOUT_PROCESSED->value,
                action: 'payout_processed',
                subject: $partner,
                description: "Commission payout processed for {$partner->name}: {$count} commissions paid (\${$total}).",
                metadata: ['partner_id' => $partner->id, 'count' => $count, 'total' => $total],
            );

            return $count;
        });
    }

    /**
     * Approve a single pending commission entry.
     */
    public function approveCommission(PartnerCommission $commission, int $actorId): PartnerCommission
    {
        return DB::transaction(function () use ($commission): PartnerCommission {
            $commission->update(['status' => PartnerCommission::STATUS_APPROVED]);

            $this->audit->log(
                event: AuditEventType::PARTNER_COMMISSION_APPROVED->value,
                action: 'commission_approved',
                subject: $commission,
                description: "Commission #{$commission->id} (\${$commission->amount}) approved for partner #{$commission->partner_id}.",
                metadata: ['partner_id' => $commission->partner_id, 'amount' => (float) $commission->amount],
            );

            return $commission->fresh();
        });
    }

    /**
     * Mark a single unpaid commission entry paid.
     */
    public function payCommission(PartnerCommission $commission, int $actorId): PartnerCommission
    {
        return DB::transaction(function () use ($commission): PartnerCommission {
            $commission->update([
                'status' => PartnerCommission::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::PARTNER_COMMISSION_PAID->value,
                action: 'commission_paid',
                subject: $commission,
                description: "Commission #{$commission->id} (\${$commission->amount}) paid for partner #{$commission->partner_id}.",
                metadata: ['partner_id' => $commission->partner_id, 'amount' => (float) $commission->amount],
            );

            return $commission->fresh();
        });
    }

    /**
     * Tenants managed by a reseller partner, with plan + live MRR,
     * in the shape the console's drawer consumes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPartnerTenants(ResellerPartner $partner): array
    {
        $conn = central_connection();
        $tenants = DB::connection($conn)->table('tenants')
            ->where('reseller_partner_id', $partner->id)->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'subdomain', 'status', 'created_at']);

        $subs = $tenants->isEmpty() ? collect() : DB::connection($conn)->table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereNull('s.deleted_at')->whereIn('s.status', ['active', 'trialing'])
            ->whereIn('s.tenant_id', $tenants->pluck('id'))
            ->get(['s.tenant_id', 'p.name as plan', 'p.price_monthly'])
            ->keyBy('tenant_id');

        return $tenants->map(fn ($t): array => [
            'id' => $t->id,
            'name' => $t->name,
            'subdomain' => $t->subdomain,
            'status' => $t->status,
            'plan' => $subs[$t->id]->plan ?? null,
            'mrr' => (float) ($subs[$t->id]->price_monthly ?? 0),
            'since' => $t->created_at,
        ])->values()->all();
    }

    /**
     * Assign a tenant to a partner, or detach it (null = direct-managed).
     */
    public function reassignTenant(string $tenantId, ?int $newPartnerId, int $actorId): Tenant
    {
        return DB::transaction(function () use ($tenantId, $newPartnerId): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::findOrFail($tenantId);
            $tenant->update(['reseller_partner_id' => $newPartnerId]);

            $this->audit->log(
                event: AuditEventType::PARTNER_TENANT_REASSIGNED->value,
                action: 'tenant_reassigned',
                subject: $tenant,
                description: $newPartnerId
                    ? "Tenant {$tenantId} reassigned to partner #{$newPartnerId}."
                    : "Tenant {$tenantId} detached from its reseller partner.",
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
