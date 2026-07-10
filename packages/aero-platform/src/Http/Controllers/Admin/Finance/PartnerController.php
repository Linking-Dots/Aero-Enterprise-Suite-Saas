<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\CreatePartnerRequest;
use Aero\Platform\Http\Requests\Finance\UpdatePartnerRequest;
use Aero\Platform\Models\PartnerCommission;
use Aero\Platform\Models\ResellerPartner;
use Aero\Platform\Services\Finance\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __construct(private readonly PartnerService $service) {}

    /**
     * The Partners command centre — roster, commission ledger, payouts,
     * managed tenants and portal config in one console.
     */
    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Partners/P2/Partners', [
            'overview' => $this->service->overview(),
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

    /**
     * The console's partner drawer subsumed the old Show page.
     */
    public function show(int $id): RedirectResponse
    {
        return redirect('/partners');
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

    public function approveCommission(Request $request, int $commissionId): JsonResponse
    {
        $commission = PartnerCommission::findOrFail($commissionId);

        if ($commission->status !== PartnerCommission::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending commissions can be approved.'], 422);
        }

        $commission = $this->service->approveCommission($commission, (int) $request->user()->id);

        return response()->json(['commission' => $commission]);
    }

    public function payCommission(Request $request, int $commissionId): JsonResponse
    {
        $commission = PartnerCommission::findOrFail($commissionId);

        if ($commission->status === PartnerCommission::STATUS_PAID) {
            return response()->json(['message' => 'This commission is already paid.'], 422);
        }

        $commission = $this->service->payCommission($commission, (int) $request->user()->id);

        return response()->json(['commission' => $commission]);
    }

    public function tenants(int $id): JsonResponse
    {
        $partner = ResellerPartner::findOrFail($id);

        return response()->json(['tenants' => $this->service->listPartnerTenants($partner)]);
    }

    public function reassign(Request $request, string $tenantId): JsonResponse
    {
        $request->validate(['partner_id' => ['nullable', 'integer', 'exists:reseller_partners,id']]);

        $tenant = $this->service->reassignTenant(
            $tenantId,
            $request->filled('partner_id') ? (int) $request->input('partner_id') : null,
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
