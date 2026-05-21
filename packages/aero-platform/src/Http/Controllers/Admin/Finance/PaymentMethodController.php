<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Finance\PaymentMethodAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentMethodAdminService $service) {}

    public function index(Request $request, string $tenantId): Response
    {
        $methods = $this->service->listForTenant($tenantId);

        return Inertia::render('Platform/Admin/PaymentMethods/Index', [
            'tenant_id' => $tenantId,
            'methods' => $methods,
        ]);
    }

    public function add(Request $request, string $tenantId): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:card,ach,sepa'],
            'token' => ['required', 'string', 'max:255'],
        ]);

        $method = $this->service->add($tenantId, $request->validated(), (int) $request->user()->id);

        return response()->json(['method' => $method], 201);
    }

    public function update(Request $request, string $tenantId, string $id): JsonResponse
    {
        // Placeholder — real update delegates to gateway.
        return response()->json(['message' => 'Method updated.']);
    }

    public function remove(Request $request, string $tenantId, string $id): JsonResponse
    {
        $this->service->remove($tenantId, $id, (int) $request->user()->id);

        return response()->json(['message' => 'Method removed.']);
    }

    public function setDefault(Request $request, string $tenantId, string $id): JsonResponse
    {
        $this->service->setDefault($tenantId, $id, (int) $request->user()->id);

        return response()->json(['message' => 'Default payment method updated.']);
    }

    public function threeDsConfig(): Response
    {
        $config = $this->service->get3dsConfig();

        return Inertia::render('Platform/Admin/PaymentMethods/ThreeDsConfig', [
            'config' => $config,
        ]);
    }

    public function updateThreeDsConfig(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
            'mode' => ['required', 'string', 'in:automatic,always,never'],
            'exemption_low' => ['sometimes', 'boolean'],
        ]);

        $config = $this->service->update3dsConfig(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['config' => $config]);
    }
}
