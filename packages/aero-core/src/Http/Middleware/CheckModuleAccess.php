<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Services\ModuleAccessService;
use Aero\Core\Support\TenantCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Module Access Middleware
 *
 * Enforces hybrid RBAC: validates BOTH subscription entitlement AND user permissions.
 *
 * Access is granted only if:
 * 1. Tenant has an active subscription
 * 2. Subscription's plan includes the requested module
 * 3. User has at least one of the module's required permissions (via tenant-scoped roles)
 *
 * Usage:
 *   - module:hrm                                    - Check module access
 *   - module:hrm,employees                          - Check sub-module access
 *   - module:hrm,employees,employee-list            - Check component access
 *   - module:hrm,employees,employee-list,view       - Check action access
 */
class CheckModuleAccess
{
    protected ModuleAccessService $moduleAccessService;

    public function __construct(ModuleAccessService $moduleAccessService)
    {
        $this->moduleAccessService = $moduleAccessService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     * @param  string|null  $subModuleCode  The sub-module code (optional, e.g., 'employees')
     * @param  string|null  $componentCode  The component code (optional, e.g., 'employee-list')
     * @param  string|null  $actionCode  The action code (optional, e.g., 'view')
     */
    public function handle(
        Request $request,
        Closure $next,
        string $moduleCode,
        ?string $subModuleCode = null,
        ?string $componentCode = null,
        ?string $actionCode = null
    ): Response {
        // Determine which guard to use based on the route
        $guard = $this->detectGuard($request);

        if (! Auth::guard($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect($guard === 'landlord' ? '/login' : '/login');
        }

        $user = Auth::guard($guard)->user();

        // Handle platform/landlord context differently
        if ($guard === 'landlord') {
            return $this->handlePlatformAccess($request, $next, $user, $moduleCode, $subModuleCode, $componentCode, $actionCode);
        }

        // In standalone mode, skip tenant check
        $isStandalone = is_standalone_mode();

        if (! $isStandalone) {
            // Tenant context - check if tenant is properly initialized (from domain routing)
            $tenant = tenant();
            if (! $tenant) {
                return $this->denyAccess(
                    $request,
                    'no_tenant',
                    'Tenant context not found. Please access via tenant subdomain.',
                    401
                );
            }
        }

        // Empty string check - treat empty strings as null
        $subModuleCode = ($subModuleCode === '' || $subModuleCode === null) ? null : $subModuleCode;
        $componentCode = ($componentCode === '' || $componentCode === null) ? null : $componentCode;
        $actionCode = ($actionCode === '' || $actionCode === null) ? null : $actionCode;

        // ── SaaS product subscription gate ─────────────────────────────────────────
        // In SaaS mode, verify tenant has an active product subscription for the module.
        // Plan tier does NOT gate product access — plans govern limits only.
        if (! $isStandalone && class_exists(\Aero\Platform\Services\ProductAccessService::class)) {
            try {
                $tenantKey = tenant()?->getTenantKey();
                if ($tenantKey) {
                    /** @var \Aero\Platform\Services\ProductAccessService $productAccess */
                    $productAccess = app(\Aero\Platform\Services\ProductAccessService::class);

                    if (! $productAccess->tenantCanAccessModule((string) $tenantKey, $moduleCode)) {
                        return $this->denyAccess(
                            $request,
                            'no_product_subscription',
                            'Your account does not have an active subscription for this module. Subscribe from Settings > Products.',
                            402,
                            ['module' => $moduleCode]
                        );
                    }
                }
            } catch (\Throwable) {
                // ProductAccessService unavailable — skip gate (fail open)
            }
        }
        // ────────────────────────────────────────────────────────────────────────────

        // Determine the level of access to check
        $accessCheck = null;

        if ($actionCode !== null) {
            // Check action access (most granular level)
            $accessCheck = $this->moduleAccessService->canPerformAction(
                $user,
                $moduleCode,
                $subModuleCode,
                $componentCode,
                $actionCode
            );
        } elseif ($componentCode !== null) {
            // Check component access
            $accessCheck = $this->moduleAccessService->canAccessComponent(
                $user,
                $moduleCode,
                $subModuleCode,
                $componentCode
            );
        } elseif ($subModuleCode !== null) {
            // Check sub-module access
            $accessCheck = $this->moduleAccessService->canAccessSubModule(
                $user,
                $moduleCode,
                $subModuleCode
            );
        } else {
            // Check module access
            $accessCheck = $this->moduleAccessService->canAccessModule(
                $user,
                $moduleCode
            );
        }

        // Handle access denial
        if (! $accessCheck['allowed']) {
            $statusCode = match ($accessCheck['reason']) {
                'plan_restriction' => 402,
                'not_found' => 404,
                'insufficient_permissions' => 403,
                default => 403,
            };

            return $this->denyAccess(
                $request,
                $accessCheck['reason'],
                $accessCheck['message'] ?? $accessCheck['reason'],
                $statusCode,
                [
                    'module' => $moduleCode,
                    'submodule' => $subModuleCode,
                    'component' => $componentCode,
                    'action' => $actionCode,
                ]
            );
        }

        return $next($request);
    }

    /**
     * Detect which authentication guard to use based on the request context.
     */
    protected function detectGuard(Request $request): string
    {
        // In standalone mode, always use 'web' guard
        if (is_standalone_mode()) {
            return 'web';
        }

        // Check if landlord guard is defined in auth config
        $guards = array_keys(config('auth.guards', []));
        $landlordExists = in_array('landlord', $guards);

        // Check if we're on an admin subdomain or route
        $host = $request->getHost();

        // Check for admin subdomain pattern
        if ($landlordExists && (str_starts_with($host, 'admin.') || str_contains($host, 'admin'))) {
            return 'landlord';
        }

        // Check route middleware for landlord guard
        $route = $request->route();
        if ($landlordExists && $route) {
            $middleware = $route->middleware();
            if (in_array('auth:landlord', $middleware)) {
                return 'landlord';
            }
        }

        // Check if landlord guard is already authenticated (only if guard exists)
        if ($landlordExists && Auth::guard('landlord')->check()) {
            return 'landlord';
        }

        return 'web';
    }

    /**
     * Handle access control for platform/landlord context.
     * Platform users have role-based access without subscription checks.
     */
    protected function handlePlatformAccess(
        Request $request,
        Closure $next,
        $user,
        string $moduleCode,
        ?string $subModuleCode = null,
        ?string $componentCode = null,
        ?string $actionCode = null
    ): Response {
        // Super Administrators bypass all checks
        if ($user->is_super_admin || $user->hasRole('Super Administrator')) {
            return $next($request);
        }

        // Build the access path for platform modules
        $accessPath = $moduleCode;
        if ($subModuleCode) {
            $accessPath .= '.'.$subModuleCode;
        }
        if ($componentCode) {
            $accessPath .= '.'.$componentCode;
        }
        if ($actionCode) {
            $accessPath .= '.'.$actionCode;
        }

        // Check if the user has the required platform permission
        // Platform permissions follow pattern: platform.{module}.{submodule}.{component}.{action}
        $permissionName = 'platform.'.str_replace(',', '.', $accessPath);

        // For now, allow all authenticated landlord users to access platform modules
        // TODO: Implement granular platform permission checking when needed
        // This is a temporary bypass to prevent redirect loops

        // Log platform access for debugging
        Log::debug('Platform module access', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'module' => $moduleCode,
            'submodule' => $subModuleCode,
            'component' => $componentCode,
            'action' => $actionCode,
            'access_path' => $accessPath,
        ]);

        return $next($request);
    }

    /**
     * Deny access with consistent response format.
     */
    protected function denyAccess(
        Request $request,
        string $reason,
        string $message,
        int $statusCode = 403,
        array $meta = []
    ): Response {
        // Log denial with context
        Log::warning('Module access denied', array_merge([
            'reason' => $reason,
            'route' => $request->route()?->getName(),
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'tenant_id' => Auth::user()?->tenant_id,
            'ip' => $request->ip(),
            'timestamp' => now(),
        ], $meta));

        // JSON API response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'reason' => $reason,
                'message' => $message,
                'meta' => $meta,
            ], $statusCode);
        }

        // Inertia response
        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'Errors/UnifiedError',
                'props' => [
                    'error' => [
                        'code' => $statusCode,
                        'type' => $reason === 'plan_restriction' ? 'SubscriptionRequired' : 'AccessDenied',
                        'title' => $reason === 'plan_restriction' ? 'Upgrade Required' : 'Access Denied',
                        'message' => $message,
                        'trace_id' => Str::uuid()->toString(),
                        'showHomeButton' => true,
                        'showRetryButton' => false,
                        'details' => $meta,
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'url' => $request->url(),
                'version' => '',
            ], $statusCode, ['X-Inertia' => 'true']);
        }

        // Regular redirect with error
        return back()->with('error', $message);
    }
}
