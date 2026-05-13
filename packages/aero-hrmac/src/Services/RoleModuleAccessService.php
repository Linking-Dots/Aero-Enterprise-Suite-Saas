<?php

declare(strict_types=1);

namespace Aero\HRMAC\Services;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\Action;
use Aero\HRMAC\Models\Component;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\SubModule;
use Illuminate\Support\Facades\Cache;

/**
 * Role Module Access Service
 *
 * Unified service for role-based module access control.
 * Works with both tenant and landlord databases based on context.
 *
 * This replaces permission-based access with direct role_module_access table lookups.
 * No dependency on Spatie permissions for module access checks.
 */
class RoleModuleAccessService implements RoleModuleAccessInterface
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Check if a role has access to a specific module by ID.
     */
    public function canAccessModule(mixed $role, int $moduleId): bool
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->getCacheKey("role_access:{$roleId}:module:{$moduleId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $moduleId) {
            return $this->modelForCurrentContext()::where('role_id', $roleId)
                ->where('module_id', $moduleId)
                ->whereNull('sub_module_id')
                ->whereNull('component_id')
                ->whereNull('action_id')
                ->exists();
        });
    }

    /**
     * Check if a role has access to a specific sub-module by ID.
     */
    public function canAccessSubModule(mixed $role, int $subModuleId): bool
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->getCacheKey("role_access:{$roleId}:sub_module:{$subModuleId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $subModuleId) {
            // Check direct sub-module access
            if ($this->modelForCurrentContext()::where('role_id', $roleId)
                ->where('sub_module_id', $subModuleId)
                ->whereNull('component_id')
                ->whereNull('action_id')
                ->exists()) {
                return true;
            }

            // Check parent module access (inheritance)
            $subModule = SubModule::find($subModuleId);
            if ($subModule && $subModule->module_id) {
                return $this->canAccessModule($roleId, $subModule->module_id);
            }

            return false;
        });
    }

    /**
     * Check if a role has access to a specific component by ID.
     */
    public function canAccessComponent(mixed $role, int $componentId): bool
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->getCacheKey("role_access:{$roleId}:component:{$componentId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $componentId) {
            // Check direct component access
            if ($this->modelForCurrentContext()::where('role_id', $roleId)
                ->where('component_id', $componentId)
                ->whereNull('action_id')
                ->exists()) {
                return true;
            }

            // Check parent sub-module access (inheritance)
            $component = Component::find($componentId);
            if ($component && $component->sub_module_id) {
                return $this->canAccessSubModule($roleId, $component->sub_module_id);
            }

            return false;
        });
    }

    /**
     * Check if a role has access to a specific action by ID.
     */
    public function canAccessAction(mixed $role, int $actionId): bool
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->getCacheKey("role_access:{$roleId}:action:{$actionId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $actionId) {
            // Check direct action access
            if ($this->modelForCurrentContext()::where('role_id', $roleId)
                ->where('action_id', $actionId)
                ->exists()) {
                return true;
            }

            // Check parent component access (inheritance)
            $action = Action::find($actionId);
            if ($action) {
                $componentId = $action->module_component_id ?? $action->component_id;
                if ($componentId) {
                    return $this->canAccessComponent($roleId, $componentId);
                }
            }

            return false;
        });
    }

    /**
     * Check if a user (through their roles) can access a module by code.
     */
    public function userCanAccessModule(mixed $user, string $moduleCode): bool
    {
        if (! $user) {
            return false;
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $module = Module::where('code', $moduleCode)
            ->where('is_active', true)
            ->first();

        if (! $module) {
            return false;
        }

        // Check each of user's roles
        foreach ($user->roles as $role) {
            if ($this->canAccessModule($role, $module->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user (through their roles) can access a sub-module by codes.
     */
    public function userCanAccessSubModule(mixed $user, string $moduleCode, string $subModuleCode): bool
    {
        if (! $user) {
            return false;
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $subModule = SubModule::whereHas('module', function ($q) use ($moduleCode) {
            $q->where('code', $moduleCode)->where('is_active', true);
        })->where('code', $subModuleCode)->where('is_active', true)->first();

        if (! $subModule) {
            return false;
        }

        // Check each of user's roles
        foreach ($user->roles as $role) {
            if ($this->canAccessSubModule($role, $subModule->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user (through their roles) can perform a specific action by codes.
     *
     * @param  mixed  $user  The user to check
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The sub-module code (e.g., 'leaves')
     * @param  string  $actionCode  The action code (e.g., 'view_all', 'approve', 'manage')
     */
    public function userCanAccessAction(mixed $user, string $moduleCode, string $subModuleCode, string $actionCode): bool
    {
        if (! $user) {
            return false;
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // First check if user has sub-module level access (which grants all actions)
        if ($this->userCanAccessSubModule($user, $moduleCode, $subModuleCode)) {
            return true;
        }

        // Get the module hierarchy
        $module = Module::where('code', $moduleCode)->where('is_active', true)->first();
        if (! $module) {
            return false;
        }

        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->where('is_active', true)
            ->first();
        if (! $subModule) {
            return false;
        }

        // Find action by code in any component of this sub-module
        $action = Action::where('code', $actionCode)
            ->where('is_active', true)
            ->whereHas('component', function ($q) use ($subModule) {
                $q->where('sub_module_id', $subModule->id)->where('is_active', true);
            })
            ->first();

        if (! $action) {
            // Action doesn't exist in the system - return false (no access)
            return false;
        }

        // Check each of user's roles for action access
        foreach ($user->roles as $role) {
            if ($this->canAccessAction($role, $action->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first accessible route for a user.
     * Used for smart landing page redirects.
     */
    public function getFirstAccessibleRoute(mixed $user): ?string
    {
        if (! $user) {
            return null;
        }

        // Super admin goes to dashboard
        if ($this->isSuperAdmin($user)) {
            return 'core.dashboard';
        }

        // Check if user has Dashboard access first
        if ($this->userCanAccessSubModule($user, 'core', 'dashboard')) {
            return 'core.dashboard';
        }

        // Get user's accessible sub-modules and find the first one with a route
        $subModuleIds = $this->getUserAccessibleSubModuleIds($user);
        if (empty($subModuleIds)) {
            return null;
        }

        // Get sub-modules with routes, prioritized
        $subModule = SubModule::whereIn('id', $subModuleIds)
            ->where('is_active', true)
            ->whereNotNull('route')
            ->orderBy('priority')
            ->first();

        if ($subModule && $subModule->route) {
            return $subModule->route;
        }

        // If no sub-module has a route, check parent modules
        $moduleIds = SubModule::whereIn('id', $subModuleIds)->pluck('module_id')->unique();
        $module = Module::whereIn('id', $moduleIds)
            ->where('is_active', true)
            ->whereNotNull('route_prefix')
            ->orderBy('priority')
            ->first();

        if ($module && $module->route_prefix) {
            return $module->route_prefix;
        }

        return null;
    }

    /**
     * Get all accessible module IDs for a role.
     */
    public function getAccessibleModuleIds(mixed $role): array
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->getCacheKey("role_accessible_modules:{$roleId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId) {
            $access = $this->modelForCurrentContext()::where('role_id', $roleId)->get();

            $moduleIds = collect();

            foreach ($access as $entry) {
                if ($entry->module_id) {
                    $moduleIds->push($entry->module_id);
                } elseif ($entry->sub_module_id) {
                    $subModule = SubModule::find($entry->sub_module_id);
                    if ($subModule) {
                        $moduleIds->push($subModule->module_id);
                    }
                } elseif ($entry->component_id) {
                    $component = Component::find($entry->component_id);
                    if ($component) {
                        $moduleIds->push($component->module_id);
                    }
                } elseif ($entry->action_id) {
                    $action = Action::find($entry->action_id);
                    $componentId = $action->module_component_id ?? $action->component_id ?? null;
                    if ($componentId) {
                        $component = Component::find($componentId);
                        if ($component) {
                            $moduleIds->push($component->module_id);
                        }
                    }
                }
            }

            return $moduleIds->unique()->values()->toArray();
        });
    }

    /**
     * Get all accessible sub-module IDs for a user.
     */
    public function getUserAccessibleSubModuleIds(mixed $user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = $this->getCacheKey("user_accessible_submodules:{$user->id}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $subModuleIds = collect();

            foreach ($user->roles as $role) {
                $access = $this->modelForCurrentContext()::where('role_id', $role->id)->get();

                foreach ($access as $entry) {
                    // Direct sub-module access
                    if ($entry->sub_module_id) {
                        $subModuleIds->push($entry->sub_module_id);
                    }
                    // Module-level access grants all sub-modules
                    elseif ($entry->module_id && ! $entry->sub_module_id) {
                        $moduleSubModules = SubModule::where('module_id', $entry->module_id)
                            ->where('is_active', true)
                            ->pluck('id');
                        $subModuleIds = $subModuleIds->merge($moduleSubModules);
                    }
                    // Component/action level access - get parent sub-module
                    elseif ($entry->component_id) {
                        $component = Component::find($entry->component_id);
                        if ($component) {
                            $subModuleIds->push($component->sub_module_id);
                        }
                    } elseif ($entry->action_id) {
                        $action = Action::find($entry->action_id);
                        $componentId = $action->module_component_id ?? $action->component_id ?? null;
                        if ($componentId) {
                            $component = Component::find($componentId);
                            if ($component) {
                                $subModuleIds->push($component->sub_module_id);
                            }
                        }
                    }
                }
            }

            return $subModuleIds->unique()->values()->toArray();
        });
    }

    /**
     * Sync role access from UI selections.
     */
    public function syncRoleAccess(mixed $role, array $accessData): void
    {
        $roleId = is_object($role) ? $role->id : $role;

        // Clear existing access for this role
        $this->modelForCurrentContext()::where('role_id', $roleId)->delete();

        // Add module-level access
        foreach ($accessData['modules'] ?? [] as $moduleId) {
            $this->modelForCurrentContext()::create([
                'role_id' => $roleId,
                'module_id' => $moduleId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add sub-module-level access
        foreach ($accessData['sub_modules'] ?? [] as $subModuleId) {
            $this->modelForCurrentContext()::create([
                'role_id' => $roleId,
                'sub_module_id' => $subModuleId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add component-level access
        foreach ($accessData['components'] ?? [] as $componentId) {
            $this->modelForCurrentContext()::create([
                'role_id' => $roleId,
                'component_id' => $componentId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add action-level access with scope
        foreach ($accessData['actions'] ?? [] as $actionData) {
            if (is_array($actionData)) {
                $this->modelForCurrentContext()::create([
                    'role_id' => $roleId,
                    'action_id' => $actionData['id'],
                    'access_scope' => $actionData['scope'] ?? RoleModuleAccess::SCOPE_ALL,
                ]);
            } else {
                $this->modelForCurrentContext()::create([
                    'role_id' => $roleId,
                    'action_id' => $actionData,
                    'access_scope' => RoleModuleAccess::SCOPE_ALL,
                ]);
            }
        }

        // Clear cache
        $this->clearRoleCache($role);
    }

    /**
     * Get the full access tree for a role.
     *
     * Explicit module-level grants take priority. When only sub_module-level
     * grants exist (no explicit module_id rows), parent module IDs are derived
     * from the sub_modules table so that `accessible_modules` is populated
     * correctly for navigation and access checks.
     */
    public function getRoleAccessTree(mixed $role): array
    {
        $roleId = is_object($role) ? $role->id : $role;
        $access = $this->modelForCurrentContext()::where('role_id', $roleId)->get();

        // Explicit module-level grants (module_id set, no sub_module_id).
        $explicitModuleIds = $access->whereNotNull('module_id')
            ->whereNull('sub_module_id')
            ->pluck('module_id')
            ->toArray();

        $subModuleIds = $access->whereNotNull('sub_module_id')
            ->whereNull('component_id')
            ->pluck('sub_module_id')
            ->toArray();

        // When there are no explicit module grants but sub_module grants exist,
        // derive the parent module IDs so navigation and access checks work.
        if (empty($explicitModuleIds) && ! empty($subModuleIds)) {
            try {
                $derivedModuleIds = SubModule::whereIn('id', $subModuleIds)
                    ->whereNotNull('module_id')
                    ->pluck('module_id')
                    ->unique()
                    ->values()
                    ->toArray();
            } catch (\Throwable) {
                $derivedModuleIds = [];
            }
        } else {
            $derivedModuleIds = [];
        }

        $moduleIds = array_values(array_unique(array_merge($explicitModuleIds, $derivedModuleIds)));

        return [
            'modules' => $moduleIds,
            'sub_modules' => $subModuleIds,
            'components' => $access->whereNotNull('component_id')
                ->whereNull('action_id')
                ->pluck('component_id')
                ->toArray(),
            'actions' => $access->whereNotNull('action_id')
                ->map(fn ($a) => ['id' => $a->action_id, 'scope' => $a->access_scope])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Clear cached access data for a role.
     */
    public function clearRoleCache(mixed $role): void
    {
        $roleId = is_object($role) ? $role->id : $role;

        $this->cache()->forget($this->getCacheKey("role_accessible_modules:{$roleId}"));

        // Note: Individual module/submodule/component/action caches
        // will naturally expire. For immediate invalidation, use tagged caching.
    }

    /**
     * Clear cached access data for a user.
     */
    public function clearUserCache(mixed $user): void
    {
        if ($user) {
            $userId = is_object($user) ? $user->id : $user;
            $this->cache()->forget($this->getCacheKey("user_accessible_submodules:{$userId}"));
        }
    }

    /**
     * Check if user is a super admin.
     */
    protected function isSuperAdmin(mixed $user): bool
    {
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        $superAdminRoles = config('hrmac.super_admin_roles', [
            'Super Administrator',
            'super-admin',
            'tenant_super_administrator',
        ]);

        return $user->hasRole($superAdminRoles);
    }

    /**
     * Get all users who have access to a specific sub-module.
     * This is used for sending notifications to users with module access.
     *
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The sub-module code (e.g., 'leaves')
     * @param  string|null  $actionCode  Optional action code to check specific action access (e.g., 'approve')
     * @return \Illuminate\Support\Collection Collection of User models
     */
    public function getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null): \Illuminate\Support\Collection
    {
        // Get the module
        $module = Module::where('code', $moduleCode)->where('is_active', true)->first();
        if (! $module) {
            return collect();
        }

        // Get the sub-module
        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->where('is_active', true)
            ->first();
        if (! $subModule) {
            return collect();
        }

        // Build query to find role IDs with access
        $roleIdsQuery = $this->modelForCurrentContext()::query()
            ->where(function ($query) use ($module, $subModule) {
                // Direct module access (grants access to all sub-modules)
                $query->where(function ($q) use ($module) {
                    $q->where('module_id', $module->id)
                        ->whereNull('sub_module_id')
                        ->whereNull('component_id')
                        ->whereNull('action_id');
                })
                // Direct sub-module access
                    ->orWhere(function ($q) use ($subModule) {
                        $q->where('sub_module_id', $subModule->id)
                            ->whereNull('component_id')
                            ->whereNull('action_id');
                    });
            });

        // If action code is specified, also include component/action level access
        if ($actionCode) {
            // Get components in this submodule that have the specified action
            $componentIds = Component::where('sub_module_id', $subModule->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($componentIds->isNotEmpty()) {
                $roleIdsQuery->orWhere(function ($query) use ($componentIds) {
                    $query->whereIn('component_id', $componentIds)
                        ->whereNull('action_id');
                });

                // Also check specific action access
                $actionIds = \Aero\HRMAC\Models\Action::whereIn('component_id', $componentIds)
                    ->where('code', $actionCode)
                    ->where('is_active', true)
                    ->pluck('id');

                if ($actionIds->isNotEmpty()) {
                    $roleIdsQuery->orWhereIn('action_id', $actionIds);
                }
            }
        }

        $roleIds = $roleIdsQuery->pluck('role_id')->unique();

        if ($roleIds->isEmpty()) {
            return collect();
        }

        // Get the User model class from config
        $userModel = config('hrmac.models.user', \Aero\Core\Models\User::class);

        // Find all active users with these roles
        return $userModel::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get all users who have access to perform a specific action.
     *
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The sub-module code (e.g., 'leaves')
     * @param  string  $componentCode  The component code (e.g., 'leave-requests')
     * @param  string  $actionCode  The action code (e.g., 'approve')
     * @return \Illuminate\Support\Collection Collection of User models
     */
    public function getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): \Illuminate\Support\Collection
    {
        // Get the module hierarchy
        $module = Module::where('code', $moduleCode)->where('is_active', true)->first();
        if (! $module) {
            return collect();
        }

        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->where('is_active', true)
            ->first();
        if (! $subModule) {
            return collect();
        }

        $component = Component::where('sub_module_id', $subModule->id)
            ->where('code', $componentCode)
            ->where('is_active', true)
            ->first();
        if (! $component) {
            return collect();
        }

        $action = \Aero\HRMAC\Models\Action::where('component_id', $component->id)
            ->where('code', $actionCode)
            ->where('is_active', true)
            ->first();

        // Build query to find role IDs with cascading access
        $roleIds = $this->modelForCurrentContext()::query()
            ->where(function ($query) use ($module, $subModule, $component, $action) {
                // Module level access (full access)
                $query->where(function ($q) use ($module) {
                    $q->where('module_id', $module->id)
                        ->whereNull('sub_module_id')
                        ->whereNull('component_id')
                        ->whereNull('action_id');
                })
                // Sub-module level access
                    ->orWhere(function ($q) use ($subModule) {
                        $q->where('sub_module_id', $subModule->id)
                            ->whereNull('component_id')
                            ->whereNull('action_id');
                    })
                // Component level access
                    ->orWhere(function ($q) use ($component) {
                        $q->where('component_id', $component->id)
                            ->whereNull('action_id');
                    });

                // Specific action access (if action exists)
                if ($action) {
                    $query->orWhere('action_id', $action->id);
                }
            })
            ->pluck('role_id')
            ->unique();

        if ($roleIds->isEmpty()) {
            return collect();
        }

        // Get the User model class from config
        $userModel = config('hrmac.models.user', \Aero\Core\Models\User::class);

        // Find all active users with these roles
        return $userModel::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get cache key with optional tenant prefix.
     */
    protected function getCacheKey(string $key): string
    {
        // If in tenant context, prefix with tenant ID
        if (function_exists('tenant') && tenant()) {
            return 'tenant:'.tenant('id').':hrmac:'.$key;
        }

        return 'hrmac:'.$key;
    }

    /**
     * Get cache store.
     */
    protected function cache()
    {
        return Cache::store();
    }

    /**
     * Return the correct RoleModuleAccess model class for the current execution context.
     *
     * Platform/landlord context → LandlordRoleModuleAccess (pinned to central connection)
     * Tenant/standalone context → RoleModuleAccess (uses current connection)
     */
    private function modelForCurrentContext(): string
    {
        try {
            $context = app(\Aero\Core\ValueObjects\RequestContext::class);
            if ($context->isPlatform()) {
                return \Aero\HRMAC\Models\LandlordRoleModuleAccess::class;
            }
        } catch (\Throwable) {
            // No RequestContext bound (CLI, queue, test) — use default
        }

        return RoleModuleAccess::class;
    }
}
