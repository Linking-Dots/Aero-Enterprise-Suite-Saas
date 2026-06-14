<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformAuditLog;
use Aero\Platform\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

/**
 * P-5 — P5AuditLogController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Connection sharing + DatabaseMigrations are provided by the package TestCase.
 */
class AuditLogControllerTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
        $this->admin = $this->superAdminLandlord();
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
