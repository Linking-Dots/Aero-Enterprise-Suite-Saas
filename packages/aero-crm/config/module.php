<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Module — Customer Relationship Management
    |--------------------------------------------------------------------------
    |
    | Manages customers, leads, deals, pipelines, opportunities,
    | and marketing campaigns.
    |
    */

    'code' => 'crm',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Customer Relationship Management',
    'description' => 'Full-lifecycle CRM: contacts, accounts, leads, deals, pipelines, quotes, orders, service contracts (EAM), support, marketing automation, and analytics.',
    'version' => '2.0.0',
    'category' => 'business',
    'icon' => 'UsersIcon',
    'priority' => 12,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('CRM_MODULE_ENABLED', true),
    'min_plan' => null,
    'minimum_plan' => null,
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => 'crm',

    'features' => [
        'dashboard' => true,
        'contacts' => true,
        'accounts' => true,
        'customers' => true,
        'leads' => true,
        'deals' => true,
        'pipelines' => true,
        'opportunities' => true,
        'activities' => true,
        'calls_meetings' => true,
        'quotes_proposals' => true,
        'orders' => true,
        'contracts' => true,
        'service_contracts' => true, // EAM: customer asset service contracts
        'customer_assets' => true, // EAM: customer-owned assets under service
        'support_helpdesk' => true,
        'marketing_campaigns' => true,
        'marketing_automation' => true,
        'email_templates' => true,
        'segmentation' => true,
        'forecasting' => true,
        'commission' => true,
        'territory_management' => true,
        'loyalty_program' => true,
        'customer_portal' => true,
        'surveys_nps' => true,
        'analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        // ==================== CRM DASHBOARD ====================
        [
            'code' => 'crm-dashboard',
            'name' => 'CRM Dashboard',
            'description' => 'Sales pipeline overview, deal metrics, and activity feed.',
            'icon' => 'ChartPieIcon',
            'route' => 'tenant.crm.dashboard',
            'priority' => 1,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== CUSTOMERS ====================
        [
            'code' => 'customers',
            'name' => 'Customers',
            'description' => 'Customer management and relationship tracking.',
            'icon' => 'UserGroupIcon',
            'route' => 'tenant.crm.customers.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'customer-list',
                    'name' => 'Customer List',
                    'description' => 'View and manage all customers.',
                    'route_name' => 'tenant.crm.customers.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                        ['code' => 'import', 'name' => 'Import', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== LEADS ====================
        [
            'code' => 'leads',
            'name' => 'Leads',
            'description' => 'Lead capture, scoring, and conversion tracking.',
            'icon' => 'FunnelIcon',
            'route' => 'tenant.crm.leads.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'lead-list',
                    'name' => 'Lead List',
                    'description' => 'View and manage all leads.',
                    'route_name' => 'tenant.crm.leads.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'convert', 'name' => 'Convert to Customer', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== DEALS ====================
        [
            'code' => 'deals',
            'name' => 'Deals',
            'description' => 'Sales deal management with stage tracking and activities.',
            'icon' => 'CurrencyDollarIcon',
            'route' => 'tenant.crm.deals.index',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'deal-list',
                    'name' => 'Deal List',
                    'description' => 'View and manage all deals.',
                    'route_name' => 'tenant.crm.deals.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== PIPELINES ====================
        [
            'code' => 'pipelines',
            'name' => 'Pipelines',
            'description' => 'Sales pipeline configuration and stage management.',
            'icon' => 'AdjustmentsHorizontalIcon',
            'route' => 'tenant.crm.pipelines.index',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'pipeline-list',
                    'name' => 'Pipeline List',
                    'description' => 'View and manage sales pipelines.',
                    'route_name' => 'tenant.crm.pipelines.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== OPPORTUNITIES ====================
        [
            'code' => 'opportunities',
            'name' => 'Opportunities',
            'description' => 'Opportunity tracking and revenue forecasting.',
            'icon' => 'SparklesIcon',
            'route' => 'tenant.crm.opportunities.index',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'opportunity-list',
                    'name' => 'Opportunity List',
                    'description' => 'View and manage all opportunities.',
                    'route_name' => 'tenant.crm.opportunities.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== MARKETING CAMPAIGNS ====================
        [
            'code' => 'marketing',
            'name' => 'Marketing Campaigns',
            'description' => 'Email and SMS campaign management with audience targeting.',
            'icon' => 'MegaphoneIcon',
            'route' => 'tenant.crm.marketing.index',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'email-campaigns',
                    'name' => 'Email Campaigns',
                    'description' => 'Create and manage email marketing campaigns.',
                    'route_name' => 'tenant.crm.marketing.email.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'sms-campaigns',
                    'name' => 'SMS Campaigns',
                    'description' => 'Create and manage SMS marketing campaigns.',
                    'route_name' => 'tenant.crm.marketing.sms.index',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'marketing-automation', 'name' => 'Marketing Automation (Workflows)',
                    'route_name' => 'tenant.crm.marketing.automation',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows', 'is_active' => true]],
                ],
                [
                    'code' => 'segments', 'name' => 'Audience Segments',
                    'route_name' => 'tenant.crm.marketing.segments',
                    'priority' => 4, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Segment', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'landing-pages', 'name' => 'Landing Pages & Forms',
                    'route_name' => 'tenant.crm.marketing.landing-pages',
                    'priority' => 5, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Pages', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== CONTACTS ====================
        [
            'code' => 'contacts',
            'name' => 'Contacts',
            'description' => 'Person-level contacts separate from accounts/customers.',
            'icon' => 'IdentificationIcon',
            'route' => 'tenant.crm.contacts.index',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'contact-list', 'name' => 'Contact List',
                    'route_name' => 'tenant.crm.contacts.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'import', 'name' => 'Import', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                        ['code' => 'merge', 'name' => 'Merge Duplicates', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'contact-timeline', 'name' => 'Contact Timeline',
                    'route_name' => 'tenant.crm.contacts.timeline',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Timeline', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== ACCOUNTS (B2B) ====================
        [
            'code' => 'accounts',
            'name' => 'Accounts (B2B Companies)',
            'description' => 'Company-level records with hierarchy, contacts, deals.',
            'icon' => 'BuildingOfficeIcon',
            'route' => 'tenant.crm.accounts.index',
            'priority' => 9,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'account-list', 'name' => 'Account List',
                    'route_name' => 'tenant.crm.accounts.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'hierarchy', 'name' => 'Manage Hierarchy', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ACTIVITIES ====================
        [
            'code' => 'activities',
            'name' => 'Activities (Calls / Meetings / Tasks / Emails)',
            'description' => 'Log calls, meetings, tasks, emails, notes per entity.',
            'icon' => 'CalendarDaysIcon',
            'route' => 'tenant.crm.activities.index',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'activity-feed', 'name' => 'Activity Feed',
                    'route_name' => 'tenant.crm.activities.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'log-call', 'name' => 'Log Call', 'is_active' => true],
                        ['code' => 'log-meeting', 'name' => 'Log Meeting', 'is_active' => true],
                        ['code' => 'log-task', 'name' => 'Log Task', 'is_active' => true],
                        ['code' => 'log-email', 'name' => 'Log Email', 'is_active' => true],
                        ['code' => 'log-note', 'name' => 'Add Note', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'calendar', 'name' => 'Activities Calendar',
                    'route_name' => 'tenant.crm.activities.calendar',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Calendar', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== QUOTES & PROPOSALS ====================
        [
            'code' => 'quotes-proposals',
            'name' => 'Quotes & Proposals',
            'description' => 'CPQ quoting, proposals, e-signature.',
            'icon' => 'DocumentTextIcon',
            'route' => 'tenant.crm.quotes.index',
            'priority' => 11,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'quote-list', 'name' => 'Quotes',
                    'route_name' => 'tenant.crm.quotes.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Quotes', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Quote', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Quote', 'is_active' => true],
                        ['code' => 'send', 'name' => 'Send Quote', 'is_active' => true],
                        ['code' => 'accept', 'name' => 'Accept Quote', 'is_active' => true],
                        ['code' => 'reject', 'name' => 'Reject Quote', 'is_active' => true],
                        ['code' => 'convert-order', 'name' => 'Convert to Order', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'proposals', 'name' => 'Proposals',
                    'route_name' => 'tenant.crm.quotes.proposals',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Proposals', 'is_active' => true]],
                ],
                [
                    'code' => 'product-catalog', 'name' => 'Product Catalog & Price Books',
                    'route_name' => 'tenant.crm.quotes.catalog',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Catalog', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== ORDERS ====================
        [
            'code' => 'orders',
            'name' => 'Sales Orders',
            'description' => 'Convert quotes to orders, link to fulfilment.',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => 'tenant.crm.orders.index',
            'priority' => 12,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'order-list', 'name' => 'Order List',
                    'route_name' => 'tenant.crm.orders.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'cancel', 'name' => 'Cancel Order', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== CONTRACTS ====================
        [
            'code' => 'contracts',
            'name' => 'Customer Contracts',
            'description' => 'Sales contracts, renewals, clauses.',
            'icon' => 'DocumentDuplicateIcon',
            'route' => 'tenant.crm.contracts.index',
            'priority' => 13,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'contract-list', 'name' => 'Contract List',
                    'route_name' => 'tenant.crm.contracts.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Contract', 'is_active' => true],
                        ['code' => 'sign', 'name' => 'Send for E-Signature', 'is_active' => true],
                        ['code' => 'renew', 'name' => 'Renew Contract', 'is_active' => true],
                        ['code' => 'terminate', 'name' => 'Terminate Contract', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== CUSTOMER ASSETS & SERVICE CONTRACTS (EAM) ====================
        [
            'code' => 'customer-assets',
            'name' => 'Customer Assets & Service Contracts',
            'description' => 'Track customer-owned assets under warranty or service. EAM-aligned.',
            'icon' => 'CubeIcon',
            'route' => 'tenant.crm.customer-assets.index',
            'priority' => 14,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'installed-base', 'name' => 'Installed Base (Customer Assets)',
                    'route_name' => 'tenant.crm.customer-assets.installed-base',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Installed Base', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Add Customer Asset', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Asset', 'is_active' => true],
                        ['code' => 'transfer', 'name' => 'Transfer Asset Ownership', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'warranty', 'name' => 'Warranty Tracking',
                    'route_name' => 'tenant.crm.customer-assets.warranty',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Warranties', 'is_active' => true],
                        ['code' => 'register', 'name' => 'Register Warranty', 'is_active' => true],
                        ['code' => 'claim', 'name' => 'File Warranty Claim', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'service-contracts', 'name' => 'Service Contracts (AMC / SLA)',
                    'route_name' => 'tenant.crm.customer-assets.service-contracts',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Service Contracts', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Service Contract', 'is_active' => true],
                        ['code' => 'renew', 'name' => 'Renew Contract', 'is_active' => true],
                        ['code' => 'invoice', 'name' => 'Invoice Service Contract', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'service-history', 'name' => 'Service History per Asset',
                    'route_name' => 'tenant.crm.customer-assets.service-history',
                    'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Service History', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== SUPPORT / HELPDESK ====================
        [
            'code' => 'support',
            'name' => 'Support & Helpdesk',
            'description' => 'Customer support tickets, SLAs, knowledge base.',
            'icon' => 'LifebuoyIcon',
            'route' => 'tenant.crm.support.index',
            'priority' => 15,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'tickets', 'name' => 'Support Tickets',
                    'route_name' => 'tenant.crm.support.tickets',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'assign', 'name' => 'Assign', 'is_active' => true],
                        ['code' => 'resolve', 'name' => 'Resolve', 'is_active' => true],
                        ['code' => 'close', 'name' => 'Close', 'is_active' => true],
                        ['code' => 'escalate', 'name' => 'Escalate', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'sla-policies', 'name' => 'SLA Policies',
                    'route_name' => 'tenant.crm.support.sla',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage SLAs', 'is_active' => true]],
                ],
                [
                    'code' => 'knowledge-base', 'name' => 'Knowledge Base',
                    'route_name' => 'tenant.crm.support.kb',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Article', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Article', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'case-escalation', 'name' => 'Case Escalation',
                    'route_name' => 'tenant.crm.support.escalation',
                    'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Escalation Matrix', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== FORECASTING & COMMISSION ====================
        [
            'code' => 'forecasting-commission',
            'name' => 'Forecasting & Commission',
            'description' => 'Sales forecast, quotas, commission plans.',
            'icon' => 'ChartBarSquareIcon',
            'route' => 'tenant.crm.forecasting.index',
            'priority' => 16,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'forecast', 'name' => 'Sales Forecast',
                    'route_name' => 'tenant.crm.forecasting.forecast',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'generate', 'name' => 'Generate Forecast', 'is_active' => true]],
                ],
                [
                    'code' => 'quotas', 'name' => 'Sales Quotas',
                    'route_name' => 'tenant.crm.forecasting.quotas',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Quotas', 'is_active' => true]],
                ],
                [
                    'code' => 'commission-plans', 'name' => 'Commission Plans',
                    'route_name' => 'tenant.crm.forecasting.commission',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Commission', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Plan', 'is_active' => true],
                        ['code' => 'calculate', 'name' => 'Calculate Commission', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== TERRITORY MANAGEMENT ====================
        [
            'code' => 'territories',
            'name' => 'Territory Management',
            'description' => 'Geo/industry territories, assignment rules.',
            'icon' => 'MapIcon',
            'route' => 'tenant.crm.territories.index',
            'priority' => 17,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'territory-list', 'name' => 'Territory List',
                    'route_name' => 'tenant.crm.territories.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Territories', 'is_active' => true]],
                ],
                [
                    'code' => 'assignment-rules', 'name' => 'Assignment Rules',
                    'route_name' => 'tenant.crm.territories.rules',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Rules', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== LOYALTY & CUSTOMER PORTAL ====================
        [
            'code' => 'loyalty-portal',
            'name' => 'Loyalty & Customer Portal',
            'description' => 'Loyalty program and customer self-service portal.',
            'icon' => 'GiftIcon',
            'route' => 'tenant.crm.loyalty.index',
            'priority' => 18,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'loyalty-program', 'name' => 'Loyalty Program',
                    'route_name' => 'tenant.crm.loyalty.program',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Program', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Tiers', 'is_active' => true],
                        ['code' => 'award', 'name' => 'Award Points', 'is_active' => true],
                        ['code' => 'redeem', 'name' => 'Redeem Points', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'customer-portal', 'name' => 'Customer Portal',
                    'route_name' => 'tenant.crm.loyalty.portal',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal', 'is_active' => true]],
                ],
                [
                    'code' => 'surveys-nps', 'name' => 'Surveys & NPS',
                    'route_name' => 'tenant.crm.loyalty.surveys',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Surveys', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Survey', 'is_active' => true],
                        ['code' => 'send', 'name' => 'Send Survey', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ANALYTICS ====================
        [
            'code' => 'analytics',
            'name' => 'CRM Analytics',
            'description' => 'Revenue, conversion, customer health, churn risk.',
            'icon' => 'ChartPieIcon',
            'route' => 'tenant.crm.analytics.index',
            'priority' => 19,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'sales-reports', 'name' => 'Sales Reports',
                    'route_name' => 'tenant.crm.analytics.sales',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'export', 'name' => 'Export', 'is_active' => true]],
                ],
                [
                    'code' => 'customer-health', 'name' => 'Customer Health Score',
                    'route_name' => 'tenant.crm.analytics.health',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Health Scores', 'is_active' => true]],
                ],
                [
                    'code' => 'churn-risk', 'name' => 'Churn Risk (AI)',
                    'route_name' => 'tenant.crm.analytics.churn',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Churn Risk', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== INTEGRATIONS ====================
        [
            'code' => 'integrations',
            'name' => 'Integrations',
            'description' => 'Email, telephony, calendar, marketing platforms.',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => 'tenant.crm.integrations.index',
            'priority' => 20,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'email-integration', 'name' => 'Email (Gmail / Outlook)',
                    'route_name' => 'tenant.crm.integrations.email',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure', 'is_active' => true]],
                ],
                [
                    'code' => 'telephony', 'name' => 'Telephony / CTI',
                    'route_name' => 'tenant.crm.integrations.telephony',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure', 'is_active' => true]],
                ],
                [
                    'code' => 'calendar-integration', 'name' => 'Calendar',
                    'route_name' => 'tenant.crm.integrations.calendar',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'CRM Settings',
            'description' => 'Pipelines, custom fields, templates, automation.',
            'icon' => 'CogIcon',
            'route' => 'tenant.crm.settings.index',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'custom-fields', 'name' => 'Custom Fields',
                    'route_name' => 'tenant.crm.settings.custom-fields',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Custom Fields', 'is_active' => true]],
                ],
                [
                    'code' => 'email-templates', 'name' => 'Email Templates',
                    'route_name' => 'tenant.crm.settings.email-templates',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates', 'is_active' => true]],
                ],
                [
                    'code' => 'general', 'name' => 'General Settings',
                    'route_name' => 'tenant.crm.settings.index',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Settings', 'is_active' => true], ['code' => 'update', 'name' => 'Update Settings', 'is_active' => true]],
                ],
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
            'crm.installed_base' => 'customer-assets.installed-base',
            'crm.warranty' => 'customer-assets.warranty',
            'crm.service_contracts' => 'customer-assets.service-contracts',
            'crm.service_history' => 'customer-assets.service-history',
            'crm.support_tickets' => 'support.tickets',
            'crm.sla_policies' => 'support.sla-policies',
            'crm.customers' => 'customers.customer-list',
            'crm.accounts' => 'accounts.account-list',
        ],
        'consumes' => [
            'eam.work_orders' => 'aero-eam',
            'eam.asset_registry' => 'aero-eam',
            'field_service.dispatch' => 'aero-field-service',
            'finance.invoices' => 'aero-finance',
            'ims.stock_availability' => 'aero-ims',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'crm_admin_role' => 'crm-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'crm-access'],
    ],
];
