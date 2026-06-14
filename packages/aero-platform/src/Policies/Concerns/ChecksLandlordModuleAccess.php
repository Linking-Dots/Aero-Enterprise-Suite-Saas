<?php

namespace Aero\Platform\Policies\Concerns;

use Aero\Auth\Models\User;
use Aero\Platform\Services\Module\ModuleAccessService;

/**
 * Trait ChecksLandlordModuleAccess
 *
 * Provides consistent module access checking for landlord/platform policies.
 * Uses role-based module access (not permissions) for authorization.
 *
 * Access Formula: User Access = Super Admin Bypass OR Role Module Access
 *
 * For platform modules (like tenant_management), the structure is:
 * - module (e.g., 'tenant_management')
 * - component (e.g., 'tenant_list')
 * - action (e.g., 'view', 'create', 'edit', 'delete', 'suspend', 'activate', 'impersonate')
 *
 * Note: Platform modules don't have submodules - they go directly from module to component.
 *
 * Usage in policies:
 * ```php
 * use ChecksLandlordModuleAccess;
 *
 * public function viewAny(User $user): bool
 * {
 *     return $this->canAccessPlatformModule($user, 'tenant_management');
 * }
 *
 * public function create(User $user): bool
 * {
 *     return $this->canPerformPlatformAction($user, 'tenant_management', 'tenant_list', 'create');
 * }
 * ```
 */
trait ChecksLandlordModuleAccess
{
    /**
     * Get the module access service instance.
     */
    protected function getModuleAccessService(): ModuleAccessService
    {
        return app(ModuleAccessService::class);
    }

    /**
     * Check if user is Super Administrator (bypasses all checks).
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Administrator');
    }

    /**
     * Check if user can access a platform module.
     *
     * @param  User  $user  The landlord user to check
     * @param  string  $moduleCode  The module code (e.g., 'tenant_management')
     */
    protected function canAccessPlatformModule(User $user, string $moduleCode): bool
    {
        // Super Admins bypass all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $result = $this->getModuleAccessService()->canAccessModule($user, $moduleCode);

        return $result['allowed'];
    }

    /**
     * Check if user can access a platform component.
     *
     * Platform modules have a flat structure: module → component → action
     * (no submodule level)
     *
     * @param  User  $user  The landlord user to check
     * @param  string  $moduleCode  The module code (e.g., 'tenant_management')
     * @param  string  $componentCode  The component code (e.g., 'tenant_list')
     */
    protected function canAccessPlatformComponent(User $user, string $moduleCode, string $componentCode): bool
    {
        // Super Admins bypass all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // For platform modules, we treat component as submodule for the service call
        // since platform modules have flat structure (module → component → action)
        $result = $this->getModuleAccessService()->canAccessSubModule($user, $moduleCode, $componentCode);

        return $result['allowed'];
    }

    /**
     * Check if user can perform an action on a platform component.
     *
     * @param  User  $user  The landlord user to check
     * @param  string  $moduleCode  The module code (e.g., 'tenant_management')
     * @param  string  $componentCode  The component code (e.g., 'tenant_list')
     * @param  string  $actionCode  The action code (e.g., 'create', 'view', 'edit', 'delete')
     */
    protected function canPerformPlatformAction(
        User $user,
        string $moduleCode,
        string $componentCode,
        string $actionCode
    ): bool {
        // Super Admins bypass all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // For platform modules, the structure is module → component → action
        // We need to map this to the service's expected structure
        // The ModuleAccessService expects: module → submodule → component → action
        // But platform modules use: module → component → action (no submodule)

        // Try calling with component as both submodule and component for compatibility
        $result = $this->getModuleAccessService()->canPerformAction(
            $user,
            $moduleCode,
            $componentCode, // Using component code as submodule
            $componentCode, // And also as component (they're the same in flat structure)
            $actionCode
        );

        return $result['allowed'];
    }

    /**
     * Check if user has any of the specified action permissions.
     *
     * @param  User  $user  The landlord user to check
     * @param  string  $moduleCode  The module code
     * @param  string  $componentCode  The component code
     * @param  array  $actionCodes  Array of action codes to check (any match = true)
     */
    protected function canPerformAnyPlatformAction(
        User $user,
        string $moduleCode,
        string $componentCode,
        array $actionCodes
    ): bool {
        // Super Admins bypass all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        foreach ($actionCodes as $actionCode) {
            if ($this->canPerformPlatformAction($user, $moduleCode, $componentCode, $actionCode)) {
                return true;
            }
        }

        return false;
    }
}
