<?php

declare(strict_types=1);

namespace Aero\HRMAC\Services;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\HrmacAuditLog;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\SubModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Role Module Access Service
 *
 * Unified service for role-based module access control.
 * Context-free engine: uses the default DB connection; the consuming host/package
 * decides the execution context (connection, guard) and the data.
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
        $cacheKey = $this->versionedRoleKey($roleId, "module:{$moduleId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $moduleId) {
            return $this->accessModel()::where('role_id', $roleId)
                ->where('status', RoleModuleAccess::STATUS_ACTIVE) // D17: suspended rows must not grant access
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
        $cacheKey = $this->versionedRoleKey($roleId, "sub_module:{$subModuleId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $subModuleId) {
            // Check direct sub-module access (D17: suspended rows excluded)
            if ($this->accessModel()::where('role_id', $roleId)
                ->where('status', RoleModuleAccess::STATUS_ACTIVE)
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
        $cacheKey = $this->versionedRoleKey($roleId, "component:{$componentId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $componentId) {
            // Check direct component access (D17: suspended rows excluded)
            if ($this->accessModel()::where('role_id', $roleId)
                ->where('status', RoleModuleAccess::STATUS_ACTIVE)
                ->where('component_id', $componentId)
                ->whereNull('action_id')
                ->exists()) {
                return true;
            }

            // Check parent sub-module access (inheritance)
            $component = ModuleComponent::find($componentId);
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
        $cacheKey = $this->versionedRoleKey($roleId, "action:{$actionId}");

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId, $actionId) {
            // Check direct action access (D17: suspended rows excluded)
            if ($this->accessModel()::where('role_id', $roleId)
                ->where('status', RoleModuleAccess::STATUS_ACTIVE)
                ->where('action_id', $actionId)
                ->exists()) {
                return true;
            }

            // Check parent component access (inheritance)
            $action = ModuleComponentAction::find($actionId);
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

        // Nested cascade: a grant on the sub-module OR any ancestor sub-module
        // (via parent_id) grants access to this (descendant) sub-module.
        $subModuleIds = array_merge([$subModule->id], $subModule->ancestorIds());

        foreach ($user->roles as $role) {
            foreach ($subModuleIds as $smId) {
                if ($this->canAccessSubModule($role, $smId)) {
                    return true;
                }
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
        $action = ModuleComponentAction::where('code', $actionCode)
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
     *
     * Access-check path — only 'active' rows grant navigation access (D17).
     */
    public function getAccessibleModuleIds(mixed $role): array
    {
        $roleId = is_object($role) ? $role->id : $role;
        $cacheKey = $this->versionedRoleKey($roleId, 'accessible_modules');

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($roleId) {
            // D17: suspended rows must not appear in navigation access
            $access = $this->accessModel()::where('role_id', $roleId)
                ->where('status', RoleModuleAccess::STATUS_ACTIVE)
                ->get();

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
                    $component = ModuleComponent::find($entry->component_id);
                    if ($component) {
                        $moduleIds->push($component->module_id);
                    }
                } elseif ($entry->action_id) {
                    $action = ModuleComponentAction::find($entry->action_id);
                    $componentId = $action->module_component_id ?? $action->component_id ?? null;
                    if ($componentId) {
                        $component = ModuleComponent::find($componentId);
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
                // D17: suspended rows must not grant navigation access
                $access = $this->accessModel()::where('role_id', $role->id)
                    ->where('status', RoleModuleAccess::STATUS_ACTIVE)
                    ->get();

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
                        $component = ModuleComponent::find($entry->component_id);
                        if ($component) {
                            $subModuleIds->push($component->sub_module_id);
                        }
                    } elseif ($entry->action_id) {
                        $action = ModuleComponentAction::find($entry->action_id);
                        $componentId = $action->module_component_id ?? $action->component_id ?? null;
                        if ($componentId) {
                            $component = ModuleComponent::find($componentId);
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

        // Capture before state for audit trail
        $beforeState = $this->accessModel()::where('role_id', $roleId)
            ->get(['module_id', 'sub_module_id', 'component_id', 'action_id', 'access_scope'])
            ->toArray();

        // Clear existing access for this role
        $this->accessModel()::where('role_id', $roleId)->delete();

        // Add module-level access
        foreach ($accessData['modules'] ?? [] as $moduleId) {
            $this->accessModel()::create([
                'role_id' => $roleId,
                'module_id' => $moduleId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add sub-module-level access
        foreach ($accessData['sub_modules'] ?? [] as $subModuleId) {
            $this->accessModel()::create([
                'role_id' => $roleId,
                'sub_module_id' => $subModuleId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add component-level access
        foreach ($accessData['components'] ?? [] as $componentId) {
            $this->accessModel()::create([
                'role_id' => $roleId,
                'component_id' => $componentId,
                'access_scope' => RoleModuleAccess::SCOPE_ALL,
            ]);
        }

        // Add action-level access with scope
        foreach ($accessData['actions'] ?? [] as $actionData) {
            if (is_array($actionData)) {
                $this->accessModel()::create([
                    'role_id' => $roleId,
                    'action_id' => $actionData['id'],
                    'access_scope' => $actionData['scope'] ?? RoleModuleAccess::SCOPE_ALL,
                ]);
            } else {
                $this->accessModel()::create([
                    'role_id' => $roleId,
                    'action_id' => $actionData,
                    'access_scope' => RoleModuleAccess::SCOPE_ALL,
                ]);
            }
        }

        // Clear cache
        $this->clearRoleCache($role);

        // Write audit log — failure must never break the sync
        try {
            HrmacAuditLog::create([
                'actor_user_id' => auth()->id(),
                'role_id' => $roleId,
                'action' => 'sync',
                'before_state' => $beforeState,
                'after_state' => $accessData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Audit failure must not break the access sync
        }
    }

    /**
     * Get the full access tree for a role.
     *
     * Explicit module-level grants take priority. When only sub_module-level
     * grants exist (no explicit module_id rows), parent module IDs are derived
     * from the sub_modules table so that `accessible_modules` is populated
     * correctly for navigation and access checks.
     *
     * INVENTORY method — suspended rows are intentionally included so that
     * admin UIs (RBAC management pages) can display the suspended state and
     * allow admins to understand what will be restored on re-subscription.
     * Do NOT add a status filter here.
     */
    public function getRoleAccessTree(mixed $role): array
    {
        $roleId = is_object($role) ? $role->id : $role;
        // D17: No status filter — inventory query, admins see ALL grants including suspended.
        $access = $this->accessModel()::where('role_id', $roleId)->get();

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
            // 'modules' is the merged set (explicit + derived-from-sub-grants) used by
            // navigation/menus. The editor must use 'explicit_modules' so a no-op save
            // doesn't convert a derived parent into an explicit full-module grant.
            'modules' => $moduleIds,
            'explicit_modules' => array_values($explicitModuleIds),
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

        // Axis C C6/C8 — bump the role's cache version. This invalidates EVERY
        // versioned per-role access cache (module/sub-module/component/action/
        // accessible-modules) immediately, instead of waiting out the TTL. Closes
        // the window where a revoked/suspended grant kept authorizing for up to
        // CACHE_TTL.
        $this->bumpRoleCacheVersion($roleId);
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

        $config = config('hrmac.super_admin_roles', [
            'Super Administrator',
            'super-admin',
            'tenant_super_administrator',
        ]);

        // The config may be a flat list OR guard-scoped (['web' => [...],
        // 'landlord' => [...]]). Flatten to a flat list of role names so
        // hasRole()'s whereIn() never receives nested arrays.
        $superAdminRoles = [];
        array_walk_recursive($config, function ($role) use (&$superAdminRoles) {
            if (is_string($role)) {
                $superAdminRoles[] = $role;
            }
        });

        return $user->hasRole($superAdminRoles);
    }

    /**
     * Get all users who have access to a specific sub-module.
     * This is used for sending notifications to users with module access.
     *
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The sub-module code (e.g., 'leaves')
     * @param  string|null  $actionCode  Optional action code to check specific action access (e.g., 'approve')
     * @return Collection Collection of User models
     */
    public function getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null): Collection
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
        // D17: suspended rows must not be included in notification dispatch
        $roleIdsQuery = $this->accessModel()::query()
            ->where('status', RoleModuleAccess::STATUS_ACTIVE)
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
            $componentIds = ModuleComponent::where('sub_module_id', $subModule->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($componentIds->isNotEmpty()) {
                $roleIdsQuery->orWhere(function ($query) use ($componentIds) {
                    $query->whereIn('component_id', $componentIds)
                        ->whereNull('action_id');
                });

                // Also check specific action access
                $actionIds = ModuleComponentAction::whereIn('component_id', $componentIds)
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

        // Resolve the host's user model (HRMAC names no concrete User class).
        $userModel = config('hrmac.models.user') ?: config('auth.providers.users.model');

        // Find all active users with these roles
        return $userModel::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
            ->whereNull('deleted_at') // active = not soft-deleted
            ->get();
    }

    /**
     * Get all users who have access to perform a specific action.
     *
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string  $subModuleCode  The sub-module code (e.g., 'leaves')
     * @param  string  $componentCode  The component code (e.g., 'leave-requests')
     * @param  string  $actionCode  The action code (e.g., 'approve')
     * @return Collection Collection of User models
     */
    public function getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): Collection
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

        $component = ModuleComponent::where('sub_module_id', $subModule->id)
            ->where('code', $componentCode)
            ->where('is_active', true)
            ->first();
        if (! $component) {
            return collect();
        }

        $action = ModuleComponentAction::where('component_id', $component->id)
            ->where('code', $actionCode)
            ->where('is_active', true)
            ->first();

        // Build query to find role IDs with cascading access
        // D17: suspended rows must not be included in notification dispatch
        $roleIds = $this->accessModel()::query()
            ->where('status', RoleModuleAccess::STATUS_ACTIVE)
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

        // Resolve the host's user model (HRMAC names no concrete User class).
        $userModel = config('hrmac.models.user') ?: config('auth.providers.users.model');

        // Find all active users with these roles
        return $userModel::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
            ->whereNull('deleted_at') // active = not soft-deleted
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
     * Per-role cache version (Axis C C6/C8 — complete invalidation).
     *
     * All per-role access caches embed this version in their key. Bumping it
     * (clearRoleCache) instantly invalidates EVERY cached access result for the
     * role — closing the gap where individual module/sub-module/component/action
     * caches previously "naturally expired" only after TTL, leaving a revoked
     * grant authorizing for up to CACHE_TTL. A version miss simply re-resolves
     * from the DB (the correct answer), so this is safe.
     */
    protected function roleCacheVersion(mixed $roleId): int
    {
        return (int) $this->cache()->get($this->getCacheKey("role_cache_version:{$roleId}"), 1);
    }

    /**
     * Build a per-role cache key carrying the role's current cache version.
     */
    protected function versionedRoleKey(mixed $roleId, string $suffix): string
    {
        return $this->getCacheKey("role:{$roleId}:v{$this->roleCacheVersion($roleId)}:{$suffix}");
    }

    /**
     * Bump a role's cache version, invalidating all of its versioned access caches.
     */
    protected function bumpRoleCacheVersion(mixed $roleId): void
    {
        $key = $this->getCacheKey("role_cache_version:{$roleId}");
        $this->cache()->forever($key, $this->roleCacheVersion($roleId) + 1);
    }

    /**
     * The RoleModuleAccess model class — configurable by the consuming package
     * (config('hrmac.models.role_module_access')), defaulting to the bundled model.
     *
     * HRMAC is context-free: this model uses the DEFAULT database connection, so the
     * host/consumer's runtime context (stancl tenancy for tenant requests, central for
     * platform requests, single DB for standalone) determines which data is read/written.
     * HRMAC itself never detects or names a connection.
     */
    private function accessModel(): string
    {
        return config('hrmac.models.role_module_access', RoleModuleAccess::class);
    }
}
