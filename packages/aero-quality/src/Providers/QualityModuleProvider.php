<?php

namespace Aero\Quality\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\DashboardRegistry;
use Aero\Quality\Contracts\NcrBlockingServiceInterface;
use Aero\Quality\Services\NcrBlockingService;

class QualityModuleProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'quality';

    protected string $moduleName = 'Quality Management';

    protected string $moduleDescription = 'Quality assurance, inspections, and non-conformance tracking';

    protected string $moduleVersion = '1.0.0';

    protected int $modulePriority = 19;

    protected array $dependencies = ['core'];

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Register any application services.
     */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Register navigation
        $this->registerNavigation();

        // Register dashboards
        $this->registerDashboards();

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/module.php' => config_path('quality.php'),
            ], 'quality-config');
        }
    }

    /**
     * Register Quality dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            'quality.dashboard',
            'Quality Dashboard',
            'quality',
            'Quality metrics, inspections, and NCR tracking',
            'ChartBarIcon',
            'quality.dashboard.view'
        );
    }

    /**
     * Get module navigation items for sidebar.
     */
    public function getNavigationItems(): array
    {
        return [
            [
                'label' => 'Quality Dashboard',
                'icon' => 'ChartBarIcon',
                'route' => 'quality.dashboard',
                'permission' => 'quality.dashboard.view',
            ],
            [
                'label' => 'Inspections',
                'icon' => 'ClipboardDocumentCheckIcon',
                'route' => 'quality.inspections.index',
                'permission' => 'quality.inspections.view',
            ],
            [
                'label' => 'Non-Conformances',
                'icon' => 'ExclamationTriangleIcon',
                'route' => 'quality.ncrs.index',
                'permission' => 'quality.ncrs.view',
            ],
            [
                'label' => 'Quality Standards',
                'icon' => 'DocumentCheckIcon',
                'route' => 'quality.standards.index',
                'permission' => 'quality.standards.view',
            ],
        ];
    }

    /**
     * Get module hierarchy structure for ModuleRegistry.
     */
    public function getModuleHierarchy(): array
    {
        return [
            [
                'code' => 'inspections',
                'name' => 'Quality Inspections',
                'description' => 'Manage quality inspections and checklists',
                'components' => [
                    [
                        'code' => 'inspection_management',
                        'name' => 'Inspection Management',
                        'description' => 'Create and manage quality inspections',
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Inspections', 'permission' => 'quality.inspections.view'],
                            ['code' => 'create', 'name' => 'Create Inspection', 'permission' => 'quality.inspections.create'],
                            ['code' => 'edit', 'name' => 'Edit Inspection', 'permission' => 'quality.inspections.edit'],
                            ['code' => 'delete', 'name' => 'Delete Inspection', 'permission' => 'quality.inspections.delete'],
                            ['code' => 'approve', 'name' => 'Approve Inspection', 'permission' => 'quality.inspections.approve'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'ncr',
                'name' => 'Non-Conformance Reports',
                'description' => 'Track and manage non-conformances',
                'components' => [
                    [
                        'code' => 'ncr_management',
                        'name' => 'NCR Management',
                        'description' => 'Manage non-conformance reports',
                        'actions' => [
                            ['code' => 'view', 'name' => 'View NCRs', 'permission' => 'quality.ncrs.view'],
                            ['code' => 'create', 'name' => 'Create NCR', 'permission' => 'quality.ncrs.create'],
                            ['code' => 'edit', 'name' => 'Edit NCR', 'permission' => 'quality.ncrs.edit'],
                            ['code' => 'delete', 'name' => 'Delete NCR', 'permission' => 'quality.ncrs.delete'],
                            ['code' => 'close', 'name' => 'Close NCR', 'permission' => 'quality.ncrs.close'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'standards',
                'name' => 'Quality Standards',
                'description' => 'Manage quality standards and specifications',
                'components' => [
                    [
                        'code' => 'standard_management',
                        'name' => 'Standard Management',
                        'description' => 'Define and manage quality standards',
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Standards', 'permission' => 'quality.standards.view'],
                            ['code' => 'create', 'name' => 'Create Standard', 'permission' => 'quality.standards.create'],
                            ['code' => 'edit', 'name' => 'Edit Standard', 'permission' => 'quality.standards.edit'],
                            ['code' => 'delete', 'name' => 'Delete Standard', 'permission' => 'quality.standards.delete'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
