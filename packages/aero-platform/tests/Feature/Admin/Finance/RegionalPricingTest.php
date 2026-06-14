<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Services\Finance\FinanceCurrencyService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RegionalPricingTest extends TestCase
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

    /**
     * Regional price must be unique per (priceable_type + priceable_id + currency).
     * Upsert must replace the existing row, not insert a duplicate.
     */
    public function test_upsert_replaces_existing_regional_price_for_plan(): void
    {
        $plan = Plan::factory()->create();
        $service = app(FinanceCurrencyService::class);

        $service->upsertRegionalPrice([
            'priceable_type' => Plan::class,
            'priceable_id' => $plan->id,
            'currency_code' => 'EUR',
            'price_monthly' => 49.00,
            'price_annual' => 490.00,
        ], $this->admin->id);

        $service->upsertRegionalPrice([
            'priceable_type' => Plan::class,
            'priceable_id' => $plan->id,
            'currency_code' => 'EUR',
            'price_monthly' => 59.00,
            'price_annual' => 590.00,
        ], $this->admin->id);

        $this->assertDatabaseCount('regional_prices', 1);
        $this->assertDatabaseHas('regional_prices', [
            'priceable_type' => Plan::class,
            'priceable_id' => $plan->id,
            'currency_code' => 'EUR',
            'price_monthly' => '59.00',
        ]);
    }

    public function test_upsert_supports_product_priceable(): void
    {
        $product = Product::factory()->create();
        $service = app(FinanceCurrencyService::class);

        $service->upsertRegionalPrice([
            'priceable_type' => Product::class,
            'priceable_id' => $product->id,
            'currency_code' => 'GBP',
            'price_monthly' => 29.00,
            'price_annual' => 290.00,
        ], $this->admin->id);

        $this->assertDatabaseHas('regional_prices', [
            'priceable_type' => Product::class,
            'priceable_id' => $product->id,
            'currency_code' => 'GBP',
        ]);
    }

    public function test_plan_and_product_can_have_same_currency_independently(): void
    {
        $plan = Plan::factory()->create();
        $product = Product::factory()->create();
        $service = app(FinanceCurrencyService::class);

        $service->upsertRegionalPrice([
            'priceable_type' => Plan::class,
            'priceable_id' => $plan->id,
            'currency_code' => 'USD',
            'price_monthly' => 99.00,
            'price_annual' => 990.00,
        ], $this->admin->id);

        $service->upsertRegionalPrice([
            'priceable_type' => Product::class,
            'priceable_id' => $product->id,
            'currency_code' => 'USD',
            'price_monthly' => 19.00,
            'price_annual' => 190.00,
        ], $this->admin->id);

        $this->assertDatabaseCount('regional_prices', 2);
    }
}
