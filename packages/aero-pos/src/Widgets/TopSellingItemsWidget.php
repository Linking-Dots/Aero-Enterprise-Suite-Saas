<?php

declare(strict_types=1);

namespace Aero\Pos\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Top Selling Items Widget
 *
 * Displays best-selling products.
 * This is a SUMMARY widget showing top selling items.
 *
 * Appears on: POS Dashboard (/pos/dashboard)
 */
class TopSellingItemsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['pos.reports'];

    protected array $dashboards = ['pos'];

    public function getKey(): string
    {
        return 'pos.top_selling';
    }

    public function getComponent(): string
    {
        return 'Widgets/POS/TopSellingItemsWidget';
    }

    public function getTitle(): string
    {
        return 'Top Selling Items';
    }

    public function getDescription(): string
    {
        return 'Best-selling products';
    }

    public function getModuleCode(): string
    {
        return 'pos';
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

            // TODO: Implement real data from POS\Models\Transaction
            return [
                'items' => [],
                'period' => 'this_month',
                'total_sales' => 0,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'items' => [],
            'period' => 'this_month',
            'total_sales' => 0,
            'message' => 'No sales data available',
        ];
    }
}
