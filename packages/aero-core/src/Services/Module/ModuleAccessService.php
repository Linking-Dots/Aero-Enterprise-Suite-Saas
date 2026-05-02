<?php

namespace Aero\Core\Services\Module;

use Aero\Core\Models\Module;
use Aero\Core\Models\ModuleComponent;
use Aero\Core\Models\ModuleComponentAction;
use Aero\Core\Models\SubModule;
use Aero\Core\Models\User;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Aero\Platform\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * Module Access Service
 *
 * Handles checking user access to modules based on:
 * 1. Super Admin Bypass - Platform/Tenant Super Admins have special access
 * 2. Plan Access - Is the module/submodule/component/action in the tenant's subscription plan?
 * 3. Role Module Access - Does the user's role have access to the module hierarchy?
 *
 * Access Formula: User Access = Super Admin Bypass OR (Plan Access ∩ Role Module Access)
 *
 * Compliance: Section 7 - Access Control Logic
 */
class ModuleAccessService
{
    protected RoleModuleAccessService $roleAccessService;

    public function __construct(RoleModuleAccessService $roleAccessService)
    {
        $this->roleAccessService = $roleAccessService;
    }

    /**
     * Check if user is platform super administrator.
     */
    protected function isPlatformSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Administrator');
    }

    /**
     * Check if user is tenant super administrator.
     */
    protected function isTenantSuperAdmin(User $user): bool
    {
        return $user->hasRole('tenant_super_administrator');
    }

    /**
     * Check if user's role has access to a module by ID.
     */
    protected function userHasModuleAccess(User $user, int $moduleId): bool
    {
        // Get user's roles and check each
        $roles = $user->roles;

        foreach ($roles as $role) {
            if ($this->roleAccessService->canAccessModule($role, $moduleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user's role has access to a submodule by ID.
     */
    protected function userHasSubModuleAccess(User $user, int $subModuleId): bool
    {
        $roles = $user->roles;

        foreach ($roles as $role) {
            if ($this->roleAccessService->canAccessSubModule($role, $subModuleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user's role has access to a component by ID.
     */
    protected function userHasComponentAccess(User $user, int $componentId): bool
    {
        $roles = $user->roles;

        foreach ($roles as $role) {
            if ($this->roleAccessService->canAccessComponent($role, $componentId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user's role has access to an action by ID.
     */
    protected function userHasActionAccess(User $user, int $actionId): bool
    {
        $roles = $user->roles;

        foreach ($roles as $role) {
            if ($this->roleAccessService->canAccessAction($role, $actionId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's access scope for an action.
     */
    public function getUserAccessScope(User $user, int $actionId): ?string
    {
        $roles = $user->roles;

        // Return the most permissive scope from all roles
        $scopes = [];
        foreach ($roles as $role) {
            $scope = $this->roleAccessService->getAccessScope($role, $actionId);
            if ($scope) {
                $scopes[] = $scope;
            }
        }

        if (empty($scopes)) {
            return null;
        }

        // Priority: all > department > team > own
        if (in_array('all', $scopes)) {
            return 'all';
        }
        if (in_array('department', $scopes)) {
            return 'department';
        }
        if (in_array('team', $scopes)) {
            return 'team';
        }

        return 'own';
    }

    /**
     * Check if user can access a module.
     *
     * @return array{allowed: bool, reason: string, message: string}
     */
    public function canAccessModule(User $user, string $moduleCode): array
    {
        // EXCEPTION: Super Administrator bypasses everything
        if ($this->isPlatformSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'platform_super_admin', 'message' => 'Platform Super Admin access.'];
        }

        // Step 1: Check plan access
        if (! $this->isPlanAllowed($user, 'module', $moduleCode)) {
            return [
                'allowed' => false,
                'reason' => 'plan_restriction',
                'message' => "Module '{$moduleCode}' is not included in your subscription plan.",
            ];
        }

        // Step 2: Find the module
        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Module '{$moduleCode}' does not exist.",
            ];
        }

        // EXCEPTION: tenant_super_administrator bypasses access checks (but NOT subscription)
        if ($this->isTenantSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'tenant_super_admin', 'message' => 'Tenant Super Admin access.'];
        }

        // Step 3: Check role module access
        $hasAccess = $this->userHasModuleAccess($user, $module->id);

        if (! $hasAccess) {
            return [
                'allowed' => false,
                'reason' => 'no_module_access',
                'message' => "You don't have access to this module.",
            ];
        }

        return ['allowed' => true, 'reason' => 'success', 'message' => 'Access granted.'];
    }

    /**
     * Check if user can access a submodule.
     *
     * @return array{allowed: bool, reason: string, message: string}
     */
    public function canAccessSubModule(User $user, string $moduleCode, string $subModuleCode): array
    {
        // EXCEPTION: Super Administrator bypasses everything
        if ($this->isPlatformSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'platform_super_admin', 'message' => 'Platform Super Admin access.'];
        }

        // Find the module first
        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Module '{$moduleCode}' does not exist.",
            ];
        }

        // Check plan access for module
        if (! $this->isPlanAllowed($user, 'module', $moduleCode)) {
            return [
                'allowed' => false,
                'reason' => 'plan_restriction',
                'message' => "Module '{$moduleCode}' is not included in your subscription plan.",
            ];
        }

        // Find the submodule
        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->first();

        if (! $subModule) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Feature '{$subModuleCode}' does not exist.",
            ];
        }

        // EXCEPTION: tenant_super_administrator bypasses access checks (but NOT subscription)
        if ($this->isTenantSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'tenant_super_admin', 'message' => 'Tenant Super Admin access.'];
        }

        // Check role submodule access
        $hasAccess = $this->userHasSubModuleAccess($user, $subModule->id);

        if (! $hasAccess) {
            return [
                'allowed' => false,
                'reason' => 'no_submodule_access',
                'message' => "You don't have access to this feature.",
            ];
        }

        return ['allowed' => true, 'reason' => 'success', 'message' => 'Access granted.'];
    }

    /**
     * Check if user can access a component.
     *
     * @return array{allowed: bool, reason: string, message: string}
     */
    public function canAccessComponent(User $user, string $moduleCode, string $subModuleCode, string $componentCode): array
    {
        // EXCEPTION: Super Administrator bypasses everything
        if ($this->isPlatformSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'platform_super_admin', 'message' => 'Platform Super Admin access.'];
        }

        // Find the module
        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Module '{$moduleCode}' does not exist.",
            ];
        }

        // Check plan access
        if (! $this->isPlanAllowed($user, 'module', $moduleCode)) {
            return [
                'allowed' => false,
                'reason' => 'plan_restriction',
                'message' => "Module '{$moduleCode}' is not included in your subscription plan.",
            ];
        }

        // Find the submodule
        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->first();

        if (! $subModule) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Feature '{$subModuleCode}' does not exist.",
            ];
        }

        // Find the component
        $component = ModuleComponent::where('sub_module_id', $subModule->id)
            ->where('code', $componentCode)
            ->first();

        if (! $component) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Component '{$componentCode}' does not exist.",
            ];
        }

        // EXCEPTION: tenant_super_administrator bypasses access checks (but NOT subscription)
        if ($this->isTenantSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'tenant_super_admin', 'message' => 'Tenant Super Admin access.'];
        }

        // Check role component access
        $hasAccess = $this->userHasComponentAccess($user, $component->id);

        if (! $hasAccess) {
            return [
                'allowed' => false,
                'reason' => 'no_component_access',
                'message' => "You don't have access to this component.",
            ];
        }

        return ['allowed' => true, 'reason' => 'success', 'message' => 'Access granted.'];
    }

    /**
     * Check if user can perform an action.
     *
     * @return array{allowed: bool, reason: string, message: string}
     */
    public function canPerformAction(
        User $user,
        string $moduleCode,
        string $subModuleCode,
        string $componentCode,
        string $actionCode
    ): array {
        // EXCEPTION: Super Administrator bypasses everything
        if ($this->isPlatformSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'platform_super_admin', 'message' => 'Platform Super Admin access.'];
        }

        // Find the module
        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Module '{$moduleCode}' does not exist.",
            ];
        }

        // Check plan access
        if (! $this->isPlanAllowed($user, 'module', $moduleCode)) {
            return [
                'allowed' => false,
                'reason' => 'plan_restriction',
                'message' => "Module '{$moduleCode}' is not included in your subscription plan.",
            ];
        }

        // Find the submodule
        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->first();

        if (! $subModule) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Feature '{$subModuleCode}' does not exist.",
            ];
        }

        // Find the component
        $component = ModuleComponent::where('sub_module_id', $subModule->id)
            ->where('code', $componentCode)
            ->first();

        if (! $component) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Component '{$componentCode}' does not exist.",
            ];
        }

        // Find the action
        $action = ModuleComponentAction::where('module_component_id', $component->id)
            ->where('code', $actionCode)
            ->first();

        if (! $action) {
            return [
                'allowed' => false,
                'reason' => 'not_found',
                'message' => "Action '{$actionCode}' does not exist.",
            ];
        }

        // EXCEPTION: tenant_super_administrator bypasses access checks (but NOT subscription)
        if ($this->isTenantSuperAdmin($user)) {
            return ['allowed' => true, 'reason' => 'tenant_super_admin', 'message' => 'Tenant Super Admin access.'];
        }

        // Check role action access
        $hasAccess = $this->userHasActionAccess($user, $action->id);

        if (! $hasAccess) {
            return [
                'allowed' => false,
                'reason' => 'no_action_access',
                'message' => "You don't have permission to perform this action.",
            ];
        }

        return ['allowed' => true, 'reason' => 'success', 'message' => 'Access granted.'];
    }

    /**
     * Check if the tenant's plan allows access to a specific item.
     *
     * @param  string  $type  Type: module, submodule, component, action
     */
    protected function isPlanAllowed(
        User $user,
        string $type,
        string $moduleCode,
        ?string $subModuleCode = null,
        ?string $componentCode = null
    ): bool {
        // Get tenant's active modules from subscription OR custom selection
        $tenant = tenant();

        if (! $tenant) {
            return false;
        }

        $cacheKey = "tenant_modules_access:{$tenant->id}";

        $activeModuleCodes = Cache::remember($cacheKey, 300, function () use ($tenant) {
            $moduleCodes = [];

            // In SaaS mode, only include plan and custom modules if subscription is active
            $includePaidModules = true;
            if (is_saas_mode()) {
                $subscription = $tenant->currentSubscription;
                $includePaidModules = $subscription && $subscription->isActive();
            }

            if ($includePaidModules) {
                // Check 1: Get modules from subscription plan (if plan_id exists)
                if ($tenant->plan_id) {
                    $plan = Plan::find($tenant->plan_id);
                    if ($plan) {
                        $planModules = $plan->modules()
                            ->where('is_active', true)
                            ->pluck('modules.code')
                            ->toArray();
                        $moduleCodes = array_merge($moduleCodes, $planModules);
                    }
                }

                // Check 2: Get modules from tenant's custom modules collection
                if (! empty($tenant->modules) && is_array($tenant->modules)) {
                    $moduleCodes = array_merge($moduleCodes, $tenant->modules);
                }
            }

            // Add core modules (always accessible)
            $coreModules = Module::where('is_core', true)
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();
            $moduleCodes = array_merge($moduleCodes, $coreModules);

            // Return unique module codes
            return array_unique($moduleCodes);
        });

        // Check if module is allowed
        if (! in_array($moduleCode, $activeModuleCodes)) {
            return false;
        }

        // For now, if module is allowed, all its children are allowed
        // In the future, plan_module pivot can include submodule/component restrictions
        return true;
    }

    /**
     * Get all accessible modules for a user (respecting both plan and role access).
     */
    public function getAccessibleModules(User $user): array
    {
        $cacheKey = "user_accessible_modules:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $modules = Module::where('is_active', true)->orderBy('id')->get();
            $accessible = [];

            foreach ($modules as $module) {
                $check = $this->canAccessModule($user, $module->code);
                if ($check['allowed']) {
                    $accessible[] = [
                        'id' => $module->id,
                        'code' => $module->code,
                        'name' => $module->name,
                        'icon' => $module->icon,
                        'route_prefix' => $module->route_prefix,
                    ];
                }
            }

            return $accessible;
        });
    }

    /**
     * Clear user access cache.
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("user_accessible_modules:{$user->id}");

        $tenant = tenant();
        if ($tenant) {
            Cache::forget("tenant_modules_access:{$tenant->id}");
        }
    }
}
