<?php

declare(strict_types=1);

namespace Aero\Finance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Invoices Widget
 *
 * Shows unpaid/overdue invoices requiring attention.
 * This is an ALERT widget - revenue collection critical.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class PendingInvoicesWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['finance.invoices'];

    protected array $dashboards = ['finance'];

    public function getKey(): string
    {
        return 'finance.pending_invoices';
    }

    public function getComponent(): string
    {
        return 'Widgets/Finance/PendingInvoicesWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Invoices';
    }

    public function getDescription(): string
    {
        return 'Unpaid and overdue invoices';
    }

    public function getModuleCode(): string
    {
        return 'finance';
    }

    /**
     * Check if widget is enabled for current user.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - MUST BE FIRST
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        // Check HRMAC module access
        return $this->userHasModuleAccess();
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = auth()->user();
            if (! $user) {
                return $this->getEmptyState();
            }

            // Get pending invoices
            // In production: Query from Invoice model
            // For now, return structure with sample data
            $unpaidCount = 0;
            $overdueCount = 0;
            $totalAmount = 0;

            // TODO: Implement actual queries when Invoice model is ready
            // $unpaidCount = Invoice::where('status', 'unpaid')->count();
            // $overdueCount = Invoice::where('status', 'overdue')->count();
            // $totalAmount = Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('total_amount');

            return [
                'unpaid' => $unpaidCount,
                'overdue' => $overdueCount,
                'total_amount' => $totalAmount,
                'currency' => 'BDT',
                'show_more_url' => route('finance.invoices.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'unpaid' => 0,
            'overdue' => 0,
            'total_amount' => 0,
            'currency' => 'BDT',
            'message' => 'All invoices paid',
        ];
    }
}
