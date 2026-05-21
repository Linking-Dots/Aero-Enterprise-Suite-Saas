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
 * P-7 — Maintenance Window Controller (Admin).
 *
 * Route prefix: /admin/p7/maintenance-windows
 * Route names:  platform.admin.tenant-comms.maintenance-windows.*
 */
class MaintenanceWindowController extends Controller
{
    public function __construct(private readonly TenantCommunicationService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Communications/Maintenance', [
            'windows' => $this->svc->listMaintenanceWindows($request->only(['status'])),
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'affected_tenants' => ['required', 'string', Rule::in(['all', 'specific'])],
            'affected_tenant_ids' => ['nullable', 'array'],
            'affected_tenant_ids.*' => ['string'],
        ]);

        $window = $this->svc->createMaintenanceWindow($data);

        return response()->json(['message' => 'Maintenance window scheduled.', 'data' => $window], 201);
    }

    public function cancel(int $id): JsonResponse
    {
        $window = $this->svc->cancelMaintenanceWindow($id);

        return response()->json(['message' => 'Maintenance window cancelled.', 'data' => $window]);
    }
}
