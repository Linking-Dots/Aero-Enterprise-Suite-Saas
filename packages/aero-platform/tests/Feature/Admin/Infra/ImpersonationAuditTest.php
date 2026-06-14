<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Infra;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantImpersonationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ImpersonationAuditTest extends TestCase
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
    }

    public function test_logs_complete_audit_trail_for_start_and_end(): void
    {
        $auditMock = $this->createMock(AuditServiceInterface::class);

        // Expect log() to be called exactly twice: start + end
        $auditMock->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [$this->callback(fn ($args) => true)],
                [$this->callback(fn ($args) => true)],
            );

        $svc = new TenantImpersonationService($auditMock);

        $tenant = Tenant::factory()->create();

        $token = $svc->start($tenant, $this->admin->id);

        $this->assertNotEmpty($token);

        $svc->end($token);
    }

    public function test_impersonation_start_logs_correct_event(): void
    {
        $logged = [];

        $auditMock = $this->createMock(AuditServiceInterface::class);
        $auditMock->method('log')
            ->willReturnCallback(function (string $event) use (&$logged) {
                $logged[] = $event;
            });

        $svc = new TenantImpersonationService($auditMock);
        $tenant = Tenant::factory()->create();

        $token = $svc->start($tenant, $this->admin->id);
        $svc->end($token);

        $this->assertContains(AuditEventType::TENANT_IMPERSONATION_STARTED->value, $logged);
        $this->assertContains(AuditEventType::TENANT_IMPERSONATION_ENDED->value, $logged);
    }
}
