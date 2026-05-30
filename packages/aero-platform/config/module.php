<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the Aero Platform module structure.
    | The Platform module provides multi-tenant SaaS infrastructure including:
    | - Landlord authentication & administration
    | - Tenant management & provisioning
    | - Plans & subscription billing
    | - Public registration & onboarding
    | - Error monitoring & analytics
    |
    | Hierarchy: Module → SubModule → Component → Action
    |
    | Scope: 'platform' - Platform admin modules, 'tenant' - Tenant user modules
    |
    */

    'code' => 'platform',
    'schema_version' => '2.0',
    'scope' => 'platform',
    'name' => 'Platform Administration',
    'description' => 'Multi-tenant SaaS platform management including tenants, plans, billing, and system settings',
    'icon' => 'BuildingOffice2Icon',
    'route_prefix' => '/admin',
    'category' => 'platform',
    'priority' => 0, // Highest priority - platform module
    'is_core' => true,
    'is_active' => true,
    'version' => '1.0.0',
    'min_plan' => null,
    'license_type' => 'platform',
    'dependencies' => [],
    'release_date' => '2024-01-01',
    'enabled' => true,

    'submodules' => [
        /*
        |--------------------------------------------------------------------------
        | 1. Dashboard
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-dashboard',
            'name' => 'Dashboard',
            'description' => 'Platform overview and statistics',
            'icon' => 'HomeIcon',
            'route' => '/dashboard',
            'priority' => 1,

            'components' => [
                [
                    'code' => 'dashboard-overview',
                    'name' => 'Dashboard',
                    'route' => '/dashboard',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Dashboard'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2. Tenant Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'tenants',
            'name' => 'Tenants',
            'description' => 'Manage all tenant organizations',
            'icon' => 'BuildingOfficeIcon',
            'route' => '/tenants',
            'priority' => 2,

            'components' => [
                [
                    'code' => 'tenant-list',
                    'name' => 'All Tenants',
                    'route' => '/tenants',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tenants'],
                        ['code' => 'create', 'name' => 'Create Tenant'],
                        ['code' => 'edit', 'name' => 'Edit Tenant'],
                        ['code' => 'delete', 'name' => 'Delete Tenant'],
                        ['code' => 'suspend', 'name' => 'Suspend Tenant'],
                        ['code' => 'activate', 'name' => 'Activate Tenant'],
                        ['code' => 'impersonate', 'name' => 'Impersonate Tenant'],
                        ['code' => 'forget', 'name' => 'GDPR Right to be Forgotten'],
                    ],
                ],
                [
                    'code' => 'tenant-domains',
                    'name' => 'Domains',
                    'route' => '/tenants/domains',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Domains'],
                        ['code' => 'manage', 'name' => 'Manage Domains'],
                    ],
                ],
                [
                    'code' => 'tenant-databases',
                    'name' => 'Databases',
                    'route' => '/tenants/databases',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Databases'],
                        ['code' => 'migrate', 'name' => 'Run Migrations'],
                        ['code' => 'backup', 'name' => 'Backup Database'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 3. Onboarding Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-onboarding',
            'name' => 'Onboarding',
            'description' => 'Manage tenant registration and onboarding',
            'icon' => 'UserPlusIcon',
            'route' => '/onboarding',
            'priority' => 3,

            'components' => [
                [
                    'code' => 'onboarding-dashboard',
                    'name' => 'Dashboard',
                    'route' => '/onboarding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Onboarding Stats'],
                    ],
                ],
                [
                    'code' => 'pending-approvals',
                    'name' => 'Pending Approvals',
                    'route' => '/onboarding/pending',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pending'],
                        ['code' => 'approve', 'name' => 'Approve Tenant'],
                        ['code' => 'reject', 'name' => 'Reject Tenant'],
                    ],
                ],
                [
                    'code' => 'provisioning',
                    'name' => 'Provisioning',
                    'route' => '/onboarding/provisioning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Provisioning'],
                        ['code' => 'retry', 'name' => 'Retry Failed'],
                    ],
                ],
                [
                    'code' => 'trials',
                    'name' => 'Trials',
                    'route' => '/onboarding/trials',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Trials'],
                        ['code' => 'extend', 'name' => 'Extend Trial'],
                        ['code' => 'convert', 'name' => 'Convert to Paid'],
                    ],
                ],
                [
                    'code' => 'onboarding-analytics',
                    'name' => 'Analytics',
                    'route' => '/onboarding/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Analytics'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
                [
                    'code' => 'onboarding-automation',
                    'name' => 'Automation',
                    'route' => '/onboarding/automation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Automation'],
                        ['code' => 'manage', 'name' => 'Manage Rules'],
                        ['code' => 'execute', 'name' => 'Execute Actions'],
                    ],
                ],
                [
                    'code' => 'onboarding-settings',
                    'name' => 'Settings',
                    'route' => '/onboarding/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 4. Plans & Pricing
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'plan-management',
            'name' => 'Plans',
            'description' => 'Manage subscription plans and pricing',
            'icon' => 'CurrencyDollarIcon',
            'route' => '/plans',
            'priority' => 4,

            'components' => [
                [
                    'code' => 'plan-list',
                    'name' => 'All Plans',
                    'route' => '/plans',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Plans'],
                        ['code' => 'create', 'name' => 'Create Plan'],
                        ['code' => 'edit', 'name' => 'Edit Plan'],
                        ['code' => 'delete', 'name' => 'Delete Plan'],
                        ['code' => 'archive', 'name' => 'Archive Plan'],
                        ['code' => 'clone', 'name' => 'Clone Plan'],
                    ],
                ],
                [
                    'code' => 'plan-details',
                    'name' => 'Plan Details',
                    'route' => '/plans/{id}',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Plan Details'],
                        ['code' => 'view-subscribers', 'name' => 'View Subscribers'],
                        ['code' => 'view-revenue', 'name' => 'View Revenue Metrics'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 5. Quota Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'quota-management',
            'name' => 'Quota Management',
            'description' => 'Monitor and configure quota enforcement across all tenants',
            'icon' => 'ChartPieIcon',
            'route' => '/quotas',
            'priority' => 5,

            'components' => [
                [
                    'code' => 'quota-dashboard',
                    'name' => 'Quota Monitor',
                    'route' => '/quotas',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Quota Dashboard'],
                        ['code' => 'override', 'name' => 'Override Tenant Quotas'],
                        ['code' => 'dismiss-warnings', 'name' => 'Dismiss Warnings'],
                    ],
                ],
                [
                    'code' => 'quota-settings',
                    'name' => 'Enforcement Settings',
                    'route' => '/quotas/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Enforcement Settings'],
                        ['code' => 'edit', 'name' => 'Edit Enforcement Settings'],
                    ],
                ],
                [
                    'code' => 'quota-analytics',
                    'name' => 'Usage Analytics',
                    'route' => '/quotas/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Usage Analytics'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 6. Billing & Subscriptions
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'billing-management',
            'name' => 'Billing',
            'description' => 'Manage subscriptions, invoices, and payments',
            'icon' => 'CreditCardIcon',
            'route' => '/billing',
            'priority' => 6,

            'components' => [
                [
                    'code' => 'billing-dashboard',
                    'name' => 'Dashboard',
                    'route' => '/billing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Billing Dashboard'],
                    ],
                ],
                [
                    'code' => 'subscriptions',
                    'name' => 'Subscriptions',
                    'route' => '/billing/subscriptions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Subscriptions'],
                        ['code' => 'cancel', 'name' => 'Cancel Subscription'],
                        ['code' => 'upgrade', 'name' => 'Upgrade/Downgrade'],
                    ],
                ],
                [
                    'code' => 'invoices',
                    'name' => 'Invoices',
                    'route' => '/billing/invoices',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Invoices'],
                        ['code' => 'generate', 'name' => 'Generate Invoice'],
                        ['code' => 'send', 'name' => 'Send Invoice'],
                        ['code' => 'mark-paid', 'name' => 'Mark as Paid'],
                    ],
                ],
                [
                    'code' => 'payment-gateways',
                    'name' => 'Payment Gateways',
                    'route' => '/billing/gateways',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Gateways'],
                        ['code' => 'configure', 'name' => 'Configure Gateway'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 7. Modules Marketplace
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'module-management',
            'name' => 'Modules',
            'description' => 'Manage available modules and marketplace',
            'icon' => 'CubeIcon',
            'route' => '/modules',
            'priority' => 7,

            'components' => [
                [
                    'code' => 'module-list',
                    'name' => 'All Modules',
                    'route' => '/modules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Modules'],
                        ['code' => 'configure', 'name' => 'Configure Module'],
                        ['code' => 'toggle-active', 'name' => 'Toggle Active'],
                    ],
                ],
                [
                    'code' => 'module-pricing',
                    'name' => 'Module Pricing',
                    'route' => '/modules/pricing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pricing'],
                        ['code' => 'edit', 'name' => 'Edit Pricing'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 8. Error Monitoring
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'error-monitoring',
            'name' => 'Error Logs',
            'description' => 'Monitor errors from all installations',
            'icon' => 'ExclamationTriangleIcon',
            'route' => '/error-logs',
            'priority' => 8,

            'components' => [
                [
                    'code' => 'error-log-list',
                    'name' => 'All Errors',
                    'route' => '/error-logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Errors'],
                        ['code' => 'resolve', 'name' => 'Mark Resolved'],
                        ['code' => 'delete', 'name' => 'Delete Errors'],
                    ],
                ],
                [
                    'code' => 'error-analytics',
                    'name' => 'Analytics',
                    'route' => '/error-logs/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Analytics'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 9. Platform Users (Landlord)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-users',
            'name' => 'Platform Users',
            'description' => 'Manage platform administrators',
            'icon' => 'UserGroupIcon',
            'route' => '/users',
            'priority' => 9,

            'components' => [
                [
                    'code' => 'landlord-user-list',
                    'name' => 'All Users',
                    'route' => '/users',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Users'],
                        ['code' => 'create', 'name' => 'Create User'],
                        ['code' => 'edit', 'name' => 'Edit User'],
                        ['code' => 'delete', 'name' => 'Delete User'],
                    ],
                ],
                [
                    'code' => 'landlord-roles',
                    'name' => 'Roles',
                    'route' => '/roles',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Roles'],
                        ['code' => 'manage', 'name' => 'Manage Roles'],
                    ],
                ],
                [
                    'code' => 'module-access',
                    'name' => 'Module Access',
                    'route' => '/module-access',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Module Access'],
                        ['code' => 'manage', 'name' => 'Manage Module Access'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 10. Integrations
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-integrations',
            'name' => 'Integrations',
            'description' => 'Manage API keys, webhooks, and connectors',
            'icon' => 'LinkIcon',
            'route' => '/integrations',
            'priority' => 10,

            'components' => [
                [
                    'code' => 'api-keys',
                    'name' => 'API Keys',
                    'route' => '/integrations/api',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View API Keys'],
                        ['code' => 'create', 'name' => 'Create API Key'],
                        ['code' => 'revoke', 'name' => 'Revoke API Key'],
                    ],
                ],
                [
                    'code' => 'webhooks',
                    'name' => 'Webhooks',
                    'route' => '/integrations/webhooks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Webhooks'],
                        ['code' => 'manage', 'name' => 'Manage Webhooks'],
                    ],
                ],
                [
                    'code' => 'connectors',
                    'name' => 'Connectors',
                    'route' => '/integrations/connectors',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Connectors'],
                        ['code' => 'configure', 'name' => 'Configure Connector'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 11. Platform Settings
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'system-settings',
            'name' => 'Settings',
            'description' => 'Platform configuration and settings',
            'icon' => 'Cog6ToothIcon',
            'route' => '/settings',
            'priority' => 11,

            'components' => [
                [
                    'code' => 'general-settings',
                    'name' => 'General',
                    'route' => '/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
                [
                    'code' => 'branding-settings',
                    'name' => 'Branding',
                    'route' => '/settings/branding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Branding'],
                        ['code' => 'edit', 'name' => 'Edit Branding'],
                    ],
                ],
                [
                    'code' => 'email-settings',
                    'name' => 'Email',
                    'route' => '/settings/email',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Settings'],
                        ['code' => 'edit', 'name' => 'Edit Email Settings'],
                        ['code' => 'test', 'name' => 'Send Test Email'],
                    ],
                ],
                [
                    'code' => 'localization-settings',
                    'name' => 'Localization',
                    'route' => '/settings/localization',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Localization'],
                        ['code' => 'edit', 'name' => 'Edit Localization'],
                    ],
                ],
                [
                    'code' => 'maintenance-settings',
                    'name' => 'Maintenance',
                    'route' => '/settings/maintenance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Maintenance'],
                        ['code' => 'toggle', 'name' => 'Toggle Maintenance Mode'],
                    ],
                ],
                [
                    'code' => 'infrastructure-settings',
                    'name' => 'Infrastructure',
                    'icon' => 'ServerStackIcon',
                    'route' => '/settings/infrastructure',
                    'route_name' => 'admin.settings.infrastructure',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Infrastructure Settings'],
                        ['code' => 'edit', 'name' => 'Edit Infrastructure Settings'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 12. Developer Tools
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'developer-tools',
            'name' => 'Developer',
            'description' => 'Developer tools and system monitoring',
            'icon' => 'CommandLineIcon',
            'route' => '/developer',
            'priority' => 12,

            'components' => [
                [
                    'code' => 'developer-dashboard',
                    'name' => 'Dashboard',
                    'route' => '/developer',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Developer Dashboard'],
                    ],
                ],
                [
                    'code' => 'cache-management',
                    'name' => 'Cache',
                    'route' => '/developer/cache',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cache'],
                        ['code' => 'clear', 'name' => 'Clear Cache'],
                    ],
                ],
                [
                    'code' => 'queue-management',
                    'name' => 'Queues',
                    'route' => '/developer/queues',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Queues'],
                        ['code' => 'manage', 'name' => 'Manage Queues'],
                    ],
                ],
                [
                    'code' => 'log-viewer',
                    'name' => 'Logs',
                    'route' => '/developer/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Logs'],
                        ['code' => 'download', 'name' => 'Download Logs'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 13. Audit Logs
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'audit-logs',
            'name' => 'Audit Logs',
            'description' => 'Platform activity and audit trail',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/audit-logs',
            'priority' => 13,

            'components' => [
                [
                    'code' => 'audit-log-list',
                    'name' => 'All Logs',
                    'route' => '/audit-logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Audit Logs'],
                        ['code' => 'export', 'name' => 'Export Logs'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 13b. Access Logs (PII access tracking)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'access-logs',
            'name' => 'Access Logs',
            'description' => 'Track access to sensitive fields and PII data',
            'icon' => 'ShieldCheckIcon',
            'route' => '/access-logs',
            'priority' => 14,

            'components' => [
                [
                    'code' => 'access-log-list',
                    'name' => 'All Access Logs',
                    'route' => '/access-logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Access Logs'],
                        ['code' => 'export', 'name' => 'Export Logs'],
                    ],
                ],
                [
                    'code' => 'pii-access',
                    'name' => 'PII Access',
                    'route' => '/access-logs/pii',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PII Access Logs'],
                        ['code' => 'export', 'name' => 'Export PII Logs'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 14. Analytics & Reports
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-analytics',
            'name' => 'Analytics',
            'description' => 'Platform analytics and reports',
            'icon' => 'ChartBarIcon',
            'route' => '/analytics',
            'priority' => 14,

            'components' => [
                [
                    'code' => 'analytics-dashboard',
                    'name' => 'Dashboard',
                    'route' => '/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Analytics'],
                    ],
                ],
                [
                    'code' => 'revenue-reports',
                    'name' => 'Revenue',
                    'route' => '/analytics/revenue',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Revenue'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
                [
                    'code' => 'tenant-analytics',
                    'name' => 'Tenant Analytics',
                    'route' => '/analytics/tenants',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tenant Analytics'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 15. SEO Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'seo-management',
            'name' => 'SEO',
            'description' => 'Search engine optimization and meta tag management',
            'icon' => 'MagnifyingGlassIcon',
            'route' => '/seo',
            'priority' => 15,

            'components' => [
                [
                    'code' => 'seo-settings',
                    'name' => 'SEO Settings',
                    'route' => '/seo/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SEO Settings'],
                        ['code' => 'edit', 'name' => 'Edit SEO Settings'],
                    ],
                ],
                [
                    'code' => 'page-seo',
                    'name' => 'Page SEO',
                    'route' => '/seo/pages',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Page SEO'],
                        ['code' => 'edit', 'name' => 'Edit Page SEO'],
                    ],
                ],
                [
                    'code' => 'sitemap-management',
                    'name' => 'Sitemap',
                    'route' => '/seo/sitemap',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sitemap'],
                        ['code' => 'generate', 'name' => 'Generate Sitemap'],
                    ],
                ],
                [
                    'code' => 'analytics-integrations',
                    'name' => 'Analytics Integrations',
                    'route' => '/seo/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Integrations'],
                        ['code' => 'configure', 'name' => 'Configure Integrations'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 16. Lead Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'lead-management',
            'name' => 'Leads',
            'description' => 'Manage prospect leads and inquiries',
            'icon' => 'UserPlusIcon',
            'route' => '/leads',
            'priority' => 16,

            'components' => [
                [
                    'code' => 'lead-list',
                    'name' => 'All Leads',
                    'route' => '/leads',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Leads'],
                        ['code' => 'create', 'name' => 'Create Lead'],
                        ['code' => 'edit', 'name' => 'Edit Lead'],
                        ['code' => 'delete', 'name' => 'Delete Lead'],
                        ['code' => 'assign', 'name' => 'Assign Lead'],
                        ['code' => 'convert', 'name' => 'Convert Lead'],
                    ],
                ],
                [
                    'code' => 'lead-pipeline',
                    'name' => 'Pipeline',
                    'route' => '/leads/pipeline',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pipeline'],
                        ['code' => 'manage', 'name' => 'Manage Pipeline'],
                    ],
                ],
                [
                    'code' => 'lead-analytics',
                    'name' => 'Lead Analytics',
                    'route' => '/leads/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Analytics'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 17. Newsletter Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'newsletter-management',
            'name' => 'Newsletter',
            'description' => 'Manage newsletter subscribers and campaigns',
            'icon' => 'EnvelopeIcon',
            'route' => '/newsletter',
            'priority' => 17,

            'components' => [
                [
                    'code' => 'subscriber-list',
                    'name' => 'Subscribers',
                    'route' => '/newsletter/subscribers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Subscribers'],
                        ['code' => 'create', 'name' => 'Add Subscriber'],
                        ['code' => 'delete', 'name' => 'Remove Subscriber'],
                        ['code' => 'import', 'name' => 'Import Subscribers'],
                        ['code' => 'export', 'name' => 'Export Subscribers'],
                    ],
                ],
                [
                    'code' => 'newsletter-settings',
                    'name' => 'Settings',
                    'route' => '/newsletter/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 18. Affiliate Program
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'affiliate-program',
            'name' => 'Affiliates',
            'description' => 'Manage affiliate/referral program',
            'icon' => 'UsersIcon',
            'route' => '/affiliates',
            'priority' => 18,

            'components' => [
                [
                    'code' => 'affiliate-list',
                    'name' => 'All Affiliates',
                    'route' => '/affiliates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Affiliates'],
                        ['code' => 'create', 'name' => 'Create Affiliate'],
                        ['code' => 'edit', 'name' => 'Edit Affiliate'],
                        ['code' => 'delete', 'name' => 'Delete Affiliate'],
                        ['code' => 'approve', 'name' => 'Approve Affiliate'],
                        ['code' => 'suspend', 'name' => 'Suspend Affiliate'],
                    ],
                ],
                [
                    'code' => 'affiliate-referrals',
                    'name' => 'Referrals',
                    'route' => '/affiliates/referrals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Referrals'],
                        ['code' => 'approve-commission', 'name' => 'Approve Commission'],
                    ],
                ],
                [
                    'code' => 'affiliate-payouts',
                    'name' => 'Payouts',
                    'route' => '/affiliates/payouts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payouts'],
                        ['code' => 'create', 'name' => 'Create Payout'],
                        ['code' => 'process', 'name' => 'Process Payout'],
                    ],
                ],
                [
                    'code' => 'affiliate-settings',
                    'name' => 'Settings',
                    'route' => '/affiliates/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
                [
                    'code' => 'affiliate-analytics',
                    'name' => 'Analytics',
                    'route' => '/affiliates/analytics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Analytics'],
                        ['code' => 'export', 'name' => 'Export Reports'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 19. Social Authentication
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'social-authentication',
            'name' => 'Social Login',
            'description' => 'Configure social authentication providers',
            'icon' => 'ShareIcon',
            'route' => '/social-auth',
            'priority' => 19,

            'components' => [
                [
                    'code' => 'social-providers',
                    'name' => 'Providers',
                    'route' => '/social-auth/providers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Providers'],
                        ['code' => 'configure', 'name' => 'Configure Provider'],
                    ],
                ],
                [
                    'code' => 'social-accounts',
                    'name' => 'Linked Accounts',
                    'route' => '/social-auth/accounts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Accounts'],
                        ['code' => 'unlink', 'name' => 'Unlink Account'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 20. Tenant Operations (Clone / Migrate / Export / Freeze / Archive)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'tenant-operations',
            'name' => 'Tenant Operations',
            'description' => 'Tenant clone, migration between regions/databases, full data export, freeze/unfreeze, archive',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => '/tenant-operations',
            'priority' => 20,

            'components' => [
                [
                    'code' => 'tenant-clone', 'name' => 'Tenant Cloning', 'route' => '/tenant-operations/clone',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Clones'],
                        ['code' => 'clone', 'name' => 'Clone Tenant'],
                    ],
                ],
                [
                    'code' => 'tenant-migration', 'name' => 'Tenant Migration', 'route' => '/tenant-operations/migration',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Migrations'],
                        ['code' => 'plan', 'name' => 'Plan Migration'],
                        ['code' => 'execute', 'name' => 'Execute Migration'],
                        ['code' => 'rollback', 'name' => 'Rollback Migration'],
                    ],
                ],
                [
                    'code' => 'tenant-export', 'name' => 'Full Tenant Export (GDPR)', 'route' => '/tenant-operations/export',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Exports'],
                        ['code' => 'request', 'name' => 'Request Tenant Export'],
                        ['code' => 'download', 'name' => 'Download Export'],
                    ],
                ],
                [
                    'code' => 'tenant-import', 'name' => 'Tenant Import (Migration In)', 'route' => '/tenant-operations/import',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Imports'],
                        ['code' => 'upload', 'name' => 'Upload Import Bundle'],
                        ['code' => 'process', 'name' => 'Process Import'],
                    ],
                ],
                [
                    'code' => 'tenant-freeze', 'name' => 'Freeze / Unfreeze Tenant', 'route' => '/tenant-operations/freeze',
                    'actions' => [
                        ['code' => 'freeze', 'name' => 'Freeze Tenant'],
                        ['code' => 'unfreeze', 'name' => 'Unfreeze Tenant'],
                    ],
                ],
                [
                    'code' => 'tenant-archive', 'name' => 'Archive / Restore Tenant', 'route' => '/tenant-operations/archive',
                    'actions' => [
                        ['code' => 'archive', 'name' => 'Archive Tenant'],
                        ['code' => 'restore', 'name' => 'Restore Tenant'],
                    ],
                ],
                [
                    'code' => 'bulk-actions', 'name' => 'Bulk Tenant Actions', 'route' => '/tenant-operations/bulk',
                    'actions' => [
                        ['code' => 'bulk-email', 'name' => 'Bulk Email'],
                        ['code' => 'bulk-suspend', 'name' => 'Bulk Suspend'],
                        ['code' => 'bulk-plan-change', 'name' => 'Bulk Plan Change'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 21. Backup & Restore (Tenant-Level)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'backup-restore',
            'name' => 'Backup & Restore',
            'description' => 'Scheduled tenant backups, on-demand backups, point-in-time restore',
            'icon' => 'CircleStackIcon',
            'route' => '/backup-restore',
            'priority' => 21,

            'components' => [
                [
                    'code' => 'backup-dashboard', 'name' => 'Backup Dashboard', 'route' => '/backup-restore',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Backups'],
                    ],
                ],
                [
                    'code' => 'backup-schedules', 'name' => 'Backup Schedules', 'route' => '/backup-restore/schedules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Schedules'],
                        ['code' => 'create', 'name' => 'Create Schedule'],
                        ['code' => 'update', 'name' => 'Update Schedule'],
                        ['code' => 'delete', 'name' => 'Delete Schedule'],
                    ],
                ],
                [
                    'code' => 'manual-backups', 'name' => 'On-Demand Backups', 'route' => '/backup-restore/manual',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Manual Backups'],
                        ['code' => 'create', 'name' => 'Create Manual Backup'],
                    ],
                ],
                [
                    'code' => 'restore', 'name' => 'Restore', 'route' => '/backup-restore/restore',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Restore Points'],
                        ['code' => 'restore', 'name' => 'Restore Tenant'],
                        ['code' => 'pitr', 'name' => 'Point-in-Time Restore'],
                    ],
                ],
                [
                    'code' => 'backup-storage', 'name' => 'Backup Storage', 'route' => '/backup-restore/storage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Storage'],
                        ['code' => 'configure', 'name' => 'Configure Storage Backend'],
                    ],
                ],
                [
                    'code' => 'retention-policies', 'name' => 'Backup Retention', 'route' => '/backup-restore/retention',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Retention Policies'],
                        ['code' => 'manage', 'name' => 'Manage Retention'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 22. Coupons & Promotions
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'coupons-promotions',
            'name' => 'Coupons & Promotions',
            'description' => 'Discount codes, promotional campaigns, time-limited offers',
            'icon' => 'TagIcon',
            'route' => '/coupons',
            'priority' => 22,

            'components' => [
                [
                    'code' => 'coupons', 'name' => 'Coupons', 'route' => '/coupons',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Coupons'],
                        ['code' => 'create', 'name' => 'Create Coupon'],
                        ['code' => 'update', 'name' => 'Update Coupon'],
                        ['code' => 'delete', 'name' => 'Delete Coupon'],
                        ['code' => 'archive', 'name' => 'Archive Coupon'],
                        ['code' => 'bulk-generate', 'name' => 'Bulk Generate Codes'],
                    ],
                ],
                [
                    'code' => 'campaigns', 'name' => 'Promotional Campaigns', 'route' => '/coupons/campaigns',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Campaigns'],
                        ['code' => 'create', 'name' => 'Create Campaign'],
                        ['code' => 'launch', 'name' => 'Launch Campaign'],
                        ['code' => 'end', 'name' => 'End Campaign'],
                    ],
                ],
                [
                    'code' => 'redemptions', 'name' => 'Redemptions', 'route' => '/coupons/redemptions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Redemptions'],
                        ['code' => 'export', 'name' => 'Export Redemptions'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 23. Add-ons & Metered Billing
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'addons-metered',
            'name' => 'Add-ons & Metered Billing',
            'description' => 'À-la-carte add-ons, usage-based billing, pay-as-you-go',
            'icon' => 'CubeIcon',
            'route' => '/addons-metered',
            'priority' => 23,

            'components' => [
                [
                    'code' => 'addons', 'name' => 'Add-ons Catalog', 'route' => '/addons-metered/addons',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Add-ons'],
                        ['code' => 'create', 'name' => 'Create Add-on'],
                        ['code' => 'update', 'name' => 'Update Add-on'],
                        ['code' => 'archive', 'name' => 'Archive Add-on'],
                    ],
                ],
                [
                    'code' => 'metered-meters', 'name' => 'Usage Meters', 'route' => '/addons-metered/meters',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Meters'],
                        ['code' => 'create', 'name' => 'Create Meter'],
                        ['code' => 'configure', 'name' => 'Configure Meter'],
                    ],
                ],
                [
                    'code' => 'metered-events', 'name' => 'Usage Events', 'route' => '/addons-metered/events',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Events'],
                        ['code' => 'export', 'name' => 'Export Events'],
                    ],
                ],
                [
                    'code' => 'pay-as-you-go', 'name' => 'Pay-As-You-Go', 'route' => '/addons-metered/payg',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PAYG'],
                        ['code' => 'configure', 'name' => 'Configure PAYG Pricing'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 24. Refunds & Credit Notes
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'refunds-credits',
            'name' => 'Refunds & Credit Notes',
            'description' => 'Process refunds, issue credit notes, apply credits',
            'icon' => 'ArrowUturnLeftIcon',
            'route' => '/refunds-credits',
            'priority' => 24,

            'components' => [
                [
                    'code' => 'refunds', 'name' => 'Refunds', 'route' => '/refunds-credits/refunds',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Refunds'],
                        ['code' => 'create', 'name' => 'Create Refund'],
                        ['code' => 'approve', 'name' => 'Approve Refund'],
                        ['code' => 'process', 'name' => 'Process Refund'],
                    ],
                ],
                [
                    'code' => 'credit-notes', 'name' => 'Credit Notes', 'route' => '/refunds-credits/credit-notes',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Credit Notes'],
                        ['code' => 'create', 'name' => 'Issue Credit Note'],
                        ['code' => 'apply', 'name' => 'Apply Credit'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 25. Dunning Workflow (Failed Payment Recovery)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'dunning',
            'name' => 'Dunning & Recovery',
            'description' => 'Failed payment retries, dunning emails, grace periods, churn prevention',
            'icon' => 'BellAlertIcon',
            'route' => '/dunning',
            'priority' => 25,

            'components' => [
                [
                    'code' => 'dunning-dashboard', 'name' => 'Dunning Dashboard', 'route' => '/dunning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Dunning Dashboard'],
                    ],
                ],
                [
                    'code' => 'dunning-rules', 'name' => 'Dunning Rules', 'route' => '/dunning/rules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rules'],
                        ['code' => 'manage', 'name' => 'Manage Rules'],
                    ],
                ],
                [
                    'code' => 'failed-payments', 'name' => 'Failed Payments', 'route' => '/dunning/failed-payments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Failed Payments'],
                        ['code' => 'retry', 'name' => 'Retry Payment'],
                        ['code' => 'mark-uncollectible', 'name' => 'Mark Uncollectible'],
                    ],
                ],
                [
                    'code' => 'recovery-emails', 'name' => 'Recovery Email Templates', 'route' => '/dunning/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'manage', 'name' => 'Manage Templates'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 26. Tax Engine
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'tax-engine',
            'name' => 'Tax Engine',
            'description' => 'Regional tax rates, VAT/GST validation, tax provider integration',
            'icon' => 'ReceiptPercentIcon',
            'route' => '/tax-engine',
            'priority' => 26,

            'components' => [
                [
                    'code' => 'tax-rates', 'name' => 'Tax Rates by Region', 'route' => '/tax-engine/rates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rates'],
                        ['code' => 'manage', 'name' => 'Manage Rates'],
                    ],
                ],
                [
                    'code' => 'tax-id-validation', 'name' => 'VAT / Tax ID Validation', 'route' => '/tax-engine/validation',
                    'actions' => [
                        ['code' => 'validate', 'name' => 'Validate Tax ID'],
                    ],
                ],
                [
                    'code' => 'tax-providers', 'name' => 'Tax Providers (Stripe Tax / Avalara / TaxJar)', 'route' => '/tax-engine/providers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Providers'],
                        ['code' => 'configure', 'name' => 'Configure Provider'],
                    ],
                ],
                [
                    'code' => 'tax-reports', 'name' => 'Tax Reports', 'route' => '/tax-engine/reports',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reports'],
                        ['code' => 'generate', 'name' => 'Generate Tax Report'],
                        ['code' => 'export', 'name' => 'Export'],
                    ],
                ],
                [
                    'code' => 'w9-1099', 'name' => 'W-9 / 1099 Forms', 'route' => '/tax-engine/w9-1099',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Forms'],
                        ['code' => 'generate', 'name' => 'Generate Forms'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 27. Multi-Currency
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'multi-currency',
            'name' => 'Multi-Currency',
            'description' => 'Currency exchange rates, regional pricing, currency conversion',
            'icon' => 'GlobeAltIcon',
            'route' => '/multi-currency',
            'priority' => 27,

            'components' => [
                [
                    'code' => 'currencies', 'name' => 'Currencies', 'route' => '/multi-currency/currencies',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Currencies'],
                        ['code' => 'manage', 'name' => 'Manage Currencies'],
                    ],
                ],
                [
                    'code' => 'exchange-rates', 'name' => 'Exchange Rates', 'route' => '/multi-currency/rates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rates'],
                        ['code' => 'sync', 'name' => 'Sync Rates'],
                        ['code' => 'manual', 'name' => 'Set Manual Rate'],
                    ],
                ],
                [
                    'code' => 'regional-pricing', 'name' => 'Regional Pricing', 'route' => '/multi-currency/regional',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Regional Prices'],
                        ['code' => 'manage', 'name' => 'Manage Regional Prices'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 28. Reseller / Channel Partners
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'reseller-partners',
            'name' => 'Reseller / Channel Partners',
            'description' => 'White-label resellers, partner-managed tenants, commission rules, partner portal',
            'icon' => 'UsersIcon',
            'route' => '/partners',
            'priority' => 28,

            'components' => [
                [
                    'code' => 'partners', 'name' => 'Partners', 'route' => '/partners',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Partners'],
                        ['code' => 'create', 'name' => 'Create Partner'],
                        ['code' => 'update', 'name' => 'Update Partner'],
                        ['code' => 'approve', 'name' => 'Approve Partner'],
                        ['code' => 'suspend', 'name' => 'Suspend Partner'],
                    ],
                ],
                [
                    'code' => 'partner-commissions', 'name' => 'Commission Rules', 'route' => '/partners/commissions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Commissions'],
                        ['code' => 'manage', 'name' => 'Manage Commission Rules'],
                        ['code' => 'payout', 'name' => 'Process Payout'],
                    ],
                ],
                [
                    'code' => 'partner-tenants', 'name' => 'Partner-Managed Tenants', 'route' => '/partners/tenants',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Partner Tenants'],
                        ['code' => 'reassign', 'name' => 'Reassign Tenant'],
                    ],
                ],
                [
                    'code' => 'partner-portal', 'name' => 'Partner Portal', 'route' => '/partners/portal',
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Portal'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 29. White-Label per Tenant
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'white-label',
            'name' => 'White-Label per Tenant',
            'description' => 'Custom domains, SSL provisioning, per-tenant branding, custom CSS',
            'icon' => 'PaintBrushIcon',
            'route' => '/white-label',
            'priority' => 29,

            'components' => [
                [
                    'code' => 'custom-domains', 'name' => 'Custom Domains', 'route' => '/white-label/domains',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custom Domains'],
                        ['code' => 'add', 'name' => 'Add Custom Domain'],
                        ['code' => 'verify', 'name' => 'Verify Domain'],
                        ['code' => 'remove', 'name' => 'Remove Domain'],
                    ],
                ],
                [
                    'code' => 'ssl-provisioning', 'name' => 'SSL Provisioning (Lets Encrypt)', 'route' => '/white-label/ssl',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SSL'],
                        ['code' => 'provision', 'name' => 'Provision SSL'],
                        ['code' => 'renew', 'name' => 'Renew SSL'],
                        ['code' => 'upload', 'name' => 'Upload Custom Cert'],
                    ],
                ],
                [
                    'code' => 'tenant-branding', 'name' => 'Tenant Branding', 'route' => '/white-label/branding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Branding'],
                        ['code' => 'manage', 'name' => 'Manage Per-Tenant Branding'],
                    ],
                ],
                [
                    'code' => 'custom-css', 'name' => 'Custom CSS / Code Injection', 'route' => '/white-label/custom-css',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custom CSS'],
                        ['code' => 'edit', 'name' => 'Edit Custom CSS'],
                    ],
                ],
                [
                    'code' => 'tenant-email-branding', 'name' => 'Tenant Email Sender (DKIM)', 'route' => '/white-label/email',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Senders'],
                        ['code' => 'configure', 'name' => 'Configure DKIM per Tenant'],
                        ['code' => 'verify', 'name' => 'Verify Email DNS'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 30. Feature Flags / Rollouts
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'feature-flags',
            'name' => 'Feature Flags',
            'description' => 'Per-tenant feature flags, gradual rollouts, A/B experiments',
            'icon' => 'BeakerIcon',
            'route' => '/feature-flags',
            'priority' => 30,

            'components' => [
                [
                    'code' => 'flags', 'name' => 'Feature Flags', 'route' => '/feature-flags',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Flags'],
                        ['code' => 'create', 'name' => 'Create Flag'],
                        ['code' => 'update', 'name' => 'Update Flag'],
                        ['code' => 'archive', 'name' => 'Archive Flag'],
                        ['code' => 'toggle', 'name' => 'Toggle Flag'],
                    ],
                ],
                [
                    'code' => 'rollouts', 'name' => 'Gradual Rollouts', 'route' => '/feature-flags/rollouts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rollouts'],
                        ['code' => 'configure', 'name' => 'Configure Rollout'],
                    ],
                ],
                [
                    'code' => 'experiments', 'name' => 'A/B Experiments', 'route' => '/feature-flags/experiments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Experiments'],
                        ['code' => 'start', 'name' => 'Start Experiment'],
                        ['code' => 'stop', 'name' => 'Stop Experiment'],
                    ],
                ],
                [
                    'code' => 'tenant-flags', 'name' => 'Per-Tenant Flag Overrides', 'route' => '/feature-flags/tenant-overrides',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Overrides'],
                        ['code' => 'manage', 'name' => 'Manage Overrides'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 31. Tenant Communications
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'tenant-communications',
            'name' => 'Tenant Communications',
            'description' => 'Broadcast announcements, in-app messages, targeted email blasts',
            'icon' => 'MegaphoneIcon',
            'route' => '/communications',
            'priority' => 31,

            'components' => [
                [
                    'code' => 'broadcasts', 'name' => 'Broadcasts (In-App)', 'route' => '/communications/broadcasts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Broadcasts'],
                        ['code' => 'create', 'name' => 'Create Broadcast'],
                        ['code' => 'publish', 'name' => 'Publish Broadcast'],
                        ['code' => 'dismiss-all', 'name' => 'Dismiss Globally'],
                    ],
                ],
                [
                    'code' => 'email-blasts', 'name' => 'Email Blasts to Tenants', 'route' => '/communications/email',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Blasts'],
                        ['code' => 'create', 'name' => 'Create Email Blast'],
                        ['code' => 'send', 'name' => 'Send Email Blast'],
                    ],
                ],
                [
                    'code' => 'targeted-messages', 'name' => 'Targeted Messages', 'route' => '/communications/targeted',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Targeted Messages'],
                        ['code' => 'create', 'name' => 'Create Targeted Message'],
                    ],
                ],
                [
                    'code' => 'maintenance-windows', 'name' => 'Maintenance Windows', 'route' => '/communications/maintenance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Maintenance Windows'],
                        ['code' => 'schedule', 'name' => 'Schedule Maintenance'],
                        ['code' => 'cancel', 'name' => 'Cancel Maintenance'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 32. Status Page & Incident Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'status-incidents',
            'name' => 'Status Page & Incidents',
            'description' => 'Public status page, incident management, postmortems, SLA reporting',
            'icon' => 'SignalIcon',
            'route' => '/status',
            'priority' => 32,

            'components' => [
                [
                    'code' => 'status-page', 'name' => 'Public Status Page', 'route' => '/status',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Status Page'],
                        ['code' => 'configure', 'name' => 'Configure Status Page'],
                    ],
                ],
                [
                    'code' => 'service-components', 'name' => 'Service Components', 'route' => '/status/components',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Components'],
                        ['code' => 'manage', 'name' => 'Manage Components'],
                        ['code' => 'set-status', 'name' => 'Set Component Status'],
                    ],
                ],
                [
                    'code' => 'incidents', 'name' => 'Incidents', 'route' => '/status/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Incidents'],
                        ['code' => 'create', 'name' => 'Open Incident'],
                        ['code' => 'update', 'name' => 'Update Incident'],
                        ['code' => 'resolve', 'name' => 'Resolve Incident'],
                    ],
                ],
                [
                    'code' => 'postmortems', 'name' => 'Postmortems', 'route' => '/status/postmortems',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Postmortems'],
                        ['code' => 'create', 'name' => 'Create Postmortem'],
                        ['code' => 'publish', 'name' => 'Publish Postmortem'],
                    ],
                ],
                [
                    'code' => 'sla-reporting', 'name' => 'SLA Reporting', 'route' => '/status/sla',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SLA Report'],
                        ['code' => 'export', 'name' => 'Export SLA Report'],
                    ],
                ],
                [
                    'code' => 'uptime-monitoring', 'name' => 'Uptime Monitoring', 'route' => '/status/uptime',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Uptime'],
                        ['code' => 'configure', 'name' => 'Configure Monitors'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 33. Customer Success
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'customer-success',
            'name' => 'Customer Success',
            'description' => 'Tenant health score, churn risk, onboarding tracking, CSM assignment, NPS',
            'icon' => 'HeartIcon',
            'route' => '/customer-success',
            'priority' => 33,

            'components' => [
                [
                    'code' => 'health-score', 'name' => 'Tenant Health Score', 'route' => '/customer-success/health',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Health Scores'],
                        ['code' => 'configure', 'name' => 'Configure Scoring'],
                    ],
                ],
                [
                    'code' => 'churn-risk', 'name' => 'Churn Risk Predictions', 'route' => '/customer-success/churn',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Churn Risk'],
                        ['code' => 'run', 'name' => 'Run Prediction'],
                    ],
                ],
                [
                    'code' => 'onboarding-progress', 'name' => 'Onboarding Progress', 'route' => '/customer-success/onboarding-progress',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Onboarding Progress'],
                    ],
                ],
                [
                    'code' => 'csm-assignment', 'name' => 'CSM Assignment', 'route' => '/customer-success/csm',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CSM Assignments'],
                        ['code' => 'assign', 'name' => 'Assign CSM'],
                    ],
                ],
                [
                    'code' => 'nps-csat', 'name' => 'NPS / CSAT Surveys', 'route' => '/customer-success/nps',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View NPS Scores'],
                        ['code' => 'send', 'name' => 'Send NPS Survey'],
                        ['code' => 'export', 'name' => 'Export NPS Data'],
                    ],
                ],
                [
                    'code' => 'success-playbooks', 'name' => 'Success Playbooks', 'route' => '/customer-success/playbooks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Playbooks'],
                        ['code' => 'manage', 'name' => 'Manage Playbooks'],
                        ['code' => 'execute', 'name' => 'Execute Playbook'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 34. Help Center & Support Tickets (Platform-Side for Tenants)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'help-center',
            'name' => 'Help Center',
            'description' => 'Knowledge base for tenants, in-app help, support tickets, live chat',
            'icon' => 'LifebuoyIcon',
            'route' => '/help-center',
            'priority' => 34,

            'components' => [
                [
                    'code' => 'kb-articles', 'name' => 'Knowledge Base Articles', 'route' => '/help-center/articles',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Articles'],
                        ['code' => 'create', 'name' => 'Create Article'],
                        ['code' => 'update', 'name' => 'Update Article'],
                        ['code' => 'delete', 'name' => 'Delete Article'],
                        ['code' => 'publish', 'name' => 'Publish Article'],
                    ],
                ],
                [
                    'code' => 'video-tutorials', 'name' => 'Video Tutorials', 'route' => '/help-center/videos',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Videos'],
                        ['code' => 'manage', 'name' => 'Manage Videos'],
                    ],
                ],
                [
                    'code' => 'tenant-tickets', 'name' => 'Tenant Support Tickets', 'route' => '/help-center/tickets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tickets'],
                        ['code' => 'reply', 'name' => 'Reply to Ticket'],
                        ['code' => 'assign', 'name' => 'Assign Ticket'],
                        ['code' => 'escalate', 'name' => 'Escalate Ticket'],
                        ['code' => 'close', 'name' => 'Close Ticket'],
                    ],
                ],
                [
                    'code' => 'live-chat', 'name' => 'Live Chat', 'route' => '/help-center/chat',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Chats'],
                        ['code' => 'reply', 'name' => 'Reply to Chat'],
                        ['code' => 'configure', 'name' => 'Configure Chat'],
                    ],
                ],
                [
                    'code' => 'in-app-help', 'name' => 'In-App Help & Tours', 'route' => '/help-center/in-app',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tours'],
                        ['code' => 'create', 'name' => 'Create Tour'],
                        ['code' => 'publish', 'name' => 'Publish Tour'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 35. Compliance & Legal
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'compliance-legal',
            'name' => 'Compliance & Legal',
            'description' => 'DPA management, subprocessor registry, ToS/Privacy versions, certifications',
            'icon' => 'ScaleIcon',
            'route' => '/compliance-legal',
            'priority' => 35,

            'components' => [
                [
                    'code' => 'dpa', 'name' => 'Data Processing Agreements', 'route' => '/compliance-legal/dpa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DPAs'],
                        ['code' => 'manage', 'name' => 'Manage DPA Templates'],
                        ['code' => 'sign', 'name' => 'Capture Signed DPA'],
                    ],
                ],
                [
                    'code' => 'subprocessors', 'name' => 'Subprocessor Registry', 'route' => '/compliance-legal/subprocessors',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Subprocessors'],
                        ['code' => 'manage', 'name' => 'Manage Subprocessors'],
                        ['code' => 'notify', 'name' => 'Notify Tenants of Changes'],
                    ],
                ],
                [
                    'code' => 'tos-versions', 'name' => 'Terms of Service Versions', 'route' => '/compliance-legal/tos',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View ToS Versions'],
                        ['code' => 'create', 'name' => 'Publish New Version'],
                        ['code' => 'require-acceptance', 'name' => 'Require Re-Acceptance'],
                    ],
                ],
                [
                    'code' => 'privacy-versions', 'name' => 'Privacy Policy Versions', 'route' => '/compliance-legal/privacy',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Versions'],
                        ['code' => 'create', 'name' => 'Publish New Version'],
                    ],
                ],
                [
                    'code' => 'certifications', 'name' => 'Compliance Certifications', 'route' => '/compliance-legal/certifications',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Certifications'],
                        ['code' => 'upload', 'name' => 'Upload Certification (SOC2/ISO)'],
                    ],
                ],
                [
                    'code' => 'data-residency', 'name' => 'Data Residency per Tenant', 'route' => '/compliance-legal/data-residency',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Residency'],
                        ['code' => 'configure', 'name' => 'Configure Residency Rules'],
                    ],
                ],
                [
                    'code' => 'platform-dsar', 'name' => 'Platform-Level DSAR', 'route' => '/compliance-legal/dsar',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DSARs'],
                        ['code' => 'fulfill', 'name' => 'Fulfill DSAR'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 36. Multi-Region / Geo
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'multi-region',
            'name' => 'Multi-Region & Geo',
            'description' => 'Region management, geo-distributed tenants, CDN configuration',
            'icon' => 'GlobeAmericasIcon',
            'route' => '/multi-region',
            'priority' => 36,

            'components' => [
                [
                    'code' => 'regions', 'name' => 'Regions', 'route' => '/multi-region/regions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Regions'],
                        ['code' => 'manage', 'name' => 'Manage Regions'],
                        ['code' => 'enable', 'name' => 'Enable Region'],
                        ['code' => 'disable', 'name' => 'Disable Region'],
                    ],
                ],
                [
                    'code' => 'tenant-region-assignment', 'name' => 'Tenant Region Assignment', 'route' => '/multi-region/tenant-assignment',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Assignments'],
                        ['code' => 'reassign', 'name' => 'Reassign Tenant Region'],
                    ],
                ],
                [
                    'code' => 'cdn-config', 'name' => 'CDN Configuration', 'route' => '/multi-region/cdn',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CDN'],
                        ['code' => 'configure', 'name' => 'Configure CDN'],
                        ['code' => 'purge', 'name' => 'Purge CDN Cache'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 37. Security Center
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'security-center',
            'name' => 'Security Center',
            'description' => 'Pentest reports, security incidents, vulnerability disclosures, bug bounty',
            'icon' => 'ShieldExclamationIcon',
            'route' => '/security-center',
            'priority' => 37,

            'components' => [
                [
                    'code' => 'security-dashboard', 'name' => 'Security Dashboard', 'route' => '/security-center',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Dashboard'],
                    ],
                ],
                [
                    'code' => 'pentest-reports', 'name' => 'Penetration Test Reports', 'route' => '/security-center/pentest',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reports'],
                        ['code' => 'upload', 'name' => 'Upload Report'],
                        ['code' => 'share', 'name' => 'Share with Tenant'],
                    ],
                ],
                [
                    'code' => 'security-incidents', 'name' => 'Security Incidents', 'route' => '/security-center/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Incidents'],
                        ['code' => 'create', 'name' => 'Log Incident'],
                        ['code' => 'notify', 'name' => 'Notify Affected Tenants'],
                    ],
                ],
                [
                    'code' => 'vulnerability-disclosures', 'name' => 'Vulnerability Disclosures', 'route' => '/security-center/vulnerabilities',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Disclosures'],
                        ['code' => 'manage', 'name' => 'Manage Disclosures'],
                    ],
                ],
                [
                    'code' => 'bug-bounty', 'name' => 'Bug Bounty Program', 'route' => '/security-center/bug-bounty',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submissions'],
                        ['code' => 'manage', 'name' => 'Manage Program'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 38. Email Deliverability (Platform)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'email-deliverability',
            'name' => 'Email Deliverability',
            'description' => 'DKIM/SPF/DMARC, suppression lists, bounces, sender reputation',
            'icon' => 'EnvelopeIcon',
            'route' => '/email-deliverability',
            'priority' => 38,

            'components' => [
                [
                    'code' => 'dns-setup', 'name' => 'DKIM / SPF / DMARC', 'route' => '/email-deliverability/dns',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DNS Records'],
                        ['code' => 'configure', 'name' => 'Configure DNS'],
                        ['code' => 'verify', 'name' => 'Verify DNS Records'],
                    ],
                ],
                [
                    'code' => 'suppression-list', 'name' => 'Suppression List', 'route' => '/email-deliverability/suppression',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Suppression'],
                        ['code' => 'remove', 'name' => 'Remove from Suppression'],
                    ],
                ],
                [
                    'code' => 'bounce-complaints', 'name' => 'Bounces & Complaints', 'route' => '/email-deliverability/bounces',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bounces'],
                        ['code' => 'export', 'name' => 'Export Bounces'],
                    ],
                ],
                [
                    'code' => 'sender-reputation', 'name' => 'Sender Reputation', 'route' => '/email-deliverability/reputation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reputation'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 39. API Gateway (Per-Tenant Rate Limits / Quotas)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'api-gateway',
            'name' => 'API Gateway',
            'description' => 'Per-tenant API rate limits, quotas, usage analytics, gateway routing',
            'icon' => 'CommandLineIcon',
            'route' => '/api-gateway',
            'priority' => 39,

            'components' => [
                [
                    'code' => 'rate-limits', 'name' => 'Rate Limits per Tenant', 'route' => '/api-gateway/rate-limits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rate Limits'],
                        ['code' => 'manage', 'name' => 'Manage Rate Limits'],
                    ],
                ],
                [
                    'code' => 'api-quotas', 'name' => 'API Quotas per Tenant', 'route' => '/api-gateway/quotas',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View API Quotas'],
                        ['code' => 'configure', 'name' => 'Configure Quotas'],
                    ],
                ],
                [
                    'code' => 'api-usage-analytics', 'name' => 'API Usage Analytics', 'route' => '/api-gateway/usage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Usage'],
                        ['code' => 'export', 'name' => 'Export Usage'],
                    ],
                ],
                [
                    'code' => 'gateway-routing', 'name' => 'Gateway Routing', 'route' => '/api-gateway/routing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Routing'],
                        ['code' => 'configure', 'name' => 'Configure Routing'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40. Resource Provisioning
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'resource-provisioning',
            'name' => 'Resource Provisioning',
            'description' => 'Database server pools, storage backends, compute, auto-scaling',
            'icon' => 'ServerStackIcon',
            'route' => '/provisioning',
            'priority' => 40,

            'components' => [
                [
                    'code' => 'db-pools', 'name' => 'Database Server Pools', 'route' => '/provisioning/db-pools',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pools'],
                        ['code' => 'manage', 'name' => 'Manage Pools'],
                        ['code' => 'rebalance', 'name' => 'Rebalance Pools'],
                    ],
                ],
                [
                    'code' => 'storage-backends', 'name' => 'Storage Backends (S3/GCS/Azure)', 'route' => '/provisioning/storage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Storage'],
                        ['code' => 'configure', 'name' => 'Configure Storage'],
                    ],
                ],
                [
                    'code' => 'compute-resources', 'name' => 'Compute Resources', 'route' => '/provisioning/compute',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Compute'],
                        ['code' => 'manage', 'name' => 'Manage Compute'],
                    ],
                ],
                [
                    'code' => 'auto-scaling', 'name' => 'Auto-Scaling Rules', 'route' => '/provisioning/auto-scaling',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rules'],
                        ['code' => 'manage', 'name' => 'Manage Auto-Scaling'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40b. Platform Security (Landlord RBAC, Impersonation, Sessions, MFA)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-security',
            'name' => 'Platform Security',
            'description' => 'Landlord-side RBAC, tenant impersonation with audit, staff sessions, MFA enforcement, IP allowlist',
            'icon' => 'ShieldCheckIcon',
            'route' => '/platform-security',
            'priority' => 40,

            'components' => [
                [
                    'code' => 'landlord-roles', 'name' => 'Landlord Roles & Permissions', 'route' => '/platform-security/roles',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Roles'],
                        ['code' => 'create', 'name' => 'Create Role'],
                        ['code' => 'update', 'name' => 'Update Role'],
                        ['code' => 'delete', 'name' => 'Delete Role'],
                        ['code' => 'assign', 'name' => 'Assign Role'],
                    ],
                ],
                [
                    'code' => 'impersonation', 'name' => 'Tenant Impersonation', 'route' => '/platform-security/impersonation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Impersonation Sessions'],
                        ['code' => 'start', 'name' => 'Start Impersonation'],
                        ['code' => 'end', 'name' => 'End Impersonation'],
                        ['code' => 'audit', 'name' => 'View Impersonation Audit Trail'],
                    ],
                ],
                [
                    'code' => 'staff-sessions', 'name' => 'Staff Sessions', 'route' => '/platform-security/sessions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Active Sessions'],
                        ['code' => 'force-logout', 'name' => 'Force Logout'],
                    ],
                ],
                [
                    'code' => 'staff-mfa', 'name' => 'Staff MFA Enforcement', 'route' => '/platform-security/mfa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View MFA Status'],
                        ['code' => 'enforce', 'name' => 'Enforce MFA'],
                        ['code' => 'reset', 'name' => 'Reset Staff MFA'],
                    ],
                ],
                [
                    'code' => 'staff-sso', 'name' => 'Staff SSO (SAML/OIDC)', 'route' => '/platform-security/sso',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SSO Config'],
                        ['code' => 'configure', 'name' => 'Configure Staff SSO'],
                    ],
                ],
                [
                    'code' => 'ip-allowlist', 'name' => 'Staff IP Allowlist', 'route' => '/platform-security/ip-allowlist',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Allowlist'],
                        ['code' => 'manage', 'name' => 'Manage Allowlist'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40c. Secrets Management (KMS, Encryption Keys)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'secrets-management',
            'name' => 'Secrets Management',
            'description' => 'KMS, encryption-key lifecycle, per-tenant DEK, secret rotation',
            'icon' => 'KeyIcon',
            'route' => '/secrets',
            'priority' => 41,

            'components' => [
                [
                    'code' => 'kms', 'name' => 'KMS / Master Keys', 'route' => '/secrets/kms',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Master Keys'],
                        ['code' => 'rotate', 'name' => 'Rotate Master Key'],
                        ['code' => 'configure', 'name' => 'Configure KMS Provider'],
                    ],
                ],
                [
                    'code' => 'tenant-deks', 'name' => 'Per-Tenant Encryption Keys', 'route' => '/secrets/tenant-deks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DEKs'],
                        ['code' => 'rotate', 'name' => 'Rotate Tenant DEK'],
                    ],
                ],
                [
                    'code' => 'secrets-vault', 'name' => 'Secrets Vault', 'route' => '/secrets/vault',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Secrets'],
                        ['code' => 'create', 'name' => 'Store Secret'],
                        ['code' => 'rotate', 'name' => 'Rotate Secret'],
                        ['code' => 'revoke', 'name' => 'Revoke Secret'],
                    ],
                ],
                [
                    'code' => 'secret-audit', 'name' => 'Secret Access Audit', 'route' => '/secrets/audit',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Access Log'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40d. Outbound Webhooks (Platform Events to Tenants/Integrations)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'outbound-webhooks',
            'name' => 'Outbound Webhooks',
            'description' => 'Platform-event webhooks: tenant.provisioned, subscription.updated, invoice.paid, etc.',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => '/outbound-webhooks',
            'priority' => 42,

            'components' => [
                [
                    'code' => 'webhook-endpoints', 'name' => 'Webhook Endpoints', 'route' => '/outbound-webhooks/endpoints',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Endpoints'],
                        ['code' => 'create', 'name' => 'Create Endpoint'],
                        ['code' => 'update', 'name' => 'Update Endpoint'],
                        ['code' => 'delete', 'name' => 'Delete Endpoint'],
                        ['code' => 'test', 'name' => 'Send Test Event'],
                    ],
                ],
                [
                    'code' => 'event-catalog', 'name' => 'Event Catalog', 'route' => '/outbound-webhooks/events',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Event Types'],
                    ],
                ],
                [
                    'code' => 'delivery-logs', 'name' => 'Delivery Logs', 'route' => '/outbound-webhooks/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Logs'],
                        ['code' => 'replay', 'name' => 'Replay Delivery'],
                    ],
                ],
                [
                    'code' => 'webhook-signing', 'name' => 'Webhook Signing Secrets', 'route' => '/outbound-webhooks/signing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Signing Secrets'],
                        ['code' => 'rotate', 'name' => 'Rotate Signing Secret'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40e. Invoicing (PDF, Numbering, Tax Lines)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'invoicing',
            'name' => 'Invoicing',
            'description' => 'Invoice generation, PDF rendering, numbering, tax lines, custom branding',
            'icon' => 'DocumentTextIcon',
            'route' => '/invoicing',
            'priority' => 43,

            'components' => [
                [
                    'code' => 'invoices', 'name' => 'Invoices', 'route' => '/invoicing/invoices',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Invoices'],
                        ['code' => 'create', 'name' => 'Create Invoice'],
                        ['code' => 'update', 'name' => 'Update Invoice'],
                        ['code' => 'send', 'name' => 'Send Invoice'],
                        ['code' => 'void', 'name' => 'Void Invoice'],
                        ['code' => 'mark-paid', 'name' => 'Mark Paid'],
                        ['code' => 'download-pdf', 'name' => 'Download PDF'],
                    ],
                ],
                [
                    'code' => 'invoice-numbering', 'name' => 'Invoice Numbering', 'route' => '/invoicing/numbering',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Numbering'],
                    ],
                ],
                [
                    'code' => 'invoice-templates', 'name' => 'Invoice Templates', 'route' => '/invoicing/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'manage', 'name' => 'Manage Templates'],
                    ],
                ],
                [
                    'code' => 'invoice-branding', 'name' => 'Invoice Branding', 'route' => '/invoicing/branding',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Branding'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40f. Payment Methods Vault
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'payment-methods',
            'name' => 'Payment Methods',
            'description' => 'Card vault, ACH/SEPA, payment-method updates, 3DS/SCA handling, default selection',
            'icon' => 'CreditCardIcon',
            'route' => '/payment-methods',
            'priority' => 44,

            'components' => [
                [
                    'code' => 'pm-list', 'name' => 'Payment Methods', 'route' => '/payment-methods',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payment Methods'],
                        ['code' => 'add', 'name' => 'Add Payment Method'],
                        ['code' => 'update', 'name' => 'Update Payment Method'],
                        ['code' => 'remove', 'name' => 'Remove Payment Method'],
                        ['code' => 'set-default', 'name' => 'Set Default'],
                    ],
                ],
                [
                    'code' => 'card-vault', 'name' => 'Card Vault', 'route' => '/payment-methods/cards',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cards'],
                        ['code' => 'tokenize', 'name' => 'Tokenize Card'],
                    ],
                ],
                [
                    'code' => 'ach-sepa', 'name' => 'ACH / SEPA / Bank Debit', 'route' => '/payment-methods/bank-debit',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bank Debits'],
                        ['code' => 'authorize', 'name' => 'Authorize Mandate'],
                    ],
                ],
                [
                    'code' => 'sca-3ds', 'name' => '3DS / SCA Authentication', 'route' => '/payment-methods/sca',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SCA Logs'],
                        ['code' => 'configure', 'name' => 'Configure 3DS Rules'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40g. Subscription Lifecycle (Trials, Proration, Pause/Resume)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'subscription-lifecycle',
            'name' => 'Subscription Lifecycle',
            'description' => 'Trials, proration, upgrades/downgrades, pause/resume, mid-cycle changes',
            'icon' => 'ArrowPathIcon',
            'route' => '/subscription-lifecycle',
            'priority' => 45,

            'components' => [
                [
                    'code' => 'trials', 'name' => 'Trials', 'route' => '/subscription-lifecycle/trials',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Trials'],
                        ['code' => 'extend', 'name' => 'Extend Trial'],
                        ['code' => 'convert', 'name' => 'Convert to Paid'],
                    ],
                ],
                [
                    'code' => 'proration', 'name' => 'Proration', 'route' => '/subscription-lifecycle/proration',
                    'actions' => [
                        ['code' => 'preview', 'name' => 'Preview Proration'],
                        ['code' => 'configure', 'name' => 'Configure Proration Rules'],
                    ],
                ],
                [
                    'code' => 'plan-changes', 'name' => 'Plan Upgrades / Downgrades', 'route' => '/subscription-lifecycle/plan-changes',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Plan Changes'],
                        ['code' => 'execute', 'name' => 'Execute Plan Change'],
                    ],
                ],
                [
                    'code' => 'pause-resume', 'name' => 'Pause / Resume', 'route' => '/subscription-lifecycle/pause-resume',
                    'actions' => [
                        ['code' => 'pause', 'name' => 'Pause Subscription'],
                        ['code' => 'resume', 'name' => 'Resume Subscription'],
                    ],
                ],
                [
                    'code' => 'cancellations', 'name' => 'Cancellations & Save Flows', 'route' => '/subscription-lifecycle/cancellations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cancellations'],
                        ['code' => 'configure', 'name' => 'Configure Save Flow'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40h. Observability (APM, Traces, Metrics, Logs)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'observability',
            'name' => 'Observability',
            'description' => 'APM, distributed traces, metrics, logs aggregation, performance monitoring',
            'icon' => 'ChartBarIcon',
            'route' => '/observability',
            'priority' => 46,

            'components' => [
                [
                    'code' => 'apm', 'name' => 'APM Dashboard', 'route' => '/observability/apm',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View APM'],
                    ],
                ],
                [
                    'code' => 'traces', 'name' => 'Distributed Traces', 'route' => '/observability/traces',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Traces'],
                        ['code' => 'search', 'name' => 'Search Traces'],
                    ],
                ],
                [
                    'code' => 'metrics', 'name' => 'Metrics', 'route' => '/observability/metrics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Metrics'],
                        ['code' => 'export', 'name' => 'Export Metrics'],
                    ],
                ],
                [
                    'code' => 'logs-aggregation', 'name' => 'Logs Aggregation', 'route' => '/observability/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Logs'],
                        ['code' => 'search', 'name' => 'Search Logs'],
                        ['code' => 'export', 'name' => 'Export Logs'],
                    ],
                ],
                [
                    'code' => 'alerts', 'name' => 'Alerting & Anomaly', 'route' => '/observability/alerts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Alerts'],
                        ['code' => 'configure', 'name' => 'Configure Alerts'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40i. Disaster Recovery (Platform-Wide)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'disaster-recovery',
            'name' => 'Disaster Recovery',
            'description' => 'Platform-wide DR runbooks, RTO/RPO tracking, failover, DR drills',
            'icon' => 'ShieldExclamationIcon',
            'route' => '/disaster-recovery',
            'priority' => 47,

            'components' => [
                [
                    'code' => 'dr-runbooks', 'name' => 'DR Runbooks', 'route' => '/disaster-recovery/runbooks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Runbooks'],
                        ['code' => 'create', 'name' => 'Create Runbook'],
                        ['code' => 'execute', 'name' => 'Execute Runbook'],
                    ],
                ],
                [
                    'code' => 'rto-rpo', 'name' => 'RTO / RPO Tracking', 'route' => '/disaster-recovery/rto-rpo',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RTO/RPO'],
                        ['code' => 'configure', 'name' => 'Configure Targets'],
                    ],
                ],
                [
                    'code' => 'failover', 'name' => 'Failover', 'route' => '/disaster-recovery/failover',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Failover Status'],
                        ['code' => 'initiate', 'name' => 'Initiate Failover'],
                        ['code' => 'failback', 'name' => 'Failback'],
                    ],
                ],
                [
                    'code' => 'dr-drills', 'name' => 'DR Drills', 'route' => '/disaster-recovery/drills',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Drills'],
                        ['code' => 'schedule', 'name' => 'Schedule Drill'],
                        ['code' => 'execute', 'name' => 'Execute Drill'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40j. Notification Center (Landlord-Side)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'notifications',
            'name' => 'Notification Center (Landlord)',
            'description' => 'Landlord staff notification preferences, digest, escalation routing',
            'icon' => 'BellIcon',
            'route' => '/notification-center',
            'priority' => 48,

            'components' => [
                [
                    'code' => 'staff-preferences', 'name' => 'Staff Notification Preferences', 'route' => '/notification-center/preferences',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Preferences'],
                        ['code' => 'update', 'name' => 'Update Preferences'],
                    ],
                ],
                [
                    'code' => 'digest', 'name' => 'Digest Configuration', 'route' => '/notification-center/digest',
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Digest'],
                    ],
                ],
                [
                    'code' => 'escalation-routing', 'name' => 'Escalation Routing', 'route' => '/notification-center/escalation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Routing'],
                        ['code' => 'manage', 'name' => 'Manage Escalation'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40k. Enterprise SCIM Provisioning
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'enterprise-scim',
            'name' => 'Enterprise SCIM Provisioning',
            'description' => 'SCIM 2.0 endpoints for enterprise tenants directory sync (Okta, Azure AD)',
            'icon' => 'UserGroupIcon',
            'route' => '/enterprise-scim',
            'priority' => 49,

            'components' => [
                [
                    'code' => 'scim-endpoints', 'name' => 'SCIM Endpoints per Tenant', 'route' => '/enterprise-scim/endpoints',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Endpoints'],
                        ['code' => 'configure', 'name' => 'Configure Tenant SCIM'],
                        ['code' => 'rotate-token', 'name' => 'Rotate SCIM Token'],
                    ],
                ],
                [
                    'code' => 'scim-logs', 'name' => 'SCIM Sync Logs', 'route' => '/enterprise-scim/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sync Logs'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40l. Contract Management (MSAs / Order Forms / Custom Contracts)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'contract-management',
            'name' => 'Contract Management',
            'description' => 'MSAs, order forms, custom enterprise contracts, e-signature, version control',
            'icon' => 'DocumentDuplicateIcon',
            'route' => '/contracts',
            'priority' => 50,

            'components' => [
                [
                    'code' => 'msa', 'name' => 'Master Service Agreements (MSAs)', 'route' => '/contracts/msa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View MSAs'],
                        ['code' => 'create', 'name' => 'Create MSA'],
                        ['code' => 'sign', 'name' => 'Capture Signed MSA'],
                        ['code' => 'amend', 'name' => 'Amend MSA'],
                    ],
                ],
                [
                    'code' => 'order-forms', 'name' => 'Order Forms', 'route' => '/contracts/order-forms',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Order Forms'],
                        ['code' => 'create', 'name' => 'Create Order Form'],
                        ['code' => 'sign', 'name' => 'Send for Signature'],
                    ],
                ],
                [
                    'code' => 'rate-cards', 'name' => 'Custom Rate Cards', 'route' => '/contracts/rate-cards',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rate Cards'],
                        ['code' => 'manage', 'name' => 'Manage Rate Cards'],
                    ],
                ],
                [
                    'code' => 'contract-versions', 'name' => 'Contract Versions', 'route' => '/contracts/versions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Versions'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40m. App Marketplace (Third-Party Integrations)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'app-marketplace',
            'name' => 'App Marketplace',
            'description' => 'Third-party app marketplace, app submission, reviews, install/uninstall',
            'icon' => 'CubeIcon',
            'route' => '/app-marketplace',
            'priority' => 51,

            'components' => [
                [
                    'code' => 'app-catalog', 'name' => 'App Catalog', 'route' => '/app-marketplace',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Apps'],
                        ['code' => 'install', 'name' => 'Install App'],
                        ['code' => 'uninstall', 'name' => 'Uninstall App'],
                    ],
                ],
                [
                    'code' => 'developer-apps', 'name' => 'Developer App Submissions', 'route' => '/app-marketplace/developers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submissions'],
                        ['code' => 'approve', 'name' => 'Approve App'],
                        ['code' => 'reject', 'name' => 'Reject App'],
                    ],
                ],
                [
                    'code' => 'app-reviews', 'name' => 'App Reviews', 'route' => '/app-marketplace/reviews',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reviews'],
                        ['code' => 'moderate', 'name' => 'Moderate Reviews'],
                    ],
                ],
                [
                    'code' => 'app-revenue', 'name' => 'App Revenue Sharing', 'route' => '/app-marketplace/revenue',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Revenue'],
                        ['code' => 'configure', 'name' => 'Configure Revenue Share'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40n. Release Management
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'release-management',
            'name' => 'Release Management',
            'description' => 'Changelog, release notes, deployment tracking, rollback',
            'icon' => 'RocketLaunchIcon',
            'route' => '/releases',
            'priority' => 52,

            'components' => [
                [
                    'code' => 'changelog', 'name' => 'Changelog', 'route' => '/releases/changelog',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Changelog'],
                        ['code' => 'create', 'name' => 'Create Entry'],
                        ['code' => 'publish', 'name' => 'Publish Entry'],
                    ],
                ],
                [
                    'code' => 'releases', 'name' => 'Releases', 'route' => '/releases',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Releases'],
                        ['code' => 'create', 'name' => 'Create Release'],
                        ['code' => 'deploy', 'name' => 'Deploy Release'],
                        ['code' => 'rollback', 'name' => 'Rollback Release'],
                    ],
                ],
                [
                    'code' => 'deployment-tracking', 'name' => 'Deployment Tracking', 'route' => '/releases/deployments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Deployments'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 40o. Migration Imports (From Competitors / Other Platforms)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'migration-imports',
            'name' => 'Migration & Imports',
            'description' => 'Import tenants from competitor platforms (CSV, Salesforce, HubSpot, etc.)',
            'icon' => 'ArrowDownTrayIcon',
            'route' => '/migrations',
            'priority' => 53,

            'components' => [
                [
                    'code' => 'import-jobs', 'name' => 'Import Jobs', 'route' => '/migrations/imports',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Imports'],
                        ['code' => 'create', 'name' => 'Start Import'],
                        ['code' => 'monitor', 'name' => 'Monitor Import'],
                        ['code' => 'rollback', 'name' => 'Rollback Import'],
                    ],
                ],
                [
                    'code' => 'connectors', 'name' => 'Migration Connectors', 'route' => '/migrations/connectors',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Connectors'],
                        ['code' => 'configure', 'name' => 'Configure Connector'],
                    ],
                ],
                [
                    'code' => 'field-mapping', 'name' => 'Field Mapping', 'route' => '/migrations/mapping',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Mappings'],
                        ['code' => 'manage', 'name' => 'Manage Mappings'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 41. Job Scheduler
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'job-scheduler',
            'name' => 'Job Scheduler',
            'description' => 'Scheduled tasks management, cron monitoring, failed task retries',
            'icon' => 'ClockIcon',
            'route' => '/job-scheduler',
            'priority' => 41,

            'components' => [
                [
                    'code' => 'scheduled-tasks', 'name' => 'Scheduled Tasks', 'route' => '/job-scheduler/scheduled',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tasks'],
                        ['code' => 'create', 'name' => 'Create Task'],
                        ['code' => 'update', 'name' => 'Update Task'],
                        ['code' => 'pause', 'name' => 'Pause Task'],
                        ['code' => 'run-now', 'name' => 'Run Now'],
                    ],
                ],
                [
                    'code' => 'task-history', 'name' => 'Task Execution History', 'route' => '/job-scheduler/history',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View History'],
                        ['code' => 'retry', 'name' => 'Retry Failed Task'],
                    ],
                ],
                [
                    'code' => 'cron-monitoring', 'name' => 'Cron Monitoring', 'route' => '/job-scheduler/cron',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cron Status'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | License Management (Standalone / Self-Hosted)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'license-management',
            'name' => 'License Management',
            'description' => 'License key generation, validation, activation, offline/air-gapped installs',
            'icon' => 'KeyIcon',
            'route' => '/licenses',
            'priority' => 52,
            'components' => [
                [
                    'code' => 'license-keys',
                    'name' => 'License Keys',
                    'route' => '/licenses/keys',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Licenses'],
                        ['code' => 'generate', 'name' => 'Generate License Key'],
                        ['code' => 'revoke', 'name' => 'Revoke License'],
                        ['code' => 'extend', 'name' => 'Extend License'],
                    ],
                ],
                [
                    'code' => 'activations',
                    'name' => 'Activations',
                    'route' => '/licenses/activations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Activations'],
                        ['code' => 'deactivate', 'name' => 'Deactivate Install'],
                    ],
                ],
                [
                    'code' => 'license-settings',
                    'name' => 'License Settings',
                    'route' => '/licenses/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'configure', 'name' => 'Configure License Model'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Platform Access Logs (distinct from activity audit)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'access-logs',
            'name' => 'Access Logs',
            'description' => 'PII field access log, sensitive data exposure log, admin access trail',
            'icon' => 'EyeIcon',
            'route' => '/access-logs',
            'priority' => 53,
            'components' => [
                [
                    'code' => 'access-log-list',
                    'name' => 'All Access Events',
                    'route' => '/access-logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Access Logs'],
                        ['code' => 'export', 'name' => 'Export Logs'],
                    ],
                ],
                [
                    'code' => 'pii-access',
                    'name' => 'PII Field Access',
                    'route' => '/access-logs/pii',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PII Access Events'],
                        ['code' => 'export', 'name' => 'Export PII Access Report'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Product Analytics (Cohort, Funnel, Feature Usage)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'product-analytics',
            'name' => 'Product Analytics',
            'description' => 'Feature usage heatmaps, cohort retention, funnel analysis, adoption metrics',
            'icon' => 'PresentationChartLineIcon',
            'route' => '/product-analytics',
            'priority' => 54,
            'components' => [
                [
                    'code' => 'feature-usage',
                    'name' => 'Feature Usage',
                    'route' => '/product-analytics/features',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Feature Usage'],
                        ['code' => 'export', 'name' => 'Export Report'],
                    ],
                ],
                [
                    'code' => 'cohort-analysis',
                    'name' => 'Cohort Analysis',
                    'route' => '/product-analytics/cohorts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cohorts'],
                        ['code' => 'export', 'name' => 'Export Cohort Data'],
                    ],
                ],
                [
                    'code' => 'funnel-analysis',
                    'name' => 'Funnel Analysis',
                    'route' => '/product-analytics/funnels',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Funnels'],
                        ['code' => 'manage', 'name' => 'Manage Funnel Definitions'],
                    ],
                ],
                [
                    'code' => 'adoption-metrics',
                    'name' => 'Adoption Metrics',
                    'route' => '/product-analytics/adoption',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Adoption Metrics'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | P-10: White-Label
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'white-label',
            'name' => 'White-Label',
            'description' => 'Custom domains, SSL provisioning, tenant branding, CSS, and email branding',
            'icon' => 'PaintBrushIcon',
            'route' => '/white-label',
            'priority' => 30,

            'components' => [
                [
                    'code' => 'custom-domains',
                    'name' => 'Custom Domains',
                    'route' => '/white-label/domains',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custom Domains'],
                        ['code' => 'add', 'name' => 'Add Domain'],
                        ['code' => 'verify', 'name' => 'Verify Domain'],
                        ['code' => 'remove', 'name' => 'Remove Domain'],
                    ],
                ],
                [
                    'code' => 'ssl-provisioning',
                    'name' => 'SSL Provisioning',
                    'route' => '/white-label/ssl',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SSL Status'],
                        ['code' => 'provision', 'name' => 'Provision SSL'],
                        ['code' => 'renew', 'name' => 'Renew SSL'],
                    ],
                ],
                [
                    'code' => 'tenant-branding',
                    'name' => 'Tenant Branding',
                    'route' => '/white-label/branding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Branding'],
                        ['code' => 'manage', 'name' => 'Manage Branding'],
                    ],
                ],
                [
                    'code' => 'custom-css',
                    'name' => 'Custom CSS',
                    'route' => '/white-label/css',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Custom CSS'],
                        ['code' => 'edit', 'name' => 'Edit Custom CSS'],
                    ],
                ],
                [
                    'code' => 'tenant-email-branding',
                    'name' => 'Email Branding',
                    'route' => '/white-label/email-branding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Branding'],
                        ['code' => 'configure', 'name' => 'Configure Email Branding'],
                        ['code' => 'verify', 'name' => 'Verify DKIM'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | P-10: Backup & Restore
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'backup-restore',
            'name' => 'Backup & Restore',
            'description' => 'Backup schedules, manual backups, restore points, storage, and retention policies',
            'icon' => 'CircleStackIcon',
            'route' => '/backup',
            'priority' => 31,

            'components' => [
                [
                    'code' => 'backup-dashboard',
                    'name' => 'Backup Dashboard',
                    'route' => '/backup',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Backup Dashboard'],
                    ],
                ],
                [
                    'code' => 'backup-schedules',
                    'name' => 'Backup Schedules',
                    'route' => '/backup/schedules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Schedules'],
                        ['code' => 'create', 'name' => 'Create Schedule'],
                        ['code' => 'update', 'name' => 'Update Schedule'],
                        ['code' => 'delete', 'name' => 'Delete Schedule'],
                    ],
                ],
                [
                    'code' => 'manual-backups',
                    'name' => 'Manual Backups',
                    'route' => '/backup/manual',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Manual Backups'],
                        ['code' => 'create', 'name' => 'Trigger Manual Backup'],
                    ],
                ],
                [
                    'code' => 'restore',
                    'name' => 'Restore',
                    'route' => '/backup/restore',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Restore Points'],
                        ['code' => 'restore', 'name' => 'Restore Backup'],
                        ['code' => 'pitr', 'name' => 'Point-In-Time Restore'],
                    ],
                ],
                [
                    'code' => 'backup-storage',
                    'name' => 'Backup Storage',
                    'route' => '/backup/storage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Storage Config'],
                        ['code' => 'configure', 'name' => 'Configure Storage'],
                    ],
                ],
                [
                    'code' => 'retention-policies',
                    'name' => 'Retention Policies',
                    'route' => '/backup/retention',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Retention Policies'],
                        ['code' => 'manage', 'name' => 'Manage Retention Policies'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | P-10: Status & Incidents
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'status-incidents',
            'name' => 'Status & Incidents',
            'description' => 'Status page, service components, incidents, postmortems, SLA, uptime monitoring',
            'icon' => 'SignalIcon',
            'route' => '/status',
            'priority' => 32,

            'components' => [
                [
                    'code' => 'status-page',
                    'name' => 'Status Page',
                    'route' => '/status',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Status Page'],
                        ['code' => 'configure', 'name' => 'Configure Status Page'],
                    ],
                ],
                [
                    'code' => 'service-components',
                    'name' => 'Service Components',
                    'route' => '/status/components',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Components'],
                        ['code' => 'manage', 'name' => 'Manage Components'],
                        ['code' => 'set-status', 'name' => 'Set Component Status'],
                    ],
                ],
                [
                    'code' => 'incidents',
                    'name' => 'Incidents',
                    'route' => '/status/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Incidents'],
                        ['code' => 'create', 'name' => 'Create Incident'],
                        ['code' => 'update', 'name' => 'Update Incident'],
                        ['code' => 'resolve', 'name' => 'Resolve Incident'],
                    ],
                ],
                [
                    'code' => 'postmortems',
                    'name' => 'Postmortems',
                    'route' => '/status/postmortems',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Postmortems'],
                        ['code' => 'create', 'name' => 'Create Postmortem'],
                        ['code' => 'publish', 'name' => 'Publish Postmortem'],
                    ],
                ],
                [
                    'code' => 'sla-reporting',
                    'name' => 'SLA Reporting',
                    'route' => '/status/sla',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SLA Report'],
                        ['code' => 'export', 'name' => 'Export SLA Report'],
                    ],
                ],
                [
                    'code' => 'uptime-monitoring',
                    'name' => 'Uptime Monitoring',
                    'route' => '/status/uptime',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Uptime'],
                        ['code' => 'configure', 'name' => 'Configure Uptime Monitoring'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | P-10: Platform Security
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'platform-security',
            'name' => 'Platform Security',
            'description' => 'Landlord roles, impersonation audit, staff MFA/SSO, IP allowlist',
            'icon' => 'LockClosedIcon',
            'route' => '/security',
            'priority' => 33,

            'components' => [
                [
                    'code' => 'landlord-roles',
                    'name' => 'Landlord Roles',
                    'route' => '/security/roles',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Roles'],
                        ['code' => 'create', 'name' => 'Create Role'],
                        ['code' => 'update', 'name' => 'Update Role'],
                        ['code' => 'delete', 'name' => 'Delete Role'],
                        ['code' => 'assign', 'name' => 'Assign Role'],
                    ],
                ],
                [
                    'code' => 'impersonation',
                    'name' => 'Impersonation',
                    'route' => '/security/impersonation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Impersonation Log'],
                        ['code' => 'start', 'name' => 'Start Impersonation'],
                        ['code' => 'end', 'name' => 'End Impersonation'],
                        ['code' => 'audit', 'name' => 'Audit Impersonation'],
                    ],
                ],
                [
                    'code' => 'staff-sessions',
                    'name' => 'Staff Sessions',
                    'route' => '/security/sessions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sessions'],
                        ['code' => 'force-logout', 'name' => 'Force Logout'],
                    ],
                ],
                [
                    'code' => 'staff-mfa',
                    'name' => 'Staff MFA',
                    'route' => '/security/mfa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View MFA Status'],
                        ['code' => 'enforce', 'name' => 'Enforce MFA'],
                        ['code' => 'reset', 'name' => 'Reset MFA'],
                    ],
                ],
                [
                    'code' => 'staff-sso',
                    'name' => 'Staff SSO',
                    'route' => '/security/sso',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SSO Config'],
                        ['code' => 'configure', 'name' => 'Configure SSO'],
                    ],
                ],
                [
                    'code' => 'ip-allowlist',
                    'name' => 'IP Allowlist',
                    'route' => '/security/ip-allowlist',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View IP Allowlist'],
                        ['code' => 'manage', 'name' => 'Manage IP Allowlist'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | P-10: Security Center
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'security-center',
            'name' => 'Security Center',
            'description' => 'Security dashboard, incidents, pentest reports, bug bounty',
            'icon' => 'ShieldExclamationIcon',
            'route' => '/security-center',
            'priority' => 34,

            'components' => [
                [
                    'code' => 'security-dashboard',
                    'name' => 'Security Dashboard',
                    'route' => '/security-center',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Security Dashboard'],
                    ],
                ],
                [
                    'code' => 'security-incidents',
                    'name' => 'Security Incidents',
                    'route' => '/security-center/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Security Incidents'],
                        ['code' => 'create', 'name' => 'Create Security Incident'],
                        ['code' => 'notify', 'name' => 'Notify Tenants'],
                    ],
                ],
                [
                    'code' => 'pentest-reports',
                    'name' => 'Pentest Reports',
                    'route' => '/security-center/pentests',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pentest Reports'],
                        ['code' => 'upload', 'name' => 'Upload Report'],
                        ['code' => 'share', 'name' => 'Share with Tenants'],
                    ],
                ],
            ],
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'platform-super-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['platform-module-access', 'platform-role-access'],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map (Platform-Level)
    |--------------------------------------------------------------------------
    | Platform hosts tenants and module catalog. For EAM: advertises which
    | tenants have EAM enabled and exposes plan-based feature gating.
    */
    'eam_integration' => [
        'provides' => [
            'platform.tenants' => 'tenant_management.tenant_list',
            'platform.plans' => 'plan_management.plan_list',
            'platform.module_catalog' => 'module_management.module_list',
            'platform.quota_enforcement' => 'quota_management.quota_dashboard',
            'platform.billing' => 'billing_management.billing_dashboard',
            'platform.api_keys' => 'integrations.api_keys',
            'platform.webhooks' => 'integrations.webhooks',
        ],
        'consumes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Lifecycle States
    |--------------------------------------------------------------------------
    */
    'tenant_states' => [
        'pending' => [
            'label' => 'Pending',
            'color' => 'warning',
            'transitions' => ['provisioning', 'cancelled'],
        ],
        'provisioning' => [
            'label' => 'Provisioning',
            'color' => 'primary',
            'transitions' => ['active', 'failed'],
        ],
        'active' => [
            'label' => 'Active',
            'color' => 'success',
            'transitions' => ['suspended', 'cancelled'],
        ],
        'suspended' => [
            'label' => 'Suspended',
            'color' => 'danger',
            'transitions' => ['active', 'cancelled'],
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => 'default',
            'transitions' => ['archived'],
        ],
        'failed' => [
            'label' => 'Failed',
            'color' => 'danger',
            'transitions' => ['provisioning', 'cancelled'],
        ],
        'archived' => [
            'label' => 'Archived',
            'color' => 'default',
            'transitions' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'trial_days' => env('PLATFORM_TRIAL_DAYS', 14),
        'trial_enabled' => env('PLATFORM_TRIAL_ENABLED', true),
        'grace_period_days' => env('PLATFORM_GRACE_PERIOD', 5),
        'currency' => env('PLATFORM_CURRENCY', 'USD'),
        'tax_enabled' => true,
        'tax_type' => 'region', // simple, region, external
        'payment_gateways' => [
            'stripe' => [
                'enabled' => env('STRIPE_ENABLED', false),
                'mode' => env('STRIPE_MODE', 'test'),
            ],
            'sslcommerz' => [
                'enabled' => env('SSLCOMMERZ_ENABLED', false),
                'mode' => env('SSLCOMMERZ_MODE', 'sandbox'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Onboarding Configuration
    |--------------------------------------------------------------------------
    */
    'onboarding' => [
        'require_email_verification' => true,
        'require_phone_verification' => false,
        'require_admin_approval' => false,
        'auto_provision' => true,
        'steps' => [
            'account_type',
            'details',
            'admin',
            'verify_email',
            'plan',
            'payment',
            'provisioning',
        ],
    ],
];
