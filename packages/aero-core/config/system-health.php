<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Health Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the System Health monitoring feature.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Threshold values that trigger health alerts when exceeded.
    |
    */
    'alert_thresholds' => [
        'cpu_usage' => 80, // Percentage
        'memory_usage' => 85, // Percentage
        'disk_usage' => 90, // Percentage
        'queue_size' => 1000, // Number of pending jobs
        'failed_jobs' => 100, // Number of failed jobs
        'response_time' => 2000, // Milliseconds
        'error_rate' => 5, // Percentage
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Intervals
    |--------------------------------------------------------------------------
    |
    | How often to run health checks and log metrics (in minutes).
    |
    */
    'check_interval' => 5, // Run health checks every 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Log Metrics
    |--------------------------------------------------------------------------
    |
    | Whether to log health metrics to the database for historical analysis.
    |
    */
    'log_metrics' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Retention Period
    |--------------------------------------------------------------------------
    |
    | How long to keep health logs in the database (in days).
    |
    */
    'log_retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | External Services to Monitor
    |--------------------------------------------------------------------------
    |
    | List of external services to monitor for health status.
    |
    */
    'services' => [
        // Add external services here
        // [
        //     'name' => 'Payment Gateway',
        //     'url' => 'https://api.payment-gateway.com/health',
        //     'method' => 'GET',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for health alert notifications.
    |
    */
    'notifications' => [
        'enabled' => false,
        'channels' => ['email', 'slack'], // Available: email, slack, database
        'recipients' => [
            'email' => ['admin@example.com'],
            'slack' => '#admin-alerts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time Updates
    |--------------------------------------------------------------------------
    |
    | Enable real-time health data updates via polling or websockets.
    |
    */
    'realtime_updates' => [
        'enabled' => true,
        'method' => 'polling', // 'polling' or 'websocket'
        'interval' => 30, // Seconds between updates
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for performance metrics collection.
    |
    */
    'performance_metrics' => [
        'enabled' => true,
        'track_response_time' => true,
        'track_error_rate' => true,
        'track_requests_per_minute' => true,
        'track_active_connections' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Health Check
    |--------------------------------------------------------------------------
    |
    | Configuration for cache health monitoring.
    |
    */
    'cache' => [
        'enabled' => true,
        'test_key' => 'health_check',
        'test_value' => 'ok',
        'test_ttl' => 60, // Seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Health Check
    |--------------------------------------------------------------------------
    |
    | Configuration for database health monitoring.
    |
    */
    'database' => [
        'enabled' => true,
        'check_slow_queries' => true,
        'slow_query_threshold' => 1000, // Milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Health Check
    |--------------------------------------------------------------------------
    |
    | Configuration for queue health monitoring.
    |
    */
    'queue' => [
        'enabled' => true,
        'queues_to_monitor' => ['default', 'emails', 'notifications', 'exports'],
    ],
];
