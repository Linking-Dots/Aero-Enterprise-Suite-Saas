<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Models;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Listeners\RecordProductEntitlementLedger;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 — bundle-aware entitlement + the tenant_entitlements ledger.
 *
 * Covers the two new behaviours introduced with product_modules:
 *   1. A product that bundles several modules grants ALL of them (not just a
 *      single scalar module_code).
 *   2. RecordProductEntitlementLedger appends an open grant row per bundled
 *      module on 'created'/'reactivated', and closes them on 'cancelled'.
 *
 * Reuses the in-memory sqlite harness from TenantSubscribedModulesTest (FKs off,
 * so pivot rows can reference module codes without seeding the modules table).
 */
class ProductBundleEntitlementTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->refreshTestDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
            'tenancy.database.central_connection' => 'sqlite',
        ]);
        $this->app['db']->purge('sqlite');
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function refreshTestDatabase(): void
    {
        Schema::dropAllTables();

        $packages = realpath(__DIR__.'/../../../..');

        foreach ([
            $packages.'/aero-core/database/migrations',
            $packages.'/aero-auth/database/migrations',
            $packages.'/aero-hrmac/database/migrations',
            $packages.'/aero-platform/database/migrations',
        ] as $path) {
            /** @var Migrator $migrator */
            $migrator = $this->app['migrator'];
            $migrator->setConnection('sqlite');
            if (! $migrator->repositoryExists()) {
                $migrator->getRepository()->createRepository();
            }
            $migrator->run([$path]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        \Aero\Contracts\AeroMode::reset();
        config(['hrmac.baseline_modules' => ['core', 'auth', 'hrmac']]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTenant(): Tenant
    {
        $id = (string) Str::uuid();
        $slug = 'test-tenant-'.Str::random(6);

        DB::table('tenants')->insert([
            'id' => $id, 'name' => 'Test Tenant', 'subdomain' => $slug,
            'email' => $slug.'@example.com', 'type' => 'business',
            'status' => Tenant::STATUS_ACTIVE, 'data' => json_encode([]),
            'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
        ]);

        return Tenant::find($id);
    }

    /** Seed a module into the registry so product_modules.module_code FK holds. */
    private function ensureModule(string $code): void
    {
        DB::table('modules')->insertOrIgnore([
            'code' => $code,
            'name' => ucfirst($code),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * A product whose scalar module_code is its primary module, plus extra
     * bundled modules attached via the product_modules pivot.
     *
     * @param  array<int, string>  $bundledModules
     */
    private function makeBundledProduct(string $primary, array $bundledModules): Product
    {
        $product = Product::create([
            'code' => 'product-'.$primary,
            'module_code' => $primary,
            'name' => ucfirst($primary).' Suite',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        foreach (array_unique(array_merge([$primary], $bundledModules)) as $code) {
            $this->ensureModule($code);
            DB::table('product_modules')->insert([
                'product_id' => $product->id,
                'module_code' => $code,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $product;
    }

    private function makeSubscription(Tenant $tenant, Product $product, array $overrides = []): ProductSubscription
    {
        return ProductSubscription::withoutEvents(
            fn () => ProductSubscription::create(array_merge([
                'tenant_id' => $tenant->id, 'product_id' => $product->id,
                'billing_cycle' => 'monthly', 'status' => 'active',
                'starts_at' => now()->subDay(), 'ends_at' => null,
                'currency' => 'USD', 'amount' => '99.00',
            ], $overrides))
        );
    }

    // -------------------------------------------------------------------------
    // Bundle resolution
    // -------------------------------------------------------------------------

    public function test_bundled_product_grants_all_its_modules(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeBundledProduct('hrm', ['finance', 'crm']);
        $this->makeSubscription($tenant, $product);

        $result = $tenant->subscribed_product_modules;

        $this->assertContains('hrm', $result);
        $this->assertContains('finance', $result);
        $this->assertContains('crm', $result);
        $this->assertContains('core', $result); // baseline still present
    }

    public function test_cancelled_bundled_subscription_returns_baseline_only(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeBundledProduct('hrm', ['finance']);
        $this->makeSubscription($tenant, $product, ['status' => 'cancelled', 'cancelled_at' => now()]);

        $result = $tenant->subscribed_product_modules;

        $this->assertEqualsCanonicalizing(['core', 'auth', 'hrmac'], $result);
        $this->assertNotContains('finance', $result);
    }

    // -------------------------------------------------------------------------
    // Ledger writer
    // -------------------------------------------------------------------------

    public function test_ledger_records_open_grant_per_bundled_module_on_create(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeBundledProduct('hrm', ['finance']);
        $sub = $this->makeSubscription($tenant, $product);

        (new RecordProductEntitlementLedger)->handle(new ProductSubscriptionChanged($sub, 'created'));

        $open = DB::table('tenant_entitlements')
            ->where('tenant_id', $tenant->id)
            ->whereNull('revoked_at')
            ->pluck('module_code')
            ->all();

        $this->assertContains('hrm', $open);
        $this->assertContains('finance', $open);
    }

    public function test_ledger_grant_is_idempotent(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeBundledProduct('hrm', []);
        $sub = $this->makeSubscription($tenant, $product);

        $listener = new RecordProductEntitlementLedger;
        $listener->handle(new ProductSubscriptionChanged($sub, 'created'));
        $listener->handle(new ProductSubscriptionChanged($sub, 'created')); // re-fire

        $count = DB::table('tenant_entitlements')
            ->where('source_id', $sub->id)
            ->where('module_code', 'hrm')
            ->whereNull('revoked_at')
            ->count();

        $this->assertSame(1, $count, 'Re-firing created must not duplicate open grants.');
    }

    public function test_ledger_closes_grants_on_cancel(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeBundledProduct('hrm', ['finance']);
        $sub = $this->makeSubscription($tenant, $product);

        $listener = new RecordProductEntitlementLedger;
        $listener->handle(new ProductSubscriptionChanged($sub, 'created'));
        $listener->handle(new ProductSubscriptionChanged($sub, 'cancelled'));

        $open = DB::table('tenant_entitlements')->whereNull('revoked_at')->count();
        $closed = DB::table('tenant_entitlements')->whereNotNull('revoked_at')->count();

        $this->assertSame(0, $open, 'All grants should be revoked after cancel.');
        $this->assertSame(2, $closed, 'Both bundled-module grants should be closed.');
    }
}
