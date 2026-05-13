<?php

declare(strict_types=1);

namespace Aero\DMS\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Dms\Models\Document;

class DmsDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalDocuments = Document::count();
        $pendingApproval = Document::where('status', 'pending_approval')->count();
        $recentUploads = Document::where('created_at', '>=', now()->subDays(7))->count();

        $alerts = [];
        if ($pendingApproval > 0) {
            $alerts[] = "{$pendingApproval} documents awaiting approval";
        }

        return [
            'key' => 'dms',
            'label' => 'Documents',
            'icon' => 'DocumentTextIcon',
            'route' => 'tenant.dms.documents.index',
            'stats' => [
                'total' => $totalDocuments,
                'pendingApproval' => $pendingApproval,
                'recentUploads' => $recentUploads,
            ],
            'alerts' => $alerts,
            'pendingCount' => $pendingApproval,
        ];
    }
}
