<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automation Module — Business Process Automation
    |--------------------------------------------------------------------------
    |
    | Cross-cutting business process automation with scheduled tasks, RPA,
    * event triggers, webhooks, and API automation.
    *
    | Tenancy concern:
    *   - All automation rules are tenant-scoped
    *   - Automation execution logs are tenant-scoped
    *   - Cross-module automation triggers (e.g., aero-commerce → aero-finance)
    *   - No cross-tenant automation data access
    */

    'code' => 'automation',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Business Process Automation',
    'description' => 'Cross-cutting automation: scheduled tasks, RPA, event triggers, webhooks, API automation, and process orchestration.',
    'icon' => 'Cog6ToothIcon',
    'route_prefix' => '/automation',
    'category' => 'infrastructure',
    'priority' => 8,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('AUTOMATION_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core', 'workflow'],
    'release_date' => '2024-01-01',

    'features' => [
        'scheduled_tasks' => true,
        'rpa' => true,
        'event_triggers' => true,
        'webhooks' => true,
        'api_automation' => true,
        'cron_jobs' => true,
        'workflow_automation' => true,
        'retry_logic' => true,
        'error_handling' => true,
        'automation_logs' => true,
        'automation_analytics' => true,
        'bulk_operations' => true,
        'conditional_automation' => true,
        'settings' => true,
    ],

    'submodules' => [
        // Scheduled tasks, triggers, webhooks, logs
    ],
];
