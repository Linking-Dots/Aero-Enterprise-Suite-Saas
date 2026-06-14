<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Core\Models\User;
use Aero\Core\Support\TenantCache;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * Shared Admin Role Controller
 *
 * Context-aware role management controller that works for both:
 * - Platform Admin (landlord guard) - manages platform-level roles
 * - Tenant Admin (web guard) - manages tenant-level roles
 *
 * Simplified version using only roles (no permissions table)
 */
class RoleController extends Controller
{
    /**
     * Determine if current context is platform admin (landlord guard)
     */
    protected function isPlatformContext(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /**
     * Get the current authenticated user based on context
     */
    protected function getCurrentUser()
    {
        if ($this->isPlatformContext()) {
            return Auth::guard('landlord')->user();
        }

        return Auth::guard('web')->user();
    }

    /**
     * Determine the Inertia page path based on context
     */
    protected function getViewPath(): string
    {
        return 'RoleManagement';
    }

    /**
     * Check if user is a super administrator (context-aware)
     */
    protected function isSuperAdmin(): bool
    {
        $user = $this->getCurrentUser();

        if ($this->isPlatformContext()) {
            return $user instanceof User && $user->isSuperAdmin();
        }

        return $user?->hasRole('Super Administrator') ?? false;
    }

    /**
     * Display the main role management interface
     */
    public function index()
    {
        try {
            $user = $this->getCurrentUser();
            $isSuperAdmin = $this->isSuperAdmin();
            $isPlatform = $this->isPlatformContext();

            // Get all roles (Super Admin can see all, others see limited)
            $roles = Role::query()
                ->when(! $isSuperAdmin, function ($query) {
                    return $query->whereNotIn('name', ['Super Administrator']);
                })
                ->orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description ?? '',
                        'guard_name' => $role->guard_name,
                        'scope' => $role->scope ?? 'platform',
                        'is_protected' => $role->is_protected ?? false,
                        'users_count' => $role->users()->count(),
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ];
                });

            // Get users with their roles
            $users = collect([]);
            if ($isPlatform) {
                $users = User::with('roles')
                    ->select(['id', 'name', 'email'])
                    ->orderBy('name')
                    ->get()
                    ->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'roles' => $u->roles->map(fn ($role) => [
                                'id' => $role->id,
                                'name' => $role->name,
                            ]),
                        ];
                    });
            } else {
                $users = User::with('roles')
                    ->select(['id', 'name', 'email'])
                    ->orderBy('name')
                    ->get()
                    ->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'roles' => $u->roles->map(fn ($role) => [
                                'id' => $role->id,
                                'name' => $role->name,
                            ]),
                        ];
                    });
            }

            return Inertia::render($this->getViewPath(), [
                'title' => $isPlatform ? 'Platform Role Management' : 'Enterprise Role Management',
                'roles' => $roles->toArray(),
                'permissions' => [], // Empty - not using permissions
                'permissionsGrouped' => [], // Empty - not using permissions
                'role_has_permissions' => [], // Empty - not using permissions
                'enterprise_modules' => [], // Empty - not using permissions
                'can_manage_super_admin' => $isSuperAdmin,
                'is_platform_context' => $isPlatform,
                'users' => $users,
                'server_info' => [
                    'environment' => app()->environment(),
                    'timestamp' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load role management interface: '.$e->getMessage(), [
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render($this->getViewPath(), [
                'title' => 'Role Management - Error',
                'roles' => [],
                'permissions' => [],
                'permissionsGrouped' => [],
                'role_has_permissions' => [],
                'enterprise_modules' => [],
                'can_manage_super_admin' => false,
                'is_platform_context' => $this->isPlatformContext(),
                'users' => [],
                'error' => [
                    'message' => 'Failed to load role management data',
                    'details' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Alias for index - backward compatibility
     */
    public function getRolesAndPermissions()
    {
        return $this->index();
    }

    /**
     * Store a new role
     */
    public function storeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            if (! $this->isSuperAdmin()) {
                return response()->json([
                    'error' => 'Insufficient permissions to create roles',
                ], 403);
            }

            $guardName = $this->isPlatformContext() ? 'landlord' : 'web';
            $scope = $this->isPlatformContext() ? 'platform' : 'tenant';

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $guardName,
            ]);

            // Update description and scope
            DB::table('roles')->where('id', $role->id)->update([
                'description' => $request->description ?? null,
                'scope' => $scope,
                'updated_at' => now(),
            ]);

            Log::info('Role created', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Role created successfully',
                'role' => $role->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role creation failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to create role',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing role
     */
    public function updateRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $role = Role::find($id);

            if (! $role) {
                return response()->json(['error' => 'Role not found'], 404);
            }

            if ($this->isProtectedRole($role)) {
                return response()->json([
                    'error' => 'Super Administrator role cannot be edited.',
                ], 403);
            }

            $role->name = $request->name;
            $role->save();

            if ($request->has('description')) {
                DB::table('roles')->where('id', $role->id)->update([
                    'description' => $request->description,
                    'updated_at' => now(),
                ]);
            }

            Log::info('Role updated', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Role updated successfully',
                'role' => $role->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role update failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to update role',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($id)
    {
        DB::beginTransaction();

        try {
            $role = Role::find($id);

            if (! $role) {
                return response()->json(['error' => 'Role not found'], 404);
            }

            if ($this->isProtectedRole($role)) {
                return response()->json([
                    'error' => 'Super Administrator role cannot be deleted.',
                ], 403);
            }

            $usersCount = $role->users()->count();
            if ($usersCount > 0) {
                return response()->json([
                    'error' => "Cannot delete role. It is assigned to {$usersCount} user(s).",
                ], 409);
            }

            Log::warning('Role deleted', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'deleted_by' => Auth::id(),
            ]);

            $role->delete();

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Role deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role deletion failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to delete role',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign roles to a user
     */
    public function assignRolesToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $isPlatform = $this->isPlatformContext();

            if ($isPlatform) {
                $user = User::findOrFail($request->user_id);
            } else {
                $user = User::findOrFail($request->user_id);
            }

            // Sync roles
            $user->syncRoles($request->roles);

            Log::info('Roles assigned to user', [
                'user_id' => $user->id,
                'roles' => $request->roles,
                'assigned_by' => Auth::id(),
            ]);

            $this->clearCache();

            return response()->json([
                'message' => 'Roles assigned successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Role assignment failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to assign roles',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if role is protected (cannot be modified)
     */
    protected function isProtectedRole(Role $role): bool
    {
        return $role->name === 'Super Administrator' || ($role->is_protected ?? false);
    }

    /**
     * Clear role-related caches
     */
    protected function clearCache(): void
    {
        try {
            TenantCache::forget('roles_list');
            TenantCache::forget('roles_with_users');
            // HRMAC role-access cache self-invalidates (versioned); no Spatie cache.
        } catch (\Exception $e) {
            Log::warning('Cache clear failed: '.$e->getMessage());
        }
    }

    /**
     * Export roles data
     */
    public function exportRoles()
    {
        try {
            $roles = Role::all()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description ?? '',
                    'guard_name' => $role->guard_name,
                    'users_count' => $role->users()->count(),
                ];
            });

            return response()->json([
                'roles' => $roles,
                'exported_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh roles data for frontend
     */
    public function refreshData()
    {
        $this->clearCache();

        return response()->json([
            'message' => 'Data refreshed successfully',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
