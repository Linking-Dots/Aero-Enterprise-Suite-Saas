<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Finance\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PlanChangeIndependenceTest extends TestCase
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
     * ARCH NOTE: Executing a plan change on a plan Subscription MUST NOT cancel
     * or modify any ProductSubscription rows for the same tenant.
     */
    public function test_plan_change_does_not_touch_product_subscriptions(): void
    {
        $tenant = Tenant::factory()->create();
        $planA = Plan::factory()->create();
        $planB = Plan::factory()->create();
        $product = Product::factory()->create();

        $planSub = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'type' => 'default',
            'name' => 'default',
            'status' => 'active',
            'plan_id' => $planA->id,
        ]);

        $productSub = ProductSubscription::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'status' => 'active',
            'amount' => '20.00',
        ]);

        $originalProductStatus = $productSub->status;

        $service = app(SubscriptionLifecycleService::class);
        $service->executeChange($planSub, $planB->id, $this->admin->id);

        $freshProductSub = ProductSubscription::find($productSub->id);
        $this->assertEquals($originalProductStatus, $freshProductSub->status);
        $this->assertEquals($product->id, $freshProductSub->product_id);

        // Also verify the plan subscription was actually updated.
        $freshPlanSub = Subscription::find($planSub->id);
        $this->assertEquals($planB->id, $freshPlanSub->plan_id);
    }
}
