<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Catalog lookups for module availability.
 *
 * Plans and products are SEPARATE subscriptions; modules are carried by
 * products (products.module_code), never by plans. The contract keeps its
 * historical name, but answers come from the product catalog.
 * Catalog data lives in the central DB — this service is the ONLY way
 * to query it from a tenant context.
 */
class PlatformPlanService implements \Aero\Contracts\PlanCatalogInterface
{
    /**
     * Get all active products that carry the given module code.
     */
    public function getPlansForModule(string $moduleCode): Collection
    {
        return Cache::remember("module_products:{$moduleCode}", 300, function () use ($moduleCode) {
            return Product::active()
                ->where('module_code', $moduleCode)
                ->get(['id', 'code', 'name', 'monthly_price', 'yearly_price']);
        });
    }

    /**
     * Check if a module is carried by any active product.
     */
    public function isModuleInAnyPlan(string $moduleCode): bool
    {
        return $this->getPlansForModule($moduleCode)->isNotEmpty();
    }
}
