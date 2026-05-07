<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Point of Sale (POS) Module Configuration
    |--------------------------------------------------------------------------
    | EAM-relevant: POS hardware register (terminals, scanners, cash drawers,
    | printers) as managed assets with maintenance + device health.
    */

    'code' => 'pos',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Point of Sale',
    'description' => 'Multi-terminal POS: sales, payments, cash management, offline-first, inventory integration, customer loyalty, and POS hardware asset management.',
    'version' => '2.0.0',
    'category' => 'business',
    'icon' => 'ShoppingCartIcon',
    'priority' => 14,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('POS_MODULE_ENABLED', true),
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/pos',

    'features' => [
        'dashboard' => true,
        'register_terminal' => true,
        'sales' => true,
        'returns_refunds' => true,
        'exchanges' => true,
        'quotations' => true,
        'layaway_holds' => true,
        'customers_loyalty' => true,
        'gift_cards' => true,
        'discounts_promotions' => true,
        'tax_management' => true,
        'cash_management' => true,
        'shifts_tills' => true,
        'payments' => true,
        'split_payments' => true,
        'offline_mode' => true,
        'receipt_printing' => true,
        'kitchen_display' => true,
        'table_management' => true, // restaurant
        'menu_management' => true,
        'inventory_integration' => true,
        'stock_counts' => true,
        'multi_store' => true,
        'staff_management' => true,
        'hardware_devices' => true, // EAM
        'device_health' => true, // EAM
        'reports' => true,
        'analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        /* 1 DASHBOARD */
        [
            'code' => 'dashboard', 'name' => 'POS Dashboard',
            'description' => 'Sales KPIs, top items, store performance',
            'icon' => 'HomeIcon', 'route' => '/pos/dashboard', 'priority' => 1,
            'components' => [
                ['code' => 'pos-dashboard', 'name' => 'POS Dashboard', 'type' => 'page', 'route' => '/pos/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
            ],
        ],

        /* 2 REGISTER / TERMINAL */
        [
            'code' => 'register', 'name' => 'Register / Terminal',
            'description' => 'Point-of-sale terminal: ring up sales, accept payment',
            'icon' => 'ComputerDesktopIcon', 'route' => '/pos/register', 'priority' => 2,
            'components' => [
                ['code' => 'sales-terminal', 'name' => 'Sales Terminal', 'type' => 'page', 'route' => '/pos/register',
                    'actions' => [
                        ['code' => 'view', 'name' => 'Open Terminal'],
                        ['code' => 'ring-sale', 'name' => 'Ring Up Sale'],
                        ['code' => 'tender', 'name' => 'Tender Payment'],
                        ['code' => 'void', 'name' => 'Void Transaction'],
                        ['code' => 'suspend', 'name' => 'Suspend Sale'],
                        ['code' => 'resume', 'name' => 'Resume Sale'],
                    ]],
                ['code' => 'quick-menu', 'name' => 'Quick Menu / Favorites', 'type' => 'feature', 'route' => null,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Quick Menu']]],
            ],
        ],

        /* 3 SALES & TRANSACTIONS */
        [
            'code' => 'sales', 'name' => 'Sales & Transactions',
            'description' => 'Sales history, receipts, returns, exchanges, refunds',
            'icon' => 'ShoppingBagIcon', 'route' => '/pos/sales', 'priority' => 3,
            'components' => [
                ['code' => 'sales-history', 'name' => 'Sales History', 'type' => 'page', 'route' => '/pos/sales',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sales'],
                        ['code' => 'reprint', 'name' => 'Reprint Receipt'],
                        ['code' => 'void', 'name' => 'Void Sale'],
                        ['code' => 'export', 'name' => 'Export Sales'],
                    ]],
                ['code' => 'returns', 'name' => 'Returns', 'type' => 'page', 'route' => '/pos/sales/returns',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Returns'],
                        ['code' => 'create', 'name' => 'Process Return'],
                        ['code' => 'approve', 'name' => 'Approve Return'],
                    ]],
                ['code' => 'exchanges', 'name' => 'Exchanges', 'type' => 'page', 'route' => '/pos/sales/exchanges',
                    'actions' => [['code' => 'create', 'name' => 'Process Exchange']]],
                ['code' => 'refunds', 'name' => 'Refunds', 'type' => 'page', 'route' => '/pos/sales/refunds',
                    'actions' => [['code' => 'create', 'name' => 'Process Refund']]],
                ['code' => 'quotations', 'name' => 'Quotations', 'type' => 'page', 'route' => '/pos/sales/quotations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Quotations'],
                        ['code' => 'create', 'name' => 'Create Quotation'],
                        ['code' => 'convert', 'name' => 'Convert to Sale'],
                    ]],
                ['code' => 'layaway', 'name' => 'Layaway / Holds', 'type' => 'page', 'route' => '/pos/sales/layaway',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Layaways'],
                        ['code' => 'create', 'name' => 'Create Layaway'],
                        ['code' => 'payment', 'name' => 'Apply Layaway Payment'],
                        ['code' => 'complete', 'name' => 'Complete Layaway'],
                    ]],
            ],
        ],

        /* 4 CUSTOMERS & LOYALTY */
        [
            'code' => 'customers-loyalty', 'name' => 'Customers & Loyalty',
            'description' => 'Customer lookup, loyalty points, gift cards',
            'icon' => 'UsersIcon', 'route' => '/pos/customers', 'priority' => 4,
            'components' => [
                ['code' => 'customer-list', 'name' => 'Customers', 'type' => 'page', 'route' => '/pos/customers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Customers'],
                        ['code' => 'create', 'name' => 'Create Customer'],
                        ['code' => 'update', 'name' => 'Update Customer'],
                    ]],
                ['code' => 'loyalty', 'name' => 'Loyalty Points', 'type' => 'page', 'route' => '/pos/customers/loyalty',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Points'],
                        ['code' => 'earn', 'name' => 'Earn Points'],
                        ['code' => 'redeem', 'name' => 'Redeem Points'],
                    ]],
                ['code' => 'gift-cards', 'name' => 'Gift Cards', 'type' => 'page', 'route' => '/pos/gift-cards',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Gift Cards'],
                        ['code' => 'issue', 'name' => 'Issue Gift Card'],
                        ['code' => 'redeem', 'name' => 'Redeem Gift Card'],
                        ['code' => 'check-balance', 'name' => 'Check Balance'],
                    ]],
            ],
        ],

        /* 5 DISCOUNTS & PROMOTIONS */
        [
            'code' => 'discounts-promotions', 'name' => 'Discounts & Promotions',
            'description' => 'Discount rules, coupons, BOGO, time-based promotions',
            'icon' => 'TagIcon', 'route' => '/pos/promotions', 'priority' => 5,
            'components' => [
                ['code' => 'discount-rules', 'name' => 'Discount Rules', 'type' => 'page', 'route' => '/pos/promotions/discounts',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Discounts']]],
                ['code' => 'coupons', 'name' => 'Coupons', 'type' => 'page', 'route' => '/pos/promotions/coupons',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Coupons'], ['code' => 'generate', 'name' => 'Generate Coupon Codes']]],
                ['code' => 'campaigns', 'name' => 'Promo Campaigns', 'type' => 'page', 'route' => '/pos/promotions/campaigns',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Campaigns']]],
            ],
        ],

        /* 6 CASH MANAGEMENT */
        [
            'code' => 'cash-management', 'name' => 'Cash Management',
            'description' => 'Shifts, till counts, safe drops, cash-in/cash-out',
            'icon' => 'BanknotesIcon', 'route' => '/pos/cash', 'priority' => 6,
            'components' => [
                ['code' => 'shifts', 'name' => 'Shifts & Tills', 'type' => 'page', 'route' => '/pos/cash/shifts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Shifts'],
                        ['code' => 'open', 'name' => 'Open Shift'],
                        ['code' => 'close', 'name' => 'Close Shift'],
                        ['code' => 'x-report', 'name' => 'Print X-Report'],
                        ['code' => 'z-report', 'name' => 'Print Z-Report'],
                    ]],
                ['code' => 'cash-drops', 'name' => 'Cash Drops / Pickups', 'type' => 'page', 'route' => '/pos/cash/drops',
                    'actions' => [['code' => 'record', 'name' => 'Record Drop/Pickup']]],
                ['code' => 'till-count', 'name' => 'Till Count & Variance', 'type' => 'page', 'route' => '/pos/cash/till-count',
                    'actions' => [['code' => 'count', 'name' => 'Count Till'], ['code' => 'variance', 'name' => 'Review Variance']]],
            ],
        ],

        /* 7 PAYMENTS */
        [
            'code' => 'payments', 'name' => 'Payments',
            'description' => 'Payment methods, card processing, split payments',
            'icon' => 'CreditCardIcon', 'route' => '/pos/payments', 'priority' => 7,
            'components' => [
                ['code' => 'payment-methods', 'name' => 'Payment Methods', 'type' => 'page', 'route' => '/pos/payments/methods',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Payment Methods']]],
                ['code' => 'card-processing', 'name' => 'Card Processing', 'type' => 'page', 'route' => '/pos/payments/card',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Card Processor']]],
                ['code' => 'payment-reconciliation', 'name' => 'Payment Reconciliation', 'type' => 'page', 'route' => '/pos/payments/reconcile',
                    'actions' => [['code' => 'run', 'name' => 'Run Reconciliation']]],
            ],
        ],

        /* 8 TABLES & MENU (restaurant) */
        [
            'code' => 'restaurant', 'name' => 'Restaurant / F&B',
            'description' => 'Table management, menu, kitchen display, modifiers',
            'icon' => 'BuildingStorefrontIcon', 'route' => '/pos/restaurant', 'priority' => 8,
            'components' => [
                ['code' => 'tables', 'name' => 'Table Management', 'type' => 'page', 'route' => '/pos/restaurant/tables',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tables'],
                        ['code' => 'seat', 'name' => 'Seat Guests'],
                        ['code' => 'transfer', 'name' => 'Transfer Table'],
                        ['code' => 'split', 'name' => 'Split Check'],
                        ['code' => 'merge', 'name' => 'Merge Check'],
                    ]],
                ['code' => 'menu', 'name' => 'Menu Management', 'type' => 'page', 'route' => '/pos/restaurant/menu',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Menu'],
                        ['code' => 'create-item', 'name' => 'Create Menu Item'],
                        ['code' => 'update-item', 'name' => 'Update Menu Item'],
                        ['code' => 'modifier', 'name' => 'Manage Modifiers'],
                    ]],
                ['code' => 'kds', 'name' => 'Kitchen Display System (KDS)', 'type' => 'page', 'route' => '/pos/restaurant/kds',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View KDS'],
                        ['code' => 'bump', 'name' => 'Bump Order'],
                        ['code' => 'recall', 'name' => 'Recall Order'],
                    ]],
                ['code' => 'reservations', 'name' => 'Reservations', 'type' => 'page', 'route' => '/pos/restaurant/reservations',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Reservations']]],
            ],
        ],

        /* 9 INVENTORY INTEGRATION */
        [
            'code' => 'pos-inventory', 'name' => 'POS Inventory',
            'description' => 'Real-time stock at POS, stock counts, transfers',
            'icon' => 'CubeIcon', 'route' => '/pos/inventory', 'priority' => 9,
            'components' => [
                ['code' => 'stock-lookup', 'name' => 'Stock Lookup', 'type' => 'page', 'route' => '/pos/inventory/lookup',
                    'actions' => [['code' => 'view', 'name' => 'Check Stock']]],
                ['code' => 'store-transfers', 'name' => 'Store Transfers', 'type' => 'page', 'route' => '/pos/inventory/transfers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Transfers'],
                        ['code' => 'create', 'name' => 'Create Transfer'],
                        ['code' => 'receive', 'name' => 'Receive Transfer'],
                    ]],
                ['code' => 'stock-counts-pos', 'name' => 'Stock Counts', 'type' => 'page', 'route' => '/pos/inventory/counts',
                    'actions' => [['code' => 'conduct', 'name' => 'Conduct Count']]],
            ],
        ],

        /* 10 STAFF */
        [
            'code' => 'staff', 'name' => 'Staff & Roles',
            'description' => 'Cashier logins, permissions, clock-in/out',
            'icon' => 'UserIcon', 'route' => '/pos/staff', 'priority' => 10,
            'components' => [
                ['code' => 'staff-list', 'name' => 'Staff', 'type' => 'page', 'route' => '/pos/staff',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Staff'],
                        ['code' => 'create', 'name' => 'Add Staff'],
                        ['code' => 'update', 'name' => 'Update Staff'],
                        ['code' => 'assign-role', 'name' => 'Assign Role'],
                    ]],
                ['code' => 'staff-clock', 'name' => 'Clock In / Out', 'type' => 'feature', 'route' => null,
                    'actions' => [['code' => 'clock-in', 'name' => 'Clock In'], ['code' => 'clock-out', 'name' => 'Clock Out']]],
            ],
        ],

        /* 11 STORES / LOCATIONS */
        [
            'code' => 'stores', 'name' => 'Stores / Locations',
            'description' => 'Multi-store management, store config',
            'icon' => 'BuildingStorefrontIcon', 'route' => '/pos/stores', 'priority' => 11,
            'components' => [
                ['code' => 'store-list', 'name' => 'Stores', 'type' => 'page', 'route' => '/pos/stores',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Stores'],
                        ['code' => 'create', 'name' => 'Create Store'],
                        ['code' => 'update', 'name' => 'Update Store'],
                        ['code' => 'configure', 'name' => 'Configure Store'],
                    ]],
            ],
        ],

        /* 12 HARDWARE & DEVICES (EAM) */
        [
            'code' => 'hardware-devices', 'name' => 'POS Hardware & Devices',
            'description' => 'Terminals, scanners, cash drawers, printers, scales — EAM-aligned',
            'icon' => 'ComputerDesktopIcon', 'route' => '/pos/hardware', 'priority' => 12,
            'components' => [
                ['code' => 'device-register', 'name' => 'Device Register', 'type' => 'page', 'route' => '/pos/hardware/devices',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Devices'],
                        ['code' => 'create', 'name' => 'Register Device'],
                        ['code' => 'update', 'name' => 'Update Device'],
                        ['code' => 'delete', 'name' => 'Decommission Device'],
                        ['code' => 'link-asset', 'name' => 'Link to EAM Asset'],
                    ]],
                ['code' => 'device-health', 'name' => 'Device Health Monitor', 'type' => 'page', 'route' => '/pos/hardware/health',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Health'],
                        ['code' => 'diagnose', 'name' => 'Run Diagnostics'],
                    ]],
                ['code' => 'device-maintenance', 'name' => 'Device Maintenance', 'type' => 'page', 'route' => '/pos/hardware/maintenance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Maintenance Records'],
                        ['code' => 'create', 'name' => 'Log Maintenance'],
                        ['code' => 'schedule', 'name' => 'Schedule Maintenance'],
                    ]],
                ['code' => 'receipt-printers', 'name' => 'Receipt Printers', 'type' => 'page', 'route' => '/pos/hardware/printers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Printers'], ['code' => 'test', 'name' => 'Test Print']]],
                ['code' => 'barcode-scanners', 'name' => 'Barcode Scanners', 'type' => 'page', 'route' => '/pos/hardware/scanners',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Scanners']]],
                ['code' => 'cash-drawers', 'name' => 'Cash Drawers', 'type' => 'page', 'route' => '/pos/hardware/cash-drawers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cash Drawers'], ['code' => 'open', 'name' => 'Open Drawer']]],
                ['code' => 'card-terminals', 'name' => 'Card Terminals / EMV', 'type' => 'page', 'route' => '/pos/hardware/card-terminals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Card Terminals']]],
                ['code' => 'customer-display', 'name' => 'Customer Display', 'type' => 'page', 'route' => '/pos/hardware/customer-display',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Display']]],
            ],
        ],

        /* 13 OFFLINE MODE */
        [
            'code' => 'offline', 'name' => 'Offline Mode',
            'description' => 'Offline transaction queue, sync',
            'icon' => 'CloudArrowUpIcon', 'route' => '/pos/offline', 'priority' => 13,
            'components' => [
                ['code' => 'offline-queue', 'name' => 'Offline Queue', 'type' => 'page', 'route' => '/pos/offline/queue',
                    'actions' => [['code' => 'view', 'name' => 'View Queue'], ['code' => 'sync', 'name' => 'Force Sync']]],
            ],
        ],

        /* 14 REPORTS */
        [
            'code' => 'reports', 'name' => 'POS Reports',
            'description' => 'Sales, cash, staff, hourly, X/Z reports',
            'icon' => 'DocumentChartBarIcon', 'route' => '/pos/reports', 'priority' => 14,
            'components' => [
                ['code' => 'sales-report', 'name' => 'Sales Reports', 'type' => 'page', 'route' => '/pos/reports/sales',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'staff-report', 'name' => 'Staff Performance', 'type' => 'page', 'route' => '/pos/reports/staff',
                    'actions' => [['code' => 'view', 'name' => 'View']]],
                ['code' => 'hourly-report', 'name' => 'Hourly Sales', 'type' => 'page', 'route' => '/pos/reports/hourly',
                    'actions' => [['code' => 'view', 'name' => 'View']]],
                ['code' => 'end-of-day', 'name' => 'End of Day / Z-Report', 'type' => 'page', 'route' => '/pos/reports/eod',
                    'actions' => [['code' => 'generate', 'name' => 'Generate EOD Report']]],
            ],
        ],

        /* 15 INTEGRATIONS */
        [
            'code' => 'integrations', 'name' => 'Integrations',
            'description' => 'Accounting sync, eCommerce, loyalty platforms',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/pos/integrations', 'priority' => 15,
            'components' => [
                ['code' => 'accounting-sync', 'name' => 'Accounting Sync', 'type' => 'page', 'route' => '/pos/integrations/accounting',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']]],
                ['code' => 'ecommerce-sync', 'name' => 'E-Commerce Sync', 'type' => 'page', 'route' => '/pos/integrations/ecommerce',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']]],
                ['code' => 'delivery-apps', 'name' => 'Delivery Apps (Uber, DoorDash)', 'type' => 'page', 'route' => '/pos/integrations/delivery',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']]],
            ],
        ],

        /* 16 SETTINGS */
        [
            'code' => 'settings', 'name' => 'POS Settings',
            'description' => 'Receipt templates, tax, payment, hardware config',
            'icon' => 'CogIcon', 'route' => '/pos/settings', 'priority' => 99,
            'components' => [
                ['code' => 'receipt-settings', 'name' => 'Receipt Settings', 'type' => 'page', 'route' => '/pos/settings/receipt',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Receipt']]],
                ['code' => 'tax-settings', 'name' => 'Tax Settings', 'type' => 'page', 'route' => '/pos/settings/tax',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tax Settings']]],
                ['code' => 'payment-settings', 'name' => 'Payment Settings', 'type' => 'page', 'route' => '/pos/settings/payment',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Payment Settings']]],
                ['code' => 'hardware-settings', 'name' => 'Hardware Settings', 'type' => 'page', 'route' => '/pos/settings/hardware',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Hardware Settings']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/pos/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map
    |--------------------------------------------------------------------------
    */
    'eam_integration' => [
        'provides' => [
            'pos.hardware_register' => 'hardware-devices.device-register',
            'pos.device_health' => 'hardware-devices.device-health',
            'pos.device_maintenance' => 'hardware-devices.device-maintenance',
            'pos.stores' => 'stores.store-list',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.maintenance_schedule' => 'aero-eam',
            'ims.stock_availability' => 'aero-ims',
            'finance.gl_posting' => 'aero-finance',
            'crm.customers' => 'aero-crm',
            'iot.device_telemetry' => 'aero-iot',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Settings (kept)
    |--------------------------------------------------------------------------
    */
    'sales' => [
        'default_tax_rate' => env('POS_DEFAULT_TAX_RATE', 0),
        'allow_discounts' => env('POS_ALLOW_DISCOUNTS', true),
        'require_customer' => env('POS_REQUIRE_CUSTOMER', false),
        'auto_print_receipt' => env('POS_AUTO_PRINT_RECEIPT', false),
    ],

    'payment' => [
        'accepted_methods' => ['cash', 'card', 'mobile', 'bank_transfer'],
        'enable_split_payment' => env('POS_SPLIT_PAYMENT', true),
    ],

    'receipt' => [
        'paper_size' => env('POS_RECEIPT_PAPER_SIZE', '80mm'),
        'show_logo' => env('POS_RECEIPT_SHOW_LOGO', true),
        'footer_text' => env('POS_RECEIPT_FOOTER', 'Thank you for your business!'),
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'pos_admin_role' => 'pos-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'pos-access'],
    ],
];
