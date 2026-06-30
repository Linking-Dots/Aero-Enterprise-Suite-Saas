<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Billing;

use Aero\Platform\Models\Plan;
use Aero\Platform\Services\Billing\TenantSubscriptionPresenter;
use PHPUnit\Framework\TestCase;

class TenantSubscriptionPresenterTest extends TestCase
{
    private TenantSubscriptionPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new TenantSubscriptionPresenter();
    }

    public function test_plan_shapes_monthly_price_and_interval(): void
    {
        $plan = new Plan(['name' => 'Pro', 'monthly_price' => '49.00', 'yearly_price' => '490.00', 'currency' => 'USD', 'features' => ['A', 'B']]);

        $shaped = $this->presenter->plan($plan, 'monthly');

        $this->assertSame('Pro', $shaped['name']);
        $this->assertEquals(49.00, $shaped['price']);
        $this->assertSame('month', $shaped['interval']);
        $this->assertSame('USD', $shaped['currency']);
        $this->assertSame(['A', 'B'], $shaped['features']);
    }

    public function test_plan_uses_yearly_price_for_yearly_cycle(): void
    {
        $plan = new Plan(['name' => 'Pro', 'monthly_price' => '49.00', 'yearly_price' => '490.00']);

        $shaped = $this->presenter->plan($plan, 'yearly');

        $this->assertEquals(490.00, $shaped['price']);
        $this->assertSame('year', $shaped['interval']);
    }

    public function test_plan_returns_null_when_no_plan(): void
    {
        $this->assertNull($this->presenter->plan(null, 'monthly'));
    }

    public function test_usage_nests_users_and_storage(): void
    {
        $usage = $this->presenter->usage(7, 10, 3.5, 50, ['api_calls' => 1200]);

        $this->assertSame(['used' => 7, 'limit' => 10], $usage['users']);
        $this->assertSame(['used_gb' => 3.5, 'limit_gb' => 50], $usage['storage']);
        $this->assertSame(['api_calls' => 1200], $usage['metrics']);
    }

    public function test_direction_is_upgrade_when_new_costs_more(): void
    {
        $current = new Plan(['monthly_price' => '20.00', 'duration_in_months' => 1]);
        $new = new Plan(['monthly_price' => '50.00', 'duration_in_months' => 1]);

        $this->assertSame('upgrade', $this->presenter->direction($current, $new));
    }

    public function test_direction_is_downgrade_when_new_costs_less(): void
    {
        $current = new Plan(['monthly_price' => '50.00', 'duration_in_months' => 1]);
        $new = new Plan(['monthly_price' => '20.00', 'duration_in_months' => 1]);

        $this->assertSame('downgrade', $this->presenter->direction($current, $new));
    }
}
