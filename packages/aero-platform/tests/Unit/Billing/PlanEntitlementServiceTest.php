<?php

namespace Aero\Platform\Tests\Unit\Billing;

use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanEntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlanEntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlanEntitlementService::class);
    }

    public function test_has_reached_user_limit_returns_true_when_no_subscription_in_saas_mode(): void
    {
        config(['aero.mode' => 'saas']);

        $tenant = Tenant::factory()->create();

        $this->assertTrue($this->service->hasReachedUserLimit($tenant->id));
    }

    public function test_has_reached_user_limit_returns_false_in_standalone_mode(): void
    {
        config(['aero.mode' => 'standalone']);

        $tenant = Tenant::factory()->create();

        $this->assertFalse($this->service->hasReachedUserLimit($tenant->id));
    }

    public function test_has_reached_storage_limit_returns_true_when_no_subscription_in_saas_mode(): void
    {
        config(['aero.mode' => 'saas']);

        $tenant = Tenant::factory()->create();

        $this->assertTrue($this->service->hasReachedStorageLimit($tenant->id));
    }

    public function test_has_reached_storage_limit_returns_false_in_standalone_mode(): void
    {
        config(['aero.mode' => 'standalone']);

        $tenant = Tenant::factory()->create();

        $this->assertFalse($this->service->hasReachedStorageLimit($tenant->id));
    }

    public function test_has_reached_user_limit_enforces_plan_max_users(): void
    {
        config(['aero.mode' => 'saas']);

        $plan = Plan::factory()->create([
            'max_users' => 3,
        ]);

        $tenant = Tenant::factory()->create();
        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'amount' => $plan->monthly_price,
            'currency' => 'USD',
            'current_period_start' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);

        // Create 3 active users
        \Aero\Core\Models\User::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->hasReachedUserLimit($tenant->id));

        // One less user should not reach limit
        \Aero\Core\Models\User::where('tenant_id', $tenant->id)->first()->delete();
        $this->assertFalse($this->service->hasReachedUserLimit($tenant->id));
    }
}
