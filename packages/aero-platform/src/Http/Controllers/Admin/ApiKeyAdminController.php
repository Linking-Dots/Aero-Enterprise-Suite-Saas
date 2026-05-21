<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\PlatformApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform API Key Controller (Admin).
 *
 * Route prefix: /admin/integrations/api-keys
 * Route names:  platform.admin.integrations.api-keys.*
 */
class ApiKeyAdminController extends Controller
{
    public function __construct(private readonly PlatformApiKeyService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Integrations/ApiKeys', [
            'apiKeys' => $this->svc->list($request->only(['q', 'revoked', 'active'])),
            'filters' => $request->only(['q', 'revoked', 'active']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $data['created_by'] = auth('landlord')->id();

        $result = $this->svc->create($data);

        return response()->json([
            'message' => 'API key created. Store the key now — it will not be shown again.',
            'key' => $result['raw_key'],
            'data' => $result['model'],
        ], 201);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $this->svc->revoke($id);

        return response()->json(['message' => 'API key revoked.']);
    }
}
