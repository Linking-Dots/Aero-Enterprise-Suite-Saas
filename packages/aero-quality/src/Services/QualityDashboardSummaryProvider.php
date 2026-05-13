<?php

declare(strict_types=1);

namespace Aero\Quality\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Quality\Models\QualityInspection;

class QualityDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $total = QualityInspection::count();
        $pending = QualityInspection::where('status', 'pending')->count();
        $failed = QualityInspection::where('status', 'failed')->count();
        $passed = QualityInspection::where('status', 'passed')->count();

        $alerts = [];
        if ($failed > 0) {
            $alerts[] = "{$failed} failed inspections need attention";
        }
        if ($pending > 5) {
            $alerts[] = "{$pending} inspections pending review";
        }

        return [
            'key' => 'quality',
            'label' => 'Quality',
            'icon' => 'ShieldCheckIcon',
            'route' => 'tenant.quality.inspections.index',
            'stats' => [
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pending' => $pending,
            ],
            'alerts' => $alerts,
            'pendingCount' => $pending + $failed,
        ];
    }
}
