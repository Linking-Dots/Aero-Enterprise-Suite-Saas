<?php

declare(strict_types=1);

namespace Aero\Quality\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Upcoming Audits Widget
 *
 * Displays scheduled quality audits.
 * This is a DISPLAY widget for audit planning visibility.
 *
 * Appears on: Core Dashboard (/dashboard), Quality Dashboard (/quality/dashboard)
 */
class UpcomingAuditsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 30;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = ['quality.audits'];

    protected array $dashboards = ['quality'];

    public function getKey(): string
    {
        return 'quality.upcoming_audits';
    }

    public function getComponent(): string
    {
        return 'Widgets/Quality/UpcomingAuditsWidget';
    }

    public function getTitle(): string
    {
        return 'Upcoming Audits';
    }

    public function getDescription(): string
    {
        return 'Scheduled quality audits';
    }

    public function getModuleCode(): string
    {
        return 'quality';
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

            // TODO: Query actual audits from Quality\Models\Audit
            return [
                'audits' => [],
                'this_week' => 0,
                'this_month' => 0,
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
            'total_scheduled' => 0,
            'message' => 'No upcoming audits',
        ];
    }
}
