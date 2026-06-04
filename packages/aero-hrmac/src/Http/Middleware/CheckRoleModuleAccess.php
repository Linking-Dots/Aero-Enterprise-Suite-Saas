<?php

declare(strict_types=1);

namespace Aero\HRMAC\Http\Middleware;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Services\HrmacAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Role Module Access Middleware
 *
 * Enforces role-based module/sub-module access using the role_module_access table.
 * Supports both dot-notation and legacy comma-separated formats.
 *
 * Usage in routes (dot-notation — preferred):
 *   - hrmac:hrm                              - Module-level access
 *   - hrmac:hrm.employees                    - Sub-module access
 *   - hrmac:hrm.employees.departments        - Component-level (checks sub-module)
 *   - hrmac:hrm.attendance.own-attendance,punch - Component + action check
 *
 * Legacy format (comma-separated — still supported):
 *   - role.access:hrm,employees              - Sub-module access
 */
class CheckRoleModuleAccess
{
    /**
     * Sub-module code aliases for backward compatibility.
     * Maps route names to canonical config/module.php codes.
     *
     * @var array<string, string>
     */
    protected const SUB_MODULE_ALIASES = [
        'time-off' => 'leaves',
        'hr-reports' => 'hr-analytics',
    ];

    public function __construct(
        protected RoleModuleAccessInterface $roleModuleAccessService,
        protected HrmacAuditService $audit,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  string  $path  Dot-notation path (e.g., 'hrm.employees.departments') or legacy module code
     * @param  string|null  $extra1  Legacy submodule code, or component override, or action code
     * @param  string|null  $extra2  Action code when extra1 is component
     */
    public function handle(
        Request $request,
        Closure $next,
        string $path,
        ?string $extra1 = null,
        ?string $extra2 = null
    ): Response {
        $user = $request->user() ?? Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()->to($this->getLoginUrl($request));
        }

        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Parse the access path into module/submodule/component/action
        [$moduleCode, $subModuleCode, $componentCode, $actionCode] = $this->parsePath($path, $extra1, $extra2);

        // Check access at the appropriate level
        $hasAccess = $this->checkAccess($user, $moduleCode, $subModuleCode, $componentCode, $actionCode);

        if (! $hasAccess) {
            // Plan 04 T1 — persist denial to hrmac_audit_log (queryable)
            $this->audit->logDenial($request, $user, $moduleCode, $subModuleCode, $componentCode, $actionCode);

            // Keep the Log channel write for short-term ops dashboards / SIEM tailing
            Log::warning('Role module access denied', [
                'user_id' => $user->id,
                'path' => $path,
                'module' => $moduleCode,
                'sub_module' => $subModuleCode,
                'component' => $componentCode,
                'action' => $actionCode,
                'user_roles' => $user->roles->pluck('name')->toArray(),
            ]);

            return $this->denyAccess($request, $moduleCode, $subModuleCode);
        }

        return $next($request);
    }

    /**
     * Parse middleware parameters into module/submodule/component/action.
     *
     * @return array{0: string, 1: ?string, 2: ?string, 3: ?string}
     */
    protected function parsePath(string $path, ?string $extra1, ?string $extra2): array
    {
        // Detect format: dot-notation vs legacy comma-separated
        if (str_contains($path, '.')) {
            // Dot-notation: hrm.employees.departments
            $parts = explode('.', $path);
            $moduleCode = $this->normalize($parts[0]);
            $subModuleCode = isset($parts[1]) ? $this->normalize($parts[1]) : null;
            $componentCode = isset($parts[2]) ? $this->normalize($parts[2]) : null;

            // Comma params extend the path
            // Pattern: hrmac:hrm.attendance.own-attendance,punch → action=punch
            // Pattern: hrmac:hrm.employees.departments,department-list,create → component=department-list, action=create
            if ($extra1 !== null && $extra2 !== null) {
                // Two extra params: override component + action
                $componentCode = $this->normalize($extra1);
                $actionCode = $this->normalize($extra2);
            } elseif ($extra1 !== null) {
                // One extra param: it's the action code
                $actionCode = $this->normalize($extra1);
            } else {
                $actionCode = null;
            }
        } else {
            // Legacy format: hrmac:hrm,employees or role.access:hrm,employees
            $moduleCode = $this->normalize($path);
            $subModuleCode = $extra1 ? $this->normalize($extra1) : null;
            $componentCode = null;
            $actionCode = $extra2 ? $this->normalize($extra2) : null;
        }

        // Resolve sub-module aliases
        if ($subModuleCode) {
            $subModuleCode = self::SUB_MODULE_ALIASES[$subModuleCode] ?? $subModuleCode;
        }

        return [$moduleCode, $subModuleCode, $componentCode, $actionCode];
    }

    /**
     * Check access at the most specific level possible, falling back to broader checks.
     */
    protected function checkAccess(mixed $user, string $moduleCode, ?string $subModuleCode, ?string $componentCode, ?string $actionCode): bool
    {
        // If action specified, try action-level check first
        if ($actionCode && $subModuleCode) {
            return $this->roleModuleAccessService->userCanAccessAction($user, $moduleCode, $subModuleCode, $actionCode);
        }

        // Sub-module level check (primary gate for most routes)
        if ($subModuleCode) {
            return $this->roleModuleAccessService->userCanAccessSubModule($user, $moduleCode, $subModuleCode);
        }

        // Module-level check
        return $this->roleModuleAccessService->userCanAccessModule($user, $moduleCode);
    }

    /**
     * Normalize a code segment: convert underscores to dashes, lowercase.
     */
    protected function normalize(string $code): string
    {
        return strtolower(str_replace('_', '-', trim($code)));
    }

    /**
     * Check if the given user holds a super-admin role.
     *
     * Context-free: HRMAC does NOT detect the active guard. The user's roles are
     * resolved from the current (host-decided) connection, so a tenant user only
     * carries tenant role rows and a platform user only carries central role rows.
     * A single flat super_admin_roles list (the union of all contexts) is therefore
     * safe — names that don't exist in the current DB simply never match.
     *
     * Accepts both the flat list format and the legacy guard-scoped assoc format
     * (values are flattened) so transitional configs keep working.
     */
    protected function isSuperAdmin($user): bool
    {
        $rolesConfig = config('hrmac.super_admin_roles', []);

        $superAdminRoles = [];
        array_walk_recursive($rolesConfig, function ($role) use (&$superAdminRoles) {
            if (is_string($role) && $role !== '') {
                $superAdminRoles[] = $role;
            }
        });

        if (empty($superAdminRoles)) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($superAdminRoles);
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($superAdminRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Deny access with appropriate response.
     */
    protected function denyAccess(Request $request, string $moduleCode, ?string $subModuleCode): Response
    {
        $message = $subModuleCode
            ? "You don't have access to the {$subModuleCode} section."
            : "You don't have access to the {$moduleCode} module.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'reason' => 'module_access_denied',
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

        return redirect()->to($this->getLoginUrl($request))->with('error', $message);
    }

    /**
     * Get the appropriate login URL based on domain context.
     */
    protected function getLoginUrl(Request $request): string
    {
        // Check if we're on admin domain
        $host = $request->getHost();
        $adminDomain = config('tenancy.admin_domain', 'admin.'.config('tenancy.platform_domain'));

        if ($host === $adminDomain || str_starts_with($host, 'admin.')) {
            return url('/login'); // Admin domain login
        }

        // Try named route for regular login
        if (Route::has('login')) {
            return route('login');
        }

        return url('/login');
    }
}
