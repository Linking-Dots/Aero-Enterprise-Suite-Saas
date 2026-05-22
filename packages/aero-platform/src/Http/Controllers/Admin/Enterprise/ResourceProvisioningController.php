<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\ConfigureAutoScalingRequest;
use Aero\Platform\Http\Requests\Enterprise\CreateDbPoolRequest;
use Aero\Platform\Http\Requests\Enterprise\CreateStorageBackendRequest;
use Aero\Platform\Services\Enterprise\ResourceProvisioningService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResourceProvisioningController extends Controller
{
    public function __construct(private readonly ResourceProvisioningService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/ResourceProvisioning/Index', [
            'db_pools' => $this->svc->listDbPools(),
        ]);
    }

    public function dbPools(): JsonResponse
    {
        return response()->json(['pools' => $this->svc->listDbPools()]);
    }

    public function storeDbPool(CreateDbPoolRequest $request): JsonResponse
    {
        $pool = $this->svc->manageDbPools($request->validated());

        return response()->json(['message' => 'DB pool created.', 'id' => $pool->id], 201);
    }

    public function storeStorage(CreateStorageBackendRequest $request): JsonResponse
    {
        $backend = $this->svc->manageStorage($request->validated());

        return response()->json(['message' => 'Storage backend created.', 'id' => $backend->id], 201);
    }

    public function configureAutoScaling(ConfigureAutoScalingRequest $request): JsonResponse
    {
        $rule = $this->svc->configureAutoScaling($request->validated());

        return response()->json(['message' => 'Auto-scaling rule configured.', 'id' => $rule->id]);
    }
}
