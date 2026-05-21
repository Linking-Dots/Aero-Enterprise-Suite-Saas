<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PartnerCommission;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Services\Finance\PartnerService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CommissionPayoutTest extends TestCase
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
     * processCommissionPayout must only pay out PENDING commissions.
     * Approved / already-paid commissions must remain unchanged.
     */
    public function test_payout_only_processes_pending_commissions(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'Payout Reseller',
            'email' => 'payout@example.com',
            'status' => ResellerPartner::STATUS_ACTIVE,
        ]);

        PartnerCommission::create([
            'partner_id' => $partner->id,
            'tenant_id' => 'tenant-1',
            'amount' => '50.00',
            'status' => PartnerCommission::STATUS_PENDING,
        ]);

        PartnerCommission::create([
            'partner_id' => $partner->id,
            'tenant_id' => 'tenant-2',
            'amount' => '30.00',
            'status' => PartnerCommission::STATUS_PAID, // must NOT be re-processed
        ]);

        $service = app(PartnerService::class);
        $count = $service->processCommissionPayout($partner, $this->admin->id);

        $this->assertEquals(1, $count);

        // The pending one is now paid.
        $this->assertDatabaseHas('partner_commissions', [
            'tenant_id' => 'tenant-1',
            'status' => PartnerCommission::STATUS_PAID,
        ]);

        // The already-paid one is unchanged.
        $this->assertDatabaseHas('partner_commissions', [
            'tenant_id' => 'tenant-2',
            'status' => PartnerCommission::STATUS_PAID,
        ]);
    }

    public function test_payout_stamps_paid_at_on_each_processed_commission(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'Stamp Reseller',
            'email' => 'stamp@example.com',
            'status' => ResellerPartner::STATUS_ACTIVE,
        ]);

        PartnerCommission::create([
            'partner_id' => $partner->id,
            'tenant_id' => 'tenant-3',
            'amount' => '25.00',
            'status' => PartnerCommission::STATUS_PENDING,
        ]);

        $service = app(PartnerService::class);
        $service->processCommissionPayout($partner, $this->admin->id);

        $commission = PartnerCommission::where('tenant_id', 'tenant-3')->first();
        $this->assertNotNull($commission->paid_at);
    }

    public function test_payout_writes_audit_log(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'Audit Reseller',
            'email' => 'audit@example.com',
            'status' => ResellerPartner::STATUS_ACTIVE,
        ]);

        $service = app(PartnerService::class);
        $service->processCommissionPayout($partner, $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.partner.commission_payout_processed',
        ]);
    }
}
