<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Middleware;

use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Context-free super-admin check (2026-06-04 HRMAC redesign).
 *
 * HRMAC no longer detects the active guard. super_admin_roles is a FLAT union list;
 * a user's roles resolve from the host-decided connection (tenant DB for tenant
 * requests, central DB for platform requests), so role names that don't exist in the
 * current DB can never match. These pins guard against regressing back to guard-scoped
 * context detection inside the package.
 */
class SuperAdminGuardScopingTest extends TestCase
{
    public function test_isSuperAdmin_exists_and_guard_probing_is_gone(): void
    {
        $r = new ReflectionClass(CheckRoleModuleAccess::class);

        $this->assertTrue($r->hasMethod('isSuperAdmin'),
            'CheckRoleModuleAccess::isSuperAdmin() must exist.');
        $this->assertFalse($r->hasMethod('resolveActiveGuard'),
            'resolveActiveGuard() must be GONE — HRMAC does not detect the active guard.');
    }

    public function test_config_super_admin_roles_is_a_flat_list(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';

        $this->assertIsArray($config['super_admin_roles']);
        $this->assertTrue(array_is_list($config['super_admin_roles']),
            'super_admin_roles must be a flat list (no guard-scoped keys) — context-free.');
    }

    public function test_flat_list_contains_both_platform_and_tenant_super_admins(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';
        $roles = $config['super_admin_roles'];

        // The union is intentional and safe: each name only matches against the
        // current connection's role rows.
        $this->assertContains('Platform Super Administrator', $roles,
            'Platform super-admin name must be present in the flat union list.');
        $this->assertContains('Tenant Super Administrator', $roles,
            'Tenant super-admin name must be present in the flat union list.');
    }
}
