<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internationalization Package — i18n Infrastructure
    |--------------------------------------------------------------------------
    | Scope: BOTH platform + tenant (infrastructure layer, not a UI module)
    |
    | aero-i18n is NOT a user-facing module. It is the shared internationalization
    | infrastructure used by all modules for translations, locales, and RTL support.
    | It does not appear in the module marketplace.
    |
    | Tenancy concern:
    *   - Translation files are tenant-scoped (custom translations)
    *   - Locale preferences are tenant-scoped
    *   - Platform default locale in central config
    *   - Tenant default locale in tenant DB
    */

    'code'         => 'i18n',
    'scope'        => 'infrastructure',
    'name'         => 'Internationalization Infrastructure',
    'description'  => 'Shared i18n layer: translations, locales, RTL support, currency formatting, date/time formatting, and language detection for both tenant and platform contexts.',
    'icon'         => 'GlobeAltIcon',
    'route_prefix' => null,
    'category'     => 'infrastructure',
    'priority'     => 0,
    'is_core'      => true,
    'is_active'    => true,
    'enabled'      => true,
    'version'      => '1.0.0',
    'min_plan'     => null,
    'license_type' => 'platform',
    'dependencies' => [],
    'release_date' => '2024-01-01',
    'marketplace_visible' => false,

    'features' => [
        'translations'            => true,
        'locale_management'       => true,
        'rtl_support'             => true,
        'currency_formatting'     => true,
        'date_time_formatting'    => true,
        'number_formatting'       => true,
        'language_detection'     => true,
        'translation_import_export'=> true,
        'missing_translation_detection'=> true,
        'pluralization'           => true,
    ],

    'tenancy' => [
        'tenant_aware'      => true,
        'uses_tenant_db'    => true,
        'central_tables'    => [
            'platform_locales',
            'platform_translations',
        ],
        'tenant_tables'     => [
            'tenant_locales',
            'tenant_translations',
            'locale_preferences',
        ],
    ],
];
