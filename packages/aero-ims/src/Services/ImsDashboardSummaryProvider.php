<?php

declare(strict_types=1);

namespace Aero\Ims\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Ims\Models\Product;
use Aero\Ims\Models\Warehouse;

class ImsDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $warehouses = Warehouse::where('status', 'active')->count();

        $lowStock = Product::where('status', 'active')
            ->whereColumn('minimum_stock', '>', 'reorder_point')
            ->count();

        $alerts = [];
        if ($lowStock > 0) {
            $alerts[] = "{$lowStock} products below reorder point";
        }

        return [
            'key' => 'ims',
            'label' => 'Inventory',
            'icon' => 'CubeIcon',
            'route' => 'tenant.ims.inventory.index',
            'stats' => [
                'products' => $totalProducts,
                'active' => $activeProducts,
                'warehouses' => $warehouses,
                'lowStock' => $lowStock,
            ],
            'alerts' => $alerts,
            'pendingCount' => $lowStock,
        ];
    }
}
