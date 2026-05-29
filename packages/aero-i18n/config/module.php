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

    'code' => 'i18n',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',
    'name' => 'Internationalization Infrastructure',
    'description' => 'Shared i18n layer: translations, locales, RTL support, currency formatting, date/time formatting, and language detection for both tenant and platform contexts.',
    'icon' => 'GlobeAltIcon',
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

    'submodules' => [
        [
            'code' => 'translations',
            'name' => 'Translation Management',
            'description' => 'Language management and translation editor',
            'icon' => 'LanguageIcon',
            'route' => '/i18n/translations',
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
    ],

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,
        // Plan 07 (aero-i18n) Task 1 — declarations now match the shipped
        // migration shape (`languages` + `translations`). Phase 1 audit
        // found the previous block declared `platform_locales`,
        // `platform_translations`, `tenant_locales`, `tenant_translations`,
        // `locale_preferences` — none of which exist as migrations. The
        // migrations 2026_05_05_000000 and 2026_05_05_000001 create the
        // two tables below, on the tenant connection.
        'central_tables' => [],
        'tenant_tables' => [
            'languages',
            'translations',
        ],
    ],
];
