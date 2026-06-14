<?php

namespace Aero\Platform\Http\Middleware;

use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Tenant;
use App\Http\Controllers\Tenant\TenantOnboardingController;
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

            // Check for users with 'super_admin' or 'admin' role
            // Using model_has_roles table from Spatie Permission (if it exists)
            if (DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                $adminExists = DB::table('users')
                    ->join('model_has_roles', function ($join) {
                        $join->on('users.id', '=', 'model_has_roles.model_id')
                            ->where('model_has_roles.model_type', '=', 'user');
                    })
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('roles.name', ['super_admin', 'admin'])
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
     */
    protected function isOnboardingCompleted(Tenant $tenant): bool
    {
        // Use the TenantOnboardingController's static method
        return TenantOnboardingController::isOnboardingCompleted();
    }

    /**
     * Check if the system is in maintenance mode (platform or tenant level).
     *
     * Returns true if either:
     * - Platform-wide maintenance is enabled
     * - Current tenant is in maintenance mode
     */
    protected function isInMaintenanceMode(): bool
    {
        // Check platform-level maintenance mode (use central database)
        $platformMaintenance = tenancy()->central(function () {
            return PlatformSetting::isMaintenanceModeEnabled();
        });

        if ($platformMaintenance) {
            return true;
        }

        // Check tenant-level maintenance mode
        $tenant = tenant();
        if ($tenant && $tenant->isInMaintenanceMode()) {
            return true;
        }

        return false;
    }
}
