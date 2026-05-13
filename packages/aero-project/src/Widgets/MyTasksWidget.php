<?php

declare(strict_types=1);

namespace Aero\Project\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * My Tasks Widget
 *
 * Shows tasks assigned to current user with deadlines.
 * This is an ACTION widget - user needs to work on tasks.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class MyTasksWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 10;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ACTION;

    protected array $requiredPermissions = ['project.tasks'];

    protected array $dashboards = ['project'];

    public function getKey(): string
    {
        return 'project.my_tasks';
    }

    public function getComponent(): string
    {
        return 'Widgets/Project/MyTasksWidget';
    }

    public function getTitle(): string
    {
        return 'My Tasks';
    }

    public function getDescription(): string
    {
        return 'Tasks assigned to you';
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

            // Get tasks assigned to user
            // In production: Query from Task model
            // For now, return structure with sample data
            $totalTasks = 0;
            $dueTodayCount = 0;
            $dueThisWeekCount = 0;
            $inProgressCount = 0;

            // TODO: Implement actual queries when Task model is ready
            // $totalTasks = Task::where('assigned_to', $user->id)->whereIn('status', ['todo', 'in_progress'])->count();
            // $dueTodayCount = Task::where('assigned_to', $user->id)->whereDate('due_date', today())->count();
            // $dueThisWeekCount = Task::where('assigned_to', $user->id)->whereBetween('due_date', [now(), now()->addWeek()])->count();
            // $inProgressCount = Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count();

            return [
                'total' => $totalTasks,
                'due_today' => $dueTodayCount,
                'due_this_week' => $dueThisWeekCount,
                'in_progress' => $inProgressCount,
                'show_more_url' => route('project.tasks.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'total' => 0,
            'due_today' => 0,
            'due_this_week' => 0,
            'in_progress' => 0,
            'message' => 'No tasks assigned',
        ];
    }
}
