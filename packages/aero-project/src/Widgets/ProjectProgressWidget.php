<?php

declare(strict_types=1);

namespace Aero\Project\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Project Progress Widget
 *
 * Displays active projects with completion status.
 * This is a SUMMARY widget showing project progress.
 *
 * Appears on: Project Dashboard (/project/dashboard)
 */
class ProjectProgressWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 20;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['project.projects'];

    protected array $dashboards = ['project'];

    public function getKey(): string
    {
        return 'project.progress';
    }

    public function getComponent(): string
    {
        return 'Widgets/Project/ProjectProgressWidget';
    }

    public function getTitle(): string
    {
        return 'Project Progress';
    }

    public function getDescription(): string
    {
        return 'Active projects with completion status';
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

            // TODO: Implement real data from Project\Models\Project
            return [
                'projects' => [],
                'active_count' => 0,
                'on_track' => 0,
                'at_risk' => 0,
                'delayed' => 0,
                'avg_completion' => 0,
            ];
        });
    }

    protected function getEmptyState(): array
    {
        return [
            'projects' => [],
            'active_count' => 0,
            'on_track' => 0,
            'at_risk' => 0,
            'delayed' => 0,
            'avg_completion' => 0,
            'message' => 'No active projects',
        ];
    }
}
