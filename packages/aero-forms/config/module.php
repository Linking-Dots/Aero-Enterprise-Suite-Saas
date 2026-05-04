<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forms Module — Dynamic Form Builder
    |--------------------------------------------------------------------------
    |
    | Cross-cutting dynamic form builder for all modules with templates,
    * validations, conditional logic, and PDF generation.
    |
    | Tenancy concern:
    *   - All form definitions are tenant-scoped
    *   - Form submissions are tenant-scoped
    *   - Cross-module form embedding (e.g., aero-hrm forms in aero-crm)
    *   - No cross-tenant form data access
    */

    'code'         => 'forms',
    'scope'        => 'tenant',
    'name'         => 'Dynamic Form Builder',
    'description'  => 'Cross-cutting form builder: dynamic forms, templates, validations, conditional logic, PDF generation, and cross-module form embedding.',
    'icon'         => 'DocumentTextIcon',
    'route_prefix' => '/forms',
    'category'     => 'infrastructure',
    'priority'     => 6,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('FORMS_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'form_builder'           => true,
        'form_templates'         => true,
        'field_types'            => true,
        'validations'            => true,
        'conditional_logic'      => true,
        'form_submissions'       => true,
        'pdf_generation'         => true,
        'form_permissions'       => true,
        'form_analytics'         => true,
        'cross_module_forms'     => true,
        'form_versioning'        => true,
        'form_cloning'           => true,
        'settings'                => true,
    ],

    'submodules' => [
        // Form builder, templates, submissions, analytics
    ],
];
