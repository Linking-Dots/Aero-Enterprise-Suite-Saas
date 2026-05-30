<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Aero\Platform\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Is Active Middleware
 *
 * Prevents access to archived or suspended tenants.
 * Must run AFTER tenancy initialization middleware.
 *
 * This middleware enforces the "lock access" requirement from the
 * tenant lifecycle compliance specification.
 */
class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            // No tenant context - let InitializeTenancy handle this
            return $next($request);
        }

        // Check if tenant is archived (soft deleted)
        if ($tenant->trashed()) {
            abort(410, 'This organization has been archived and is no longer accessible.');
        }

        // Check tenant status
        if ($tenant->status === Tenant::STATUS_ARCHIVED) {
            abort(410, 'This organization has been archived and is no longer accessible.');
        }

        if ($tenant->status === Tenant::STATUS_SUSPENDED) {
            $reason = $tenant->data['suspended_reason'] ?? 'Your account has been suspended';

            // API requests (Axis A A8) get JSON, not the HTML suspended page.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $reason,
                    'suspended_at' => $tenant->data['suspended_at'] ?? now()->toIso8601String(),
                ], 403);
            }

            return response()->view('errors.tenant-suspended', [
                'message' => $reason,
                'suspended_at' => $tenant->data['suspended_at'] ?? now()->toIso8601String(),
            ], 403);
        }

        if ($tenant->status === Tenant::STATUS_FAILED) {
            abort(503, 'Tenant provisioning failed. Please contact support.');
        }

        // Allow provisioning status to proceed (for admin setup)
        if (! $tenant->isActive() && $tenant->status !== Tenant::STATUS_PROVISIONING) {
            abort(503, 'This organization is currently unavailable.');
        }

        return $next($request);
    }
}
