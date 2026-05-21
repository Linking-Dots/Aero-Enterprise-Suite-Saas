<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreCampaignRequest;
use Aero\Platform\Models\CouponCampaign;
use Aero\Platform\Services\AdvancedBilling\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Coupons/Campaigns', [
            'campaigns' => $this->svc->list($request->only(['status', 'search'])),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['campaign' => $campaign], 201);
    }

    public function launch(Request $request, int $id): JsonResponse
    {
        $campaign = CouponCampaign::findOrFail($id);
        $campaign = $this->svc->launch($campaign, (int) $request->user('landlord')->id);

        return response()->json(['campaign' => $campaign]);
    }

    public function end(Request $request, int $id): JsonResponse
    {
        $campaign = CouponCampaign::findOrFail($id);
        $campaign = $this->svc->end($campaign, (int) $request->user('landlord')->id);

        return response()->json(['campaign' => $campaign]);
    }

    public function redemptions(Request $request, int $id): JsonResponse
    {
        $campaign = CouponCampaign::findOrFail($id);
        $redemptions = $this->svc->redemptions($campaign);

        return response()->json(['redemptions' => $redemptions]);
    }
}
