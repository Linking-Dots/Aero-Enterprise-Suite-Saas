<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\ConfigureScimRequest;
use Aero\Platform\Models\Enterprise\ScimEndpoint;
use Aero\Platform\Services\Enterprise\EnterpriseScimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EnterpriseScimController extends Controller
{
    public function __construct(private readonly EnterpriseScimService $svc) {}

    public function endpoints(): Response
    {
        return Inertia::render('Platform/Admin/Enterprise/Scim', [
            'endpoints' => ScimEndpoint::orderByDesc('created_at')->get(['id', 'tenant_id', 'provider', 'is_active', 'token_rotated_at', 'created_at']),
        ]);
    }

    public function configureEndpoint(ConfigureScimRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $endpoint = $this->svc->configureEndpoint($validated['tenant_id'], $validated);

        return response()->json(['message' => 'SCIM endpoint configured.', 'id' => $endpoint->id], 201);
    }

    public function rotateToken(ScimEndpoint $endpoint): JsonResponse
    {
        $this->svc->rotateToken($endpoint, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'SCIM token rotated.']);
    }

    public function syncLogs(Request $request, string $tenantId): JsonResponse
    {
        $logs = $this->svc->listSyncLogs($tenantId);

        return response()->json(['logs' => $logs]);
    }
}
