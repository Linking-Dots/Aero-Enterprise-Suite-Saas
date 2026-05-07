<?php

// packages/aero-core/config/license.php

return [

    /*
    |--------------------------------------------------------------------------
    | License Server (LicenseService)
    |--------------------------------------------------------------------------
    |
    | Settings used by LicenseService for online activation and validation.
    |
    */

    'server_url'           => env('LICENSE_SERVER_URL', 'https://licenses.aerosuite.com'),
    'check_ttl_seconds'    => env('LICENSE_CHECK_TTL', 86400),
    'grace_period_seconds' => env('LICENSE_GRACE_PERIOD', 259200),
    'checksum_salt'        => env('LICENSE_CHECKSUM_SALT', 'aero-license-salt'),
    'bypass'               => env('LICENSE_BYPASS', false),

    /*
    |--------------------------------------------------------------------------
    | License Providers
    |--------------------------------------------------------------------------
    |
    | Configure multiple license providers for marketplace-agnostic validation.
    | The system automatically detects the provider from the license key format.
    |
    */

    'providers' => [

        /*
        |--------------------------------------------------------------------------
        | Aero Platform (Primary Provider)
        |--------------------------------------------------------------------------
        |
        | Licenses purchased through the official Aero Platform.
        | Format: AP-{PRODUCT}-{MODULE}-{KEY}-{CHECKSUM}
        |
        */
        'aero' => [
            'enabled' => env('LICENSE_PROVIDER_AERO_ENABLED', true),
            // Hardcoded to official Aero Platform API - do not change
            'api_url' => 'https://aeos365.com/api',
            'timeout' => env('LICENSE_PROVIDER_AERO_TIMEOUT', 10),
            'retry_attempts' => env('LICENSE_PROVIDER_AERO_RETRY', 3),
        ],

        /*
        |--------------------------------------------------------------------------
        | CodeCanyon/Envato
        |--------------------------------------------------------------------------
        |
        | Licenses purchased through CodeCanyon marketplace.
        | Format: CC-{PRODUCT}-{MODULE}-{PURCHASE_CODE}
        |
        */
        'codecanyon' => [
            'enabled' => env('LICENSE_PROVIDER_CODECANYON_ENABLED', true),
            'api_url' => env('LICENSE_PROVIDER_CODECANYON_API_URL', 'https://api.envato.com'),
            'personal_token' => env('LICENSE_PROVIDER_CODECANYON_TOKEN'),
            'timeout' => env('LICENSE_PROVIDER_CODECANYON_TIMEOUT', 10),
        ],

        /*
        |--------------------------------------------------------------------------
        | Enterprise Licenses (Offline Validation)
        |--------------------------------------------------------------------------
        |
        | Enterprise licenses with offline cryptographic validation.
        | Format: EP-{PRODUCT}-{MODULE}-{KEY}-{CHECKSUM}
        |
        */
        'enterprise' => [
            'enabled' => env('LICENSE_PROVIDER_ENTERPRISE_ENABLED', true),
            'secret_key' => env('LICENSE_PROVIDER_ENTERPRISE_SECRET', 'change-this-in-production'),
            'offline_mode' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | License Verification Settings
    |--------------------------------------------------------------------------
    |
    | Control how frequently licenses are verified and grace period behavior.
    |
    */

    'verification' => [

        // How often to verify license with remote server (in seconds)
        'frequency' => env('LICENSE_VERIFICATION_FREQUENCY', 86400), // 24 hours

        // Grace period when verification fails (in seconds)
        'grace_period' => env('LICENSE_GRACE_PERIOD', 604800), // 7 days

        // Enable offline mode (use cached validation)
        'offline_mode' => env('LICENSE_OFFLINE_MODE', false),

        // Strict mode - fail immediately if verification fails
        'strict_mode' => env('LICENSE_STRICT_MODE', false),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | License validation results are cached to reduce API calls.
    |
    */

    'cache_duration' => env('LICENSE_CACHE_DURATION', 86400), // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Product Module Mapping
    |--------------------------------------------------------------------------
    |
    | Define which modules are included in each product variant.
    |
    */

    'products' => [
        'AES-HRM' => [
            'name' => 'Aero HRM',
            'included_modules' => ['core', 'hrm'],
            'description' => 'Human Resource Management System',
        ],
        'AES-CRM' => [
            'name' => 'Aero CRM',
            'included_modules' => ['core', 'crm'],
            'description' => 'Customer Relationship Management',
        ],
        'AES-RFI' => [
            'name' => 'Aero RFI',
            'included_modules' => ['core', 'rfi'],
            'description' => 'Request for Information Management',
        ],
        'AES-FIN' => [
            'name' => 'Aero Finance',
            'included_modules' => ['core', 'finance'],
            'description' => 'Financial Management System',
        ],
        'AES-PRJ' => [
            'name' => 'Aero Project',
            'included_modules' => ['core', 'project'],
            'description' => 'Project Management System',
        ],
        'AES-FULL' => [
            'name' => 'aeos365',
            'included_modules' => ['all'],
            'description' => 'Complete ERP Suite with all modules',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Codes
    |--------------------------------------------------------------------------
    |
    | Standard module codes for license validation.
    |
    */

    'modules' => [
        'core' => 'Core Module',
        'hrm' => 'Human Resource Management',
        'crm' => 'Customer Relationship Management',
        'rfi' => 'Request for Information',
        'finance' => 'Finance & Accounting',
        'project' => 'Project Management',
        'ims' => 'Inventory Management',
        'pos' => 'Point of Sale',
        'scm' => 'Supply Chain Management',
        'quality' => 'Quality Management',
        'dms' => 'Document Management',
        'compliance' => 'Compliance Management',
    ],

    /*
    |--------------------------------------------------------------------------
    | License Types
    |--------------------------------------------------------------------------
    |
    | Different license types with their permissions.
    |
    */

    'license_types' => [
        'regular' => [
            'name' => 'Regular License',
            'support_months' => 6,
            'updates_months' => 12,
        ],
        'extended' => [
            'name' => 'Extended License',
            'support_months' => 12,
            'updates_months' => 24,
        ],
        'enterprise' => [
            'name' => 'Enterprise License',
            'support_months' => null, // Unlimited
            'updates_months' => null, // Unlimited
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions on License Expiry
    |--------------------------------------------------------------------------
    |
    | Define what happens when license expires or validation fails.
    |
    */

    'expiry_actions' => [

        // Redirect to license page on expiry
        'redirect_to_license' => env('LICENSE_EXPIRY_REDIRECT', true),

        // Allow read-only access during grace period
        'read_only_mode' => env('LICENSE_GRACE_READ_ONLY', true),

        // Show banner notification before expiry (days)
        'warning_days' => env('LICENSE_WARNING_DAYS', 30),

        // Disable specific features on expiry
        'disable_features' => [
            'api_access' => false,
            'exports' => false,
            'imports' => false,
            'module_installation' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notifications
    |--------------------------------------------------------------------------
    |
    | Send license events to external webhooks (optional).
    |
    */

    'webhooks' => [
        'enabled' => env('LICENSE_WEBHOOK_ENABLED', false),
        'url' => env('LICENSE_WEBHOOK_URL'),
        'secret' => env('LICENSE_WEBHOOK_SECRET'),
        'events' => [
            'license.validated',
            'license.expired',
            'license.renewed',
            'module.installed',
        ],
    ],

];
