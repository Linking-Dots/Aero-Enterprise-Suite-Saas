<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Aero\Platform\Services\Quotas\QuotaEnforcementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Tenant Quotas Middleware
 *
 * Checks and enforces quota limits for specific operations.
 * Blocks requests that would exceed tenant quotas.
 *
 * Usage in routes:
 * ```php
 * // Check user quota before user creation
 * Route::post('users', [UserController::class, 'store'])
 *     ->middleware(EnforceTenantQuotas::class.':users');
 *
 * // Check storage quota before file upload
 * Route::post('files', [FileController::class, 'store'])
 *     ->middleware(EnforceTenantQuotas::class.':storage');
 *
 * // Check API quota for API routes
 * Route::middleware(EnforceTenantQuotas::class.':api')->group(...);
 * ```
 */
class EnforceTenantQuotas
{
    public function __construct(
        protected QuotaEnforcementService $quotaService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  string  $quotaType  The quota type to check (users, storage, api, etc.)
     */
    public function handle(Request $request, Closure $next, string $quotaType = 'api'): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            // No tenant context - skip quota check
            return $next($request);
        }

        // Check the appropriate quota based on type
        $allowed = match ($quotaType) {
            'api' => $this->checkApiQuota($tenant),
            'storage' => $this->checkStorageQuota($tenant, $request),
            default => $this->checkResourceQuota($tenant, $quotaType),
        };

        if (! $allowed) {
            return $this->buildQuotaExceededResponse($quotaType, $request);
        }

        // Track API calls
        if ($quotaType === 'api') {
            $this->quotaService->incrementApiCalls($tenant);
        }

        return $next($request);
    }

    /**
     * Check API call quota.
     */
    protected function checkApiQuota(mixed $tenant): bool
    {
        return $this->quotaService->canMakeApiCall($tenant);
    }

    /**
     * Check storage quota for file uploads.
     */
    protected function checkStorageQuota(mixed $tenant, Request $request): bool
    {
        // Get file size from request
        $fileSize = 0;

        if ($request->hasFile('file')) {
            $fileSize = $request->file('file')->getSize();
        } elseif ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileSize += $file->getSize();
            }
        } elseif ($request->hasFile('document')) {
            $fileSize = $request->file('document')->getSize();
        } elseif ($request->hasFile('attachment')) {
            $fileSize = $request->file('attachment')->getSize();
        }

        return $this->quotaService->canUseStorage($tenant, $fileSize);
    }

    /**
     * Check resource creation quota.
     */
    protected function checkResourceQuota(mixed $tenant, string $quotaType): bool
    {
        return $this->quotaService->canCreate($tenant, $quotaType);
    }

    /**
     * Build quota exceeded response.
     *
     * Returns an Inertia-compatible redirect with flash error for browser requests,
     * or a JSON 402 response for API/AJAX requests.
     */
    protected function buildQuotaExceededResponse(string $quotaType, Request $request): Response
    {
        $messages = [
            'users' => 'User quota exceeded. Please upgrade your plan to add more users.',
            'employees' => 'Employee quota exceeded. Please upgrade your plan to add more employees.',
            'projects' => 'Project quota exceeded. Please upgrade your plan to create more projects.',
            'customers' => 'Customer quota exceeded. Please upgrade your plan to add more customers.',
            'rfis' => 'RFI quota exceeded. Please upgrade your plan to create more RFIs.',
            'storage' => 'Storage quota exceeded. Please upgrade your plan or free up space.',
            'api' => 'Monthly API call quota exceeded. Your quota will reset at the start of next month.',
        ];

        $message = $messages[$quotaType] ?? 'Quota exceeded. Please upgrade your plan.';

        // Inertia requests — redirect back with a flash error the frontend can display
        if ($request->header('X-Inertia')) {
            return back()->withErrors(['quota' => $message]);
        }

        // AJAX/API requests — return JSON
        return response()->json([
            'error' => 'Quota Exceeded',
            'message' => $message,
            'quota_type' => $quotaType,
            'upgrade_url' => '/subscription/plans',
        ], 402); // 402 Payment Required
    }
}
