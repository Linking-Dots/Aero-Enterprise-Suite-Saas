<?php

namespace Aero\Platform\Tests\Unit;

use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription as CashierSubscription;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for the unified Subscription model (Option A implementation).
 *
 * Ensures Cashier and lifecycle fields coexist on the same single source of truth.
 */
class SubscriptionUnifiedModelTest extends \Aero\Platform\Tests\TestCase
{
    use RefreshDatabase;

    #[Test]
    public function subscription_model_extends_cashier_subscription(): void
    {
        $this->assertTrue(
            is_subclass_of(Subscription::class, CashierSubscription::class),
            'Subscription must extend Cashier\Subscription for unified single source of truth'
        );
    }

    #[Test]
    public function lifecycle_fields_are_fillable_on_unified_model(): void
    {
        $subscription = new Subscription;
        $fillable = $subscription->getFillable();

        $this->assertContains('upgraded_from_plan_id', $fillable);
        $this->assertContains('downgrade_scheduled_at', $fillable);
        $this->assertContains('grace_period_ends_at', $fillable);
        $this->assertContains('next_billing_date', $fillable);
    }

    #[Test]
    public function cashier_fields_are_fillable_on_unified_model(): void
    {
        $subscription = new Subscription;
        $fillable = $subscription->getFillable();

        $this->assertContains('stripe_id', $fillable);
        $this->assertContains('stripe_status', $fillable);
        $this->assertContains('stripe_price', $fillable);
        $this->assertContains('billable_type', $fillable);
        $this->assertContains('billable_id', $fillable);
        $this->assertContains('quantity', $fillable);
    }

    #[Test]
    public function subscription_can_persist_lifecycle_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $subscription = Subscription::create([
            'id' => fake()->uuid(),
            'tenant_id' => $tenant->id,
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'plan_id' => fake()->uuid(),
            'billing_cycle' => 'monthly',
            'amount' => 99.00,
            'currency' => 'USD',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'upgraded_from_plan_id' => fake()->uuid(),
            'upgraded_at' => now(),
            'downgrade_scheduled_at' => now()->addWeek(),
            'grace_period_ends_at' => now()->addDays(14),
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'upgraded_from_plan_id' => $subscription->upgraded_from_plan_id,
            'downgrade_scheduled_at' => $subscription->downgrade_scheduled_at,
            'grace_period_ends_at' => $subscription->grace_period_ends_at,
            'next_billing_date' => $subscription->next_billing_date,
        ]);
    }

    #[Test]
    public function subscription_cashier_status_scope_works_with_lifecycle_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $active = Subscription::create([
            'id' => fake()->uuid(),
            'tenant_id' => $tenant->id,
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'plan_id' => fake()->uuid(),
            'billing_cycle' => 'monthly',
            'amount' => 99.00,
            'currency' => 'USD',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'stripe_id' => 'sub_active',
            'stripe_status' => 'active',
        ]);

        $cancelled = Subscription::create([
            'id' => fake()->uuid(),
            'tenant_id' => $tenant->id,
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'plan_id' => fake()->uuid(),
            'billing_cycle' => 'monthly',
            'amount' => 99.00,
            'currency' => 'USD',
            'status' => 'cancelled',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => now(),
            'stripe_id' => 'sub_cancelled',
            'stripe_status' => 'canceled',
        ]);

        $activeScope = Subscription::active()->pluck('id')->toArray();
        $this->assertContains($active->id, $activeScope);
        $this->assertNotContains($cancelled->id, $activeScope);
    }
}
