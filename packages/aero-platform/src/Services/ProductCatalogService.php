<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    public function __construct(private AuditServiceInterface $audit) {}

    /** @return array{kpis: array, lifecycle: array, products: array, systemModules: array, moduleOptions: array} */
    public function overview(): array
    {
        $tenantsTotal = max(1, (int) DB::table('tenants')->count());

        return [
            'kpis'          => $this->kpis($tenantsTotal),
            'lifecycle'     => $this->lifecycle($tenantsTotal),
            'products'      => $this->products($tenantsTotal),
            'systemModules' => $this->systemModules(),
            'moduleOptions' => $this->moduleOptions(),
        ];
    }

    /**
     * Modules an admin can bundle into a product — non-core, active modules
     * (foundation modules are never sold). Guard-free via the query builder.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function moduleOptions(): array
    {
        return DB::table('modules')
            ->where('is_active', true)
            ->where('is_core', false)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn ($m) => ['code' => $m->code, 'name' => $m->name])
            ->all();
    }

    /**
     * Create or update a product and sync its bundled modules (product_modules).
     * Keeps the legacy products.module_code scalar pointed at the primary (first)
     * bundled module for the BC readers that still use it, and busts the
     * entitlement caches of every tenant subscribed to the product.
     *
     * @param  array<string, mixed>  $data  validated: name, code?, description?,
     *         monthly_price, yearly_price, is_active, is_marketplace_visible, modules[]
     */
    public function save(array $data, ?string $id = null): Product
    {
        return DB::transaction(function () use ($data, $id): Product {
            $product = $id ? Product::findOrFail($id) : new Product;
            $isNew = ! $product->exists;

            $modules = array_values(array_unique(array_filter((array) ($data['modules'] ?? []))));
            $primary = $modules[0] ?? ($product->module_code ?? null);

            $product->fill([
                'name'                   => $data['name'],
                'description'            => $data['description'] ?? null,
                'monthly_price'          => $data['monthly_price'] ?? 0,
                'yearly_price'           => $data['yearly_price'] ?? 0,
                'currency'               => $data['currency'] ?? 'USD',
                'is_active'              => (bool) ($data['is_active'] ?? true),
                'is_marketplace_visible' => (bool) ($data['is_marketplace_visible'] ?? true),
                'module_code'            => $primary,
            ]);
            if ($isNew) {
                $product->code = $data['code'] ?: Str::slug($data['name']);
            }
            $product->save();

            // Re-sync the pivot to exactly the submitted set.
            DB::table('product_modules')->where('product_id', $product->id)->delete();
            $now = now();
            foreach ($modules as $code) {
                DB::table('product_modules')->insert([
                    'product_id' => $product->id, 'module_code' => $code,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $this->bustEntitlementCaches($product->id);

            $this->audit->log(
                event: $isNew ? 'platform.products.created' : 'platform.products.updated',
                action: $isNew ? 'create' : 'edit',
                subject: $product,
                description: "Product {$product->code} ".($isNew ? 'created' : 'updated')." — modules: ".implode(',', $modules),
            );

            return $product->refresh();
        });
    }

    /**
     * Soft-delete (retire) a product. Refuses if it still has active/trialing
     * subscriptions — you deactivate a live product, you don't delete it out from
     * under paying customers (industry norm). Cleans the pivot + busts caches.
     *
     * @throws \RuntimeException when active subscriptions exist
     */
    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $product = Product::findOrFail($id);

            $active = (int) DB::table('product_subscriptions')
                ->where('product_id', $id)
                ->whereIn('status', ['active', 'trialing'])
                ->count();

            if ($active > 0) {
                throw new \RuntimeException("Cannot delete — {$active} active subscription(s). Deactivate the product instead.");
            }

            DB::table('product_modules')->where('product_id', $id)->delete();
            $this->bustEntitlementCaches($id);
            $product->delete();

            $this->audit->log(
                event: 'platform.products.deleted',
                action: 'delete',
                subject: $product,
                description: "Product {$product->code} deleted",
            );
        });
    }

    /** Bust per-tenant entitlement caches for every tenant subscribed to a product. */
    private function bustEntitlementCaches(string $productId): void
    {
        DB::table('product_subscriptions')
            ->where('product_id', $productId)
            ->distinct()
            ->pluck('tenant_id')
            ->each(function ($tid): void {
                Cache::forget("tenant_subscribed_modules:{$tid}");
                Cache::forget("module_entitlement:{$tid}");
            });
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
