<?php

namespace Aero\Core\Services;

use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\Core\Models\Module;
use Aero\Core\Models\SubModule;
use Aero\Core\Models\User;
use Aero\Core\Support\TenantCache;
use Closure;

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
     * Check if the cache store supports tagging.
     */
    protected function cacheSupportsTagging(): bool
    {
        try {
            $driver = config('cache.default');
            $store = config("cache.stores.{$driver}.driver");

            // Only redis, memcached, and dynamodb support tagging
            return in_array($store, ['redis', 'memcached', 'dynamodb']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Cache with tags if supported, otherwise fall back to regular cache.
     *
     * @param  array<string>  $tags
     */
    protected function rememberWithOptionalTags(array $tags, string $key, int $ttl, Closure $callback): mixed
    {
        if ($this->cacheSupportsTagging()) {
            return TenantCache::tags($tags)->remember($key, $ttl, $callback);
        }

        // Prefix the key with tags for manual grouping when tagging is not supported
        $prefixedKey = 'module_access.'.implode('.', $tags).'.'.$key;

        return TenantCache::remember($prefixedKey, $ttl, $callback);
    }

    /**
     * Flush cache by tags if supported, otherwise clear matching keys.
     *
     * @param  array<string>  $tags
     */
    protected function flushWithOptionalTags(array $tags): void
    {
        if ($this->cacheSupportsTagging()) {
            TenantCache::tags($tags)->flush();

            return;
        }

        // For non-tagging cache stores, we can't selectively flush by tags
        // The cache will naturally expire, or use TenantCache::flush() to clear all
        // In production, consider using Redis or Memcached for proper tag support
    }

    /**
     * Check if user can access a module.
     */
    public function canAccessModule(User $user, string $moduleCode): array
    {
        // Super admin bypass - allow all modules without checking existence
        if ($user->hasRole('super-admin') || $user->hasRole('Super Administrator') || $user->isSuperAdmin()) {
            return [
                'allowed' => true,
                'module' => null,
                'bypass' => 'super_admin',
            ];
        }

        $cacheKey = "user.{$user->id}.module.{$moduleCode}";

        return $this->rememberWithOptionalTags(['module-access', "user-{$user->id}"], $cacheKey, 3600, function () use ($user, $moduleCode) {
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
        // Super admin bypass - allow all sub-modules without checking existence
        if ($user->hasRole('super-admin') || $user->hasRole('Super Administrator') || $user->isSuperAdmin()) {
            return [
                'allowed' => true,
                'subModule' => null,
                'bypass' => 'super_admin',
            ];
        }

        // First check module access
        $moduleAccess = $this->canAccessModule($user, $moduleCode);
        if (! $moduleAccess['allowed']) {
            return $moduleAccess;
        }

        $cacheKey = "user.{$user->id}.submodule.{$moduleCode}.{$subModuleCode}";

        return $this->rememberWithOptionalTags(['module-access', "user-{$user->id}"], $cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode) {
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
        // Super admin bypass - allow all components without checking existence
        if ($user->hasRole('super-admin') || $user->hasRole('Super Administrator') || $user->isSuperAdmin()) {
            return [
                'allowed' => true,
                'component' => null,
                'bypass' => 'super_admin',
            ];
        }

        // First check sub-module access
        $subModuleAccess = $this->canAccessSubModule($user, $moduleCode, $subModuleCode);
        if (! $subModuleAccess['allowed']) {
            return $subModuleAccess;
        }

        $cacheKey = "user.{$user->id}.component.{$moduleCode}.{$subModuleCode}.{$componentCode}";

        return $this->rememberWithOptionalTags(['module-access', "user-{$user->id}"], $cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode, $componentCode) {
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
        // Super admin bypass - allow all actions without checking existence
        if ($user->hasRole('super-admin') || $user->hasRole('Super Administrator') || $user->isSuperAdmin()) {
            return [
                'allowed' => true,
                'action' => null,
                'bypass' => 'super_admin',
            ];
        }

        // First check component access
        $componentAccess = $this->canAccessComponent($user, $moduleCode, $subModuleCode, $componentCode);
        if (! $componentAccess['allowed']) {
            return $componentAccess;
        }

        $cacheKey = "user.{$user->id}.action.{$moduleCode}.{$subModuleCode}.{$componentCode}.{$actionCode}";

        return $this->rememberWithOptionalTags(['module-access', "user-{$user->id}"], $cacheKey, 3600, function () use ($user, $moduleCode, $subModuleCode, $componentCode, $actionCode) {
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

        return $this->rememberWithOptionalTags(['module-access', "user-{$user->id}"], $cacheKey, 3600, function () use ($user) {
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
        $this->flushWithOptionalTags(["user-{$user->id}"]);
    }

    /**
     * Clear all module access caches.
     */
    public function clearAllCache(): void
    {
        $this->flushWithOptionalTags(['module-access']);
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
        // Super admin (both role name formats) has all access
        if ($user->hasRole('super-admin') || $user->hasRole('Super Administrator') || $user->isSuperAdmin()) {
            return true;
        }

        // Check using HRMAC's role module access system
        // Permission format: module.hrm, submodule.hrm.employees, component.hrm.employees.list, action.hrm.employees.list.view
        $parts = explode('.', $permission);
        $type = $parts[0] ?? '';

        // Get user's primary role
        $role = $user->roles->first();
        if (! $role) {
            return false;
        }

        // Use HRMAC's Role model methods for access check
        if (method_exists($role, 'hasFullAccess') && $role->hasFullAccess()) {
            return true;
        }

        switch ($type) {
            case 'module':
                $moduleCode = $parts[1] ?? '';

                return $role->hasModuleAccess($moduleCode);

            case 'submodule':
                $moduleCode = $parts[1] ?? '';
                $subModuleCode = $parts[2] ?? '';

                return $role->hasSubModuleAccess($moduleCode, $subModuleCode);

            case 'component':
                $moduleCode = $parts[1] ?? '';
                $subModuleCode = $parts[2] ?? '';
                $componentCode = $parts[3] ?? '';

                // Components are accessible if submodule is accessible
                return $role->hasSubModuleAccess($moduleCode, $subModuleCode);

            case 'action':
                $moduleCode = $parts[1] ?? '';
                $subModuleCode = $parts[2] ?? '';
                $componentCode = $parts[3] ?? '';
                $actionCode = $parts[4] ?? '';

                return $role->hasActionAccess($moduleCode, $subModuleCode, $componentCode, $actionCode);

            default:
                return false;
        }
    }
}
