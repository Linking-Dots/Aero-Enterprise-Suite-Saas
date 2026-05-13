<?php

declare(strict_types=1);

namespace Aero\Compliance\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Compliance\Models\ComplianceAudit;

class ComplianceDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalAudits = ComplianceAudit::count();
        $openAudits = ComplianceAudit::whereNotIn('status', ['completed', 'cancelled'])->count();
        $upcomingAudits = ComplianceAudit::where('status', 'planned')
            ->whereBetween('planned_date', [now(), now()->addDays(30)])
            ->count();

        $alerts = [];
        if ($upcomingAudits > 0) {
            $alerts[] = "{$upcomingAudits} audits scheduled within 30 days";
        }

        return [
            'key' => 'compliance',
            'label' => 'Compliance',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => 'tenant.compliance.audits.index',
            'stats' => [
                'total' => $totalAudits,
                'open' => $openAudits,
                'upcoming' => $upcomingAudits,
            ],
            'alerts' => $alerts,
            'pendingCount' => $openAudits,
        ];
    }
}
