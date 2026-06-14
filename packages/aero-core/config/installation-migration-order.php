<?php

/**
 * Installation Migration Order Configuration
 *
 * Defines the explicit order in which migration tags should execute during installation.
 * This ensures critical dependencies are met before dependent migrations run.
 *
 * Format: tag => [dependencies]
 * Dependencies are tags that must complete before this tag runs.
 */

return [
    /**
     * SaaS Mode Installation Order (8 steps)
     * Multi-tenant cloud deployment
     */
    'saas' => [
        'infrastructure:shared' => [
            'step' => 0,
            'description' => 'Shared infrastructure tables (installation_history, installation_progress, module_pricing, system_health_logs)',
            'dependencies' => [],
        ],
        'core:foundation' => [
            'step' => 1,
            'description' => 'Core app setup: keys, encryption',
            'dependencies' => [],
        ],
        'core:auth' => [
            'step' => 2,
            'description' => 'Authentication: users, roles, permissions',
            'dependencies' => ['core:foundation'],
        ],
        'platform:tenancy' => [
            'step' => 3,
            'description' => 'Multi-tenancy: tenants, domains, database mapping',
            'dependencies' => ['core:auth'],
        ],
        'platform:billing' => [
            'step' => 4,
            'description' => 'Billing: plans, subscriptions, Stripe integration',
            'dependencies' => ['platform:tenancy'],
        ],
        'platform:modules' => [
            'step' => 5,
            'description' => 'Module system: modules, permissions, role access',
            'dependencies' => ['core:auth', 'platform:tenancy'],
        ],
        'platform:settings' => [
            'step' => 6,
            'description' => 'Platform settings: configuration, email, SMS',
            'dependencies' => ['platform:modules'],
        ],
        'platform:cache' => [
            'step' => 7,
            'description' => 'Cache warming: compile routes, config, views',
            'dependencies' => ['platform:settings'],
        ],
        'platform:finalize' => [
            'step' => 8,
            'description' => 'Installation finalization: mark complete',
            'dependencies' => ['platform:cache'],
        ],
    ],

    /**
     * Standalone Mode Installation Order (9 steps)
     * Single-tenant on-premise deployment
     */
    'standalone' => [
        'infrastructure:shared' => [
            'step' => 0,
            'description' => 'Shared infrastructure tables (installation_history, installation_progress, module_pricing, system_health_logs)',
            'dependencies' => [],
        ],
        'core:foundation' => [
            'step' => 1,
            'description' => 'Core app setup: keys, encryption',
            'dependencies' => [],
        ],
        'core:license' => [
            'step' => 2,
            'description' => 'License validation (standalone only)',
            'dependencies' => ['core:foundation'],
        ],
        'core:auth' => [
            'step' => 3,
            'description' => 'Authentication: users, roles, permissions',
            'dependencies' => ['core:foundation'],
        ],
        'core:rbac' => [
            'step' => 4,
            'description' => 'Role-based access control',
            'dependencies' => ['core:auth'],
        ],
        'platform:modules' => [
            'step' => 5,
            'description' => 'Module system: modules, permissions, role access',
            'dependencies' => ['core:rbac'],
        ],
        'hrm:base' => [
            'step' => 6,
            'description' => 'HRM base setup (if enabled): departments, employees, designations',
            'dependencies' => ['platform:modules'],
            'optional' => true,
            'condition' => 'env("INSTALL_HRM", false)',
        ],
        'platform:settings' => [
            'step' => 7,
            'description' => 'Platform settings: configuration, email, SMS',
            'dependencies' => ['platform:modules'],
        ],
        'platform:cache' => [
            'step' => 8,
            'description' => 'Cache warming: compile routes, config, views',
            'dependencies' => ['platform:settings'],
        ],
        'platform:finalize' => [
            'step' => 9,
            'description' => 'Installation finalization: mark complete',
            'dependencies' => ['platform:cache'],
        ],
    ],

    /**
     * Migration Tag Taxonomy
     * Maps migration tags to their meanings and expected packages
     */
    'tag_taxonomy' => [
        'infrastructure:shared' => 'Shared infrastructure tables (installation_history, installation_progress, module_pricing, system_health_logs)',
        'core:foundation' => 'Core framework and security setup',
        'core:auth' => 'User authentication and sessions',
        'core:license' => 'License validation',
        'core:rbac' => 'Role-based access control',
        'core:audit' => 'Audit logging',
        'core:notifications' => 'Notification system',

        'platform:tenancy' => 'Multi-tenancy core (stancl/tenancy)',
        'platform:billing' => 'Billing and subscription management',
        'platform:modules' => 'Module registry and discovery',
        'platform:settings' => 'Platform and system settings',
        'platform:cache' => 'Cache compilation',
        'platform:finalize' => 'Installation completion',

        'hrm:base' => 'HRM foundation: departments, employees, designations',
        'hrm:payroll' => 'Payroll: salary components, structures',
        'hrm:leave' => 'Leave management system',
        'hrm:onboarding' => 'Employee onboarding',
        'hrm:offboarding' => 'Employee offboarding',

        'crm:base' => 'CRM foundation: leads, accounts, opportunities',
        'crm:campaigns' => 'Campaign management',
        'crm:pipeline' => 'Sales pipeline',

        'project:base' => 'Project management foundation',
        'project:tasks' => 'Task management',
        'project:resources' => 'Resource allocation',

        'cms:base' => 'CMS foundation: pages, blocks',
        'cms:publishing' => 'Publishing workflow',
        'cms:seo' => 'SEO metadata and keywords',

        'finance:base' => 'Finance foundation: accounts, ledger',
        'finance:invoicing' => 'Invoice management',
        'finance:reporting' => 'Financial reporting',

        'ims:base' => 'Inventory foundation: items, warehouses',
        'ims:transactions' => 'Inventory transactions',

        'quality:base' => 'Quality management foundation',
        'scm:base' => 'Supply chain foundation',
        'dms:base' => 'Document management foundation',
        'compliance:base' => 'Compliance foundation',
    ],

    /**
     * Critical Migrations (installation must succeed)
     * If any of these fail, entire installation is rolled back
     */
    'critical_tags' => [
        'core:foundation',
        'core:auth',
        'platform:tenancy',
        'platform:modules',
    ],

    /**
     * Timeout Configuration (seconds)
     */
    'timeouts' => [
        'per_step' => 300,              // 5 minutes per step
        'per_migration' => 60,          // 1 minute per migration
        'per_seeder' => 120,            // 2 minutes per seeder
        'total_installation' => 1800,   // 30 minutes total
    ],

    /**
     * Retry Configuration
     */
    'retry' => [
        'max_attempts' => 3,
        'retriable_steps' => ['platform:cache', 'platform:finalize'],
        'non_retriable_steps' => ['core:foundation', 'core:auth', 'platform:tenancy'],
    ],
];
