<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Infra;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Infra\StaffIpAllowlist;
use Aero\Auth\Models\User;
use Aero\Platform\Services\Infra\PlatformSecurityService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class IpAllowlistTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    protected PlatformSecurityService $svc;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
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

        $audit = $this->createMock(AuditServiceInterface::class);
        $this->svc = new PlatformSecurityService($audit);
    }

    public function test_ip_inside_cidr_is_allowed(): void
    {
        StaffIpAllowlist::create([
            'ip_cidr' => '10.0.0.0/24',
            'label' => 'Office',
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->svc->isIpAllowed('10.0.0.50'));
    }

    public function test_ip_outside_cidr_is_blocked(): void
    {
        StaffIpAllowlist::create([
            'ip_cidr' => '10.0.0.0/24',
            'label' => 'Office',
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->assertFalse($this->svc->isIpAllowed('192.168.1.1'));
    }

    public function test_no_active_rules_allows_all_ips(): void
    {
        // No entries in table
        $this->assertTrue($this->svc->isIpAllowed('1.2.3.4'));
    }

    public function test_inactive_entry_does_not_block(): void
    {
        StaffIpAllowlist::create([
            'ip_cidr' => '10.0.0.0/24',
            'label' => 'Office',
            'created_by' => $this->admin->id,
            'is_active' => false,
        ]);

        // Only inactive rule, so behaves as empty — all allowed
        $this->assertTrue($this->svc->isIpAllowed('8.8.8.8'));
    }

    public function test_exact_ip_cidr_slash32_matches(): void
    {
        StaffIpAllowlist::create([
            'ip_cidr' => '203.0.113.42/32',
            'label' => 'Single IP',
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->svc->isIpAllowed('203.0.113.42'));
        $this->assertFalse($this->svc->isIpAllowed('203.0.113.43'));
    }
}
