<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Payroll Summary Widget
 *
 * Displays payroll summary for HR managers.
 *
 * Appears on: HRM Manager Dashboard (/hrm/dashboard)
 */
class PayrollSummaryWidget extends AbstractDashboardWidget
{
    protected string $position = 'stats_row';

    protected int $order = 75;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['hrm.payroll']; // HRMAC format: module.submodule

    protected array $dashboards = ['hrm'];

    public function getKey(): string
    {
        return 'hrm.payroll_summary';
    }

    public function getComponent(): string
    {
        return 'Widgets/HRM/PayrollSummaryWidget';
    }

    public function getTitle(): string
    {
        return 'Payroll Summary';
    }

    public function getDescription(): string
    {
        return 'Current month payroll overview';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    public function getData(): array
    {
        // In production, query from Payroll model
        return [
            'total_payroll' => 0,
            'total_employees' => 0,
            'processed_count' => 0,
            'pending_count' => 0,
            'current_month' => now()->format('F Y'),
            'status' => 'pending', // pending, processing, completed
        ];
    }

    public function getProps(): array
    {
        return array_merge($this->getData(), [
            'title' => $this->getTitle(),
            'manage_url' => route('hrm.payroll.index', [], false),
        ]);
    }

    /**
     * Check if widget is enabled.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - always enabled, bypasses ALL checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        // Check HRM payroll module access via HRMAC
        return $this->userHasModuleAccess();
    }

    public function getPriority(): int
    {
        return 75;
    }
}
