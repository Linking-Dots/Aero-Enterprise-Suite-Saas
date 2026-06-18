<?php

namespace Aero\Core\Http\Controllers;

use Aero\Core\Services\Dashboard\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController — Tenant dashboard aggregator.
 *
 * Each Inertia prop uses a lazy closure so PHP only evaluates the data
 * that the frontend actually needs. All heavy methods are already cached
 * inside AdminDashboardService (2–15 min TTLs), so re-calling them
 * across requests is cheap.
 */
class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboardService) {}

    public function index(): Response
    {
        return Inertia::render('Tenant/Dashboard', [

            // ── Core stats ────────────────────────────────────────────────
            'coreStats'          => Inertia::lazy(fn () => $this->dashboardService->getCoreStats()),

            // ── Welcome greeting ─────────────────────────────────────────
            'welcomeData'        => $this->dashboardService->getWelcomeData(),

            // ── Onboarding checklist (null = hide widget) ─────────────────
            'onboardingProgress' => Inertia::lazy(fn () => $this->dashboardService->getOnboardingProgress()),

            // ── Announcements ─────────────────────────────────────────────
            'announcements'      => Inertia::lazy(fn () => $this->dashboardService->getAnnouncements()),

            // ── User activity chart data ──────────────────────────────────
            'userActivity'       => Inertia::lazy(fn () => $this->dashboardService->getUserActivity('week')),

            // ── Active sessions snapshot ──────────────────────────────────
            'sessionsData'       => Inertia::lazy(fn () => $this->dashboardService->getActiveSessionsData()),

            // ── Security overview ─────────────────────────────────────────
            'securityOverview'   => Inertia::lazy(fn () => $this->dashboardService->getSecurityOverview()),

            // ── Storage usage ─────────────────────────────────────────────
            'storageAnalytics'   => Inertia::lazy(fn () => $this->dashboardService->getStorageAnalytics()),

            // ── Subscription / plan info ──────────────────────────────────
            'subscriptionInfo'   => Inertia::lazy(fn () => $this->dashboardService->getSubscriptionInfo()),

            // ── Audit log (recent 15 entries) ─────────────────────────────
            'recentAuditLog'     => Inertia::lazy(fn () => $this->dashboardService->getRecentAuditLog(15)),

            // ── Permission-gated quick actions ────────────────────────────
            'quickActions'       => $this->dashboardService->getQuickActions(),

            // ── System health ─────────────────────────────────────────────
            'systemHealth'       => Inertia::lazy(fn () => $this->dashboardService->getSystemHealth()),
        ]);
    }

    /**
     * JSON endpoint — refresh a single widget via fetch() on the frontend.
     * Route: GET /dashboard/widget/{widgetKey}
     */
    public function widgetData(): \Illuminate\Http\JsonResponse
    {
        // Read the named route param explicitly. Tenant routes carry a leading
        // {tenant} parameter, which Laravel would otherwise bind positionally to
        // a typed method argument (so `string $widgetKey` received the subdomain).
        $widgetKey = (string) request()->route('widgetKey');

        // Map of known widget keys -> resolvers. Kept separate from the result so
        // a known widget whose data is legitimately null (e.g. onboardingProgress
        // = "hide", or an empty fresh tenant) is NOT mistaken for an unknown key.
        $resolvers = [
            'coreStats'          => fn () => $this->dashboardService->getCoreStats(),
            'userActivity'       => fn () => $this->dashboardService->getUserActivity(request('period', 'week')),
            'sessionsData'       => fn () => $this->dashboardService->getActiveSessionsData(),
            'securityOverview'   => fn () => $this->dashboardService->getSecurityOverview(),
            'storageAnalytics'   => fn () => $this->dashboardService->getStorageAnalytics(),
            'subscriptionInfo'   => fn () => $this->dashboardService->getSubscriptionInfo(),
            'recentAuditLog'     => fn () => $this->dashboardService->getRecentAuditLog(15),
            'systemHealth'       => fn () => $this->dashboardService->getSystemHealth(),
            'announcements'      => fn () => $this->dashboardService->getAnnouncements(),
            'onboardingProgress' => fn () => $this->dashboardService->getOnboardingProgress(),
        ];

        if (! isset($resolvers[$widgetKey])) {
            return response()->json(['error' => 'Unknown widget key'], 404);
        }

        return response()->json(['data' => $resolvers[$widgetKey]()]);
    }

    /**
     * JSON endpoint — refresh user activity for a different period.
     * Route: GET /dashboard/user-activity?period=month
     */
    public function userActivity(): \Illuminate\Http\JsonResponse
    {
        $period = in_array(request('period'), ['week', 'month', 'quarter'])
            ? request('period')
            : 'week';

        return response()->json([
            'data' => $this->dashboardService->getUserActivity($period),
        ]);
    }

    /**
     * POST /dashboard/announcements/{announcement}/dismiss
     */
    public function dismissAnnouncement(\Aero\Core\Models\Announcement $announcement): \Illuminate\Http\JsonResponse
    {
        try {
            $announcement->dismissals()->firstOrCreate(['user_id' => auth()->id()]);
        } catch (\Throwable) {
            // dismissals relationship may not exist in all versions
        }

        return response()->json(['dismissed' => true]);
    }
}
