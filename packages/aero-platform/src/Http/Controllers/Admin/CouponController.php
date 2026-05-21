<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\BulkGenerateCouponRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreCouponRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\UpdateCouponRequest;
use Aero\Platform\Models\Coupon;
use Aero\Platform\Models\CouponCampaign;
use Aero\Platform\Services\AdvancedBilling\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Coupons/Index', [
            'coupons' => $this->svc->list($request->only(['status', 'search', 'campaign_id'])),
            'filters' => $request->only(['status', 'search', 'campaign_id']),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['coupon' => $coupon], 201);
    }

    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon = $this->svc->update($coupon, $request->validated(), (int) $request->user('landlord')->id);

        return response()->json(['coupon' => $coupon]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon = $this->svc->archive($coupon, (int) $request->user('landlord')->id);

        return response()->json(['coupon' => $coupon]);
    }

    public function bulkGenerate(BulkGenerateCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $campaign = CouponCampaign::findOrFail((int) $validated['campaign_id']);

        $coupons = $this->svc->bulkGenerate(
            $campaign,
            (string) $validated['prefix'],
            (int) $validated['count'],
            (array) ($validated['options'] ?? []),
            (int) $request->user('landlord')->id
        );

        return response()->json(['coupons' => $coupons, 'count' => count($coupons)], 201);
    }
}
