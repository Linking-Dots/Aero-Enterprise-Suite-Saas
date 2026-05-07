<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notifications Package — Notification Infrastructure
    |--------------------------------------------------------------------------
    | Scope: BOTH platform + tenant (infrastructure layer, not a UI module)
    |
    | aero-notifications is NOT a user-facing module. It is the shared notification
    | infrastructure used by all modules for email, SMS, push, in-app, and webhook
    | notifications. It does not appear in the module marketplace.
    |
    | Tenancy concern:
    |   - Email templates are tenant-scoped (custom branding)
    *   - Notification preferences are tenant-scoped
    *   - Queue workers process notifications in tenant context
    *   - Platform notifications (landlord) use central DB
    *   - Tenant notifications use tenant DB
    */

    'code' => 'notifications',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',
    'name' => 'Notification Infrastructure',
    'description' => 'Shared notification layer: email, SMS, push, in-app, webhooks, templates, preferences, and queue processing for both tenant and platform contexts.',
    'icon' => 'BellIcon',
    'route_prefix' => null,
    'category' => 'infrastructure',
    'priority' => 0,
    'is_core' => true,
    'is_active' => true,
    'enabled' => true,
    'version' => '1.0.0',
    'min_plan' => null,
    'license_type' => 'platform',
    'dependencies' => [],
    'release_date' => '2024-01-01',
    'marketplace_visible' => false,

    'features' => [
        'email_notifications' => true,
        'sms_notifications' => true,
        'push_notifications' => true,
        'in_app_notifications' => true,
        'webhook_notifications' => true,
        'email_templates' => true,
        'notification_preferences' => true,
        'queue_processing' => true,
        'digest_notifications' => true,
        'notification_logs' => true,
        'suppression_list' => true,
        'bounce_handling' => true,
    ],

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,
        'central_tables' => [
            'platform_notification_templates',
            'platform_notification_logs',
        ],
        'tenant_tables' => [
            'notification_templates',
            'notification_preferences',
            'notification_logs',
        ],
    ],
];
