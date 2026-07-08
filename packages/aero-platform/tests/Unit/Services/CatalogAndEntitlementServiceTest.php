<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\EntitlementOverrideService;
use Aero\Platform\Services\ProductCatalogService;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Service-level coverage for the two new admin services:
 *   • ProductCatalogService — create/update (bundle sync) + delete (subscription guard)
 *   • EntitlementOverrideService — grant (idempotent) + revoke (append-only ledger)
 *
 * Reuses the in-memory sqlite harness (FKs off) from the model tests.
 */
class CatalogAndEntitlementServiceTest extends TestCase
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
        // No-op audit so the services under test don't need the real audit stack.
        $this->app->instance(AuditServiceInterface::class, Mockery::mock(AuditServiceInterface::class)->shouldIgnoreMissing());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function catalog(): ProductCatalogService
    {
        return $this->app->make(ProductCatalogService::class);
    }

    private function overrides(): EntitlementOverrideService
    {
        return $this->app->make(EntitlementOverrideService::class);
    }

    private function ensureModule(string $code, bool $core = false): void
    {
        DB::table('modules')->insertOrIgnore([
            'code' => $code, 'name' => ucfirst($code), 'is_core' => $core, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeTenant(): Tenant
    {
        $id = (string) Str::uuid();
        DB::table('tenants')->insert([
            'id' => $id, 'name' => 'T '.Str::random(4), 'subdomain' => 't'.Str::random(6),
            'email' => Str::random(6).'@e.test', 'type' => 'business', 'status' => Tenant::STATUS_ACTIVE,
            'data' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Tenant::find($id);
    }

    // -------------------------------------------------------------------------
    // ProductCatalogService
    // -------------------------------------------------------------------------

    public function test_save_creates_product_and_syncs_bundle(): void
    {
        $this->ensureModule('hrm');
        $this->ensureModule('finance');

        $product = $this->catalog()->save([
            'name' => 'Ops Suite', 'code' => 'ops-suite', 'monthly_price' => 25,
            'is_active' => true, 'is_marketplace_visible' => true, 'modules' => ['hrm', 'finance'],
        ]);

        $this->assertSame('ops-suite', $product->code);
        $this->assertSame('hrm', $product->module_code, 'primary scalar = first bundled module');
        $pivot = DB::table('product_modules')->where('product_id', $product->id)->pluck('module_code')->all();
        $this->assertEqualsCanonicalizing(['hrm', 'finance'], $pivot);
    }

    public function test_save_update_replaces_bundle(): void
    {
        $this->ensureModule('hrm');
        $this->ensureModule('finance');
        $p = $this->catalog()->save(['name' => 'P', 'code' => 'p', 'monthly_price' => 1, 'modules' => ['hrm']]);

        $this->catalog()->save(['name' => 'P', 'monthly_price' => 9, 'modules' => ['finance']], $p->id);

        $pivot = DB::table('product_modules')->where('product_id', $p->id)->pluck('module_code')->all();
        $this->assertSame(['finance'], $pivot);
        $this->assertSame('9.00', (string) $p->fresh()->monthly_price);
    }

    public function test_delete_refused_when_active_subscription_exists(): void
    {
        $this->ensureModule('hrm');
        $p = $this->catalog()->save(['name' => 'P', 'code' => 'p', 'monthly_price' => 1, 'modules' => ['hrm']]);
        $tenant = $this->makeTenant();
        ProductSubscription::withoutEvents(fn () => ProductSubscription::create([
            'tenant_id' => $tenant->id, 'product_id' => $p->id, 'billing_cycle' => 'monthly',
            'status' => 'active', 'starts_at' => now()->subDay(), 'currency' => 'USD', 'amount' => '1.00',
        ]));

        $this->expectException(\RuntimeException::class);
        $this->catalog()->delete($p->id);
    }

    public function test_delete_succeeds_without_subscriptions(): void
    {
        $this->ensureModule('hrm');
        $p = $this->catalog()->save(['name' => 'P', 'code' => 'p', 'monthly_price' => 1, 'modules' => ['hrm']]);

        $this->catalog()->delete($p->id);

        $this->assertNull(Product::find($p->id));
        $this->assertSame(0, DB::table('product_modules')->where('product_id', $p->id)->count());
    }

    // -------------------------------------------------------------------------
    // EntitlementOverrideService
    // -------------------------------------------------------------------------

    public function test_grant_inserts_open_override(): void
    {
        $this->ensureModule('hrm');
        $tenant = $this->makeTenant();

        $this->overrides()->grant($tenant->id, 'hrm', 'pilot');

        $row = DB::table('tenant_entitlements')->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('override', $row->source);
        $this->assertNull($row->revoked_at);
        $this->assertSame('pilot', $row->reason);
    }

    public function test_grant_refuses_duplicate_open_override(): void
    {
        $this->ensureModule('hrm');
        $tenant = $this->makeTenant();
        $this->overrides()->grant($tenant->id, 'hrm', null);

        $this->expectException(\RuntimeException::class);
        $this->overrides()->grant($tenant->id, 'hrm', null);
    }

    public function test_revoke_closes_override_but_keeps_ledger_row(): void
    {
        $this->ensureModule('hrm');
        $tenant = $this->makeTenant();
        $this->overrides()->grant($tenant->id, 'hrm', null);
        $id = (int) DB::table('tenant_entitlements')->where('tenant_id', $tenant->id)->value('id');

        $this->overrides()->revoke($id);

        $row = DB::table('tenant_entitlements')->where('id', $id)->first();
        $this->assertNotNull($row, 'append-only: row is retained');
        $this->assertNotNull($row->revoked_at, 'row is closed');
        $this->assertSame(0, DB::table('tenant_entitlements')->whereNull('revoked_at')->count());
    }
}
