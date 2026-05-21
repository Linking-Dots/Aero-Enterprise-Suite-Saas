<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Services\Finance\PartnerService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PartnerApproveTest extends TestCase
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

    public function test_approve_sets_status_active_and_stamps_approved_at(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'Acme Reseller',
            'email' => 'acme@example.com',
            'commission_rate' => '0.1000',
            'status' => ResellerPartner::STATUS_PENDING,
        ]);

        $service = app(PartnerService::class);
        $approved = $service->approve($partner, $this->admin->id);

        $this->assertEquals(ResellerPartner::STATUS_ACTIVE, $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertEquals($this->admin->id, $approved->approved_by);
    }

    public function test_approve_writes_audit_log(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'Beta Reseller',
            'email' => 'beta@example.com',
            'commission_rate' => '0.0800',
            'status' => ResellerPartner::STATUS_PENDING,
        ]);

        $service = app(PartnerService::class);
        $service->approve($partner, $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.partner.approved',
        ]);
    }

    public function test_approve_endpoint_returns_ok(): void
    {
        $partner = ResellerPartner::create([
            'name' => 'HTTP Reseller',
            'email' => 'http@example.com',
            'status' => ResellerPartner::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.partners.approve', $partner->id))
            ->assertOk()
            ->assertJsonPath('partner.status', ResellerPartner::STATUS_ACTIVE);
    }
}
