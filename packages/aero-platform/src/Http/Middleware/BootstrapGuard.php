<?php

namespace Aero\Platform\Http\Middleware;

use Aero\Core\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BootstrapGuard Middleware (Platform/SaaS Mode)
 *
 * Global middleware that ensures ALL requests are redirected to /install
 * if the system is not installed. This middleware has route supremacy.
 *
 * Unlike the Core BootstrapGuard, this handles multi-domain scenarios:
 * - Redirects tenant subdomains to platform domain /install
 * - Checks database accessibility (tenants table must exist)
 *
 * Registered globally via AeroPlatformServiceProvider::register() to intercept
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
        if ($request->is('build/*', 'storage/*', 'assets/*', 'favicon.ico', 'robots.txt', 'up')) {
            return $next($request);
        }

        // Check if system is installed (file-based detection)
        if (! $this->installed()) {
            $host = $request->getHost();

            // If it's an AJAX/API request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System not installed. Please run the installation wizard.',
                    'redirect' => $this->getInstallUrl($request, $host),
                ], 503);
            }

            // Get the platform domain for redirect
            $platformHost = $this->getPlatformHost($host);

            // If we're already on the platform domain, just redirect to /install
            if ($host === $platformHost) {
                return redirect('/install');
            }

            // Otherwise redirect to the platform domain's /install
            return redirect()->away($request->getScheme().'://'.$platformHost.'/install');
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
     * Get the full installation URL.
     */
    protected function getInstallUrl(Request $request, string $host): string
    {
        $platformHost = $this->getPlatformHost($host);

        return $request->getScheme().'://'.$platformHost.'/install';
    }

    /**
     * Get the platform (root) domain by removing any subdomain.
     *
     * Examples:
     * - admin.aeos365.test → aeos365.test
     * - tenant1.aeos365.test → aeos365.test
     * - aeos365.test → aeos365.test
     */
    protected function getPlatformHost(string $host): string
    {
        // First check if platform domain is configured
        $platformDomain = config('app.platform_domain') ?? config('tenancy.central_domains.0');
        if ($platformDomain) {
            return $platformDomain;
        }

        // Extract base domain from subdomain pattern
        // For xxx.domain.tld, return domain.tld
        $parts = explode('.', $host);

        // If only 2 parts (domain.tld), it's already the platform domain
        if (count($parts) <= 2) {
            return $host;
        }

        // Remove the first subdomain part to get platform domain
        array_shift($parts);

        return implode('.', $parts);
    }
}
