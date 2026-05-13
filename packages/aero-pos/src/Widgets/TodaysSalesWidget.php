<?php

declare(strict_types=1);

namespace Aero\Pos\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Today's Sales Widget
 *
 * Shows today's sales total and transaction count.
 * This is a STAT widget - daily business metrics.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class TodaysSalesWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 50;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::STAT;

    protected array $requiredPermissions = ['pos.sales'];

    protected array $dashboards = ['pos'];

    public function getKey(): string
    {
        return 'pos.todays_sales';
    }

    public function getComponent(): string
    {
        return 'Widgets/POS/TodaysSalesWidget';
    }

    public function getTitle(): string
    {
        return "Today's Sales";
    }

    public function getDescription(): string
    {
        return 'Sales total and transactions today';
    }

    public function getModuleCode(): string
    {
        return 'pos';
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

            // Get today's sales data
            // In production: Query from Sale/Transaction model
            // For now, return structure with sample data
            $totalSales = 0;
            $transactionCount = 0;
            $averageTicket = 0;

            // TODO: Implement actual queries when Sale model is ready
            // $totalSales = Sale::whereDate('created_at', today())->sum('total_amount');
            // $transactionCount = Sale::whereDate('created_at', today())->count();
            // $averageTicket = $transactionCount > 0 ? $totalSales / $transactionCount : 0;

            return [
                'total_sales' => $totalSales,
                'transaction_count' => $transactionCount,
                'average_ticket' => $averageTicket,
                'currency' => 'BDT',
                'show_more_url' => route('pos.reports.daily', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'total_sales' => 0,
            'transaction_count' => 0,
            'average_ticket' => 0,
            'currency' => 'BDT',
            'message' => 'No sales today',
        ];
    }
}
