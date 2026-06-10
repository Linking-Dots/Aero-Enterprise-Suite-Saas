<?php

namespace Aero\Kernel\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Safe Redirect Helpers for Inertia.js Compliance
 *
 * Provides safe redirect methods that validate destinations and handle
 * multi-domain navigation safely.
 */
class SafeRedirect
{
    /**
     * Safely redirect to intended URL with validation and fallback.
     *
     * This method validates the intended URL to ensure:
     * 1. It exists in session
     * 2. It belongs to the same domain (prevents cross-domain issues)
     * 3. Falls back to a safe route if validation fails
     *
     * @param  string  $defaultRoute  Fallback route name
     * @param  bool  $validateDomain  Whether to validate domain matches
     * @return RedirectResponse
     *
     * @example
     * // In a login controller
     * use Aero\Core\Support\SafeRedirect;
     *
     * return SafeRedirect::intended('dashboard');
     */
    public static function intended(string $defaultRoute, bool $validateDomain = true)
    {
        $intended = session()->get('url.intended');

        // No intended URL - use default
        if (! $intended) {
            Log::debug('SafeRedirect::intended - No intended URL, using default', [
                'default' => $defaultRoute,
            ]);

            return self::toRoute($defaultRoute);
        }

        // Validate domain if requested
        if ($validateDomain && ! self::validateSameDomain($intended)) {
            Log::warning('SafeRedirect::intended - Cross-domain redirect blocked', [
                'intended' => $intended,
                'current_host' => request()->getHost(),
                'default' => $defaultRoute,
            ]);

            return self::toRoute($defaultRoute);
        }

        // Validate URL is well-formed
        if (! filter_var($intended, FILTER_VALIDATE_URL)) {
            Log::warning('SafeRedirect::intended - Invalid URL format', [
                'intended' => $intended,
                'default' => $defaultRoute,
            ]);

            return self::toRoute($defaultRoute);
        }

        Log::debug('SafeRedirect::intended - Redirecting to intended URL', [
            'intended' => $intended,
        ]);

        return redirect()->to($intended);
    }

    /**
     * Safely redirect to a named route with existence validation.
     *
     * @param  string  $routeName  Route name
     * @param  array  $parameters  Route parameters
     * @param  string  $fallbackRoute  Fallback route if primary doesn't exist
     * @return RedirectResponse
     *
     * @example
     * return SafeRedirect::toRoute('employees.index', [], 'dashboard');
     */
    public static function toRoute(string $routeName, array $parameters = [], string $fallbackRoute = 'core.dashboard')
    {
        // Check if route exists
        if (! Route::has($routeName)) {
            Log::warning('SafeRedirect::toRoute - Route not found', [
                'route' => $routeName,
                'fallback' => $fallbackRoute,
            ]);

            // Try fallback
            if ($routeName !== $fallbackRoute && Route::has($fallbackRoute)) {
                return redirect()->route($fallbackRoute);
            }

            // Last resort: redirect to '/'
            return redirect('/');
        }

        return redirect()->route($routeName, $parameters);
    }

    /**
     * Safely redirect back with fallback.
     *
     * Uses HTTP Referer but falls back to a safe route if:
     * 1. Referer is missing
     * 2. Referer is from different domain
     * 3. Referer is invalid
     *
     * @param  string  $fallbackRoute  Fallback route name
     * @param  bool  $validateDomain  Whether to validate referer domain
     * @return RedirectResponse
     *
     * @example
     * return SafeRedirect::back('dashboard');
     */
    public static function back(string $fallbackRoute = 'dashboard', bool $validateDomain = true)
    {
        $referer = request()->header('referer');

        // No referer - use fallback
        if (! $referer) {
            Log::debug('SafeRedirect::back - No referer, using fallback', [
                'fallback' => $fallbackRoute,
            ]);

            return self::toRoute($fallbackRoute);
        }

        // Validate domain if requested
        if ($validateDomain && ! self::validateSameDomain($referer)) {
            Log::warning('SafeRedirect::back - Cross-domain referer blocked', [
                'referer' => $referer,
                'current_host' => request()->getHost(),
                'fallback' => $fallbackRoute,
            ]);

            return self::toRoute($fallbackRoute);
        }

        return redirect()->back();
    }

    /**
     * Redirect with success message.
     *
     * @param  string  $routeName  Route name
     * @param  string  $message  Success message
     * @param  array  $parameters  Route parameters
     * @return RedirectResponse
     */
    public static function withSuccess(string $routeName, string $message, array $parameters = [])
    {
        return self::toRoute($routeName, $parameters)
            ->with('success', $message);
    }

    /**
     * Redirect with error message.
     *
     * @param  string  $routeName  Route name
     * @param  string  $message  Error message
     * @param  array  $parameters  Route parameters
     * @return RedirectResponse
     */
    public static function withError(string $routeName, string $message, array $parameters = [])
    {
        return self::toRoute($routeName, $parameters)
            ->with('error', $message);
    }

    /**
     * Redirect back with success message.
     *
     * @param  string  $message  Success message
     * @param  string  $fallback  Fallback route
     * @return RedirectResponse
     */
    public static function backWithSuccess(string $message, string $fallback = 'dashboard')
    {
        return self::back($fallback)->with('success', $message);
    }

    /**
     * Redirect back with error message.
     *
     * @param  string  $message  Error message
     * @param  string  $fallback  Fallback route
     * @return RedirectResponse
     */
    public static function backWithError(string $message, string $fallback = 'dashboard')
    {
        return self::back($fallback)->with('error', $message);
    }

    /**
     * Validate that a URL belongs to the same domain as current request.
     *
     * @param  string  $url  URL to validate
     */
    protected static function validateSameDomain(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);

            // No host in URL (relative URL) - consider it safe
            if (! isset($parsedUrl['host'])) {
                return true;
            }

            $urlHost = $parsedUrl['host'];
            $currentHost = request()->getHost();

            // Exact match
            if ($urlHost === $currentHost) {
                return true;
            }

            // Check if both are subdomains of same base domain
            $baseDomain = config('app.domain', config('app.url'));
            $baseDomainClean = parse_url($baseDomain, PHP_URL_HOST) ?? $baseDomain;

            $urlBelongsToBaseDomain = str_ends_with($urlHost, '.'.$baseDomainClean) || $urlHost === $baseDomainClean;
            $currentBelongsToBaseDomain = str_ends_with($currentHost, '.'.$baseDomainClean) || $currentHost === $baseDomainClean;

            // Both belong to same base domain
            if ($urlBelongsToBaseDomain && $currentBelongsToBaseDomain) {
                // Additional check: prevent admin <-> tenant crossing if needed
                $isAdminUrl = str_starts_with($urlHost, 'admin.');
                $isAdminCurrent = str_starts_with($currentHost, 'admin.');

                // Allow same context (both admin or both non-admin)
                return $isAdminUrl === $isAdminCurrent;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SafeRedirect domain validation failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a route exists and is accessible.
     *
     * @param  string  $routeName  Route name
     */
    public static function routeExists(string $routeName): bool
    {
        return Route::has($routeName);
    }

    /**
     * Determine whether a string is a safe internal redirect path.
     *
     * Plan 05 (aero-auth) Task 3 — open-redirect guard. Used by
     * ImpersonationController and other surfaces that accept a
     * redirect target from untrusted input.
     *
     * A path is considered safe ONLY when:
     *   - It's a non-empty string
     *   - It starts with a single forward slash (root-relative)
     *   - It does NOT start with `//` (protocol-relative — opens redirect to //evil.com)
     *   - It does NOT start with `/\` (encoded backslash trick on some browsers)
     *   - It does NOT contain `://` anywhere (defensive — would mean someone smuggled scheme in)
     *   - It does NOT contain `\` characters (Windows-style backslashes confuse some browsers)
     *   - It does NOT contain control characters (newline, tab in URL = response splitting)
     *
     * Callers should fall back to a safe default ('/dashboard', '/', etc.)
     * when this returns false.
     */
    public static function isSafePath(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        // Must be root-relative
        if ($path[0] !== '/') {
            return false;
        }

        // Protocol-relative attack: //evil.com → most browsers treat as evil.com
        if (str_starts_with($path, '//')) {
            return false;
        }

        // Encoded backslash trick
        if (str_starts_with($path, '/\\')) {
            return false;
        }

        // Full URL smuggled in
        if (str_contains($path, '://')) {
            return false;
        }

        // Windows backslashes
        if (str_contains($path, '\\')) {
            return false;
        }

        // Response splitting via control characters
        if (preg_match('/[\x00-\x1f\x7f]/', $path)) {
            return false;
        }

        return true;
    }
}
