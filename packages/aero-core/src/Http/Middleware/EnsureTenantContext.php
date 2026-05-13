<?php

declare(strict_types=1);

namespace Aero\Core\Http\Middleware;

use Aero\Core\Traits\ParsesHostDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Context Middleware
 *
 * Dual-architecture middleware that ensures valid context for routes:
 *
 * SaaS Mode (with aero-platform):
 * - Requires valid tenant context from InitializeTenancyIfNotCentral
 * - 404s if tenant() is not available (central domain access)
 *
 * Standalone Mode (without aero-platform):
 * - Acts as passthrough (no tenant context required)
 * - Allows all requests since there's no multi-tenancy
 *
 * This provides a safety net for tenant-only routes in SaaS mode
 * while remaining compatible with standalone deployments.
 */
class EnsureTenantContext
{
    use ParsesHostDomain;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Standalone Mode: No platform package = no tenancy = passthrough
        if (! $this->isPlatformActive()) {
            return $next($request);
        }

        // SaaS Mode: Require valid tenant context
        if (function_exists('tenant') && tenant()) {
            return $next($request);
        }

        // SaaS Mode without tenant context - 404
        // Either on central domain or tenant couldn't be resolved
        abort(404);
    }

    /**
     * Check if aero-platform package is active (SaaS mode).
     */
    protected function isPlatformActive(): bool
    {
        return class_exists('Aero\\Platform\\AeroPlatformServiceProvider');
    }
}
