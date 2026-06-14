<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Finance\SubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class TrialConvertTest extends TestCase
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
     * convertTrial must fail if the plan subscription is already active.
     */
    public function test_convert_trial_fails_if_plan_subscription_already_active(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $tenant = Tenant::factory()->create();

        $sub = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'type' => 'default',
            'name' => 'default',
            'status' => 'active',
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $service->convertTrial($sub, 1, $this->admin->id);
    }

    /**
     * convertTrial must fail if the product subscription is already active.
     */
    public function test_convert_trial_fails_if_product_subscription_already_active(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $tenant = Tenant::factory()->create();

        $productSub = ProductSubscription::create([
            'tenant_id' => $tenant->id,
            'product_id' => 1,
            'status' => 'active',
            'amount' => '10.00',
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $service->convertTrial($productSub, 1, $this->admin->id);
    }

    public function test_convert_trial_succeeds_for_trialing_plan_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $sub = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'type' => 'default',
            'name' => 'default',
            'status' => 'trialing',
            'trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $converted = $service->convertTrial($sub, 1, $this->admin->id);

        $this->assertEquals('active', $converted->getStatus());
        $this->assertNull($converted->getTrialEndsAt());
    }

    public function test_convert_trial_succeeds_for_trialing_product_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $productSub = ProductSubscription::create([
            'tenant_id' => $tenant->id,
            'product_id' => 1,
            'status' => 'trialing',
            'trial_ends_at' => Carbon::now()->addDays(3),
            'amount' => '0.00',
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $converted = $service->convertTrial($productSub, 1, $this->admin->id);

        $this->assertEquals('active', $converted->getStatus());
    }
}
