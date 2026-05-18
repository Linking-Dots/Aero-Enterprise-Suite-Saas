<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Analytics;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Services\Analytics\HeadcountAnalyticsService;
use Aero\HRM\Services\Analytics\TurnoverAnalyticsService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsDashboardController extends Controller
{
    public function __construct(
        private readonly HeadcountAnalyticsService $headcount,
        private readonly TurnoverAnalyticsService $turnover,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Render the HR Analytics overview dashboard.
     */
    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.hr-analytics.workforce-overview.view');

        $kpis = $this->headcount->kpis();
        $monthlyTrend = $this->turnover->monthlyTrend();

        $this->audit->log(
            event: 'analytics.dashboard.view',
            action: 'view',
            subject: null,
            description: 'HR Analytics dashboard accessed',
        );

        return Inertia::render('HRM/Analytics/Dashboard', [
            'kpis' => $kpis,
            'turnover' => $monthlyTrend,
            'distribution' => $kpis['byDept'] ?? [],
        ]);
    }
}
