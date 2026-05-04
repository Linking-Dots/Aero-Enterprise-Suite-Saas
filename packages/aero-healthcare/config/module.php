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

    'code'         => 'healthcare',
    'scope'        => 'tenant',
    'name'         => 'Healthcare & EHR',
    'description'  => 'Electronic Health Records: patients, appointments, prescriptions, medical records, billing, lab integration, telemedicine, and HIPAA compliance.',
    'icon'         => 'HeartIcon',
    'route_prefix' => '/healthcare',
    'category'     => 'industry',
    'priority'     => 35,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('HEALTHCARE_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'dashboard'              => true,
        'patients'               => true,
        'appointments'           => true,
        'scheduling'             => true,
        'prescriptions'          => true,
        'medication_management'  => true,
        'medical_records'        => true,
        'clinical_notes'         => true,
        'vitals'                 => true,
        'lab_results'            => true,
        'imaging'                => true,
        'diagnoses'              => true,
        'procedures'             => true,
        'billing'                => true,
        'insurance'              => true,
        'claims'                 => true,
        'telemedicine'           => true,
        'video_consultations'    => true,
        'patient_portal'         => true,
        'referrals'              => true,
        'pharmacy_integration'   => true,
        'lab_integration'        => true,
        'hipaa_compliance'       => true,
        'audit_trail'            => true,
        'consent_management'     => true,
        'analytics'              => true,
        'integrations'           => true,
        'settings'               => true,
    ],

    'submodules' => [
        // Submodule structure to be defined
    ],
];
