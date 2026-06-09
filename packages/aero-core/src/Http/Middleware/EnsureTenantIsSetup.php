<?php

namespace Aero\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantIsSetup Middleware
 *
 * Ensures proper tenant initialization flow:
 * 1. If no admin user exists → redirect to /admin-setup
 * 2. If admin exists but onboarding not completed → redirect to /onboarding
 * 3. If everything is complete → allow normal access
 *
 * Note: Skips verification when in maintenance mode to prevent redirect loops.
 * Note: In standalone mode (without aero-platform), maintenance mode checks are skipped.
 */
class EnsureTenantIsSetup
{
    /**
     * Routes that should be excluded from this check.
     */
    protected array $except = [
        'admin-setup',
        'admin-setup/*',
        'onboarding',
        'onboarding/*',
        'logout',
        'api/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for excluded routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Skip verification if system is in maintenance mode
        // This prevents redirect loops when maintenance pages need to be shown
        if ($this->isInMaintenanceMode()) {
            return $next($request);
        }

        // Get current tenant
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        // Check if admin user exists
        if (! $this->hasAdminUser()) {
            return redirect('/admin-setup')
                ->with('info', 'Please complete the admin setup to continue.');
        }

        // Check if onboarding is completed
        if (! $this->isOnboardingCompleted($tenant)) {
            return redirect('/onboarding')
                ->with('info', 'Please complete the onboarding process to continue.');
        }

        return $next($request);
    }

    /**
     * Check if the request should skip this middleware.
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if at least one admin user exists in the tenant database.
     */
    protected function hasAdminUser(): bool
    {
        try {
            // Check if users table exists first
            if (! DB::getSchemaBuilder()->hasTable('users')) {
                return false;
            }

            // Check for users with Super Administrator or Administrator role
            // Using model_has_roles table from Spatie Permission (if it exists)
            if (DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                $adminExists = DB::table('users')
                    ->join('model_has_roles', function ($join) {
                        $join->on('users.id', '=', 'model_has_roles.model_id')
                            ->where('model_has_roles.model_type', '=', 'user');
                    })
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('roles.name', ['Super Administrator', 'Administrator'])
                    ->exists();

                if ($adminExists) {
                    return true;
                }
            }

            // Fallback: Check if ANY user exists (for fresh installs)
            return DB::table('users')->exists();

        } catch (\Exception $e) {
            // If there's any error (table doesn't exist, etc.), assume no admin
            return false;
        }
    }

    /**
     * Check if tenant has completed onboarding.
     *
     * @param  mixed  $tenant  The tenant model (type varies based on mode)
     */
    protected function isOnboardingCompleted(mixed $tenant): bool
    {
        // Use the TenantOnboardingController's static method if available (SaaS mode)
        $controllerClass = 'Aero\\Platform\\Http\\Controllers\\TenantOnboardingController';
        if (class_exists($controllerClass) && method_exists($controllerClass, 'isOnboardingCompleted')) {
            return $controllerClass::isOnboardingCompleted();
        }

        // Standalone mode: no onboarding required
        return true;
    }

    /**
     * Check if the system is in maintenance mode (platform or tenant level).
     *
     * Returns true if either:
     * - Platform-wide maintenance is enabled (SaaS mode only)
     * - Current tenant is in maintenance mode
     *
     * In standalone mode, only tenant-level maintenance is checked.
     */
    protected function isInMaintenanceMode(): bool
    {
        // Check platform-level maintenance mode only if aero-platform is installed
        if (class_exists('Aero\Platform\Models\PlatformSetting') && function_exists('tenancy')) {
            try {
                $platformSettingClass = 'Aero\Platform\Models\PlatformSetting';
                $platformMaintenance = tenancy()->central(function () use ($platformSettingClass) {
                    return $platformSettingClass::isMaintenanceModeEnabled();
                });

                if ($platformMaintenance) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Silently continue if tenancy functions aren't available
            }
        }

        // Check tenant-level maintenance mode
        $tenant = function_exists('tenant') ? tenant() : null;
        if ($tenant && method_exists($tenant, 'isInMaintenanceMode') && $tenant->isInMaintenanceMode()) {
            return true;
        }

        return false;
    }
}
