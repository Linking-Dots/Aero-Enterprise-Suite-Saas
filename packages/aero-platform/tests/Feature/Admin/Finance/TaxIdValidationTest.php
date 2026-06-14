<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Services\Finance\TaxService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TaxIdValidationTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $cfg, 'database.connections.central' => $cfg]);
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
        $this->admin = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);
    }

    public function test_validate_route_returns_valid_for_well_formed_vat_number(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tax.validate'), [
                'country_code' => 'DE',
                'tax_id' => 'DE123456789',
            ])
            ->assertOk()
            ->assertJsonFragment(['valid' => true]);
    }

    public function test_validate_route_returns_invalid_for_malformed_vat_number(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tax.validate'), [
                'country_code' => 'GB',
                'tax_id' => 'bad',
            ])
            ->assertOk()
            ->assertJsonFragment(['valid' => false]);
    }

    public function test_validate_route_rejects_missing_fields(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tax.validate'), [])
            ->assertUnprocessable();
    }

    public function test_service_validates_format_correctly(): void
    {
        $service = app(TaxService::class);

        $valid = $service->validateTaxId('GB', 'GB123456789');
        $this->assertTrue($valid['valid']);

        $invalid = $service->validateTaxId('GB', 'x');
        $this->assertFalse($invalid['valid']);
    }
}
