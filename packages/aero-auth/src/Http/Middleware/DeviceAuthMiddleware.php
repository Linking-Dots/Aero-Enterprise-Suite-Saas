<?php

namespace Aero\Auth\Http\Middleware;

use Aero\Auth\Services\DeviceAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuthMiddleware
{
    protected DeviceAuthService $deviceAuthService;

    public function __construct(DeviceAuthService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Handle an incoming request.
     * Verifies that authenticated users are using their registered device.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for login/register routes
        if ($request->routeIs('login') || $request->routeIs('register')) {
            return $next($request);
        }

        // Only check for authenticated users
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip device verification if single device login is disabled for this user
        if (! $user->hasSingleDeviceLoginEnabled()) {
            return $next($request);
        }

        // Get device_id from header or input
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        // If no device_id is provided, log warning but allow (for backward compatibility during migration)
        if (! $deviceId) {
            Log::warning('No device_id provided in authenticated request', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            // In strict mode, you would return an error here:
            // return response()->json(['error' => 'Device verification required'], 403);

            return $next($request);
        }

        // Verify device
        $isValid = $this->deviceAuthService->verifyDeviceOnRequest($user, $request);

        if (! $isValid) {
            Log::warning('Invalid device verification', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'route' => $request->path(),
            ]);

            // Log out the user
            Auth::logout();

            // Return error response
            return response()->json([
                'error' => 'Device verification failed. Please login again.',
                'reason' => 'invalid_device',
            ], 403);
        }

        return $next($request);
    }
}
