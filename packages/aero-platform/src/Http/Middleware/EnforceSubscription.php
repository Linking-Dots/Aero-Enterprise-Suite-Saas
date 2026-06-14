<?php

namespace Aero\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce subscription payments on tenant applications.
 *
 * This middleware ensures tenants have an active subscription before
 * accessing protected tenant resources. It allows:
 * - Tenants on an active trial period
 * - Tenants with an active subscription
 * - Tenants in a grace period (after cancellation but before subscription ends)
 *
 * Billing routes are always exempt to allow subscription management.
 */
class EnforceSubscription
{
    /**
     * Routes that are exempt from subscription enforcement.
     * These allow tenants to manage their billing even without an active subscription.
     *
     * @var array<string>
     */
    protected array $exemptRoutes = [
        'tenant.billing.*',
        'core.subscription.*',
    ];

    /**
     * Route prefixes that are exempt from subscription enforcement.
     *
     * @var array<string>
     */
    protected array $exemptPrefixes = [
        'billing',
        'subscription',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce in tenant context
        if (! function_exists('tenant') || ! tenant()) {
            return $next($request);
        }

        $tenant = tenant();

        // Allow billing/subscription routes so tenants can manage their subscription
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Allow access if tenant is on trial
        if ($tenant->isOnTrial()) {
            return $next($request);
        }

        // Allow access if tenant has an active subscription
        if ($tenant->subscribed('default')) {
            return $next($request);
        }

        // Allow access if tenant is in grace period (cancelled but still active)
        if ($this->isOnGracePeriod($tenant)) {
            return $next($request);
        }

        // Subscription required - block access
        return $this->handleUnsubscribed($request, $tenant);
    }

    /**
     * Check if the current route is exempt from subscription enforcement.
     */
    protected function isExemptRoute(Request $request): bool
    {
        // Check named routes
        $routeName = $request->route()?->getName();
        if ($routeName) {
            foreach ($this->exemptRoutes as $pattern) {
                if ($this->routeMatches($routeName, $pattern)) {
                    return true;
                }
            }
        }

        // Check URL prefixes
        $path = trim($request->path(), '/');
        foreach ($this->exemptPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a route name matches a pattern (supports wildcards).
     */
    protected function routeMatches(string $routeName, string $pattern): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        // Handle wildcard patterns like 'tenant.billing.*'
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');

            return str_starts_with($routeName, $prefix);
        }

        return false;
    }

    /**
     * Check if tenant is in a grace period after cancellation.
     */
    protected function isOnGracePeriod(mixed $tenant): bool
    {
        $subscription = $tenant->subscription('default');

        if (! $subscription) {
            return false;
        }

        return $subscription->onGracePeriod();
    }

    /**
     * Handle unsubscribed tenant - redirect or return error response.
     */
    protected function handleUnsubscribed(Request $request, mixed $tenant): Response
    {
        $message = __('Your subscription has expired. Please subscribe to continue using the platform.');

        // API/JSON requests get a JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'subscription_required',
                'redirect' => route('tenant.billing.portal'),
            ], 402); // 402 Payment Required
        }

        // Web requests get redirected to billing portal
        return redirect()
            ->route('tenant.billing.portal')
            ->with('warning', $message);
    }
}
