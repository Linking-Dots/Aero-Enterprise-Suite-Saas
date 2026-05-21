<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformAuditLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-5 — P5AuditLogController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class AuditLogControllerTest extends TestCase
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
        $this->admin = LandlordUser::factory()->create();
    }

    public function test_index_renders_inertia_component(): void
    {
        PlatformAuditLog::create([
            'event_type' => 'TENANT_CREATED',
            'action' => 'create',
            'description' => 'Tenant created',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/AuditLogs/Index'));
    }

    public function test_audit_log_list_filters_by_event_type(): void
    {
        PlatformAuditLog::create([
            'event_type' => 'TENANT_CREATED',
            'action' => 'create',
            'description' => 'x',
            'created_at' => now(),
        ]);
        PlatformAuditLog::create([
            'event_type' => 'PLAN_CREATED',
            'action' => 'create',
            'description' => 'y',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.audit-logs.index', ['event_type' => 'TENANT_CREATED']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event_type', 'TENANT_CREATED')
            );
    }

    public function test_show_returns_json(): void
    {
        $log = PlatformAuditLog::create([
            'event_type' => 'TEST_EVENT',
            'action' => 'view',
            'description' => 'test',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->getJson(route('platform.admin.audit-logs.show', $log->id))
            ->assertOk()
            ->assertJsonPath('id', $log->id);
    }

    public function test_export_returns_csv(): void
    {
        PlatformAuditLog::create([
            'event_type' => 'EXPORT_TEST',
            'action' => 'export',
            'description' => 'desc',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.audit-logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
