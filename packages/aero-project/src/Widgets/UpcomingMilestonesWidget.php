<?php

declare(strict_types=1);

namespace Aero\Project\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Upcoming Milestones Widget
 *
 * Displays project milestones due soon.
 * This is a DISPLAY widget for milestone visibility.
 *
 * Appears on: Core Dashboard (/dashboard), Project Dashboard (/project/dashboard)
 */
class UpcomingMilestonesWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 40;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = ['project.projects'];

    protected array $dashboards = ['project'];

    public function getKey(): string
    {
        return 'project.upcoming_milestones';
    }

    public function getComponent(): string
    {
        return 'Widgets/Project/UpcomingMilestonesWidget';
    }

    public function getTitle(): string
    {
        return 'Upcoming Milestones';
    }

    public function getDescription(): string
    {
        return 'Project milestones due soon';
    }

    public function getModuleCode(): string
    {
        return 'project';
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

            // TODO: Query actual milestones from Project\Models\Milestone
            return [
                'milestones' => [],
                'this_week' => 0,
                'this_month' => 0,
                'overdue' => 0,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'milestones' => [],
            'this_week' => 0,
            'this_month' => 0,
            'overdue' => 0,
            'message' => 'No upcoming milestones',
        ];
    }
}
