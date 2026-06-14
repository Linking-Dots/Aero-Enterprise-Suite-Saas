<?php

namespace Aero\Installation\Http\Middleware;

use Aero\Core\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BootstrapGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install*')) {
            if (file_exists(storage_path('app/aeos.installed'))) {
                abort(404);
            }

            if ($this->installed() && ! $request->is('install/cleanup*')) {
                abort(404);
            }

            return $next($request);
        }

        if ($request->is('build/*') ||
            $request->is('storage/*') ||
            $request->is('aero-core/health') ||
            $request->is('api/error-log') ||
            $request->is('api/version/check')) {
            return $next($request);
        }

        if (! $this->installed() && ! $this->hasPlatformPackage()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System not installed. Please run the installation wizard.',
                    'redirect' => '/install',
                ], 503);
            }

            return redirect('/install');
        }

        return $next($request);
    }

    protected function installed(): bool
    {
        return InstallationState::isInstalled();
    }

    protected function hasPlatformPackage(): bool
    {
        return class_exists('Aero\Platform\AeroPlatformServiceProvider');
    }
}
