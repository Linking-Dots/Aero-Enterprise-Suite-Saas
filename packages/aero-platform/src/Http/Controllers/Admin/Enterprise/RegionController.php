<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\AssignTenantRegionRequest;
use Aero\Platform\Http\Requests\Enterprise\ConfigureCdnRequest;
use Aero\Platform\Models\Enterprise\Region;
use Aero\Platform\Services\Enterprise\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    public function __construct(private readonly RegionService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Regions/Index', [
            'regions' => $this->svc->list(),
        ]);
    }

    public function enable(Region $region): JsonResponse
    {
        $this->svc->enable($region);

        return response()->json(['message' => "Region {$region->code} enabled."]);
    }

    public function disable(Region $region): JsonResponse
    {
        $this->svc->disable($region);

        return response()->json(['message' => "Region {$region->code} disabled."]);
    }

    public function assign(AssignTenantRegionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->svc->assignTenant(
            $validated['tenant_id'],
            $validated['region_id'],
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'Tenant assigned to region.']);
    }

    public function configureCdn(ConfigureCdnRequest $request, Region $region): JsonResponse
    {
        $this->svc->configureCdn($region, $request->validated());

        return response()->json(['message' => 'CDN configuration updated.']);
    }
}
