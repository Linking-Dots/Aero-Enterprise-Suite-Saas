<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Audit;

use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Tests\PackageTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Axis B B1 + B2 (2026-05-30) — audit routing parity.
 *
 * Before the fix, AuditService routed platform-vs-tenant on app()->bound('currentTenant')
 * — a binding nothing sets — so every write targeted the central platform_audit_logs
 * table. In standalone there is no 'central' connection, so those writes threw and were
 * swallowed → standalone had ZERO audit logging.
 *
 * The package test harness runs in standalone mode (no aeos.mode file, central has no
 * 'tenants' table → aero_mode() === 'standalone'). These tests prove the standalone
 * audit path now writes to the local audit_logs table and that central_connection()
 * resolves to the default connection (not the absent 'central').
 */
class AuditServiceRoutingTest extends PackageTestCase
{
    public function test_harness_runs_in_standalone_mode(): void
    {
        $this->assertTrue(is_standalone_mode(), 'Precondition: package harness is standalone.');
    }

    public function test_central_connection_resolves_to_default_in_standalone(): void
    {
        $this->assertSame(
            config('database.default'),
            central_connection(),
            'In standalone, central_connection() must resolve to the default connection, '.
            'never the (absent) "central" connection.'
        );
    }

    public function test_standalone_audit_log_persists_to_local_audit_logs(): void
    {
        $this->assertSame(0, DB::table('audit_logs')->count());

        app(AuditService::class)->log(
            event: 'test.event',
            action: 'created',
            subject: null,
            description: 'standalone audit routing proof',
        );

        $this->assertSame(
            1,
            DB::table('audit_logs')->count(),
            'Standalone audit write must land in the local audit_logs table (B1), not be '.
            'swallowed by a failed central-connection write.'
        );

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'test.event',
            'action'     => 'created',
        ]);
    }
}
