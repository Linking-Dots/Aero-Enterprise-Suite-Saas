<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature;

use Aero\HRMAC\Services\ModuleDiscoveryService;
use Aero\HRMAC\Tests\PackageTestCase;
use Illuminate\Support\Facades\File;

/**
 * Phase 2, Task 4 of the shared access-control consolidation plan
 * (docs/superpowers/plans/2026-07-06-shared-auth-access-control-consolidation.md).
 *
 * Pins that packages/aero-auth/config/module.php declares the 'user_management'
 * submodule (users + user_invitations components) — the UNION of the
 * duplicate declarations previously in aero-core (`user_management`) and
 * aero-platform (`platform-users` -> `landlord-user-list`) — and that it is
 * discoverable through ModuleDiscoveryService::getAllPermissionCodes(), the
 * same flattening path `modules:sync` uses to populate the roles/permissions
 * tables.
 *
 * Mirrors packages/aero-hrmac/tests/Feature/HrmacModuleDefinitionTest.php:
 * builds a synthetic vendor/aero/auth/config/module.php from THIS package's
 * real config file and points the app's base path at it, proving aero-auth's
 * own module definition is discoverable on its own.
 */
class AuthUserManagementModuleTest extends PackageTestCase
{
    private ?string $syntheticBasePath = null;

    protected function tearDown(): void
    {
        if ($this->syntheticBasePath && File::exists($this->syntheticBasePath)) {
            File::deleteDirectory($this->syntheticBasePath);
        }

        parent::tearDown();
    }

    public function test_auth_declares_user_management_submodule_permission_codes(): void
    {
        $this->syntheticBasePath = sys_get_temp_dir().'/aero-auth-module-discovery-'.uniqid();
        $configDir = $this->syntheticBasePath.'/vendor/aero/auth/config';

        File::makeDirectory($configDir, 0755, true);
        File::copy(dirname(__DIR__, 2).'/config/module.php', $configDir.'/module.php');

        $this->app->setBasePath($this->syntheticBasePath);

        $names = app(ModuleDiscoveryService::class)
            ->getAllPermissionCodes()
            ->pluck('name');

        $this->assertContains('auth.user_management.users.view', $names);
        $this->assertContains('auth.user_management.users.create', $names);
        $this->assertContains('auth.user_management.users.impersonate', $names);
        $this->assertContains('auth.user_management.user_invitations.resend', $names);
    }
}
