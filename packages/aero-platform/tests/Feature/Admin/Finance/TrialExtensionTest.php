<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Finance\SubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TrialExtensionTest extends TestCase
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
     * extendTrial must add the requested days to trial_ends_at — plan Subscription path.
     */
    public function test_extend_trial_adds_days_for_plan_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $trialEnd = Carbon::now()->addDays(7);

        $sub = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'type' => 'default',
            'name' => 'default',
            'status' => 'trialing',
            'trial_ends_at' => $trialEnd,
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $updated = $service->extendTrial($sub, 14, $this->admin->id);

        $diff = now()->diffInDays($updated->getTrialEndsAt());
        $this->assertGreaterThanOrEqual(20, $diff);
        $this->assertLessThanOrEqual(22, $diff);
    }

    /**
     * extendTrial must add the requested days — ProductSubscription path.
     */
    public function test_extend_trial_adds_days_for_product_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $trialEnd = Carbon::now()->addDays(5);

        $productSub = ProductSubscription::create([
            'tenant_id' => $tenant->id,
            'product_id' => 1,
            'status' => 'trialing',
            'trial_ends_at' => $trialEnd,
            'amount' => '0.00',
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $updated = $service->extendTrial($productSub, 10, $this->admin->id);

        $diff = now()->diffInDays($updated->getTrialEndsAt());
        $this->assertGreaterThanOrEqual(14, $diff);
        $this->assertLessThanOrEqual(16, $diff);
    }

    public function test_extend_trial_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->create();

        $sub = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'type' => 'default',
            'name' => 'default',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(7),
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $service->extendTrial($sub, 7, $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.subscription.trial_extended',
        ]);
    }
}
