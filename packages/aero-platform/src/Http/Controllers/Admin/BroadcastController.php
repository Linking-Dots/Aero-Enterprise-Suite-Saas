<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\TenantCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-7 — Tenant Broadcast Controller (Admin).
 *
 * Route prefix: /admin/p7/broadcasts
 * Route names:  platform.admin.tenant-comms.broadcasts.*
 */
class BroadcastController extends Controller
{
    public function __construct(private readonly TenantCommunicationService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Communications/Broadcasts', [
            'broadcasts' => $this->svc->listBroadcasts($request->only(['q', 'published'])),
            'filters' => $request->only(['q', 'published']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'target' => ['required', 'string', Rule::in(['all', 'specific'])],
            'target_tenant_ids' => ['nullable', 'array'],
            'target_tenant_ids.*' => ['string'],
        ]);

        $data['created_by'] = auth('landlord')->id();
        $broadcast = $this->svc->createBroadcast($data);

        return response()->json(['message' => 'Broadcast created.', 'data' => $broadcast], 201);
    }

    public function publish(int $id): JsonResponse
    {
        $broadcast = $this->svc->publishBroadcast($id);

        return response()->json(['message' => 'Broadcast published.', 'data' => $broadcast]);
    }

    public function dismissAll(int $id): JsonResponse
    {
        $this->svc->dismissAll($id);

        return response()->json(['message' => 'Broadcast dismissed for all tenants.']);
    }
}
