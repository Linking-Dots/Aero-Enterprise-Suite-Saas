<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile Module — Mobile App Framework
    |--------------------------------------------------------------------------
    |
    | Mobile app framework with PWA configuration, push notifications,
    * offline sync, and mobile app builder.
    *
    | Tenancy concern:
    *   - PWA configurations are tenant-scoped (custom branding)
    *   - Push notification tokens are tenant-scoped
    *   - Offline sync data is tenant-scoped
    *   - No cross-tenant mobile data access
    */

    'code' => 'mobile',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',
    'name' => 'Mobile App Framework',
    'description' => 'Mobile app framework: PWA configuration, push notifications, offline sync, and mobile app builder for both tenant and platform contexts.',
    'icon' => 'DevicePhoneMobileIcon',
    'route_prefix' => null,
    'category' => 'infrastructure',
    'priority' => 0,
    'is_core' => true,
    'is_active' => true,
    'enabled' => true,
    'version' => '1.0.0',
    'min_plan' => null,
    'license_type' => 'platform',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'marketplace_visible' => false,

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,
        'central_tables' => [
            'platform_pwa_config',
            'platform_push_credentials',
        ],
        'tenant_tables' => [
            'tenant_pwa_config',
            'push_tokens',
            'offline_sync_queue',
        ],
    ],
];
