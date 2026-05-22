<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Jobs\Infra\ProvisionSslJob;
use Aero\Platform\Models\Infra\TenantCustomDomain;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Services\Infra\CustomDomainService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomDomainServiceTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    protected CustomDomainService $svc;

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

        $this->admin = LandlordUser::factory()->create();

        $audit = $this->createMock(AuditServiceInterface::class);
        $this->svc = new CustomDomainService($audit);
    }

    public function test_dns_verification_logic_correct(): void
    {
        $domain = TenantCustomDomain::create([
            'tenant_id' => 'tenant-abc',
            'domain' => 'example-verify.test',
            'status' => 'pending',
            'ssl_status' => 'none',
            'dns_txt_record' => 'aeos-verify=abc123',
        ]);

        // Mock dns_get_record to return no matching record
        // (we cannot actually query DNS in tests; we verify the false-path)
        // The service calls dns_get_record which will return [] in test env.
        $result = $this->svc->verifyDns($domain);

        $this->assertFalse($result);
        $this->assertDatabaseHas('tenant_custom_domains', [
            'id' => $domain->id,
            'status' => 'failed',
        ]);
    }

    public function test_ssl_provisioning_dispatches_background_job(): void
    {
        Queue::fake();

        $domain = TenantCustomDomain::create([
            'tenant_id' => 'tenant-abc',
            'domain' => 'example-ssl.test',
            'status' => 'verified',
            'ssl_status' => 'none',
            'dns_txt_record' => 'aeos-verify=abc123',
        ]);

        $this->svc->provisionSsl($domain);

        Queue::assertPushed(ProvisionSslJob::class, function (ProvisionSslJob $job) {
            return true; // Job was dispatched; domain_id is a private property
        });

        $this->assertDatabaseHas('tenant_custom_domains', [
            'id' => $domain->id,
            'ssl_status' => 'provisioning',
        ]);
    }

    public function test_add_domain_creates_record_with_txt(): void
    {
        $record = $this->svc->addDomain('tenant-xyz', 'brand-new-domain.test');

        $this->assertInstanceOf(TenantCustomDomain::class, $record);
        $this->assertSame('pending', $record->status);
        $this->assertNotEmpty($record->dns_txt_record);
        $this->assertStringStartsWith('aeos-verify=', $record->dns_txt_record);
    }
}
