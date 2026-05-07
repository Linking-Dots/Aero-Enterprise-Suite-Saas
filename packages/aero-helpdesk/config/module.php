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

    'features' => [
        'ticket_management' => true,
        'sla_monitoring' => true,
        'knowledge_base' => true,
        'agent_assignment' => true,
        'customer_portal' => true,
        'ticket_categories' => true,
        'ticket_priorities' => true,
        'email_integration' => true,
        'chat_support' => true,
        'ticket_analytics' => true,
        'customer_satisfaction' => true,
        'escalation_rules' => true,
        'macros_templates' => true,
        'settings' => true,
    ],

    'submodules' => [
        // Tickets, knowledge base, analytics, settings
    ],
];
