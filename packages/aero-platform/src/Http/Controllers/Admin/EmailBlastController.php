<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\TenantCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-7 — Email Blast Controller (Admin).
 *
 * Route prefix: /admin/p7/email-blasts
 * Route names:  platform.admin.tenant-comms.email-blasts.*
 */
class EmailBlastController extends Controller
{
    public function __construct(private readonly TenantCommunicationService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Communications/EmailBlasts', [
            'blasts' => $this->svc->listEmailBlasts($request->only(['q'])),
            'filters' => $request->only(['q']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'target_filter' => ['nullable', 'array'],
            'target_filter.plan_ids' => ['nullable', 'array'],
            'target_filter.plan_ids.*' => ['integer'],
            'target_filter.product_ids' => ['nullable', 'array'],
            'target_filter.product_ids.*' => ['integer'],
            'target_filter.statuses' => ['nullable', 'array'],
            'target_filter.statuses.*' => ['string'],
        ]);

        $data['created_by'] = auth('landlord')->id();
        $blast = $this->svc->createEmailBlast($data);

        return response()->json(['message' => 'Email blast created.', 'data' => $blast], 201);
    }

    public function send(int $id): JsonResponse
    {
        $blast = $this->svc->sendBlast($id);

        return response()->json(['message' => 'Email blast queued for delivery.', 'data' => $blast]);
    }
}
