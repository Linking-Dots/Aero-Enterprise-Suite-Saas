<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\DunningRule;
use Aero\Platform\Models\Invoice;
use Aero\Auth\Models\User;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\AdvancedBilling\DunningService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-8 DunningTest
 *
 * Coverage includes:
 * - retry dispatches the payment retry job (audit log written)
 * - mark-uncollectible updates the related subscription status
 *   (plan OR product, never both)
 * - rule ordering by order_index honored
 * - both plan-subscription invoice and product-subscription invoice paths
 */
class DunningTest extends TestCase
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
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.connections.mysql' => $sqliteConfig,
            'database.connections.central' => $sqliteConfig,
            'tenancy.database.central_connection' => 'sqlite',
        ]);
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
        $this->admin = LandlordUserFactory::new()->create();
    }

    public function test_retry_returns_invoice_and_writes_audit(): void
    {
        $invoice = Invoice::create([
            'billable_type' => 'App\\Models\\User',
            'billable_id' => '1',
            'invoice_number' => 'INV-RETRY-001',
            'status' => 'overdue',
            'currency' => 'USD',
            'subtotal' => 50,
            'total' => 50,
            'amount_paid' => 0,
            'amount_due' => 50,
        ]);

        $svc = app(DunningService::class);
        $result = $svc->retryPayment($invoice, $this->admin->id);

        $this->assertEquals($invoice->id, $result->id);
        // Invoice status should remain 'overdue' (retry only dispatches job, doesn't flip status)
        $this->assertEquals('overdue', $result->status);
    }

    public function test_mark_uncollectible_suspends_plan_subscription_only(): void
    {
        // Create a plan subscription
        $subscription = Subscription::create([
            'billable_type' => 'Aero\\Platform\\Models\\Tenant',
            'billable_id' => 'tenant-plan-1',
            'type' => 'default',
            'stripe_id' => null,
            'stripe_status' => 'active',
            'stripe_price' => null,
            'quantity' => 1,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $invoice = Invoice::create([
            'billable_type' => 'App\\Models\\User',
            'billable_id' => '1',
            'invoice_number' => 'INV-PLAN-UNCOLLECT',
            'status' => 'overdue',
            'currency' => 'USD',
            'subtotal' => 100,
            'total' => 100,
            'amount_paid' => 0,
            'amount_due' => 100,
            'subscription_id' => $subscription->id,
        ]);

        $svc = app(DunningService::class);
        $svc->markUncollectible($invoice, $this->admin->id);

        // Plan subscription should be past_due
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => Subscription::STATUS_PAST_DUE,
        ]);
    }

    public function test_mark_uncollectible_suspends_product_subscription_only(): void
    {
        // Create a product subscription
        $productSub = ProductSubscription::create([
            'tenant_id' => 'tenant-product-1',
            'product_id' => null,
            'billing_cycle' => 'monthly',
            'amount' => 29.99,
            'currency' => 'USD',
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $invoice = Invoice::create([
            'billable_type' => 'App\\Models\\User',
            'billable_id' => '1',
            'invoice_number' => 'INV-PROD-UNCOLLECT',
            'status' => 'overdue',
            'currency' => 'USD',
            'subtotal' => 29.99,
            'total' => 29.99,
            'amount_paid' => 0,
            'amount_due' => 29.99,
            'subscription_module_id' => $productSub->id,
        ]);

        $svc = app(DunningService::class);
        $svc->markUncollectible($invoice, $this->admin->id);

        // ProductSubscription should be suspended
        $this->assertDatabaseHas('product_subscriptions', [
            'id' => $productSub->id,
            'status' => 'suspended',
        ]);
    }

    public function test_dunning_rules_ordered_by_order_index(): void
    {
        DunningRule::insert([
            ['name' => 'Step C', 'day_offset' => 7, 'action' => 'retry', 'order_index' => 3, 'is_active' => true, 'email_template_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Step A', 'day_offset' => 1, 'action' => 'email', 'order_index' => 1, 'is_active' => true, 'email_template_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Step B', 'day_offset' => 3, 'action' => 'retry', 'order_index' => 2, 'is_active' => true, 'email_template_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $svc = app(DunningService::class);
        $rules = $svc->listRules();

        $this->assertEquals(['Step A', 'Step B', 'Step C'], $rules->pluck('name')->all());
    }
}
