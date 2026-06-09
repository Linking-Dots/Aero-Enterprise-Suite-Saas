<?php

namespace Aero\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedirectIfNoAdmin Middleware
 *
 * Redirects login/password reset routes to admin-setup if no admin user exists.
 * This ensures newly provisioned tenants complete admin setup before allowing login.
 */
class RedirectIfNoAdmin
{
    /**
     * Routes that should be excluded from this check.
     */
    protected array $except = [
        'admin-setup',
        'admin-setup/*',
        'api/*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for excluded routes
        if ($this->shouldSkip($request)) {
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
}
