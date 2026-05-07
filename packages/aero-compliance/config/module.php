<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HSE & Regulatory Compliance Module
    |--------------------------------------------------------------------------
    | Full GRC + HSE scope: Governance, Risk, Compliance, Health, Safety,
    | Environmental. EAM-aligned: asset permits, environmental discharge,
    | contractor compliance, regulatory registers, and audit trails.
    */

    'code' => 'compliance',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'HSE, GRC & Compliance',
    'description' => 'Integrated HSE, Governance, Risk & Compliance: permits, environmental, health & safety, regulatory tracking, audits, policies, ESG, and asset compliance.',
    'version' => '3.0.0',
    'category' => 'business',
    'icon' => 'ShieldCheckIcon',
    'priority' => 17,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('COMPLIANCE_MODULE_ENABLED', true),
    'min_plan' => 'enterprise',
    'minimum_plan' => 'enterprise',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/compliance',

    'features' => [
        'dashboard' => true,
        'hse_management' => true,
        'permit_to_work' => true, // EAM
        'incident_management' => true,
        'toolbox_talks' => true,
        'environmental' => true, // EAM
        'emissions_tracking' => true,
        'waste_management' => true,
        'workforce_certifications' => true,
        'contractor_compliance' => true, // EAM
        'regulatory_register' => true,
        'permits_licenses' => true, // EAM asset permits
        'audits' => true,
        'policies_procedures' => true,
        'risk_management' => true,
        'risk_register' => true,
        'controls_library' => true,
        'soc_compliance' => true,
        'iso_compliance' => true,
        'gdpr_privacy' => true,
        'whistleblower' => true,
        'ethics_hotline' => true,
        'esg_sustainability' => true,
        'asset_compliance' => true, // EAM
        'training_awareness' => true,
        'reporting' => true,
        'analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        // ==================== 0. DASHBOARD ====================
        [
            'code' => 'compliance-dashboard',
            'name' => 'Compliance Dashboard',
            'description' => 'HSE, compliance status, incident trends, audit findings',
            'icon' => 'ChartPieIcon',
            'route' => '/compliance',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                ['code' => 'compliance-overview', 'name' => 'Compliance Overview', 'type' => 'page', 'route' => '/compliance',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
                ['code' => 'risk-heatmap', 'name' => 'Risk Heatmap', 'type' => 'page', 'route' => '/compliance/risk-heatmap',
                    'actions' => [['code' => 'view', 'name' => 'View Heatmap']]],
            ],
        ],

        // ==================== 1. HSE MANAGEMENT ====================
        [
            'code' => 'hse-management',
            'name' => 'Site Safety (HSE)',
            'description' => 'Incidents, near-misses, inspections, drills, toolbox talks',
            'icon' => 'LifebuoyIcon',
            'route' => '/compliance/hse',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                ['code' => 'incident-dashboard', 'name' => 'Incident Command', 'type' => 'dashboard', 'route' => '/compliance/hse/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Incident Dashboard']]],
                ['code' => 'incidents', 'name' => 'Incidents (LTI, FAC, Near-Miss)', 'type' => 'page', 'route' => '/compliance/hse/incidents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Incidents'],
                        ['code' => 'report', 'name' => 'Report Incident'],
                        ['code' => 'investigate', 'name' => 'Investigate Incident'],
                        ['code' => 'close', 'name' => 'Close Incident'],
                        ['code' => 'export', 'name' => 'Export Incidents'],
                    ]],
                ['code' => 'safety-inspections', 'name' => 'Safety Inspections', 'type' => 'page', 'route' => '/compliance/hse/inspections',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inspections'],
                        ['code' => 'schedule', 'name' => 'Schedule Inspection'],
                        ['code' => 'conduct', 'name' => 'Conduct Inspection'],
                        ['code' => 'export', 'name' => 'Export Inspection Report'],
                    ]],
                ['code' => 'toolbox-talks', 'name' => 'Toolbox Talks', 'type' => 'page', 'route' => '/compliance/hse/toolbox',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Talks'],
                        ['code' => 'schedule', 'name' => 'Schedule Talk'],
                        ['code' => 'sign', 'name' => 'Capture Attendance'],
                    ]],
                ['code' => 'safety-drills', 'name' => 'Safety Drills (Fire, Emergency, Evacuation)', 'type' => 'page', 'route' => '/compliance/hse/drills',
                    'actions' => [['code' => 'schedule', 'name' => 'Schedule Drill'], ['code' => 'conduct', 'name' => 'Conduct Drill']]],
                ['code' => 'hazard-register', 'name' => 'Hazard Register', 'type' => 'page', 'route' => '/compliance/hse/hazards',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Hazards']]],
                ['code' => 'jsa-jha', 'name' => 'Job Safety / Hazard Analysis (JSA / JHA)', 'type' => 'page', 'route' => '/compliance/hse/jsa',
                    'actions' => [['code' => 'manage', 'name' => 'Manage JSA/JHA']]],
                ['code' => 'investigations', 'name' => 'Root-Cause Investigations', 'type' => 'page', 'route' => '/compliance/hse/investigations',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Investigations']]],
                ['code' => 'stop-work', 'name' => 'Stop-Work Authority', 'type' => 'page', 'route' => '/compliance/hse/stop-work',
                    'actions' => [['code' => 'invoke', 'name' => 'Invoke Stop-Work'], ['code' => 'review', 'name' => 'Review Stop-Work']]],
            ],
        ],

        // ==================== 2. PERMIT TO WORK (PTW) — EAM ====================
        [
            'code' => 'ptw',
            'name' => 'Permit to Work (PTW)',
            'description' => 'Digital PTW for high-risk activities — hot work, confined space, height, electrical, excavation',
            'icon' => 'LockClosedIcon',
            'route' => '/compliance/ptw',
            'priority' => 15,
            'is_active' => true,
            'components' => [
                ['code' => 'ptw-register', 'name' => 'PTW Register', 'type' => 'page', 'route' => '/compliance/ptw',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Permits'],
                        ['code' => 'request', 'name' => 'Request Permit'],
                        ['code' => 'authorize', 'name' => 'Authorize Permit'],
                        ['code' => 'reject', 'name' => 'Reject Permit'],
                        ['code' => 'revoke', 'name' => 'Emergency Revoke'],
                        ['code' => 'close', 'name' => 'Close Permit'],
                        ['code' => 'audit', 'name' => 'Audit Permits'],
                    ]],
                ['code' => 'ptw-types', 'name' => 'PTW Types & Templates', 'type' => 'page', 'route' => '/compliance/ptw/types',
                    'actions' => [['code' => 'manage', 'name' => 'Manage PTW Types']]],
                ['code' => 'ptw-isolations', 'name' => 'Isolations (LOTO Link)', 'type' => 'page', 'route' => '/compliance/ptw/isolations',
                    'actions' => [['code' => 'view', 'name' => 'View Isolations'], ['code' => 'manage', 'name' => 'Manage Isolations']]],
                ['code' => 'cross-permits', 'name' => 'Cross-Permit Conflict Check', 'type' => 'feature', 'route' => null,
                    'actions' => [['code' => 'check', 'name' => 'Run Conflict Check']]],
            ],
        ],

        // ==================== 3. ENVIRONMENTAL ====================
        [
            'code' => 'environmental',
            'name' => 'Environmental Compliance',
            'description' => 'Emissions, effluents, waste, spills, energy & water, ISO 14001',
            'icon' => 'GlobeAltIcon',
            'route' => '/compliance/environmental',
            'priority' => 20,
            'is_active' => true,
            'components' => [
                ['code' => 'emissions', 'name' => 'Emissions Tracking (Air)', 'type' => 'page', 'route' => '/compliance/environmental/emissions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Emissions'],
                        ['code' => 'log', 'name' => 'Log Emission Reading'],
                        ['code' => 'export', 'name' => 'Export Emissions Report'],
                    ]],
                ['code' => 'effluents', 'name' => 'Effluents / Water Discharge', 'type' => 'page', 'route' => '/compliance/environmental/effluents',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Effluents']]],
                ['code' => 'waste-management', 'name' => 'Waste Management', 'type' => 'page', 'route' => '/compliance/environmental/waste',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Waste Records'],
                        ['code' => 'log', 'name' => 'Log Waste Generation'],
                        ['code' => 'dispose', 'name' => 'Record Disposal'],
                        ['code' => 'manifest', 'name' => 'Generate Waste Manifest'],
                    ]],
                ['code' => 'spill-reporting', 'name' => 'Spill / Release Reporting', 'type' => 'page', 'route' => '/compliance/environmental/spills',
                    'actions' => [['code' => 'report', 'name' => 'Report Spill'], ['code' => 'close', 'name' => 'Close Spill']]],
                ['code' => 'energy-water', 'name' => 'Energy & Water Consumption', 'type' => 'page', 'route' => '/compliance/environmental/energy-water',
                    'actions' => [['code' => 'view', 'name' => 'View Consumption'], ['code' => 'log', 'name' => 'Log Reading']]],
                ['code' => 'env-impact-assessment', 'name' => 'Environmental Impact Assessment (EIA)', 'type' => 'page', 'route' => '/compliance/environmental/eia',
                    'actions' => [['code' => 'manage', 'name' => 'Manage EIA']]],
                ['code' => 'ghg-inventory', 'name' => 'GHG / Carbon Inventory (Scope 1/2/3)', 'type' => 'page', 'route' => '/compliance/environmental/ghg',
                    'actions' => [['code' => 'view', 'name' => 'View GHG'], ['code' => 'export', 'name' => 'Export GHG Report']]],
            ],
        ],

        // ==================== 4. WORKFORCE CERTIFICATIONS ====================
        [
            'code' => 'workforce-certs',
            'name' => 'Workforce Certifications',
            'description' => 'Mandatory certifications (OSHA, CSCS, Welding, First Aid), expiry tracking',
            'icon' => 'IdentificationIcon',
            'route' => '/compliance/workforce-certs',
            'priority' => 25,
            'is_active' => true,
            'components' => [
                ['code' => 'competency-matrix', 'name' => 'Competency Matrix', 'type' => 'page', 'route' => '/compliance/workforce-certs/matrix',
                    'actions' => [['code' => 'view', 'name' => 'View Matrix'], ['code' => 'export', 'name' => 'Export Matrix']]],
                ['code' => 'expiry-monitor', 'name' => 'Expiry Monitor', 'type' => 'page', 'route' => '/compliance/workforce-certs/expiry',
                    'actions' => [['code' => 'view', 'name' => 'View Expiring'], ['code' => 'remind', 'name' => 'Send Reminder']]],
                ['code' => 'worker-compliance', 'name' => 'Worker Compliance Status', 'type' => 'page', 'route' => '/compliance/workforce-certs/workers',
                    'actions' => [['code' => 'view', 'name' => 'View Worker Compliance']]],
            ],
        ],

        // ==================== 5. CONTRACTOR COMPLIANCE (EAM) ====================
        [
            'code' => 'contractor-compliance',
            'name' => 'Contractor Compliance',
            'description' => 'Contractor onboarding, insurance verification, site induction, permits',
            'icon' => 'BuildingStorefrontIcon',
            'route' => '/compliance/contractor',
            'priority' => 30,
            'is_active' => true,
            'components' => [
                ['code' => 'contractor-register', 'name' => 'Contractor Register', 'type' => 'page', 'route' => '/compliance/contractor/register',
                    'actions' => [['code' => 'view', 'name' => 'View Contractors'], ['code' => 'onboard', 'name' => 'Onboard Contractor']]],
                ['code' => 'insurance-verification', 'name' => 'Insurance Verification', 'type' => 'page', 'route' => '/compliance/contractor/insurance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Insurance'],
                        ['code' => 'upload', 'name' => 'Upload Certificate'],
                        ['code' => 'verify', 'name' => 'Verify Certificate'],
                    ]],
                ['code' => 'site-induction', 'name' => 'Site Induction', 'type' => 'page', 'route' => '/compliance/contractor/induction',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inductions'],
                        ['code' => 'conduct', 'name' => 'Conduct Induction'],
                        ['code' => 'certify', 'name' => 'Issue Induction Certificate'],
                    ]],
                ['code' => 'prequalification', 'name' => 'Contractor Prequalification', 'type' => 'page', 'route' => '/compliance/contractor/prequalification',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Prequalification']]],
            ],
        ],

        // ==================== 6. REGULATORY & PERMITS ====================
        [
            'code' => 'regulatory-permits',
            'name' => 'Regulatory & Permits',
            'description' => 'Government permits, licenses, regulatory registers, renewals',
            'icon' => 'BuildingLibraryIcon',
            'route' => '/compliance/regulatory',
            'priority' => 35,
            'is_active' => true,
            'components' => [
                ['code' => 'permit-register', 'name' => 'Permit & License Register', 'type' => 'page', 'route' => '/compliance/regulatory/permits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Permits'],
                        ['code' => 'create', 'name' => 'Add Permit'],
                        ['code' => 'renew', 'name' => 'Renew Permit'],
                        ['code' => 'expire', 'name' => 'Mark Expired'],
                        ['code' => 'export', 'name' => 'Export Register'],
                    ]],
                ['code' => 'regulatory-register', 'name' => 'Regulatory Register', 'type' => 'page', 'route' => '/compliance/regulatory/register',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Regulations']]],
                ['code' => 'regulatory-submissions', 'name' => 'Regulatory Submissions', 'type' => 'page', 'route' => '/compliance/regulatory/submissions',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Submissions']]],
                /* EAM: asset-specific regulatory permits (boiler, elevator, pressure vessel) */
                ['code' => 'asset-permits', 'name' => 'Asset Regulatory Permits', 'type' => 'page', 'route' => '/compliance/regulatory/asset-permits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Asset Permits'],
                        ['code' => 'link-asset', 'name' => 'Link Permit to Asset'],
                        ['code' => 'renew', 'name' => 'Renew Permit'],
                    ]],
            ],
        ],

        // ==================== 7. AUDITS ====================
        [
            'code' => 'audits',
            'name' => 'Compliance Audits',
            'description' => 'Internal & external audits, ISO audit trails, corrective actions',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/compliance/audits',
            'priority' => 40,
            'is_active' => true,
            'components' => [
                ['code' => 'audit-plan', 'name' => 'Audit Plan', 'type' => 'page', 'route' => '/compliance/audits/plan',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Audit Plan']]],
                ['code' => 'audit-execution', 'name' => 'Audit Execution', 'type' => 'page', 'route' => '/compliance/audits/execution',
                    'actions' => [
                        ['code' => 'conduct', 'name' => 'Conduct Audit'],
                        ['code' => 'finding', 'name' => 'Record Finding'],
                        ['code' => 'close', 'name' => 'Close Audit'],
                    ]],
                ['code' => 'audit-findings', 'name' => 'Findings & Actions', 'type' => 'page', 'route' => '/compliance/audits/findings',
                    'actions' => [['code' => 'view', 'name' => 'View Findings'], ['code' => 'track', 'name' => 'Track Actions']]],
            ],
        ],

        // ==================== 8. POLICIES & PROCEDURES ====================
        [
            'code' => 'policies',
            'name' => 'Policies & Procedures',
            'description' => 'Policy library, acknowledgments, SOPs',
            'icon' => 'DocumentTextIcon',
            'route' => '/compliance/policies',
            'priority' => 45,
            'is_active' => true,
            'components' => [
                ['code' => 'policy-library', 'name' => 'Policy Library', 'type' => 'page', 'route' => '/compliance/policies',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Policies'],
                        ['code' => 'create', 'name' => 'Create Policy'],
                        ['code' => 'revise', 'name' => 'Revise Policy'],
                        ['code' => 'publish', 'name' => 'Publish Policy'],
                        ['code' => 'archive', 'name' => 'Archive Policy'],
                    ]],
                ['code' => 'acknowledgments', 'name' => 'Policy Acknowledgments', 'type' => 'page', 'route' => '/compliance/policies/acknowledgments',
                    'actions' => [['code' => 'send', 'name' => 'Send for Acknowledgment'], ['code' => 'track', 'name' => 'Track Acknowledgments']]],
                ['code' => 'sops', 'name' => 'Standard Operating Procedures (SOPs)', 'type' => 'page', 'route' => '/compliance/policies/sops',
                    'actions' => [['code' => 'manage', 'name' => 'Manage SOPs']]],
            ],
        ],

        // ==================== 9. RISK MANAGEMENT ====================
        [
            'code' => 'risk-management',
            'name' => 'Risk Management',
            'description' => 'Enterprise risk register, risk assessments, controls, KRIs',
            'icon' => 'ShieldExclamationIcon',
            'route' => '/compliance/risk',
            'priority' => 50,
            'is_active' => true,
            'components' => [
                ['code' => 'risk-register', 'name' => 'Risk Register', 'type' => 'page', 'route' => '/compliance/risk/register',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Risks'],
                        ['code' => 'create', 'name' => 'Add Risk'],
                        ['code' => 'update', 'name' => 'Update Risk'],
                        ['code' => 'assess', 'name' => 'Assess Risk'],
                        ['code' => 'close', 'name' => 'Close Risk'],
                    ]],
                ['code' => 'risk-assessments', 'name' => 'Risk Assessments', 'type' => 'page', 'route' => '/compliance/risk/assessments',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Assessments']]],
                ['code' => 'controls-library', 'name' => 'Controls Library', 'type' => 'page', 'route' => '/compliance/risk/controls',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Controls'], ['code' => 'test', 'name' => 'Test Control']]],
                ['code' => 'kri', 'name' => 'Key Risk Indicators (KRIs)', 'type' => 'page', 'route' => '/compliance/risk/kri',
                    'actions' => [['code' => 'view', 'name' => 'View KRIs'], ['code' => 'manage', 'name' => 'Manage KRIs']]],
            ],
        ],

        // ==================== 10. GDPR / DATA PRIVACY ====================
        [
            'code' => 'privacy',
            'name' => 'Data Privacy (GDPR / CCPA / HIPAA)',
            'description' => 'DSR handling, consents, processing register, DPIA',
            'icon' => 'KeyIcon',
            'route' => '/compliance/privacy',
            'priority' => 55,
            'is_active' => true,
            'components' => [
                ['code' => 'dsr', 'name' => 'Data Subject Requests (DSR)', 'type' => 'page', 'route' => '/compliance/privacy/dsr',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View DSRs'],
                        ['code' => 'create', 'name' => 'Log DSR'],
                        ['code' => 'fulfill', 'name' => 'Fulfill Request'],
                    ]],
                ['code' => 'consents', 'name' => 'Consent Management', 'type' => 'page', 'route' => '/compliance/privacy/consents',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Consents']]],
                ['code' => 'ropa', 'name' => 'Record of Processing Activities (RoPA)', 'type' => 'page', 'route' => '/compliance/privacy/ropa',
                    'actions' => [['code' => 'manage', 'name' => 'Manage RoPA']]],
                ['code' => 'dpia', 'name' => 'DPIA', 'type' => 'page', 'route' => '/compliance/privacy/dpia',
                    'actions' => [['code' => 'manage', 'name' => 'Manage DPIA']]],
                ['code' => 'breach-notifications', 'name' => 'Breach Notifications', 'type' => 'page', 'route' => '/compliance/privacy/breaches',
                    'actions' => [['code' => 'report', 'name' => 'Report Breach'], ['code' => 'notify', 'name' => 'Notify Authorities']]],
            ],
        ],

        // ==================== 11. WHISTLEBLOWER & ETHICS ====================
        [
            'code' => 'ethics',
            'name' => 'Ethics & Whistleblower',
            'description' => 'Ethics hotline, whistleblower reports, case handling',
            'icon' => 'ChatBubbleOvalLeftEllipsisIcon',
            'route' => '/compliance/ethics',
            'priority' => 60,
            'is_active' => true,
            'components' => [
                ['code' => 'ethics-hotline', 'name' => 'Ethics Hotline', 'type' => 'page', 'route' => '/compliance/ethics/hotline',
                    'actions' => [['code' => 'report', 'name' => 'Submit Report'], ['code' => 'triage', 'name' => 'Triage Report']]],
                ['code' => 'cases', 'name' => 'Ethics Cases', 'type' => 'page', 'route' => '/compliance/ethics/cases',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cases'], ['code' => 'close', 'name' => 'Close Case']]],
            ],
        ],

        // ==================== 12. ESG / SUSTAINABILITY ====================
        [
            'code' => 'esg',
            'name' => 'ESG & Sustainability',
            'description' => 'ESG scoring, GRI/SASB/TCFD reporting, sustainability goals',
            'icon' => 'GlobeAmericasIcon',
            'route' => '/compliance/esg',
            'priority' => 65,
            'is_active' => true,
            'components' => [
                ['code' => 'esg-metrics', 'name' => 'ESG Metrics', 'type' => 'page', 'route' => '/compliance/esg/metrics',
                    'actions' => [['code' => 'view', 'name' => 'View Metrics'], ['code' => 'log', 'name' => 'Log ESG Data']]],
                ['code' => 'esg-reports', 'name' => 'ESG Reports (GRI/SASB/TCFD)', 'type' => 'page', 'route' => '/compliance/esg/reports',
                    'actions' => [['code' => 'generate', 'name' => 'Generate Report'], ['code' => 'export', 'name' => 'Export Report']]],
                ['code' => 'sustainability-goals', 'name' => 'Sustainability Goals', 'type' => 'page', 'route' => '/compliance/esg/goals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Goals']]],
            ],
        ],

        // ==================== 13. ASSET COMPLIANCE (EAM) ====================
        [
            'code' => 'asset-compliance',
            'name' => 'Asset Compliance',
            'description' => 'Asset regulatory checks, statutory inspections (boilers, pressure vessels, elevators)',
            'icon' => 'CubeIcon',
            'route' => '/compliance/asset',
            'priority' => 70,
            'is_active' => true,
            'components' => [
                ['code' => 'statutory-inspections', 'name' => 'Statutory Asset Inspections', 'type' => 'page', 'route' => '/compliance/asset/statutory',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Statutory Inspections'],
                        ['code' => 'schedule', 'name' => 'Schedule Inspection'],
                        ['code' => 'conduct', 'name' => 'Conduct Inspection'],
                        ['code' => 'certify', 'name' => 'Issue Certificate'],
                    ]],
                ['code' => 'asset-compliance-status', 'name' => 'Asset Compliance Status', 'type' => 'page', 'route' => '/compliance/asset/status',
                    'actions' => [['code' => 'view', 'name' => 'View Status'], ['code' => 'export', 'name' => 'Export Status']]],
                ['code' => 'asset-recalls', 'name' => 'Asset Recalls & Safety Notices', 'type' => 'page', 'route' => '/compliance/asset/recalls',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Recalls']]],
            ],
        ],

        // ==================== 14. TRAINING & AWARENESS ====================
        [
            'code' => 'training-awareness',
            'name' => 'Compliance Training & Awareness',
            'description' => 'Mandatory compliance training, code of conduct, anti-bribery',
            'icon' => 'AcademicCapIcon',
            'route' => '/compliance/training',
            'priority' => 75,
            'is_active' => true,
            'components' => [
                ['code' => 'mandatory-training', 'name' => 'Mandatory Training Assignments', 'type' => 'page', 'route' => '/compliance/training/mandatory',
                    'actions' => [['code' => 'assign', 'name' => 'Assign Training'], ['code' => 'track', 'name' => 'Track Completion']]],
                ['code' => 'awareness-campaigns', 'name' => 'Awareness Campaigns', 'type' => 'page', 'route' => '/compliance/training/campaigns',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Campaigns']]],
            ],
        ],

        // ==================== 15. REPORTS & ANALYTICS ====================
        [
            'code' => 'reports-analytics',
            'name' => 'Compliance Reports & Analytics',
            'description' => 'Regulatory reports, trend analysis, predictive compliance',
            'icon' => 'DocumentChartBarIcon',
            'route' => '/compliance/reports',
            'priority' => 80,
            'is_active' => true,
            'components' => [
                ['code' => 'regulatory-reports', 'name' => 'Regulatory Reports', 'type' => 'page', 'route' => '/compliance/reports/regulatory',
                    'actions' => [['code' => 'generate', 'name' => 'Generate Report'], ['code' => 'submit', 'name' => 'Submit Report']]],
                ['code' => 'incident-trends', 'name' => 'Incident Trends (TRIR/LTIFR)', 'type' => 'page', 'route' => '/compliance/reports/trends',
                    'actions' => [['code' => 'view', 'name' => 'View Trends'], ['code' => 'export', 'name' => 'Export Trends']]],
                ['code' => 'predictive-compliance', 'name' => 'Predictive Compliance (AI)', 'type' => 'page', 'route' => '/compliance/reports/predictive',
                    'actions' => [['code' => 'view', 'name' => 'View Predictions']]],
                ['code' => 'custom-reports', 'name' => 'Custom Reports', 'type' => 'page', 'route' => '/compliance/reports/custom',
                    'actions' => [['code' => 'create', 'name' => 'Create Report'], ['code' => 'export', 'name' => 'Export Report']]],
            ],
        ],

        // ==================== 16. INTEGRATIONS ====================
        [
            'code' => 'integrations',
            'name' => 'Integrations',
            'description' => 'Regulatory bodies, safety equipment, HR, EAM systems',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => '/compliance/integrations',
            'priority' => 85,
            'is_active' => true,
            'components' => [
                ['code' => 'regulatory-api', 'name' => 'Regulatory APIs', 'type' => 'page', 'route' => '/compliance/integrations/regulatory',
                    'actions' => [['code' => 'configure', 'name' => 'Configure API']]],
                ['code' => 'osha-reporting', 'name' => 'OSHA / Regulatory Reporting Feed', 'type' => 'page', 'route' => '/compliance/integrations/osha',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']]],
            ],
        ],

        // ==================== 17. SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'Compliance Settings',
            'description' => 'Workflows, approvals, notification rules',
            'icon' => 'CogIcon',
            'route' => '/compliance/settings',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                ['code' => 'workflows', 'name' => 'Approval Workflows', 'type' => 'page', 'route' => '/compliance/settings/workflows',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows']]],
                ['code' => 'notification-rules', 'name' => 'Notification Rules', 'type' => 'page', 'route' => '/compliance/settings/notifications',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Notifications']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/compliance/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map
    |--------------------------------------------------------------------------
    */
    'eam_integration' => [
        'provides' => [
            'compliance.incidents' => 'hse-management.incidents',
            'compliance.hazards' => 'hse-management.hazard-register',
            'compliance.jsa' => 'hse-management.jsa-jha',
            'compliance.permit_to_work' => 'ptw.ptw-register',
            'compliance.isolations' => 'ptw.ptw-isolations',
            'compliance.environmental' => 'environmental',
            'compliance.emissions' => 'environmental.emissions',
            'compliance.waste' => 'environmental.waste-management',
            'compliance.contractor_insurance' => 'contractor-compliance.insurance-verification',
            'compliance.contractor_induction' => 'contractor-compliance.site-induction',
            'compliance.asset_permits' => 'regulatory-permits.asset-permits',
            'compliance.statutory_inspections' => 'asset-compliance.statutory-inspections',
            'compliance.asset_recalls' => 'asset-compliance.asset-recalls',
            'compliance.workforce_certs' => 'workforce-certs.competency-matrix',
            'compliance.risks' => 'risk-management.risk-register',
            'compliance.controls' => 'risk-management.controls-library',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_orders' => 'aero-eam',
            'hrm.safety_loto' => 'aero-hrm',
            'hrm.safety_ppe' => 'aero-hrm',
            'hrm.certifications' => 'aero-hrm',
            'iot.sensor_breach_alerts' => 'aero-iot',
            'scm.contractor_master' => 'aero-scm',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'compliance_admin_role' => 'compliance-admin',
        'hse_admin_role' => 'hse-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'compliance-access'],
    ],
];
