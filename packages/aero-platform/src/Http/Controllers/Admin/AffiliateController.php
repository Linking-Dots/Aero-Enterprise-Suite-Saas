<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\Affiliate;
use Aero\Platform\Models\AffiliatePayout;
use Aero\Platform\Models\AffiliateReferral;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Marketing\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Affiliate Controller
 *
 * Manages affiliate program from the platform admin.
 */
class AffiliateController extends Controller
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Display affiliates list.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_dir']);
        $perPage = $request->input('perPage', 20);

        $affiliates = $this->affiliateService->getPaginatedAffiliates($filters, $perPage);
        $stats = $this->affiliateService->getAffiliateStats($request->input('period', 'month'));
        $settings = PlatformSetting::current()->getAffiliateSettings();

        return Inertia::render('Platform/Admin/Affiliates/Index', [
            'title' => 'Affiliate Program',
            'affiliates' => $affiliates,
            'stats' => $stats,
            'filters' => $filters,
            'settings' => $settings,
            'statusOptions' => Affiliate::getStatusOptions(),
        ]);
    }

    /**
     * Get paginated affiliates (API).
     */
    public function paginate(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_dir']);
        $perPage = $request->input('perPage', 20);

        $affiliates = $this->affiliateService->getPaginatedAffiliates($filters, $perPage);

        return response()->json($affiliates);
    }

    /**
     * Show affiliate details.
     */
    public function show(Affiliate $affiliate): Response
    {
        $affiliate->load(['referrals' => function ($query) {
            $query->latest()->limit(20);
        }, 'payouts' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Platform/Admin/Affiliates/Show', [
            'title' => 'Affiliate Details',
            'affiliate' => $affiliate,
            'referralStats' => [
                'total' => $affiliate->referrals()->count(),
                'converted' => $affiliate->referrals()->where('status', AffiliateReferral::STATUS_CONVERTED)->count(),
                'pending' => $affiliate->referrals()->whereIn('status', [
                    AffiliateReferral::STATUS_CLICKED,
                    AffiliateReferral::STATUS_REGISTERED,
                ])->count(),
            ],
        ]);
    }

    /**
     * Store a new affiliate.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:affiliates,email',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|string|in:percentage,fixed',
            'auto_approve' => 'boolean',
        ]);

        $affiliate = $this->affiliateService->createAffiliate($validated);

        if ($validated['auto_approve'] ?? false) {
            $affiliate->approve();
        }

        return response()->json([
            'success' => true,
            'message' => 'Affiliate created successfully.',
            'data' => $affiliate,
        ], 201);
    }

    /**
     * Update an affiliate.
     */
    public function update(Request $request, Affiliate $affiliate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|string|in:percentage,fixed',
            'cookie_days' => 'nullable|integer|min:1|max:365',
            'minimum_payout' => 'nullable|numeric|min:0',
        ]);

        $affiliate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Affiliate updated successfully.',
            'data' => $affiliate->fresh(),
        ]);
    }

    /**
     * Delete an affiliate.
     */
    public function destroy(Affiliate $affiliate): JsonResponse
    {
        $affiliate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Affiliate deleted successfully.',
        ]);
    }

    /**
     * Approve affiliate application.
     */
    public function approve(Affiliate $affiliate): JsonResponse
    {
        if ($affiliate->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Affiliate is already approved.',
            ], 400);
        }

        $affiliate->approve();

        return response()->json([
            'success' => true,
            'message' => 'Affiliate approved successfully.',
            'data' => $affiliate->fresh(),
        ]);
    }

    /**
     * Reject affiliate application.
     */
    public function reject(Request $request, Affiliate $affiliate): JsonResponse
    {
        $reason = $request->input('reason');
        $affiliate->reject($reason);

        return response()->json([
            'success' => true,
            'message' => 'Affiliate rejected.',
            'data' => $affiliate->fresh(),
        ]);
    }

    /**
     * Suspend affiliate.
     */
    public function suspend(Request $request, Affiliate $affiliate): JsonResponse
    {
        $reason = $request->input('reason');
        $affiliate->suspend($reason);

        return response()->json([
            'success' => true,
            'message' => 'Affiliate suspended.',
            'data' => $affiliate->fresh(),
        ]);
    }

    /**
     * Get affiliate referrals.
     */
    public function referrals(Request $request, Affiliate $affiliate): JsonResponse
    {
        $filters = $request->only(['status', 'commission_status']);
        $perPage = $request->input('perPage', 20);

        $referrals = $this->affiliateService->getAffiliateReferrals($affiliate, $filters, $perPage);

        return response()->json($referrals);
    }

    /**
     * Get affiliate payouts.
     */
    public function payouts(Request $request, Affiliate $affiliate): JsonResponse
    {
        $perPage = $request->input('perPage', 20);
        $payouts = $this->affiliateService->getAffiliatePayouts($affiliate, $perPage);

        return response()->json($payouts);
    }

    /**
     * Display pending payouts.
     */
    public function pendingPayouts(): Response
    {
        $pendingPayouts = $this->affiliateService->getPendingPayouts();

        return Inertia::render('Platform/Admin/Affiliates/Payouts', [
            'title' => 'Pending Payouts',
            'affiliates' => $pendingPayouts,
        ]);
    }

    /**
     * Create a payout for affiliate.
     */
    public function createPayout(Request $request, Affiliate $affiliate): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        if (! $affiliate->isEligibleForPayout()) {
            return response()->json([
                'success' => false,
                'message' => 'Affiliate is not eligible for payout.',
            ], 400);
        }

        $payout = $this->affiliateService->createPayout($affiliate, $validated['amount'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Payout created successfully.',
            'data' => $payout,
        ], 201);
    }

    /**
     * Process a payout.
     */
    public function processPayout(AffiliatePayout $payout): JsonResponse
    {
        if ($payout->status !== AffiliatePayout::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Payout is not pending.',
            ], 400);
        }

        $this->affiliateService->processPayout($payout);

        return response()->json([
            'success' => true,
            'message' => 'Payout processing started.',
            'data' => $payout->fresh(),
        ]);
    }

    /**
     * Complete a payout.
     */
    public function completePayout(Request $request, AffiliatePayout $payout): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string|max:255',
        ]);

        $payout->markAsCompleted($validated['transaction_id']);

        return response()->json([
            'success' => true,
            'message' => 'Payout marked as completed.',
            'data' => $payout->fresh(),
        ]);
    }

    /**
     * Get affiliate statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $stats = $this->affiliateService->getAffiliateStats($period);
        $topAffiliates = $this->affiliateService->getTopAffiliates(5, $period);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'top_affiliates' => $topAffiliates,
        ]);
    }

    /**
     * Update affiliate program settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'default_commission_type' => 'nullable|string|in:percentage,fixed',
            'cookie_days' => 'nullable|integer|min:1|max:365',
            'minimum_payout' => 'nullable|numeric|min:0',
            'payout_methods' => 'nullable|array',
            'auto_approve_affiliates' => 'boolean',
            'terms_url' => 'nullable|url|max:500',
        ]);

        $settings = PlatformSetting::current();
        $affiliateSettings = array_merge($settings->affiliate_settings ?? [], $validated);
        $settings->update(['affiliate_settings' => $affiliateSettings]);

        return response()->json([
            'success' => true,
            'message' => 'Affiliate settings updated successfully.',
            'data' => $settings->fresh()->getAffiliateSettings(),
        ]);
    }
}
