<?php

namespace Aero\Finance\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\ModuleRegistry;

/**
 * Finance Module Provider
 *
 * Provides Financial Management functionality including chart of accounts,
 * general ledger, accounts payable/receivable, and journal entries.
 */
class FinanceModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code.
     */
    protected string $moduleCode = 'finance';

    /**
     * Module display name.
     */
    protected string $moduleName = 'Finance';

    /**
     * Module description.
     */
    protected string $moduleDescription = 'Financial management system with chart of accounts, general ledger, AP/AR, and journal entries';

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
    protected string $moduleIcon = 'CurrencyDollarIcon';

    /**
     * Module priority.
     */
    protected int $modulePriority = 12;

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
     * Navigation items for Finance module.
     */
    protected array $navigationItems = [
        [
            'code' => 'finance_dashboard',
            'name' => 'Finance Dashboard',
            'icon' => 'ChartBarIcon',
            'route' => 'finance.dashboard',
            'priority' => 1,
        ],
        [
            'code' => 'finance_chart_of_accounts',
            'name' => 'Chart of Accounts',
            'icon' => 'DocumentTextIcon',
            'route' => 'finance.chart-of-accounts.index',
            'priority' => 2,
        ],
        [
            'code' => 'finance_general_ledger',
            'name' => 'General Ledger',
            'icon' => 'BookOpenIcon',
            'route' => 'finance.general-ledger.index',
            'priority' => 3,
        ],
        [
            'code' => 'finance_journal_entries',
            'name' => 'Journal Entries',
            'icon' => 'PencilIcon',
            'route' => 'finance.journal-entries.index',
            'priority' => 4,
        ],
        [
            'code' => 'finance_accounts_payable',
            'name' => 'Accounts Payable',
            'icon' => 'ArrowDownIcon',
            'route' => 'finance.accounts-payable.index',
            'priority' => 5,
        ],
        [
            'code' => 'finance_accounts_receivable',
            'name' => 'Accounts Receivable',
            'icon' => 'ArrowUpIcon',
            'route' => 'finance.accounts-receivable.index',
            'priority' => 6,
        ],
    ];

    /**
     * Module hierarchy.
     */
    protected array $moduleHierarchy = [
        'code' => 'finance',
        'name' => 'Finance',
        'description' => 'Financial management system',
        'icon' => 'CurrencyDollarIcon',
        'priority' => 12,
        'is_active' => true,
        'requires_subscription' => true,
        'route_prefix' => 'finance',
        'sub_modules' => [
            [
                'code' => 'chart_of_accounts',
                'name' => 'Chart of Accounts',
                'description' => 'Manage account structure and classification',
                'icon' => 'DocumentTextIcon',
                'priority' => 1,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'account_list',
                        'name' => 'Account List',
                        'description' => 'View and manage accounts',
                        'route_name' => 'finance.chart-of-accounts.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Accounts', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Account', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Account', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Account', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'general_ledger',
                'name' => 'General Ledger',
                'description' => 'View general ledger and account balances',
                'icon' => 'BookOpenIcon',
                'priority' => 2,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'ledger_view',
                        'name' => 'Ledger View',
                        'description' => 'View general ledger entries',
                        'route_name' => 'finance.general-ledger.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Ledger', 'is_active' => true],
                            ['code' => 'export', 'name' => 'Export Ledger', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'journal_entries',
                'name' => 'Journal Entries',
                'description' => 'Create and manage journal entries',
                'icon' => 'PencilIcon',
                'priority' => 3,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'journal_entry_list',
                        'name' => 'Journal Entry List',
                        'description' => 'View and manage journal entries',
                        'route_name' => 'finance.journal-entries.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Journal Entries', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Journal Entry', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Journal Entry', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Journal Entry', 'is_active' => true],
                            ['code' => 'post', 'name' => 'Post Journal Entry', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'accounts_payable',
                'name' => 'Accounts Payable',
                'description' => 'Manage vendor payments and payables',
                'icon' => 'ArrowDownIcon',
                'priority' => 4,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'payables_list',
                        'name' => 'Payables List',
                        'description' => 'View and manage accounts payable',
                        'route_name' => 'finance.accounts-payable.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Payables', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Payable', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Payable', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Payable', 'is_active' => true],
                            ['code' => 'pay', 'name' => 'Process Payment', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'accounts_receivable',
                'name' => 'Accounts Receivable',
                'description' => 'Manage customer payments and receivables',
                'icon' => 'ArrowUpIcon',
                'priority' => 5,
                'is_active' => true,
                'components' => [
                    [
                        'code' => 'receivables_list',
                        'name' => 'Receivables List',
                        'description' => 'View and manage accounts receivable',
                        'route_name' => 'finance.accounts-receivable.index',
                        'priority' => 1,
                        'is_active' => true,
                        'actions' => [
                            ['code' => 'view', 'name' => 'View Receivables', 'is_active' => true],
                            ['code' => 'create', 'name' => 'Create Receivable', 'is_active' => true],
                            ['code' => 'edit', 'name' => 'Edit Receivable', 'is_active' => true],
                            ['code' => 'delete', 'name' => 'Delete Receivable', 'is_active' => true],
                            ['code' => 'receive', 'name' => 'Record Payment', 'is_active' => true],
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
        // Register Finance-specific services here when needed
    }

    /**
     * Boot Finance module.
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
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->register($this);
    }
}
