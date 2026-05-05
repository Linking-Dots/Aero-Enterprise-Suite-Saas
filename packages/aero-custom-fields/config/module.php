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
        [
            'code' => 'custom_fields',
            'name' => 'Custom Fields',
            'description' => 'Create and manage custom field definitions',
            'icon' => 'TagIcon',
            'route' => '/custom-fields',
            'components' => [
                [
                    'code' => 'definitions',
                    'name' => 'Field Definitions',
                    'type' => 'page',
                    'route' => '/custom-fields',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Fields'],
                        ['code' => 'create', 'name' => 'Create Field'],
                        ['code' => 'update', 'name' => 'Update Field'],
                        ['code' => 'delete', 'name' => 'Delete Field'],
                    ],
                ],
                [
                    'code' => 'field_types',
                    'name' => 'Field Types',
                    'type' => 'page',
                    'route' => '/custom-fields/types',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Field Types'],
                        ['code' => 'create', 'name' => 'Create Field Type'],
                        ['code' => 'update', 'name' => 'Update Field Type'],
                        ['code' => 'delete', 'name' => 'Delete Field Type'],
                    ],
                ],
                [
                    'code' => 'field_groups',
                    'name' => 'Field Groups',
                    'type' => 'page',
                    'route' => '/custom-fields/groups',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Field Groups'],
                        ['code' => 'create', 'name' => 'Create Field Group'],
                        ['code' => 'update', 'name' => 'Update Field Group'],
                        ['code' => 'delete', 'name' => 'Delete Field Group'],
                    ],
                ],
                [
                    'code' => 'validation_rules',
                    'name' => 'Validation Rules',
                    'type' => 'page',
                    'route' => '/custom-fields/validation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Validation Rules'],
                        ['code' => 'create', 'name' => 'Create Validation Rule'],
                        ['code' => 'update', 'name' => 'Update Validation Rule'],
                        ['code' => 'delete', 'name' => 'Delete Validation Rule'],
                    ],
                ],
            ],
        ],
    ],
];
