<?php

declare(strict_types=1);

namespace Aero\Scm\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Scm\Models\Supplier;

class ScmDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 'active')->count();

        $pendingOrders = 0;
        try {
            if (class_exists(\Aero\Scm\Models\PurchaseOrder::class)) {
                $pendingOrders = \Aero\Scm\Models\PurchaseOrder::where('status', 'pending')->count();
            }
        } catch (\Throwable) {
        }

        $alerts = [];
        if ($pendingOrders > 0) {
            $alerts[] = "{$pendingOrders} pending purchase orders";
        }

        return [
            'key' => 'scm',
            'label' => 'Supply Chain',
            'icon' => 'TruckIcon',
            'route' => 'tenant.scm.procurement.index',
            'stats' => [
                'suppliers' => $totalSuppliers,
                'active' => $activeSuppliers,
                'pendingOrders' => $pendingOrders,
            ],
            'alerts' => $alerts,
            'pendingCount' => $pendingOrders,
        ];
    }
}
