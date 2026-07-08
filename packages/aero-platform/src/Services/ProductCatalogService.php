<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Assembles the data for the platform Products (Catalog) command centre.
 *
 * Reads the module registry via the query builder (not the HRMAC Module model)
 * so it is free of the HRMAC context guard and safe to call in any scope. All
 * sources are central: products, product_modules (bundles), product_subscriptions
 * (adoption + MRR), modules (registry), tenants (adoption denominator).
 */
class ProductCatalogService
{
    /** @return array{kpis: array, lifecycle: array, products: array, systemModules: array} */
    public function overview(): array
    {
        $tenantsTotal = max(1, (int) DB::table('tenants')->count());

        return [
            'kpis'          => $this->kpis($tenantsTotal),
            'lifecycle'     => $this->lifecycle($tenantsTotal),
            'products'      => $this->products($tenantsTotal),
            'systemModules' => $this->systemModules(),
        ];
    }

    /** Active/trialing subscription aggregates keyed by product_id. */
    private function subscriptionAggregates()
    {
        return DB::table('product_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->select('product_id', DB::raw('COUNT(*) as subs'), DB::raw('COALESCE(SUM(amount),0) as mrr'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    /** @return array<int, array> */
    private function products(int $tenantsTotal): array
    {
        $agg = $this->subscriptionAggregates();
        $bundles = DB::table('product_modules')->get()->groupBy('product_id');

        return Product::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) use ($agg, $bundles, $tenantsTotal): array {
                $a = $agg[$p->id] ?? null;
                $subs = $a ? (int) $a->subs : 0;
                $mrr = $a ? (float) $a->mrr : 0.0;

                $modules = isset($bundles[$p->id])
                    ? $bundles[$p->id]->pluck('module_code')->filter()->values()->all()
                    : [];
                if ($modules === [] && $p->module_code) {
                    $modules = [$p->module_code];
                }

                return [
                    'id'                     => $p->id,
                    'code'                   => $p->code,
                    'name'                   => $p->name,
                    'icon'                   => $p->icon,
                    'modules'                => $modules,
                    'monthly_price'          => (float) $p->monthly_price,
                    'yearly_price'           => (float) $p->yearly_price,
                    'currency'               => $p->currency,
                    'is_active'              => (bool) $p->is_active,
                    'is_marketplace_visible' => (bool) $p->is_marketplace_visible,
                    'subscriptions'          => $subs,
                    'mrr'                    => round($mrr, 2),
                    'adoption_pct'           => (int) round($subs / $tenantsTotal * 100),
                    'tenants_total'          => $tenantsTotal,
                ];
            })
            ->all();
    }

    /**
     * Foundation / infrastructure modules — the demoted "system" tray. Bundled
     * with every tenant, never sold. Read guard-free via the query builder.
     *
     * @return array<int, array>
     */
    private function systemModules(): array
    {
        return DB::table('modules')
            ->where('is_core', true)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('name')
            ->get(['code', 'name', 'category'])
            ->map(fn ($m) => [
                'code'     => $m->code,
                'name'     => $m->name,
                'category' => $m->category,
            ])
            ->all();
    }

    /** @return array<string, int|float|string> */
    private function kpis(int $tenantsTotal): array
    {
        $productsTotal = (int) Product::query()->count();
        $liveProducts = (int) Product::query()->where('is_active', true)->count();

        $entitledTenants = (int) DB::table('product_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->distinct()
            ->count('tenant_id');

        $moduleMrr = (float) DB::table('product_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->sum('amount');

        // Health = share of active products that actually grant at least one module.
        $activeProductIds = Product::query()->where('is_active', true)->pluck('id');
        $withModules = DB::table('product_modules')
            ->whereIn('product_id', $activeProductIds)
            ->distinct()
            ->count('product_id');
        $health = $liveProducts > 0 ? (int) round($withModules / $liveProducts * 100) : 100;

        return [
            'products_total' => $productsTotal,
            'live_products'  => $liveProducts,
            'adoption_pct'   => (int) round($entitledTenants / $tenantsTotal * 100),
            'entitled_tenants' => $entitledTenants,
            'tenants_total'  => $tenantsTotal,
            'module_mrr'     => round($moduleMrr, 2),
            'catalog_health' => $health,
        ];
    }

    /** @return array<string, int> Counts of modules at each lifecycle stage. */
    private function lifecycle(int $tenantsTotal): array
    {
        $modulesTotal = (int) DB::table('modules')->where('is_active', true)->count();
        $sellable = (int) Product::query()->where('is_active', true)->count();

        $entitledTenants = (int) DB::table('product_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->distinct()
            ->count('tenant_id');

        return [
            'developed'        => $modulesTotal,
            'cataloged'        => $modulesTotal,
            'sellable'         => $sellable,
            'entitled_tenants' => $entitledTenants,
            'active'           => $entitledTenants,
            'tenants_total'    => $tenantsTotal,
        ];
    }
}
