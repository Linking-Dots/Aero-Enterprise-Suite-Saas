<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Aero\Contracts\RoleModuleAccessInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Role Module Access Middleware
 *
 * Enforces role-based module/sub-module access using the role_module_access table.
 * This is different from permission-based access - it checks what modules/sub-modules
 * are assigned to the user's role(s).
 *
 * Usage in routes:
 *   - role.access:core,dashboard       - Check sub-module access (module code, submodule code)
 *   - role.access:hrm                  - Check module-level access
 *   - role.access:hrm,employees        - Check sub-module access within HRM
 */
class CheckRoleModuleAccess
{
    public function __construct(
        protected RoleModuleAccessInterface $roleModuleAccessService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  string  $moduleCode  The module code (e.g., 'core', 'hrm')
     * @param  string|null  $subModuleCode  The sub-module code (optional, e.g., 'dashboard', 'employees')
     */
    public function handle(
        Request $request,
        Closure $next,
        string $moduleCode,
        ?string $subModuleCode = null
    ): Response {
        $user = Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check access based on level
        $hasAccess = $subModuleCode
            ? $this->roleModuleAccessService->userCanAccessSubModule($user, $moduleCode, $subModuleCode)
            : $this->roleModuleAccessService->userCanAccessModule($user, $moduleCode);

        if (! $hasAccess) {
            Log::warning('Role module access denied', [
                'user_id' => $user->id,
                'module' => $moduleCode,
                'sub_module' => $subModuleCode,
                'user_roles' => $user->roles->pluck('name')->toArray(),
            ]);

            return $this->denyAccess($request, $moduleCode, $subModuleCode);
        }

        return $next($request);
    }

    /**
     * Deny access with appropriate response.
     */
    protected function denyAccess(Request $request, string $moduleCode, ?string $subModuleCode): Response
    {
        $message = $subModuleCode
            ? "You don't have access to the {$subModuleCode} section."
            : "You don't have access to this module.";

        // JSON API response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'reason' => 'insufficient_role_access',
                'message' => $message,
                'meta' => [
                    'module' => $moduleCode,
                    'sub_module' => $subModuleCode,
                ],
            ], 403);
        }

        // Inertia response
        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'Errors/UnifiedError',
                'props' => [
                    'error' => [
                        'code' => 403,
                        'type' => 'AccessDenied',
                        'title' => 'Access Denied',
                        'message' => $message,
                        'trace_id' => Str::uuid()->toString(),
                        'showHomeButton' => true,
                        'showRetryButton' => false,
                        'details' => [
                            'module' => $moduleCode,
                            'sub_module' => $subModuleCode,
                        ],
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'url' => $request->url(),
                'version' => '',
            ], 403, ['X-Inertia' => 'true']);
        }

        // Try to redirect to an accessible route
        $redirectRoute = $this->roleModuleAccessService->getFirstAccessibleRoute($request->user());
        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with('warning', $message);
        }

        return redirect()->route('login')->with('error', $message);
    }

    /**
     * Check if user is a super admin.
     */
    protected function isSuperAdmin($user): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        $superAdminRoles = config('hrmac.super_admin_roles', [
            'Super Administrator',
            'super-admin',
            'tenant_super_administrator',
        ]);

        return $user->hasRole($superAdminRoles);
    }
}
