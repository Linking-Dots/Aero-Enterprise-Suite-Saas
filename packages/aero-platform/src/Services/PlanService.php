<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

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
