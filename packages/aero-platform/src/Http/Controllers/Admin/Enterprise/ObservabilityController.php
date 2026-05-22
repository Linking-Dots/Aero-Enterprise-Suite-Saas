<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\StoreAlertRequest;
use Aero\Platform\Services\Enterprise\ObservabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObservabilityController extends Controller
{
    public function __construct(private readonly ObservabilityService $svc) {}

    public function apm(): Response
    {
        return Inertia::render('Platform/Admin/Observability/Index', [
            'apm' => $this->svc->getApm(),
            'alerts' => $this->svc->getAlerts(),
        ]);
    }

    public function traces(Request $request): JsonResponse
    {
        return response()->json($this->svc->searchTraces($request->all()));
    }

    public function metrics(): JsonResponse
    {
        return response()->json($this->svc->getMetrics());
    }

    public function logs(Request $request): JsonResponse
    {
        return response()->json($this->svc->aggregateLogs($request->all()));
    }

    public function alerts(): JsonResponse
    {
        return response()->json(['alerts' => $this->svc->getAlerts()]);
    }

    public function storeAlert(StoreAlertRequest $request): JsonResponse
    {
        $alert = $this->svc->manageAlert($request->validated());

        return response()->json(['message' => 'Alert created.', 'id' => $alert->id], 201);
    }
}
