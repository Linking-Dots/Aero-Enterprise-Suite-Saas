<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Provisioning;

use Aero\Platform\Jobs\ProvisionTenant;
use Aero\Platform\Support\TenantDatabaseDropGuard;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

/**
 * Tenant-database DROP guard (Plan 03 T12 → Axis A A9).
 *
 * The regex + tenant-prefix + central-DB refusal checks were extracted from
 * ProvisionTenant::rollbackDatabase into the shared TenantDatabaseDropGuard
 * (A9) so all three drop paths (provision rollback, GDPR forget, retention
 * purge) enforce them identically. This test now verifies the GUARD's behavior
 * directly (better than grepping source) plus that ProvisionTenant routes
 * through it.
 */
class RollbackDatabaseGuardTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('tenancy.database.prefix', 'tenant');
        $app['config']->set('database.connections.central.database', 'central_db');
    }

    public function test_rollback_database_method_exists(): void
    {
        $this->assertTrue((new ReflectionClass(ProvisionTenant::class))->hasMethod('rollbackDatabase'));
    }

    public function test_provision_tenant_routes_through_shared_drop_guard(): void
    {
        $source = file_get_contents((new ReflectionClass(ProvisionTenant::class))->getFileName());

        $this->assertStringContainsString(
            'TenantDatabaseDropGuard',
            $source,
            'rollbackDatabase() must delegate safety checks to the shared TenantDatabaseDropGuard (A9).'
        );
    }

    public function test_guard_accepts_valid_tenant_database_name(): void
    {
        TenantDatabaseDropGuard::assertSafe('tenant_abc123');
        $this->assertTrue(TenantDatabaseDropGuard::isSafe('tenant_abc123'));
    }

    public function test_guard_refuses_unsafe_characters(): void
    {
        $this->assertFalse(TenantDatabaseDropGuard::isSafe('tenant; DROP DATABASE x'));

        $this->expectException(\RuntimeException::class);
        TenantDatabaseDropGuard::assertSafe('tenant`; DROP');
    }

    public function test_guard_refuses_name_without_tenant_prefix(): void
    {
        $this->assertFalse(TenantDatabaseDropGuard::isSafe('some_other_db'));

        $this->expectException(\RuntimeException::class);
        TenantDatabaseDropGuard::assertSafe('some_other_db');
    }

    public function test_guard_refuses_central_database(): void
    {
        // central DB name does not start with the tenant prefix, so it is refused
        // both by the prefix guard and the explicit central-DB guard.
        $this->assertFalse(TenantDatabaseDropGuard::isSafe('central_db'));
    }
}
