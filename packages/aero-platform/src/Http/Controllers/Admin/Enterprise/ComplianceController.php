<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\FulfillDsarRequest;
use Aero\Platform\Http\Requests\Enterprise\PublishTosRequest;
use Aero\Platform\Http\Requests\Enterprise\RecordDpaSigningRequest;
use Aero\Platform\Models\Enterprise\DsarRequest;
use Aero\Platform\Models\Enterprise\TosVersion;
use Aero\Platform\Services\Enterprise\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __construct(private readonly ComplianceService $svc) {}

    public function index(): Response
    {
        $dpa = $this->svc->getDpa();
        $dsars = $this->svc->listDsars();

        return Inertia::render('Platform/Admin/Compliance/Index', [
            'active_dpa' => $dpa,
            'dsars' => $dsars,
        ]);
    }

    public function recordDpaSigning(RecordDpaSigningRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $signing = $this->svc->recordSigning(
            $validated['tenant_id'],
            $validated['template_id'],
            $validated
        );

        return response()->json(['message' => 'DPA signing recorded.', 'id' => $signing->id], 201);
    }

    public function publishTos(PublishTosRequest $request): JsonResponse
    {
        $tos = $this->svc->publishTosVersion($request->validated());

        return response()->json(['message' => 'ToS version published.', 'id' => $tos->id], 201);
    }

    public function requireTosAcceptance(TosVersion $tos): JsonResponse
    {
        $this->svc->requireReAcceptance($tos);

        return response()->json(['message' => 'Re-acceptance required.']);
    }

    public function fulfillDsar(FulfillDsarRequest $request, DsarRequest $dsar): JsonResponse
    {
        $validated = $request->validated();
        $this->svc->fulfillDsar(
            $dsar,
            (string) Auth::guard('landlord')->id(),
            $validated['notes'] ?? ''
        );

        return response()->json(['message' => 'DSAR fulfilled.']);
    }
}
