<?php

namespace Aero\Core\Http\Middleware;

use Aero\Contracts\LicenseServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceLicense
{
    private const EXEMPT_PREFIXES = [
        'install',
        'api/version/check',
        'api/error-log',
        'aero-core/health',
        'license',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // SaaS mode: never enforce here — platform handles billing
        if (is_saas_mode()) {
            return $next($request);
        }

        // Development bypass
        if (config('license.bypass', false) || app()->environment('testing')) {
            return $next($request);
        }

        // Exempt installation and health routes
        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if ($request->is($prefix) || $request->is($prefix.'/*')) {
                return $next($request);
            }
        }

        /** @var LicenseServiceInterface $license */
        $license = app(LicenseServiceInterface::class);
        $status  = $license->status();

        if ($status === 'valid' || $status === 'not_activated') {
            return $next($request);
        }

        if ($status === 'grace') {
            $hours = (int) ceil($license->graceSecondsRemaining() / 3600);
            session()->flash('license_warning',
                "License verification pending. {$hours} hour(s) remaining in grace period."
            );

            return $next($request);
        }

        // 'invalid', 'expired' — block access
        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'License/LicenseRequired',
                'props'     => [
                    'status'  => $status,
                    'traceId' => Str::uuid()->toString(),
                ],
                'url'     => $request->url(),
                'version' => '',
            ], 402, ['X-Inertia' => 'true']);
        }

        return response()->view('aero-core::license.required', ['status' => $status], 402);
    }
}
