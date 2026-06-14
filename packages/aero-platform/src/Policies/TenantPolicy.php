<?php

declare(strict_types=1);

namespace Aero\Platform\Policies;

use Aero\Auth\Models\User;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Policies\Concerns\ChecksLandlordModuleAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Tenant Authorization Policy
 *
 * Provides fine-grained authorization for tenant management operations.
 * Uses role-based module access (not permissions) for authorization.
 *
 * Module Path: tenant_management → tenant_list → {action}
 *
 * Available actions from config/module.php:
 * - view: View Tenants
 * - create: Create Tenant
 * - edit: Edit Tenant
 * - delete: Delete Tenant
 * - suspend: Suspend Tenant
 * - activate: Activate Tenant
 * - impersonate: Impersonate Tenant
 */
class TenantPolicy
{
    use ChecksLandlordModuleAccess;
    use HandlesAuthorization;

    /**
     * Module and component codes for tenant management.
     */
    private const MODULE_CODE = 'tenant_management';

    private const COMPONENT_TENANT_LIST = 'tenant_list';

    private const COMPONENT_DOMAINS = 'tenant_domains';

    private const COMPONENT_DATABASES = 'tenant_databases';

    /**
     * Determine whether the user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'view'
        );
    }

    /**
     * Determine whether the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'view'
        );
    }

    /**
     * Determine whether the user can create tenants.
     */
    public function create(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'create'
        );
    }

    /**
     * Determine whether the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'edit'
        );
    }

    /**
     * Determine whether the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'delete'
        );
    }

    /**
     * Determine whether the user can suspend the tenant.
     */
    public function suspend(User $user, Tenant $tenant): bool
    {
        // Cannot suspend already suspended or archived tenants
        if (in_array($tenant->status, [Tenant::STATUS_SUSPENDED, Tenant::STATUS_ARCHIVED])) {
            return false;
        }

        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'suspend'
        );
    }

    /**
     * Determine whether the user can activate the tenant.
     */
    public function activate(User $user, Tenant $tenant): bool
    {
        // Can only activate suspended or pending tenants
        if (! in_array($tenant->status, [Tenant::STATUS_SUSPENDED, Tenant::STATUS_PENDING])) {
            return false;
        }

        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'activate'
        );
    }

    /**
     * Determine whether the user can archive the tenant.
     */
    public function archive(User $user, Tenant $tenant): bool
    {
        // Cannot archive already archived tenants
        if ($tenant->status === Tenant::STATUS_ARCHIVED) {
            return false;
        }

        // Archive uses delete action from module config
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'delete'
        );
    }

    /**
     * Determine whether the user can restore the tenant.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        // Restore requires edit permission
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'edit'
        );
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'delete'
        );
    }

    /**
     * Determine whether the user can impersonate the tenant.
     */
    public function impersonate(User $user, Tenant $tenant): bool
    {
        // Cannot impersonate inactive tenants
        if (! in_array($tenant->status, [Tenant::STATUS_ACTIVE, Tenant::STATUS_PENDING])) {
            return false;
        }

        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'impersonate'
        );
    }

    /**
     * Determine whether the user can retry provisioning for a failed tenant.
     */
    public function retryProvisioning(User $user, Tenant $tenant): bool
    {
        // Can only retry provisioning for failed tenants
        if ($tenant->status !== Tenant::STATUS_FAILED) {
            return false;
        }

        // Retry provisioning requires create permission
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_TENANT_LIST,
            'create'
        );
    }

    /**
     * Determine whether the user can view tenant domains.
     */
    public function viewDomains(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_DOMAINS,
            'view'
        );
    }

    /**
     * Determine whether the user can manage tenant domains.
     */
    public function manageDomains(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_DOMAINS,
            'manage'
        );
    }

    /**
     * Determine whether the user can view tenant databases.
     */
    public function viewDatabases(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_DATABASES,
            'view'
        );
    }

    /**
     * Determine whether the user can run tenant database migrations.
     */
    public function migrateDatabases(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_DATABASES,
            'migrate'
        );
    }

    /**
     * Determine whether the user can backup tenant databases.
     */
    public function backupDatabases(User $user): bool
    {
        return $this->canPerformPlatformAction(
            $user,
            self::MODULE_CODE,
            self::COMPONENT_DATABASES,
            'backup'
        );
    }
}
