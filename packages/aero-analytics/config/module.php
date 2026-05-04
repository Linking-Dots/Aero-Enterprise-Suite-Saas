<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics & Business Intelligence Module
    |--------------------------------------------------------------------------
    | Scope: tenant
    | Provides cross-module BI, executive dashboards, ad-hoc reporting,
    | data warehouse read layer, KPI scorecards, and predictive analytics.
    | All data access is strictly tenant-scoped — no cross-tenant queries.
    |
    | NOTE: This package owns the BI/reporting surface only.
    | Operational dashboards live in their respective domain packages.
    */

    'code'         => 'analytics',
    'scope'        => 'tenant',
    'name'         => 'Analytics & Business Intelligence',
    'description'  => 'Cross-module BI platform: executive dashboards, KPI scorecards, ad-hoc reporting, data warehouse read layer, embedded charts, and predictive analytics.',
    'icon'         => 'ChartBarIcon',
    'route_prefix' => '/analytics',
    'category'     => 'intelligence',
    'priority'     => 30,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('ANALYTICS_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'executive_dashboard'        => true,  // CEO/CFO/COO cross-module KPI view
        'kpi_scorecards'             => true,  // configurable KPI targets & tracking
        'custom_dashboards'          => true,  // drag-and-drop widget builder (tenant)
        'ad_hoc_reporting'           => true,  // visual query builder, no SQL
        'scheduled_reports'          => true,  // email/export on schedule
        'data_export'                => true,  // CSV, Excel, PDF exports
        'embedded_charts'            => true,  // chart components shared with modules
        'cohort_analysis'            => true,  // retention, churn cohorts
        'predictive_analytics'       => true,  // ML-backed forecasting (plan-gated)
        'data_warehouse_connector'   => true,  // read-only Snowflake/BigQuery/Redshift
        'audit_trail_reports'        => true,  // compliance-grade audit exports
        'revenue_analytics'          => true,  // cross-finance/CRM revenue view
        'workforce_analytics'        => true,  // cross-HRM headcount, attrition
        'asset_analytics'            => true,  // cross-EAM MTBF, OEE, downtime
        'supply_chain_analytics'     => true,  // cross-SCM fill rate, lead times
    ],

    'submodules' => [

        [
            'code'     => 'dashboards',
            'name'     => 'Dashboards',
            'icon'     => 'Squares2X2Icon',
            'route'    => '/analytics/dashboards',
            'priority' => 1,
            'components' => [
                ['code' => 'executive-dashboard',  'name' => 'Executive Dashboard',  'route' => '/analytics/dashboards/executive'],
                ['code' => 'custom-dashboards',    'name' => 'My Dashboards',        'route' => '/analytics/dashboards/custom'],
                ['code' => 'kpi-scorecards',       'name' => 'KPI Scorecards',       'route' => '/analytics/dashboards/kpi'],
            ],
        ],

        [
            'code'     => 'reports',
            'name'     => 'Reports',
            'icon'     => 'DocumentChartBarIcon',
            'route'    => '/analytics/reports',
            'priority' => 2,
            'components' => [
                ['code' => 'ad-hoc-builder',     'name' => 'Report Builder',        'route' => '/analytics/reports/builder'],
                ['code' => 'report-library',     'name' => 'Report Library',        'route' => '/analytics/reports/library'],
                ['code' => 'scheduled-reports',  'name' => 'Scheduled Reports',     'route' => '/analytics/reports/scheduled'],
            ],
        ],

        [
            'code'     => 'domain-analytics',
            'name'     => 'Domain Analytics',
            'icon'     => 'PresentationChartLineIcon',
            'route'    => '/analytics/domain',
            'priority' => 3,
            'components' => [
                ['code' => 'revenue-analytics',        'name' => 'Revenue Analytics',        'route' => '/analytics/domain/revenue'],
                ['code' => 'workforce-analytics',      'name' => 'Workforce Analytics',      'route' => '/analytics/domain/workforce'],
                ['code' => 'asset-analytics',          'name' => 'Asset Analytics',          'route' => '/analytics/domain/assets'],
                ['code' => 'supply-chain-analytics',   'name' => 'Supply Chain Analytics',   'route' => '/analytics/domain/supply-chain'],
                ['code' => 'cohort-analysis',          'name' => 'Cohort Analysis',          'route' => '/analytics/domain/cohorts'],
            ],
        ],

        [
            'code'     => 'predictive',
            'name'     => 'Predictive Analytics',
            'icon'     => 'SparklesIcon',
            'route'    => '/analytics/predictive',
            'priority' => 4,
            'min_plan' => 'enterprise',
            'components' => [
                ['code' => 'forecasting',        'name' => 'Forecasting Models',   'route' => '/analytics/predictive/forecasting'],
                ['code' => 'anomaly-detection',  'name' => 'Anomaly Detection',    'route' => '/analytics/predictive/anomalies'],
            ],
        ],

        [
            'code'     => 'data-connectors',
            'name'     => 'Data Connectors',
            'icon'     => 'CircleStackIcon',
            'route'    => '/analytics/connectors',
            'priority' => 5,
            'min_plan' => 'enterprise',
            'components' => [
                ['code' => 'warehouse-connector', 'name' => 'Data Warehouse',  'route' => '/analytics/connectors/warehouse'],
                ['code' => 'data-export',         'name' => 'Data Exports',    'route' => '/analytics/connectors/exports'],
            ],
        ],

    ],

    'permissions' => [
        'analytics.view'              => 'View analytics dashboards',
        'analytics.reports.run'       => 'Run ad-hoc reports',
        'analytics.reports.schedule'  => 'Schedule reports',
        'analytics.dashboards.create' => 'Create custom dashboards',
        'analytics.connectors.manage' => 'Manage data connectors',
        'analytics.export'            => 'Export analytics data',
        'analytics.predictive.view'   => 'View predictive analytics',
    ],

    'tenancy' => [
        // All analytics queries MUST be scoped to the current tenant DB.
        // No cross-tenant queries are permitted at any layer.
        'tenant_aware'      => true,
        'uses_tenant_db'    => true,   // reads from tenant_{id} database
        'central_tables'    => [],     // no reads from central DB
        'cached_per_tenant' => true,   // cache keys prefixed with tenant_id
        'row_level_security'=> false,  // tenant isolation via separate DB, not RLS
    ],
];
