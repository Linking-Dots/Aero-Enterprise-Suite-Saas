<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Feature;

use Aero\HRMAC\Services\ModuleDiscoveryService;
use Aero\HRMAC\Tests\PackageTestCase;
use Illuminate\Support\Facades\File;

/**
 * Phase 1, Task 1 of the shared access-control consolidation plan
 * (docs/superpowers/plans/2026-07-06-shared-auth-access-control-consolidation.md).
 *
 * Pins that packages/aero-hrmac/config/module.php declares the 'roles_permissions'
 * submodule (roles + module_access components) and that it is discoverable through
 * ModuleDiscoveryService::getAllPermissionCodes(), the same flattening path used to
 * sync module hierarchy into the database.
 *
 * ModuleDiscoveryService scans base_path('vendor/aero/*') for config/module.php files.
 * PackageTestCase boots HRMACServiceProvider in filesystem isolation (Testbench's
 * workbench skeleton has no vendor/aero/* directory), so this test builds a minimal
 * synthetic vendor/aero/hrmac/config/module.php from THIS package's real config file
 * and points the app's base path at it — proving hrmac's own module definition is
 * discoverable on its own, without pulling in aero-core or aero-platform.
 */
class HrmacModuleDefinitionTest extends PackageTestCase
{
    private ?string $syntheticBasePath = null;

    protected function tearDown(): void
    {
        if ($this->syntheticBasePath && File::exists($this->syntheticBasePath)) {
            File::deleteDirectory($this->syntheticBasePath);
        }

        parent::tearDown();
    }

    public function test_hrmac_declares_roles_permissions_component_actions(): void
    {
        $this->syntheticBasePath = sys_get_temp_dir().'/aero-hrmac-module-discovery-'.uniqid();
        $configDir = $this->syntheticBasePath.'/vendor/aero/hrmac/config';

        File::makeDirectory($configDir, 0755, true);
        File::copy(dirname(__DIR__, 2).'/config/module.php', $configDir.'/module.php');

        $this->app->setBasePath($this->syntheticBasePath);

        $names = app(ModuleDiscoveryService::class)
            ->getAllPermissionCodes()
            ->pluck('name');

        $this->assertContains('hrmac.roles_permissions.roles.view', $names);
        $this->assertContains('hrmac.roles_permissions.module_access.configure', $names);
    }
}
