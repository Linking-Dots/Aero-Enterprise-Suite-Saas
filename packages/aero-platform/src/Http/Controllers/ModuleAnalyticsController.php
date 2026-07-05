<?php

namespace Aero\Platform\Http\Controllers;

use Aero\Core\Support\TenantCache;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Module analytics for the platform admin.
 *
 * Modules are carried by PRODUCTS (products.module_code), and a tenant's
 * module access comes from its active product subscriptions. Plans are a
 * separate subscription and carry no modules.
 */
class ModuleAnalyticsController extends Controller
{
    /**
     * Get comprehensive module analytics dashboard data.
     */
    public function index()
    {
        $analytics = TenantCache::remember('module_analytics_dashboard', 300, function () {
            return [
                'overview' => $this->getOverviewStats(),
                'module_adoption' => $this->getModuleAdoption(),
                'product_distribution' => $this->getProductDistribution(),
                'trending_modules' => $this->getTrendingModules(),
            ];
        });

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get overview statistics.
     */
    protected function getOverviewStats(): array
    {
        $totalModules = Module::where('is_active', true)->count();
        $totalTenants = Tenant::whereNotNull('id')->count();
        $activeSubscriptions = ProductSubscription::query()->hasAccess()->count();

        return [
            'total_modules' => $totalModules,
            'total_tenants' => $totalTenants,
            'active_subscriptions' => $activeSubscriptions,
            // Each active product subscription grants exactly one module.
            'avg_modules_per_tenant' => $totalTenants > 0 ? round($activeSubscriptions / $totalTenants, 2) : 0,
        ];
    }

    /**
     * Get module adoption rates from active product subscriptions.
     */
    protected function getModuleAdoption(): array
    {
        $modules = Module::where('is_active', true)->get();

        $tenantsByModule = $this->activeTenantCountsByModuleCode();

        $totalActiveTenants = ProductSubscription::query()->hasAccess()
            ->distinct('tenant_id')
            ->count('tenant_id');

        return $modules->map(function ($module) use ($tenantsByModule, $totalActiveTenants) {
            $activeTenants = $tenantsByModule[$module->code] ?? 0;

            return [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'is_core' => $module->is_core,
                'active_tenants' => $activeTenants,
                'adoption_rate' => $totalActiveTenants > 0
                    ? round(($activeTenants / $totalActiveTenants) * 100, 2)
                    : 0,
                'total_products' => Product::active()->where('module_code', $module->code)->count(),
            ];
        })->sortByDesc('adoption_rate')->values()->all();
    }

    /**
     * Get subscription distribution by product.
     */
    protected function getProductDistribution(): array
    {
        return Product::active()
            ->withCount(['subscriptions as active_subscriptions' => fn ($q) => $q->hasAccess()])
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'module_code' => $product->module_code,
                    'monthly_price' => $product->monthly_price,
                    'active_subscriptions' => $product->active_subscriptions,
                    'revenue' => ProductSubscription::query()->hasAccess()
                        ->where('product_id', $product->id)
                        ->sum('amount'),
                ];
            })
            ->sortByDesc('active_subscriptions')
            ->values()
            ->all();
    }

    /**
     * Get trending modules (most recently adopted via product subscriptions).
     */
    protected function getTrendingModules(): array
    {
        $trendingData = ProductSubscription::query()->hasAccess()
            ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
            ->where('product_subscriptions.created_at', '>=', now()->subDays(30))
            ->select(
                'products.module_code',
                DB::raw('COUNT(DISTINCT product_subscriptions.tenant_id) as new_adoptions')
            )
            ->groupBy('products.module_code')
            ->orderByDesc('new_adoptions')
            ->limit(10)
            ->get();

        $modules = Module::whereIn('code', $trendingData->pluck('module_code'))->get()->keyBy('code');

        return $trendingData->map(function ($item) use ($modules) {
            $module = $modules->get($item->module_code);

            return [
                'id' => $module?->id,
                'code' => $item->module_code,
                'name' => $module?->name ?? $item->module_code,
                'new_adoptions' => $item->new_adoptions,
            ];
        })->toArray();
    }

    /**
     * Get detailed analytics for a specific module.
     */
    public function show(Module $module)
    {
        $analytics = TenantCache::remember("module_analytics_{$module->id}", 300, function () use ($module) {
            $module->load(['subModules.components.actions']);

            $activeTenants = ProductSubscription::query()->hasAccess()
                ->whereHas('product', fn ($q) => $q->where('module_code', $module->code))
                ->distinct('tenant_id')
                ->count('tenant_id');

            $products = Product::where('module_code', $module->code)->get();

            return [
                'module' => [
                    'id' => $module->id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'description' => $module->description,
                    'is_core' => $module->is_core,
                    'is_active' => $module->is_active,
                ],
                'hierarchy' => [
                    'submodules' => $module->subModules->count(),
                    'components' => $module->subModules->sum(function ($sub) {
                        return $sub->components->count();
                    }),
                    'actions' => $module->subModules->sum(function ($sub) {
                        return $sub->components->sum(function ($comp) {
                            return $comp->actions->count();
                        });
                    }),
                ],
                'usage' => [
                    'active_tenants' => $activeTenants,
                    'total_products' => $products->count(),
                    'products' => $products->map(fn (Product $product) => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'active_subscriptions' => $product->subscriptions()->hasAccess()->count(),
                    ]),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get module usage trends over time (active product subscriptions per day).
     */
    public function trends(Request $request)
    {
        $days = (int) $request->input('days', 30);

        $trends = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $activeSubs = ProductSubscription::whereIn('status', ['active', 'trialing'])
                ->whereDate('created_at', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $date);
                })
                ->count();

            $trends[] = [
                'date' => $date,
                'active_subscriptions' => $activeSubs,
            ];
        }

        return response()->json([
            'success' => true,
            'trends' => $trends,
        ]);
    }

    /**
     * Active-tenant counts per module code, from active product subscriptions.
     *
     * @return array<string, int>
     */
    protected function activeTenantCountsByModuleCode(): array
    {
        return ProductSubscription::query()->hasAccess()
            ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
            ->select(
                'products.module_code',
                DB::raw('COUNT(DISTINCT product_subscriptions.tenant_id) as tenants')
            )
            ->groupBy('products.module_code')
            ->pluck('tenants', 'module_code')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
