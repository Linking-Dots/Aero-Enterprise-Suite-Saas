<?php

declare(strict_types=1);

namespace Aero\Project\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Overdue Tasks Widget
 *
 * Shows overdue tasks requiring immediate attention.
 * This is an ALERT widget - critical tasks past due date.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class OverdueTasksWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 11;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['project.tasks'];

    protected array $dashboards = ['project'];

    public function getKey(): string
    {
        return 'project.overdue_tasks';
    }

    public function getComponent(): string
    {
        return 'Widgets/Project/OverdueTasksWidget';
    }

    public function getTitle(): string
    {
        return 'Overdue Tasks';
    }

    public function getDescription(): string
    {
        return 'Tasks past their due date';
    }

    public function getModuleCode(): string
    {
        return 'project';
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

            // Get overdue tasks
            // In production: Query from Task model where due_date < today
            // For now, return structure with sample data
            $myOverdueCount = 0;
            $teamOverdueCount = 0;
            $criticalCount = 0;

            // TODO: Implement actual queries when Task model is ready
            // $myOverdueCount = Task::where('assigned_to', $user->id)->where('due_date', '<', now())->whereIn('status', ['todo', 'in_progress'])->count();
            // $teamOverdueCount = Task::whereHas('project.team', fn($q) => $q->where('user_id', $user->id))->where('due_date', '<', now())->count();
            // $criticalCount = Task::where('priority', 'critical')->where('due_date', '<', now())->count();

            return [
                'my_overdue' => $myOverdueCount,
                'team_overdue' => $teamOverdueCount,
                'critical' => $criticalCount,
                'show_more_url' => route('project.tasks.index', ['filter' => 'overdue'], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'my_overdue' => 0,
            'team_overdue' => 0,
            'critical' => 0,
            'message' => 'No overdue tasks',
        ];
    }
}
