<?php

namespace Aero\Core\Tests\Unit\Module;

use Aero\Core\Models\Module;
use Aero\Core\Models\User;
use Aero\Core\Services\Module\ModuleAccessService;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ModuleAccessServiceSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleAccessService $service;
    protected RoleModuleAccessService $roleAccessService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleAccessService = Mockery::mock(RoleModuleAccessService::class);
        $this->roleAccessService->shouldReceive('canAccessModule')->andReturn(true);
        $this->app->instance(RoleModuleAccessService::class, $this->roleAccessService);
        $this->service = app(ModuleAccessService::class);
    }

    public function test_denies_module_access_when_no_active_subscription_in_saas_mode(): void
    {
        config(['aero.mode' => 'saas']);

        $plan = Plan::factory()->create();
        $module = Module::factory()->create([
            'code' => 'hrm',
            'is_core' => false,
            'is_active' => true,
        ]);
        $plan->modules()->attach($module);

        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // No active subscription
        $this->assertNull($tenant->currentSubscription);

        tenancy()->initialize($tenant);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $result = $this->service->canAccessModule($user, 'hrm');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('plan_restriction', $result['reason']);
    }

    public function test_allows_module_access_with_active_subscription_in_saas_mode(): void
    {
        config(['aero.mode' => 'saas']);

        $plan = Plan::factory()->create();
        $module = Module::factory()->create([
            'code' => 'hrm',
            'is_core' => false,
            'is_active' => true,
        ]);
        $plan->modules()->attach($module);

        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'amount' => $plan->monthly_price,
            'currency' => 'USD',
            'current_period_start' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);

        tenancy()->initialize($tenant);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $result = $this->service->canAccessModule($user, 'hrm');

        $this->assertTrue($result['allowed']);
        $this->assertEquals('success', $result['reason']);
    }

    public function test_allows_core_modules_without_subscription(): void
    {
        config(['aero.mode' => 'saas']);

        Module::factory()->create([
            'code' => 'core',
            'is_core' => true,
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create();
        tenancy()->initialize($tenant);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $result = $this->service->canAccessModule($user, 'core');

        $this->assertTrue($result['allowed']);
        $this->assertEquals('success', $result['reason']);
    }
}
