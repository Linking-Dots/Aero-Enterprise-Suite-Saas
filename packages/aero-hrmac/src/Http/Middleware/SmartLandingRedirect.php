<?php

declare(strict_types=1);

namespace Aero\HRMAC\Http\Middleware;

use Aero\Contracts\RoleModuleAccessInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Smart Landing Redirect Middleware
 *
 * Redirects authenticated users to their first accessible module/route
 * based on their role's module access configuration.
 *
 * This is used for:
 * - Root route '/' to redirect to appropriate dashboard/landing
 * - After login to redirect to first accessible module
 * - When accessing a module the user doesn't have access to
 *
 * Priority Order:
 * 1. Dashboard (if user has core module access)
 * 2. First accessible sub-module's route
 * 3. Login page (fallback)
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

        // Not authenticated - continue to login
        if (! $user) {
            return $next($request);
        }

        // Find the first accessible route for this user
        $targetRoute = $this->roleModuleAccessService->getFirstAccessibleRoute($user);

        if ($targetRoute) {
            // Check if we're not already on this route to avoid loops
            if ($request->routeIs($targetRoute)) {
                return $next($request);
            }

            Log::debug('Smart landing redirect', [
                'user_id' => $user->id,
                'target_route' => $targetRoute,
                'current_path' => $request->path(),
            ]);

            return redirect()->route($targetRoute);
        }

        // No accessible routes - log out and redirect to login
        Log::warning('No accessible routes for user', [
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'error',
            'Your account does not have access to any modules. Please contact your administrator.'
        );
    }
}
