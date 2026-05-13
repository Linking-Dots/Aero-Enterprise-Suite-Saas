<?php

declare(strict_types=1);

namespace Aero\Finance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Cash Flow Widget
 *
 * Displays current cash flow overview.
 * This is a SUMMARY widget showing cash flow summary.
 *
 * Appears on: Finance Dashboard (/finance/dashboard)
 */
class CashFlowWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['finance.reports'];

    protected array $dashboards = ['finance'];

    public function getKey(): string
    {
        return 'finance.cash_flow';
    }

    public function getComponent(): string
    {
        return 'Widgets/Finance/CashFlowWidget';
    }

    public function getTitle(): string
    {
        return 'Cash Flow';
    }

    public function getDescription(): string
    {
        return 'Current cash flow overview';
    }

    public function getModuleCode(): string
    {
        return 'finance';
    }

    public function isEnabled(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        return $this->userHasModuleAccess();
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = auth()->user();
            if (! $user) {
                return $this->getEmptyState();
            }

            // TODO: Implement real data from Finance models
            return [
                'inflow' => 125000,
                'outflow' => 85000,
                'net_flow' => 40000,
                'period' => 'this_month',
                'trend' => 'up',
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'inflow' => 0,
            'outflow' => 0,
            'net_flow' => 0,
            'period' => 'this_month',
            'trend' => 'stable',
            'message' => 'No cash flow data available',
        ];
    }
}
