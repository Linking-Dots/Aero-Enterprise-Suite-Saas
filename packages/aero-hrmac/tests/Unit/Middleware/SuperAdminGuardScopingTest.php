<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Middleware;

use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 04 (aero-hrmac) Task 2 — guard-scoped super-admin check.
 *
 * Phase 1 audit found the previous flat super_admin_roles array would let
 * a tenant role literally named "Super Administrator" satisfy the same
 * check as a landlord-scoped one. The fix nests the config by guard.
 *
 * Full HTTP-level guard tests live in the host repo's feature suite
 * (they require Auth::guard()->login() against real user models). This
 * file pins the structural contracts:
 *   - isSuperAdmin() method exists
 *   - resolveActiveGuard() helper exists
 *   - the config shape is guard-scoped
 */
class SuperAdminGuardScopingTest extends TestCase
{
    public function test_check_role_module_access_exposes_resolve_active_guard(): void
    {
        $r = new ReflectionClass(CheckRoleModuleAccess::class);

        $this->assertTrue($r->hasMethod('resolveActiveGuard'),
            'CheckRoleModuleAccess::resolveActiveGuard() must exist (Plan 04 T2).');
        $this->assertTrue($r->hasMethod('isSuperAdmin'),
            'CheckRoleModuleAccess::isSuperAdmin() must exist.');
    }

    public function test_config_super_admin_roles_is_guard_scoped(): void
    {
        $configPath = dirname(__DIR__, 3).'/config/hrmac.php';
        $this->assertFileExists($configPath);

        $config = require $configPath;

        $this->assertIsArray($config['super_admin_roles']);
        $this->assertArrayHasKey('landlord', $config['super_admin_roles'],
            "config('hrmac.super_admin_roles.landlord') must be defined for Platform Admin scope.");
        $this->assertArrayHasKey('web', $config['super_admin_roles'],
            "config('hrmac.super_admin_roles.web') must be defined for Tenant Admin scope.");
    }

    public function test_landlord_super_admin_roles_are_separate_from_tenant(): void
    {
        $configPath = dirname(__DIR__, 3).'/config/hrmac.php';
        $config = require $configPath;

        $landlordRoles = $config['super_admin_roles']['landlord'];
        $webRoles = $config['super_admin_roles']['web'];

        // The CANONICAL landlord role must NOT appear in the web (tenant) guard.
        // A tenant user accidentally creating "Platform Super Administrator"
        // would otherwise gain landlord-equivalent privileges.
        $this->assertNotContains('Platform Super Administrator', $webRoles,
            "'Platform Super Administrator' must NOT appear under the 'web' guard — ".
            "that's the role-name string match risk identified in Phase 1.");
        $this->assertContains('Platform Super Administrator', $landlordRoles,
            "'Platform Super Administrator' must appear under the 'landlord' guard.");
    }
}
