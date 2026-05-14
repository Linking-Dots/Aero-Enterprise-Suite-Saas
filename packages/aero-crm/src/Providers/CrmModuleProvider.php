<?php

namespace Aero\Crm\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;

/**
 * CRM Module Provider
 *
 * Provides Customer Relationship Management functionality including
 * deals, opportunities, pipelines, and customer management.
 */
class CrmModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code.
     */
    protected string $moduleCode = 'crm';

    /**
     * Module display name.
     */
    protected string $moduleName = 'CRM';

    /**
     * Module description.
     */
    protected string $moduleDescription = 'Customer Relationship Management with deals, opportunities, pipelines, and customer management';

    /**
     * Module version.
     */
    protected string $moduleVersion = '1.0.0';

    /**
     * Module category.
     */
    protected string $moduleCategory = 'business';

    /**
     * Module icon.
     */
    protected string $moduleIcon = 'UserGroupIcon';

    /**
     * Module priority.
     */
    protected int $modulePriority = 11;

    /**
     * Module is enabled by default.
     */
    protected bool $enabled = true;

    /**
     * Minimum plan required.
     */
    protected ?string $minimumPlan = 'professional';

    /**
     * Module dependencies.
     */
    protected array $dependencies = ['core'];

    /**
     * Navigation items for CRM module.
     */
    protected array $navigationItems = [
        [
            'code' => 'crm_customers',
            'name' => 'Customers',
            'icon' => 'UserIcon',
            'route' => 'crm.customers.index',
            'priority' => 1,
        ],
        [
            'code' => 'crm_deals',
            'name' => 'Deals',
            'icon' => 'CurrencyDollarIcon',
            'route' => 'crm.deals.index',
            'priority' => 2,
        ],
        [
            'code' => 'crm_opportunities',
            'name' => 'Opportunities',
            'icon' => 'LightBulbIcon',
            'route' => 'crm.opportunities.index',
            'priority' => 3,
        ],
        [
            'code' => 'crm_pipelines',
            'name' => 'Pipelines',
            'icon' => 'ChartBarIcon',
            'route' => 'crm.pipelines.index',
            'priority' => 4,
        ],
    ];

    /**
     * Module hierarchy.
     */
    protected array $moduleHierarchy = [
        'code' => 'crm',
        'name' => 'CRM',
        'description' => 'Customer Relationship Management',
        'icon' => 'UserGroupIcon',
        'priority' => 11,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'crm',
        'sub_modules' => [
            [
                'code' => 'customers',
                'name' => 'Customers',
                'description' => 'Manage customer information',
                'icon' => 'UserIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'customer_list',
                        'name' => 'Customer List',
                        'description' => 'View and manage customers',
                        'route_name' => 'crm.customers.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Customers', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Customer', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Customer', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Customer', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'deals',
                'name' => 'Deals',
                'description' => 'Manage sales deals',
                'icon' => 'CurrencyDollarIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'deal_list',
                        'name' => 'Deal List',
                        'description' => 'View and manage deals',
                        'route_name' => 'crm.deals.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Deals', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Deal', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Deal', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Deal', 'is_active' => true],
                            ['code' => 'close', 'name' => 'Close Deal', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'pipelines',
                'name' => 'Pipelines',
                'description' => 'Manage sales pipelines',
                'icon' => 'ChartBarIcon',
                'priority' => 3,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'pipeline_list',
                        'name' => 'Pipeline List',
                        'description' => 'View and manage pipelines',
                        'route_name' => 'crm.pipelines.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Pipelines', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Pipeline', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Pipeline', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Pipeline', 'is_active' => true],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register CRM services
        $this->app->singleton(\Aero\Crm\Services\CRMService::class);
        $this->app->singleton(\Aero\Crm\Services\PipelineService::class);
    }

    /**
     * Boot CRM module.
     */
    protected function bootModule(): void
    {
        // Register module-specific middleware, policies, etc.
    }

    /**
     * Register this module with the ModuleRegistry.
     */
    public function register(): void
    {
        parent::register();

        // Register this module with the registry
        $registry = $this->app->make(\Aero\Core\Services\ModuleRegistry::class);
        $registry->register($this);
    }
}
