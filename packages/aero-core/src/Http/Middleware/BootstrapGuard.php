<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BootstrapGuard Middleware
 *
 * Global middleware that ensures ALL requests are redirected to /install
 * if the system is not installed. This middleware has route supremacy.
 *
 * Registered globally via AeroCoreServiceProvider::register() to intercept
 * requests before any routing occurs.
 */
class BootstrapGuard
{
    /**
     * Installation flag file path
     */
    private const INSTALLED_FLAG = 'app/aeos.installed';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if already on install routes
        if ($request->is('install*')) {
            return $next($request);
        }

        // Skip check for public assets and health checks
        if ($request->is('build/*') ||
            $request->is('storage/*') ||
            $request->is('aero-core/health') ||
            $request->is('api/error-log') ||
            $request->is('api/version/check')) {
            return $next($request);
        }

        // Check if system is installed (file-based detection)
        // Only redirect to /install in standalone mode (without platform package)
        if (! $this->installed() && ! $this->hasPlatformPackage()) {
            // If it's an AJAX/API request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System not installed. Please run the installation wizard.',
                    'redirect' => '/install',
                ], 503);
            }

            // Redirect to installation
            return redirect('/install');
        }

        return $next($request);
    }

    /**
     * Check if the system is installed using file-based detection.
     *
     * This is the ONLY authoritative method for checking installation status.
     * Never use database queries for installation detection.
     */
    protected function installed(): bool
    {
        return InstallationState::isInstalled();
    }

    /**
     * Check if the platform package is installed.
     *
     * If platform is installed, we're in SaaS/tenant mode and should not
     * redirect to /install. Instead, let normal auth flow handle it.
     */
    protected function hasPlatformPackage(): bool
    {
        return class_exists('Aero\Platform\AeroPlatformServiceProvider');
    }
}
