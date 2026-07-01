<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Billing;

use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\Billing\TenantSubscriptionPresenter;
use Carbon\Carbon;
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

    public function test_invoice_shapes_fields_and_pdf_flag(): void
    {
        // Avoid triggering the date-cast → getConnection() → config() path by
        // constructing without date fields, then injecting Carbon instances directly.
        $invoice = new Invoice([
            'invoice_number' => 'INV-100',
            'total' => '99.00',
            'currency' => 'USD',
            'status' => 'paid',
            'pdf_path' => 'invoices/inv-100.pdf',
        ]);
        $invoice->id = 'inv-uuid-1';
        $invoice->setRawAttributes(array_merge($invoice->getAttributes(), [
            'billing_period_start' => Carbon::parse('2026-01-01'),
            'billing_period_end'   => Carbon::parse('2026-01-31'),
        ]));

        $shaped = $this->presenter->invoice($invoice);

        $this->assertSame('inv-uuid-1', $shaped['id']);
        $this->assertSame('INV-100', $shaped['number']);
        $this->assertSame('2026-01-01', $shaped['period_start']);
        $this->assertSame('2026-01-31', $shaped['period_end']);
        $this->assertEquals(99.00, $shaped['amount']);
        $this->assertSame('USD', $shaped['currency']);
        $this->assertSame('paid', $shaped['status']);
        $this->assertTrue($shaped['has_pdf']);
    }

    public function test_product_shapes_from_eager_loaded_relation(): void
    {
        $product = new Product(['name' => 'Payroll Add-on']);
        $sub = new ProductSubscription([
            'status' => 'active',
            'amount' => '19.00',
            'currency' => 'USD',
        ]);
        $sub->id = 'ps-1';
        $sub->setRelation('product', $product);

        $shaped = $this->presenter->product($sub);

        $this->assertSame('ps-1', $shaped['id']);
        $this->assertSame('Payroll Add-on', $shaped['name']);
        $this->assertSame('active', $shaped['status']);
        $this->assertEquals(19.00, $shaped['price']);
        $this->assertSame('USD', $shaped['currency']);
    }

    public function test_invoice_ownership_guard_matches_tenant(): void
    {
        $invoice = new \Aero\Platform\Models\Invoice([
            'billable_type' => \Aero\Platform\Models\Tenant::class,
            'billable_id' => 'tenant-123',
        ]);

        $this->assertTrue($this->presenter->invoiceBelongsToTenant($invoice, 'tenant-123'));
        $this->assertFalse($this->presenter->invoiceBelongsToTenant($invoice, 'tenant-999'));

        // Wrong billable_type, matching id — guard must still return false.
        $wrongType = new \Aero\Platform\Models\Invoice([
            'billable_type' => \Aero\Platform\Models\Plan::class,
            'billable_id' => 'tenant-123',
        ]);
        $this->assertFalse($this->presenter->invoiceBelongsToTenant($wrongType, 'tenant-123'));
    }

    public function test_product_subscription_ownership_guard_matches_tenant(): void
    {
        $sub = new ProductSubscription(['tenant_id' => 'tenant-123']);

        $this->assertTrue($this->presenter->productSubscriptionBelongsToTenant($sub, 'tenant-123'));
        $this->assertFalse($this->presenter->productSubscriptionBelongsToTenant($sub, 'tenant-999'));
    }

    public function test_summary_merges_plan_status_and_usage(): void
    {
        $plan = new Plan(['name' => 'Pro', 'monthly_price' => '49.00', 'currency' => 'USD']);
        $sub = new Subscription(['billing_cycle' => 'monthly', 'status' => 'active']);
        $usage = $this->presenter->usage(7, 10, 3.5, 50, []);

        $summary = $this->presenter->summary($sub, $plan, $usage, 5);

        $this->assertSame('Pro', $summary['plan_name']);
        $this->assertEquals(49.00, $summary['price']);
        $this->assertSame('month', $summary['interval']);
        $this->assertSame('active', $summary['status']);
        $this->assertSame(5, $summary['days_left']);
        $this->assertSame(['used' => 7, 'limit' => 10], $summary['users']);
    }
}
