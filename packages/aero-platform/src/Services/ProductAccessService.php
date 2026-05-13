<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Facades\Cache;

class ProductAccessService implements \Aero\Contracts\ProductAccessInterface
{
    /**
     * Check if a tenant has an active product subscription granting access
     * to the given module code. 'core' is always accessible.
     * If no Product record exists for the module code, access is granted
     * (graceful fallback for unregistered/development modules).
     */
    public function tenantCanAccessModule(string $tenantId, string $moduleCode): bool
    {
        if ($moduleCode === 'core') {
            return true;
        }

        $cacheKey = "product_access:{$tenantId}:{$moduleCode}";

        return $this->cacheRemember(
            ["tenant:{$tenantId}", 'product-access'],
            $cacheKey,
            300,
            function () use ($tenantId, $moduleCode) {
                $product = Product::active()
                    ->where('module_code', $moduleCode)
                    ->first();

                if ($product === null) {
                    return true;
                }

                return ProductSubscription::where('tenant_id', $tenantId)
                    ->where('product_id', $product->id)
                    ->hasAccess()
                    ->exists();
            }
        );
    }

    public function getAccessibleModuleCodes(string $tenantId): array
    {
        $cacheKey = "product_access:all:{$tenantId}";

        return $this->cacheRemember(
            ["tenant:{$tenantId}", 'product-access'],
            $cacheKey,
            300,
            function () use ($tenantId) {
                $accessible = ['core'];

                $subscribedProductIds = ProductSubscription::where('tenant_id', $tenantId)
                    ->hasAccess()
                    ->pluck('product_id');

                $moduleCodes = Product::whereIn('id', $subscribedProductIds)
                    ->pluck('module_code')
                    ->toArray();

                return array_merge($accessible, $moduleCodes);
            }
        );
    }

    public function flushCache(string $tenantId): void
    {
        try {
            Cache::tags(["tenant:{$tenantId}", 'product-access'])->flush();
        } catch (\BadMethodCallException) {
            // Cache driver does not support tags (e.g., file, database) — clear by individual keys
            Cache::forget("product_access:{$tenantId}:*");
            Cache::forget("product_access:all:{$tenantId}");
        }
    }

    /**
     * Cache::tags()-based remember with fallback to plain Cache for non-Redis drivers.
     */
    private function cacheRemember(array $tags, string $key, int $ttl, \Closure $callback): mixed
    {
        try {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\BadMethodCallException) {
            return Cache::remember($key, $ttl, $callback);
        }
    }
}
