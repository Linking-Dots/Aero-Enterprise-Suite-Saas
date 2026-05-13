<?php

namespace Aero\Project\Providers;

use Aero\Core\Providers\AbstractModuleProvider;
use Aero\Core\Services\ModuleRegistry;

/**
 * Project Module Provider
 *
 * Provides Project Management functionality including projects, tasks, milestones,
 * time tracking, resource allocation, budgets, and Gantt charts.
 */
class ProjectModuleProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'project';

    protected string $moduleName = 'Project Management';

    protected string $moduleDescription = 'Complete project management with tasks, milestones, time tracking, resources, and Gantt charts';

    protected string $moduleVersion = '1.0.0';

    protected string $moduleCategory = 'business';

    protected string $moduleIcon = 'BriefcaseIcon';

    protected int $modulePriority = 13;

    protected bool $enabled = true;

    protected ?string $minimumPlan = 'professional';

    protected array $dependencies = ['core'];

    protected array $navigationItems = [
        [
            'code' => 'project_dashboard',
            'name' => 'Projects',
            'icon' => 'BriefcaseIcon',
            'route' => 'projects.index',
            'priority' => 1,
        ],
        [
            'code' => 'project_tasks',
            'name' => 'Tasks',
            'icon' => 'ClipboardListIcon',
            'route' => 'projects.tasks.index',
            'priority' => 2,
        ],
        [
            'code' => 'project_gantt',
            'name' => 'Gantt Chart',
            'icon' => 'ChartBarIcon',
            'route' => 'projects.gantt',
            'priority' => 3,
        ],
        [
            'code' => 'project_time_tracking',
            'name' => 'Time Tracking',
            'icon' => 'ClockIcon',
            'route' => 'projects.time-tracking.index',
            'priority' => 4,
        ],
    ];

    protected array $moduleHierarchy = [
        'code' => 'project',
        'name' => 'Project Management',
        'description' => 'Project planning and execution',
        'icon' => 'BriefcaseIcon',
        'priority' => 13,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'projects',
        'sub_modules' => [
            [
                'code' => 'projects',
                'name' => 'Projects',
                'description' => 'Manage projects',
                'icon' => 'BriefcaseIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'project_list',
                        'name' => 'Project List',
                        'description' => 'View and manage projects',
                        'route_name' => 'projects.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Projects', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Project', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Project', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Project', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'tasks',
                'name' => 'Tasks',
                'description' => 'Task management',
                'icon' => 'ClipboardListIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'task_list',
                        'name' => 'Task List',
                        'description' => 'View and manage tasks',
                        'route_name' => 'projects.tasks.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Tasks', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Task', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Task', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Task', 'is_active' => true],
                            ['code' => 'assign', 'name' => 'Assign Task', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'milestones',
                'name' => 'Milestones',
                'description' => 'Project milestones',
                'icon' => 'FlagIcon',
                'priority' => 3,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'milestone_list',
                        'name' => 'Milestone List',
                        'description' => 'View and manage milestones',
                        'route_name' => 'projects.milestones.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Milestones', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Milestone', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Milestone', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Milestone', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'time_tracking',
                'name' => 'Time Tracking',
                'description' => 'Track time spent on projects',
                'icon' => 'ClockIcon',
                'priority' => 4,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'time_entries',
                        'name' => 'Time Entries',
                        'description' => 'Log and view time entries',
                        'route_name' => 'projects.time-tracking.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Time Entries', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Log Time', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Time Entry', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Time Entry', 'is_active' => true],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    protected function registerServices(): void
    {
        // Register Project-specific services here when needed
    }

    protected function bootModule(): void
    {
        // Register module-specific middleware, policies, etc.
    }

    public function register(): void
    {
        parent::register();
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->register($this);
    }

    public function boot(): void
    {
        parent::boot();
        $this->registerNavigation();
    }
}
