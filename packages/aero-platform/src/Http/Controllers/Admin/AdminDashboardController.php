<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\PlatformWidgetRegistry;
use Aero\Platform\Widgets\BillingOverviewWidget;
use Aero\Platform\Widgets\ModuleUsageWidget;
use Aero\Platform\Widgets\QuickActionsWidget;
use Aero\Platform\Widgets\RecentActivityWidget;
use Aero\Platform\Widgets\SubscriptionDistributionWidget;
use Aero\Platform\Widgets\SystemAlertsWidget;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin Dashboard Controller
 *
 * Main dashboard for the platform administration panel.
 * Aggregates all platform widgets and exposes them as named Inertia props.
 * Individual widgets support lazy/deferred loading via Inertia::lazy().
 */
class AdminDashboardController extends Controller
{
    public function __construct(
        protected PlatformWidgetRegistry      $widgetRegistry,
        protected BillingOverviewWidget       $billingWidget,
        protected ModuleUsageWidget           $moduleWidget,
        protected QuickActionsWidget          $quickActionsWidget,
        protected RecentActivityWidget        $activityWidget,
        protected SubscriptionDistributionWidget $distWidget,
        protected SystemAlertsWidget          $alertsWidget,
    ) {}

    // ── Main render ───────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Dashboard/Index', [

            /* Command bar — always eager (tiny payload) */
            'welcome' => $this->buildWelcome(),

            /* KPI strip — eager, cached 5 min */
            'stats' => $this->calculatePlatformStats(),

            /* Lazy props — resolved only when visited or partial-reloaded */
            'billingOverview'          => Inertia::lazy(fn () => $this->billingWidget->getData()),
            'systemAlerts'             => Inertia::lazy(fn () => $this->buildSystemAlerts()),
            'systemHealth'             => Inertia::lazy(fn () => $this->buildSystemHealth()),
            'recentTenants'            => Inertia::lazy(fn () => $this->buildRecentTenants()),
            'moduleUsage'              => Inertia::lazy(fn () => $this->moduleWidget->getData()),
            'subscriptionDistribution' => Inertia::lazy(fn () => $this->distWidget->getData()),
            'recentActivity'           => Inertia::lazy(fn () => $this->activityWidget->getData()),
            'quickActions'             => Inertia::lazy(fn () => $this->quickActionsWidget->getData()),
        ]);
    }

    // ── Partial-reload endpoints ──────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->calculatePlatformStats());
    }

    public function widgetData(Request $request, string $widgetKey): JsonResponse
    {
        // Try the new named widgets first
        $data = match ($widgetKey) {
            'stats'                    => $this->calculatePlatformStats(),
            'billingOverview'          => $this->billingWidget->getData(),
            'systemAlerts'             => $this->buildSystemAlerts(),
            'systemHealth'             => $this->buildSystemHealth(),
            'recentTenants'            => $this->buildRecentTenants(),
            'moduleUsage'              => $this->moduleWidget->getData(),
            'subscriptionDistribution' => $this->distWidget->getData(),
            'recentActivity'           => $this->activityWidget->getData(),
            'quickActions'             => $this->quickActionsWidget->getData(),
            default                    => null,
        };

        // Fallback: check the generic widget registry (legacy compatibility)
        if ($data === null) {
            foreach ($this->widgetRegistry->getWidgets() as $widget) {
                if ($widget->getKey() === $widgetKey) {
                    return response()->json(['key' => $widget->getKey(), 'data' => $widget->getData()]);
                }
            }
            return response()->json(['error' => 'Widget not found'], 404);
        }

        return response()->json($data);
    }

    public function refresh(Request $request): JsonResponse
    {
        Cache::forget('platform.dashboard.stats');
        Cache::forget('platform.dashboard.alerts');
        Cache::forget('platform.dashboard.subscription_distribution');
        Cache::forget('platform.dashboard.recent_tenants');
        Cache::forget('platform.dashboard.system_health');
        Cache::forget('platform:dashboard:stats');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache refreshed',
            'stats'   => $this->calculatePlatformStats(),
        ]);
    }

    // ── Private builders ─────────────────────────────────────────────────────

    private function buildWelcome(): array
    {
        $user = Auth::guard('landlord')->user();
        $hour = (int) now()->format('H');

        return [
            'greeting' => match (true) {
                $hour < 12 => 'Good morning',
                $hour < 17 => 'Good afternoon',
                default    => 'Good evening',
            },
            'userName' => $user?->name ?? 'Admin',
            'date'     => Carbon::now()->translatedFormat('l, F j Y'),
        ];
    }

    private function buildSystemAlerts(): array
    {
        $raw        = $this->alertsWidget->getData();
        $alerts     = $raw['alerts'] ?? (is_array($raw) && isset($raw[0]) ? $raw : []);
        $hasCritical = collect($alerts)->contains(
            fn ($a) => ($a['severity'] ?? $a['level'] ?? '') === 'critical'
        );

        return [
            'alerts'      => array_values($alerts),
            'totalCount'  => count($alerts),
            'hasCritical' => $hasCritical,
        ];
    }

    private function buildSystemHealth(): array
    {
        return Cache::remember('platform.dashboard.system_health', 60, function () {
            $services = [];

            // Central database
            try {
                $start = microtime(true);
                DB::connection()->getPdo();
                $latency = round((microtime(true) - $start) * 1000, 2);
                $dbStatus = 'ok';
                $services['database'] = ['status' => 'ok', 'latency' => $latency];
            } catch (\Throwable) {
                $dbStatus = 'down';
                $services['database'] = ['status' => 'down', 'latency' => null];
            }

            // Cache
            try {
                Cache::put('_health_check', true, 5);
                $cacheOk = Cache::get('_health_check') === true;
                $services['cache'] = ['status' => $cacheOk ? 'ok' : 'degraded', 'latency' => null];
            } catch (\Throwable) {
                $services['cache'] = ['status' => 'down', 'latency' => null];
            }

            // Queue
            try {
                $failedJobs = DB::table('failed_jobs')->count();
                $services['queue'] = [
                    'status'  => $failedJobs > 50 ? 'degraded' : 'ok',
                    'latency' => null,
                    'failed'  => $failedJobs,
                ];
            } catch (\Throwable) {
                $services['queue'] = ['status' => 'unknown', 'latency' => null];
            }

            // Storage / disk
            $diskFree  = @disk_free_space(base_path());
            $diskTotal = @disk_total_space(base_path());
            $diskPct   = ($diskFree && $diskTotal)
                ? round((1 - $diskFree / $diskTotal) * 100, 1)
                : 0;
            $services['storage'] = ['status' => $diskPct > 90 ? 'degraded' : 'ok', 'latency' => null];

            // Mail + Search placeholders
            $services['mail']   = ['status' => 'ok',      'latency' => null];
            $services['search'] = ['status' => 'unknown', 'latency' => null];

            return [
                'cpu'      => null,
                'memory'   => null,
                'disk'     => $diskPct,
                'services' => $services,
            ];
        });
    }

    private function buildRecentTenants(): array
    {
        return Cache::remember('platform.dashboard.recent_tenants', 300, function () {
            return Tenant::query()
                ->with(['subscription.plan', 'domains'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function (Tenant $tenant) {
                    $domain = $tenant->domains->first()?->domain ?? $tenant->id;
                    $isTrial = $tenant->status === Tenant::STATUS_ACTIVE
                        && ($tenant->subscription?->onTrial() ?? false);

                    return [
                        'id'        => $tenant->id,
                        'name'      => $tenant->name ?? $tenant->id,
                        'domain'    => $domain,
                        'plan'      => $tenant->subscription?->plan?->name ?? null,
                        'status'    => $isTrial ? 'trial' : ($tenant->status ?? 'unknown'),
                        'mrr'       => $tenant->subscription?->mrr ?? null,
                        'createdAt' => $tenant->created_at?->diffForHumans() ?? null,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Calculate all platform statistics.
     * Cached for 5 minutes to reduce database load.
     */
    protected function calculatePlatformStats(): array
    {
        return Cache::remember('platform.dashboard.stats', 300, function () {
            $totalTenants        = Tenant::count();
            $activeTenants       = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();
            $pendingTenants      = Tenant::where('status', Tenant::STATUS_PENDING)->count();
            $suspendedTenants    = Tenant::where('status', Tenant::STATUS_SUSPENDED)->count();
            $failedTenants       = Tenant::where('status', Tenant::STATUS_FAILED)->count();
            $provisioningTenants = Tenant::where('status', Tenant::STATUS_PROVISIONING)->count();
            $archivedTenants     = Tenant::where('status', 'archived')->count();

            $trialTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)
                ->whereHas('subscription', fn ($q) =>
                    $q->where('status', Subscription::STATUS_TRIALING)
                      ->where('trial_ends_at', '>', now())
                )
                ->count();

            $totalAdmins  = LandlordUser::count();
            $activeAdmins = LandlordUser::where('active', true)->count();

            $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();

            $monthlyMrr = Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->where('billing_cycle', 'monthly')
                ->sum('amount');

            $yearlyMrr = Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->where('billing_cycle', 'yearly')
                ->selectRaw('SUM(amount / 12) as mrr')
                ->value('mrr') ?? 0;

            $mrr  = (float) $monthlyMrr + (float) $yearlyMrr;
            $arr  = $mrr * 12;
            $arpt = $activeTenants > 0 ? $mrr / $activeTenants : 0;

            $newThisMonth = Tenant::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count();

            $newThisWeek = Tenant::where('created_at', '>=', now()->startOfWeek())->count();

            $cancelledLast30 = Subscription::where('status', Subscription::STATUS_CANCELLED)
                ->where('cancelled_at', '>=', now()->subDays(30))->count();

            $activeAt30DaysAgo = Subscription::where('starts_at', '<=', now()->subDays(30))
                ->where(fn ($q) => $q->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>', now()->subDays(30)))
                ->count();

            $churnRate = $activeAt30DaysAgo > 0
                ? round(($cancelledLast30 / $activeAt30DaysAgo) * 100, 2)
                : 0;

            return [
                // Tenant pipeline (all statuses)
                'totalTenants'        => $totalTenants,
                'activeTenants'       => $activeTenants,
                'pendingTenants'      => $pendingTenants,
                'suspendedTenants'    => $suspendedTenants,
                'failedTenants'       => $failedTenants,
                'provisioningTenants' => $provisioningTenants,
                'archivedTenants'     => $archivedTenants,
                'trialTenants'        => $trialTenants,

                // Admin users
                'totalAdmins'  => $totalAdmins,
                'activeAdmins' => $activeAdmins,
                'totalUsers'   => $totalAdmins,
                'activeUsers'  => $activeAdmins,

                // Revenue
                'activeSubscriptions' => $activeSubscriptions,
                'mrr'                 => round($mrr, 2),
                'arr'                 => round($arr, 2),
                'arpt'                => round($arpt, 2),
                'avgRevenuePerTenant' => round($arpt, 0),

                // Growth
                'newThisMonth' => $newThisMonth,
                'newThisWeek'  => $newThisWeek,
                'churnRate'    => $churnRate,

                // System
                'systemStatus' => ($failedTenants > 0 || $provisioningTenants > 5) ? 'degraded' : 'operational',
                'uptime'       => 99.98,
            ];
        });
    }
}
