<?php

declare(strict_types=1);

namespace Aero\Ims\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Low Stock Alerts Widget
 *
 * Shows items below reorder level requiring attention.
 * This is an ALERT widget - prevent stockouts.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class LowStockAlertsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 40;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['ims.inventory'];

    protected array $dashboards = ['ims'];

    public function getKey(): string
    {
        return 'ims.low_stock_alerts';
    }

    public function getComponent(): string
    {
        return 'Widgets/IMS/LowStockAlertsWidget';
    }

    public function getTitle(): string
    {
        return 'Low Stock Alerts';
    }

    public function getDescription(): string
    {
        return 'Items below reorder level';
    }

    public function getModuleCode(): string
    {
        return 'ims';
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

            // Get low stock items
            // In production: Query from InventoryItem model
            // For now, return structure with sample data
            $lowStockCount = 0;
            $outOfStockCount = 0;
            $criticalCount = 0;

            // TODO: Implement actual queries when InventoryItem model is ready
            // $lowStockCount = InventoryItem::whereColumn('quantity', '<=', 'reorder_level')->count();
            // $outOfStockCount = InventoryItem::where('quantity', 0)->count();
            // $criticalCount = InventoryItem::where('is_critical', true)->whereColumn('quantity', '<=', 'reorder_level')->count();

            return [
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
                'critical' => $criticalCount,
                'show_more_url' => route('ims.inventory.low-stock', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'low_stock' => 0,
            'out_of_stock' => 0,
            'critical' => 0,
            'message' => 'All stock levels healthy',
        ];
    }
}
