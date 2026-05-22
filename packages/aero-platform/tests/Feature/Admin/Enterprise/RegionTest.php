<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Enterprise;

use Aero\Platform\Models\Enterprise\Region;
use Aero\Platform\Models\Enterprise\TenantRegionAssignment;
use Aero\Platform\Services\Enterprise\RegionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P-11 — RegionService
 *
 * Tests cover: tenant region assignment persistence, and that disabling
 * a region leaves existing TenantRegionAssignment rows untouched.
 */
class RegionTest extends TestCase
{
    use DatabaseMigrations;

    protected RegionService $service;

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

        $this->service = app(RegionService::class);
    }

    private function createRegion(string $code = 'us-east-1', bool $isActive = true): Region
    {
        return Region::create([
            'code' => $code,
            'name' => 'Region ' . $code,
            'db_host' => 'db.' . $code . '.example.com',
            'is_active' => $isActive,
        ]);
    }

    // =========================================================================
    // TENANT REGION ASSIGNMENT
    // =========================================================================

    public function test_tenant_region_assignment_stored(): void
    {
        $region = $this->createRegion('eu-west-1');
        $tenantId = (string) Str::uuid();
        $assignedBy = (string) Str::uuid();

        $assignment = $this->service->assignTenant($tenantId, $region->id, $assignedBy);

        $this->assertInstanceOf(TenantRegionAssignment::class, $assignment);
        $this->assertDatabaseHas('tenant_region_assignments', [
            'tenant_id' => $tenantId,
            'region_id' => $region->id,
        ]);

        $persisted = TenantRegionAssignment::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertNotNull($persisted->assigned_at);
    }

    public function test_tenant_region_assignment_upserts_on_re_assignment(): void
    {
        $regionA = $this->createRegion('ap-southeast-1');
        $regionB = $this->createRegion('ap-northeast-1');
        $tenantId = (string) Str::uuid();
        $assignedBy = (string) Str::uuid();

        $this->service->assignTenant($tenantId, $regionA->id, $assignedBy);
        $this->service->assignTenant($tenantId, $regionB->id, $assignedBy);

        $this->assertSame(1, TenantRegionAssignment::where('tenant_id', $tenantId)->count());

        $assignment = TenantRegionAssignment::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertSame((string) $regionB->id, (string) $assignment->region_id);
    }

    public function test_tenant_region_assignment_writes_audit_log(): void
    {
        $region = $this->createRegion('ca-central-1');
        $tenantId = (string) Str::uuid();
        $assignedBy = (string) Str::uuid();

        $this->service->assignTenant($tenantId, $region->id, $assignedBy);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.multi_region.tenant_assigned',
        ]);
    }

    // =========================================================================
    // DISABLE REGION — EXISTING ASSIGNMENTS UNTOUCHED
    // =========================================================================

    public function test_disabling_region_does_not_affect_existing_tenant_assignments(): void
    {
        $region = $this->createRegion('sa-east-1', isActive: true);

        $tenantIds = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        $assignedBy = (string) Str::uuid();

        foreach ($tenantIds as $tenantId) {
            $this->service->assignTenant($tenantId, $region->id, $assignedBy);
        }

        $this->assertSame(3, TenantRegionAssignment::where('region_id', $region->id)->count());

        $this->service->disable($region);

        $region->refresh();
        $this->assertFalse($region->is_active);

        // Existing assignment rows must remain intact.
        $this->assertSame(3, TenantRegionAssignment::where('region_id', $region->id)->count());

        foreach ($tenantIds as $tenantId) {
            $this->assertDatabaseHas('tenant_region_assignments', [
                'tenant_id' => $tenantId,
                'region_id' => $region->id,
            ]);
        }
    }

    public function test_disabling_region_writes_audit_log(): void
    {
        $region = $this->createRegion('af-south-1');

        $this->service->disable($region);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.multi_region.region_disabled',
        ]);
    }

    public function test_enabling_region_sets_is_active_true(): void
    {
        $region = $this->createRegion('me-south-1', isActive: false);

        $this->service->enable($region);

        $region->refresh();
        $this->assertTrue($region->is_active);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.multi_region.region_enabled',
        ]);
    }
}
