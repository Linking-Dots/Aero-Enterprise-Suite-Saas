<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Module Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-cutting workflow engine for approval workflows, business rules,
    | automation triggers, SLA monitoring, and process orchestration.
    |
    | Tenancy concern:
    *   - All workflow definitions are tenant-scoped
    *   - Workflow instances are tenant-scoped
    *   - Cross-module workflow triggers (e.g., aero-compliance → aero-finance)
    *   - No cross-tenant workflow data access
    */

    'code'         => 'workflow',
    'scope'        => 'tenant',
    'name'         => 'Workflow Engine',
    'description'  => 'Cross-cutting workflow engine: approval workflows, business rules, automation triggers, SLA monitoring, and process orchestration for all modules.',
    'icon'         => 'ArrowsRightLeftIcon',
    'route_prefix' => '/workflow',
    'category'     => 'infrastructure',
    'priority'     => 5,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('WORKFLOW_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'workflow_designer'       => true,
        'approval_workflows'      => true,
        'business_rules'          => true,
        'automation_triggers'     => true,
        'sla_monitoring'         => true,
        'process_orchestration'   => true,
        'workflow_templates'      => true,
        'conditional_logic'       => true,
        'parallel_branches'       => true,
        'workflow_history'        => true,
        'workflow_analytics'      => true,
        'cross_module_hooks'      => true,
        'webhook_triggers'        => true,
        'scheduled_workflows'     => true,
        'workflow_permissions'    => true,
        'settings'                => true,
    ],

    'submodules' => [
        // Workflow designer, templates, instances, monitoring
    ],
];
