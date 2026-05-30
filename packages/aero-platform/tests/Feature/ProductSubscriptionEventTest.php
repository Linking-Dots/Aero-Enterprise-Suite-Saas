<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Observers\ProductSubscriptionObserver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Audit D15 — ProductSubscriptionObserver + ProductSubscriptionChanged event.
 *
 * Verifies that the observer fires the correct event action strings on:
 *   - create            → 'created'
 *   - active → cancelled → 'cancelled'
 *   - cancelled → active → 'reactivated'
 *   - unrelated field update → no event
 */
class ProductSubscriptionEventTest extends TestCase
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
            // Register sqlite manager so HasDatabase::bootHasDatabase() does not throw.
            'tenancy.database.managers' => [
                'sqlite' => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
                'mysql'  => \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            ],
            'tenancy.database.prefix' => '',
            'tenancy.database.suffix' => '',
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

        $packages = realpath(__DIR__.'/../../..');

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

        // Prevent stancl/tenancy from trying to provision a real DB in sqlite tests.
        \Aero\Contracts\AeroMode::reset();

        // Ensure the observer is registered for these tests.
        ProductSubscription::observe(ProductSubscriptionObserver::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTenant(): Tenant
    {
        // Raw DB insert bypasses stancl/tenancy's HasDatabase boot hook which
        // would try to provision a real SQLite file for each tenant.
        $id   = (string) \Illuminate\Support\Str::uuid();
        $slug = 'evt-tenant-'.\Illuminate\Support\Str::random(6);

        \Illuminate\Support\Facades\DB::table('tenants')->insert([
            'id'         => $id,
            'name'       => 'Event Test Tenant',
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

    private function makeProduct(): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create([
            'code' => 'product-evt-'.$counter,
            'module_code' => 'module-evt-'.$counter,
            'name' => 'Event Test Module '.$counter,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    private function createSubscription(Tenant $tenant, Product $product, array $overrides = []): ProductSubscription
    {
        return ProductSubscription::create(array_merge([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'currency' => 'USD',
            'amount' => '99.00',
        ], $overrides));
    }

    /**
     * Create a subscription as test SETUP, bypassing the observer.
     *
     * Without this, the create() would fire ProductSubscriptionChanged → the
     * real ResyncTenantModuleCatalog listener → tenancy()->initialize() →
     * Artisan::call('hrmac:sync-modules') → sqlite-backed schema lookups
     * against an uninitialized tenant context. Setup state must not trigger
     * the production lifecycle; Event::fake() must come AFTER setup.
     */
    private function createSubscriptionSetup(Tenant $tenant, Product $product, array $overrides = []): ProductSubscription
    {
        return ProductSubscription::withoutEvents(
            fn () => $this->createSubscription($tenant, $product, $overrides)
        );
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_creating_subscription_dispatches_created_action(): void
    {
        Event::fake([ProductSubscriptionChanged::class]);

        $tenant = $this->makeTenant();
        $product = $this->makeProduct();
        $this->createSubscription($tenant, $product);

        Event::assertDispatched(ProductSubscriptionChanged::class, function (ProductSubscriptionChanged $e): bool {
            return $e->action === 'created';
        });
    }

    public function test_updating_status_active_to_cancelled_dispatches_cancelled(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeProduct();
        $sub = $this->createSubscriptionSetup($tenant, $product, ['status' => 'active']);

        Event::fake([ProductSubscriptionChanged::class]);

        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        Event::assertDispatched(ProductSubscriptionChanged::class, function (ProductSubscriptionChanged $e): bool {
            return $e->action === 'cancelled';
        });
    }

    public function test_updating_status_cancelled_to_active_dispatches_reactivated(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeProduct();
        $sub = $this->createSubscriptionSetup($tenant, $product, ['status' => 'cancelled', 'cancelled_at' => now()]);

        Event::fake([ProductSubscriptionChanged::class]);

        $sub->update(['status' => 'active', 'cancelled_at' => null]);

        Event::assertDispatched(ProductSubscriptionChanged::class, function (ProductSubscriptionChanged $e): bool {
            return $e->action === 'reactivated';
        });
    }

    public function test_updating_unrelated_field_does_not_dispatch_event(): void
    {
        $tenant = $this->makeTenant();
        $product = $this->makeProduct();
        $sub = $this->createSubscriptionSetup($tenant, $product, ['status' => 'active', 'amount' => '99.00']);

        Event::fake([ProductSubscriptionChanged::class]);

        $sub->update(['amount' => '199.00']);

        Event::assertNotDispatched(ProductSubscriptionChanged::class);
    }
}
