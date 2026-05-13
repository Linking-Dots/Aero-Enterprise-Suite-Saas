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
 * Smart Landing Redirect Middleware
 *
 * Redirects users to their first accessible module/sub-module based on role_module_access.
 *
 * Use Cases:
 * 1. Root route '/' - redirect to first accessible page
 * 2. Dashboard access denied - redirect to first accessible page
 * 3. After login - redirect to appropriate landing page
 */
class SmartLandingRedirect
{
    public function __construct(
        protected RoleModuleAccessInterface $roleModuleAccessService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Super admin always goes to dashboard
        if ($user->hasRole(['Super Administrator', 'super-admin', 'tenant_super_administrator'])) {
            return redirect()->route('core.dashboard');
        }

        // Check if user has Dashboard access
        if ($this->roleModuleAccessService->userCanAccessSubModule($user, 'core', 'dashboard')) {
            return redirect()->route('core.dashboard');
        }

        // Get first accessible route
        $firstRoute = $this->roleModuleAccessService->getFirstAccessibleRoute($user);

        if ($firstRoute) {
            // Check if it's a named route or a URL path
            try {
                if (route()->has($firstRoute)) {
                    return redirect()->route($firstRoute);
                }
            } catch (\Exception $e) {
                // Not a named route, try as URL
            }

            // Treat as URL path
            $url = $firstRoute;
            if (! str_starts_with($url, '/')) {
                $url = '/'.$url;
            }

            return redirect($url);
        }

        // No accessible routes - show error page
        Log::warning('User has no accessible modules', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray(),
        ]);

        // Inertia response for no access
        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'Errors/UnifiedError',
                'props' => [
                    'error' => [
                        'code' => 403,
                        'type' => 'NoModuleAccess',
                        'title' => 'No Access',
                        'message' => 'Your account does not have access to any modules. Please contact your administrator.',
                        'trace_id' => Str::uuid()->toString(),
                        'showHomeButton' => false,
                        'showRetryButton' => false,
                        'showLogoutButton' => true,
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'url' => $request->url(),
                'version' => '',
            ], 403, ['X-Inertia' => 'true']);
        }

        return response()->view('errors.no-access', [], 403);
    }
}
