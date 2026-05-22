<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Enterprise;

use Aero\Platform\Models\Enterprise\DpaTemplate;
use Aero\Platform\Models\Enterprise\DsarRequest;
use Aero\Platform\Models\Enterprise\TenantDpaSigning;
use Aero\Platform\Models\Enterprise\TosVersion;
use Aero\Platform\Services\Enterprise\ComplianceService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P-11 — ComplianceService
 *
 * Tests cover: DPA signing persistence, ToS version re-acceptance flag,
 * and DSAR fulfillment audit log.
 */
class ComplianceTest extends TestCase
{
    use DatabaseMigrations;

    protected ComplianceService $service;

    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->artisan('migrate:fresh');
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->service = app(ComplianceService::class);
    }

    // =========================================================================
    // DPA SIGNING
    // =========================================================================

    public function test_dpa_signing_recorded(): void
    {
        $template = DpaTemplate::create([
            'version' => '1.0',
            'title' => 'Standard DPA',
            'content_md' => '# DPA Content',
            'is_active' => true,
        ]);

        $tenantId = (string) Str::uuid();

        $signing = $this->service->recordSigning($tenantId, $template->id, [
            'signed_by_name' => 'Jane Doe',
            'signed_by_email' => 'jane@acme.test',
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertInstanceOf(TenantDpaSigning::class, $signing);
        $this->assertDatabaseHas('tenant_dpa_signings', [
            'tenant_id' => $tenantId,
            'template_id' => $template->id,
            'signed_by_email' => 'jane@acme.test',
        ]);

        $persisted = TenantDpaSigning::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertNotNull($persisted->signed_at);
    }

    public function test_dpa_signing_writes_audit_log(): void
    {
        $template = DpaTemplate::create([
            'version' => '1.1',
            'title' => 'Updated DPA',
            'content_md' => '# Updated DPA',
            'is_active' => true,
        ]);

        $this->service->recordSigning((string) Str::uuid(), $template->id, [
            'signed_by_name' => 'Bob Smith',
            'signed_by_email' => 'bob@corp.test',
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.compliance.dpa_signed',
        ]);
    }

    // =========================================================================
    // TOS VERSION
    // =========================================================================

    public function test_tos_version_publish_requires_re_acceptance_flag(): void
    {
        $tos = $this->service->publishTosVersion([
            'version' => '2.0',
            'content_md' => '# Terms of Service v2',
            'requires_re_acceptance' => true,
        ]);

        $this->assertInstanceOf(TosVersion::class, $tos);
        $this->assertDatabaseHas('tos_versions', [
            'version' => '2.0',
            'requires_re_acceptance' => true,
        ]);

        $persisted = TosVersion::where('version', '2.0')->firstOrFail();
        $this->assertTrue($persisted->requires_re_acceptance);
        $this->assertNotNull($persisted->published_at);
    }

    public function test_tos_version_published_without_re_acceptance_flag_defaults_to_false(): void
    {
        $tos = $this->service->publishTosVersion([
            'version' => '1.5',
            'content_md' => '# Terms of Service v1.5',
        ]);

        $persisted = TosVersion::where('version', '1.5')->firstOrFail();
        $this->assertFalse($persisted->requires_re_acceptance);
    }

    public function test_tos_version_publish_writes_audit_log(): void
    {
        $this->service->publishTosVersion([
            'version' => '3.0',
            'content_md' => '# Terms of Service v3',
            'requires_re_acceptance' => false,
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.compliance.tos_published',
        ]);
    }

    public function test_require_re_acceptance_updates_existing_tos_version(): void
    {
        $tos = $this->service->publishTosVersion([
            'version' => '4.0',
            'content_md' => '# v4',
            'requires_re_acceptance' => false,
        ]);

        $this->assertFalse($tos->requires_re_acceptance);

        $updated = $this->service->requireReAcceptance($tos);

        $this->assertTrue($updated->requires_re_acceptance);
        $this->assertDatabaseHas('tos_versions', [
            'version' => '4.0',
            'requires_re_acceptance' => true,
        ]);
    }

    // =========================================================================
    // DSAR FULFILLMENT — AUDIT LOG
    // =========================================================================

    public function test_dsar_fulfillment_writes_audit_log(): void
    {
        $dsar = DsarRequest::create([
            'tenant_id' => (string) Str::uuid(),
            'requester_email' => 'user@gdpr.test',
            'request_type' => 'access',
            'status' => 'pending',
        ]);

        $this->service->fulfillDsar($dsar, 'admin@platform.test', 'Data exported successfully');

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.compliance.dsar_fulfilled',
        ]);
    }

    public function test_dsar_fulfillment_updates_status_and_timestamps(): void
    {
        $dsar = DsarRequest::create([
            'tenant_id' => (string) Str::uuid(),
            'requester_email' => 'data@subject.test',
            'request_type' => 'erasure',
            'status' => 'pending',
        ]);

        $fulfilled = $this->service->fulfillDsar($dsar, 'admin@platform.test', 'Erasure completed');

        $this->assertSame('fulfilled', $fulfilled->status);
        $this->assertNotNull($fulfilled->fulfilled_at);
        $this->assertSame('admin@platform.test', $fulfilled->fulfilled_by);

        $this->assertDatabaseHas('dsar_requests', [
            'id' => $dsar->id,
            'status' => 'fulfilled',
        ]);
    }
}
