<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Helpdesk Module — IT Support
    |--------------------------------------------------------------------------
    |
    | IT helpdesk and support with ticket management, SLA, knowledge base,
    * agent assignment, and customer portal.
    *
    | Tenancy concern:
    *   - All tickets are tenant-scoped
    *   - Knowledge base is tenant-scoped
    *   - Customer portal is tenant-scoped
    *   - No cross-tenant ticket data access
    */

    'code' => 'helpdesk',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'IT Helpdesk & Support',
    'description' => 'IT helpdesk and support: ticket management, SLA, knowledge base, agent assignment, customer portal, and integrations.',
    'icon' => 'LifebuoyIcon',
    'route_prefix' => '/helpdesk',
    'category' => 'business',
    'priority' => 15,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('HELPDESK_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [
        // Tickets, knowledge base, analytics, settings
    ],
];
