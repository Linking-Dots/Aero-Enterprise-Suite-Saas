<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Services\Finance\TaxService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TaxRateTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $cfg, 'database.connections.central' => $cfg, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->shareSqliteAcrossConnections();
        Gate::before(fn () => true);

        $role = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'landlord']);
        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);
    }

    /**
     * upsertRate must update the existing row for the same country_code + type
     * instead of inserting a duplicate.
     */
    public function test_upsert_rate_updates_existing_row_for_same_country_and_type(): void
    {
        $service = app(TaxService::class);

        $service->upsertRate([
            'country_code' => 'DE',
            'region_code' => null,
            'name' => 'German VAT',
            'rate' => 0.19,
            'type' => 'vat',
            'is_active' => true,
        ], $this->admin->id);

        // Second upsert with same country + type should update, not insert.
        $service->upsertRate([
            'country_code' => 'DE',
            'region_code' => null,
            'name' => 'German VAT Updated',
            'rate' => 0.21,
            'type' => 'vat',
            'is_active' => true,
        ], $this->admin->id);

        $this->assertDatabaseCount('tax_rates', 1);

        $this->assertDatabaseHas('tax_rates', [
            'country_code' => 'DE',
            'type' => 'vat',
            'rate' => '0.2100',
            'name' => 'German VAT Updated',
        ]);
    }

    public function test_upsert_rate_creates_new_row_for_different_type(): void
    {
        $service = app(TaxService::class);

        $service->upsertRate([
            'country_code' => 'US',
            'region_code' => 'CA',
            'name' => 'CA Sales Tax',
            'rate' => 0.0725,
            'type' => 'sales_tax',
        ], $this->admin->id);

        $service->upsertRate([
            'country_code' => 'US',
            'region_code' => 'CA',
            'name' => 'CA Withholding',
            'rate' => 0.05,
            'type' => 'withholding',
        ], $this->admin->id);

        $this->assertDatabaseCount('tax_rates', 2);
    }

    public function test_upsert_rate_writes_audit_log(): void
    {
        $service = app(TaxService::class);

        $service->upsertRate([
            'country_code' => 'FR',
            'region_code' => null,
            'name' => 'French VAT',
            'rate' => 0.20,
            'type' => 'vat',
        ], $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.tax.rate_upserted',
        ]);
    }
}
