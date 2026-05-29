<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Http;

use Aero\Auth\Http\Controllers\Auth\ImpersonationController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 05 (aero-auth) Task 4 — impersonation role-lookup hardening.
 *
 * Phase 1 audit X-9: ImpersonationController::impersonate() did
 *   User::whereHas('roles', fn ($q) => $q->where('name', 'Super Administrator'))
 *     ->orderBy('id')->first()
 *     ?? User::orderBy('id')->first();
 *
 * Brittle:
 *   - Hardcoded single role name 'Super Administrator'
 *   - If the tenant uses 'tenant_super_administrator', 'super-admin', or
 *     'Tenant Super Administrator' (all valid per hrmac.super_admin_roles),
 *     the lookup fails and falls through to "oldest user" — could pick
 *     a non-admin, leak privileges via impersonation
 *
 * Fix: pull names from config('hrmac.super_admin_roles.web') — the SAME
 * source CheckRoleModuleAccess middleware uses, so impersonation target
 * resolution stays consistent with the bypass list.
 */
class ImpersonationRoleLookupTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(ImpersonationController::class))->getFileName());
    }

    public function test_no_hardcoded_super_administrator_string(): void
    {
        $source = $this->source();

        // Pin: the literal where('name', 'Super Administrator') call is gone
        $this->assertDoesNotMatchRegularExpression(
            "/->where\(\s*['\"]name['\"]\s*,\s*['\"]Super Administrator['\"]\s*\)/",
            $source,
            "ImpersonationController must NOT use ->where('name', 'Super Administrator') — ".
            "fragile hardcoded single name. Use config-driven list from ".
            "hrmac.super_admin_roles.web instead (Plan 05 T4)."
        );
    }

    public function test_uses_whereIn_against_config_driven_list(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/whereIn\(\s*[\'"]name[\'"]\s*,\s*\$superAdminRoleNames\s*\)/',
            $source,
            'Impersonate target lookup must use whereIn against the config-driven '.
            '$superAdminRoleNames list, not a hardcoded single string.'
        );

        $this->assertMatchesRegularExpression(
            "/config\(\s*['\"]hrmac\.super_admin_roles\.web['\"]/",
            $source,
            'List must come from config(hrmac.super_admin_roles.web) — same source as '.
            'CheckRoleModuleAccess middleware uses, so impersonation resolution stays '.
            'consistent with bypass rules.'
        );
    }

    public function test_config_default_includes_all_known_super_admin_names(): void
    {
        $source = $this->source();

        // Pin all four canonical names as defaults in case the config is missing
        // (early-boot, fresh install, misconfiguration)
        foreach ([
            'Tenant Super Administrator',
            'tenant_super_administrator',
            'Super Administrator',
            'super-admin',
        ] as $expected) {
            $this->assertStringContainsString("'{$expected}'", $source,
                "Default fallback list must include '{$expected}' — that's a canonical ".
                "super-admin role name in the seeder/config.");
        }
    }
}
