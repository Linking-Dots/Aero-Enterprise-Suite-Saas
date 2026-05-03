<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the Aero Platform multi-tenant SaaS system.
    |
    */

    // Platform name
    'name' => env('PLATFORM_NAME', 'aeos365'),

    // Installation secret used by installation controller when reading package config
    'installation_secret_hash' => env('INSTALLATION_SECRET_HASH', '$2y$10$KPYdv5U6b/ViDNc6y6qe9.d05R7PMfCSWySMA9ACHInMEC/3E7yFi'),

    // Platform domain (without subdomain)
    'domain' => env('PLATFORM_DOMAIN', 'localhost'),

    // Central domain for tenant subdomains (used for subdomain URLs like tenant.domain.com)
    'central_domain' => env('PLATFORM_DOMAIN', 'localhost'),

    // Admin subdomain
    'admin_subdomain' => env('PLATFORM_ADMIN_SUBDOMAIN', 'admin'),

    // Trial configuration
    'trial' => [
        'enabled' => env('PLATFORM_TRIAL_ENABLED', true),
        'days' => env('PLATFORM_TRIAL_DAYS', 14),
    ],

    // Registration settings
    'registration' => [
        'enabled' => env('PLATFORM_REGISTRATION_ENABLED', true),
        'require_email_verification' => env('PLATFORM_REQUIRE_EMAIL_VERIFICATION', true),
        'require_phone_verification' => env('PLATFORM_REQUIRE_PHONE_VERIFICATION', false),
        'pending_lock_minutes' => env('PLATFORM_REGISTRATION_PENDING_LOCK_MINUTES', 30),
        'resume_reclaim_window_hours' => env('PLATFORM_REGISTRATION_RECLAIM_WINDOW_HOURS', 24),
    ],

    // Billing settings
    'billing' => [
        'provider' => env('BILLING_PROVIDER', 'stripe'), // stripe, sslcommerz
        'currency' => env('BILLING_CURRENCY', 'USD'),
    ],

    // Provisioning settings
    'provisioning' => [
        'async' => env('PLATFORM_ASYNC_PROVISIONING', true),
        'timeout' => env('PLATFORM_PROVISIONING_TIMEOUT', 300), // 5 minutes
    ],

    // Tenant provisioning rollback behavior
    // When true: Keep failed tenant records for debugging (useful in development)
    // When false: Delete failed tenants completely to allow re-registration (production)
    // Defaults to APP_DEBUG value if not explicitly set
    'preserve_failed_tenants' => env('PRESERVE_FAILED_TENANTS') ?? env('APP_DEBUG', false),

];
