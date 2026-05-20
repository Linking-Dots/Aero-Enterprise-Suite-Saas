<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\BulkOperation;
use Aero\Platform\Services\BulkTenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BulkTenantController extends Controller
{
    public function __construct(private BulkTenantService $svc) {}

    public function execute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:suspend,plan-change,email',
            'tenant_ids' => 'required|array|min:1',
            'tenant_ids.*' => 'string|exists:tenants,id',
            'reason' => 'required_if:type,suspend|string|max:255',
            'plan_id' => 'required_if:type,plan-change|integer|exists:plans,id',
            'subject' => 'required_if:type,email|string|max:255',
            'body' => 'required_if:type,email|string',
        ]);

        $tenantIds = $data['tenant_ids'];
        $payload = array_filter([
            'reason' => $data['reason'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
        ], fn ($v) => $v !== null);

        $op = $this->svc->execute(
            $data['type'],
            $tenantIds,
            $payload,
            $request->user()->id,
        );

        return back()->with('success', "Bulk operation queued (#{$op->id})");
    }

    public function history(): Response
    {
        return Inertia::render('Platform/Admin/Tenants/Bulk', [
            'operations' => BulkOperation::orderByDesc('created_at')->paginate(25),
        ]);
    }
}
