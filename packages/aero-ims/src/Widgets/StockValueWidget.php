<?php

declare(strict_types=1);

namespace Aero\Ims\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Stock Value Widget
 *
 * Displays current inventory value.
 * This is a SUMMARY widget showing inventory value.
 *
 * Appears on: IMS Dashboard (/ims/dashboard)
 */
class StockValueWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['ims.reports'];

    protected array $dashboards = ['ims'];

    public function getKey(): string
    {
        return 'ims.stock_value';
    }

    public function getComponent(): string
    {
        return 'Widgets/IMS/StockValueWidget';
    }

    public function getTitle(): string
    {
        return 'Stock Value';
    }

    public function getDescription(): string
    {
        return 'Current inventory value';
    }

    public function getModuleCode(): string
    {
        return 'ims';
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

            // TODO: Implement real data from IMS\Models\Item
            return [
                'total_value' => 250000,
                'items_count' => 1250,
                'trend' => 'up',
                'trend_percent' => 5,
                'by_category' => [],
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'total_value' => 0,
            'items_count' => 0,
            'trend' => 'stable',
            'trend_percent' => 0,
            'by_category' => [],
            'message' => 'No inventory data available',
        ];
    }
}
