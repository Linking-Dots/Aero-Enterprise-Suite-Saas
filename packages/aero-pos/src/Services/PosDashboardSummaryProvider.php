<?php

declare(strict_types=1);

namespace Aero\Pos\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Pos\Models\Sale;
use Aero\Pos\Models\Transaction;

class PosDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $todaySales = Transaction::whereDate('created_at', today())->count();
        $todayRevenue = Transaction::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');
        $pendingPayments = Transaction::where('payment_status', 'pending')->count();
        $totalTransactions = Transaction::count();

        $alerts = [];
        if ($pendingPayments > 5) {
            $alerts[] = "{$pendingPayments} pending payments";
        }

        return [
            'key' => 'pos',
            'label' => 'Point of Sale',
            'icon' => 'ShoppingCartIcon',
            'route' => 'tenant.pos.index',
            'stats' => [
                'todaySales' => $todaySales,
                'todayRevenue' => number_format((float) $todayRevenue, 2),
                'pending' => $pendingPayments,
                'total' => $totalTransactions,
            ],
            'alerts' => $alerts,
            'pendingCount' => $pendingPayments,
        ];
    }
}
