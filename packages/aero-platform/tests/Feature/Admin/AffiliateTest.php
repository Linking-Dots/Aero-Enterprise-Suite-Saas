<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\Affiliate;
use Aero\Platform\Models\AffiliatePayout;
use Aero\Platform\Models\AffiliateReferral;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-6 — AffiliateController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class AffiliateTest extends TestCase
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

    private function makeApprovedAffiliate(float $commissionRate = 20.0): Affiliate
    {
        /** @var Affiliate $affiliate */
        $affiliate = Affiliate::create([
            'name' => 'Test Affiliate',
            'email' => 'affiliate-'.uniqid().'@example.com',
            'status' => Affiliate::STATUS_APPROVED,
            'commission_rate' => $commissionRate,
            'commission_type' => Affiliate::COMMISSION_PERCENTAGE,
            'payout_method' => Affiliate::PAYOUT_PAYPAL,
            'payout_details' => ['paypal_email' => 'pay@example.com'],
            'minimum_payout' => 0.01,
            'pending_earnings' => 100.00,
            'approved_at' => now(),
        ]);

        return $affiliate;
    }

    public function test_index_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.affiliates.index'))
            ->assertOk();
    }

    public function test_can_create_affiliate(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.store'), [
                'name' => 'New Partner',
                'email' => 'partner@example.com',
                'commission_rate' => 15.0,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('affiliates', [
            'email' => 'partner@example.com',
        ]);
    }

    public function test_commission_calculated_correctly_on_referral(): void
    {
        $affiliate = $this->makeApprovedAffiliate(20.0);

        // Simulate recording a referral with a $500 transaction
        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id,
            'status' => AffiliateReferral::STATUS_CONVERTED,
            'transaction_amount' => 500.00,
            'commission_amount' => $affiliate->calculateCommission(500.00),
            'commission_status' => AffiliateReferral::COMMISSION_PENDING,
            'converted_at' => now(),
        ]);

        // Commission should be 20% of 500 = 100
        $this->assertEquals(100.00, (float) $referral->commission_amount);
    }

    public function test_payout_transitions_pending_to_processing_to_completed(): void
    {
        $affiliate = $this->makeApprovedAffiliate();

        // Create payout in pending state
        $payout = AffiliatePayout::create([
            'affiliate_id' => $affiliate->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => AffiliatePayout::STATUS_PENDING,
            'payout_method' => Affiliate::PAYOUT_PAYPAL,
        ]);

        $this->assertEquals(AffiliatePayout::STATUS_PENDING, $payout->status);

        // Transition to processing via controller
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.payout.process', $payout))
            ->assertOk()
            ->assertJsonPath('success', true);

        $payout->refresh();
        $this->assertEquals(AffiliatePayout::STATUS_PROCESSING, $payout->status);

        // Transition to completed via controller
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.payout.complete', $payout), [
                'transaction_id' => 'TXN-TEST-001',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $payout->refresh();
        $this->assertEquals(AffiliatePayout::STATUS_COMPLETED, $payout->status);
        $this->assertEquals('TXN-TEST-001', $payout->transaction_reference);
    }

    public function test_can_approve_affiliate(): void
    {
        $affiliate = Affiliate::create([
            'name' => 'Pending Partner',
            'email' => 'pending@example.com',
            'status' => Affiliate::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.approve', $affiliate))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('affiliates', [
            'id' => $affiliate->id,
            'status' => Affiliate::STATUS_APPROVED,
        ]);
    }

    public function test_can_suspend_affiliate(): void
    {
        $affiliate = $this->makeApprovedAffiliate();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.suspend', $affiliate), [
                'reason' => 'Fraudulent activity',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('affiliates', [
            'id' => $affiliate->id,
            'status' => Affiliate::STATUS_SUSPENDED,
        ]);
    }

    public function test_process_payout_requires_pending_status(): void
    {
        $affiliate = $this->makeApprovedAffiliate();

        $payout = AffiliatePayout::create([
            'affiliate_id' => $affiliate->id,
            'amount' => 50.00,
            'currency' => 'USD',
            'status' => AffiliatePayout::STATUS_COMPLETED,
            'payout_method' => Affiliate::PAYOUT_PAYPAL,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.payout.process', $payout))
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_create_affiliate_requires_name_and_email(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.affiliates.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
