<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Policies\TenantPolicy;
use Aero\Platform\Tests\TestCase;

/**
 * @covers \Aero\Platform\Policies\TenantPolicy
 */
class TenantPolicyTest extends TestCase
{
    protected TenantPolicy $policy;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $regularUser;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TenantPolicy;

        // Create roles with appropriate module access
        $this->setupRolesAndUsers();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    protected function setupRolesAndUsers(): void
    {
        // Create Super Administrator role (has all access)
        $superAdminRole = Role::create([
            'name' => 'Super Administrator',
            'guard_name' => 'landlord',
        ]);

        // Create Platform Admin role with tenant management access
        $platformAdminRole = Role::create([
            'name' => 'Platform Admin',
            'guard_name' => 'landlord',
        ]);

        // Create Regular User role with limited access
        $regularRole = Role::create([
            'name' => 'Regular User',
            'guard_name' => 'landlord',
        ]);

        // Create users with roles
        $this->superAdmin = LandlordUserFactory::new()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $this->adminUser = LandlordUserFactory::new()->create();
        $this->adminUser->assignRole($platformAdminRole);

        $this->regularUser = LandlordUserFactory::new()->create();
        $this->regularUser->assignRole($regularRole);
    }

    public function test_super_admin_can_view_any_tenants(): void
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
    }

    public function test_super_admin_can_view_tenant(): void
    {
        $this->assertTrue($this->policy->view($this->superAdmin, $this->tenant));
    }

    public function test_super_admin_can_create_tenant(): void
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
    }

    public function test_super_admin_can_update_tenant(): void
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->tenant));
    }

    public function test_super_admin_can_delete_tenant(): void
    {
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->tenant));
    }

    public function test_super_admin_can_suspend_active_tenant(): void
    {
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertTrue($this->policy->suspend($this->superAdmin, $activeTenant));
    }

    public function test_cannot_suspend_already_suspended_tenant(): void
    {
        $suspendedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->assertFalse($this->policy->suspend($this->superAdmin, $suspendedTenant));
    }

    public function test_cannot_suspend_archived_tenant(): void
    {
        $archivedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);

        $this->assertFalse($this->policy->suspend($this->superAdmin, $archivedTenant));
    }

    public function test_super_admin_can_activate_suspended_tenant(): void
    {
        $suspendedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->assertTrue($this->policy->activate($this->superAdmin, $suspendedTenant));
    }

    public function test_super_admin_can_activate_pending_tenant(): void
    {
        $pendingTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_PENDING,
        ]);

        $this->assertTrue($this->policy->activate($this->superAdmin, $pendingTenant));
    }

    public function test_cannot_activate_already_active_tenant(): void
    {
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertFalse($this->policy->activate($this->superAdmin, $activeTenant));
    }

    public function test_super_admin_can_archive_active_tenant(): void
    {
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertTrue($this->policy->archive($this->superAdmin, $activeTenant));
    }

    public function test_cannot_archive_already_archived_tenant(): void
    {
        $archivedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);

        $this->assertFalse($this->policy->archive($this->superAdmin, $archivedTenant));
    }

    public function test_super_admin_can_impersonate_active_tenant(): void
    {
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertTrue($this->policy->impersonate($this->superAdmin, $activeTenant));
    }

    public function test_super_admin_can_impersonate_pending_tenant(): void
    {
        $pendingTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_PENDING,
        ]);

        $this->assertTrue($this->policy->impersonate($this->superAdmin, $pendingTenant));
    }

    public function test_cannot_impersonate_suspended_tenant(): void
    {
        $suspendedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->assertFalse($this->policy->impersonate($this->superAdmin, $suspendedTenant));
    }

    public function test_cannot_impersonate_archived_tenant(): void
    {
        $archivedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);

        $this->assertFalse($this->policy->impersonate($this->superAdmin, $archivedTenant));
    }

    public function test_super_admin_can_retry_provisioning_for_failed_tenant(): void
    {
        $failedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_FAILED,
        ]);

        $this->assertTrue($this->policy->retryProvisioning($this->superAdmin, $failedTenant));
    }

    public function test_cannot_retry_provisioning_for_non_failed_tenant(): void
    {
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertFalse($this->policy->retryProvisioning($this->superAdmin, $activeTenant));
    }

    public function test_super_admin_can_view_domains(): void
    {
        $this->assertTrue($this->policy->viewDomains($this->superAdmin));
    }

    public function test_super_admin_can_manage_domains(): void
    {
        $this->assertTrue($this->policy->manageDomains($this->superAdmin));
    }

    public function test_super_admin_can_view_databases(): void
    {
        $this->assertTrue($this->policy->viewDatabases($this->superAdmin));
    }

    public function test_super_admin_can_migrate_databases(): void
    {
        $this->assertTrue($this->policy->migrateDatabases($this->superAdmin));
    }

    public function test_super_admin_can_backup_databases(): void
    {
        $this->assertTrue($this->policy->backupDatabases($this->superAdmin));
    }

    public function test_super_admin_can_force_delete_tenant(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->superAdmin, $this->tenant));
    }

    public function test_super_admin_can_restore_tenant(): void
    {
        $this->assertTrue($this->policy->restore($this->superAdmin, $this->tenant));
    }
}
