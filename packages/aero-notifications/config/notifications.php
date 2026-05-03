<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Aero Notifications Engine Configuration
    |--------------------------------------------------------------------------
    */

    // Default channels enabled globally (per-notification channel selection still respected)
    'channels' => [
        'mail' => ['enabled' => true, 'queue' => true],
        'sms' => ['enabled' => true, 'queue' => true],
        'push' => ['enabled' => true, 'queue' => true],
        'broadcast' => ['enabled' => true, 'queue' => false],
        'database' => ['enabled' => true, 'queue' => false],
    ],

    // Retry settings applied uniformly across all channels
    'retry' => [
        'max_attempts' => 3,
        'backoff_minutes' => [1, 5, 15],
        'dead_letter' => [
            'enabled' => true,
            'log_channel' => 'notifications',
        ],
    ],

    // Quiet hours default (users can override via preferences)
    'quiet_hours' => [
        'enabled' => false,
        'start' => '22:00',
        'end' => '08:00',
        'timezone' => config('app.timezone', 'UTC'),
    ],

    // Digest settings
    'digest' => [
        'enabled' => false,
        'frequencies' => ['immediate', 'daily', 'weekly'],
        'default' => 'immediate',
    ],

    // Branding fallback (packages can inject tenant/platform branding via callbacks)
    'branding' => [
        'company_name' => config('app.name', 'aeos365'),
        'logo_url' => null,
        'primary_color' => '#3B82F6',
        'support_email' => 'support@aeroenterprise.com',
        'support_phone' => '+1-800-AERO-365',
    ],

    // Feature flags
    'features' => [
        'broadcast_channel' => true,
        'sms_failover' => true,
        'mail_template_engine' => true,
    ],
];
