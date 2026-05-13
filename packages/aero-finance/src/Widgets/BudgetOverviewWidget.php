<?php

declare(strict_types=1);

namespace Aero\Finance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Budget Overview Widget
 *
 * Displays budget vs actual spending.
 * This is a SUMMARY widget showing budget status.
 *
 * Appears on: Finance Dashboard (/finance/dashboard)
 */
class BudgetOverviewWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 30;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['finance.budget'];

    protected array $dashboards = ['finance'];

    public function getKey(): string
    {
        return 'finance.budget_overview';
    }

    public function getComponent(): string
    {
        return 'Widgets/Finance/BudgetOverviewWidget';
    }

    public function getTitle(): string
    {
        return 'Budget Overview';
    }

    public function getDescription(): string
    {
        return 'Budget vs actual spending';
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

            // TODO: Implement real data from Finance\Models\Budget
            return [
                'total_budget' => 500000,
                'spent' => 350000,
                'remaining' => 150000,
                'utilization_percent' => 70,
                'period' => 'this_month',
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'total_budget' => 0,
            'spent' => 0,
            'remaining' => 0,
            'utilization_percent' => 0,
            'period' => 'this_month',
            'message' => 'No budget data available',
        ];
    }
}
