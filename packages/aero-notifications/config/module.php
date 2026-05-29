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

    /*
    |--------------------------------------------------------------------------
    | Submodules (Plan 08 Task 1)
    |--------------------------------------------------------------------------
    |
    | The package's substantial implementation surface (Email/SMS/Push/InApp/
    | Suppression/Bounce/Deliverability/Templates/Preferences) was previously
    | UNDECLARED — Phase 1 audit found this is what made HRMAC permission
    | enforcement, sidebar wiring, and module sync silently no-op for the
    | notification admin pages.
    |
    | ALSO resolves aero-core Task 12 — the email_engine submodule that was
    | declared in aero-core/config/module.php (lines 1323-1373 historically)
    | belongs HERE next to the controllers/jobs/services that implement it.
    | Operators upgrading from pre-Plan-08 must re-run `php artisan modules:sync`
    | and re-grant any role permissions that previously used core.email_engine.*
    | → notifications.email_engine.* paths.
    */
    'submodules' => [
        [
            'code' => 'email_engine',
            'name' => 'Email Engine',
            'icon' => 'EnvelopeIcon',
            'description' => 'Templates, logs, suppression list, deliverability, bounce handling.',
            'route' => '/notifications/email',
            'components' => [
                [
                    'code' => 'templates', 'name' => 'Email Templates', 'type' => 'page', 'route' => '/notifications/email/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                        ['code' => 'duplicate', 'name' => 'Duplicate Template'],
                    ],
                ],
                [
                    'code' => 'logs', 'name' => 'Email Logs', 'type' => 'page', 'route' => '/notifications/email/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Logs'],
                        ['code' => 'resend', 'name' => 'Resend Email'],
                    ],
                ],
                [
                    'code' => 'suppression_list', 'name' => 'Suppression List', 'type' => 'page', 'route' => '/notifications/email/suppression',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Suppression'],
                        ['code' => 'add', 'name' => 'Add Address'],
                        ['code' => 'remove', 'name' => 'Remove Address'],
                        ['code' => 'export', 'name' => 'Export Suppression List'],
                    ],
                ],
                [
                    'code' => 'deliverability', 'name' => 'Deliverability', 'type' => 'page', 'route' => '/notifications/email/deliverability',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Deliverability Score'],
                        ['code' => 'test_smtp', 'name' => 'Test SMTP'],
                    ],
                ],
                [
                    'code' => 'bounces', 'name' => 'Bounces & Complaints', 'type' => 'page', 'route' => '/notifications/email/bounces',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bounces'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'sms_engine',
            'name' => 'SMS Engine',
            'icon' => 'ChatBubbleLeftIcon',
            'description' => 'SMS gateways, templates, send logs.',
            'route' => '/notifications/sms',
            'components' => [
                [
                    'code' => 'gateways', 'name' => 'SMS Gateways', 'type' => 'page', 'route' => '/notifications/sms/gateways',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Gateways'],
                        ['code' => 'configure', 'name' => 'Configure Gateway'],
                        ['code' => 'test', 'name' => 'Test Gateway'],
                    ],
                ],
                [
                    'code' => 'logs', 'name' => 'SMS Logs', 'type' => 'page', 'route' => '/notifications/sms/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SMS Logs'],
                        ['code' => 'resend', 'name' => 'Resend SMS'],
                    ],
                ],
                [
                    'code' => 'templates', 'name' => 'SMS Templates', 'type' => 'page', 'route' => '/notifications/sms/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'push_engine',
            'name' => 'Push Notification Engine',
            'icon' => 'BellAlertIcon',
            'description' => 'FCM config, push topics, device-token management.',
            'route' => '/notifications/push',
            'components' => [
                [
                    'code' => 'fcm_config', 'name' => 'FCM Configuration', 'type' => 'page', 'route' => '/notifications/push/fcm',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View FCM Config'],
                        ['code' => 'configure', 'name' => 'Configure FCM'],
                        ['code' => 'test', 'name' => 'Send Test Push'],
                    ],
                ],
                [
                    'code' => 'topics', 'name' => 'Push Topics', 'type' => 'page', 'route' => '/notifications/push/topics',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Topics'],
                        ['code' => 'create', 'name' => 'Create Topic'],
                        ['code' => 'subscribe', 'name' => 'Subscribe Users'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'in_app',
            'name' => 'In-App Notifications',
            'icon' => 'InboxIcon',
            'description' => 'User-facing notification inbox.',
            'route' => '/notifications/inbox',
            'components' => [
                [
                    'code' => 'inbox', 'name' => 'Inbox', 'type' => 'page', 'route' => '/notifications/inbox',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inbox'],
                        ['code' => 'mark_read', 'name' => 'Mark Read'],
                        ['code' => 'delete', 'name' => 'Delete Notification'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'preferences',
            'name' => 'User Notification Preferences',
            'icon' => 'Cog6ToothIcon',
            'description' => 'Per-user channel and digest preferences.',
            'route' => '/profile/notifications',
            'components' => [
                [
                    'code' => 'channels', 'name' => 'Channel Preferences', 'type' => 'page', 'route' => '/profile/notifications',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Preferences'],
                        ['code' => 'update', 'name' => 'Update Preferences'],
                    ],
                ],
                [
                    'code' => 'digest', 'name' => 'Email Digest', 'type' => 'page', 'route' => '/profile/notifications/digest',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Digest Settings'],
                        ['code' => 'update', 'name' => 'Update Digest Settings'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'settings',
            'name' => 'Global Notification Settings',
            'icon' => 'Cog8ToothIcon',
            'description' => 'Tenant-level notification settings.',
            'route' => '/admin/notifications/settings',
            'components' => [
                [
                    'code' => 'global', 'name' => 'Global Settings', 'type' => 'page', 'route' => '/admin/notifications/settings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'update', 'name' => 'Update Settings'],
                    ],
                ],
            ],
        ],
    ],
];
