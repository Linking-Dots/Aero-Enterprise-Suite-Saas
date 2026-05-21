<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\AuditService;
use Aero\Platform\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    /**
     * Return a paginated list of plans for the admin grid.
     *
     * @return LengthAwarePaginator<Plan>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Plan::query()
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                });
            })
            ->withCount(['subscriptions as active_subscribers_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->paginate($perPage);
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

            $this->audit->log(AuditEventType::PLAN_DELETED->value, $plan, "Plan [{$plan->name}] deleted.");
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

            $this->audit->log(AuditEventType::PLAN_ARCHIVED->value, $plan, "Plan [{$plan->name}] archived.");

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
                $copy,
                "Plan [{$plan->name}] cloned as [{$copy->name}].",
                ['source_plan_id' => $plan->id],
                $copy->toArray()
            );

            return $copy;
        });
    }
}
