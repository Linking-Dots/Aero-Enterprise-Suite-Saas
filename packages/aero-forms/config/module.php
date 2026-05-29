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

    'code' => 'forms',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Dynamic Form Builder',
    'description' => 'Cross-cutting form builder: dynamic forms, templates, validations, conditional logic, PDF generation, and cross-module form embedding.',
    'icon' => 'DocumentTextIcon',
    'route_prefix' => '/forms',
    'category' => 'infrastructure',
    'priority' => 6,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('FORMS_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    /*
    |--------------------------------------------------------------------------
    | Submodules (Plan 11 — declared surface aligned with implementation)
    |--------------------------------------------------------------------------
    |
    | Phase 1 audit declared 3 components (forms, submissions, templates)
    | but only 2 controllers ship (FormController, FormSubmissionController).
    | Module description also mentioned "PDF generation" — no PDF service exists.
    |
    | Trimmed to the implemented surface; roadmap items captured below.
    */
    'submodules' => [
        [
            'code' => 'forms',
            'name' => 'Form Builder',
            'description' => 'Create and manage dynamic forms',
            'icon' => 'DocumentTextIcon',
            'route' => '/forms',
            'components' => [
                [
                    'code' => 'forms',
                    'name' => 'Forms',
                    'type' => 'page',
                    'route' => '/forms',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Forms'],
                        ['code' => 'create', 'name' => 'Create Form'],
                        ['code' => 'update', 'name' => 'Update Form'],
                        ['code' => 'delete', 'name' => 'Delete Form'],
                        ['code' => 'publish', 'name' => 'Publish Form'],
                        ['code' => 'unpublish', 'name' => 'Unpublish Form'],
                    ],
                ],
                [
                    'code' => 'submissions',
                    'name' => 'Form Submissions',
                    'type' => 'page',
                    'route' => '/forms/submissions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submissions'],
                        ['code' => 'update', 'name' => 'Update Status'],
                        ['code' => 'delete', 'name' => 'Delete Submission'],
                        ['code' => 'export', 'name' => 'Export Submissions'],
                    ],
                ],
            ],
        ],
    ],

    /*
    | Roadmap items intentionally NOT declared until they ship.
    */
    'roadmap' => [
        'templates' => 'Reusable form blueprints — FormTemplate model + controller + migration to be built. Until then operators clone an existing form.',
        'pdf_generation' => 'Render a submission to PDF (dompdf/mpdf). FormPdfService scaffold deferred — operators export submissions as CSV today.',
        'conditional_logic' => 'Show/hide fields based on other-field values. The form schema accepts a "when" key today but FormBuilderService does not yet resolve it.',
    ],
];
