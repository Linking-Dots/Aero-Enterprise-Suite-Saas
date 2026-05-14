<?php

namespace Aero\Ims\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\ModuleRegistry;

/**
 * IMS Module Provider
 *
 * Provides Inventory Management System functionality including stock management,
 * item tracking, and warehouse management.
 */
class ImsModuleProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'ims';

    protected string $moduleName = 'Inventory Management';

    protected string $moduleDescription = 'Complete inventory management system with stock tracking, item management, and warehouse operations';

    protected string $moduleVersion = '1.0.0';

    protected string $moduleCategory = 'business';

    protected string $moduleIcon = 'CubeIcon';

    protected int $modulePriority = 16;

    protected bool $enabled = true;

    protected ?string $minimumPlan = 'professional';

    protected array $dependencies = ['core'];

    protected array $navigationItems = [
        [
            'code' => 'ims_inventory',
            'name' => 'Inventory',
            'icon' => 'CubeIcon',
            'route' => 'ims.inventory.index',
            'priority' => 1,
        ],
        [
            'code' => 'ims_warehouses',
            'name' => 'Warehouses',
            'icon' => 'BuildingStorefrontIcon',
            'route' => 'ims.warehouses.index',
            'priority' => 2,
        ],
        [
            'code' => 'ims_stock_movements',
            'name' => 'Stock Movements',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => 'ims.stock-movements.index',
            'priority' => 3,
        ],
    ];

    protected array $moduleHierarchy = [
        'code' => 'ims',
        'name' => 'Inventory Management',
        'description' => 'Inventory and stock management',
        'icon' => 'CubeIcon',
        'priority' => 16,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'ims',
        'sub_modules' => [
            [
                'code' => 'inventory',
                'name' => 'Inventory Items',
                'description' => 'Manage inventory items',
                'icon' => 'CubeIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'inventory_list',
                        'name' => 'Inventory List',
                        'description' => 'View and manage inventory items',
                        'route_name' => 'ims.inventory.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Inventory', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Item', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Item', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Item', 'is_active' => true],
                            ['code' => 'adjust_stock', 'name' => 'Adjust Stock', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'warehouses',
                'name' => 'Warehouse Management',
                'description' => 'Manage warehouses and locations',
                'icon' => 'BuildingStorefrontIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'warehouse_list',
                        'name' => 'Warehouse List',
                        'description' => 'View and manage warehouses',
                        'route_name' => 'ims.warehouses.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Warehouses', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Warehouse', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Warehouse', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Warehouse', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'stock_movements',
                'name' => 'Stock Movements',
                'description' => 'Track stock movements',
                'icon' => 'ArrowsRightLeftIcon',
                'priority' => 3,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'movement_history',
                        'name' => 'Movement History',
                        'description' => 'View stock movement history',
                        'route_name' => 'ims.stock-movements.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Movements', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Movement', 'is_active' => true],
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
        // Register IMS-specific services here when needed
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
}
