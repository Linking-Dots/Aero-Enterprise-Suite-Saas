<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quality Control (QC) & Material Intelligence Module
    |--------------------------------------------------------------------------
    |
    | Advanced Quality Assurance system integrating Field Inspections (ITP),
    | Material Laboratory Data, and Defect Management (NCR).
    |
    */

    'code' => 'quality',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Quality Management & Labs',
    'description' => 'Full QMS: inspections (ITP/WIR), material labs (LIMS), NCR/CAPA, SPC, calibration, supplier quality, audits, ISO compliance, and EAM asset-quality checks.',
    'version' => '3.0.0',
    'category' => 'engineering_ops',
    'icon' => 'BeakerIcon',
    'priority' => 18,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('QUALITY_MODULE_ENABLED', true),
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/quality',

    'submodules' => [

        // ==================== 0. DASHBOARD ====================
        [
            'code' => 'dashboard',
            'name' => 'Quality Dashboard',
            'description' => 'Quality KPIs, defect rates, CAPA status, calibration due',
            'icon' => 'HomeIcon',
            'route' => '/quality/dashboard',
            'priority' => 0,
            'is_active' => true,
            'components' => [
                ['code' => 'quality-dashboard', 'name' => 'Quality Dashboard', 'type' => 'page', 'route' => '/quality/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
            ],
        ],

        // ==================== 1. SMART INSPECTIONS (ITP) ====================
        [
            'code' => 'inspections',
            'name' => 'Site Inspections (ITP)',
            'description' => 'Inspection & Test Plans with automated environmental validation.',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => '/quality/inspections',
            'priority' => 10,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'inspection-request',
                    'name' => 'Inspection Request (WIR)',
                    'description' => 'Digital WIR with hold-point management',
                    'route' => '/quality/inspections/wir',
                    'icon' => 'DocumentCheckIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'create', 'name' => 'Raise Request', 'description' => 'Submit new inspection request'],
                        // NOVELTY: Validates if weather was acceptable during work execution
                        ['code' => 'validate_env', 'name' => 'Env Check', 'description' => 'Auto-validate against RFI weather logs'],
                        ['code' => 'sign_off', 'name' => 'Digital Sign-off', 'description' => 'Multi-party digital signature'],
                    ],
                ],
                [
                    'code' => 'checklists',
                    'name' => 'Smart Checklists',
                    'description' => 'Dynamic checklists based on work type (Earthwork, Concrete, MEP)',
                    'route' => '/quality/inspections/checklists',
                    'icon' => 'ListBulletIcon',
                    'type' => 'settings',
                ],
            ],
        ],

        // ==================== 2. MATERIAL TESTING LAB (HIGH VALUE/NOVELTY) ====================
        [
            'code' => 'material-lab',
            'name' => 'Material Testing Lab',
            'description' => 'Integrated Laboratory Information Management System (LIMS) for construction materials.',
            'icon' => 'BeakerIcon',
            'route' => '/quality/lab',
            'priority' => 20,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'concrete-register',
                    'name' => 'Concrete Cube Register',
                    'description' => 'Track pouring dates, curing methods, and compressive strength results',
                    'route' => '/quality/lab/concrete',
                    'icon' => 'CubeIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'log_pour', 'name' => 'Log Pour', 'description' => 'Record new concrete pour'],
                        ['code' => 'input_7day', 'name' => '7-Day Result', 'description' => 'Input 7-day crush test result'],
                        ['code' => 'predict_28day', 'name' => 'AI Prediction', 'description' => 'Predict 28-day strength using regression model'],
                    ],
                ],
                [
                    'code' => 'soil-compaction',
                    'name' => 'Soil Density Tests',
                    'description' => 'Record Field Density Tests (FDT) and Proctor values',
                    'route' => '/quality/lab/soil',
                    'icon' => 'GlobeAmericasIcon',
                    'type' => 'page',
                ],
                [
                    'code' => 'material-approvals',
                    'name' => 'Material Submittals',
                    'description' => 'Track approval status of source materials (MAR)',
                    'route' => '/quality/lab/materials',
                    'icon' => 'TagIcon',
                    'type' => 'page',
                ],
            ],
        ],

        // ==================== 3. DEFECT MANAGEMENT (NCR) ====================
        [
            'code' => 'ncr-management',
            'name' => 'Non-Conformance (NCR)',
            'description' => 'Root cause analysis and cost-of-correction tracking.',
            'icon' => 'ExclamationTriangleIcon',
            'route' => '/quality/ncr',
            'priority' => 30,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'ncr-log',
                    'name' => 'NCR Register',
                    'description' => 'Central register of all non-conformances',
                    'route' => '/quality/ncr',
                    'icon' => 'TableCellsIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'issue', 'name' => 'Issue NCR', 'description' => 'Raise new non-conformance'],
                        ['code' => 'close_out', 'name' => 'Close Out', 'description' => 'Verify rectification and close'],
                    ],
                ],
                [
                    'code' => 'root-cause',
                    'name' => 'Root Cause Analysis',
                    'description' => '5-Whys or Fishbone diagram tools for defect analysis',
                    'route' => '/quality/ncr/analysis',
                    'icon' => 'ChartPieIcon',
                    'type' => 'feature',
                    'actions' => [['code' => 'analyze', 'name' => 'Run RCA'], ['code' => 'export', 'name' => 'Export Analysis']],
                ],
            ],
        ],

        // ==================== 4. CAPA ====================
        [
            'code' => 'capa',
            'name' => 'CAPA (Corrective & Preventive Action)',
            'description' => 'Corrective and preventive action workflow with effectiveness verification',
            'icon' => 'ArrowPathRoundedSquareIcon',
            'route' => '/quality/capa',
            'priority' => 40,
            'is_active' => true,
            'components' => [
                ['code' => 'capa-list', 'name' => 'CAPA Register', 'type' => 'page', 'route' => '/quality/capa',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CAPAs'],
                        ['code' => 'create', 'name' => 'Open CAPA'],
                        ['code' => 'update', 'name' => 'Update CAPA'],
                        ['code' => 'approve', 'name' => 'Approve CAPA'],
                        ['code' => 'close', 'name' => 'Close CAPA'],
                        ['code' => 'verify-effectiveness', 'name' => 'Verify Effectiveness'],
                    ]],
                ['code' => 'capa-templates', 'name' => 'CAPA Templates', 'type' => 'page', 'route' => '/quality/capa/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates']]],
            ],
        ],

        // ==================== 5. SPC & CONTROL CHARTS ====================
        [
            'code' => 'spc',
            'name' => 'Statistical Process Control (SPC)',
            'description' => 'Control charts, capability studies (Cp/Cpk), process monitoring',
            'icon' => 'ChartBarIcon',
            'route' => '/quality/spc',
            'priority' => 45,
            'is_active' => true,
            'components' => [
                ['code' => 'control-charts', 'name' => 'Control Charts (X-bar, R, p, np, c, u)', 'type' => 'page', 'route' => '/quality/spc/control-charts',
                    'actions' => [['code' => 'view', 'name' => 'View Charts'], ['code' => 'configure', 'name' => 'Configure Chart']]],
                ['code' => 'capability-study', 'name' => 'Capability Studies (Cp/Cpk)', 'type' => 'page', 'route' => '/quality/spc/capability',
                    'actions' => [['code' => 'run', 'name' => 'Run Study'], ['code' => 'view', 'name' => 'View Results']]],
                ['code' => 'sampling-plans', 'name' => 'Sampling Plans (AQL)', 'type' => 'page', 'route' => '/quality/spc/sampling',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Sampling Plans']]],
            ],
        ],

        // ==================== 6. CALIBRATION (EAM-CRITICAL) ====================
        [
            'code' => 'calibration',
            'name' => 'Calibration Management',
            'description' => 'Equipment / gauge calibration schedules, records, traceability, MSA',
            'icon' => 'AdjustmentsHorizontalIcon',
            'route' => '/quality/calibration',
            'priority' => 50,
            'is_active' => true,
            'components' => [
                ['code' => 'calibration-register', 'name' => 'Calibration Register', 'type' => 'page', 'route' => '/quality/calibration/register',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Calibration Register'],
                        ['code' => 'create', 'name' => 'Add Equipment'],
                        ['code' => 'update', 'name' => 'Update Equipment'],
                        ['code' => 'delete', 'name' => 'Delete Equipment'],
                        ['code' => 'link-asset', 'name' => 'Link to EAM Asset'],
                    ]],
                ['code' => 'calibration-schedule', 'name' => 'Calibration Schedule', 'type' => 'page', 'route' => '/quality/calibration/schedule',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Schedule'],
                        ['code' => 'create', 'name' => 'Schedule Calibration'],
                        ['code' => 'remind', 'name' => 'Send Due Reminder'],
                    ]],
                ['code' => 'calibration-certificates', 'name' => 'Calibration Certificates', 'type' => 'page', 'route' => '/quality/calibration/certificates',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Certificates'],
                        ['code' => 'upload', 'name' => 'Upload Certificate'],
                        ['code' => 'print', 'name' => 'Print Certificate'],
                    ]],
                ['code' => 'traceability-chain', 'name' => 'Traceability Chain (NIST / NABL)', 'type' => 'page', 'route' => '/quality/calibration/traceability',
                    'actions' => [['code' => 'view', 'name' => 'View Traceability']]],
                ['code' => 'msa', 'name' => 'Measurement System Analysis (MSA / Gage R&R)', 'type' => 'page', 'route' => '/quality/calibration/msa',
                    'actions' => [['code' => 'run', 'name' => 'Run MSA'], ['code' => 'view', 'name' => 'View MSA Results']]],
                ['code' => 'out-of-tolerance', 'name' => 'Out-of-Tolerance (OOT) Events', 'type' => 'page', 'route' => '/quality/calibration/oot',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View OOT Events'],
                        ['code' => 'report', 'name' => 'Report OOT'],
                        ['code' => 'impact-assess', 'name' => 'Impact Assessment'],
                    ]],
            ],
        ],

        // ==================== 7. SUPPLIER QUALITY ====================
        [
            'code' => 'supplier-quality',
            'name' => 'Supplier Quality',
            'description' => 'Incoming inspection, supplier NCR/SCAR, PPAP, first-article inspection',
            'icon' => 'BuildingStorefrontIcon',
            'route' => '/quality/supplier',
            'priority' => 55,
            'is_active' => true,
            'components' => [
                ['code' => 'incoming-inspection', 'name' => 'Incoming / Receiving Inspection', 'type' => 'page', 'route' => '/quality/supplier/incoming',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inspections'],
                        ['code' => 'create', 'name' => 'Create Inspection'],
                        ['code' => 'pass', 'name' => 'Pass Inspection'],
                        ['code' => 'fail', 'name' => 'Fail Inspection'],
                    ]],
                ['code' => 'scar', 'name' => 'Supplier CAR (SCAR)', 'type' => 'page', 'route' => '/quality/supplier/scar',
                    'actions' => [['code' => 'manage', 'name' => 'Manage SCAR']]],
                ['code' => 'ppap', 'name' => 'PPAP (Production Part Approval)', 'type' => 'page', 'route' => '/quality/supplier/ppap',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PPAPs'],
                        ['code' => 'create', 'name' => 'Create PPAP Submission'],
                        ['code' => 'approve', 'name' => 'Approve PPAP'],
                    ]],
                ['code' => 'first-article', 'name' => 'First Article Inspection (FAI)', 'type' => 'page', 'route' => '/quality/supplier/fai',
                    'actions' => [['code' => 'manage', 'name' => 'Manage FAI']]],
            ],
        ],

        // ==================== 8. QUALITY AUDITS ====================
        [
            'code' => 'audits',
            'name' => 'Quality Audits',
            'description' => 'Internal, external, supplier, and process audits',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/quality/audits',
            'priority' => 60,
            'is_active' => true,
            'components' => [
                ['code' => 'audit-plan', 'name' => 'Audit Plan & Schedule', 'type' => 'page', 'route' => '/quality/audits/plan',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Audit Plan']]],
                ['code' => 'audit-execution', 'name' => 'Audit Execution', 'type' => 'page', 'route' => '/quality/audits/execution',
                    'actions' => [
                        ['code' => 'conduct', 'name' => 'Conduct Audit'],
                        ['code' => 'finding', 'name' => 'Record Findings'],
                        ['code' => 'close', 'name' => 'Close Audit'],
                    ]],
                ['code' => 'audit-findings', 'name' => 'Audit Findings & Observations', 'type' => 'page', 'route' => '/quality/audits/findings',
                    'actions' => [['code' => 'view', 'name' => 'View Findings'], ['code' => 'respond', 'name' => 'Respond to Finding']]],
            ],
        ],

        // ==================== 9. ISO / REGULATORY COMPLIANCE ====================
        [
            'code' => 'iso-compliance',
            'name' => 'ISO / Regulatory Compliance',
            'description' => 'ISO 9001/14001/45001/13485/17025, FDA, GMP compliance tracking',
            'icon' => 'ShieldCheckIcon',
            'route' => '/quality/iso',
            'priority' => 65,
            'is_active' => true,
            'components' => [
                ['code' => 'standards-register', 'name' => 'Standards Register', 'type' => 'page', 'route' => '/quality/iso/standards',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Standards']]],
                ['code' => 'controlled-documents', 'name' => 'Controlled Documents & SOPs', 'type' => 'page', 'route' => '/quality/iso/documents',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Documents'],
                        ['code' => 'revise', 'name' => 'Revise Document'],
                        ['code' => 'approve', 'name' => 'Approve Revision'],
                        ['code' => 'distribute', 'name' => 'Distribute Document'],
                    ]],
                ['code' => 'compliance-matrix', 'name' => 'Compliance Matrix', 'type' => 'page', 'route' => '/quality/iso/matrix',
                    'actions' => [['code' => 'view', 'name' => 'View Matrix'], ['code' => 'export', 'name' => 'Export Matrix']]],
                ['code' => 'management-review', 'name' => 'Management Review', 'type' => 'page', 'route' => '/quality/iso/mr',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Management Reviews']]],
            ],
        ],

        // ==================== 10. DEVIATIONS & CHANGE CONTROL ====================
        [
            'code' => 'deviation-change',
            'name' => 'Deviations & Change Control',
            'description' => 'Deviation reports, engineering change requests, change control board',
            'icon' => 'ArrowPathIcon',
            'route' => '/quality/deviation-change',
            'priority' => 70,
            'is_active' => true,
            'components' => [
                ['code' => 'deviations', 'name' => 'Deviation Reports', 'type' => 'page', 'route' => '/quality/deviations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Deviations'],
                        ['code' => 'create', 'name' => 'Report Deviation'],
                        ['code' => 'close', 'name' => 'Close Deviation'],
                    ]],
                ['code' => 'change-control', 'name' => 'Change Control (ECN / ECR)', 'type' => 'page', 'route' => '/quality/change-control',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Change Requests'],
                        ['code' => 'create', 'name' => 'Create ECN/ECR'],
                        ['code' => 'approve', 'name' => 'Approve Change'],
                        ['code' => 'implement', 'name' => 'Implement Change'],
                    ]],
            ],
        ],

        // ==================== 11. CUSTOMER COMPLAINTS ====================
        [
            'code' => 'complaints',
            'name' => 'Customer Complaints',
            'description' => 'Complaint register, investigation, response, trend analysis',
            'icon' => 'ChatBubbleOvalLeftEllipsisIcon',
            'route' => '/quality/complaints',
            'priority' => 75,
            'is_active' => true,
            'components' => [
                ['code' => 'complaint-list', 'name' => 'Complaint Register', 'type' => 'page', 'route' => '/quality/complaints',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Complaints'],
                        ['code' => 'create', 'name' => 'Log Complaint'],
                        ['code' => 'investigate', 'name' => 'Investigate Complaint'],
                        ['code' => 'respond', 'name' => 'Respond to Customer'],
                        ['code' => 'close', 'name' => 'Close Complaint'],
                    ]],
                ['code' => 'complaint-trends', 'name' => 'Complaint Trends', 'type' => 'page', 'route' => '/quality/complaints/trends',
                    'actions' => [['code' => 'view', 'name' => 'View Trends']]],
            ],
        ],

        // ==================== 12. RISK / FMEA ====================
        [
            'code' => 'risk-fmea',
            'name' => 'Risk & FMEA',
            'description' => 'DFMEA, PFMEA, risk-based thinking per ISO 9001:2015',
            'icon' => 'ShieldExclamationIcon',
            'route' => '/quality/risk-fmea',
            'priority' => 80,
            'is_active' => true,
            'components' => [
                ['code' => 'fmea-register', 'name' => 'FMEA Register', 'type' => 'page', 'route' => '/quality/fmea',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View FMEAs'],
                        ['code' => 'create', 'name' => 'Create FMEA'],
                        ['code' => 'update', 'name' => 'Update FMEA'],
                        ['code' => 'calculate-rpn', 'name' => 'Calculate RPN'],
                    ]],
                ['code' => 'risk-register', 'name' => 'Quality Risk Register', 'type' => 'page', 'route' => '/quality/risks',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Risks']]],
            ],
        ],

        // ==================== 13. ASSET QUALITY CHECKS (EAM) ====================
        [
            'code' => 'asset-quality',
            'name' => 'Asset Quality Checks',
            'description' => 'Periodic asset quality inspections, commissioning QA, reliability checks',
            'icon' => 'CubeIcon',
            'route' => '/quality/asset-quality',
            'priority' => 85,
            'is_active' => true,
            'components' => [
                ['code' => 'commissioning-qa', 'name' => 'Commissioning QA', 'type' => 'page', 'route' => '/quality/asset-quality/commissioning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Commissioning Records'],
                        ['code' => 'conduct', 'name' => 'Conduct Commissioning QA'],
                        ['code' => 'sign-off', 'name' => 'Sign Off Commissioning'],
                    ]],
                ['code' => 'reliability-checks', 'name' => 'Reliability Checks', 'type' => 'page', 'route' => '/quality/asset-quality/reliability',
                    'actions' => [['code' => 'view', 'name' => 'View Reliability'], ['code' => 'run', 'name' => 'Run Reliability Check']]],
                ['code' => 'warranty-claims-qa', 'name' => 'Warranty Claim QA', 'type' => 'page', 'route' => '/quality/asset-quality/warranty',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Warranty QA']]],
            ],
        ],

        // ==================== 14. TRAINING RECORDS (QA) ====================
        [
            'code' => 'quality-training',
            'name' => 'Quality Training Records',
            'description' => 'QA training matrix, qualifications, and competency records',
            'icon' => 'AcademicCapIcon',
            'route' => '/quality/training',
            'priority' => 90,
            'is_active' => true,
            'components' => [
                ['code' => 'training-matrix', 'name' => 'Training Matrix', 'type' => 'page', 'route' => '/quality/training/matrix',
                    'actions' => [['code' => 'view', 'name' => 'View Matrix'], ['code' => 'manage', 'name' => 'Manage Matrix']]],
                ['code' => 'qualifications', 'name' => 'Qualifications Register', 'type' => 'page', 'route' => '/quality/training/qualifications',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Qualifications']]],
            ],
        ],

        // ==================== 15. COST OF QUALITY ====================
        [
            'code' => 'cost-of-quality',
            'name' => 'Cost of Quality',
            'description' => 'Prevention, appraisal, internal/external failure cost analysis',
            'icon' => 'CalculatorIcon',
            'route' => '/quality/cost-of-quality',
            'priority' => 95,
            'is_active' => true,
            'components' => [
                ['code' => 'coq-analysis', 'name' => 'COQ Analysis', 'type' => 'page', 'route' => '/quality/cost-of-quality',
                    'actions' => [['code' => 'view', 'name' => 'View COQ'], ['code' => 'export', 'name' => 'Export COQ']]],
            ],
        ],

        // ==================== 16. ANALYTICS ====================
        [
            'code' => 'analytics',
            'name' => 'Quality Analytics',
            'description' => 'Quality KPIs, defect trends, predictive quality',
            'icon' => 'ChartPieIcon',
            'route' => '/quality/analytics',
            'priority' => 96,
            'is_active' => true,
            'components' => [
                ['code' => 'quality-kpis', 'name' => 'Quality KPIs', 'type' => 'page', 'route' => '/quality/analytics/kpis',
                    'actions' => [['code' => 'view', 'name' => 'View KPIs']]],
                ['code' => 'defect-trends', 'name' => 'Defect Trends', 'type' => 'page', 'route' => '/quality/analytics/defects',
                    'actions' => [['code' => 'view', 'name' => 'View Defect Trends']]],
                ['code' => 'predictive-quality', 'name' => 'AI Predictive Quality', 'type' => 'page', 'route' => '/quality/analytics/ai',
                    'actions' => [['code' => 'view', 'name' => 'View Predictions'], ['code' => 'run', 'name' => 'Run Prediction']]],
            ],
        ],

        // ==================== 17. SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'Quality Settings',
            'description' => 'Workflows, numbering, approval chains, inspection templates',
            'icon' => 'CogIcon',
            'route' => '/quality/settings',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                ['code' => 'inspection-templates', 'name' => 'Inspection Templates', 'type' => 'page', 'route' => '/quality/settings/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates']]],
                ['code' => 'numbering', 'name' => 'Document Numbering', 'type' => 'page', 'route' => '/quality/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']]],
                ['code' => 'approvals', 'name' => 'Approval Workflows', 'type' => 'page', 'route' => '/quality/settings/approvals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Approvals']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/quality/settings/general',
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
            'quality.inspections' => 'inspections.inspection-request',
            'quality.checklists' => 'inspections.checklists',
            'quality.material_lab' => 'material-lab',
            'quality.ncr' => 'ncr-management.ncr-log',
            'quality.capa' => 'capa.capa-list',
            'quality.spc' => 'spc.control-charts',
            'quality.calibration' => 'calibration.calibration-register',
            'quality.calibration_schedule' => 'calibration.calibration-schedule',
            'quality.calibration_oot' => 'calibration.out-of-tolerance',
            'quality.supplier_quality' => 'supplier-quality.incoming-inspection',
            'quality.audits' => 'audits.audit-plan',
            'quality.asset_commissioning' => 'asset-quality.commissioning-qa',
            'quality.reliability' => 'asset-quality.reliability-checks',
            'quality.fmea' => 'risk-fmea.fmea-register',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_order_qa' => 'aero-eam',
            'ims.incoming_items' => 'aero-ims',
            'scm.vendor_master' => 'aero-scm',
            'iot.sensor_readings' => 'aero-iot',
            'compliance.regulations' => 'aero-compliance',
            'project.ncr_link' => 'aero-project',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'quality_admin_role' => 'quality-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'quality-access'],
    ],
];
