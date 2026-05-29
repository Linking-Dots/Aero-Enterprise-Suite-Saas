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

    'code' => 'custom_fields',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Custom Fields System',
    'description' => 'Cross-cutting custom fields: field definitions, field types, field groups, validation rules, cross-module fields, and field value storage.',
    'icon' => 'TagIcon',
    'route_prefix' => '/custom-fields',
    'category' => 'infrastructure',
    'priority' => 7,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('CUSTOM_FIELDS_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    /*
    |--------------------------------------------------------------------------
    | Submodules (Plan 10 Task 1 — declared surface aligned with implementation)
    |--------------------------------------------------------------------------
    |
    | Phase 1 audit flagged this package as "75% of declared surface missing".
    | Investigation showed three of the four declared components had no
    | controller backing them: field_types, field_groups, validation_rules.
    |
    | Rather than scaffold three placeholder CRUD surfaces that don't yet have
    | a clear product use case, this config is now trimmed to reflect what
    | ACTUALLY ships (definitions, backed by CustomFieldController). The
    | roadmap items remain captured in the 'roadmap' key below — when an
    | operator wants to add custom-field groups or reusable validation rules,
    | the migrations + controllers can be built then. Until then HRMAC has no
    | actions to gate that no route exercises.
    |
    | The CustomField model already encodes field_type as a column with a
    | hardcoded supported-types list (see CustomField::getFieldTypeLabelAttribute)
    | so a separate 'field_types' admin surface adds no value today.
    */
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
            ],
        ],
    ],

    /*
    | Roadmap items intentionally NOT declared as submodules until they ship
    | with controllers + migrations. Tracked here so per-package gap audits
    | recognize them as "deferred by design" not "declared and broken".
    */
    'roadmap' => [
        'field_types' => 'Operator-managed custom field type registry. Currently the supported types are hardcoded in CustomField::getFieldTypeLabelAttribute() — adequate for v1.',
        'field_groups' => 'Logical grouping of fields by entity / use case. Wait until customers ask.',
        'validation_rules' => 'Reusable named validation rules. Today validation_rules is a JSON column on CustomField — adequate for v1.',
    ],
];
