<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Core\Models\User;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Resources\UserCollection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared User Controller
 *
 * Handles user management for both:
 * - Platform Admin context (User on central database)
 * - Tenant context (User on tenant database)
 *
 * The controller determines which model to use based on the context.
 */
class UserController extends Controller
{
    /**
     * Determine the user model based on context
     */
    protected function getUserModel(string $context = 'tenant')
    {
        return $context === 'admin' ? User::class : User::class;
    }

    /**
     * Determine the guard based on context
     */
    protected function getGuard(string $context = 'tenant'): string
    {
        return $context === 'admin' ? 'landlord' : 'web';
    }

    /**
     * Display the platform admin users list (admin context)
     */
    public function adminIndex(): Response
    {
        return Inertia::render('Shared/UsersList', [
            'title' => 'Platform Administrators',
            'roles' => Role::where('guard_name', 'landlord')->get(),
            'departments' => [], // Platform admins don't have departments
            'designations' => [],
            'context' => 'admin',
        ]);
    }

    /**
     * Display the tenant users list (tenant context)
     */
    public function tenantIndex(): Response
    {
        return Inertia::render('Shared/UsersList', [
            'title' => 'User Management',
            'roles' => Role::where('guard_name', 'web')->get(),
            'departments' => Department::all(),
            'designations' => Designation::with('department')->orderBy('hierarchy_level', 'asc')->get(),
            'context' => 'tenant',
        ]);
    }

    /**
     * Paginate users with filters
     */
    public function paginate(Request $request, string $context = 'tenant'): JsonResponse
    {
        try {
            $perPage = $request->input('perPage', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $role = $request->input('role');
            $status = $request->input('status');
            $department = $request->input('department');

            $modelClass = $this->getUserModel($context);
            $guardName = $this->getGuard($context);

            // Base query with relations
            if ($context === 'admin') {
                $query = $modelClass::with(['roles']);
            } else {
                $query = $modelClass::withTrashed()
                    ->with(['department', 'designation', 'roles', 'currentDevice', 'reportsTo']);
            }

            // Search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    // Phone search for tenant users
                    if (in_array('phone', (new ($q->getModel()))->getFillable())) {
                        $q->orWhere('phone', 'like', "%{$search}%");
                    }
                });
            }

            // Role filter
            if ($role && $role !== 'all') {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role)->where('guard_name', $guardName));
            }

            // Status filter
            if ($status && $status !== 'all') {
                $query->where('active', $status === 'active' ? 1 : 0);
            }

            // Department filter (tenant only) - Query through employee relationship
            if ($context === 'tenant' && $department && $department !== 'all') {
                $query->whereHas('employee', fn ($q) => $q->where('department_id', $department));
            }

            // Sort active users first
            $query->orderByDesc('active')->orderBy('name');

            // Paginate
            $users = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'users' => new UserCollection($users),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while retrieving user data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function stats(Request $request, string $context = 'tenant'): JsonResponse
    {
        try {
            $modelClass = $this->getUserModel($context);
            $guardName = $this->getGuard($context);

            // Basic user counts
            if ($context === 'admin') {
                $totalUsers = $modelClass::count();
                $activeUsers = $modelClass::where('active', 1)->count();
                $inactiveUsers = $modelClass::where('active', 0)->count();
                $deletedUsers = 0;
            } else {
                $totalUsers = $modelClass::withTrashed()->count();
                $activeUsers = $modelClass::withTrashed()->where('active', 1)->count();
                $inactiveUsers = $modelClass::withTrashed()->where('active', 0)->count();
                $deletedUsers = $modelClass::onlyTrashed()->count();
            }

            // Role analytics
            $roleCount = Role::where('guard_name', $guardName)->count();
            $rolesWithUsers = Role::where('guard_name', $guardName)
                ->withCount('users')
                ->get()
                ->map(function ($role) use ($totalUsers) {
                    return [
                        'name' => $role->name,
                        'count' => $role->users_count,
                        'percentage' => $totalUsers > 0 ? round(($role->users_count / $totalUsers) * 100, 1) : 0,
                    ];
                });

            // Department stats (tenant only)
            $departmentStats = [];
            $deptCount = 0;
            if ($context === 'tenant') {
                $departmentStats = Department::withCount('users')
                    ->get()
                    ->map(function ($dept) use ($totalUsers) {
                        return [
                            'name' => $dept->name,
                            'count' => $dept->users_count,
                            'percentage' => $totalUsers > 0 ? round(($dept->users_count / $totalUsers) * 100, 1) : 0,
                        ];
                    });
                $deptCount = Department::count();
            }

            // Status distribution
            $statusDistribution = [
                ['name' => 'Active', 'count' => $activeUsers, 'percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0],
                ['name' => 'Inactive', 'count' => $inactiveUsers, 'percentage' => $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100, 1) : 0],
            ];

            if ($context === 'tenant') {
                $statusDistribution[] = ['name' => 'Deleted', 'count' => $deletedUsers, 'percentage' => $totalUsers > 0 ? round(($deletedUsers / $totalUsers) * 100, 1) : 0];
            }

            // Recent activity metrics
            $recentlyActive = $modelClass::where('last_login_at', '>=', now()->subDays(7))->count();
            $newUsers30Days = $modelClass::where('created_at', '>=', now()->subDays(30))->count();
            $newUsers90Days = $modelClass::where('created_at', '>=', now()->subDays(90))->count();
            $newUsersYear = $modelClass::where('created_at', '>=', now()->subYear())->count();

            // System health calculations
            $activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;
            $roleCoverage = $totalUsers > 0
                ? round(($modelClass::has('roles')->count() / $totalUsers) * 100, 1)
                : 0;

            // Calculate system health score
            $healthScore = round(($activePercentage * 0.5) + ($roleCoverage * 0.5), 1);

            return response()->json([
                'stats' => [
                    'overview' => [
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers,
                        'inactive_users' => $inactiveUsers,
                        'deleted_users' => $deletedUsers,
                        'total_roles' => $roleCount,
                        'total_departments' => $deptCount,
                    ],
                    'distribution' => [
                        'by_role' => $rolesWithUsers,
                        'by_department' => $departmentStats,
                        'by_status' => $statusDistribution,
                    ],
                    'activity' => [
                        'recent_registrations' => [
                            'new_users_30_days' => $newUsers30Days,
                            'new_users_90_days' => $newUsers90Days,
                            'new_users_year' => $newUsersYear,
                            'recently_active' => $recentlyActive,
                        ],
                        'user_growth_rate' => 0,
                        'current_month_registrations' => $newUsers30Days,
                    ],
                    'security' => [
                        'access_metrics' => [
                            'users_with_roles' => $modelClass::has('roles')->count(),
                            'users_without_roles' => $modelClass::doesntHave('roles')->count(),
                            'admin_users' => $modelClass::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Administrator', 'Admin', 'Platform Admin']))->count(),
                            'regular_users' => $totalUsers - $modelClass::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Administrator', 'Admin', 'Platform Admin']))->count(),
                        ],
                        'role_distribution' => $rolesWithUsers,
                    ],
                    'health' => [
                        'status_ratio' => [
                            'active_percentage' => $activePercentage,
                            'inactive_percentage' => $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100, 1) : 0,
                            'deleted_percentage' => $totalUsers > 0 ? round(($deletedUsers / $totalUsers) * 100, 1) : 0,
                        ],
                        'system_metrics' => [
                            'user_activation_rate' => $activePercentage,
                            'role_coverage' => $roleCoverage,
                            'department_coverage' => $context === 'tenant'
                                ? ($totalUsers > 0 ? round(($modelClass::whereHas('employee', fn ($q) => $q->whereNotNull('department_id'))->count() / $totalUsers) * 100, 1) : 0)
                                : 0,
                        ],
                    ],
                    'quick_metrics' => [
                        'total_users' => $totalUsers,
                        'active_ratio' => $activePercentage,
                        'role_diversity' => $roleCount,
                        'department_diversity' => $deptCount,
                        'recent_activity' => $recentlyActive,
                        'system_health_score' => $healthScore,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while retrieving user statistics.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new user
     */
    public function store(Request $request, string $context = 'tenant')
    {
        $modelClass = $this->getUserModel($context);
        $guardName = $this->getGuard($context);

        // Determine the connection and table for unique validation
        $connection = $context === 'admin' ? 'central' : config('database.default');
        $table = (new $modelClass)->getTable();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:{$connection}.{$table},email",
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $validated['password'] = Hash::make($validated['password']);
            $validated['active'] = $validated['active'] ?? true;

            $user = $modelClass::create($validated);

            // Assign roles
            if (! empty($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->fresh(['roles']),
            ], 201);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'A user with this email already exists.',
                'message' => 'The email address is already in use.',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'error' => 'Failed to create user.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a user
     */
    public function update(Request $request, $id, string $context = 'tenant')
    {
        $modelClass = $this->getUserModel($context);
        $user = $modelClass::findOrFail($id);

        // Determine the connection and table for unique validation
        $connection = $context === 'admin' ? 'central' : config('database.default');
        $table = $user->getTable();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:{$connection}.{$table},email,{$id}",
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            if (! empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            // Update roles
            if (isset($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $user->fresh(['roles']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'error' => 'Failed to update user.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(Request $request, $id, string $context = 'tenant')
    {
        $modelClass = $this->getUserModel($context);

        if ($context === 'tenant') {
            $user = $modelClass::withTrashed()->findOrFail($id);
        } else {
            $user = $modelClass::findOrFail($id);
        }

        $user->active = $request->input('active', ! $user->active);

        // Handle soft delete for tenant users
        if ($context === 'tenant') {
            if ($user->active) {
                $user->restore();
            } else {
                $user->delete();
            }
        }

        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'active' => $user->active,
            'user' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Delete a user
     */
    public function destroy($id, string $context = 'tenant')
    {
        $modelClass = $this->getUserModel($context);
        $user = $modelClass::findOrFail($id);

        try {
            if ($context === 'tenant') {
                $user->active = false;
                $user->save();
                $user->delete(); // Soft delete
            } else {
                // For admin users, we might want to be more careful
                // Just deactivate instead of delete
                $user->active = false;
                $user->save();
            }

            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'error' => 'Failed to delete user.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user roles
     */
    public function updateRoles(Request $request, $id, string $context = 'tenant')
    {
        $modelClass = $this->getUserModel($context);
        $user = $modelClass::findOrFail($id);

        $request->validate([
            'roles' => 'required|array',
        ]);

        try {
            $user->syncRoles($request->input('roles'));

            return response()->json([
                'message' => 'Roles updated successfully',
                'user' => $user->fresh(['roles']),
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'error' => 'Failed to update roles.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
