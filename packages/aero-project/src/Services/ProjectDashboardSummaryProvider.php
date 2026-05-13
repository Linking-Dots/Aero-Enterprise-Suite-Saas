<?php

declare(strict_types=1);

namespace Aero\Project\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\Project\Models\Project;
use Aero\Project\Models\ProjectTask;

class ProjectDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'in_progress')->count();
        $overdueTasks = ProjectTask::where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();
        $openTasks = ProjectTask::whereNotIn('status', ['completed', 'cancelled'])->count();

        $alerts = [];
        if ($overdueTasks > 0) {
            $alerts[] = "{$overdueTasks} overdue tasks";
        }

        return [
            'key' => 'project',
            'label' => 'Projects',
            'icon' => 'BriefcaseIcon',
            'route' => 'tenant.projects.index',
            'stats' => [
                'projects' => $totalProjects,
                'active' => $activeProjects,
                'openTasks' => $openTasks,
                'overdueTasks' => $overdueTasks,
            ],
            'alerts' => $alerts,
            'pendingCount' => $overdueTasks,
        ];
    }
}
