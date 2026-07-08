<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    /**
     * Statuses surfaced on the admin tenant list, in display order. Grouped counts
     * for every status power the KPI strip and the command-shell context rail.
     */
    private const LIST_STATUSES = [
        'active', 'trial', 'pending', 'provisioning', 'suspended', 'failed', 'archived',
    ];

    /**
     * Unified command-centre payload for the Tenants operating view: normalised
     * tenant rows (plan + MRR + outstanding resolved), KPI stats, a signups trend,
     * a plan distribution and KPI sparklines. Guard-free query builder so it stays
     * context-agnostic — mirrors InvoiceAdminService::overview().
     *
     * Honesty: MRR and plan come from the active/trialing base subscription;
     * outstanding comes from the invoices ledger. No seat/usage/storage figures
     * are fabricated — there is no real source for them on this schema.
     *
     * @return array{stats: array, tenants: array, trend: array, planMix: array, sparks: array}
     */
    public function overview(): array
    {
        $tenants = DB::table('tenants')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get([
                'id', 'name', 'type', 'is_demo', 'subdomain', 'email', 'status', 'currency',
                'stripe_id', 'pm_type', 'pm_last_four', 'byoc_enabled',
                'suspended_at', 'suspension_reason', 'created_at',
            ]);

        $ids = $tenants->pluck('id')->all();
        $subs = $this->planSubscriptionsFor($ids);
        $outstanding = $this->outstandingFor($ids);

        $rows = $tenants->map(function ($t) use ($subs, $outstanding) {
            $sub = $subs->get($t->id);
            $due = (float) ($outstanding[$t->id] ?? 0);

            return [
                'id'                => (string) $t->id,
                'name'              => $t->name ?: '—',
                'type'              => $t->type ?: 'company',
                'is_demo'           => (bool) $t->is_demo,
                'subdomain'         => $t->subdomain,
                'domain'            => $t->subdomain ? $t->subdomain.'.aeos365.com' : null,
                'email'             => $t->email,
                'status'            => $t->status,
                'currency'          => $t->currency ?: ($sub->currency ?? 'USD'),
                'plan'              => $sub->plan_name ?? null,
                'mrr'               => $this->normalizeMrr($sub),
                'outstanding'       => round($due, 2),
                'has_card'          => $t->pm_last_four !== null,
                'pm_last_four'      => $t->pm_last_four,
                'byoc_enabled'      => (bool) $t->byoc_enabled,
                'suspended_at'      => $t->suspended_at,
                'suspension_reason' => $t->suspension_reason,
                'created_at'        => $t->created_at,
            ];
        })->all();

        return [
            'stats'   => $this->overviewStats($rows),
            'tenants' => $rows,
            'trend'   => $this->buildSignupTrend($rows),
            'planMix' => $this->buildPlanMix($rows),
            'sparks'  => $this->buildSparks($rows),
        ];
    }

    /**
     * KPI + donut metrics derived from the normalised rows: grouped status counts,
     * headline totals, demo/live split, summed MRR (monthly-normalized) and total
     * outstanding. All numerics cast at the boundary.
     *
     * @param  array<int, array>  $rows
     * @return array<string, int|float|array>
     */
    private function overviewStats(array $rows): array
    {
        $countWhere = fn (callable $p) => count(array_filter($rows, $p));

        $byStatus = [];
        foreach (self::LIST_STATUSES as $s) {
            $byStatus[$s] = $countWhere(fn ($r) => $r['status'] === $s);
        }
        $known = array_sum($byStatus);

        return [
            'total'        => count($rows),
            'active'       => $byStatus['active'],
            'trial'        => $byStatus['trial'],
            'suspended'    => $byStatus['suspended'],
            'pending'      => $byStatus['pending'],
            'provisioning' => $byStatus['provisioning'],
            'failed'       => $byStatus['failed'],
            'archived'     => $byStatus['archived'],
            // Statuses outside the display set (cancelled/frozen) still count to the total.
            'other'        => max(0, count($rows) - $known),
            'demo'         => $countWhere(fn ($r) => $r['is_demo']),
            'live'         => $countWhere(fn ($r) => ! $r['is_demo']),
            'mrr'          => round(array_sum(array_map(fn ($r) => (float) ($r['mrr'] ?? 0), $rows)), 2),
            'outstanding'  => round(array_sum(array_map(fn ($r) => $r['outstanding'], $rows)), 2),
            'owing'        => $countWhere(fn ($r) => $r['outstanding'] > 0),
            'byStatus'     => $byStatus,
        ];
    }

    /**
     * Signups per calendar month over the trailing 6 months (ending at the latest
     * signup), plus the running cumulative total. Pure derivation from created_at.
     *
     * @param  array<int, array>  $rows
     * @return array{labels: array, signups: array, cumulative: array}
     */
    private function buildSignupTrend(array $rows): array
    {
        $months = $this->monthWindow($rows);
        $keys = array_map(fn ($m) => $m->format('Y-m'), $months);
        $perMonth = array_fill_keys($keys, 0);

        foreach ($rows as $r) {
            if ($r['created_at'] !== null) {
                $k = Carbon::parse($r['created_at'])->format('Y-m');
                if (isset($perMonth[$k])) {
                    $perMonth[$k]++;
                }
            }
        }

        // Cumulative = every tenant created on or before each month's end.
        $windowStart = $months[0]->copy()->startOfMonth();
        $priorCount = count(array_filter($rows, fn ($r) => $r['created_at'] !== null
            && Carbon::parse($r['created_at'])->lt($windowStart)));

        $cumulative = [];
        $running = $priorCount;
        foreach ($keys as $k) {
            $running += $perMonth[$k];
            $cumulative[] = $running;
        }

        return [
            'labels'     => array_map(fn ($m) => $m->format('M'), $months),
            'signups'    => array_values($perMonth),
            'cumulative' => $cumulative,
        ];
    }

    /**
     * Plan distribution across tenants that carry an active/trialing subscription,
     * with the summed monthly-normalized MRR per plan. Tenants with no plan are
     * omitted (they surface via the "No plan" facet on the client).
     *
     * @param  array<int, array>  $rows
     * @return array<int, array{plan: string, count: int, mrr: float}>
     */
    private function buildPlanMix(array $rows): array
    {
        $mix = [];
        foreach ($rows as $r) {
            if (! $r['plan']) {
                continue;
            }
            $mix[$r['plan']] ??= ['plan' => $r['plan'], 'count' => 0, 'mrr' => 0.0];
            $mix[$r['plan']]['count']++;
            $mix[$r['plan']]['mrr'] += (float) ($r['mrr'] ?? 0);
        }

        $out = array_map(fn ($m) => ['plan' => $m['plan'], 'count' => $m['count'], 'mrr' => round($m['mrr'], 2)], array_values($mix));
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $out;
    }

    /**
     * KPI sparkline series (6 months). Each point is a real cumulative derivation
     * by signup month: total tenants, and the currently-active / currently-trial /
     * currently-suspended cohorts, live tenants, and cumulative MRR. This shows how
     * each segment grew — it does not reconstruct historical status transitions.
     *
     * @param  array<int, array>  $rows
     * @return array{total: array, active: array, trial: array, suspended: array, live: array, mrr: array}
     */
    private function buildSparks(array $rows): array
    {
        $months = $this->monthWindow($rows);

        $cum = function (callable $pred, bool $sumMrr = false) use ($rows, $months) {
            $series = [];
            foreach ($months as $m) {
                $end = $m->copy()->endOfMonth();
                $acc = 0.0;
                foreach ($rows as $r) {
                    if ($r['created_at'] === null || ! $pred($r)) {
                        continue;
                    }
                    if (Carbon::parse($r['created_at'])->lte($end)) {
                        $acc += $sumMrr ? (float) ($r['mrr'] ?? 0) : 1;
                    }
                }
                $series[] = $sumMrr ? round($acc, 2) : (int) $acc;
            }

            return $series;
        };

        return [
            'total'     => $cum(fn () => true),
            'active'    => $cum(fn ($r) => $r['status'] === 'active'),
            'trial'     => $cum(fn ($r) => $r['status'] === 'trial'),
            'suspended' => $cum(fn ($r) => $r['status'] === 'suspended'),
            'live'      => $cum(fn ($r) => ! $r['is_demo']),
            'mrr'       => $cum(fn () => true, true),
        ];
    }

    /**
     * Trailing-6-month window (ending at the latest signup month) as Carbon
     * startOfMonth instances — mirrors the invoices centre window helper.
     *
     * @param  array<int, array>  $rows
     * @return array<int, Carbon>
     */
    private function monthWindow(array $rows): array
    {
        $latest = null;
        foreach ($rows as $r) {
            if ($r['created_at'] !== null) {
                $c = Carbon::parse($r['created_at']);
                if ($latest === null || $c->gt($latest)) {
                    $latest = $c;
                }
            }
        }
        $end = ($latest ?? now())->copy()->startOfMonth();

        return array_map(fn ($i) => $end->copy()->subMonths($i), range(5, 0));
    }

    /**
     * Outstanding balance (open/overdue/issued invoice amount_due) per tenant,
     * keyed by billable_id. Guarded so a missing invoices table can't break the
     * overview.
     *
     * @param  array<int, string>  $tenantIds
     * @return array<string, float>
     */
    private function outstandingFor(array $tenantIds): array
    {
        if (empty($tenantIds)) {
            return [];
        }

        try {
            return DB::table('invoices')
                ->whereIn('billable_id', $tenantIds)
                ->whereNull('deleted_at')
                ->whereIn('status', ['open', 'overdue', 'issued'])
                ->where('amount_due', '>', 0)
                ->selectRaw('billable_id, SUM(amount_due) as due')
                ->groupBy('billable_id')
                ->pluck('due', 'billable_id')
                ->map(fn ($v) => (float) $v)
                ->all();
        } catch (\Illuminate\Database\QueryException) {
            return [];
        }
    }

    /**
     * Drawer detail for a single tenant: subscription snapshot, its invoices, and
     * the platform audit activity. Every read is guarded so a missing table can't
     * 500 the drawer — mirrors InvoiceAdminService::detail().
     *
     * @return array{subscription: ?array, invoices: array, activity: array}
     */
    public function detail(string $tenantId): array
    {
        $tenant = DB::table('tenants')->where('id', $tenantId)->first(['id', 'currency']);
        abort_if($tenant === null, 404);

        $subscription = DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.tenant_id', $tenantId)
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.created_at')
            ->first(['p.name as plan', 's.status', 's.amount', 's.billing_cycle', 's.currency', 's.trial_ends_at', 's.created_at']);

        $subOut = $subscription === null ? null : [
            'plan'       => $subscription->plan ?: 'No plan',
            'status'     => $subscription->status,
            'amount'     => (float) $subscription->amount,
            'cycle'      => $subscription->billing_cycle,
            'currency'   => $subscription->currency ?: ($tenant->currency ?: 'USD'),
            'trial_ends' => $subscription->trial_ends_at,
            'started_at' => $subscription->created_at,
        ];

        $invoices = [];
        try {
            $invoices = DB::table('invoices')
                ->where('billable_id', $tenantId)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(['invoice_number', 'reference', 'status', 'total', 'amount_due', 'currency', 'due_date', 'created_at'])
                ->map(fn ($i) => [
                    'number'     => $i->invoice_number ?: ($i->reference ?: '—'),
                    'status'     => $i->status === 'issued' ? 'open' : $i->status,
                    'total'      => (float) $i->total,
                    'amount_due' => (float) $i->amount_due,
                    'currency'   => $i->currency ?: 'USD',
                    'due_date'   => $i->due_date,
                    'created_at' => $i->created_at,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // invoices table absent in this context
        }

        // Activity from the central platform audit trail (or tenant audit_logs in
        // standalone). Same guarded dual-source pattern the invoices drawer uses.
        $activity = [];
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $activity = DB::connection($conn)->table($table)
                ->where('subject_type', 'like', '%Tenant%')
                ->where('subject_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['event_type', 'action', 'description', 'actor_name', 'created_at'])
                ->map(fn ($a) => [
                    'event'  => $a->event_type,
                    'action' => $a->action,
                    'detail' => $a->description,
                    'actor'  => $a->actor_name,
                    'at'     => $a->created_at,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // audit table absent in this context
        }

        return [
            'subscription' => $subOut,
            'invoices'     => $invoices,
            'activity'     => $activity,
        ];
    }

    /**
     * Every tenant flattened for CSV export.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(): array
    {
        return array_map(fn ($r) => [
            'name'        => $r['name'],
            'subdomain'   => $r['subdomain'] ?? '',
            'email'       => $r['email'] ?? '',
            'type'        => $r['is_demo'] ? 'demo' : $r['type'],
            'status'      => $r['status'],
            'plan'        => $r['plan'] ?? '',
            'currency'    => $r['currency'],
            'mrr'         => $r['mrr'] ?? '',
            'outstanding' => $r['outstanding'],
            'created_at'  => $r['created_at'] ?? '',
        ], $this->overview()['tenants']);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $q = Tenant::query()->with('domains');

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['plan_id'])) {
            $planId = $filters['plan_id'];
            $q->whereHas('currentSubscription', fn ($sq) => $sq->where('plan_id', $planId));
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('subdomain', 'like', "%{$s}%")
                ->orWhere('id', $s));
        }

        $paginator = $q->orderByDesc('created_at')
            ->paginate(config('aero-platform.admin_page_size', 25))
            ->withQueryString();

        // Enrich the current page with plan + monthly-normalized MRR from the base
        // plan subscription (active/trialing basis), fetched in one query to avoid
        // N+1. Map to an explicit array contract so sensitive tenant columns
        // (byoc credentials, Stripe ids) never reach the client.
        $subs = $this->planSubscriptionsFor(collect($paginator->items())->pluck('id')->all());

        $paginator->through(function (Tenant $t) use ($subs) {
            $sub = $subs->get($t->id);
            $primary = $t->domains->firstWhere('is_primary', true) ?? $t->domains->first();

            return [
                'id'         => $t->id,
                'name'       => $t->name,
                'subdomain'  => $t->subdomain,
                'email'      => $t->email,
                'status'     => $t->status,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'domain'     => $primary->domain
                    ?? ($t->subdomain ? $t->subdomain.'.aeos365.com' : null),
                'plan'       => $sub->plan_name ?? null,
                'currency'   => $sub->currency ?? null,
                'mrr'        => $this->normalizeMrr($sub),
            ];
        });

        return $paginator;
    }

    /**
     * KPI + rail metrics: grouped status counts, total, active count, and headline
     * MRR (active + trialing subscriptions, monthly-normalized, summed raw as
     * USD-equivalent for the demo). All numerics cast at the boundary so the
     * frontend never receives a decimal string.
     */
    public function stats(): array
    {
        $byStatus = Tenant::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $counts = [];
        foreach (self::LIST_STATUSES as $s) {
            $counts[$s] = (int) ($byStatus[$s] ?? 0);
        }

        $mrr = DB::table('subscriptions')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('deleted_at')
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'yearly' THEN amount / 12 ELSE amount END) as mrr")
            ->value('mrr');

        return [
            'total'    => (int) $byStatus->sum(),
            'active'   => $counts['active'],
            'mrr'      => round((float) $mrr, 2),
            'byStatus' => $counts,
        ];
    }

    /**
     * The active/trialing base plan subscription for each given tenant, keyed by
     * tenant_id, with the plan name joined in. Uses the direct tenant_id column
     * (indexed) — the same source the platform dashboard reads.
     */
    private function planSubscriptionsFor(array $tenantIds): \Illuminate\Support\Collection
    {
        if (empty($tenantIds)) {
            return collect();
        }

        return DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->whereIn('s.tenant_id', $tenantIds)
            ->whereIn('s.status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.created_at')
            ->select('s.tenant_id', 'p.name as plan_name', 's.amount', 's.billing_cycle', 's.currency')
            ->get()
            ->unique('tenant_id') // newest-first order preserved; keep the most recent sub per tenant
            ->keyBy('tenant_id');
    }

    /** Monthly-normalize a subscription row's charged amount (yearly → /12). */
    private function normalizeMrr(?object $sub): ?float
    {
        if (! $sub || $sub->amount === null) {
            return null;
        }

        $amount = (float) $sub->amount;

        return round($sub->billing_cycle === 'yearly' ? $amount / 12 : $amount, 2);
    }

    public function show(string $tenantId): Tenant
    {
        return Tenant::with([
            'domains',
            'provisioningLogs' => fn ($q) => $q->latest()->limit(20),
        ])->findOrFail($tenantId);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'status' => 'provisioning',
                'byoc_enabled' => $data['byoc_enabled'] ?? false,
            ]);

            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'step' => 'queued',
                'message' => 'Tenant created, provisioning queued',
            ]);

            if (! empty($data['product_id'])) {
                ProductSubscription::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $data['product_id'],
                    'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                    'amount' => 0, // set by billing flow; 0 for admin-created tenants
                    'currency' => 'USD',
                    'status' => 'trialing',
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(14),
                ]);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_CREATED->value,
                action: 'create',
                subject: $tenant,
                description: "Tenant {$tenant->name} created"
            );

            return $tenant;
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn ($v) => $v !== null);

            if (! empty($updateData)) {
                $tenant->update($updateData);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_UPDATED->value,
                action: 'update',
                subject: $tenant,
                description: "Tenant {$tenant->name} updated"
            );

            return $tenant->fresh();
        });
    }

    public function suspend(Tenant $tenant, string $reason): Tenant
    {
        if ($tenant->status === 'suspended') {
            throw new \DomainException('Tenant is already suspended');
        }

        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_SUSPENDED->value,
                action: 'suspend',
                subject: $tenant,
                description: "Suspended: {$reason}"
            );

            return $tenant->fresh();
        });
    }

    public function activate(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
                'frozen_at' => null,
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_ACTIVATED->value,
                action: 'activate',
                subject: $tenant,
                description: "Tenant {$tenant->name} activated"
            );

            return $tenant->fresh();
        });
    }

    public function freeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'frozen', 'frozen_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_FROZEN->value,
                action: 'freeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} frozen"
            );

            return $tenant->fresh();
        });
    }

    public function unfreeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'frozen_at' => null]);

            $this->audit->log(
                event: AuditEventType::TENANT_UNFROZEN->value,
                action: 'unfreeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} unfrozen"
            );

            return $tenant->fresh();
        });
    }

    public function archive(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'archived', 'archived_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_ARCHIVED->value,
                action: 'archive',
                subject: $tenant,
                description: "Tenant {$tenant->name} archived"
            );

            return $tenant->fresh();
        });
    }

    public function restore(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'archived_at' => null]);

            $this->audit->log(
                event: AuditEventType::TENANT_RESTORED->value,
                action: 'restore',
                subject: $tenant,
                description: "Tenant {$tenant->name} restored"
            );

            return $tenant->fresh();
        });
    }

    public function purge(Tenant $tenant, int $actorId): void
    {
        DB::transaction(function () use ($tenant, $actorId) {
            $this->audit->log(
                event: AuditEventType::TENANT_PURGED->value,
                action: 'delete',
                subject: $tenant,
                description: "Tenant {$tenant->name} purged by user {$actorId}"
            );

            // TODO(P-10): Dispatch TenantPurgeJob to drop tenant DB, revoke domains, cancel Stripe subscription
            $tenant->delete();
        });
    }

    public function updateByocCredentials(Tenant $tenant, array $credentials): Tenant
    {
        return DB::transaction(function () use ($tenant, $credentials) {
            $tenant->update([
                'byoc_enabled' => true,
                'byoc_db_host' => $credentials['host'],
                'byoc_db_name' => $credentials['database'],
                'byoc_db_username' => $credentials['username'],
                'byoc_db_password' => $credentials['password'],
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_BYOC_UPDATED->value,
                action: 'update',
                subject: $tenant,
                description: "BYOC credentials updated for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
