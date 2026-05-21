<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform Webhook Endpoint Controller (Admin).
 *
 * Route prefix: /admin/integrations/webhooks
 * Route names:  platform.admin.integrations.webhooks.*
 */
class WebhookAdminController extends Controller
{
    public function __construct(private readonly WebhookService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Integrations/Webhooks', [
            'endpoints' => $this->svc->list($request->only(['q', 'active'])),
            'filters' => $request->only(['q', 'active']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'max:100'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $endpoint = $this->svc->create($data);

        return response()->json(['message' => 'Webhook endpoint created.', 'data' => $endpoint], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'url' => ['sometimes', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $endpoint = $this->svc->update($id, $data);

        return response()->json(['message' => 'Webhook endpoint updated.', 'data' => $endpoint]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->svc->delete($id);

        return response()->json(['message' => 'Webhook endpoint deleted.']);
    }

    public function test(int $id): JsonResponse
    {
        $log = $this->svc->test($id);

        return response()->json([
            'message' => 'Test webhook dispatched.',
            'response_status' => $log->response_status,
            'log_id' => $log->id,
        ]);
    }

    public function eventCatalog(): JsonResponse
    {
        return response()->json(['data' => $this->svc->eventCatalog()]);
    }

    public function deliveryLogs(Request $request, int $id): JsonResponse
    {
        $logs = $this->svc->deliveryLogs($id, $request->only(['event_type']));

        return response()->json($logs);
    }

    public function replay(int $logId): JsonResponse
    {
        $log = $this->svc->replay($logId);

        return response()->json([
            'message' => 'Log replayed.',
            'response_status' => $log->response_status,
            'log_id' => $log->id,
        ]);
    }

    public function rotateSecret(int $id): JsonResponse
    {
        $this->svc->rotateSecret($id);

        return response()->json(['message' => 'Signing secret rotated.']);
    }
}
