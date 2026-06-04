<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B-36: on the bare platform (central, non-admin) domain there is no login — tenants
 * log in at {tenant}.<domain> and operators at admin.<domain>. The mode-agnostic
 * aero-auth tenant login route is registered WITHOUT a domain (correct for standalone
 * + tenant subdomains), so on the central marketing domain it shadows the intended
 * `login -> /signup` redirect (platform web.php). This global middleware runs before
 * routing and enforces that redirect for the bare platform host only.
 *
 * Scope is deliberately narrow: GET, path exactly "login", host exactly the platform
 * domain (NOT admin.<domain>, NOT a tenant subdomain). Only registered in SaaS (this
 * package isn't loaded in standalone), so standalone's bare-domain login is untouched.
 */
class RedirectCentralLoginToSignup
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && $request->path() === 'login'
            && $request->getHost() === $this->platformDomain()) {
            return redirect('/signup', 302);
        }

        return $next($request);
    }

    private function platformDomain(): string
    {
        return (string) env('PLATFORM_DOMAIN', env('APP_DOMAIN', config('app.domain', 'localhost')));
    }
}
