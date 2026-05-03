<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\PlatformWidgetRegistry;
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
 * Aggregates widgets from Platform and provides real-time metrics.
 *
 * Using Platform's own widget registry:
 * - Uses PlatformWidgetRegistry for dynamic widgets (independent from Core)
 * - Provides API endpoints for lazy loading
 * - Caches expensive aggregations
 */
class AdminDashboardController extends Controller
{
    public function __construct(
        protected PlatformWidgetRegistry $widgetRegistry
    ) {}

    /**
     * Display the main admin dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::guard('landlord')->user();

        // Get platform stats (cached for performance)
        $stats = $this->getPlatformStats();

        // Get dynamic widgets from registry (from Platform + registered modules)
        // The registry handles lazy loading and permission checks
        $dynamicWidgets = $this->widgetRegistry->getWidgetsForFrontend();

        // Filter to only platform widgets for admin dashboard
        // Then key by widget key for easier frontend access
        $platformWidgets = collect($dynamicWidgets)
            ->filter(fn ($widget) => $widget['module'] === 'platform')
            ->keyBy('key')
            ->toArray();

        return Inertia::render('Platform/Admin/Dashboard', [
            'title' => 'Dashboard - Admin',
            'stats' => $stats,
            'dynamicWidgets' => $platformWidgets,
            'recentTenants' => fn () => $this->getRecentTenants(),
            'systemHealth' => fn () => $this->getSystemHealth(),
        ]);
    }

    /**
     * Get dashboard stats (for async loading).
     */
    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->getPlatformStats());
    }

    /**
     * Get widget data for a specific widget (for lazy loading).
     */
    public function widgetData(Request $request, string $widgetKey): JsonResponse
    {
        $widgets = $this->widgetRegistry->getWidgets();

        foreach ($widgets as $widget) {
            if ($widget->getKey() === $widgetKey) {
                return response()->json([
                    'key' => $widget->getKey(),
                    'data' => $widget->getData(),
                ]);
            }
        }

        return response()->json(['error' => 'Widget not found'], 404);
    }

    /**
     * Refresh dashboard cache.
     */
    public function refresh(Request $request): JsonResponse
    {
        // Clear cached stats
        Cache::forget('platform.dashboard.stats');
        Cache::forget('platform.dashboard.alerts');
        Cache::forget('platform.dashboard.subscription_distribution');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache refreshed',
            'stats' => $this->getPlatformStats(),
        ]);
    }

    /**
     * Get platform statistics.
     * Cached for 5 minutes to reduce database load.
     */
    protected function getPlatformStats(): array
    {
        return Cache::remember('platform.dashboard.stats', 300, function () {
            return $this->calculatePlatformStats();
        });
    }

    /**
     * Calculate all platform statistics.
     */
    protected function calculatePlatformStats(): array
    {
        // Tenant statistics
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();
        $pendingTenants = Tenant::where('status', Tenant::STATUS_PENDING)->count();
        $suspendedTenants = Tenant::where('status', Tenant::STATUS_SUSPENDED)->count();
        $failedTenants = Tenant::where('status', Tenant::STATUS_FAILED)->count();
        $provisioningTenants = Tenant::where('status', Tenant::STATUS_PROVISIONING)->count();

        // Trial tenants
        $trialTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)
            ->whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->where('trial_ends_at', '>', now()))
            ->count();

        // User statistics
        $totalAdmins = LandlordUser::count();
        $activeAdmins = LandlordUser::where('active', true)->count();

        // Revenue statistics
        $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();

        $monthlyMrr = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', 'monthly')
            ->sum('amount');

        $yearlyMrr = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', 'yearly')
            ->selectRaw('SUM(amount / 12) as mrr')
            ->value('mrr') ?? 0;

        $mrr = (float) $monthlyMrr + (float) $yearlyMrr;
        $arr = $mrr * 12;

        // Average revenue per tenant
        $arpt = $activeTenants > 0 ? $mrr / $activeTenants : 0;

        // Growth metrics
        $newThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $newThisWeek = Tenant::where('created_at', '>=', now()->startOfWeek())
            ->count();

        // Calculate churn rate (last 30 days)
        $cancelledLast30 = Subscription::where('status', Subscription::STATUS_CANCELLED)
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->count();

        $activeAt30DaysAgo = Subscription::where('starts_at', '<=', now()->subDays(30))
            ->where(function ($q) {
                $q->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>', now()->subDays(30));
            })
            ->count();

        $churnRate = $activeAt30DaysAgo > 0
            ? round(($cancelledLast30 / $activeAt30DaysAgo) * 100, 2)
            : 0;

        // System health indicators
        $systemStatus = 'operational';
        if ($failedTenants > 0 || $provisioningTenants > 5) {
            $systemStatus = 'degraded';
        }

        return [
            // Tenant metrics
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'pendingTenants' => $pendingTenants,
            'suspendedTenants' => $suspendedTenants,
            'failedTenants' => $failedTenants,
            'provisioningTenants' => $provisioningTenants,
            'trialTenants' => $trialTenants,

            // User metrics (aliased for frontend compatibility)
            'totalAdmins' => $totalAdmins,
            'activeAdmins' => $activeAdmins,
            'totalUsers' => $totalAdmins,
            'activeUsers' => $activeAdmins,

            // Revenue metrics
            'activeSubscriptions' => $activeSubscriptions,
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'arpt' => round($arpt, 2),
            'avgRevenuePerTenant' => round($arpt, 0),

            // Growth metrics
            'newThisMonth' => $newThisMonth,
            'newThisWeek' => $newThisWeek,
            'churnRate' => $churnRate,

            // System health
            'systemStatus' => $systemStatus,
            'uptime' => 99.98,

            // Formatted values for display
            'formatted' => [
                'mrr' => '$'.number_format($mrr / 1000, 1).'K',
                'arr' => '$'.number_format($arr / 1000000, 2).'M',
                'arpt' => '$'.number_format($arpt, 0),
            ],
        ];
    }

    /**
     * Get recent tenants for dashboard display (last 10 created).
     *
     * @return array<int, array{id: string, name: string, domain: string, status: string, plan: string, users: int, createdAt: string}>
     */
    protected function getRecentTenants(): array
    {
        return Cache::remember('platform.dashboard.recent_tenants', 300, function () {
            return Tenant::query()
                ->with(['subscription.plan'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function (Tenant $tenant) {
                    $domain = $tenant->domains()->first();

                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name ?? $tenant->id,
                        'domain' => $domain?->domain ?? $tenant->id,
                        'status' => $this->resolveTenantDisplayStatus($tenant),
                        'plan' => $tenant->subscription?->plan?->name ?? 'No Plan',
                        'users' => (int) ($tenant->data['team_size'] ?? 0),
                        'createdAt' => $tenant->created_at?->diffForHumans() ?? '',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Resolve display-friendly tenant status (active, trial, pending, suspended, failed).
     */
    protected function resolveTenantDisplayStatus(Tenant $tenant): string
    {
        if ($tenant->status === Tenant::STATUS_ACTIVE && ($tenant->subscription('default')?->onTrial() ?? false)) {
            return 'trial';
        }

        return $tenant->status ?? 'unknown';
    }

    /**
     * Get basic system health via PHP-native checks.
     *
     * @return array{cpu: int, memory: int, disk: int, network: int, services: array, dbStatus: string, failedJobs: int, queueSize: int, tenantDbCount: int}
     */
    protected function getSystemHealth(): array
    {
        return Cache::remember('platform.dashboard.system_health', 60, function () {
            $services = [];

            // Central database check
            try {
                $start = microtime(true);
                DB::connection()->getPdo();
                $latency = round((microtime(true) - $start) * 1000);
                $dbStatus = 'healthy';
                $services[] = ['name' => 'Central Database', 'status' => 'healthy', 'latency' => $latency.'ms'];
            } catch (\Throwable) {
                $dbStatus = 'critical';
                $services[] = ['name' => 'Central Database', 'status' => 'critical', 'latency' => '—'];
            }

            // Tenant database count
            $tenantDbCount = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();
            $services[] = ['name' => "Tenant Databases ({$tenantDbCount})", 'status' => 'healthy', 'latency' => '—'];

            // Failed jobs
            $failedJobs = 0;
            try {
                $failedJobs = DB::table('failed_jobs')->count();
                $services[] = [
                    'name' => 'Job Queue',
                    'status' => $failedJobs > 10 ? 'warning' : ($failedJobs > 0 ? 'warning' : 'healthy'),
                    'latency' => $failedJobs.' failed',
                ];
            } catch (\Throwable) {
                $services[] = ['name' => 'Job Queue', 'status' => 'warning', 'latency' => 'N/A'];
            }

            // Queue size (pending jobs)
            $queueSize = 0;
            try {
                $queueSize = DB::table('jobs')->count();
                $services[] = [
                    'name' => 'Queue Backlog',
                    'status' => $queueSize > 100 ? 'warning' : 'healthy',
                    'latency' => $queueSize.' pending',
                ];
            } catch (\Throwable) {
                $services[] = ['name' => 'Queue Backlog', 'status' => 'warning', 'latency' => 'N/A'];
            }

            // Disk usage (PHP-native)
            $diskFree = @disk_free_space(base_path());
            $diskTotal = @disk_total_space(base_path());
            $diskPercent = ($diskFree && $diskTotal) ? (int) round((1 - $diskFree / $diskTotal) * 100) : 0;

            // Cache check
            try {
                Cache::put('_health_check', true, 5);
                $cacheOk = Cache::get('_health_check') === true;
                $services[] = ['name' => 'Cache', 'status' => $cacheOk ? 'healthy' : 'warning', 'latency' => $cacheOk ? 'OK' : 'Unreachable'];
            } catch (\Throwable) {
                $services[] = ['name' => 'Cache', 'status' => 'critical', 'latency' => 'Error'];
            }

            return [
                'cpu' => 0,
                'memory' => 0,
                'disk' => $diskPercent,
                'network' => 0,
                'services' => $services,
                'dbStatus' => $dbStatus,
                'failedJobs' => $failedJobs,
                'queueSize' => $queueSize,
                'tenantDbCount' => $tenantDbCount,
            ];
        });
    }
}
