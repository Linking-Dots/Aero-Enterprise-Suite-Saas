<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Services\ModuleAccessService;
use Aero\Core\ValueObjects\RequestContext;
use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Contracts\ProductAccessInterface;
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
        if (! $isStandalone && app()->bound(ProductAccessInterface::class)) {
            try {
                $tenantKey = tenant()?->getTenantKey();
                if ($tenantKey) {
                    /** @var ProductAccessInterface $productAccess */
                    $productAccess = app(ProductAccessInterface::class);

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
     * Detect which authentication guard to use based on the injected RequestContext.
     *
     * RequestContext is set by ResolvePlatformContext or ResolveTenantContext middleware
     * on route groups. This eliminates hostname sniffing and active-session bleeding.
     *
     * Falls back to 'web' if no context is bound (e.g. during testing or pre-install).
     */
    protected function detectGuard(Request $request): string
    {
        if (is_standalone_mode()) {
            return 'web';
        }

        try {
            $context = app(RequestContext::class);

            return $context->guard;
        } catch (\Throwable) {
            return 'web';
        }
    }

    /**
     * Handle access control for platform/landlord context using HRMAC.
     *
     * Uses the same hierarchy as the tenant side: module → sub_module → component → action.
     * RoleModuleAccessInterface handles super-admin bypass and cascade internally.
     * No spatie permissions involved — HRMAC owns all access control on both sides.
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
        // Emergency bypass: Super Platform Admin is never locked out by HRMAC failures.
        // This role is checked at the model level so it survives HRMAC infrastructure outages.
        try {
            if ($user->roles()->where('name', 'Super Platform Admin')->exists()) {
                return $next($request);
            }
        } catch (\Throwable) {
            // Role lookup itself failed — proceed to HRMAC check
        }

        try {
            /** @var RoleModuleAccessInterface $hrmac */
            $hrmac = app(RoleModuleAccessInterface::class);

            $allowed = match (true) {
                // Action-level check (most granular) — requires subModuleCode
                $actionCode !== null && $subModuleCode !== null => $hrmac->userCanAccessAction($user, $moduleCode, $subModuleCode, $actionCode),

                // SubModule-level check — also covers component-level (cascade)
                $subModuleCode !== null => $hrmac->userCanAccessSubModule($user, $moduleCode, $subModuleCode),

                // Module-level check (broadest)
                default => $hrmac->userCanAccessModule($user, $moduleCode),
            };

        } catch (\Throwable $e) {
            // HRMAC not available (e.g. tables not migrated yet) — deny access safely
            Log::warning('HRMAC unavailable for platform access check', [
                'error' => $e->getMessage(),
                'module' => $moduleCode,
                'user' => $user->id ?? null,
            ]);
            $allowed = false;
        }

        if (! $allowed) {
            return $this->denyAccess(
                $request,
                'insufficient_permissions',
                'You do not have access to this platform feature.',
                403,
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
