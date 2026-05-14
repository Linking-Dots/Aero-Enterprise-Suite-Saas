<?php

namespace Aero\Pos\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Pos\Services\POSService;

/**
 * POS Module Provider
 *
 * Provides Point of Sale functionality including sales processing,
 * inventory integration, receipt generation, and payment processing.
 */
class PosModuleProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'pos';

    protected string $moduleName = 'Point of Sale';

    protected string $moduleDescription = 'Complete POS system with sales processing, inventory integration, and payment management';

    protected string $moduleVersion = '1.0.0';

    protected string $moduleCategory = 'business';

    protected string $moduleIcon = 'ShoppingCartIcon';

    protected int $modulePriority = 14;

    protected bool $enabled = true;

    protected ?string $minimumPlan = 'professional';

    protected array $dependencies = ['core'];

    protected array $navigationItems = [
        [
            'code' => 'pos_sales',
            'name' => 'POS',
            'icon' => 'ShoppingCartIcon',
            'route' => 'pos.index',
            'priority' => 1,
        ],
        [
            'code' => 'pos_sales_history',
            'name' => 'Sales History',
            'icon' => 'ClipboardListIcon',
            'route' => 'pos.sales.index',
            'priority' => 2,
        ],
    ];

    protected array $moduleHierarchy = [
        'code' => 'pos',
        'name' => 'Point of Sale',
        'description' => 'Point of sale and sales management',
        'icon' => 'ShoppingCartIcon',
        'priority' => 14,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'pos',
        'sub_modules' => [
            [
                'code' => 'sales',
                'name' => 'Sales',
                'description' => 'Process sales and transactions',
                'icon' => 'ShoppingCartIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'pos_terminal',
                        'name' => 'POS Terminal',
                        'description' => 'Process sales transactions',
                        'route_name' => 'pos.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View POS', 'is_active' => true],
                            ['code' => 'process_sale', 'name' => 'Process Sale', 'is_active' => true],
                            ['code' => 'process_return', 'name' => 'Process Return', 'is_active' => true],
                        ],
                    ],
                    [
                        'code' => 'sales_list',
                        'name' => 'Sales History',
                        'description' => 'View and manage sales',
                        'route_name' => 'pos.sales.index',
                        'priority' => 2,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Sales', 'is_active' => true],
                            ['code' => 'view_details', 'name' => 'View Sale Details', 'is_active' => true],
                            ['code' => 'print_receipt', 'name' => 'Print Receipt', 'is_active' => true],
                            ['code' => 'process_refund', 'name' => 'Process Refund', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'payments',
                'name' => 'Payments',
                'description' => 'Payment processing and management',
                'icon' => 'CreditCardIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'payment_methods',
                        'name' => 'Payment Methods',
                        'description' => 'Manage payment methods',
                        'route_name' => 'pos.payments.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Payment Methods', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Add Payment Method', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Payment Method', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Payment Method', 'is_active' => true],
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
        // Register POS service
        $this->app->singleton(POSService::class);
    }

    protected function bootModule(): void
    {
        // Register module-specific middleware, policies, etc.
    }}
