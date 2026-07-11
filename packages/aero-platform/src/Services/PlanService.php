<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /** Currency symbols kept in sync with the plans.currency column. */
    private const CURRENCY_SYMBOL = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'BDT' => '৳', 'AUD' => 'A$', 'CAD' => 'C$'];

    /**
     * Unified command-centre payload for the Plans catalog: normalised plan rows
     * (subscribers + MRR resolved), KPI stats, price ladder, subscriber / MRR
     * distributions and KPI sparklines. Guard-free — mirrors the Tenants and
     * Invoices command centres.
     *
     * Honesty: subscriber counts and MRR come from the subscriptions ledger; no
     * usage/revenue figure is fabricated.
     *
     * @return array{stats: array, plans: array, ladder: array, sparks: array}
     */
    public function overview(): array
    {
        $plans = Plan::query()
            ->withCount([
                'subscriptions as active_subscribers_count' => fn ($q) => $q->where('status', Subscription::STATUS_ACTIVE),
                'subscriptions as trial_subscribers_count' => fn ($q) => $q->where('status', Subscription::STATUS_TRIALING),
            ])
            ->orderBy('sort_order')
            ->get();

        $mrrByPlan = $this->mrrByPlan($plans->pluck('id')->all());

        $rows = $plans->map(function (Plan $p) use ($mrrByPlan) {
            $features = is_array($p->features) ? $p->features : [];

            return [
                'id'             => (string) $p->id,
                'name'           => $p->name,
                'slug'           => $p->slug,
                'tier'           => $p->tier ?: 'free',
                'status'         => $p->status ?: ($p->is_active ? 'active' : 'draft'),
                'is_public'      => (bool) $p->is_public,
                'is_featured'    => (bool) $p->is_featured,
                'currency'       => $p->currency ?: 'USD',
                'price_monthly'  => (float) ($p->price_monthly ?? $p->monthly_price),
                'price_annual'   => (float) ($p->price_annual ?? $p->yearly_price),
                'trial_days'     => (int) ($p->trial_days ?? 0),
                'grace_days'     => (int) ($p->grace_days ?? 0),
                'max_users'      => (int) ($p->max_users ?? 0),
                'max_storage_gb' => (int) ($p->max_storage_gb ?? 0),
                // limits JSON carries the AI allowance (max_ai_messages / ai_model)
                // so the editor can prefill it.
                'limits'         => is_array($p->limits) ? $p->limits : [],
                'downgrade_policy'    => $p->downgrade_policy,
                'cancellation_policy' => $p->cancellation_policy,
                'description'    => $p->description,
                'features'       => array_values(array_filter($features, 'is_string')),
                'features_count' => count($features),
                'sort_order'     => (int) ($p->sort_order ?? 0),
                'active_subs'    => (int) $p->active_subscribers_count,
                'trial_subs'     => (int) $p->trial_subscribers_count,
                'mrr'            => round((float) ($mrrByPlan[$p->id] ?? 0.0), 2),
            ];
        })->all();

        return [
            'stats'  => $this->overviewStats($rows),
            'plans'  => $rows,
            'ladder' => $this->buildLadder($rows),
            'sparks' => $this->buildSparks(),
        ];
    }

    /**
     * KPI metrics from the normalised rows: catalog totals, public/private split,
     * subscriber + trial counts, plan MRR/ARR and featured count.
     *
     * @param  array<int, array>  $rows
     * @return array<string, int|float>
     */
    private function overviewStats(array $rows): array
    {
        $count = fn (callable $p) => count(array_filter($rows, $p));
        $mrr = round(array_sum(array_map(fn ($r) => $r['mrr'], $rows)), 2);

        return [
            'total'       => count($rows),
            'active'      => $count(fn ($r) => $r['status'] === 'active'),
            'draft'       => $count(fn ($r) => $r['status'] === 'draft'),
            'archived'    => $count(fn ($r) => $r['status'] === 'archived'),
            'public'      => $count(fn ($r) => $r['is_public']),
            'private'     => $count(fn ($r) => ! $r['is_public']),
            'featured'    => $count(fn ($r) => $r['is_featured']),
            'free'        => $count(fn ($r) => $r['price_monthly'] <= 0),
            'subscribers' => array_sum(array_map(fn ($r) => $r['active_subs'], $rows)),
            'trials'      => array_sum(array_map(fn ($r) => $r['trial_subs'], $rows)),
            'mrr'         => $mrr,
            'arr'         => round($mrr * 12, 2),
        ];
    }

    /**
     * Price-ladder rows (monthly price + subscriber count per plan), ordered by
     * price so the client can render the ascending tier ladder.
     *
     * @param  array<int, array>  $rows
     * @return array<int, array{name: string, tier: string, price: float, subs: int, mrr: float}>
     */
    private function buildLadder(array $rows): array
    {
        $ladder = array_map(fn ($r) => [
            'name'  => $r['name'],
            'slug'  => $r['slug'],
            'tier'  => $r['tier'],
            'price' => $r['price_monthly'],
            'subs'  => $r['active_subs'] + $r['trial_subs'],
            'mrr'   => $r['mrr'],
        ], $rows);

        usort($ladder, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $ladder;
    }

    /**
     * KPI sparkline series (6 months) from the subscriptions ledger: cumulative
     * active subscribers and cumulative MRR by subscription start month. Real
     * derivation — every point comes from subscriptions.created_at.
     *
     * @return array{subscribers: array<int,int>, mrr: array<int,float>}
     */
    private function buildSparks(): array
    {
        $end = now()->startOfMonth();
        $months = array_map(fn ($i) => $end->copy()->subMonths($i), range(5, 0));

        $subs = DB::table('subscriptions')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('deleted_at')
            ->get(['created_at', 'billing_cycle', 'amount']);

        $subSeries = [];
        $mrrSeries = [];
        foreach ($months as $m) {
            $cut = $m->copy()->endOfMonth();
            $c = 0;
            $mrr = 0.0;
            foreach ($subs as $s) {
                if ($s->created_at !== null && Carbon::parse($s->created_at)->lte($cut)) {
                    $c++;
                    $mrr += $s->billing_cycle === 'yearly' ? (float) $s->amount / 12 : (float) $s->amount;
                }
            }
            $subSeries[] = $c;
            $mrrSeries[] = round($mrr, 2);
        }

        return ['subscribers' => $subSeries, 'mrr' => $mrrSeries];
    }

    /**
     * Drawer detail for a single plan: subscriber list (tenant names), revenue
     * roll-up and audit activity. Guarded so a missing table can't 500 the drawer.
     *
     * @return array{subscribers: array, revenue: array, activity: array}
     */
    public function detail(string $planId): array
    {
        $plan = Plan::query()->findOrFail($planId);

        $subscribers = DB::table('subscriptions as s')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->where('s.plan_id', $planId)
            ->whereIn('s.status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.created_at')
            ->limit(100)
            ->get(['t.name as tenant', 's.status', 's.amount', 's.billing_cycle', 's.currency', 's.created_at'])
            ->map(fn ($s) => [
                'tenant'   => $s->tenant ?: '—',
                'status'   => $s->status,
                'amount'   => (float) $s->amount,
                'cycle'    => $s->billing_cycle,
                'currency' => $s->currency ?: ($plan->currency ?: 'USD'),
                'since'    => $s->created_at,
            ])->all();

        $active = array_filter($subscribers, fn ($s) => $s['status'] === Subscription::STATUS_ACTIVE);
        $mrr = (float) ($this->mrrByPlan([$planId])[$planId] ?? 0.0);
        $revenue = [
            'mrr'   => round($mrr, 2),
            'arr'   => round($mrr * 12, 2),
            'arpu'  => count($active) > 0 ? round($mrr / count($active), 2) : 0.0,
            'active' => count($active),
            'trial'  => count($subscribers) - count($active),
        ];

        $activity = [];
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $activity = DB::connection($conn)->table($table)
                ->where('subject_type', 'like', '%Plan%')
                ->where('subject_id', $planId)
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

        return ['subscribers' => $subscribers, 'revenue' => $revenue, 'activity' => $activity];
    }

    /** Toggle a plan's public visibility on the pricing page. */
    public function setPublic(Plan $plan, bool $public): Plan
    {
        return DB::transaction(function () use ($plan, $public) {
            $plan->update(['is_public' => $public, 'visibility' => $public ? 'public' : 'private']);

            $this->audit->log(
                AuditEventType::PLAN_UPDATED->value,
                $public ? 'published' : 'unpublished',
                $plan,
                "Plan [{$plan->name}] ".($public ? 'published to' : 'hidden from').' the pricing page.'
            );

            return $plan->fresh();
        });
    }

    /** Toggle a plan's featured (recommended) flag. */
    public function setFeatured(Plan $plan, bool $featured): Plan
    {
        return DB::transaction(function () use ($plan, $featured) {
            $plan->update(['is_featured' => $featured]);

            $this->audit->log(
                AuditEventType::PLAN_UPDATED->value,
                $featured ? 'featured' : 'unfeatured',
                $plan,
                "Plan [{$plan->name}] ".($featured ? 'marked as featured.' : 'unfeatured.')
            );

            return $plan->fresh();
        });
    }

    /**
     * Persist a new display order for the public pricing page. Each id gets its
     * 1-based position; unknown ids are ignored.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $i => $id) {
                Plan::where('id', $id)->update(['sort_order' => $i + 1]);
            }

            $this->audit->log(
                AuditEventType::PLAN_UPDATED->value,
                'reordered',
                null,
                'Plan display order updated ('.count($orderedIds).' plans).'
            );
        });
    }

    /**
     * All plans flattened for CSV export.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(): array
    {
        return array_map(fn ($r) => [
            'name'        => $r['name'],
            'slug'        => $r['slug'],
            'tier'        => $r['tier'],
            'status'      => $r['status'],
            'visibility'  => $r['is_public'] ? 'public' : 'private',
            'featured'    => $r['is_featured'] ? 'yes' : 'no',
            'monthly'     => $r['price_monthly'],
            'annual'      => $r['price_annual'],
            'currency'    => $r['currency'],
            'subscribers' => $r['active_subs'],
            'trials'      => $r['trial_subs'],
            'mrr'         => $r['mrr'],
        ], $this->overview()['plans']);
    }

    /**
     * Return a paginated list of plans for the admin grid.
     *
     * Maps to an explicit array contract (decimals → float) so the client never
     * receives Stripe ids or other sensitive plan columns, and prices arrive as
     * numbers rather than decimal strings.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $paginator = Plan::query()
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['tier']), fn ($q) => $q->where('tier', $filters['tier']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                });
            })
            ->withCount(['subscriptions as active_subscribers_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();

        $mrrByPlan = $this->mrrByPlan(collect($paginator->items())->pluck('id')->all());

        $paginator->through(fn (Plan $plan) => [
            'id'                       => $plan->id,
            'name'                     => $plan->name,
            'slug'                     => $plan->slug,
            'tier'                     => $plan->tier,
            'status'                   => $plan->status,
            'price_monthly'            => (float) $plan->price_monthly,
            'price_annual'             => (float) $plan->price_annual,
            'currency'                 => $plan->currency ?? 'USD',
            'is_public'                => (bool) $plan->is_public,
            'features_count'           => $plan->features_count,
            'active_subscribers_count' => (int) $plan->active_subscribers_count,
            'mrr'                      => (float) ($mrrByPlan[$plan->id] ?? 0.0),
        ]);

        return $paginator;
    }

    /**
     * Headline stats for the KPI strip + context rail. Mirrors the shape of
     * TenantAdminService::stats(): catalog totals, public/private split, active
     * subscribers, monthly-normalized plan MRR, and per-tier subscriber counts.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $counts = Plan::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw('SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public')
            ->first();

        $total  = (int) ($counts->total ?? 0);
        $public = (int) ($counts->public ?? 0);

        $subscribers = (int) DB::table('subscriptions')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->count();

        $mrr = (float) DB::table('subscriptions')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('deleted_at')
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'yearly' THEN amount / 12 ELSE amount END) as mrr")
            ->value('mrr');

        // Active subscribers grouped by the plan's tier — powers the rail overview.
        $byTier = DB::table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.status', Subscription::STATUS_ACTIVE)
            ->whereNull('s.deleted_at')
            ->groupBy('p.tier')
            ->selectRaw('p.tier as tier, COUNT(*) as subs')
            ->pluck('subs', 'tier');

        return [
            'total'       => $total,
            'active'      => (int) ($counts->active ?? 0),
            'public'      => $public,
            'private'     => max(0, $total - $public),
            'subscribers' => $subscribers,
            'mrr'         => round($mrr, 2),
            'byTier'      => [
                'free'         => (int) ($byTier['free'] ?? 0),
                'starter'      => (int) ($byTier['starter'] ?? 0),
                'professional' => (int) ($byTier['professional'] ?? 0),
                'enterprise'   => (int) ($byTier['enterprise'] ?? 0),
            ],
        ];
    }

    /**
     * Monthly-normalized MRR per plan (active + trialing basis), keyed by plan id.
     * One grouped query — no N+1.
     *
     * @param  array<int, string>  $planIds
     * @return \Illuminate\Support\Collection<string, float>
     */
    private function mrrByPlan(array $planIds): \Illuminate\Support\Collection
    {
        if (empty($planIds)) {
            return collect();
        }

        return DB::table('subscriptions')
            ->whereIn('plan_id', $planIds)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->whereNull('deleted_at')
            ->groupBy('plan_id')
            ->selectRaw("plan_id, SUM(CASE WHEN billing_cycle = 'yearly' THEN amount / 12 ELSE amount END) as mrr")
            ->pluck('mrr', 'plan_id')
            ->map(fn ($v) => (float) $v);
    }

    /**
     * Return all active public plans (for plan-picker UI).
     *
     * @return Collection<int, Plan>
     */
    public function publicPlans(): Collection
    {
        return Plan::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create a new plan.
     */
    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']);

            /** @var Plan $plan */
            $plan = Plan::create($data);

            $this->audit->log(
                AuditEventType::PLAN_CREATED->value,
                'created',
                $plan,
                "Plan [{$plan->name}] created.",
                null,
                $plan->toArray()
            );

            return $plan;
        });
    }

    /**
     * Update an existing plan.
     */
    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $old = $plan->toArray();

            $plan->update($data);

            $this->audit->log(
                AuditEventType::PLAN_UPDATED->value,
                'updated',
                $plan,
                "Plan [{$plan->name}] updated.",
                $old,
                $plan->fresh()->toArray()
            );

            return $plan->fresh();
        });
    }

    /**
     * Soft-delete a plan. Refuses if active subscriptions exist.
     */
    public function delete(Plan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $activeCount = $plan->subscriptions()->where('status', 'active')->count();

            if ($activeCount > 0) {
                throw new \RuntimeException(
                    "Cannot delete plan [{$plan->name}] — it has {$activeCount} active subscription(s)."
                );
            }

            $this->audit->log(AuditEventType::PLAN_DELETED->value, 'deleted', $plan, "Plan [{$plan->name}] deleted.");
            $plan->delete();
        });
    }

    /**
     * Archive (deactivate) a plan by setting status = 'archived'.
     */
    public function archive(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $plan->update(['status' => 'archived', 'is_active' => false, 'is_public' => false]);

            $this->audit->log(AuditEventType::PLAN_ARCHIVED->value, 'archived', $plan, "Plan [{$plan->name}] archived.");

            return $plan->fresh();
        });
    }

    /**
     * Duplicate a plan. The clone is created as a draft with a modified name/slug and no active subscriptions.
     */
    public function clone(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $baseName = $plan->name.' (Copy)';
            $baseSlug = Str::slug($baseName);

            // Ensure unique slug
            $slug = $baseSlug;
            $counter = 1;
            while (Plan::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }

            $cloneData = $plan->only([
                'billing_cycle',
                'price',
                'currency',
                'trial_days',
                'features',
                'max_users',
                'max_storage_gb',
                'is_public',
                'sort_order',
                'metadata',
            ]);

            $cloneData['name'] = $baseName;
            $cloneData['slug'] = $slug;
            $cloneData['status'] = 'draft';
            $cloneData['is_active'] = false;

            /** @var Plan $copy */
            $copy = Plan::create($cloneData);

            $this->audit->log(
                AuditEventType::PLAN_CLONED->value,
                'cloned',
                $copy,
                "Plan [{$plan->name}] cloned as [{$copy->name}].",
                ['source_plan_id' => $plan->id],
                $copy->toArray()
            );

            return $copy;
        });
    }
}
