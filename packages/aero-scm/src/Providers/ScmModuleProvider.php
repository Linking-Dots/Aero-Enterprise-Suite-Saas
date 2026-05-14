<?php

namespace Aero\Scm\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;

/**
 * SCM Module Provider
 *
 * Provides Supply Chain Management functionality including procurement,
 * logistics, production planning, and supplier management.
 */
class ScmModuleProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'scm';

    protected string $moduleName = 'Supply Chain Management';

    protected string $moduleDescription = 'Complete SCM system with procurement, logistics, production planning, and supplier management';

    protected string $moduleVersion = '1.0.0';

    protected string $moduleCategory = 'business';

    protected string $moduleIcon = 'TruckIcon';

    protected int $modulePriority = 15;

    protected bool $enabled = true;

    protected ?string $minimumPlan = 'professional';

    protected array $dependencies = ['core'];

    protected array $navigationItems = [
        [
            'code' => 'scm_procurement',
            'name' => 'Procurement',
            'icon' => 'ShoppingBagIcon',
            'route' => 'scm.procurement.index',
            'priority' => 1,
        ],
        [
            'code' => 'scm_suppliers',
            'name' => 'Suppliers',
            'icon' => 'UserGroupIcon',
            'route' => 'scm.suppliers.index',
            'priority' => 2,
        ],
        [
            'code' => 'scm_logistics',
            'name' => 'Logistics',
            'icon' => 'TruckIcon',
            'route' => 'scm.logistics.index',
            'priority' => 3,
        ],
        [
            'code' => 'scm_production',
            'name' => 'Production Planning',
            'icon' => 'CogIcon',
            'route' => 'scm.production.index',
            'priority' => 4,
        ],
    ];

    protected array $moduleHierarchy = [
        'code' => 'scm',
        'name' => 'Supply Chain Management',
        'description' => 'Supply chain and procurement management',
        'icon' => 'TruckIcon',
        'priority' => 15,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'scm',
        'sub_modules' => [
            [
                'code' => 'procurement',
                'name' => 'Procurement',
                'description' => 'Manage procurement and purchases',
                'icon' => 'ShoppingBagIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'procurement_list',
                        'name' => 'Procurement List',
                        'description' => 'View and manage procurement',
                        'route_name' => 'scm.procurement.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Procurement', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Purchase Order', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Purchase Order', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Purchase Order', 'is_active' => true],
                            ['code' => 'approve', 'name' => 'Approve Purchase Order', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'suppliers',
                'name' => 'Supplier Management',
                'description' => 'Manage suppliers and vendors',
                'icon' => 'UserGroupIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'supplier_list',
                        'name' => 'Supplier List',
                        'description' => 'View and manage suppliers',
                        'route_name' => 'scm.suppliers.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Suppliers', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Supplier', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Supplier', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Supplier', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'logistics',
                'name' => 'Logistics',
                'description' => 'Manage logistics and shipping',
                'icon' => 'TruckIcon',
                'priority' => 3,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'logistics_list',
                        'name' => 'Logistics Management',
                        'description' => 'View and manage logistics',
                        'route_name' => 'scm.logistics.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Logistics', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Shipment', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Shipment', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Shipment', 'is_active' => true],
                            ['code' => 'track', 'name' => 'Track Shipment', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'production',
                'name' => 'Production Planning',
                'description' => 'Plan and manage production',
                'icon' => 'CogIcon',
                'priority' => 4,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'production_plans',
                        'name' => 'Production Plans',
                        'description' => 'View and manage production plans',
                        'route_name' => 'scm.production.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Production Plans', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Production Plan', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Production Plan', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Production Plan', 'is_active' => true],
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
        // Register SCM-specific services here when needed
    }

    protected function bootModule(): void
    {
        // Register module-specific middleware, policies, etc.
    }}
