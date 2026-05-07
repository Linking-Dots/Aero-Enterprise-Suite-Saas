<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Time Tracking Module — Universal Time Logging
    |--------------------------------------------------------------------------
    |
    | Universal time tracking with time entries, timesheets, approval,
    * project tracking, billing integration, and reporting.
    *
    | Tenancy concern:
    *   - All time entries are tenant-scoped
    *   - Timesheets are tenant-scoped
    *   - Cross-module time tracking (e.g., aero-hrm, aero-project, aero-eam)
    *   - No cross-tenant time data access
    */

    'code' => 'time_tracking',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Universal Time Tracking',
    'description' => 'Universal time tracking: time entries, timesheets, approval, project tracking, billing integration, and cross-module time logging.',
    'icon' => 'ClockIcon',
    'route_prefix' => '/time-tracking',
    'category' => 'infrastructure',
    'priority' => 9,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('TIME_TRACKING_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [
        // Time entries, timesheets, approval, analytics
    ],
];
