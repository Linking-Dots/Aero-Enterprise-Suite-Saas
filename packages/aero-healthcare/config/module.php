<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Healthcare Module — Electronic Health Records (EHR)
    |--------------------------------------------------------------------------
    |
    | Manages patients, appointments, prescriptions, medical records,
    | billing, lab integration, telemedicine, and HIPAA compliance.
    |
    | Tenancy concern:
    |   - All patient data is strictly tenant-scoped (HIPAA requirement)
    |   - No cross-tenant patient data access
    |   - Audit trail for all PHI access
    */

    'code' => 'healthcare',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Healthcare & EHR',
    'description' => 'Electronic Health Records: patients, appointments, prescriptions, medical records, billing, lab integration, telemedicine, and HIPAA compliance.',
    'icon' => 'HeartIcon',
    'route_prefix' => '/healthcare',
    'category' => 'industry',
    'priority' => 35,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('HEALTHCARE_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [
        // Submodule structure to be defined
    ],
];
