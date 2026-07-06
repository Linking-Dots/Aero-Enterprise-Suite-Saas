<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module Configuration (Merged & Expanded)
    |--------------------------------------------------------------------------
    */

    'code' => 'core',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Core Framework',
    'description' => 'Foundation framework including Dashboard, Users, Roles, Permissions, Authentication, Audit Logs, Notifications, File Manager, and Settings',
    'icon' => 'CubeIcon',
    'route_prefix' => '/tenant',
    'category' => 'core',
    'priority' => 1,
    'is_core' => true,
    'is_active' => true,
    'version' => '1.1.0',
    'min_plan' => null,
    'license_type' => 'standard',
    'dependencies' => [],
    'release_date' => '2024-01-01',
    'enabled' => true,
    'minimum_plan' => null,

    /*
    |--------------------------------------------------------------------------
    | Self-Service Navigation Items
    |--------------------------------------------------------------------------
    |
    | Core self-service items available to all authenticated users.
    |
    */
    'self_service' => [
        [
            'code' => 'my-profile',
            'name' => 'My Profile',
            'icon' => 'UserCircleIcon',
            'route' => '/profile',
            'priority' => 0, // First item
        ],
        [
            'code' => 'my-notifications',
            'name' => 'My Notifications',
            'icon' => 'BellIcon',
            'route' => '/notifications',
            'priority' => 1,
        ],
    ],

    'submodules' => [
        /*
        |--------------------------------------------------------------------------
        | 1.0 Self Service (Core My Workspace items)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'self_service',
            'name' => 'Self Service',
            'description' => 'Employee self-service features (My Workspace items)',
            'icon' => 'UserCircleIcon',
            'route' => '/profile',
            'priority' => 0,
            'show_in_nav' => false, // Handled by NavigationRegistry::getSelfServiceNavigation() → My Workspace

            'components' => [
                [
                    'code' => 'my-profile',
                    'name' => 'My Profile',
                    'type' => 'page',
                    'route' => '/profile',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Profile'],
                        ['code' => 'edit', 'name' => 'Edit Profile'],
                    ],
                ],
                [
                    'code' => 'my-notifications',
                    'name' => 'My Notifications',
                    'type' => 'page',
                    'route' => '/notifications',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Notifications'],
                        ['code' => 'mark_read', 'name' => 'Mark as Read'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.1 Dashboards (3 dashboards matching navigation)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'dashboard',
            'name' => 'Dashboards',
            'description' => 'System dashboards and overviews',
            'icon' => 'HomeIcon',
            'route' => '/dashboard',
            'priority' => 1,

            'components' => [
                [
                    'code' => 'admin-dashboard',
                    'name' => 'Admin Dashboard',
                    'type' => 'page',
                    'route' => '/dashboard',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Admin Dashboard'],
                    ],
                ],
                [
                    'code' => 'announcements',
                    'name' => 'Announcements',
                    'type' => 'feature',
                    'route' => '/dashboard',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Announcements'],
                        ['code' => 'create', 'name' => 'Create Announcement'],
                        ['code' => 'delete', 'name' => 'Delete Announcement'],
                    ],
                ],

            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.2 Subscription & Billing (SaaS mode only - requires aero-platform)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'subscription',
            'name' => 'Subscription & Billing',
            'description' => 'Tenant self-service subscription management (SaaS mode only)',
            'icon' => 'CreditCardIcon',
            'route' => '/subscription',
            'priority' => 2,
            'show_in_nav' => true, // Real tenant self-service page; no other nav home
            'collapse_nav' => true, // Single hub link; Plans/Usage/Invoices are in-page tabs
            'plan' => 'saas',

            'components' => [
                [
                    'code' => 'plans',
                    'name' => 'Subscription Plans',
                    'type' => 'page',
                    'route' => '/subscription/plans',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Plans'],
                        ['code' => 'upgrade', 'name' => 'Upgrade Plan'],
                        ['code' => 'downgrade', 'name' => 'Downgrade Plan'],
                        ['code' => 'cancel', 'name' => 'Cancel Subscription'],
                    ],
                ],
                [
                    'code' => 'usage',
                    'name' => 'Usage & Quotas',
                    'type' => 'page',
                    'route' => '/subscription/usage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Usage'],
                    ],
                ],
                [
                    'code' => 'invoices',
                    'name' => 'Invoices & Billing History',
                    'type' => 'page',
                    'route' => '/subscription/invoices',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Invoices'],
                        ['code' => 'download', 'name' => 'Download Invoice'],
                    ],
                ],
                [
                    'code' => 'products',
                    'name' => 'Add-on Products',
                    'type' => 'page',
                    'route' => '/subscription/products',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Add-ons'],
                        ['code' => 'subscribe', 'name' => 'Subscribe to Add-on'],
                        ['code' => 'cancel', 'name' => 'Cancel Add-on'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.2 User Management
        |--------------------------------------------------------------------------
        */
        [
            // User + invitation ADMINISTRATION moved to the shared aero-auth module
            // (auth.user_management.*) — see the consolidation plan. Only self-service
            // "My Profile" remains here (core-owned, not the admin surface).
            'code' => 'user_management',
            'name' => 'My Profile',
            'description' => 'Self-service profile for the signed-in user',
            'icon' => 'UserGroupIcon',
            'route' => '/profile',
            'priority' => 3,

            'components' => [
                [
                    'code' => 'user_profile',
                    'name' => 'User Profile',
                    'type' => 'page',
                    'route' => '/profile',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Profile'],
                        ['code' => 'edit', 'name' => 'Edit Profile'],
                        ['code' => 'change_password', 'name' => 'Change Password'],
                        ['code' => 'upload_avatar', 'name' => 'Upload Avatar'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.3 Authentication
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'authentication',
            'name' => 'Authentication',
            'description' => 'Authentication and security settings',
            'icon' => 'KeyIcon',
            'route' => '/security', // Filled route
            'priority' => 4,

            'components' => [
                [
                    'code' => 'devices',
                    'name' => 'Device Management',
                    'type' => 'page',
                    'route' => '/security/devices', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Devices'],
                        ['code' => 'toggle', 'name' => 'Toggle Device Trust'],
                        ['code' => 'reset', 'name' => 'Reset Device'],
                        ['code' => 'deactivate', 'name' => 'Deactivate Device'],
                    ],
                ],
                [
                    'code' => 'two_factor',
                    'name' => 'Two-Factor Authentication',
                    'type' => 'feature',
                    'route' => '/security/2fa', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View 2FA Settings'],
                        ['code' => 'enable', 'name' => 'Enable 2FA'],
                        ['code' => 'disable', 'name' => 'Disable 2FA'],
                        ['code' => 'reset', 'name' => 'Reset 2FA'],
                        ['code' => 'enroll_totp', 'name' => 'Enroll TOTP Authenticator'],
                        ['code' => 'enroll_sms', 'name' => 'Enroll SMS 2FA'],
                        ['code' => 'enroll_email', 'name' => 'Enroll Email 2FA'],
                        ['code' => 'generate_recovery_codes', 'name' => 'Generate Recovery Codes'],
                        ['code' => 'verify_recovery_code', 'name' => 'Verify Recovery Code'],
                    ],
                ],
                [
                    'code' => 'password_reset',
                    'name' => 'Password Reset / Forgot Password',
                    'type' => 'page',
                    'route' => '/security/password-reset',
                    'actions' => [
                        ['code' => 'request', 'name' => 'Request Password Reset'],
                        ['code' => 'verify_token', 'name' => 'Verify Reset Token'],
                        ['code' => 'reset', 'name' => 'Reset Password'],
                    ],
                ],
                [
                    'code' => 'email_verification',
                    'name' => 'Email Verification',
                    'type' => 'page',
                    'route' => '/security/verify-email',
                    'actions' => [
                        ['code' => 'send', 'name' => 'Send Verification Email'],
                        ['code' => 'verify', 'name' => 'Verify Email'],
                        ['code' => 'resend', 'name' => 'Resend Verification'],
                    ],
                ],
                [
                    'code' => 'sessions',
                    'name' => 'Session Management',
                    'type' => 'page',
                    'route' => '/security/sessions', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sessions'],
                        ['code' => 'terminate', 'name' => 'Terminate Session'],
                        ['code' => 'terminate_all', 'name' => 'Terminate All Sessions'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.4 Roles & Permissions — MOVED to the shared aero-hrmac module
        |     (hrmac.roles_permissions.{roles,module_access}.*). Declared once there
        |     so tenant + platform share one access-control source of truth.
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | 1.5 Audit Logs
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'audit_logs',
            'name' => 'Audit & Activity Logs',
            'description' => 'View system activity, user actions, and security events',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/audit-logs',
            'priority' => 6,
            'collapse_nav' => true,

            'components' => [
                [
                    'code' => 'activity_logs',
                    'name' => 'Activity Logs',
                    'type' => 'page',
                    'route' => '/audit-logs/activity',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Activity Logs'],
                        ['code' => 'export', 'name' => 'Export Activity Logs'],
                        ['code' => 'filter', 'name' => 'Advanced Filtering'],
                    ],
                ],
                [
                    'code' => 'security_logs',
                    'name' => 'Security Logs',
                    'type' => 'page',
                    'route' => '/audit-logs/security',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Security Logs'],
                        ['code' => 'export', 'name' => 'Export Security Logs'],
                        ['code' => 'investigate', 'name' => 'Investigate Event'],
                    ],
                ],
                /* * NEW: Added Queue Monitor for System Health
                 */
                [
                    'code' => 'queue_monitor',
                    'name' => 'Queue/Job Monitor',
                    'type' => 'page',
                    'route' => '/audit-logs/queues',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Job Queues'],
                        ['code' => 'retry', 'name' => 'Retry Failed Jobs'],
                        ['code' => 'flush', 'name' => 'Flush Queue'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.5b Activity Feed
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'activity_feed',
            'name' => 'Activity Feed',
            'description' => 'Cross-module activity timeline of user and system actions',
            'icon' => 'ClockIcon',
            'route' => '/activity',
            'priority' => 7,
            'components' => [
                [
                    'code' => 'feed',
                    'name' => 'Activity Feed',
                    'type' => 'page',
                    'route' => '/activity',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Activity Feed'],
                        ['code' => 'export', 'name' => 'Export Activities'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.6 Notifications
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'notifications',
            'name' => 'Notifications',
            'description' => 'Manage notification channels, templates, and broadcasts',
            'icon' => 'BellIcon',
            'route' => '/notifications',
            'priority' => 7,

            'components' => [
                [
                    'code' => 'channels',
                    'name' => 'Notification Channels',
                    'type' => 'page',
                    'route' => '/notifications/channels', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Channels'],
                        ['code' => 'configure', 'name' => 'Configure Channel'],
                        ['code' => 'test', 'name' => 'Test Channel'],
                    ],
                ],
                [
                    'code' => 'templates',
                    'name' => 'Notification Templates',
                    'type' => 'page',
                    'route' => '/notifications/templates', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'edit', 'name' => 'Edit Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                        ['code' => 'preview', 'name' => 'Preview Template'], // Added
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.7 File Manager
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'file_manager',
            'name' => 'File Manager',
            'description' => 'Manage file storage and media library',
            'icon' => 'FolderOpenIcon',
            'route' => '/file-manager',
            'priority' => 8,

            'components' => [
                [
                    'code' => 'storage',
                    'name' => 'Storage Management',
                    'type' => 'page',
                    'route' => '/files/storage', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Storage'],
                        ['code' => 'configure', 'name' => 'Configure Storage'],
                        ['code' => 'cleanup', 'name' => 'Cleanup Storage'],
                    ],
                ],
                [
                    'code' => 'media_library',
                    'name' => 'Media Library',
                    'type' => 'page',
                    'route' => '/files/media', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Media'],
                        ['code' => 'upload', 'name' => 'Upload Media'],
                        ['code' => 'delete', 'name' => 'Delete Media'],
                        ['code' => 'organize', 'name' => 'Organize Folders'], // Added
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 1.8 Settings
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'settings',
            'name' => 'Settings',
            'description' => 'Application settings and preferences',
            'icon' => 'Cog8ToothIcon',
            'route' => '/settings/system',
            'priority' => 99,

            // Collapse the 9 settings pages into a single "Settings" nav link →
            // /settings/system. The unified in-page SettingsLayout rail owns
            // sub-navigation; the component actions below still define HRMAC perms.
            'collapse_nav' => true,

            'components' => [
                [
                    'code' => 'general',
                    'name' => 'General Settings',
                    'type' => 'page',
                    'route' => '/settings/system',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
                [
                    'code' => 'security',
                    'name' => 'Security Settings',
                    'type' => 'page',
                    'route' => '/settings/security', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                        ['code' => 'enable_2fa', 'name' => 'Enable 2FA'],
                        ['code' => 'disable_2fa', 'name' => 'Disable 2FA'],
                    ],
                ],
                [
                    'code' => 'localization',
                    'name' => 'Localization',
                    'type' => 'page',
                    'route' => '/settings/localization', // Filled route
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings'],
                        ['code' => 'edit', 'name' => 'Edit Settings'],
                    ],
                ],
                /* * NEW: Branding Settings (Logo, Colors, White-labeling)
                 */
                [
                    'code' => 'branding',
                    'name' => 'Branding & Appearance',
                    'type' => 'page',
                    'route' => '/settings/branding',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Branding'],
                        ['code' => 'update', 'name' => 'Update Branding'],
                    ],
                ],
                /* * NEW: Mail/SMTP Configuration
                 */
                [
                    'code' => 'mail_settings',
                    'name' => 'Email (SMTP) Settings',
                    'type' => 'page',
                    'route' => '/settings/mail',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Mail Settings'],
                        ['code' => 'update', 'name' => 'Update Mail Configuration'],
                        ['code' => 'test', 'name' => 'Send Test Email'],
                    ],
                ],
                /* * NEW: API & Integrations
                 */
                [
                    'code' => 'integrations',
                    'name' => 'API & Integrations',
                    'type' => 'page',
                    'route' => '/settings/integrations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Integrations'],
                        ['code' => 'configure', 'name' => 'Configure Integration'],
                        ['code' => 'manage_keys', 'name' => 'Manage API Keys'],
                    ],
                ],
                [
                    'code' => 'password_policy',
                    'name' => 'Password Policy',
                    'type' => 'page',
                    'route' => '/settings/password-policy',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Password Policy'],
                        ['code' => 'edit', 'name' => 'Edit Password Policy'],
                    ],
                ],
                [
                    'code' => 'ip_whitelist',
                    'name' => 'IP Access Control',
                    'type' => 'page',
                    'route' => '/settings/ip-whitelist',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View IP Whitelist'],
                        ['code' => 'edit', 'name' => 'Edit IP Whitelist'],
                        ['code' => 'block', 'name' => 'Manage IP Blocklist'],
                        ['code' => 'geo_block', 'name' => 'Manage Geo Blocking'],
                    ],
                ],
                [
                    'code' => 'email_templates',
                    'name' => 'Email Templates',
                    'type' => 'page',
                    'route' => '/settings/email-templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Email Templates'],
                        ['code' => 'create', 'name' => 'Create Email Template'],
                        ['code' => 'edit', 'name' => 'Edit Email Template'],
                        ['code' => 'delete', 'name' => 'Delete Email Template'],
                        ['code' => 'preview', 'name' => 'Preview Email Template'],
                        ['code' => 'test_send', 'name' => 'Test Send Email'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.0 Organization / Tenant Profile
        |--------------------------------------------------------------------------
        | Company / org info managed inside the tenant (vs platform-side
        | tenant master). Required in both SaaS and standalone.
        */
        [
            'code' => 'organization',
            'name' => 'Organization',
            'description' => 'Organization profile, identity, fiscal year, addresses, contacts',
            'icon' => 'BuildingOffice2Icon',
            'route' => '/organization/profile',
            'priority' => 9,
            // collapse_nav: render as a single "Organization" leaf link instead of
            // /organization/profile + 4 siblings. The unified in-page
            // OrganizationLayout rail owns sub-navigation; the component actions
            // below still define HRMAC perms. Honored in BOTH registration paths
            // (AbstractModuleProvider::registerNavigation + AeroCoreServiceProvider::
            // registerCoreNavigation — last-wins, see Settings root cause).
            'collapse_nav' => true,
            'components' => [
                [
                    'code' => 'org_profile', 'name' => 'Organization Profile', 'type' => 'page', 'route' => '/organization/profile',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Profile'],
                        ['code' => 'update', 'name' => 'Update Profile'],
                    ],
                ],
                [
                    'code' => 'org_identity', 'name' => 'Tax / Legal Identity', 'type' => 'page', 'route' => '/organization/identity',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Identity'],
                        ['code' => 'update', 'name' => 'Update Identity'],
                        ['code' => 'verify', 'name' => 'Verify VAT / Tax ID'],
                    ],
                ],
                [
                    'code' => 'org_addresses', 'name' => 'Addresses & Locations', 'type' => 'page', 'route' => '/organization/addresses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Addresses'],
                        ['code' => 'manage', 'name' => 'Manage Addresses'],
                    ],
                ],
                [
                    'code' => 'fiscal_year', 'name' => 'Fiscal Year', 'type' => 'page', 'route' => '/organization/fiscal-year',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Fiscal Year'],
                        ['code' => 'manage', 'name' => 'Manage Fiscal Year'],
                    ],
                ],
                [
                    'code' => 'org_contacts', 'name' => 'Primary Contacts', 'type' => 'page', 'route' => '/organization/contacts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Contacts'],
                        ['code' => 'manage', 'name' => 'Manage Contacts'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.1 Translations / i18n (Delegated to aero-i18n)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'translations_i18n',
            'name' => 'Translations / i18n',
            'description' => 'Language management and translation editor (delegated to aero-i18n package)',
            'icon' => 'LanguageIcon',
            'route' => '/i18n/translations',
            'priority' => 10,
            'delegated_to' => 'aero-i18n',
            'components' => [
                [
                    'code' => 'languages',
                    'name' => 'Languages',
                    'type' => 'page',
                    'route' => '/i18n/languages',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Languages'],
                        ['code' => 'enable', 'name' => 'Enable Language'],
                        ['code' => 'disable', 'name' => 'Disable Language'],
                    ],
                ],
                [
                    'code' => 'translation_editor',
                    'name' => 'Translation Editor',
                    'type' => 'page',
                    'route' => '/i18n/translations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Translations'],
                        ['code' => 'update', 'name' => 'Update Translation'],
                        ['code' => 'auto_translate', 'name' => 'Auto-Translate (AI)'],
                        ['code' => 'import', 'name' => 'Import Translations'],
                        ['code' => 'export', 'name' => 'Export Translations'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.2 SSO & Identity Federation — MOVED to aero-auth (Plan 05 T6)
        |--------------------------------------------------------------------------
        | The sso_identity submodule declarations were moved to
        | packages/aero-auth/config/module.php where the controllers live.
        | Permission paths changed: core.sso_identity.* → auth.sso_identity.*
        */

        /*
        |--------------------------------------------------------------------------
        | 2.2 API & Webhooks
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'api_webhooks',
            'name' => 'API & Webhooks',
            'description' => 'API keys, personal access tokens, OAuth apps, outbound webhooks, rate limits',
            'icon' => 'CommandLineIcon',
            'route' => '/api/keys',
            'priority' => 17,
            'components' => [
                [
                    'code' => 'api_keys', 'name' => 'API Keys', 'type' => 'page', 'route' => '/api/keys',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View API Keys'],
                        ['code' => 'create', 'name' => 'Create API Key'],
                        ['code' => 'revoke', 'name' => 'Revoke API Key'],
                        ['code' => 'rotate', 'name' => 'Rotate API Key'],
                    ],
                ],
                [
                    'code' => 'pat', 'name' => 'Personal Access Tokens', 'type' => 'page', 'route' => '/api/pat',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tokens'],
                        ['code' => 'create', 'name' => 'Create Token'],
                        ['code' => 'revoke', 'name' => 'Revoke Token'],
                    ],
                ],
                [
                    'code' => 'webhooks_outbound', 'name' => 'Outbound Webhooks', 'type' => 'page', 'route' => '/api/webhooks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Webhooks'],
                        ['code' => 'create', 'name' => 'Create Webhook'],
                        ['code' => 'update', 'name' => 'Update Webhook'],
                        ['code' => 'delete', 'name' => 'Delete Webhook'],
                        ['code' => 'test', 'name' => 'Test Webhook'],
                        ['code' => 'logs', 'name' => 'View Delivery Logs'],
                        ['code' => 'replay', 'name' => 'Replay Webhook Delivery'],
                    ],
                ],
                [
                    'code' => 'rate_limits', 'name' => 'API Rate Limits', 'type' => 'page', 'route' => '/api/rate-limits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rate Limits'],
                        ['code' => 'configure', 'name' => 'Configure Rate Limits'],
                    ],
                ],
                [
                    'code' => 'api_usage', 'name' => 'API Usage Analytics', 'type' => 'page', 'route' => '/api/usage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View API Usage'],
                        ['code' => 'export', 'name' => 'Export Usage'],
                    ],
                ],
                [
                    'code' => 'api_docs', 'name' => 'API Documentation Portal', 'type' => 'page', 'route' => '/api/docs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View API Docs'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.3 Workflow Engine (Delegated to aero-workflow)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'workflow_engine',
            'name' => 'Workflows & Automations',
            'description' => 'Approval workflows, automation rules, triggers, conditions (delegated to aero-workflow package)',
            'icon' => 'ArrowPathRoundedSquareIcon',
            'route' => '/workflows',
            'priority' => 21,
            'delegated_to' => 'aero-workflow',
            'components' => [
                [
                    'code' => 'definitions', 'name' => 'Workflow Definitions', 'type' => 'page', 'route' => '/workflows',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Workflows'],
                        ['code' => 'create', 'name' => 'Create Workflow'],
                        ['code' => 'update', 'name' => 'Update Workflow'],
                        ['code' => 'delete', 'name' => 'Delete Workflow'],
                        ['code' => 'activate', 'name' => 'Activate Workflow'],
                        ['code' => 'deactivate', 'name' => 'Deactivate Workflow'],
                    ],
                ],
                [
                    'code' => 'templates', 'name' => 'Workflow Templates', 'type' => 'page', 'route' => '/workflow-templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                    ],
                ],
                [
                    'code' => 'instances', 'name' => 'Workflow Instances', 'type' => 'page', 'route' => '/workflow-instances',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Instances'],
                        ['code' => 'retry', 'name' => 'Retry Instance'],
                    ],
                ],
                [
                    'code' => 'approvals', 'name' => 'My Approvals', 'type' => 'page', 'route' => '/workflow-instances/approvals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Approvals'],
                        ['code' => 'approve', 'name' => 'Approve'],
                        ['code' => 'reject', 'name' => 'Reject'],
                        ['code' => 'escalate', 'name' => 'Escalate'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'custom_fields',
            'name' => 'Custom Fields',
            'description' => 'Per-entity custom fields, field types, validation (delegated to aero-custom-fields package)',
            'icon' => 'AdjustmentsHorizontalIcon',
            'route' => '/custom-fields',
            'priority' => 12,
            'delegated_to' => 'aero-custom-fields',
            'components' => [
                [
                    'code' => 'field_definitions', 'name' => 'Field Definitions', 'type' => 'page', 'route' => '/custom-fields',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'create', 'name' => 'Create Field'],
                        ['code' => 'update', 'name' => 'Update Field'],
                        ['code' => 'delete', 'name' => 'Delete Field'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'tags_labels',
            'name' => 'Tags & Labels',
            'description' => 'Cross-module tagging system',
            'icon' => 'TagIcon',
            'route' => '/tags',
            'priority' => 13,
            'components' => [
                [
                    'code' => 'tag_management', 'name' => 'Tag Management', 'type' => 'page', 'route' => '/tags',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tags'],
                        ['code' => 'create', 'name' => 'Create Tag'],
                        ['code' => 'update', 'name' => 'Update Tag'],
                        ['code' => 'delete', 'name' => 'Delete Tag'],
                        ['code' => 'merge', 'name' => 'Merge Tags'],
                    ],
                ],
            ],
        ],
        [
            'code' => 'saved_views',
            'name' => 'Saved Views & Filters',
            'description' => 'User and shared saved views across modules',
            'icon' => 'ViewColumnsIcon',
            'route' => '/saved-views',
            'priority' => 14,
            'components' => [
                [
                    'code' => 'saved_views', 'name' => 'Saved Views', 'type' => 'page', 'route' => '/saved-views',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View'],
                        ['code' => 'create', 'name' => 'Create View'],
                        ['code' => 'update', 'name' => 'Update View'],
                        ['code' => 'delete', 'name' => 'Delete View'],
                        ['code' => 'share', 'name' => 'Share View'],
                        ['code' => 'set_default', 'name' => 'Set as Default'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.5 Form Builder
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'form_builder',
            'name' => 'Form Builder',
            'description' => 'Drag-drop form builder with conditional logic, validation, and submissions (delegated to aero-forms package)',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/forms',
            'priority' => 15,
            'delegated_to' => 'aero-forms',
            'components' => [
                [
                    'code' => 'forms', 'name' => 'Forms', 'type' => 'page', 'route' => '/forms',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Forms'],
                        ['code' => 'create', 'name' => 'Create Form'],
                        ['code' => 'update', 'name' => 'Update Form'],
                        ['code' => 'delete', 'name' => 'Delete Form'],
                        ['code' => 'publish', 'name' => 'Publish Form'],
                    ],
                ],
                [
                    'code' => 'submissions', 'name' => 'Submissions', 'type' => 'page', 'route' => '/forms/submissions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submissions'],
                        ['code' => 'export', 'name' => 'Export Submissions'],
                        ['code' => 'delete', 'name' => 'Delete Submission'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.5 Global Search
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'global_search',
            'name' => 'Global Search',
            'description' => 'Cross-module search and indexing',
            'icon' => 'MagnifyingGlassIcon',
            'route' => '/search',
            'priority' => 16,
            'components' => [
                [
                    'code' => 'search_ui', 'name' => 'Search Interface', 'type' => 'page', 'route' => '/search',
                    'actions' => [
                        ['code' => 'use', 'name' => 'Use Search'],
                    ],
                ],
                [
                    'code' => 'search_index', 'name' => 'Search Index Management', 'type' => 'page', 'route' => '/search/index',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Index'],
                        ['code' => 'reindex', 'name' => 'Reindex Content'],
                        ['code' => 'configure', 'name' => 'Configure Search'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.8 Data Export/Import
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'data_export_import',
            'name' => 'Data Export/Import',
            'description' => 'Export and import data across entities with support for multiple formats, export history, and scheduling',
            'icon' => 'ArrowPathIcon',
            'route' => '/export-import/exports',
            'priority' => 19,
            'components' => [
                [
                    'code' => 'exports', 'name' => 'Exports', 'type' => 'page', 'route' => '/export-import/exports',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Exports'],
                        ['code' => 'create', 'name' => 'Create Export'],
                        ['code' => 'download', 'name' => 'Download Export'],
                        ['code' => 'delete', 'name' => 'Delete Export'],
                    ],
                ],
                [
                    'code' => 'imports', 'name' => 'Imports', 'type' => 'page', 'route' => '/export-import/imports',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Imports'],
                        ['code' => 'create', 'name' => 'Import Data'],
                        ['code' => 'download_template', 'name' => 'Download Template'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.10 Retention Policies
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'retention_policies',
            'name' => 'Retention Policies',
            'description' => 'Automated data retention and cleanup policies for audit logs, activities, and exports',
            'icon' => 'TrashIcon',
            'route' => '/retention-policies',
            'priority' => 20,
            'components' => [
                [
                    'code' => 'policies', 'name' => 'Policies', 'type' => 'page', 'route' => '/retention-policies',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Policies'],
                        ['code' => 'create', 'name' => 'Create Policy'],
                        ['code' => 'update', 'name' => 'Update Policy'],
                        ['code' => 'delete', 'name' => 'Delete Policy'],
                        ['code' => 'execute', 'name' => 'Execute Policy'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.12 Trash & Recycle Bin
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'trash',
            'name' => 'Trash & Recycle Bin',
            'description' => 'System-wide trash bin with restore and permanent delete functionality',
            'icon' => 'TrashIcon',
            'route' => '/trash',
            'priority' => 31,
            'components' => [
                [
                    'code' => 'view', 'name' => 'View Trash', 'type' => 'page', 'route' => '/trash',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Trashed Items'],
                        ['code' => 'restore', 'name' => 'Restore Items'],
                        ['code' => 'force_delete', 'name' => 'Permanently Delete'],
                        ['code' => 'empty', 'name' => 'Empty Trash'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.13 Comments & Mentions
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'user_preferences',
            'name' => 'User Preferences',
            'description' => 'Per-user notification preferences, theme, locale, timezone, accessibility',
            'icon' => 'AdjustmentsVerticalIcon',
            'route' => '/preferences',
            'priority' => 18,
            // Hidden until rebuilt: the page set (Core/UserPreferences/*) targets a
            // Radix-style API (Select.Trigger/.Content/.Item, Switch onCheckedChange,
            // Tabs.*) that @aero/ui does not implement, so it renders blank (React #130).
            'show_in_nav' => false,
            'components' => [
                [
                    'code' => 'notification_preferences', 'name' => 'Notification Preferences', 'type' => 'page', 'route' => '/preferences/notifications',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Preferences'],
                        ['code' => 'update', 'name' => 'Update Preferences'],
                        ['code' => 'dnd', 'name' => 'Configure Do-Not-Disturb'],
                        ['code' => 'digest', 'name' => 'Configure Digest'],
                    ],
                ],
                [
                    'code' => 'theme_preferences', 'name' => 'Theme & Appearance', 'type' => 'page', 'route' => '/preferences/theme',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Theme'],
                        ['code' => 'update', 'name' => 'Update Theme'],
                    ],
                ],
                [
                    'code' => 'locale_preferences', 'name' => 'Locale, Date & Currency', 'type' => 'page', 'route' => '/preferences/locale',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Locale'],
                        ['code' => 'update', 'name' => 'Update Locale'],
                    ],
                ],
                [
                    'code' => 'accessibility', 'name' => 'Accessibility', 'type' => 'page', 'route' => '/preferences/accessibility',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Accessibility'],
                        ['code' => 'update', 'name' => 'Update Accessibility'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.8 Comments, Mentions, Activity Feed
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'comments_mentions',
            'name' => 'Comments & Mentions',
            'description' => 'Cross-module comment system with @mentions and reactions',
            'icon' => 'ChatBubbleLeftRightIcon',
            'route' => '/comments',
            'priority' => 32,
            'show_in_nav' => false,
            'components' => [
                [
                    'code' => 'comments', 'name' => 'Comments', 'type' => 'feature', 'route' => null,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Comments'],
                        ['code' => 'create', 'name' => 'Add Comment'],
                        ['code' => 'update', 'name' => 'Edit Comment'],
                        ['code' => 'delete', 'name' => 'Delete Comment'],
                        ['code' => 'react', 'name' => 'React to Comment'],
                        ['code' => 'mention', 'name' => '@mention User'],
                    ],
                ],
                [
                    'code' => 'mentions_inbox', 'name' => 'Mentions Inbox', 'type' => 'page', 'route' => '/mentions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Mentions'],
                        ['code' => 'mark_read', 'name' => 'Mark as Read'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.9 Help & Support
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'help_support',
            'name' => 'Help & Support',
            'description' => 'In-tenant help center, knowledge base, support tickets, onboarding tours',
            'icon' => 'LifebuoyIcon',
            'route' => '/help',
            'priority' => 33,
            'components' => [
                [
                    'code' => 'help_center', 'name' => 'Help Center', 'type' => 'page', 'route' => '/help',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Help'],
                    ],
                ],
                [
                    'code' => 'knowledge_base', 'name' => 'Knowledge Base', 'type' => 'page', 'route' => '/help/kb',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Articles'],
                        ['code' => 'search', 'name' => 'Search KB'],
                    ],
                ],
                [
                    'code' => 'support_tickets', 'name' => 'Support Tickets (to Platform)', 'type' => 'page', 'route' => '/help/tickets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tickets'],
                        ['code' => 'create', 'name' => 'Create Ticket'],
                        ['code' => 'reply', 'name' => 'Reply to Ticket'],
                        ['code' => 'close', 'name' => 'Close Ticket'],
                    ],
                ],
                [
                    'code' => 'onboarding_tours', 'name' => 'Onboarding Tours', 'type' => 'page', 'route' => '/help/tours',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tours'],
                        ['code' => 'start', 'name' => 'Start Tour'],
                    ],
                ],
                [
                    'code' => 'whats_new', 'name' => "What's New / Changelog", 'type' => 'page', 'route' => '/help/whats-new',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Changelog'],
                    ],
                ],
                [
                    'code' => 'feedback', 'name' => 'Feedback & Feature Requests', 'type' => 'page', 'route' => '/help/feedback',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Feedback'],
                        ['code' => 'submit', 'name' => 'Submit Feedback'],
                        ['code' => 'vote', 'name' => 'Vote on Feature'],
                    ],
                ],
                [
                    'code' => 'live_chat', 'name' => 'Live Chat Widget', 'type' => 'feature', 'route' => null,
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Live Chat'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.10 Data & Privacy (DEPRECATED - Moved to aero-compliance)
        |--------------------------------------------------------------------------
        |
        | GDPR/CCPA/HIPAA compliance features have been moved to aero-compliance package.
        | This submodule is removed to prevent duplication and ensure compliance features
        | are centralized in the appropriate domain package.
        |
        | For data export, DSAR, consent management, retention policies, and compliance
        * mode, use the aero-compliance package which is the single source of truth for
        * all regulatory compliance features.
        */

        /*
        |--------------------------------------------------------------------------
        | 2.11 Email Engine (separate from notification templates)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'email_engine',
            'name' => 'Email Engine',
            'description' => 'Outbound email infrastructure: templates, deliverability, suppression, tracking',
            'icon' => 'EnvelopeIcon',
            'route' => '/email',
            'priority' => 22,
            'components' => [
                [
                    'code' => 'email_templates', 'name' => 'Email Templates', 'type' => 'page', 'route' => '/email/templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                        ['code' => 'preview', 'name' => 'Preview Template'],
                        ['code' => 'send_test', 'name' => 'Send Test Email'],
                    ],
                ],
                [
                    'code' => 'email_logs', 'name' => 'Email Logs', 'type' => 'page', 'route' => '/email/logs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Logs'],
                        ['code' => 'resend', 'name' => 'Resend Email'],
                        ['code' => 'export', 'name' => 'Export Logs'],
                    ],
                ],
                [
                    'code' => 'suppression_list', 'name' => 'Suppression List', 'type' => 'page', 'route' => '/email/suppression',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Suppression'],
                        ['code' => 'remove', 'name' => 'Remove from Suppression'],
                    ],
                ],
                [
                    'code' => 'deliverability', 'name' => 'Deliverability (DKIM/SPF/DMARC)', 'type' => 'page', 'route' => '/email/deliverability',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DNS Status'],
                        ['code' => 'configure', 'name' => 'Configure DKIM/SPF'],
                        ['code' => 'verify', 'name' => 'Verify DNS Records'],
                    ],
                ],
                [
                    'code' => 'bounce_complaint', 'name' => 'Bounces & Complaints', 'type' => 'page', 'route' => '/email/bounces',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bounces'],
                        ['code' => 'export', 'name' => 'Export Bounces'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.12 System Health & Diagnostics
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'system_health',
            'name' => 'System Health',
            'description' => 'Health checks, diagnostics, performance metrics, storage usage',
            'icon' => 'HeartIcon',
            'route' => '/system-health',
            'priority' => 23,
            'components' => [
                [
                    'code' => 'health_status', 'name' => 'Health Status', 'type' => 'page', 'route' => '/system-health',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Health'],
                        ['code' => 'run_checks', 'name' => 'Run Diagnostics'],
                    ],
                ],
                [
                    'code' => 'performance_metrics', 'name' => 'Performance Metrics', 'type' => 'page', 'route' => '/system-health/performance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Metrics'],
                        ['code' => 'export', 'name' => 'Export Metrics'],
                    ],
                ],
                [
                    'code' => 'storage_usage', 'name' => 'Storage Usage', 'type' => 'page', 'route' => '/system-health/storage',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Storage'],
                        ['code' => 'cleanup', 'name' => 'Run Cleanup'],
                    ],
                ],
                [
                    'code' => 'scheduled_tasks', 'name' => 'Scheduled Tasks', 'type' => 'page', 'route' => '/system-health/scheduled-tasks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Scheduled Tasks'],
                        ['code' => 'run_now', 'name' => 'Run Now'],
                        ['code' => 'pause', 'name' => 'Pause Task'],
                    ],
                ],
                [
                    'code' => 'cache_management', 'name' => 'Cache Management', 'type' => 'page', 'route' => '/system-health/cache',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cache'],
                        ['code' => 'clear', 'name' => 'Clear Cache'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.13 Mobile / PWA Settings
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'mobile_pwa',
            'name' => 'Mobile & PWA',
            'description' => 'Mobile app and Progressive Web App configuration',
            'icon' => 'DevicePhoneMobileIcon',
            'route' => '/mobile-pwa',
            'priority' => 24,
            'components' => [
                [
                    'code' => 'pwa_config', 'name' => 'PWA Configuration', 'type' => 'page', 'route' => '/mobile-pwa/pwa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PWA Settings'],
                        ['code' => 'configure', 'name' => 'Configure PWA Manifest'],
                    ],
                ],
                [
                    'code' => 'push_notifications', 'name' => 'Push Notifications', 'type' => 'page', 'route' => '/mobile-pwa/push',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Push Config'],
                        ['code' => 'configure', 'name' => 'Configure Push'],
                        ['code' => 'send_test', 'name' => 'Send Test Push'],
                    ],
                ],
                [
                    'code' => 'mobile_app_config', 'name' => 'Mobile App Configuration', 'type' => 'page', 'route' => '/mobile-pwa/mobile-app',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Mobile Config'],
                        ['code' => 'configure', 'name' => 'Configure Mobile App'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.14 Backup & Restore (Tenant-Side)
        |--------------------------------------------------------------------------
        | Standalone: full backup/restore. SaaS: backup-request that escalates
        | to platform-level scheduled backups.
        */
        [
            'code' => 'backup_restore',
            'name' => 'Backup & Restore',
            'description' => 'Tenant-side backup configuration, manual backup, restore points',
            'icon' => 'CircleStackIcon',
            'route' => '/backup',
            'priority' => 25,
            'components' => [
                [
                    'code' => 'backup_dashboard', 'name' => 'Backup Dashboard', 'type' => 'page', 'route' => '/backup',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Backups'],
                    ],
                ],
                [
                    'code' => 'backup_config', 'name' => 'Backup Configuration', 'type' => 'page', 'route' => '/backup/config',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Configuration'],
                        ['code' => 'configure', 'name' => 'Configure Backup'],
                    ],
                ],
                [
                    'code' => 'manual_backup', 'name' => 'Manual Backup', 'type' => 'page', 'route' => '/backup/manual',
                    'actions' => [
                        ['code' => 'create', 'name' => 'Create Manual Backup'],
                        ['code' => 'download', 'name' => 'Download Backup'],
                    ],
                ],
                [
                    'code' => 'restore_points', 'name' => 'Restore Points', 'type' => 'page', 'route' => '/backup/restore',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Restore Points'],
                        ['code' => 'restore', 'name' => 'Restore from Point'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.15 License Management (Standalone Distribution Only)
        |--------------------------------------------------------------------------
        | Hidden in SaaS mode; replaced by aero-platform plan_management.
        */
        [
            'code' => 'license_management',
            'name' => 'License Management',
            'description' => 'License key validation, edition tier, feature gating (standalone mode only)',
            'icon' => 'KeyIcon',
            'route' => '/license',
            'priority' => 26,
            'plan' => 'standalone',
            'components' => [
                [
                    'code' => 'license_overview', 'name' => 'License Overview', 'type' => 'page', 'route' => '/license',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View License'],
                    ],
                ],
                [
                    'code' => 'license_activation', 'name' => 'License Activation', 'type' => 'page', 'route' => '/license/activate',
                    'actions' => [
                        ['code' => 'activate', 'name' => 'Activate License'],
                        ['code' => 'deactivate', 'name' => 'Deactivate License'],
                        ['code' => 'verify', 'name' => 'Verify License Online'],
                    ],
                ],
                [
                    'code' => 'license_features', 'name' => 'Edition Features', 'type' => 'page', 'route' => '/license/features',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Edition Features'],
                    ],
                ],
                [
                    'code' => 'license_renewal', 'name' => 'License Renewal', 'type' => 'page', 'route' => '/license/renewal',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Renewal'],
                        ['code' => 'renew', 'name' => 'Renew License'],
                    ],
                ],
                [
                    'code' => 'updates', 'name' => 'Updates & Patches', 'type' => 'page', 'route' => '/license/updates',
                    'actions' => [
                        ['code' => 'check', 'name' => 'Check for Updates'],
                        ['code' => 'download', 'name' => 'Download Update'],
                        ['code' => 'apply', 'name' => 'Apply Update'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.16 Numbering / Sequences (shared service)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'numbering',
            'name' => 'Document Numbering & Sequences',
            'description' => 'Shared numbering sequences for invoices, tickets, POs, work orders, etc.',
            'icon' => 'HashtagIcon',
            'route' => '/numbering',
            'priority' => 27,
            'components' => [
                [
                    'code' => 'sequences', 'name' => 'Sequences', 'type' => 'page', 'route' => '/numbering/sequences',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sequences'],
                        ['code' => 'create', 'name' => 'Create Sequence'],
                        ['code' => 'update', 'name' => 'Update Sequence'],
                        ['code' => 'reset', 'name' => 'Reset Sequence'],
                    ],
                ],
                [
                    'code' => 'numbering_formats', 'name' => 'Number Formats', 'type' => 'page', 'route' => '/numbering/formats',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Formats'],
                        ['code' => 'manage', 'name' => 'Manage Formats'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.17 Print / PDF Templates (shared service)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'print_templates',
            'name' => 'Print & PDF Templates',
            'description' => 'Shared print/PDF templates for documents, reports, invoices, certificates',
            'icon' => 'PrinterIcon',
            'route' => '/print-templates',
            'priority' => 28,
            'components' => [
                [
                    'code' => 'templates', 'name' => 'Print Templates', 'type' => 'page', 'route' => '/print-templates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Templates'],
                        ['code' => 'create', 'name' => 'Create Template'],
                        ['code' => 'update', 'name' => 'Update Template'],
                        ['code' => 'delete', 'name' => 'Delete Template'],
                        ['code' => 'preview', 'name' => 'Preview Template'],
                    ],
                ],
                [
                    'code' => 'paper_sizes', 'name' => 'Paper Sizes & Margins', 'type' => 'page', 'route' => '/print-templates/paper',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Paper Settings'],
                    ],
                ],
                [
                    'code' => 'header_footer', 'name' => 'Headers & Footers', 'type' => 'page', 'route' => '/print-templates/header-footer',
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Headers & Footers'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.18 Announcements & Banners (Tenant-side)
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'announcements',
            'name' => 'Announcements & Banners',
            'description' => 'Tenant-internal announcements and banner messages (separate from notifications)',
            'icon' => 'MegaphoneIcon',
            'route' => '/announcements',
            'priority' => 29,
            'components' => [
                [
                    'code' => 'announcement_list', 'name' => 'Announcements', 'type' => 'page', 'route' => '/announcements',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Announcements'],
                        ['code' => 'create', 'name' => 'Create Announcement'],
                        ['code' => 'update', 'name' => 'Update Announcement'],
                        ['code' => 'delete', 'name' => 'Delete Announcement'],
                        ['code' => 'publish', 'name' => 'Publish Announcement'],
                        ['code' => 'archive', 'name' => 'Archive Announcement'],
                    ],
                ],
                [
                    'code' => 'banners', 'name' => 'Banners (System-wide)', 'type' => 'page', 'route' => '/announcements/banners',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Banners'],
                        ['code' => 'manage', 'name' => 'Manage Banners'],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2.19 Maintenance Mode
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'maintenance_mode',
            'name' => 'Maintenance Mode',
            'description' => 'Tenant-side maintenance mode toggle, allowed IPs, custom message',
            'icon' => 'WrenchScrewdriverIcon',
            'route' => '/maintenance-mode',
            'priority' => 30,
            'components' => [
                [
                    'code' => 'maintenance_toggle', 'name' => 'Maintenance Toggle', 'type' => 'page', 'route' => '/maintenance-mode',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Maintenance Status'],
                        ['code' => 'enable', 'name' => 'Enable Maintenance Mode'],
                        ['code' => 'disable', 'name' => 'Disable Maintenance Mode'],
                        ['code' => 'configure', 'name' => 'Configure Maintenance Mode'],
                        ['code' => 'allow_ip', 'name' => 'Manage Bypass IPs'],
                    ],
                ],
            ],
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access'],
    ],

];
