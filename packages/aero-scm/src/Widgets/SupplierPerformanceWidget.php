<?php

declare(strict_types=1);

namespace Aero\Scm\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Supplier Performance Widget
 *
 * Displays supplier delivery and quality metrics.
 * This is a SUMMARY widget showing supply chain health.
 *
 * Appears on: SCM Dashboard (/scm/dashboard)
 */
class SupplierPerformanceWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['scm.suppliers'];

    protected array $dashboards = ['scm'];

    public function getKey(): string
    {
        return 'scm.supplier_performance';
    }

    public function getComponent(): string
    {
        return 'Widgets/SCM/SupplierPerformanceWidget';
    }

    public function getTitle(): string
    {
        return 'Supplier Performance';
    }

    public function getDescription(): string
    {
        return 'Supplier delivery and quality metrics';
    }

    public function getModuleCode(): string
    {
        return 'scm';
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

            // TODO: Implement real data from SCM\Models\Supplier
            return [
                'suppliers' => [],
                'avg_rating' => 4.2,
                'on_time_delivery' => 92,
                'quality_score' => 88,
                'total_suppliers' => 25,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'suppliers' => [],
            'avg_rating' => 0,
            'on_time_delivery' => 0,
            'quality_score' => 0,
            'total_suppliers' => 0,
            'message' => 'No supplier data available',
        ];
    }
}
