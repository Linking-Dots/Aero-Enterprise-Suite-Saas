<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformDashboardService;
use Aero\Platform\Widgets\BillingOverviewWidget;
use Aero\Platform\Widgets\ModuleUsageWidget;
use Aero\Platform\Widgets\QuickActionsWidget;
use Aero\Platform\Widgets\RecentActivityWidget;
use Aero\Platform\Widgets\SubscriptionDistributionWidget;
use Aero\Platform\Widgets\SystemAlertsWidget;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PlatformDashboardService    $svc,
        private BillingOverviewWidget       $billingWidget,
        private ModuleUsageWidget           $moduleWidget,
        private QuickActionsWidget          $quickActionsWidget,
        private RecentActivityWidget        $activityWidget,
        private SubscriptionDistributionWidget $distWidget,
        private SystemAlertsWidget          $alertsWidget,
    ) {}

    // ── Main dashboard render ─────────────────────────────────────────────

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Dashboard/Index', [

            /* Welcome / command bar */
            'welcome' => $this->buildWelcome(),

            /* KPI strip + MRR trend */
            'stats' => Inertia::lazy(fn () => $this->svc->stats()),

            /* Billing overview — MRR/ARR breakdown + 6-month trend */
            'billingOverview' => Inertia::lazy(fn () => $this->billingWidget->getData()),

            /* System alerts — severity-ranked */
            'systemAlerts' => Inertia::lazy(fn () => $this->buildSystemAlerts()),

            /* System health — CPU/Mem/Disk + services */
            'systemHealth' => Inertia::lazy(fn () => $this->buildSystemHealth()),

            /* Recent tenants table */
            'recentTenants' => Inertia::lazy(fn () => $this->buildRecentTenants()),

            /* Module adoption bar chart */
            'moduleUsage' => Inertia::lazy(fn () => $this->moduleWidget->getData()),

            /* Subscription donut */
            'subscriptionDistribution' => Inertia::lazy(fn () => $this->distWidget->getData()),

            /* Activity timeline */
            'recentActivity' => Inertia::lazy(fn () => $this->activityWidget->getData()),

            /* Quick actions grid */
            'quickActions' => Inertia::lazy(fn () => $this->quickActionsWidget->getData()),
        ]);
    }

    // ── Partial-reload endpoint (called by router.reload({ only: [...] })) ─

    public function stats(): JsonResponse
    {
        return response()->json($this->svc->stats());
    }

    public function systemHealth(): JsonResponse
    {
        return response()->json($this->buildSystemHealth());
    }

    // ── Lazy widget endpoint (optional: /admin/dashboard/widget/{key}) ─────

    public function widget(Request $request, string $key): JsonResponse
    {
        $data = match ($key) {
            'stats'                    => $this->svc->stats(),
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

        if ($data === null) {
            return response()->json(['error' => 'Unknown widget'], 404);
        }

        return response()->json($data);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildWelcome(): array
    {
        $user = auth()->user();
        $hour = (int) now()->format('H');

        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default    => 'Good evening',
        };

        return [
            'greeting' => $greeting,
            'userName' => $user?->name ?? 'Admin',
            'date'     => Carbon::now()->translatedFormat('l, F j Y'),
        ];
    }

    private function buildSystemAlerts(): array
    {
        $raw        = $this->alertsWidget->getData();
        $alerts     = $raw['alerts']     ?? $raw ?? [];
        $hasCritical = collect($alerts)->contains(fn ($a) => ($a['severity'] ?? '') === 'critical');

        return [
            'alerts'      => $alerts,
            'totalCount'  => count($alerts),
            'hasCritical' => $hasCritical,
        ];
    }

    private function buildSystemHealth(): array
    {
        $raw = $this->svc->systemHealth();

        // Normalise the health structure the frontend expects:
        // { cpu, memory, disk, services: { database, cache, queue, storage, mail, search } }
        $storage = $raw['storage'] ?? [];

        return [
            'cpu'    => null,   // populated via server-side monitoring if available
            'memory' => null,
            'disk'   => $storage['used_pct'] ?? null,
            'services' => [
                'database' => $this->normaliseService($raw['database'] ?? []),
                'cache'    => $this->normaliseService($raw['cache']    ?? []),
                'queue'    => $this->normaliseService($raw['queue']    ?? []),
                'storage'  => $this->normaliseService($raw['storage']  ?? []),
                'mail'     => $this->normaliseService([]),
                'search'   => $this->normaliseService([]),
            ],
        ];
    }

    private function normaliseService(array $raw): array
    {
        $status  = $raw['status']     ?? 'unknown';
        $latency = $raw['latency_ms'] ?? $raw['latency'] ?? null;

        return [
            'status'  => match ($status) {
                'ok'      => 'ok',
                'warn'    => 'degraded',
                'error'   => 'down',
                default   => 'unknown',
            },
            'latency' => $latency,
        ];
    }

    private function buildRecentTenants(): array
    {
        return collect($this->svc->recentTenants(10))->map(function ($t) {
            return [
                'id'        => $t['id']          ?? null,
                'name'      => $t['name']         ?? '—',
                'domain'    => isset($t['subdomain'])
                    ? $t['subdomain'] . '.' . config('aero.platform.domain', 'app')
                    : null,
                'plan'      => $t['plan_name']    ?? $t['plan'] ?? null,
                'status'    => $t['status']       ?? 'unknown',
                'mrr'       => $t['mrr']          ?? null,
                'createdAt' => isset($t['created_at'])
                    ? Carbon::parse($t['created_at'])->diffForHumans()
                    : null,
            ];
        })->toArray();
    }
}
