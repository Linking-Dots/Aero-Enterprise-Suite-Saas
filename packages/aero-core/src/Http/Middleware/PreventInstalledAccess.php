<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventInstalledAccess
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to installation routes if system is already installed.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalled()) {
            // If it's an AJAX/API request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System is already installed.',
                    'redirect' => route('login'),
                ], 403);
            }

            // Redirect to login page with message
            return redirect()->route('login')
                ->with('info', 'System is already installed.');
        }

        return $next($request);
    }

    /**
     * Check if the system is installed using file-based detection.
     *
     * This is the ONLY authoritative method for checking installation status.
     * Never use database queries for installation detection as they can fail
     * before the database is configured.
     */
    protected function isInstalled(): bool
    {
        return InstallationState::isInstalled();
    }
}
