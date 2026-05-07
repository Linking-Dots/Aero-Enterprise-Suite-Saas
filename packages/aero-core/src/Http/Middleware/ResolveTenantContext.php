<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\ValueObjects\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(RequestContext::class, new RequestContext('tenant', 'web'));

        return $next($request);
    }
}
