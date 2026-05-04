<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Fields Module Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-cutting custom fields system for all modules with field types,
    * field groups, validation rules, and cross-module field support.
    |
    | Tenancy concern:
    *   - All custom field definitions are tenant-scoped
    *   - Custom field values are tenant-scoped
    *   - Cross-module field definitions (e.g., aero-hrm fields in aero-crm)
    *   - No cross-tenant custom field data access
    */

    'code'         => 'custom_fields',
    'scope'        => 'tenant',
    'name'         => 'Custom Fields System',
    'description'  => 'Cross-cutting custom fields: field definitions, field types, field groups, validation rules, cross-module fields, and field value storage.',
    'icon'         => 'TagIcon',
    'route_prefix' => '/custom-fields',
    'category'     => 'infrastructure',
    'priority'     => 7,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('CUSTOM_FIELDS_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'field_definitions'       => true,
        'field_types'             => true,
        'field_groups'            => true,
        'validation_rules'        => true,
        'cross_module_fields'     => true,
        'field_permissions'       => true,
        'field_analytics'         => true,
        'bulk_import_export'      => true,
        'field_cloning'           => true,
        'field_dependencies'      => true,
        'conditional_visibility'  => true,
        'settings'                => true,
    ],

    'submodules' => [
        // Field definitions, field groups, field types, analytics
    ],
];
