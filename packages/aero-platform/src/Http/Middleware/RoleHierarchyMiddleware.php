<?php

namespace Aero\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Aero\HRMAC\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role Hierarchy Middleware
 *
 * Ensures users can only manage roles below their hierarchy level
 */
class RoleHierarchyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Get user's highest role hierarchy level (lower number = higher authority)
        $userHierarchyLevel = $this->getUserHierarchyLevel($user);

        // For role management operations, check if user can manage the target role
        if ($request->route()->parameter('id') && str_contains($request->route()->getName(), 'roles')) {
            $targetRoleId = $request->route()->parameter('id');
            $targetRole = Role::find($targetRoleId);

            if ($targetRole && $targetRole->hierarchy_level <= $userHierarchyLevel) {
                \Log::warning('Hierarchy violation attempt', [
                    'user_id' => $user->id,
                    'user_hierarchy' => $userHierarchyLevel,
                    'target_role' => $targetRole->name,
                    'target_hierarchy' => $targetRole->hierarchy_level,
                    'action' => $request->method(),
                    'timestamp' => now(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'You cannot manage roles at or above your hierarchy level.',
                        'user_level' => $userHierarchyLevel,
                        'target_level' => $targetRole->hierarchy_level,
                    ], 403);
                }

                return back()->with('error', 'You cannot manage roles at or above your hierarchy level.');
            }
        }

        // Add hierarchy level to request for controllers to use
        $request->merge(['user_hierarchy_level' => $userHierarchyLevel]);

        return $next($request);
    }

    /**
     * Get user's hierarchy level
     */
    private function getUserHierarchyLevel($user): int
    {
        $hierarchyLevels = [
            'Super Administrator' => 1,
            'Administrator' => 2,
            'HR Manager' => 3,
            'Department Head' => 4,
            'Team Lead' => 5,
            'Senior Employee' => 6,
            'Employee' => 10,
        ];

        $highestLevel = 999; // Default to lowest authority
        foreach ($user->roles as $role) {
            $level = $hierarchyLevels[$role->name] ?? $role->hierarchy_level ?? 999;
            if ($level < $highestLevel) {
                $highestLevel = $level;
            }
        }

        return $highestLevel;
    }
}
