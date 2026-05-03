<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all security-related configuration for the
    | aeos365. Settings here control password policies,
    | authentication requirements, session security, and more.
    |
    */

    'password_policy' => [
        /*
        |--------------------------------------------------------------------------
        | Password Requirements
        |--------------------------------------------------------------------------
        */
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'max_length' => env('PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),

        /*
        |--------------------------------------------------------------------------
        | Password Expiration & History
        |--------------------------------------------------------------------------
        */
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90), // 0 = never expires
        'history_count' => env('PASSWORD_HISTORY_COUNT', 24), // Remember last N passwords
        'force_reset_on_first_login' => env('PASSWORD_FORCE_RESET_FIRST_LOGIN', false),

        /*
        |--------------------------------------------------------------------------
        | Password Security Features
        |--------------------------------------------------------------------------
        */
        'prevent_common_passwords' => env('PASSWORD_PREVENT_COMMON', true),
        'prevent_username_in_password' => env('PASSWORD_PREVENT_USERNAME', true),
        'max_consecutive_chars' => env('PASSWORD_MAX_CONSECUTIVE_CHARS', 3),
        'check_breached_passwords' => env('PASSWORD_CHECK_BREACHED', true),
    ],

    'authentication' => [
        /*
        |--------------------------------------------------------------------------
        | Login Security
        |--------------------------------------------------------------------------
        */
        'max_login_attempts' => env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration_minutes' => env('AUTH_LOCKOUT_DURATION', 30),
        'progressive_delays' => env('AUTH_PROGRESSIVE_DELAYS', true),

        /*
        |--------------------------------------------------------------------------
        | Password Reset Security
        |--------------------------------------------------------------------------
        */
        'password_reset_code_length' => env('AUTH_RESET_CODE_LENGTH', 8),
        'password_reset_expiry_minutes' => env('AUTH_RESET_EXPIRY', 30),
        'password_reset_max_attempts' => env('AUTH_RESET_MAX_ATTEMPTS', 3),

        /*
        |--------------------------------------------------------------------------
        | Two-Factor Authentication
        |--------------------------------------------------------------------------
        */
        'require_2fa_for_admins' => env('AUTH_REQUIRE_2FA_ADMINS', false),
        'require_2fa_for_all_users' => env('AUTH_REQUIRE_2FA_ALL', false),
        'trusted_device_duration_days' => env('AUTH_TRUSTED_DEVICE_DAYS', 30),
        '2fa_backup_codes_count' => env('AUTH_2FA_BACKUP_CODES', 8),

        /*
        |--------------------------------------------------------------------------
        | Device Management
        |--------------------------------------------------------------------------
        */
        'enable_device_tracking' => env('AUTH_DEVICE_TRACKING', true),
        'max_devices_per_user' => env('AUTH_MAX_DEVICES', 10), // 0 = unlimited
        'device_verification_required' => env('AUTH_DEVICE_VERIFICATION', false),
    ],

    'session' => [
        /*
        |--------------------------------------------------------------------------
        | Session Security
        |--------------------------------------------------------------------------
        */
        'max_concurrent_sessions' => env('SESSION_MAX_CONCURRENT', 5), // 0 = unlimited
        'timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 120),
        'encrypt_payloads' => env('SESSION_ENCRYPT_PAYLOADS', true),
        'strict_ip_validation' => env('SESSION_STRICT_IP', false),

        /*
        |--------------------------------------------------------------------------
        | Session Cleanup
        |--------------------------------------------------------------------------
        */
        'cleanup_expired_sessions' => env('SESSION_CLEANUP_EXPIRED', true),
        'cleanup_interval_hours' => env('SESSION_CLEANUP_INTERVAL', 24),
        'keep_session_history_days' => env('SESSION_HISTORY_DAYS', 30),
    ],

    'rate_limiting' => [
        /*
        |--------------------------------------------------------------------------
        | API Rate Limiting
        |--------------------------------------------------------------------------
        */
        'api_requests_per_minute' => env('RATE_LIMIT_API_PER_MINUTE', 120),
        'sensitive_endpoints_per_minute' => env('RATE_LIMIT_SENSITIVE_PER_MINUTE', 30),
        'admin_endpoints_per_minute' => env('RATE_LIMIT_ADMIN_PER_MINUTE', 60),

        /*
        |--------------------------------------------------------------------------
        | Authentication Rate Limiting
        |--------------------------------------------------------------------------
        */
        'login_attempts_per_hour' => env('RATE_LIMIT_LOGIN_PER_HOUR', 10),
        'password_reset_attempts_per_hour' => env('RATE_LIMIT_RESET_PER_HOUR', 5),
        '2fa_attempts_per_hour' => env('RATE_LIMIT_2FA_PER_HOUR', 20),
    ],

    'threat_detection' => [
        /*
        |--------------------------------------------------------------------------
        | Advanced Threat Detection
        |--------------------------------------------------------------------------
        */
        'enable_impossible_travel_detection' => env('THREAT_IMPOSSIBLE_TRAVEL', true),
        'max_travel_speed_kmh' => env('THREAT_MAX_TRAVEL_SPEED', 900), // Commercial flight speed
        'enable_velocity_checks' => env('THREAT_VELOCITY_CHECKS', true),
        'max_logins_per_minute' => env('THREAT_MAX_LOGINS_PER_MINUTE', 5),

        /*
        |--------------------------------------------------------------------------
        | Risk Scoring
        |--------------------------------------------------------------------------
        */
        'enable_risk_scoring' => env('THREAT_RISK_SCORING', true),
        'high_risk_threshold' => env('THREAT_HIGH_RISK_THRESHOLD', 7),
        'critical_risk_threshold' => env('THREAT_CRITICAL_RISK_THRESHOLD', 10),

        /*
        |--------------------------------------------------------------------------
        | Automated Response
        |--------------------------------------------------------------------------
        */
        'auto_lock_on_high_risk' => env('THREAT_AUTO_LOCK_HIGH_RISK', false),
        'require_2fa_on_medium_risk' => env('THREAT_2FA_MEDIUM_RISK', true),
        'notify_admins_on_critical_risk' => env('THREAT_NOTIFY_CRITICAL', true),
    ],

    'audit_logging' => [
        /*
        |--------------------------------------------------------------------------
        | Security Event Logging
        |--------------------------------------------------------------------------
        */
        'log_all_authentication_events' => env('AUDIT_LOG_AUTH_EVENTS', true),
        'log_permission_changes' => env('AUDIT_LOG_PERMISSION_CHANGES', true),
        'log_sensitive_data_access' => env('AUDIT_LOG_SENSITIVE_ACCESS', true),
        'log_failed_authorization_attempts' => env('AUDIT_LOG_FAILED_AUTH', true),

        /*
        |--------------------------------------------------------------------------
        | Log Retention
        |--------------------------------------------------------------------------
        */
        'retain_audit_logs_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        'archive_old_logs' => env('AUDIT_ARCHIVE_OLD_LOGS', true),
        'compress_archived_logs' => env('AUDIT_COMPRESS_ARCHIVES', true),
    ],

    'encryption' => [
        /*
        |--------------------------------------------------------------------------
        | Data Encryption
        |--------------------------------------------------------------------------
        */
        'encrypt_sensitive_user_data' => env('ENCRYPT_SENSITIVE_DATA', true),
        'encrypt_session_data' => env('ENCRYPT_SESSION_DATA', true),
        'encrypt_audit_logs' => env('ENCRYPT_AUDIT_LOGS', false),

        /*
        |--------------------------------------------------------------------------
        | Key Management
        |--------------------------------------------------------------------------
        */
        'rotate_encryption_keys' => env('ENCRYPT_ROTATE_KEYS', false),
        'key_rotation_days' => env('ENCRYPT_KEY_ROTATION_DAYS', 90),
    ],

    'compliance' => [
        /*
        |--------------------------------------------------------------------------
        | Compliance Features
        |--------------------------------------------------------------------------
        */
        'gdpr_compliance_mode' => env('COMPLIANCE_GDPR', false),
        'pci_compliance_mode' => env('COMPLIANCE_PCI', false),
        'sox_compliance_mode' => env('COMPLIANCE_SOX', false),
        'iso27001_compliance_mode' => env('COMPLIANCE_ISO27001', false),

        /*
        |--------------------------------------------------------------------------
        | Data Protection
        |--------------------------------------------------------------------------
        */
        'data_retention_days' => env('COMPLIANCE_DATA_RETENTION_DAYS', 2555), // 7 years default
        'right_to_be_forgotten' => env('COMPLIANCE_RIGHT_TO_FORGET', false),
        'data_portability' => env('COMPLIANCE_DATA_PORTABILITY', false),
    ],

    'ip_geolocation' => [
        /*
        |--------------------------------------------------------------------------
        | IP Geolocation Service
        |--------------------------------------------------------------------------
        */
        'enabled' => env('GEOLOCATION_ENABLED', false),
        'provider' => env('GEOLOCATION_PROVIDER', 'maxmind'), // maxmind, ipapi, geoip2
        'database_path' => env('GEOLOCATION_DB_PATH', storage_path('app/geoip')),
        'cache_results' => env('GEOLOCATION_CACHE', true),
        'cache_ttl_hours' => env('GEOLOCATION_CACHE_TTL', 24),
    ],

    'notifications' => [
        /*
        |--------------------------------------------------------------------------
        | Security Notifications
        |--------------------------------------------------------------------------
        */
        'notify_on_new_device' => env('SECURITY_NOTIFY_NEW_DEVICE', true),
        'notify_on_suspicious_login' => env('SECURITY_NOTIFY_SUSPICIOUS_LOGIN', true),
        'notify_on_password_change' => env('SECURITY_NOTIFY_PASSWORD_CHANGE', true),
        'notify_on_2fa_disable' => env('SECURITY_NOTIFY_2FA_DISABLE', true),

        /*
        |--------------------------------------------------------------------------
        | Admin Notifications
        |--------------------------------------------------------------------------
        */
        'notify_admins_on_multiple_failed_logins' => env('SECURITY_NOTIFY_ADMIN_FAILED_LOGINS', true),
        'failed_login_threshold_for_admin_notification' => env('SECURITY_FAILED_LOGIN_ADMIN_THRESHOLD', 10),
    ],
];
