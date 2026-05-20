<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\AuditService;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\PlanModule;
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
            ->with('planModules')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create a new plan with optional module config rows.
     */
    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']);

            $modules = $data['plan_modules'] ?? [];
            unset($data['plan_modules']);

            /** @var Plan $plan */
            $plan = Plan::create($data);

            $this->syncPlanModules($plan, $modules);

            $this->audit->log(
                'plan.created',
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

            $modules = $data['plan_modules'] ?? null;
            unset($data['plan_modules']);

            $plan->update($data);

            if ($modules !== null) {
                $this->syncPlanModules($plan, $modules);
            }

            $this->audit->log(
                'plan.updated',
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

            $this->audit->log('plan.deleted', $plan, "Plan [{$plan->name}] deleted.");
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

            $this->audit->log('plan.archived', $plan, "Plan [{$plan->name}] archived.");

            return $plan->fresh();
        });
    }

    /**
     * Sync plan_modules rows from an array of ['module_code' => string, 'is_enabled' => bool, 'config' => array].
     *
     * @param  array<int, array{module_code: string, is_enabled?: bool, config?: array<mixed>}>  $modules
     */
    private function syncPlanModules(Plan $plan, array $modules): void
    {
        if ($modules === []) {
            return;
        }

        // Build an indexed map for upsert
        $upsertRows = [];
        foreach ($modules as $row) {
            $upsertRows[] = [
                'plan_id' => $plan->id,
                'module_code' => $row['module_code'],
                'is_enabled' => (bool) ($row['is_enabled'] ?? true),
                'config' => isset($row['config']) ? json_encode($row['config']) : null,
            ];
        }

        PlanModule::upsert(
            $upsertRows,
            ['plan_id', 'module_code'],
            ['is_enabled', 'config']
        );
    }
}
