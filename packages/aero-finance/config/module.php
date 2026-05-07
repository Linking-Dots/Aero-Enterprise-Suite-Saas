<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Finance Module Configuration
    |--------------------------------------------------------------------------
    | Hierarchy: Module → SubModule → Component → Action
    | Scope: tenant. EAM-relevant: Fixed Assets register, Depreciation,
    | CapEx/OpEx, Asset Insurance, Cost Centers tied to maintenance.
    */

    'code' => 'finance',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Finance & Accounting',
    'description' => 'Full-suite finance: GL, AP/AR, cash, banking, fixed assets & depreciation, budgets, taxation, treasury, and statutory reporting',
    'icon' => 'CurrencyDollarIcon',
    'route_prefix' => '/finance',
    'category' => 'business',
    'priority' => 12,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('FINANCE_MODULE_ENABLED', true),
    'version' => '2.0.0',
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'self_service' => [
        ['code' => 'my-invoices',  'name' => 'My Invoices',  'icon' => 'DocumentTextIcon', 'route' => '/finance/my-invoices',  'priority' => 1],
        ['code' => 'my-payments',  'name' => 'My Payments',  'icon' => 'BanknotesIcon',    'route' => '/finance/my-payments',  'priority' => 2],
    ],

    'submodules' => [

        /* GROUP 1 — DASHBOARD */
        [
            'code' => 'dashboard', 'name' => 'Finance Dashboard',
            'description' => 'Financial KPIs, cash position, AR aging, AP, P&L snapshot',
            'icon' => 'HomeIcon', 'route' => '/finance/dashboard', 'priority' => 1,
            'components' => [
                ['code' => 'finance-dashboard', 'name' => 'Finance Dashboard', 'type' => 'page', 'route' => '/finance/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
                ['code' => 'cfo-dashboard', 'name' => 'CFO Dashboard', 'type' => 'page', 'route' => '/finance/cfo',
                    'actions' => [['code' => 'view', 'name' => 'View CFO Dashboard']]],
            ],
        ],

        /* GROUP 2 — CHART OF ACCOUNTS */
        [
            'code' => 'chart-of-accounts', 'name' => 'Chart of Accounts',
            'description' => 'Accounts hierarchy, account groups, segments, and templates',
            'icon' => 'RectangleStackIcon', 'route' => '/finance/coa', 'priority' => 2,
            'components' => [
                ['code' => 'accounts', 'name' => 'Accounts', 'type' => 'page', 'route' => '/finance/coa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Accounts'],
                        ['code' => 'create', 'name' => 'Create Account'],
                        ['code' => 'update', 'name' => 'Update Account'],
                        ['code' => 'delete', 'name' => 'Delete Account'],
                        ['code' => 'archive', 'name' => 'Archive Account'],
                        ['code' => 'import', 'name' => 'Import COA'],
                        ['code' => 'export', 'name' => 'Export COA'],
                    ]],
                ['code' => 'account-groups', 'name' => 'Account Groups', 'type' => 'page', 'route' => '/finance/coa/groups',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Groups']]],
                ['code' => 'account-segments', 'name' => 'Account Segments', 'type' => 'page', 'route' => '/finance/coa/segments',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Segments']]],
            ],
        ],

        /* GROUP 3 — GENERAL LEDGER */
        [
            'code' => 'general-ledger', 'name' => 'General Ledger',
            'description' => 'Journal entries, recurring entries, period close, and trial balance',
            'icon' => 'BookOpenIcon', 'route' => '/finance/gl', 'priority' => 3,
            'components' => [
                ['code' => 'journal-entries', 'name' => 'Journal Entries', 'type' => 'page', 'route' => '/finance/gl/journals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Journals'],
                        ['code' => 'create', 'name' => 'Create Journal'],
                        ['code' => 'update', 'name' => 'Update Journal'],
                        ['code' => 'delete', 'name' => 'Delete Journal'],
                        ['code' => 'post', 'name' => 'Post Journal'],
                        ['code' => 'reverse', 'name' => 'Reverse Journal'],
                        ['code' => 'approve', 'name' => 'Approve Journal'],
                        ['code' => 'reject', 'name' => 'Reject Journal'],
                    ]],
                ['code' => 'recurring-journals', 'name' => 'Recurring Journals', 'type' => 'page', 'route' => '/finance/gl/recurring',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Recurring Journals']]],
                ['code' => 'trial-balance', 'name' => 'Trial Balance', 'type' => 'page', 'route' => '/finance/gl/trial-balance',
                    'actions' => [['code' => 'view', 'name' => 'View Trial Balance'], ['code' => 'export', 'name' => 'Export Trial Balance']]],
                ['code' => 'period-close', 'name' => 'Period Close', 'type' => 'page', 'route' => '/finance/gl/period-close',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Period Status'],
                        ['code' => 'open', 'name' => 'Open Period'],
                        ['code' => 'close', 'name' => 'Close Period'],
                        ['code' => 'reopen', 'name' => 'Reopen Period'],
                    ]],
                ['code' => 'gl-inquiry', 'name' => 'GL Inquiry & Drill-Down', 'type' => 'page', 'route' => '/finance/gl/inquiry',
                    'actions' => [['code' => 'view', 'name' => 'View Inquiry']]],
            ],
        ],

        /* GROUP 4 — ACCOUNTS PAYABLE */
        [
            'code' => 'accounts-payable', 'name' => 'Accounts Payable',
            'description' => 'Vendor bills, payments, vendor master, 3-way match, and 1099/withholding',
            'icon' => 'ArrowDownTrayIcon', 'route' => '/finance/ap', 'priority' => 4,
            'components' => [
                ['code' => 'vendor-bills', 'name' => 'Vendor Bills', 'type' => 'page', 'route' => '/finance/ap/bills',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bills'],
                        ['code' => 'create', 'name' => 'Enter Bill'],
                        ['code' => 'update', 'name' => 'Update Bill'],
                        ['code' => 'delete', 'name' => 'Delete Bill'],
                        ['code' => 'approve', 'name' => 'Approve Bill'],
                        ['code' => 'pay', 'name' => 'Pay Bill'],
                        ['code' => 'three-way-match', 'name' => '3-Way Match (PO/GRN/Bill)'],
                    ]],
                ['code' => 'payments-out', 'name' => 'Outgoing Payments', 'type' => 'page', 'route' => '/finance/ap/payments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payments'],
                        ['code' => 'create', 'name' => 'Make Payment'],
                        ['code' => 'batch', 'name' => 'Run Payment Batch'],
                        ['code' => 'void', 'name' => 'Void Payment'],
                    ]],
                ['code' => 'ap-aging', 'name' => 'AP Aging', 'type' => 'page', 'route' => '/finance/ap/aging',
                    'actions' => [['code' => 'view', 'name' => 'View Aging'], ['code' => 'export', 'name' => 'Export Aging']]],
                ['code' => 'withholding-1099', 'name' => 'Withholding / 1099', 'type' => 'page', 'route' => '/finance/ap/withholding',
                    'actions' => [['code' => 'view', 'name' => 'View Withholding'], ['code' => 'generate', 'name' => 'Generate 1099/TDS Report']]],
                ['code' => 'vendor-statements', 'name' => 'Vendor Statements & Reconciliation', 'type' => 'page', 'route' => '/finance/ap/statements',
                    'actions' => [['code' => 'view', 'name' => 'View Statements'], ['code' => 'reconcile', 'name' => 'Reconcile Statement']]],
            ],
        ],

        /* GROUP 5 — ACCOUNTS RECEIVABLE */
        [
            'code' => 'accounts-receivable', 'name' => 'Accounts Receivable',
            'description' => 'Customer invoices, receipts, dunning, credit notes, and AR aging',
            'icon' => 'ArrowUpTrayIcon', 'route' => '/finance/ar', 'priority' => 5,
            'components' => [
                ['code' => 'customer-invoices', 'name' => 'Customer Invoices', 'type' => 'page', 'route' => '/finance/ar/invoices',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Invoices'],
                        ['code' => 'create', 'name' => 'Create Invoice'],
                        ['code' => 'update', 'name' => 'Update Invoice'],
                        ['code' => 'delete', 'name' => 'Delete Invoice'],
                        ['code' => 'send', 'name' => 'Send Invoice'],
                        ['code' => 'void', 'name' => 'Void Invoice'],
                        ['code' => 'recurring', 'name' => 'Setup Recurring Invoice'],
                    ]],
                ['code' => 'credit-notes', 'name' => 'Credit Notes / Refunds', 'type' => 'page', 'route' => '/finance/ar/credit-notes',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Credit Notes'],
                        ['code' => 'create', 'name' => 'Create Credit Note'],
                        ['code' => 'apply', 'name' => 'Apply Credit'],
                    ]],
                ['code' => 'receipts', 'name' => 'Receipts / Customer Payments', 'type' => 'page', 'route' => '/finance/ar/receipts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Receipts'],
                        ['code' => 'create', 'name' => 'Record Receipt'],
                        ['code' => 'allocate', 'name' => 'Allocate Receipt'],
                        ['code' => 'unallocate', 'name' => 'Unallocate Receipt'],
                    ]],
                ['code' => 'dunning', 'name' => 'Dunning & Collections', 'type' => 'page', 'route' => '/finance/ar/dunning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Dunning'],
                        ['code' => 'send-reminder', 'name' => 'Send Dunning Letter'],
                        ['code' => 'configure', 'name' => 'Configure Dunning Rules'],
                    ]],
                ['code' => 'ar-aging', 'name' => 'AR Aging', 'type' => 'page', 'route' => '/finance/ar/aging',
                    'actions' => [['code' => 'view', 'name' => 'View Aging'], ['code' => 'export', 'name' => 'Export Aging']]],
                ['code' => 'credit-management', 'name' => 'Credit Limits & Holds', 'type' => 'page', 'route' => '/finance/ar/credit',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Credit Status'],
                        ['code' => 'set-limit', 'name' => 'Set Credit Limit'],
                        ['code' => 'hold', 'name' => 'Place Credit Hold'],
                        ['code' => 'release', 'name' => 'Release Credit Hold'],
                    ]],
            ],
        ],

        /* GROUP 6 — CASH & BANKING */
        [
            'code' => 'cash-bank', 'name' => 'Cash & Banking',
            'description' => 'Bank accounts, transfers, deposits, reconciliation, and cash forecasting',
            'icon' => 'BuildingLibraryIcon', 'route' => '/finance/cash-bank', 'priority' => 6,
            'components' => [
                ['code' => 'bank-accounts', 'name' => 'Bank Accounts', 'type' => 'page', 'route' => '/finance/cash-bank/accounts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bank Accounts'],
                        ['code' => 'create', 'name' => 'Add Bank Account'],
                        ['code' => 'update', 'name' => 'Update Bank Account'],
                        ['code' => 'delete', 'name' => 'Delete Bank Account'],
                    ]],
                ['code' => 'bank-transactions', 'name' => 'Bank Transactions', 'type' => 'page', 'route' => '/finance/cash-bank/transactions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Transactions'],
                        ['code' => 'import', 'name' => 'Import Bank Statement'],
                        ['code' => 'manual', 'name' => 'Add Manual Transaction'],
                    ]],
                ['code' => 'bank-reconciliation', 'name' => 'Bank Reconciliation', 'type' => 'page', 'route' => '/finance/cash-bank/reconcile',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reconciliations'],
                        ['code' => 'run', 'name' => 'Run Reconciliation'],
                        ['code' => 'auto-match', 'name' => 'Auto-Match Transactions'],
                        ['code' => 'finalize', 'name' => 'Finalize Reconciliation'],
                    ]],
                ['code' => 'transfers', 'name' => 'Bank Transfers', 'type' => 'page', 'route' => '/finance/cash-bank/transfers',
                    'actions' => [['code' => 'create', 'name' => 'Create Transfer']]],
                ['code' => 'cash-forecast', 'name' => 'Cash Forecast', 'type' => 'page', 'route' => '/finance/cash-bank/forecast',
                    'actions' => [['code' => 'view', 'name' => 'View Forecast'], ['code' => 'generate', 'name' => 'Generate Forecast']]],
                ['code' => 'petty-cash', 'name' => 'Petty Cash', 'type' => 'page', 'route' => '/finance/cash-bank/petty-cash',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Petty Cash'],
                        ['code' => 'replenish', 'name' => 'Replenish Petty Cash'],
                        ['code' => 'expense', 'name' => 'Record Petty Expense'],
                    ]],
            ],
        ],

        /* GROUP 7 — FIXED ASSETS & DEPRECIATION (EAM CORE) */
        [
            'code' => 'fixed-assets', 'name' => 'Fixed Assets',
            'description' => 'Asset register, capitalization, depreciation, impairment, transfers, retirement, and revaluation — EAM-aligned',
            'icon' => 'BuildingOfficeIcon', 'route' => '/finance/fixed-assets', 'priority' => 7,
            'components' => [
                ['code' => 'asset-register', 'name' => 'Fixed Asset Register', 'type' => 'page', 'route' => '/finance/fixed-assets/register',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Asset Register'],
                        ['code' => 'create', 'name' => 'Add Asset'],
                        ['code' => 'update', 'name' => 'Update Asset'],
                        ['code' => 'delete', 'name' => 'Delete Asset'],
                        ['code' => 'capitalize', 'name' => 'Capitalize Asset'],
                        ['code' => 'import', 'name' => 'Import Assets'],
                        ['code' => 'export', 'name' => 'Export Asset Register'],
                        ['code' => 'tag', 'name' => 'Print Asset Tag/Barcode'],
                    ]],
                ['code' => 'asset-categories', 'name' => 'Asset Categories & Classes', 'type' => 'page', 'route' => '/finance/fixed-assets/categories',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Asset Categories']]],
                ['code' => 'depreciation', 'name' => 'Depreciation', 'type' => 'page', 'route' => '/finance/fixed-assets/depreciation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Depreciation'],
                        ['code' => 'configure', 'name' => 'Configure Depreciation Method'],
                        ['code' => 'run', 'name' => 'Run Depreciation Cycle'],
                        ['code' => 'reverse', 'name' => 'Reverse Depreciation'],
                        ['code' => 'forecast', 'name' => 'Forecast Depreciation'],
                    ]],
                ['code' => 'asset-transfers', 'name' => 'Asset Transfers (Inter-Entity / Location)', 'type' => 'page', 'route' => '/finance/fixed-assets/transfers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Transfers'],
                        ['code' => 'create', 'name' => 'Create Transfer'],
                        ['code' => 'approve', 'name' => 'Approve Transfer'],
                    ]],
                ['code' => 'asset-retirement', 'name' => 'Asset Retirement / Disposal', 'type' => 'page', 'route' => '/finance/fixed-assets/retirement',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Retirements'],
                        ['code' => 'retire', 'name' => 'Retire Asset'],
                        ['code' => 'sell', 'name' => 'Sell Asset'],
                        ['code' => 'scrap', 'name' => 'Scrap Asset'],
                    ]],
                ['code' => 'impairment-revaluation', 'name' => 'Impairment & Revaluation', 'type' => 'page', 'route' => '/finance/fixed-assets/impairment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Impairments'],
                        ['code' => 'impair', 'name' => 'Record Impairment'],
                        ['code' => 'revalue', 'name' => 'Revalue Asset'],
                    ]],
                ['code' => 'cwip', 'name' => 'CWIP (Capital Work in Progress)', 'type' => 'page', 'route' => '/finance/fixed-assets/cwip',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CWIP'],
                        ['code' => 'capitalize', 'name' => 'Transfer CWIP to Asset'],
                    ]],
                ['code' => 'asset-insurance', 'name' => 'Asset Insurance', 'type' => 'page', 'route' => '/finance/fixed-assets/insurance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Insurance Records'],
                        ['code' => 'manage', 'name' => 'Manage Insurance Policies'],
                        ['code' => 'claim', 'name' => 'File Insurance Claim'],
                    ]],
                ['code' => 'asset-lease', 'name' => 'Asset Leasing (IFRS 16 / ASC 842)', 'type' => 'page', 'route' => '/finance/fixed-assets/lease',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leases'],
                        ['code' => 'create', 'name' => 'Create Lease'],
                        ['code' => 'amortize', 'name' => 'Run Lease Amortization'],
                        ['code' => 'terminate', 'name' => 'Terminate Lease'],
                    ]],
                ['code' => 'physical-verification', 'name' => 'Physical Verification (Audit)', 'type' => 'page', 'route' => '/finance/fixed-assets/verification',
                    'actions' => [
                        ['code' => 'schedule', 'name' => 'Schedule Verification'],
                        ['code' => 'conduct', 'name' => 'Conduct Verification'],
                        ['code' => 'reconcile', 'name' => 'Reconcile Discrepancy'],
                    ]],
            ],
        ],

        /* GROUP 8 — CAPEX / OPEX */
        [
            'code' => 'capex-opex', 'name' => 'CapEx & OpEx Management',
            'description' => 'Capital expenditure budgets, project capitalization, OpEx tracking — EAM project finance',
            'icon' => 'ChartPieIcon', 'route' => '/finance/capex-opex', 'priority' => 8,
            'components' => [
                ['code' => 'capex-requests', 'name' => 'CapEx Requests', 'type' => 'page', 'route' => '/finance/capex/requests',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CapEx Requests'],
                        ['code' => 'create', 'name' => 'Create CapEx Request'],
                        ['code' => 'update', 'name' => 'Update CapEx Request'],
                        ['code' => 'approve', 'name' => 'Approve CapEx'],
                        ['code' => 'reject', 'name' => 'Reject CapEx'],
                    ]],
                ['code' => 'capex-projects', 'name' => 'CapEx Projects', 'type' => 'page', 'route' => '/finance/capex/projects',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CapEx Projects'],
                        ['code' => 'manage', 'name' => 'Manage CapEx Project'],
                        ['code' => 'capitalize', 'name' => 'Capitalize Project'],
                    ]],
                ['code' => 'opex-tracking', 'name' => 'OpEx Tracking', 'type' => 'page', 'route' => '/finance/opex/tracking',
                    'actions' => [['code' => 'view', 'name' => 'View OpEx'], ['code' => 'export', 'name' => 'Export OpEx']]],
                ['code' => 'capex-vs-actual', 'name' => 'CapEx vs Actual Variance', 'type' => 'page', 'route' => '/finance/capex/variance',
                    'actions' => [['code' => 'view', 'name' => 'View Variance']]],
            ],
        ],

        /* GROUP 9 — BUDGETING & PLANNING */
        [
            'code' => 'budgeting', 'name' => 'Budgeting & Planning',
            'description' => 'Annual budgets, forecasts, scenario planning, and budget vs actual',
            'icon' => 'ChartBarSquareIcon', 'route' => '/finance/budgeting', 'priority' => 9,
            'components' => [
                ['code' => 'budgets', 'name' => 'Budgets', 'type' => 'page', 'route' => '/finance/budgeting/budgets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Budgets'],
                        ['code' => 'create', 'name' => 'Create Budget'],
                        ['code' => 'update', 'name' => 'Update Budget'],
                        ['code' => 'approve', 'name' => 'Approve Budget'],
                        ['code' => 'lock', 'name' => 'Lock Budget'],
                        ['code' => 'copy', 'name' => 'Copy Budget'],
                    ]],
                ['code' => 'forecasts', 'name' => 'Rolling Forecasts', 'type' => 'page', 'route' => '/finance/budgeting/forecasts',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Forecasts']]],
                ['code' => 'scenarios', 'name' => 'Scenario Planning', 'type' => 'page', 'route' => '/finance/budgeting/scenarios',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Scenarios']]],
                ['code' => 'variance', 'name' => 'Budget vs Actual', 'type' => 'page', 'route' => '/finance/budgeting/variance',
                    'actions' => [['code' => 'view', 'name' => 'View Variance'], ['code' => 'export', 'name' => 'Export Variance']]],
            ],
        ],

        /* GROUP 10 — COST ACCOUNTING & ALLOCATIONS */
        [
            'code' => 'cost-accounting', 'name' => 'Cost Accounting',
            'description' => 'Cost centers, profit centers, allocations, ABC, and standard costing',
            'icon' => 'Squares2X2Icon', 'route' => '/finance/cost-accounting', 'priority' => 10,
            'components' => [
                ['code' => 'cost-centers', 'name' => 'Cost Centers', 'type' => 'page', 'route' => '/finance/cost-accounting/cost-centers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cost Centers']]],
                ['code' => 'profit-centers', 'name' => 'Profit Centers', 'type' => 'page', 'route' => '/finance/cost-accounting/profit-centers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Profit Centers']]],
                ['code' => 'allocations', 'name' => 'Cost Allocations', 'type' => 'page', 'route' => '/finance/cost-accounting/allocations',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Allocation Rules'], ['code' => 'run', 'name' => 'Run Allocation']]],
                ['code' => 'activity-based-costing', 'name' => 'Activity-Based Costing', 'type' => 'page', 'route' => '/finance/cost-accounting/abc',
                    'actions' => [['code' => 'manage', 'name' => 'Manage ABC']]],
                ['code' => 'standard-costing', 'name' => 'Standard Costing', 'type' => 'page', 'route' => '/finance/cost-accounting/standard',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Standard Costs'], ['code' => 'variance', 'name' => 'Calculate Variances']]],
            ],
        ],

        /* GROUP 11 — TAXATION */
        [
            'code' => 'taxation', 'name' => 'Taxation',
            'description' => 'Tax codes, returns (GST/VAT/Sales Tax), withholding, and statutory filings',
            'icon' => 'ReceiptPercentIcon', 'route' => '/finance/tax', 'priority' => 11,
            'components' => [
                ['code' => 'tax-codes', 'name' => 'Tax Codes / Rates', 'type' => 'page', 'route' => '/finance/tax/codes',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tax Codes']]],
                ['code' => 'tax-returns', 'name' => 'Tax Returns (GST/VAT/Sales)', 'type' => 'page', 'route' => '/finance/tax/returns',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Returns'],
                        ['code' => 'prepare', 'name' => 'Prepare Return'],
                        ['code' => 'file', 'name' => 'File Return'],
                        ['code' => 'amend', 'name' => 'Amend Return'],
                    ]],
                ['code' => 'withholding', 'name' => 'Withholding (TDS / 1099)', 'type' => 'page', 'route' => '/finance/tax/withholding',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Withholding']]],
                ['code' => 'tax-jurisdictions', 'name' => 'Tax Jurisdictions', 'type' => 'page', 'route' => '/finance/tax/jurisdictions',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Jurisdictions']]],
                ['code' => 'e-invoice', 'name' => 'E-Invoicing / E-Way Bill', 'type' => 'page', 'route' => '/finance/tax/e-invoice',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View E-Invoices'],
                        ['code' => 'generate', 'name' => 'Generate E-Invoice'],
                        ['code' => 'cancel', 'name' => 'Cancel E-Invoice'],
                    ]],
            ],
        ],

        /* GROUP 12 — TREASURY & FX */
        [
            'code' => 'treasury', 'name' => 'Treasury & FX',
            'description' => 'Multi-currency, FX rates, FX revaluation, hedging, intercompany',
            'icon' => 'GlobeAltIcon', 'route' => '/finance/treasury', 'priority' => 12,
            'components' => [
                ['code' => 'currencies', 'name' => 'Currencies', 'type' => 'page', 'route' => '/finance/treasury/currencies',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Currencies']]],
                ['code' => 'exchange-rates', 'name' => 'Exchange Rates', 'type' => 'page', 'route' => '/finance/treasury/rates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rates'],
                        ['code' => 'manual', 'name' => 'Enter Manual Rate'],
                        ['code' => 'sync', 'name' => 'Sync Rates from Provider'],
                    ]],
                ['code' => 'fx-revaluation', 'name' => 'FX Revaluation', 'type' => 'page', 'route' => '/finance/treasury/revaluation',
                    'actions' => [['code' => 'run', 'name' => 'Run Revaluation']]],
                ['code' => 'hedging', 'name' => 'Hedging Contracts', 'type' => 'page', 'route' => '/finance/treasury/hedging',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Hedges']]],
                ['code' => 'intercompany', 'name' => 'Intercompany Transactions', 'type' => 'page', 'route' => '/finance/treasury/intercompany',
                    'actions' => [['code' => 'view', 'name' => 'View IC Transactions'], ['code' => 'eliminate', 'name' => 'Run Elimination']]],
            ],
        ],

        /* GROUP 13 — EXPENSE MANAGEMENT */
        [
            'code' => 'expense-management', 'name' => 'Expense Management',
            'description' => 'Employee expenses, corporate cards, mileage, and expense policies',
            'icon' => 'ReceiptRefundIcon', 'route' => '/finance/expenses', 'priority' => 13,
            'components' => [
                ['code' => 'expense-claims', 'name' => 'Expense Claims', 'type' => 'page', 'route' => '/finance/expenses/claims',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Claims'],
                        ['code' => 'approve', 'name' => 'Approve Claim'],
                        ['code' => 'reimburse', 'name' => 'Reimburse Claim'],
                    ]],
                ['code' => 'corporate-cards', 'name' => 'Corporate Cards', 'type' => 'page', 'route' => '/finance/expenses/cards',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cards'], ['code' => 'reconcile', 'name' => 'Reconcile Statement']]],
                ['code' => 'mileage', 'name' => 'Mileage Tracking', 'type' => 'page', 'route' => '/finance/expenses/mileage',
                    'actions' => [['code' => 'view', 'name' => 'View Mileage'], ['code' => 'configure', 'name' => 'Configure Rate']]],
                ['code' => 'policies', 'name' => 'Expense Policies', 'type' => 'page', 'route' => '/finance/expenses/policies',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Policies']]],
            ],
        ],

        /* GROUP 14 — MULTI-ENTITY & CONSOLIDATION */
        [
            'code' => 'consolidation', 'name' => 'Multi-Entity & Consolidation',
            'description' => 'Legal entities, consolidation, eliminations, and minority interest',
            'icon' => 'BuildingOffice2Icon', 'route' => '/finance/consolidation', 'priority' => 14,
            'components' => [
                ['code' => 'entities', 'name' => 'Legal Entities', 'type' => 'page', 'route' => '/finance/consolidation/entities',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Entities']]],
                ['code' => 'consolidation-run', 'name' => 'Consolidation Run', 'type' => 'page', 'route' => '/finance/consolidation/run',
                    'actions' => [['code' => 'run', 'name' => 'Run Consolidation']]],
                ['code' => 'eliminations', 'name' => 'Eliminations', 'type' => 'page', 'route' => '/finance/consolidation/eliminations',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Eliminations']]],
                ['code' => 'minority-interest', 'name' => 'Minority Interest / NCI', 'type' => 'page', 'route' => '/finance/consolidation/nci',
                    'actions' => [['code' => 'manage', 'name' => 'Manage NCI']]],
            ],
        ],

        /* GROUP 15 — REPORTING (Financial + Statutory) */
        [
            'code' => 'reporting', 'name' => 'Financial & Statutory Reporting',
            'description' => 'P&L, Balance Sheet, Cash Flow, Equity, custom reports, and statutory packs',
            'icon' => 'DocumentChartBarIcon', 'route' => '/finance/reports', 'priority' => 15,
            'components' => [
                ['code' => 'profit-loss', 'name' => 'Profit & Loss', 'type' => 'page', 'route' => '/finance/reports/pnl',
                    'actions' => [['code' => 'view', 'name' => 'View P&L'], ['code' => 'export', 'name' => 'Export P&L']]],
                ['code' => 'balance-sheet', 'name' => 'Balance Sheet', 'type' => 'page', 'route' => '/finance/reports/balance-sheet',
                    'actions' => [['code' => 'view', 'name' => 'View Balance Sheet'], ['code' => 'export', 'name' => 'Export Balance Sheet']]],
                ['code' => 'cash-flow', 'name' => 'Cash Flow Statement', 'type' => 'page', 'route' => '/finance/reports/cash-flow',
                    'actions' => [['code' => 'view', 'name' => 'View Cash Flow'], ['code' => 'export', 'name' => 'Export Cash Flow']]],
                ['code' => 'equity', 'name' => 'Statement of Equity', 'type' => 'page', 'route' => '/finance/reports/equity',
                    'actions' => [['code' => 'view', 'name' => 'View Equity']]],
                ['code' => 'custom-reports', 'name' => 'Custom Reports Builder', 'type' => 'page', 'route' => '/finance/reports/custom',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reports'],
                        ['code' => 'create', 'name' => 'Create Report'],
                        ['code' => 'schedule', 'name' => 'Schedule Report Delivery'],
                    ]],
                ['code' => 'statutory-packs', 'name' => 'Statutory Reporting Packs', 'type' => 'page', 'route' => '/finance/reports/statutory',
                    'actions' => [['code' => 'generate', 'name' => 'Generate Pack'], ['code' => 'submit', 'name' => 'Submit to Regulator']]],
            ],
        ],

        /* GROUP 16 — ANALYTICS */
        [
            'code' => 'analytics', 'name' => 'Finance Analytics',
            'description' => 'Profitability analysis, ratio analysis, KPI dashboards, and AI insights',
            'icon' => 'ChartBarIcon', 'route' => '/finance/analytics', 'priority' => 16,
            'components' => [
                ['code' => 'profitability', 'name' => 'Profitability Analysis', 'type' => 'page', 'route' => '/finance/analytics/profitability',
                    'actions' => [['code' => 'view', 'name' => 'View Profitability']]],
                ['code' => 'ratios', 'name' => 'Financial Ratios', 'type' => 'page', 'route' => '/finance/analytics/ratios',
                    'actions' => [['code' => 'view', 'name' => 'View Ratios']]],
                ['code' => 'ai-insights', 'name' => 'AI Insights & Anomaly Detection', 'type' => 'page', 'route' => '/finance/analytics/ai',
                    'actions' => [['code' => 'view', 'name' => 'View Insights']]],
            ],
        ],

        /* GROUP 17 — INTEGRATIONS */
        [
            'code' => 'integrations', 'name' => 'Integrations',
            'description' => 'Bank feeds, payment gateways, ERP / tax platforms',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/finance/integrations', 'priority' => 17,
            'components' => [
                ['code' => 'bank-feeds', 'name' => 'Bank Feeds (Open Banking)', 'type' => 'page', 'route' => '/finance/integrations/bank-feeds',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Feed'], ['code' => 'sync', 'name' => 'Sync Now']]],
                ['code' => 'payment-gateways', 'name' => 'Payment Gateways', 'type' => 'page', 'route' => '/finance/integrations/gateways',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Gateways']]],
                ['code' => 'tax-platforms', 'name' => 'Tax Platforms', 'type' => 'page', 'route' => '/finance/integrations/tax',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Tax Platform']]],
                ['code' => 'erp-bridges', 'name' => 'ERP Bridges', 'type' => 'page', 'route' => '/finance/integrations/erp',
                    'actions' => [['code' => 'configure', 'name' => 'Configure ERP Bridge']]],
            ],
        ],

        /* GROUP 18 — SETTINGS */
        [
            'code' => 'settings', 'name' => 'Finance Settings',
            'description' => 'Fiscal year, posting periods, document numbering, approval workflows',
            'icon' => 'CogIcon', 'route' => '/finance/settings', 'priority' => 99,
            'components' => [
                ['code' => 'fiscal-year', 'name' => 'Fiscal Year & Periods', 'type' => 'page', 'route' => '/finance/settings/fiscal-year',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Fiscal Year']]],
                ['code' => 'numbering', 'name' => 'Document Numbering', 'type' => 'page', 'route' => '/finance/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']]],
                ['code' => 'approvals', 'name' => 'Approval Workflows', 'type' => 'page', 'route' => '/finance/settings/approvals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Approvals']]],
                ['code' => 'depreciation-policies', 'name' => 'Depreciation Policies', 'type' => 'page', 'route' => '/finance/settings/depreciation',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Depreciation Policies']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/finance/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']]],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map
    |--------------------------------------------------------------------------
    | Finance is the EAM cost-of-ownership and capital backbone.
    */
    'eam_integration' => [
        'provides' => [
            'asset.register' => 'fixed-assets.asset-register',
            'asset.depreciation_schedule' => 'fixed-assets.depreciation',
            'asset.cwip' => 'fixed-assets.cwip',
            'asset.insurance' => 'fixed-assets.asset-insurance',
            'asset.lease' => 'fixed-assets.asset-lease',
            'asset.disposal' => 'fixed-assets.asset-retirement',
            'asset.impairment' => 'fixed-assets.impairment-revaluation',
            'asset.physical_verification' => 'fixed-assets.physical-verification',
            'capex.requests' => 'capex-opex.capex-requests',
            'capex.projects' => 'capex-opex.capex-projects',
            'opex.tracking' => 'capex-opex.opex-tracking',
            'cost.center' => 'cost-accounting.cost-centers',
            'cost.allocation' => 'cost-accounting.allocations',
            'budget.budgets' => 'budgeting.budgets',
            'ap.vendor_bill' => 'accounts-payable.vendor-bills',
        ],
        'consumes' => [
            'eam.work_order_costs' => 'aero-eam',
            'eam.maintenance_costs' => 'aero-eam',
            'scm.purchase_invoice' => 'aero-scm',
            'ims.inventory_valuation' => 'aero-ims',
            'project.capex_costs' => 'aero-project',
            'hrm.payroll_costs' => 'aero-hrm',
            'real_estate.facility_costs' => 'aero-real-estate',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Settings (kept for backward compatibility)
    |--------------------------------------------------------------------------
    */
    'accounting' => [
        'default_currency' => env('FINANCE_DEFAULT_CURRENCY', 'USD'),
        'fiscal_year_start' => env('FINANCE_FISCAL_YEAR_START', '01-01'),
        'decimal_places' => env('FINANCE_DECIMAL_PLACES', 2),
        'allow_negative_balance' => env('FINANCE_ALLOW_NEGATIVE_BALANCE', false),
    ],

    'journal_entries' => [
        'require_approval' => env('FINANCE_REQUIRE_APPROVAL', true),
        'auto_number' => env('FINANCE_AUTO_NUMBER', true),
        'number_prefix' => env('FINANCE_NUMBER_PREFIX', 'JE'),
    ],

    'accounts_payable' => [
        'payment_terms_days' => env('FINANCE_AP_PAYMENT_TERMS', 30),
        'early_payment_discount' => env('FINANCE_AP_EARLY_DISCOUNT', 0),
    ],

    'accounts_receivable' => [
        'payment_terms_days' => env('FINANCE_AR_PAYMENT_TERMS', 30),
        'late_fee_percentage' => env('FINANCE_AR_LATE_FEE', 0),
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'finance_admin_role' => 'finance-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'finance-access'],
    ],
];
