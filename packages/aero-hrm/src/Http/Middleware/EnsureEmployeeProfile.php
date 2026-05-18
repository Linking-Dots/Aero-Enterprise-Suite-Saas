<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmployeeProfile
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()?->employee) {
            return redirect()->route('hrm.self-service.no-profile');
        }

        return $next($request);
    }
}
