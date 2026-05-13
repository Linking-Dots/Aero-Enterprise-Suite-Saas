<?php

declare(strict_types=1);

namespace Aero\Compliance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Upcoming Compliance Audits Widget
 *
 * Displays scheduled compliance audits.
 * This is a DISPLAY widget for audit planning visibility.
 *
 * Appears on: Core Dashboard (/dashboard), Compliance Dashboard (/compliance/dashboard)
 */
class UpcomingComplianceAuditsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 30;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = ['compliance.audits'];

    protected array $dashboards = ['compliance'];

    public function getKey(): string
    {
        return 'compliance.upcoming_audits';
    }

    public function getComponent(): string
    {
        return 'Widgets/Compliance/UpcomingComplianceAuditsWidget';
    }

    public function getTitle(): string
    {
        return 'Upcoming Compliance Audits';
    }

    public function getDescription(): string
    {
        return 'Scheduled compliance audits';
    }

    public function getModuleCode(): string
    {
        return 'compliance';
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

            // TODO: Query actual audits from ComplianceAudit model
            return [
                'audits' => [],
                'this_week' => 0,
                'this_month' => 0,
                'overdue' => 0,
                'total_scheduled' => 0,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'audits' => [],
            'this_week' => 0,
            'this_month' => 0,
            'overdue' => 0,
            'total_scheduled' => 0,
            'message' => 'No upcoming compliance audits',
        ];
    }
}
