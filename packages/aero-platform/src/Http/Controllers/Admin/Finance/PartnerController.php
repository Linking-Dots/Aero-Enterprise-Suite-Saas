<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\CreatePartnerRequest;
use Aero\Platform\Http\Requests\Finance\UpdatePartnerRequest;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Services\Finance\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __construct(private readonly PartnerService $service) {}

    public function index(Request $request): Response
    {
        $partners = $this->service->list($request->only(['status', 'search']));

        return Inertia::render('Platform/Admin/Partners/Index', [
            'partners' => $partners,
        ]);
    }

    public function store(CreatePartnerRequest $request): JsonResponse
    {
        $partner = $this->service->create(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['partner' => $partner], 201);
    }

    public function update(UpdatePartnerRequest $request, int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $partner = $this->service->update($partner, $request->validated(), (int) $request->user()->id);

        return response()->json(['partner' => $partner]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $partner = $this->service->approve($partner, (int) $request->user()->id);

        return response()->json(['partner' => $partner]);
    }

    public function suspend(Request $request, int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $partner = $this->service->suspend($partner, (int) $request->user()->id);

        return response()->json(['partner' => $partner]);
    }

    public function show(int $id): Response
    {
        $partner = ResellerPartner::with('commissions')->findOrFail($id);
        $commissions = $this->service->listCommissions($partner);

        return Inertia::render('Platform/Admin/Partners/Show', [
            'partner' => $partner,
            'commissions' => $commissions,
        ]);
    }

    public function commissions(Request $request, int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $commissions = $this->service->listCommissions($partner, $request->only(['status']));

        return response()->json(['commissions' => $commissions]);
    }

    public function payout(Request $request, int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $count = $this->service->processCommissionPayout($partner, (int) $request->user()->id);

        return response()->json(['paid_count' => $count]);
    }

    public function tenants(int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);
        $tenants = $this->service->listPartnerTenants($partner);

        return response()->json(['tenants' => $tenants]);
    }

    public function reassign(Request $request, string $tenantId): JsonResponse
    {
        $request->validate(['partner_id' => ['required', 'integer', 'exists:reseller_partners,id']]);

        $tenant = $this->service->reassignTenant(
            $tenantId,
            (int) $request->input('partner_id'),
            (int) $request->user()->id
        );

        return response()->json(['tenant' => $tenant]);
    }

    public function updatePortal(Request $request, int $id): JsonResponse
    {
        $request->validate(['config' => ['required', 'array']]);

        $partner = ResellerPartner::findOrFail($id);
        $partner = $this->service->updatePortalConfig($partner, $request->input('config'), (int) $request->user()->id);

        return response()->json(['partner' => $partner]);
    }
}
