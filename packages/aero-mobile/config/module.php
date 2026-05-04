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

    'code'         => 'mobile',
    'scope'        => 'infrastructure',
    'name'         => 'Mobile App Framework',
    'description'  => 'Mobile app framework: PWA configuration, push notifications, offline sync, and mobile app builder for both tenant and platform contexts.',
    'icon'         => 'DevicePhoneMobileIcon',
    'route_prefix' => null,
    'category'     => 'infrastructure',
    'priority'     => 0,
    'is_core'      => true,
    'is_active'    => true,
    'enabled'      => true,
    'version'      => '1.0.0',
    'min_plan'     => null,
    'license_type' => 'platform',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'marketplace_visible' => false,

    'features' => [
        'pwa_configuration'       => true,
        'push_notifications'      => true,
        'offline_sync'            => true,
        'mobile_app_builder'      => true,
        'native_wrappers'         => true,
        'device_management'       => true,
        'app_updates'             => true,
        'biometric_auth'          => true,
        'location_tracking'       => true,
        'camera_scanner'          => true,
        'mobile_analytics'        => true,
    ],

    'tenancy' => [
        'tenant_aware'      => true,
        'uses_tenant_db'    => true,
        'central_tables'    => [
            'platform_pwa_config',
            'platform_push_credentials',
        ],
        'tenant_tables'     => [
            'tenant_pwa_config',
            'push_tokens',
            'offline_sync_queue',
        ],
    ],
];
