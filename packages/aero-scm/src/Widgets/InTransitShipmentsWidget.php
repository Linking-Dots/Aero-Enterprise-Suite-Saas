<?php

declare(strict_types=1);

namespace Aero\Scm\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * In-Transit Shipments Widget
 *
 * Displays shipments currently in transit.
 * This is a DISPLAY widget for logistics visibility.
 *
 * Appears on: SCM Dashboard (/scm/dashboard)
 */
class InTransitShipmentsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 30;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = ['scm.shipments'];

    protected array $dashboards = ['scm'];

    public function getKey(): string
    {
        return 'scm.in_transit';
    }

    public function getComponent(): string
    {
        return 'Widgets/SCM/InTransitShipmentsWidget';
    }

    public function getTitle(): string
    {
        return 'In-Transit Shipments';
    }

    public function getDescription(): string
    {
        return 'Shipments currently in transit';
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

            // TODO: Implement real data from SCM\Models\Shipment
            return [
                'shipments' => [],
                'in_transit_count' => 0,
                'expected_today' => 0,
                'delayed' => 0,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'shipments' => [],
            'in_transit_count' => 0,
            'expected_today' => 0,
            'delayed' => 0,
            'message' => 'No shipments in transit',
        ];
    }
}
