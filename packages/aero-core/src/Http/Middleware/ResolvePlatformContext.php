<?php

namespace Aero\Core\Http\Middleware;

use Aero\Kernel\ValueObjects\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePlatformContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(RequestContext::class, new RequestContext('platform', 'landlord'));

        return $next($request);
    }
}
