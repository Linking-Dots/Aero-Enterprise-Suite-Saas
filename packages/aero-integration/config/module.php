<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integration Package — Integration Infrastructure
    |--------------------------------------------------------------------------
    | Scope: BOTH platform + tenant (infrastructure layer, not a UI module)
    |
    | aero-integration is NOT a user-facing module. It is the shared integration
    | infrastructure used by all modules for API connectors, webhooks, and
    | third-party integrations. It does not appear in the module marketplace.
    |
    | Tenancy concern:
    *   - API credentials are tenant-scoped (encrypted)
    *   - Webhook endpoints are tenant-scoped
    *   - Integration logs are tenant-scoped
    *   - Platform integrations (landlord) use central DB
    *   - Tenant integrations use tenant DB
    */

    'code' => 'integration',
    'schema_version' => '2.0',
    'scope' => 'infrastructure',
    'name' => 'Integration Infrastructure',
    'description' => 'Shared integration layer: API connectors, webhooks, OAuth, data pipelines, sync jobs, and integration logs for both tenant and platform contexts.',
    'icon' => 'LinkIcon',
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

    'features' => [
        'api_connectors' => true,
        'webhooks' => true,
        'oauth' => true,
        'api_keys' => true,
        'data_pipelines' => true,
        'sync_jobs' => true,
        'integration_logs' => true,
        'rate_limiting' => true,
        'retry_logic' => true,
        'error_handling' => true,
        'webhook_signing' => true,
        'bulk_import_export' => true,
    ],

    'tenancy' => [
        'tenant_aware' => true,
        'uses_tenant_db' => true,
        'central_tables' => [
            'platform_integrations',
            'platform_integration_logs',
        ],
        'tenant_tables' => [
            'integrations',
            'api_keys',
            'webhooks',
            'integration_logs',
        ],
    ],
];
