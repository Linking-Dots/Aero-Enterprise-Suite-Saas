<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformPlanService implements \Aero\Contracts\PlanCatalogInterface
{
    /**
     * Get all plans that include the given module code.
     * Plans live in the central DB — this service is the ONLY way
     * to query plan data from a tenant context.
     */
    public function getPlansForModule(string $moduleCode): Collection
    {
        return Cache::remember("plan_modules:{$moduleCode}", 300, function () use ($moduleCode) {
            return Plan::active()
                ->whereHas('modules', fn ($q) => $q->where('module_code', $moduleCode))
                ->get(['id', 'name', 'slug', 'monthly_price', 'yearly_price']);
        });
    }

    /**
     * Check if a module is included in any active plan.
     */
    public function isModuleInAnyPlan(string $moduleCode): bool
    {
        return $this->getPlansForModule($moduleCode)->isNotEmpty();
    }
}
