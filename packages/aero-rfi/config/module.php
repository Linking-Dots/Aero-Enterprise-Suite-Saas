<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RFI & Site Intelligence Module
    |--------------------------------------------------------------------------
    |
    | The operational backbone of the project. Manages Daily Site Records,
    | Geo-Fenced Inspections (RFI), and Linear Progress Mapping.
    |
    | ⭐⭐⭐ PATENTABLE FEATURES (3 algorithms):
    | 1. Linear Continuity Validator (⭐⭐⭐⭐⭐ HIGHEST PRIORITY)
    |    - Enforces construction layer prerequisites across chainage ranges
    |    - 95% threshold enforcement prevents structural failures
    |    - Component: linear-continuity-validator (integrated in RFI form)
    |
    | 2. GPS Geo-Fencing Validation (⭐⭐⭐)
    |    - Validates inspector physical presence using Haversine algorithm
    |    - 50m geofence tolerance with real-time distance calculation
    |    - Component: gps-geofencing (integrated in RFI form)
    |
    | 3. Chainage Progress Map / Digital Twin (⭐⭐⭐⭐)
    |    - Spatially-indexed construction ledger visualization
    |    - HTML5 Canvas rendering with multi-layer progress tracking
    |    - Component: digital-twin-map (Rfis page view toggle)
    |
    | All 3 features are production-ready, tested (60% coverage), and
    | demonstrable in the application workflow.
    |
    */

    'code' => 'rfi',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'RFI & Site Intelligence',
    'description' => 'Request For Inspection (construction) + Request For Information / Quote / Proposal + EAM contractor RFI/RFQ workflow, geo-fenced validation, and linear chainage mapping.',
    'version' => '3.0.0',
    'category' => 'engineering_ops',
    'icon' => 'MapIcon',
    'priority' => 15,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('RFI_MODULE_ENABLED', true),
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/rfi',

    /*
    |--------------------------------------------------------------------------
    | Self-Service Navigation Items
    |--------------------------------------------------------------------------
    */
    'self_service' => [
        [
            'code' => 'my-rfis',
            'name' => 'My RFIs',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => '/rfi/my-rfis',
            'priority' => 30,
        ],
        [
            'code' => 'my-inspections',
            'name' => 'My Inspections',
            'icon' => 'MagnifyingGlassIcon',
            'route' => '/rfi/my-inspections',
            'priority' => 31,
        ],
    ],

    'submodules' => [

        // ==================== 1. INTELLIGENT DAILY REPORTING ====================
        [
            'code' => 'daily-reporting',
            'name' => 'Smart Daily Logs',
            'description' => 'Site diaries with automated weather capture and resource verification.',
            'icon' => 'BookOpenIcon',
            'route' => '/rfi/site-diary',
            'priority' => 10,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'site-diary',
                    'name' => 'Site Diary',
                    'description' => 'Daily record of activities, manpower, equipment, materials with weather API integration',
                    'route' => '/rfi/site-diary',
                    'icon' => 'DocumentTextIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'auto_weather', 'name' => 'Fetch Weather', 'description' => 'Auto-log temp/humidity/wind with work suitability calculation'],
                        ['code' => 'import_resources', 'name' => 'Sync Resources', 'description' => 'Import active labor count from access gates'],
                        ['code' => 'upload_photos', 'name' => 'Upload Photos', 'description' => 'Multi-file progress photo upload'],
                        ['code' => 'export_pdf', 'name' => 'Export PDF', 'description' => 'Generate daily summary report'],
                    ],
                ],
                [
                    'code' => 'delay-log',
                    'name' => 'Hindrance Register',
                    'description' => 'Track stoppages caused by external factors (Force Majeure)',
                    'route' => '/rfi/daily/delays',
                    'icon' => 'ClockIcon',
                    'type' => 'page',
                ],
            ],
        ],

        // ==================== 2. GEO-FENCED INSPECTIONS (RFI) ==================== ⭐ PATENTABLE
        [
            'code' => 'inspection-management',
            'name' => 'RFI Management',
            'description' => 'Request for Inspection system with GPS geo-fencing validation (PATENTABLE).',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => '/rfi/rfis',
            'priority' => 20,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'rfi-tracker',
                    'name' => 'RFI Tracker',
                    'description' => 'Central dashboard for all inspection requests',
                    'route' => '/rfi/rfis',
                    'icon' => 'ListBulletIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'create_geolocked', 'name' => 'Create (Geo-Lock)', 'description' => 'GPS validation with 50m geofence tolerance (PATENTABLE)', 'is_patentable' => true],
                        ['code' => 'validate_gps', 'name' => 'Validate GPS', 'description' => 'Haversine distance calculation against expected location (PATENTABLE)', 'is_patentable' => true],
                        ['code' => 'schedule', 'name' => 'Schedule Inspection', 'description' => 'Assign surveyor time slot'],
                        ['code' => 'result_entry', 'name' => 'Enter Result', 'description' => 'Pass/Fail/Pass with Comments'],
                    ],
                ],
                [
                    'code' => 'gps-geofencing',
                    'name' => 'GPS Geo-Fencing', // ⭐ PATENTABLE FEATURE #1
                    'description' => 'Validates inspector physical presence using Haversine algorithm with 50m tolerance',
                    'route' => null, // Integrated in RFI form
                    'icon' => 'MapPinIcon',
                    'type' => 'feature',
                    'is_patentable' => true,
                    'patent_priority' => 2,
                    'actions' => [
                        ['code' => 'capture_gps', 'name' => 'Capture GPS', 'description' => 'Device geolocation capture'],
                        ['code' => 'validate_location', 'name' => 'Validate Location', 'description' => 'Real-time validation against expected coordinates'],
                        ['code' => 'show_map', 'name' => 'Show Map Preview', 'description' => 'Interactive Google Maps with geofence circle'],
                    ],
                ],
                [
                    'code' => 'dynamic-sampling',
                    'name' => 'AI Risk Sampling',
                    'description' => 'Algorithm suggesting high-risk locations for random checks',
                    'route' => null, // Backend service
                    'icon' => 'CpuChipIcon',
                    'type' => 'widget',
                ],
            ],
        ],

        // ==================== 3. LINEAR PROGRESS (CORE IP) ==================== ⭐⭐ PATENTABLE
        [
            'code' => 'linear-progress',
            'name' => 'Linear Topology & Digital Twin',
            'description' => 'Visual mapping of progress along the project alignment with patentable algorithms.',
            'icon' => 'QueueListIcon',
            'route' => '/project/rfis', // Integrated into Rfis page with view toggle
            'priority' => 30,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'digital-twin-map',
                    'name' => 'Digital Twin Map', // ⭐ PATENTABLE FEATURE #3
                    'description' => 'HTML5 Canvas visualization of spatially-indexed construction ledger (PATENTABLE)',
                    'route' => '/project/rfis', // Toggle view on Rfis page
                    'icon' => 'MapIcon',
                    'type' => 'canvas',
                    'is_patentable' => true,
                    'patent_priority' => 3,
                    'actions' => [
                        ['code' => 'visualize_layer', 'name' => 'Toggle Layers', 'description' => 'Filter view by construction layer'],
                        ['code' => 'zoom_control', 'name' => 'Zoom Controls', 'description' => '0.5x to 3x magnification with pan'],
                        ['code' => 'export_strip', 'name' => 'Export Strip Map', 'description' => 'Generate PDF Strip Chart (PATENTABLE)', 'is_patentable' => true],
                        ['code' => 'click_segment', 'name' => 'Click Segment', 'description' => 'View RFI details for chainage segment'],
                    ],
                ],
                [
                    'code' => 'linear-continuity-validator',
                    'name' => 'Linear Continuity Validator', // ⭐ PATENTABLE FEATURE #2 (HIGHEST PRIORITY)
                    'description' => 'Enforces construction layer prerequisites across chainage ranges with 95% threshold (PATENTABLE)',
                    'route' => null, // Integrated in RFI form
                    'icon' => 'VariableIcon',
                    'type' => 'feature',
                    'is_patentable' => true,
                    'patent_priority' => 1, // HIGHEST PRIORITY
                    'actions' => [
                        ['code' => 'check_continuity', 'name' => 'Check Continuity', 'description' => 'Validate prerequisite layer coverage ≥95%'],
                        ['code' => 'detect_gaps', 'name' => 'Detect Gaps', 'description' => 'Identify missing RFI segments with chainage locations'],
                        ['code' => 'show_dependencies', 'name' => 'Show Dependencies', 'description' => 'Visual layer hierarchy with coverage percentages'],
                        ['code' => 'block_submission', 'name' => 'Block Submission', 'description' => 'Prevent RFI approval if prerequisites incomplete'],
                    ],
                ],
                [
                    'code' => 'gap-analysis',
                    'name' => 'Chainage Gap Analysis',
                    'description' => 'Spatial prerequisite validation and optimal work location suggestions',
                    'route' => null, // Backend service
                    'icon' => 'ChartBarIcon',
                    'type' => 'service',
                ],
            ],
        ],

        // ==================== 4. ISSUE RESOLUTION ====================
        [
            'code' => 'objections',
            'name' => 'Objections & Disputes',
            'description' => 'Formal handling of rejected works and consultant objections.',
            'icon' => 'ExclamationCircleIcon',
            'route' => '/rfi/objections',
            'priority' => 40,
            'is_active' => true,

            'components' => [
                [
                    'code' => 'objection-handler',
                    'name' => 'Objection Log',
                    'description' => 'Track reasons for rejection and required remedial actions',
                    'route' => '/rfi/objections',
                    'icon' => 'XMarkIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'convert_to_ncr', 'name' => 'Escalate to NCR', 'description' => 'Move serious issues to Quality Module'],
                        ['code' => 'resubmit', 'name' => 'Resubmit RFI', 'description' => 'Link new RFI to rejected parent'],
                    ],
                ],
            ],
        ],

        // ==================== 5. REQUEST FOR INFORMATION (Engineering) ====================
        [
            'code' => 'rfi-information',
            'name' => 'Request For Information (RFI)',
            'description' => 'Engineering/design clarifications, consultant queries, response tracking.',
            'icon' => 'QuestionMarkCircleIcon',
            'route' => '/rfi/information',
            'priority' => 50,
            'is_active' => true,
            'components' => [
                ['code' => 'rfi-list', 'name' => 'RFI List', 'type' => 'page', 'route' => '/rfi/information',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RFIs'],
                        ['code' => 'create', 'name' => 'Create RFI'],
                        ['code' => 'update', 'name' => 'Update RFI'],
                        ['code' => 'send', 'name' => 'Send to Consultant'],
                        ['code' => 'respond', 'name' => 'Capture Response'],
                        ['code' => 'close', 'name' => 'Close RFI'],
                    ]],
                ['code' => 'rfi-log', 'name' => 'RFI Log', 'type' => 'page', 'route' => '/rfi/information/log',
                    'actions' => [['code' => 'view', 'name' => 'View Log'], ['code' => 'export', 'name' => 'Export Log']]],
            ],
        ],

        // ==================== 6. RFQ (Request For Quote) ====================
        [
            'code' => 'rfq',
            'name' => 'Request For Quote (RFQ)',
            'description' => 'Procurement RFQ to vendors, quote comparison, award.',
            'icon' => 'DocumentCurrencyDollarIcon',
            'route' => '/rfi/rfq',
            'priority' => 60,
            'is_active' => true,
            'components' => [
                ['code' => 'rfq-list', 'name' => 'RFQs', 'type' => 'page', 'route' => '/rfi/rfq',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RFQs'],
                        ['code' => 'create', 'name' => 'Create RFQ'],
                        ['code' => 'send', 'name' => 'Send to Vendors'],
                        ['code' => 'compare', 'name' => 'Compare Quotes'],
                        ['code' => 'award', 'name' => 'Award RFQ'],
                        ['code' => 'reject', 'name' => 'Reject RFQ'],
                    ]],
                ['code' => 'quote-comparison', 'name' => 'Quote Comparison', 'type' => 'page', 'route' => '/rfi/rfq/compare',
                    'actions' => [['code' => 'view', 'name' => 'View Comparison']]],
            ],
        ],

        // ==================== 7. RFP (Request For Proposal) ====================
        [
            'code' => 'rfp',
            'name' => 'Request For Proposal (RFP)',
            'description' => 'Strategic proposals with multi-criteria evaluation.',
            'icon' => 'DocumentTextIcon',
            'route' => '/rfi/rfp',
            'priority' => 70,
            'is_active' => true,
            'components' => [
                ['code' => 'rfp-list', 'name' => 'RFPs', 'type' => 'page', 'route' => '/rfi/rfp',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RFPs'],
                        ['code' => 'create', 'name' => 'Create RFP'],
                        ['code' => 'publish', 'name' => 'Publish RFP'],
                        ['code' => 'evaluate', 'name' => 'Evaluate Proposals'],
                        ['code' => 'award', 'name' => 'Award RFP'],
                    ]],
                ['code' => 'evaluation-scoring', 'name' => 'Evaluation Scoring', 'type' => 'page', 'route' => '/rfi/rfp/evaluation',
                    'actions' => [['code' => 'score', 'name' => 'Score Proposal'], ['code' => 'finalize', 'name' => 'Finalize Evaluation']]],
            ],
        ],

        // ==================== 8. CONTRACTOR RFI (EAM) ====================
        [
            'code' => 'contractor-rfi',
            'name' => 'Contractor RFI / Work Request (EAM)',
            'description' => 'Contractors raise RFIs/work requests against EAM work orders or assets.',
            'icon' => 'WrenchScrewdriverIcon',
            'route' => '/rfi/contractor',
            'priority' => 80,
            'is_active' => true,
            'components' => [
                ['code' => 'contractor-work-request', 'name' => 'Contractor Work Request', 'type' => 'page', 'route' => '/rfi/contractor/work-request',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Work Requests'],
                        ['code' => 'create', 'name' => 'Create Work Request'],
                        ['code' => 'approve', 'name' => 'Approve Work Request'],
                        ['code' => 'reject', 'name' => 'Reject Work Request'],
                        ['code' => 'convert-to-wo', 'name' => 'Convert to Work Order'],
                    ]],
                ['code' => 'contractor-rfi', 'name' => 'Contractor RFI', 'type' => 'page', 'route' => '/rfi/contractor/rfi',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Contractor RFIs'],
                        ['code' => 'create', 'name' => 'Create Contractor RFI'],
                        ['code' => 'respond', 'name' => 'Respond to RFI'],
                    ]],
            ],
        ],

        // ==================== 9. SUBMITTALS & TRANSMITTALS ====================
        [
            'code' => 'submittals-transmittals',
            'name' => 'Submittals & Transmittals',
            'description' => 'Material submittals, shop drawings, document transmittals.',
            'icon' => 'EnvelopeOpenIcon',
            'route' => '/rfi/submittals',
            'priority' => 90,
            'is_active' => true,
            'components' => [
                ['code' => 'submittals', 'name' => 'Submittals', 'type' => 'page', 'route' => '/rfi/submittals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Submittals'],
                        ['code' => 'create', 'name' => 'Create Submittal'],
                        ['code' => 'review', 'name' => 'Review Submittal'],
                        ['code' => 'approve', 'name' => 'Approve Submittal'],
                        ['code' => 'reject', 'name' => 'Reject Submittal'],
                    ]],
                ['code' => 'transmittals', 'name' => 'Transmittals', 'type' => 'page', 'route' => '/rfi/transmittals',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Transmittals'],
                        ['code' => 'create', 'name' => 'Create Transmittal'],
                        ['code' => 'send', 'name' => 'Send Transmittal'],
                    ]],
            ],
        ],

        // ==================== 10. PUNCH LIST ====================
        [
            'code' => 'punch-list',
            'name' => 'Punch List',
            'description' => 'Final snag list with geo-tagged items for completion.',
            'icon' => 'CheckBadgeIcon',
            'route' => '/rfi/punch-list',
            'priority' => 100,
            'is_active' => true,
            'components' => [
                ['code' => 'punch-items', 'name' => 'Punch Items', 'type' => 'page', 'route' => '/rfi/punch-list',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Punch Items'],
                        ['code' => 'create', 'name' => 'Add Punch Item'],
                        ['code' => 'assign', 'name' => 'Assign to Contractor'],
                        ['code' => 'close', 'name' => 'Close Punch Item'],
                        ['code' => 'verify', 'name' => 'Verify Completion'],
                    ]],
            ],
        ],

        // ==================== 11. REPORTS & ANALYTICS ====================
        [
            'code' => 'reports-analytics',
            'name' => 'Reports & Analytics',
            'description' => 'Site KPIs, RFI turnaround, trend analysis.',
            'icon' => 'ChartBarIcon',
            'route' => '/rfi/reports',
            'priority' => 110,
            'is_active' => true,
            'components' => [
                ['code' => 'kpis', 'name' => 'Site KPIs', 'type' => 'page', 'route' => '/rfi/reports/kpis',
                    'actions' => [['code' => 'view', 'name' => 'View KPIs'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'rfi-turnaround', 'name' => 'RFI Turnaround Analytics', 'type' => 'page', 'route' => '/rfi/reports/turnaround',
                    'actions' => [['code' => 'view', 'name' => 'View Turnaround']]],
                ['code' => 'custom-reports', 'name' => 'Custom Reports', 'type' => 'page', 'route' => '/rfi/reports/custom',
                    'actions' => [['code' => 'create', 'name' => 'Create Report'], ['code' => 'export', 'name' => 'Export Report']]],
            ],
        ],

        // ==================== 12. SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'RFI Settings',
            'description' => 'Geofence tolerance, workflow config, templates.',
            'icon' => 'CogIcon',
            'route' => '/rfi/settings',
            'priority' => 999,
            'is_active' => true,
            'components' => [
                ['code' => 'geofence-config', 'name' => 'Geofence Configuration', 'type' => 'page', 'route' => '/rfi/settings/geofence',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Geofence']]],
                ['code' => 'workflow-config', 'name' => 'Workflow Configuration', 'type' => 'page', 'route' => '/rfi/settings/workflow',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows']]],
                ['code' => 'templates', 'name' => 'RFI / RFQ / RFP Templates', 'type' => 'page', 'route' => '/rfi/settings/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/rfi/settings/general',
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
            'rfi.inspection' => 'inspection-management.rfi-tracker',
            'rfi.information' => 'rfi-information.rfi-list',
            'rfi.contractor_work_request' => 'contractor-rfi.contractor-work-request',
            'rfi.contractor_rfi' => 'contractor-rfi.contractor-rfi',
            'rfi.rfq' => 'rfq.rfq-list',
            'rfi.rfp' => 'rfp.rfp-list',
            'rfi.submittals' => 'submittals-transmittals.submittals',
            'rfi.punch_list' => 'punch-list.punch-items',
            'rfi.digital_twin' => 'linear-progress.digital-twin-map',
            'rfi.continuity_validator' => 'linear-progress.linear-continuity-validator',
            'rfi.site_diary' => 'daily-reporting.site-diary',
            'rfi.objections' => 'objections.objection-handler',
        ],
        'consumes' => [
            'eam.work_orders' => 'aero-eam',
            'eam.asset_registry' => 'aero-eam',
            'quality.ncr' => 'aero-quality',
            'scm.vendors' => 'aero-scm',
            'scm.contractors' => 'aero-scm',
            'project.projects' => 'aero-project',
            'compliance.permits' => 'aero-compliance',
            'hrm.workforce' => 'aero-hrm',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'rfi_admin_role' => 'rfi-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'rfi-access'],
    ],
];
