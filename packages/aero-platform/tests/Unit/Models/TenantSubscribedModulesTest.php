<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Models;

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
 * Audit D15 — Tenant::subscribed_product_modules accessor.
 *
 * Verifies that the accessor returns the union of baseline_modules config
 * and module codes from active/trialing product subscriptions, and that
 * cancelled/expired subscriptions are excluded.
 *
 * Uses raw DB inserts for the tenants table to bypass stancl/tenancy's
 * HasDatabase boot hook, which would try to provision a real SQLite file.
 * The accessor under test queries only the central DB (product_subscriptions
 * join products) so no tenant DB interaction is needed.
 */
class TenantSubscribedModulesTest extends TestCase
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

        // Prevent AeroMode global scope from asserting tenant context on central models.
        \Aero\Contracts\AeroMode::reset();

        // Stub baseline with deterministic values for all test cases.
        config(['hrmac.baseline_modules' => ['core', 'auth', 'hrmac']]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Insert a tenant row directly via DB::table() to bypass stancl/tenancy's
     * HasDatabase::bootHasDatabase() static creating() hook, which would try
     * to create a SQLite DB file for the new tenant. The accessor under test
     * only queries product_subscriptions/products — no tenant DB needed.
     */
    private function makeTenant(): Tenant
    {
        $id = (string) Str::uuid();
        $slug = 'test-tenant-'.Str::random(6);

        DB::table('tenants')->insert([
            'id'         => $id,
            'name'       => 'Test Tenant',
            'subdomain'  => $slug,
            'email'      => $slug.'@example.com',
            'type'       => 'business',
            'status'     => Tenant::STATUS_ACTIVE,
            'data'       => json_encode([]),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return Tenant::find($id);
    }

    private function makeProduct(string $moduleCode): Product
    {
        return Product::create([
            'code'        => 'product-'.$moduleCode,
            'module_code' => $moduleCode,
            'name'        => ucfirst($moduleCode).' Module',
            'currency'    => 'USD',
            'is_active'   => true,
        ]);
    }

    private function makeSubscription(Tenant $tenant, Product $product, array $overrides = []): ProductSubscription
    {
        // withoutEvents() prevents ProductSubscriptionObserver from firing,
        // which would dispatch ProductSubscriptionChanged → ResyncTenantModuleCatalog
        // → tenancy()->initialize() → fails on sqlite in test.
        // This test is for the accessor only — observer behaviour is in EventTest.
        return ProductSubscription::withoutEvents(
            fn () => ProductSubscription::create(array_merge([
                'tenant_id'     => $tenant->id,
                'product_id'    => $product->id,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'starts_at'     => now()->subDay(),
                'ends_at'       => null,
                'currency'      => 'USD',
                'amount'        => '99.00',
            ], $overrides))
        );
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_no_subscriptions_returns_baseline_only(): void
    {
        $tenant = $this->makeTenant();

        $result = $tenant->subscribed_product_modules;

        $this->assertEqualsCanonicalizing(['core', 'auth', 'hrmac'], $result);
    }

    public function test_active_subscription_adds_module_code(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('hrm');
        $this->makeSubscription($tenant, $product);

        $result = $tenant->subscribed_product_modules;

        $this->assertContains('hrm', $result);
        $this->assertContains('core', $result);
        $this->assertContains('auth', $result);
        $this->assertContains('hrmac', $result);
    }

    public function test_cancelled_subscription_returns_baseline_only(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('crm');
        $this->makeSubscription($tenant, $product, [
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $result = $tenant->subscribed_product_modules;

        $this->assertEqualsCanonicalizing(['core', 'auth', 'hrmac'], $result);
        $this->assertNotContains('crm', $result);
    }

    public function test_active_but_past_ends_at_returns_baseline_only(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('finance');
        $this->makeSubscription($tenant, $product, [
            'status'  => 'active',
            'ends_at' => now()->subHour(),
        ]);

        $result = $tenant->subscribed_product_modules;

        $this->assertEqualsCanonicalizing(['core', 'auth', 'hrmac'], $result);
        $this->assertNotContains('finance', $result);
    }

    public function test_trialing_with_future_trial_ends_at_grants_access(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('payroll');
        $this->makeSubscription($tenant, $product, [
            'status'        => 'trialing',
            'trial_ends_at' => now()->addDays(7),
        ]);

        $result = $tenant->subscribed_product_modules;

        $this->assertContains('payroll', $result);
        $this->assertContains('core', $result);
    }

    public function test_trialing_with_past_trial_ends_at_returns_baseline_only(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('inventory');
        $this->makeSubscription($tenant, $product, [
            'status'        => 'trialing',
            'trial_ends_at' => now()->subHour(),
        ]);

        $result = $tenant->subscribed_product_modules;

        $this->assertEqualsCanonicalizing(['core', 'auth', 'hrmac'], $result);
        $this->assertNotContains('inventory', $result);
    }

    public function test_result_has_no_duplicates_when_baseline_overlaps_product_code(): void
    {
        $tenant  = $this->makeTenant();
        $product = $this->makeProduct('core'); // same as baseline
        $this->makeSubscription($tenant, $product);

        $result = $tenant->subscribed_product_modules;

        $this->assertCount(count($result), array_unique($result), 'Result must have no duplicates.');
        $this->assertContains('core', $result);
    }
}
