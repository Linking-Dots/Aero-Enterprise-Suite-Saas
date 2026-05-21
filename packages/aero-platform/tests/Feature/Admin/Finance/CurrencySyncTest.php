<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformCurrency;
use Aero\Platform\Services\Finance\FinanceCurrencyService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CurrencySyncTest extends TestCase
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
        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);
    }

    /**
     * syncExchangeRates must update rate_updated_at and exchange_rate_to_usd
     * for active currencies only — inactive ones must remain unchanged.
     */
    public function test_sync_updates_active_currencies_only(): void
    {
        PlatformCurrency::create([
            'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€',
            'exchange_rate_to_usd' => 1.00, 'is_active' => true,
        ]);

        PlatformCurrency::create([
            'code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£',
            'exchange_rate_to_usd' => 1.00, 'is_active' => false, // inactive
        ]);

        $service = app(FinanceCurrencyService::class);
        $updated = $service->syncExchangeRates(['EUR' => 0.92, 'GBP' => 0.79], $this->admin->id);

        $this->assertEquals(1, $updated);

        $eur = PlatformCurrency::where('code', 'EUR')->first();
        $this->assertEquals('0.92000000', $eur->exchange_rate_to_usd);
        $this->assertNotNull($eur->rate_updated_at);

        $gbp = PlatformCurrency::where('code', 'GBP')->first();
        $this->assertEquals('1.00000000', $gbp->exchange_rate_to_usd);
    }

    public function test_sync_writes_audit_log(): void
    {
        PlatformCurrency::create([
            'code' => 'JPY', 'name' => 'Yen', 'symbol' => '¥',
            'exchange_rate_to_usd' => 1.00, 'is_active' => true,
        ]);

        $service = app(FinanceCurrencyService::class);
        $service->syncExchangeRates(['JPY' => 148.5], $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.currency.rates_synced',
        ]);
    }
}
