<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Aero\Contracts\DomainContextContract;
use Aero\Core\Services\InstallationState;
use Aero\Core\Traits\ParsesHostDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class IdentifyDomainContext
{
    use ParsesHostDomain;

    /**
     * Domain context constants - aliased from Core's DomainContextContract for backward compatibility.
     */
    public const CONTEXT_ADMIN = DomainContextContract::CONTEXT_ADMIN;

    public const CONTEXT_PLATFORM = DomainContextContract::CONTEXT_PLATFORM;

    public const CONTEXT_TENANT = DomainContextContract::CONTEXT_TENANT;

    public const CONTEXT_STANDALONE = DomainContextContract::CONTEXT_STANDALONE;

    /**
     * Handle an incoming request.
     *
     * Identifies whether the request is coming from:
     * - admin.platform.com (CONTEXT_ADMIN)
     * - platform.com (CONTEXT_PLATFORM)
     * - {tenant}.platform.com (CONTEXT_TENANT)
     *
     * Note: Database connection is set by SetDatabaseConnectionFromDomain
     * middleware which runs globally before sessions start.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Identify and SET the domain context on request attributes
        $context = $this->identifyContext($request);
        $request->attributes->set('domain_context', $context);

        // 1b. Set URL defaults for tenant subdomain so route() auto-fills {tenant}
        if ($context === self::CONTEXT_TENANT) {
            $parsed = $this->parseHost($request->getHost());
            if ($parsed['subdomain']) {
                URL::defaults(['tenant' => $parsed['subdomain']]);
            }
        }

        // 2. Check if application is installed (for platform domain root only)
        // NOTE: Landing page rendering is now handled by CMS package.
        // We only check for installation and redirect to installer if needed.
        if ($request->is('/') && $context === self::CONTEXT_PLATFORM) {
            if (! $this->isApplicationInstalled()) {
                return redirect('/install');
            }
            // Let the request continue to CMS catch-all route for landing page
        }

        // 3. Pass to next middleware - let routes handle all requests
        return $next($request);
    }

    /**
     * Check if the application is installed using file-based detection.
     */
    protected function isApplicationInstalled(): bool
    {
        // Check via InstallationState (file-based cache + DB schema fallback)
        if (! InstallationState::isInstalled()) {
            return false;
        }

        // Check Database connectivity and tenants table
        try {
            DB::connection()->getPdo();
            // Assuming 'tenants' table is in the default/landlord connection
            if (! Schema::hasTable('tenants')) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Identify the domain context based on the request host.
     *
     * Auto-detects domain type from URL structure:
     * - admin.domain.com → CONTEXT_ADMIN
     * - domain.com → CONTEXT_PLATFORM
     * - {anything-else}.domain.com → CONTEXT_TENANT
     *
     * No .env configuration required - derives from current request.
     */
    protected function identifyContext(Request $request): string
    {
        $host = $request->getHost();

        // Use trait's helper methods for domain detection
        if ($this->isHostAdminDomain($host)) {
            return self::CONTEXT_ADMIN;
        }

        if ($this->isHostPlatformDomain($host)) {
            return self::CONTEXT_PLATFORM;
        }

        return self::CONTEXT_TENANT;
    }

    /**
     * Get the platform/base domain from current request.
     * Useful for generating cross-domain URLs.
     */
    public static function getPlatformDomain(Request $request): string
    {
        $instance = new self;

        return $instance->getPlatformDomainFromHost($request->getHost());
    }

    /**
     * Get the admin domain from current request.
     */
    public static function getAdminDomain(Request $request): string
    {
        $instance = new self;

        return $instance->getAdminDomainFromHost($request->getHost());
    }

    /**
     * Get a tenant domain from current request.
     */
    public static function getTenantDomain(Request $request, string $tenantSlug): string
    {
        $instance = new self;

        return $instance->getTenantDomainFromHost($request->getHost(), $tenantSlug);
    }

    /**
     * Static helper to get current context from request.
     */
    public static function getContext(Request $request): string
    {
        return $request->attributes->get('domain_context', self::CONTEXT_PLATFORM);
    }

    /**
     * Check if current context is admin.
     */
    public static function isAdmin(Request $request): bool
    {
        return self::getContext($request) === self::CONTEXT_ADMIN;
    }

    /**
     * Check if current context is platform.
     */
    public static function isPlatform(Request $request): bool
    {
        return self::getContext($request) === self::CONTEXT_PLATFORM;
    }

    /**
     * Check if current context is tenant.
     */
    public static function isTenant(Request $request): bool
    {
        return self::getContext($request) === self::CONTEXT_TENANT;
    }
}
