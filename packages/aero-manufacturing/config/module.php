<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manufacturing Module — Production & MES
    |--------------------------------------------------------------------------
    |
    | Manages production planning, BOM, routing, MES, shop floor,
    | quality control, maintenance integration, and OEE.
    |
    | Tenancy concern:
    |   - All production data is tenant-scoped
    |   - No cross-tenant production data access
    |   - Cross-package integration: aero-eam (maintenance), aero-ims (inventory),
    *     aero-scm (procurement), aero-quality (QC), aero-hrm (labor)
    */

    'code' => 'manufacturing',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Manufacturing & Production',
    'description' => 'Manufacturing execution: production planning, BOM, routing, MES, shop floor, quality control, maintenance integration, and OEE.',
    'icon' => 'CogIcon',
    'route_prefix' => '/manufacturing',
    'category' => 'industry',
    'priority' => 34,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('MANUFACTURING_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [
        // Submodule structure to be defined
    ],
];
