<?php

declare(strict_types=1);

namespace Aero\Finance\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Finance\Models\Account;
use Aero\Finance\Models\Invoice;

class FinanceDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalAccounts = Account::where('is_active', true)->count();

        $overdueInvoices = 0;
        $pendingPayments = 0;
        $totalReceivable = 0;
        try {
            $overdueInvoices = Invoice::where('status', 'overdue')->count();
            $pendingPayments = Invoice::where('status', 'pending')->count();
            $totalReceivable = Invoice::whereIn('status', ['sent', 'overdue'])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
                ->value('total');
        } catch (\Throwable) {
        }

        $alerts = [];
        if ($overdueInvoices > 0) {
            $alerts[] = "{$overdueInvoices} overdue invoices";
        }

        return [
            'key' => 'finance',
            'label' => 'Finance',
            'icon' => 'CurrencyDollarIcon',
            'route' => 'tenant.finance.dashboard',
            'stats' => [
                'accounts' => $totalAccounts,
                'overdue' => $overdueInvoices,
                'pending' => $pendingPayments,
                'receivable' => number_format((float) $totalReceivable, 2),
            ],
            'alerts' => $alerts,
            'pendingCount' => $pendingPayments + $overdueInvoices,
        ];
    }
}
