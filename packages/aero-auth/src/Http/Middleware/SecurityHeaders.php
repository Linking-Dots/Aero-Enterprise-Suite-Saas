<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security response headers (shared auth capability for platform + core).
 *
 * The universally-safe headers (frame/content-type/referrer/permissions/HSTS) are
 * always applied — they do not affect SPA behaviour. The Content-Security-Policy is
 * OPT-IN via config('aero-auth.security_headers.csp') because a too-strict CSP can
 * break the Inertia/React frontend (eval, cross-origin fetch); enable it only after
 * browser-validating the policy against the deployed SPA.
 *
 * Wire in a host's bootstrap/app.php:
 *   ->withMiddleware(fn (Middleware $m) => $m->web(append: [SecurityHeaders::class]))
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Universally-safe headers (do not affect SPA behaviour) ──────────────
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS only over HTTPS.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // ── Content-Security-Policy — OPT-IN (validate against the SPA first) ────
        $csp = config('aero-auth.security_headers.csp');
        if (is_string($csp) && $csp !== '') {
            // Caller supplied an explicit, validated policy string.
            $response->headers->set('Content-Security-Policy', $csp);
        } elseif ($csp === true) {
            // Sane default that keeps 'unsafe-inline' (required by Inertia/React
            // hydration) and drops 'unsafe-eval'. connect-src is bounded to app.url.
            $appUrl = rtrim((string) config('app.url', ''), '/');
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' https://www.gstatic.com; ".
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
                "font-src 'self' https://fonts.gstatic.com; ".
                "img-src 'self' data: https:; ".
                "connect-src 'self' {$appUrl}; ".
                "frame-ancestors 'none';"
            );
        }

        return $response;
    }
}
