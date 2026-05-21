<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreRefundRequest;
use Aero\Platform\Models\Refund;
use Aero\Platform\Services\AdvancedBilling\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Refunds/Index', [
            'refunds' => $this->svc->list($request->only(['status', 'tenant_id', 'search'])),
            'filters' => $request->only(['status', 'tenant_id', 'search']),
        ]);
    }

    public function store(StoreRefundRequest $request): JsonResponse
    {
        $refund = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['refund' => $refund], 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $refund = Refund::findOrFail($id);
        $refund = $this->svc->approve($refund, (int) $request->user('landlord')->id);

        return response()->json(['refund' => $refund]);
    }

    public function process(Request $request, int $id): JsonResponse
    {
        $refund = Refund::findOrFail($id);
        $refund = $this->svc->process($refund, (int) $request->user('landlord')->id);

        return response()->json(['refund' => $refund]);
    }
}
