<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\Audit\AuditEventType;
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

            $modules = $data['plan_modules'] ?? null;
            unset($data['plan_modules']);

            $plan->update($data);

            if ($modules !== null) {
                $this->syncPlanModules($plan, $modules);
            }

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
     * Duplicate a plan and its module assignments.
     * The clone is created as a draft with a modified name/slug and no active subscriptions.
     */
    public function clone(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $plan->loadMissing('planModules');

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

            // Copy module assignments
            $modules = $plan->planModules->map(fn ($pm) => [
                'module_code' => $pm->module_code,
                'is_enabled' => (bool) $pm->is_enabled,
                'config' => $pm->config,
            ])->all();

            $this->syncPlanModules($copy, $modules);

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

    /**
     * Replace all module assignments for a plan with the provided list.
     *
     * @param  array<int, array{module_code: string, is_enabled?: bool, config?: array<mixed>}>  $modules
     */
    public function assignModules(Plan $plan, array $modules): void
    {
        DB::transaction(function () use ($plan, $modules) {
            // Full replacement — delete existing rows first
            PlanModule::where('plan_id', $plan->id)->delete();

            $this->syncPlanModules($plan, $modules);

            $this->audit->log(
                'plan.modules_assigned',
                $plan,
                "Modules reassigned for plan [{$plan->name}].",
                null,
                ['modules' => $modules]
            );
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
