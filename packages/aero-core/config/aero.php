<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Mode
    |--------------------------------------------------------------------------
    |
    | Determines the operational mode of the application:
    | - 'saas': Multi-tenant mode with aero-platform
    | - 'standalone': Single-tenant mode without platform
    |
    */

    'mode' => env('AERO_MODE', 'standalone'),

    /*
    |--------------------------------------------------------------------------
    | Platform Domains (SaaS)
    |--------------------------------------------------------------------------
    |
    | The landlord (platform) domain and its admin subdomain. Resolved from env
    | HERE, at config-load time, so the values survive `php artisan config:cache`.
    | Route registration and domain-context checks MUST read these via
    | config('aero.platform_domain' | 'aero.admin_domain') and NEVER env(...)
    | directly: once config is cached, env() outside config files returns null,
    | which previously routed the admin domain to the bogus 'admin.localhost'
    | and 404'd every admin route after a cached install.
    |
    */

    'platform_domain' => env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost')),

    'admin_domain' => env(
        'ADMIN_DOMAIN',
        'admin.'.env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Standalone Tenant ID
    |--------------------------------------------------------------------------
    |
    | The default tenant ID used in standalone mode for tenant isolation.
    | This ensures that models using AeroTenantable trait work correctly.
    |
    */

    'standalone_tenant_id' => env('AERO_STANDALONE_TENANT_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Runtime Module Loading
    |--------------------------------------------------------------------------
    |
    | Enable/disable runtime module loading for standalone mode.
    | When enabled, modules in the 'modules' directory are automatically
    | discovered and loaded without requiring Composer.
    |
    */

    'runtime_loading' => [
        'enabled' => env('AERO_RUNTIME_LOADING', true),
        'modules_path' => env('AERO_MODULES_PATH', base_path('modules')),
        'cache_enabled' => env('AERO_CACHE_MODULES', true),
        'cache_ttl' => env('AERO_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically discover and register modules from the packages directory.
    | This is used in both SaaS and Standalone modes.
    |
    */

    'auto_discovery' => [
        'enabled' => env('AERO_AUTO_DISCOVERY', true),
        'packages_path' => base_path('packages'),
        'scan_patterns' => [
            'packages/aero-*/src/*ServiceProvider.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Registry
    |--------------------------------------------------------------------------
    |
    | List of installed modules. In SaaS mode, this is synced with Composer.
    | In Standalone mode, this is manually maintained or auto-discovered.
    |
    */

    'modules' => [
        'installed' => [
            // Core modules (always available)
            'core' => [
                'enabled' => true,
                'required' => true,
            ],

            // Platform module (SaaS only)
            'platform' => [
                'enabled' => env('AERO_PLATFORM_ENABLED', false),
                'required' => false,
            ],

            // Feature modules
            'hrm' => [
                'enabled' => env('AERO_HRM_ENABLED', true),
                'required' => false,
            ],
            'crm' => [
                'enabled' => env('AERO_CRM_ENABLED', false),
                'required' => false,
            ],
            'finance' => [
                'enabled' => env('AERO_FINANCE_ENABLED', false),
                'required' => false,
            ],
            'project' => [
                'enabled' => env('AERO_PROJECT_ENABLED', false),
                'required' => false,
            ],
            'pos' => [
                'enabled' => env('AERO_POS_ENABLED', false),
                'required' => false,
            ],
            'ims' => [
                'enabled' => env('AERO_IMS_ENABLED', false),
                'required' => false,
            ],
            'scm' => [
                'enabled' => env('AERO_SCM_ENABLED', false),
                'required' => false,
            ],
            'dms' => [
                'enabled' => env('AERO_DMS_ENABLED', false),
                'required' => false,
            ],
            'quality' => [
                'enabled' => env('AERO_QUALITY_ENABLED', false),
                'required' => false,
            ],
            'compliance' => [
                'enabled' => env('AERO_COMPLIANCE_ENABLED', false),
                'required' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Management
    |--------------------------------------------------------------------------
    |
    | Configuration for module asset handling in different modes.
    |
    */

    'assets' => [
        'saas' => [
            'path' => public_path('vendor'),
            'url' => '/vendor',
        ],
        'standalone' => [
            'path' => public_path('modules'),
            'url' => '/modules',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Module-specific database settings.
    |
    */

    'database' => [
        // Tenant isolation strategy
        'tenant_isolation' => [
            'strategy' => env('AERO_TENANT_STRATEGY', 'database'), // 'database' or 'column'
            'column_name' => 'tenant_id',
        ],

        // Migration settings
        'migrations' => [
            'auto_run' => env('AERO_AUTO_MIGRATE', false),
            'path' => 'database/migrations',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for React/Vite module system.
    |
    */

    'frontend' => [
        'build_mode' => env('AERO_BUILD_MODE', 'library'), // 'library' or 'bundle'
        'shared_dependencies' => [
            'react',
            'react-dom',
            '@inertiajs/react',
            '@heroui/react',
        ],
        'cdn' => [
            'enabled' => env('AERO_CDN_ENABLED', true),
            'react' => 'https://unpkg.com/react@18/umd/react.production.min.js',
            'react_dom' => 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Performance
    |--------------------------------------------------------------------------
    |
    | Security and performance-related settings.
    |
    */

    'security' => [
        'verify_module_signatures' => env('AERO_VERIFY_SIGNATURES', false),
        'allowed_module_sources' => env('AERO_ALLOWED_SOURCES', '*'),
    ],

    'performance' => [
        'lazy_load_modules' => env('AERO_LAZY_LOAD', true),
        'preload_modules' => env('AERO_PRELOAD_MODULES', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Error Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for reporting errors to the central Aero platform.
    | All installations (SaaS tenants and standalone) report errors to the
    | platform for centralized monitoring, analytics, and product improvement.
    |
    | In standalone mode, errors are sent to platform.aerosuite.com
    | In SaaS mode, errors are stored directly in the central database
    |
    */

    'error_reporting' => [
        // Enable/disable remote error reporting
        'enabled' => env('AERO_ERROR_REPORTING', true),

        // Platform API endpoint for error reporting
        'api_url' => env('AERO_PLATFORM_API_URL', 'https://platform.aerosuite.com/api/v1'),

        // License key for authentication (validates the installation)
        'license_key' => env('AERO_LICENSE_KEY', ''),

        // Error severity levels to report (all, critical, server_only)
        // 'all' = 4xx and 5xx errors
        // 'server_only' = only 5xx errors
        // 'critical' = only 500, 502, 503 errors
        'level' => env('AERO_ERROR_LEVEL', 'all'),

        // Send errors asynchronously (queued) - recommended for production
        'async' => env('AERO_ERROR_ASYNC', true),

        // Retry failed reports
        'retry_attempts' => env('AERO_ERROR_RETRY', 3),

        // Timeout for API calls (seconds)
        'timeout' => env('AERO_ERROR_TIMEOUT', 5),

        // Fields to always redact from payloads
        'redact_fields' => [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_token', 'access_token', 'refresh_token',
            'secret', 'api_secret', 'client_secret', 'bearer',
            'credit_card', 'card_number', 'cvv', 'cvc', 'card_cvc',
            'ssn', 'social_security', 'pin',
            'authorization', 'cookie', 'session',
        ],

        // Admin email for critical error notifications (used by MailService)
        'admin_email' => env('AERO_ADMIN_EMAIL', ''),

        // Notify admin on these error types
        'notify_on' => ['ServerException', 'DatabaseException', 'TenantNotFoundException'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved Route Prefixes
    |--------------------------------------------------------------------------
    |
    | These route prefixes are reserved for specific packages and should NOT
    | be used by other modules to prevent routing conflicts.
    |
    | When creating new modules, use your module's name as prefix:
    | - /hrm/* - Reserved for aero-hrm
    | - /crm/* - Reserved for aero-crm
    | - /api/{module}/* - Module-specific APIs
    |
    | Violation of reserved prefixes will cause RouteConflictTest to fail.
    |
    */

    'routing' => [
        // Reserved API prefixes (used by RouteConflictTest)
        'reserved_api_prefixes' => [
            'api/platform/' => 'aero-platform',   // Platform public/admin APIs
            'api/admin/' => 'aero-platform',      // Platform admin APIs (deprecated, use api/platform/)
            'api/v1/' => 'aero-core',             // Core versioned APIs
        ],

        // Reserved web route prefixes by package
        'reserved_web_prefixes' => [
            'install' => ['aero-core', 'aero-platform'],
            'register' => 'aero-platform',
            'admin-setup' => 'aero-core',
            'onboarding' => 'aero-core',
        ],

        // Module route prefixes (each module should use its own prefix)
        'module_prefixes' => [
            'hrm' => 'aero-hrm',
            'crm' => 'aero-crm',
            'finance' => 'aero-finance',
            'project' => 'aero-project',
            'ims' => 'aero-ims',
            'pos' => 'aero-pos',
            'scm' => 'aero-scm',
            'quality' => 'aero-quality',
            'dms' => 'aero-dms',
            'compliance' => 'aero-compliance',
            'rfi' => 'aero-rfi',
        ],
    ],
];
