<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\TenantForgetRequest;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantForgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * GDPR Right-to-be-Forgotten endpoint (Audit D7).
 *
 * Single-action invokable controller — all purge logic lives in TenantForgetService.
 * Returns a JSON response (no Inertia redirect) because this is a destructive,
 * API-only action. The UI caller is responsible for handling the response and
 * navigating away from the now-deleted tenant.
 */
class TenantForgetController extends Controller
{
    public function __invoke(
        TenantForgetRequest $request,
        Tenant $tenant,
        TenantForgetService $service,
    ): JsonResponse {
        $subdomain = $tenant->subdomain;
        $tenantId = (string) $tenant->getTenantKey();

        $service->forget(
            $tenant,
            $request->validated('reason'),
            $request->user()?->id,
        );

        return response()->json([
            'message' => "Tenant {$subdomain} permanently purged. This action is irreversible.",
            'tenant_id' => $tenantId,
            'subdomain' => $subdomain,
        ]);
    }
}
