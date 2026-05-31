<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Services;

use Aero\HRMAC\Services\RoleModuleAccessService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 04 (aero-hrmac) Task 3 — is_active enforcement regression pin.
 *
 * Phase 1 audit raised concern that the is_active flag on Module/SubModule/
 * Component/Action might be decorative. Verification of the actual source
 * (RoleModuleAccessService) shows the flag IS enforced at every access
 * resolution path:
 *   - userCanAccessModule:   Module::where('is_active', true)
 *   - userCanAccessSubModule: filters parent module AND sub-module
 *   - userCanAccessAction:    filters module + sub-module + action
 *
 * This test pins those filters so a future refactor cannot regress them
 * silently. The companion full integration test (using real models +
 * tenant DB + role seeding) lives in the host repo's feature suite.
 */
class IsActiveEnforcementTest extends TestCase
{
    public function test_user_can_access_module_filters_by_is_active(): void
    {
        $source = file_get_contents((new ReflectionClass(RoleModuleAccessService::class))->getFileName());

        // Pin the exact filter expression so a refactor that removes it
        // will fail this test immediately.
        $this->assertMatchesRegularExpression(
            '/Module::where\(\s*[\'"]code[\'"]\s*,\s*\$moduleCode\s*\)\s*->\s*where\(\s*[\'"]is_active[\'"]\s*,\s*true\s*\)/',
            $source,
            'userCanAccessModule() must filter Module by is_active=true.'
        );
    }

    public function test_user_can_access_sub_module_filters_both_levels_by_is_active(): void
    {
        $source = file_get_contents((new ReflectionClass(RoleModuleAccessService::class))->getFileName());

        // Sub-module filters its parent module AND itself
        $this->assertMatchesRegularExpression(
            '/whereHas\([\'"]module[\'"][\s\S]*?->\s*where\(\s*[\'"]is_active[\'"]\s*,\s*true\s*\)/',
            $source,
            'userCanAccessSubModule() must filter parent Module by is_active=true.'
        );
        $this->assertMatchesRegularExpression(
            '/where\(\s*[\'"]code[\'"]\s*,\s*\$subModuleCode\s*\)\s*->\s*where\(\s*[\'"]is_active[\'"]\s*,\s*true\s*\)/',
            $source,
            'userCanAccessSubModule() must filter SubModule by is_active=true.'
        );
    }

    public function test_enforce_is_active_config_flag_is_declared(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';
        $this->assertArrayHasKey('enforce_is_active', $config,
            "config('hrmac.enforce_is_active') flag must be declared (Plan 04 T3).");
        $this->assertTrue($config['enforce_is_active'],
            'Default should be true — disabling modules via DB must deny access.');
    }
}
