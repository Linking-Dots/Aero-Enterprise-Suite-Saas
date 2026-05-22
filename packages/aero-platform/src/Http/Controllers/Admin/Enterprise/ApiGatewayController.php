<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\UpdateRateLimitRequest;
use Aero\Platform\Http\Requests\Enterprise\ConfigureQuotaRequest;
use Aero\Platform\Services\Enterprise\ApiGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApiGatewayController extends Controller
{
    public function __construct(private readonly ApiGatewayService $svc) {}

    public function rateLimits(): Response
    {
        return Inertia::render('Platform/Admin/ApiGateway/Index', [
            'rate_limits' => $this->svc->getRateLimits(),
            'quotas' => $this->svc->getApiQuotas(),
        ]);
    }

    public function updateRateLimit(UpdateRateLimitRequest $request, string $tenantId): JsonResponse
    {
        $override = $this->svc->updateRateLimit(
            $tenantId,
            $request->validated(),
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'Rate limit updated.', 'id' => $override->id]);
    }

    public function apiQuotas(): JsonResponse
    {
        return response()->json(['quotas' => $this->svc->getApiQuotas()]);
    }

    public function configureQuota(ConfigureQuotaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $override = $this->svc->configureQuota(
            $validated['tenant_id'],
            $validated['resource'],
            $validated['limit'],
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'API quota configured.', 'id' => $override->id]);
    }

    public function usageAnalytics(string $tenantId): JsonResponse
    {
        return response()->json($this->svc->usageAnalytics($tenantId));
    }
}
