<?php

namespace Aero\Platform\Services;

use Aero\Core\Models\User;
use Aero\Core\Support\TenantCache;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\SubModule;

/**
 * Module Access Service
 *
 * Handles module, sub-module, component, and action access control.
 * Uses role-based permissions from Spatie and subscription-based access.
 *
 * Access Logic:
 * 1. Check if module requires subscription
 * 2. Check if user's role has permission for the specific level
 * 3. Cache results for performance
 */
class ModuleAccessService
{
    /**
     * Check if user can access a module.
     */
    public function canAccessModule(User $user, string $moduleCode): array
    {
        $cacheKey = "user.{$user->id}.module.{$moduleCode}";

        return TenantCache::tags(['module-access', "user-{$user->id}"])->remember($cacheKey, 3600, function () use ($user, $moduleCode) {
            $module = Module::where('code', $moduleCode)->where('is_active', true)->first();

            if (! $module) {
                return [
                    'allowed' => false,
                    'reason' => 'Module not found or inactive',
                ];
            }

            // Check subscription if required
            if ($module->requires_subscription) {
                if (! $this->hasSubscriptionAccess($user, $moduleCode)) {
                    return [
                        'allowed' => false,
                        'reason' => 'Module requires active subscription',
                    ];
                }
            }

            // Check role permissions
            if (! $this->hasRolePermission($user, "module.{$moduleCode}")) {
                return [
                    'allowed' => false,
                    'reason' => 'Insufficient permissions',
                ];
            }

            return [
                'allowed' => true,
                'module' => $module,
            ];
        });
    }

    /**
     * Check if user can access a sub-module.
     */
    public function canAccessSubModule(User $user, string $moduleCode, string $subModuleCode): array
    {
        // First check module access
        $moduleAccess = $this->canAccessModule($user, $moduleCode);
        if (! $moduleAccess['allowed']) {
            return $moduleAccess;
        }

        $cacheKey = "user.{$user->id}.submodule.{$moduleCode}.{$subModuleCode}";

        return TenantCache::tags(['module-access', "user-{$user->id}"])->remember($cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode) {
            $subModule = SubModule::whereHas('module', function ($query) use ($moduleCode) {
                $query->where('code', $moduleCode);
            })
                ->where('code', $subModuleCode)
                ->where('is_active', true)
                ->first();

            if (! $subModule) {
                return [
                    'allowed' => false,
                    'reason' => 'Sub-module not found or inactive',
                ];
            }

            // Check role permissions
            if (! $this->hasRolePermission($user, "submodule.{$moduleCode}.{$subModuleCode}")) {
                return [
                    'allowed' => false,
                    'reason' => 'Insufficient permissions',
                ];
            }

            return [
                'allowed' => true,
                'subModule' => $subModule,
            ];
        });
    }

    /**
     * Check if user can access a component.
     */
    public function canAccessComponent(User $user, string $moduleCode, string $subModuleCode, string $componentCode): array
    {
        // First check sub-module access
        $subModuleAccess = $this->canAccessSubModule($user, $moduleCode, $subModuleCode);
        if (! $subModuleAccess['allowed']) {
            return $subModuleAccess;
        }

        $cacheKey = "user.{$user->id}.component.{$moduleCode}.{$subModuleCode}.{$componentCode}";

        return TenantCache::tags(['module-access', "user-{$user->id}"])->remember($cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode, $componentCode) {
            $component = ModuleComponent::whereHas('subModule.module', function ($query) use ($moduleCode) {
                $query->where('code', $moduleCode);
            })
                ->whereHas('subModule', function ($query) use ($subModuleCode) {
                    $query->where('code', $subModuleCode);
                })
                ->where('code', $componentCode)
                ->where('is_active', true)
                ->first();

            if (! $component) {
                return [
                    'allowed' => false,
                    'reason' => 'Component not found or inactive',
                ];
            }

            // Check role permissions
            if (! $this->hasRolePermission($user, "component.{$moduleCode}.{$subModuleCode}.{$componentCode}")) {
                return [
                    'allowed' => false,
                    'reason' => 'Insufficient permissions',
                ];
            }

            return [
                'allowed' => true,
                'component' => $component,
            ];
        });
    }

    /**
     * Check if user can perform an action.
     */
    public function canPerformAction(User $user, string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): array
    {
        // First check component access
        $componentAccess = $this->canAccessComponent($user, $moduleCode, $subModuleCode, $componentCode);
        if (! $componentAccess['allowed']) {
            return $componentAccess;
        }

        $cacheKey = "user.{$user->id}.action.{$moduleCode}.{$subModuleCode}.{$componentCode}.{$actionCode}";

        return TenantCache::tags(['module-access', "user-{$user->id}"])->remember($cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode, $componentCode, $actionCode) {
            $action = ModuleComponentAction::whereHas('component.subModule.module', function ($query) use ($moduleCode) {
                $query->where('code', $moduleCode);
            })
                ->whereHas('component.subModule', function ($query) use ($subModuleCode) {
                    $query->where('code', $subModuleCode);
                })
                ->whereHas('component', function ($query) use ($componentCode) {
                    $query->where('code', $componentCode);
                })
                ->where('code', $actionCode)
                ->where('is_active', true)
                ->first();

            if (! $action) {
                return [
                    'allowed' => false,
                    'reason' => 'Action not found or inactive',
                ];
            }

            // Check role permissions
            if (! $this->hasRolePermission($user, "action.{$moduleCode}.{$subModuleCode}.{$componentCode}.{$actionCode}")) {
                return [
                    'allowed' => false,
                    'reason' => 'Insufficient permissions',
                ];
            }

            return [
                'allowed' => true,
                'action' => $action,
            ];
        });
    }

    /**
     * Get all accessible modules for user.
     */
    public function getAccessibleModules(User $user): array
    {
        $cacheKey = "user.{$user->id}.accessible_modules";

        return TenantCache::tags(['module-access', "user-{$user->id}"])->remember($cacheKey, 3600, function () use ($user) {
            $modules = Module::where('is_active', true)
                ->with(['activeSubModules.activeComponents.activeActions'])
                ->ordered()
                ->get();

            return $modules->filter(function ($module) use ($user) {
                $access = $this->canAccessModule($user, $module->code);

                return $access['allowed'];
            })->values()->all();
        });
    }

    /**
     * Clear user's module access cache.
     */
    public function clearUserCache(User $user): void
    {
        TenantCache::tags(["user-{$user->id}"])->flush();
    }

    /**
     * Clear all module access caches.
     */
    public function clearAllCache(): void
    {
        TenantCache::tags(['module-access'])->flush();
    }

    /**
     * Check if user has subscription access to module.
     */
    protected function hasSubscriptionAccess(User $user, string $moduleCode): bool
    {
        // If no tenant context, allow (standalone mode)
        if (! tenancy()->initialized) {
            return true;
        }

        // Check if tenant has active subscription with module access
        $tenant = tenant();
        if (! $tenant) {
            return false;
        }

        // Check tenant's subscription (implement based on your subscription model)
        // Example: return $tenant->hasActiveSubscription() && $tenant->subscription->hasModule($moduleCode);

        // For now, return true (implement subscription check based on your platform)
        return true;
    }

    /**
     * Check if user has role permission.
     */
    protected function hasRolePermission(User $user, string $permission): bool
    {
        // Super admin has all access
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check direct permission or role permission
        return $user->hasPermissionTo($permission) || $user->hasAnyPermission([$permission, 'access-all-modules']);
    }
}
