<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Models\TenantInvitation;
use Aero\Auth\Models\User;
use Aero\Core\Notifications\InviteTeamMember;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Http\Requests\SendTeamInvitationRequest;
use Aero\Platform\Http\Requests\StoreUserRequest;
use Aero\Platform\Http\Requests\UpdateUserRequest;
use Aero\Platform\Http\Requests\UpdateUserRoleRequest;
use Aero\Platform\Http\Requests\UpdateUserStatusRequest;
use Aero\Platform\Http\Resources\UserCollection;
use Aero\Platform\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UserController - Manages User authentication and access control
 *
 * Architecture:
 * - User model: Authentication, basic profile (name, email, phone), roles, permissions
 * - Employee model: Employment data (department, designation, salary, dates)
 *
 * This controller focuses on:
 * - User CRUD operations (account management)
 * - Role and permission management
 * - Team invitations
 * - Account status (active/inactive)
 *
 * Employee-specific operations are handled by EmployeeController.
 */
class UserController extends Controller
{
    /**
     * Display users management page
     */
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Shared/UsersList', [
            'title' => 'User Management',
            'context' => 'tenant',
            'roles' => Role::all(),
            'departments' => Department::all(),
            'designations' => Designation::with('department')->orderBy('hierarchy_level', 'asc')->get(),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(StoreUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Remove profile_image from validated data as it's handled by Media Library
            unset($validated['profile_image']);

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Convert single_device_login_enabled to boolean
            if (isset($validated['single_device_login_enabled'])) {
                $validated['single_device_login_enabled'] = filter_var($validated['single_device_login_enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            // Create user
            $user = User::create($validated);

            // Assign roles if provided
            if ($roles = $request->input('roles')) {
                $user->syncRoles($roles);
            } else {
                // Assign default Employee role if no roles specified
                $user->assignRole('Employee');
            }

            // Handle profile image
            if ($request->hasFile('profile_image')) {
                $user->addMediaFromRequest('profile_image')
                    ->toMediaCollection('profile_images');
            }

            DB::commit();

            return response()->json([
                'message' => 'User created successfully.',
                'user' => new UserResource($user->fresh(['department', 'designation', 'roles', 'currentDevice'])),
            ], 201);
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
     * Update a user.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $validated = $request->validated();

            // Remove profile_image from validated data as it's handled by Media Library
            unset($validated['profile_image']);

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Convert single_device_login_enabled to boolean
            if (isset($validated['single_device_login_enabled'])) {
                $validated['single_device_login_enabled'] = filter_var($validated['single_device_login_enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            $user->update($validated);

            // Update roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->input('roles'));
            }

            // Handle profile image
            if ($request->hasFile('profile_image')) {
                $user->clearMediaCollection('profile_images');
                $user->addMediaFromRequest('profile_image')
                    ->toMediaCollection('profile_images');
            }

            DB::commit();

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => new UserResource($user->fresh(['department', 'designation', 'roles', 'currentDevice'])),
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

    public function updateUserRole(UpdateUserRoleRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $user->syncRoles($request->input('roles'));

            return response()->json([
                'message' => 'Role updated successfully',
                'user' => new UserResource($user->fresh(['department', 'designation', 'roles', 'currentDevice'])),
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'error' => 'Failed to update user role.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id, UpdateUserStatusRequest $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        // Toggle the active status based on the request
        $user->active = $request->input('active');

        // Handle soft delete or restore based on the new status
        if ($user->active) {
            $user->restore(); // Restore the user if they were soft deleted
        } else {
            $user->delete();  // Soft delete the user if marking inactive
        }

        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'active' => $user->active,
            'user' => new UserResource($user->fresh(['department', 'designation', 'roles', 'currentDevice'])),
        ]);
    }

    /**
     * Delete a user (soft delete).
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('delete', $user);

        try {
            $user->active = false;
            $user->save();
            $user->delete();

            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'error' => 'Failed to delete user.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateFcmToken(Request $request)
    {
        // Validate that the request contains an FCM token
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Update the user's FCM token
        $user->fcm_token = $request->input('fcm_token');
        $user->save();

        return response()->json([
            'message' => 'FCM token updated successfully',
            'fcm_token' => $user->fcm_token,
        ]);
    }

    public function paginate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        try {
            $perPage = $request->input('perPage', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $role = $request->input('role');
            $status = $request->input('status');
            $department = $request->input('department');

            // Base query
            $query = User::withTrashed()
                ->with(['department', 'designation', 'roles', 'currentDevice', 'reportsTo']);

            // Filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($role && $role !== 'all') {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }

            if ($status && $status !== 'all') {
                $query->where('active', $status === 'active' ? 1 : 0);
            }

            if ($department && $department !== 'all') {
                $query->where('department_id', $department);
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
     * Get user statistics - Industry standard user management metrics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Basic user counts
            $totalUsers = User::withTrashed()->count();
            $activeUsers = User::withTrashed()->where('active', 1)->count();
            $inactiveUsers = User::withTrashed()->where('active', 0)->count();
            $deletedUsers = User::onlyTrashed()->count();

            // Role and permission analytics
            $roleCount = Role::count();
            $rolesWithUsers = Role::withCount('users')->get()->map(function ($role) use ($totalUsers) {
                return [
                    'name' => $role->name,
                    'count' => $role->users_count,
                    'percentage' => $totalUsers > 0 ? round(($role->users_count / $totalUsers) * 100, 1) : 0,
                ];
            });

            // Department-wise employee distribution (via employees table)
            $departmentStats = Department::withCount('employees')
                ->get()
                ->map(function ($dept) use ($totalUsers) {
                    return [
                        'name' => $dept->name,
                        'count' => $dept->employees_count,
                        'percentage' => $totalUsers > 0 ? round(($dept->employees_count / $totalUsers) * 100, 1) : 0,
                    ];
                });

            // User activity and engagement metrics
            $now = now();
            $recentActivity = [
                'new_users_30_days' => User::where('created_at', '>=', $now->copy()->subDays(30))->count(),
                'new_users_90_days' => User::where('created_at', '>=', $now->copy()->subDays(90))->count(),
                'new_users_year' => User::where('created_at', '>=', $now->copy()->subYear())->count(),
                'recently_active' => User::where('updated_at', '>=', $now->copy()->subDays(7))->count(),
            ];

            // User status ratios and health metrics
            $statusRatio = [
                'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
                'inactive_percentage' => $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100, 1) : 0,
                'deleted_percentage' => $totalUsers > 0 ? round(($deletedUsers / $totalUsers) * 100, 1) : 0,
            ];

            // User growth analytics
            $previousMonthUsers = User::withTrashed()->where('created_at', '<', $now->copy()->startOfMonth())->count();
            $currentMonthUsers = User::withTrashed()->where('created_at', '>=', $now->copy()->startOfMonth())->count();
            $userGrowthRate = $previousMonthUsers > 0 ? round((($currentMonthUsers / $previousMonthUsers) * 100), 1) : 0;

            // Security and compliance metrics
            $securityMetrics = [
                'users_with_roles' => User::whereHas('roles')->count(),
                'users_without_roles' => User::whereDoesntHave('roles')->count(),
                'admin_users' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'like', '%admin%');
                })->count(),
                'regular_users' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'not like', '%admin%');
                })->count(),
            ];

            // Employee coverage - users who have employee records
            $employeeCount = Employee::count();

            // System health indicators
            $systemHealth = [
                'user_activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
                'role_coverage' => $totalUsers > 0 ? round(($securityMetrics['users_with_roles'] / $totalUsers) * 100, 1) : 0,
                'employee_coverage' => $totalUsers > 0 ? round(($employeeCount / $totalUsers) * 100, 1) : 0,
            ];

            // Compile comprehensive user management stats
            $stats = [
                // Basic overview
                'overview' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                    'deleted_users' => $deletedUsers,
                    'total_roles' => $roleCount,
                    'total_departments' => Department::count(),
                    'total_employees' => $employeeCount,
                ],

                // Distribution analytics
                'distribution' => [
                    'by_role' => $rolesWithUsers,
                    'by_department' => $departmentStats,
                    'by_status' => [
                        ['name' => 'Active', 'count' => $activeUsers, 'percentage' => $statusRatio['active_percentage']],
                        ['name' => 'Inactive', 'count' => $inactiveUsers, 'percentage' => $statusRatio['inactive_percentage']],
                        ['name' => 'Deleted', 'count' => $deletedUsers, 'percentage' => $statusRatio['deleted_percentage']],
                    ],
                ],

                // Activity and engagement
                'activity' => [
                    'recent_registrations' => $recentActivity,
                    'user_growth_rate' => $userGrowthRate,
                    'current_month_registrations' => $currentMonthUsers,
                ],

                // Security and access control
                'security' => [
                    'access_metrics' => $securityMetrics,
                    'role_distribution' => $rolesWithUsers,
                ],

                // System health
                'health' => [
                    'status_ratio' => $statusRatio,
                    'system_metrics' => $systemHealth,
                ],

                // Quick dashboard metrics
                'quick_metrics' => [
                    'total_users' => $totalUsers,
                    'active_ratio' => $statusRatio['active_percentage'],
                    'role_diversity' => $roleCount,
                    'department_diversity' => Department::count(),
                    'recent_activity' => $recentActivity['new_users_30_days'],
                    'system_health_score' => round(($systemHealth['user_activation_rate'] + $systemHealth['role_coverage'] + $systemHealth['employee_coverage']) / 3, 1),
                ],
            ];

            return response()->json([
                'stats' => $stats,
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'currency' => 'users',
                    'period' => 'current',
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
     * Get all roles assigned to a user
     */
    public function getUserRoles($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get user roles: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve user roles',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync roles for a user (replaces existing roles)
     */
    public function syncUserRoles(Request $request, $id)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        try {
            $user = User::findOrFail($id);

            // Store original roles for audit
            $originalRoles = $user->roles->pluck('name')->toArray();

            // Sync roles
            $user->syncRoles($request->input('roles'));

            Log::info('User roles synced', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'original_roles' => $originalRoles,
                'new_roles' => $request->input('roles'),
                'synced_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'User roles synced successfully',
                'user' => new UserResource($user->fresh(['department', 'designation', 'roles', 'currentDevice'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync user roles: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to sync user roles',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a team invitation email.
     */
    public function sendInvitation(SendTeamInvitationRequest $request): JsonResponse
    {
        $this->authorize('users.invite');

        $validated = $request->validated();

        // Check if user already exists
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'A user with this email already exists in the organization.',
                'errors' => ['email' => ['A user with this email already exists in the organization.']],
            ], 422);
        }

        // Check for pending invitation
        if (TenantInvitation::hasPendingInvitation($validated['email'])) {
            return response()->json([
                'message' => 'An invitation has already been sent to this email address.',
                'errors' => ['email' => ['An invitation has already been sent to this email address.']],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the invitation
            $invitation = TenantInvitation::create([
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invited_by' => auth()->id(),
                'metadata' => [
                    'department_id' => $validated['department_id'] ?? null,
                    'designation_id' => $validated['designation_id'] ?? null,
                    'message' => $validated['message'] ?? null,
                ],
            ]);

            // Send invitation email using MailService
            $notification = new InviteTeamMember($invitation);
            $notification->sendEmail();

            DB::commit();

            Log::info('Team invitation sent', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'invited_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Invitation sent successfully to {$validated['email']}.",
                'invitation' => $invitation,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to send team invitation', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to send invitation. Please try again.',
                'errors' => ['email' => ['Failed to send invitation. Please try again.']],
            ], 500);
        }
    }

    /**
     * Get pending invitations.
     */
    public function pendingInvitations(): JsonResponse
    {
        $this->authorize('users.view');

        $invitations = TenantInvitation::with('inviter')
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'invitations' => $invitations,
        ]);
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(TenantInvitation $invitation): JsonResponse
    {
        $this->authorize('users.invite');

        if (! $invitation->isValid()) {
            // Extend the expiration
            $invitation->update([
                'expires_at' => now()->addDays(7),
            ]);
        }

        try {
            // Send invitation email using MailService
            $notification = new InviteTeamMember($invitation);
            $notification->sendEmail();

            Log::info('Invitation resent', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Invitation resent to {$invitation->email}.",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resend invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to resend invitation.',
            ], 500);
        }
    }

    /**
     * Cancel an invitation.
     */
    public function cancelInvitation(TenantInvitation $invitation): JsonResponse
    {
        $this->authorize('users.invite');

        try {
            $invitation->cancel();

            Log::info('Invitation cancelled', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel invitation.',
            ], 500);
        }
    }
}
