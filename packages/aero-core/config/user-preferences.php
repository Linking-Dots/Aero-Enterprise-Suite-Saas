<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Preferences Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the User Preferences feature.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Preferences
    |--------------------------------------------------------------------------
    |
    | Default values for user preferences when not explicitly set.
    |
    */
    'defaults' => [
        // Notification preferences
        'notifications.email_enabled' => true,
        'notifications.in_app_enabled' => true,
        'notifications.push_enabled' => false,
        'notifications.digest_frequency' => 'immediate', // immediate, daily, weekly
        'notifications.dnd_enabled' => false,
        'notifications.dnd_start_time' => null,
        'notifications.dnd_end_time' => null,

        // Theme preferences
        'theme.theme' => 'system', // light, dark, system
        'theme.accent_color' => 'blue',
        'theme.density' => 'comfortable', // comfortable, compact
        'theme.border_radius' => 'medium', // none, small, medium, large

        // Locale preferences
        'locale.language' => config('app.locale', 'en'),
        'locale.timezone' => config('app.timezone', 'UTC'),
        'locale.date_format' => 'Y-m-d',
        'locale.time_format' => 'H:i',
        'locale.currency' => 'USD',
        'locale.number_format' => '1,234.56',

        // Accessibility preferences
        'accessibility.font_size' => 'medium', // small, medium, large, extra-large
        'accessibility.high_contrast' => false,
        'accessibility.reduced_motion' => false,
        'accessibility.screen_reader' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Preference Validations
    |--------------------------------------------------------------------------
    |
    | Validation rules for preference values.
    |
    */
    'validations' => [
        // Notification preferences
        'notifications.email_enabled' => ['type' => 'boolean'],
        'notifications.in_app_enabled' => ['type' => 'boolean'],
        'notifications.push_enabled' => ['type' => 'boolean'],
        'notifications.digest_frequency' => ['type' => 'string', 'allowed' => ['immediate', 'daily', 'weekly']],
        'notifications.dnd_enabled' => ['type' => 'boolean'],
        'notifications.dnd_start_time' => ['type' => 'string'], // Format: H:i
        'notifications.dnd_end_time' => ['type' => 'string'], // Format: H:i

        // Theme preferences
        'theme.theme' => ['type' => 'string', 'allowed' => ['light', 'dark', 'system']],
        'theme.accent_color' => ['type' => 'string', 'allowed' => ['blue', 'green', 'purple', 'orange', 'red', 'pink', 'gray']],
        'theme.density' => ['type' => 'string', 'allowed' => ['comfortable', 'compact']],
        'theme.border_radius' => ['type' => 'string', 'allowed' => ['none', 'small', 'medium', 'large']],

        // Locale preferences
        'locale.language' => ['type' => 'string'], // Should match available locales
        'locale.timezone' => ['type' => 'string'], // Should match PHP timezones
        'locale.date_format' => ['type' => 'string'],
        'locale.time_format' => ['type' => 'string'],
        'locale.currency' => ['type' => 'string'], // ISO 4217 currency codes
        'locale.number_format' => ['type' => 'string'],

        // Accessibility preferences
        'accessibility.font_size' => ['type' => 'string', 'allowed' => ['small', 'medium', 'large', 'extra-large']],
        'accessibility.high_contrast' => ['type' => 'boolean'],
        'accessibility.reduced_motion' => ['type' => 'boolean'],
        'accessibility.screen_reader' => ['type' => 'boolean'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache duration for user preferences.
    |
    */
    'cache_ttl' => 24 * 60, // 24 hours in minutes

    /*
    |--------------------------------------------------------------------------
    | Available Options
    |--------------------------------------------------------------------------
    |
    | Available options for select/multi-select preferences.
    |
    */
    'options' => [
        'digest_frequency' => [
            'immediate' => 'Immediate',
            'daily' => 'Daily Digest',
            'weekly' => 'Weekly Digest',
        ],
        'theme' => [
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
        'accent_color' => [
            'blue' => 'Blue',
            'green' => 'Green',
            'purple' => 'Purple',
            'orange' => 'Orange',
            'red' => 'Red',
            'pink' => 'Pink',
            'gray' => 'Gray',
        ],
        'density' => [
            'comfortable' => 'Comfortable',
            'compact' => 'Compact',
        ],
        'border_radius' => [
            'none' => 'None',
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
        ],
        'font_size' => [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'extra-large' => 'Extra Large',
        ],
    ],
];
