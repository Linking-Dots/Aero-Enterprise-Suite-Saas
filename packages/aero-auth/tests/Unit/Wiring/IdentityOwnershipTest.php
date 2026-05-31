<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Unit\Wiring;

use PHPUnit\Framework\TestCase;

/**
 * Plan 05 (aero-auth) Task 6 — identity ownership delegation regression pin.
 *
 * The sso_identity submodule (SAML, OIDC, OAuth Provider, SCIM, Passkeys,
 * Magic Link, MFA Policies, Session Policies, Social Login, Verification,
 * Login Activity, Account Recovery — 12 components, ~30 actions) was
 * previously DECLARED in aero-core/config/module.php but IMPLEMENTED in
 * aero-auth (15 admin controllers + routes).
 *
 * Phase 1 audit flagged this as ownership confusion: the package that
 * declares HRMAC permissions should be the package that implements them,
 * otherwise per-package gap audits flag false positives.
 *
 * This commit moves the declarations to aero-auth (where the controllers
 * live) and renames the HRMAC permission keys: core.sso_identity.* →
 * auth.sso_identity.*. All 29 route middleware references in
 * routes/identity.php and all 13 frontend useHRMAC() calls in
 * Pages/Core/Identity/*.jsx were updated.
 */
class IdentityOwnershipTest extends TestCase
{
    public function test_aero_auth_declares_sso_identity_submodule(): void
    {
        $config = require dirname(__DIR__, 3).'/config/module.php';

        $this->assertArrayHasKey('submodules', $config,
            'aero-auth config/module.php must declare submodules (Plan 05 T6).');

        $codes = array_column($config['submodules'], 'code');
        $this->assertContains('sso_identity', $codes,
            "aero-auth must declare 'sso_identity' submodule — moved from aero-core.");
    }

    public function test_sso_identity_has_all_12_components(): void
    {
        $config = require dirname(__DIR__, 3).'/config/module.php';

        $ssoIdentity = collect($config['submodules'])->firstWhere('code', 'sso_identity');
        $this->assertNotNull($ssoIdentity);

        $componentCodes = array_column($ssoIdentity['components'], 'code');

        foreach ([
            'sso_saml', 'sso_oidc', 'oauth_provider', 'scim_provisioning',
            'social_login', 'magic_link', 'passkeys', 'mfa_policies',
            'session_policies', 'login_activity', 'verification', 'account_recovery',
        ] as $expected) {
            $this->assertContains($expected, $componentCodes,
                "sso_identity must include '{$expected}' component (was in aero-core; Plan 05 T6).");
        }
    }

    public function test_aero_core_no_longer_declares_sso_identity(): void
    {
        $config = require dirname(__DIR__, 4).'/aero-core/config/module.php';

        $submoduleCodes = array_column($config['submodules'] ?? [], 'code');
        $this->assertNotContains('sso_identity', $submoduleCodes,
            'aero-core/config/module.php MUST NOT declare sso_identity — it was moved to aero-auth (Plan 05 T6). '.
            'Two declarations would create duplicate permission rows in modules:sync.');
    }

    public function test_identity_routes_use_auth_namespace(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/routes/identity.php');

        $oldCount = preg_match_all('/hrmac:core\.sso_identity\./', $routes);
        $this->assertSame(0, $oldCount,
            "routes/identity.php must not contain hrmac:core.sso_identity.* — found {$oldCount}. ".
            "All references must use hrmac:auth.sso_identity.* after the Plan 05 T6 move.");

        $newCount = preg_match_all('/hrmac:auth\.sso_identity\./', $routes);
        $this->assertGreaterThanOrEqual(20, $newCount,
            "routes/identity.php must contain at least 20 hrmac:auth.sso_identity.* references (12 components × ~2-3 actions). ".
            "Found {$newCount}.");
    }

    public function test_identity_frontend_pages_use_auth_namespace(): void
    {
        $pagesDir = dirname(__DIR__, 4).'/aero-ui/resources/js/Pages/Core/Identity';

        if (! is_dir($pagesDir)) {
            $this->markTestSkipped("Identity pages dir not found at {$pagesDir}");
        }

        foreach (glob($pagesDir.'/*.jsx') as $page) {
            $content = file_get_contents($page);
            $oldCount = substr_count($content, 'core.sso_identity');
            $this->assertSame(0, $oldCount,
                basename($page)." must NOT contain core.sso_identity.* — found {$oldCount}. ".
                "Update useHRMAC() calls to use 'auth.sso_identity.*' after the Plan 05 T6 move.");
        }
    }
}
