<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enterprise Asset Management (EAM) Module
    |--------------------------------------------------------------------------
    | The central EAM orchestrator. Owns the asset registry, hierarchy, work
    | orders, preventive/predictive maintenance schedules, and reliability
    | engineering. Integrates cross-package with:
    |
    |   - aero-finance     (depreciation, CapEx, asset financial records)
    |   - aero-scm         (MRO procurement, contractors, service contracts)
    |   - aero-ims         (spare parts, stock reservations, WO kits)
    |   - aero-iot         (condition monitoring, predictive maintenance)
    |   - aero-hrm         (technician scheduling, certifications, safety)
    |   - aero-quality     (calibration, inspections, QA commissioning)
    |   - aero-compliance  (PTW, LOTO, statutory inspections, HSE)
    |   - aero-real-estate (facility-level assets, utilities, spaces)
    |   - aero-crm         (customer assets, service contracts, warranties)
    |   - aero-project     (CapEx projects, turnaround/shutdown)
    |   - aero-dms         (asset manuals, drawings, O&M, certificates)
    |   - aero-blockchain  (asset provenance, chain of custody)
    |   - aero-assistant   (EAM-aware AI agent, work-order copilot)
    |   - aero-rfi         (contractor work requests, inspections)
    |
    | Hierarchy: Module → SubModule → Component → Action
    */

    'code' => 'eam',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Enterprise Asset Management',
    'description' => 'Central EAM: asset registry, hierarchy, work orders, preventive/predictive maintenance, reliability engineering, failure codes, and cross-package EAM orchestration.',
    'icon' => 'WrenchScrewdriverIcon',
    'route_prefix' => '/eam',
    'category' => 'business',
    'priority' => 11,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('EAM_MODULE_ENABLED', true),
    'version' => '1.0.0',
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'dashboard' => true,
        'asset_registry' => true,
        'asset_hierarchy' => true,
        'asset_lifecycle' => true,
        'asset_criticality' => true,
        'asset_bom' => true,
        'locations_sites' => true,
        'failure_codes' => true,
        'work_orders' => true,
        'work_requests' => true,
        'preventive_maintenance' => true,
        'predictive_maintenance' => true,
        'corrective_maintenance' => true,
        'condition_based_maint' => true,
        'turnaround_shutdown' => true,
        'reliability_engineering' => true,
        'rcm' => true,
        'fmeca' => true,
        'mtbf_mttr_kpis' => true,
        'inspections' => true,
        'rounds_routes' => true,
        'checklists' => true,
        'labor_crafts' => true,
        'costs_budgets' => true,
        'warranty' => true,
        'meter_readings' => true,
        'spare_parts_bridge' => true,
        'mobile_app' => true,
        'barcode_qr' => true,
        'reports_analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'self_service' => [
        ['code' => 'my-work-orders', 'name' => 'My Work Orders', 'icon' => 'WrenchIcon', 'route' => '/eam/my-work-orders', 'priority' => 40],
        ['code' => 'my-work-requests', 'name' => 'My Work Requests', 'icon' => 'DocumentPlusIcon', 'route' => '/eam/my-work-requests', 'priority' => 41],
        ['code' => 'report-issue', 'name' => 'Report Asset Issue', 'icon' => 'ExclamationTriangleIcon', 'route' => '/eam/report-issue', 'priority' => 42],
    ],

    'submodules' => [

        // ==================== 0. DASHBOARD ====================
        [
            'code' => 'dashboard', 'name' => 'EAM Dashboard',
            'description' => 'Asset health, WO backlog, MTBF/MTTR, PM compliance',
            'icon' => 'HomeIcon', 'route' => '/eam/dashboard', 'priority' => 0,
            'components' => [
                ['code' => 'eam-dashboard', 'name' => 'EAM Dashboard', 'type' => 'page', 'route' => '/eam/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
                ['code' => 'asset-health-dashboard', 'name' => 'Asset Health Dashboard', 'type' => 'page', 'route' => '/eam/dashboard/asset-health',
                    'actions' => [['code' => 'view', 'name' => 'View Asset Health']]],
                ['code' => 'maintenance-kpis', 'name' => 'Maintenance KPIs', 'type' => 'page', 'route' => '/eam/dashboard/kpis',
                    'actions' => [['code' => 'view', 'name' => 'View KPIs'], ['code' => 'export', 'name' => 'Export KPIs']]],
            ],
        ],

        // ==================== 1. ASSET REGISTRY ====================
        [
            'code' => 'asset-registry', 'name' => 'Asset Registry',
            'description' => 'Master asset records with specs, attributes, media, docs',
            'icon' => 'CubeIcon', 'route' => '/eam/assets', 'priority' => 1,
            'components' => [
                ['code' => 'assets', 'name' => 'Assets', 'type' => 'page', 'route' => '/eam/assets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Assets'],
                        ['code' => 'create', 'name' => 'Create Asset'],
                        ['code' => 'update', 'name' => 'Update Asset'],
                        ['code' => 'delete', 'name' => 'Delete Asset'],
                        ['code' => 'clone', 'name' => 'Clone Asset'],
                        ['code' => 'import', 'name' => 'Import Assets'],
                        ['code' => 'export', 'name' => 'Export Assets'],
                        ['code' => 'print-label', 'name' => 'Print QR / Barcode'],
                        ['code' => 'transfer', 'name' => 'Transfer Asset'],
                        ['code' => 'retire', 'name' => 'Retire Asset'],
                    ]],
                ['code' => 'asset-types', 'name' => 'Asset Types & Classes', 'type' => 'page', 'route' => '/eam/assets/types',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Types']]],
                ['code' => 'asset-specifications', 'name' => 'Specifications / Attributes', 'type' => 'page', 'route' => '/eam/assets/specifications',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Specifications']]],
                ['code' => 'asset-media', 'name' => 'Asset Media & Photos', 'type' => 'page', 'route' => '/eam/assets/media',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Media']]],
                ['code' => 'asset-documents', 'name' => 'Asset Documents (DMS Link)', 'type' => 'page', 'route' => '/eam/assets/documents',
                    'actions' => [['code' => 'view', 'name' => 'View Documents'], ['code' => 'link', 'name' => 'Link Document']]],
                ['code' => 'asset-timeline', 'name' => 'Asset Timeline / 360 View', 'type' => 'page', 'route' => '/eam/assets/timeline',
                    'actions' => [['code' => 'view', 'name' => 'View Timeline']]],
            ],
        ],

        // ==================== 2. ASSET HIERARCHY ====================
        [
            'code' => 'asset-hierarchy', 'name' => 'Asset Hierarchy',
            'description' => 'Functional location tree, system hierarchy (ISO 14224)',
            'icon' => 'RectangleStackIcon', 'route' => '/eam/hierarchy', 'priority' => 2,
            'components' => [
                ['code' => 'functional-locations', 'name' => 'Functional Locations', 'type' => 'page', 'route' => '/eam/hierarchy/locations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Functional Locations'],
                        ['code' => 'create', 'name' => 'Create Location'],
                        ['code' => 'update', 'name' => 'Update Location'],
                        ['code' => 'delete', 'name' => 'Delete Location'],
                    ]],
                ['code' => 'hierarchy-tree', 'name' => 'Hierarchy Tree', 'type' => 'page', 'route' => '/eam/hierarchy/tree',
                    'actions' => [['code' => 'view', 'name' => 'View Hierarchy'], ['code' => 'reorganize', 'name' => 'Reorganize Hierarchy']]],
                ['code' => 'parent-child', 'name' => 'Parent-Child Relationships', 'type' => 'page', 'route' => '/eam/hierarchy/parent-child',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Relationships']]],
                ['code' => 'sites-plants', 'name' => 'Sites / Plants / Facilities', 'type' => 'page', 'route' => '/eam/hierarchy/sites',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Sites']]],
            ],
        ],

        // ==================== 3. ASSET LIFECYCLE ====================
        [
            'code' => 'asset-lifecycle', 'name' => 'Asset Lifecycle',
            'description' => 'Commissioning, operation, decommissioning, disposal',
            'icon' => 'ArrowPathIcon', 'route' => '/eam/lifecycle', 'priority' => 3,
            'components' => [
                ['code' => 'commissioning', 'name' => 'Commissioning', 'type' => 'page', 'route' => '/eam/lifecycle/commissioning',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Commissioning'],
                        ['code' => 'initiate', 'name' => 'Initiate Commissioning'],
                        ['code' => 'sign-off', 'name' => 'Sign Off Commissioning'],
                    ]],
                ['code' => 'lifecycle-states', 'name' => 'Lifecycle State Transitions', 'type' => 'page', 'route' => '/eam/lifecycle/states',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View States'],
                        ['code' => 'transition', 'name' => 'Transition State'],
                    ]],
                ['code' => 'decommissioning', 'name' => 'Decommissioning', 'type' => 'page', 'route' => '/eam/lifecycle/decommissioning',
                    'actions' => [['code' => 'initiate', 'name' => 'Initiate Decommissioning'], ['code' => 'complete', 'name' => 'Complete Decommissioning']]],
                ['code' => 'disposal', 'name' => 'Disposal (→ Finance retirement)', 'type' => 'page', 'route' => '/eam/lifecycle/disposal',
                    'actions' => [['code' => 'request', 'name' => 'Request Disposal'], ['code' => 'execute', 'name' => 'Execute Disposal']]],
            ],
        ],

        // ==================== 4. ASSET CRITICALITY ====================
        [
            'code' => 'asset-criticality', 'name' => 'Asset Criticality & BOM',
            'description' => 'Criticality analysis, asset BOM, redundancy',
            'icon' => 'StarIcon', 'route' => '/eam/criticality', 'priority' => 4,
            'components' => [
                ['code' => 'criticality-analysis', 'name' => 'Criticality Analysis', 'type' => 'page', 'route' => '/eam/criticality',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Criticality'],
                        ['code' => 'classify', 'name' => 'Classify Asset'],
                        ['code' => 'run-analysis', 'name' => 'Run Criticality Analysis'],
                    ]],
                ['code' => 'asset-bom', 'name' => 'Asset Bill of Materials', 'type' => 'page', 'route' => '/eam/criticality/bom',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View BOM'],
                        ['code' => 'manage', 'name' => 'Manage BOM'],
                        ['code' => 'link-spares', 'name' => 'Link to IMS Spares'],
                    ]],
                ['code' => 'redundancy', 'name' => 'Redundancy & Sparing', 'type' => 'page', 'route' => '/eam/criticality/redundancy',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Redundancy']]],
            ],
        ],

        // ==================== 5. FAILURE CODES (ISO 14224) ====================
        [
            'code' => 'failure-codes', 'name' => 'Failure Codes & Taxonomy',
            'description' => 'ISO 14224 failure taxonomy: problem / cause / action codes',
            'icon' => 'ExclamationTriangleIcon', 'route' => '/eam/failure-codes', 'priority' => 5,
            'components' => [
                ['code' => 'problem-codes', 'name' => 'Problem Codes', 'type' => 'page', 'route' => '/eam/failure-codes/problems',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Problem Codes']]],
                ['code' => 'cause-codes', 'name' => 'Cause Codes', 'type' => 'page', 'route' => '/eam/failure-codes/causes',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cause Codes']]],
                ['code' => 'action-codes', 'name' => 'Action Codes', 'type' => 'page', 'route' => '/eam/failure-codes/actions',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Action Codes']]],
                ['code' => 'failure-modes', 'name' => 'Failure Modes (FMEA Link)', 'type' => 'page', 'route' => '/eam/failure-codes/modes',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Failure Modes']]],
            ],
        ],

        // ==================== 6. WORK ORDERS ====================
        [
            'code' => 'work-orders', 'name' => 'Work Orders',
            'description' => 'Central work-order lifecycle (create → plan → schedule → execute → close)',
            'icon' => 'WrenchIcon', 'route' => '/eam/work-orders', 'priority' => 6,
            'components' => [
                ['code' => 'wo-list', 'name' => 'Work Order List', 'type' => 'page', 'route' => '/eam/work-orders',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Work Orders'],
                        ['code' => 'create', 'name' => 'Create Work Order'],
                        ['code' => 'update', 'name' => 'Update Work Order'],
                        ['code' => 'delete', 'name' => 'Delete Work Order'],
                        ['code' => 'approve', 'name' => 'Approve Work Order'],
                        ['code' => 'assign', 'name' => 'Assign Technician / Crew'],
                        ['code' => 'schedule', 'name' => 'Schedule Work Order'],
                        ['code' => 'start', 'name' => 'Start Work Order'],
                        ['code' => 'pause', 'name' => 'Pause Work Order'],
                        ['code' => 'complete', 'name' => 'Complete Work Order'],
                        ['code' => 'close', 'name' => 'Close Work Order'],
                        ['code' => 'cancel', 'name' => 'Cancel Work Order'],
                        ['code' => 'print', 'name' => 'Print Work Order'],
                        ['code' => 'export', 'name' => 'Export Work Orders'],
                    ]],
                ['code' => 'wo-types', 'name' => 'Work Order Types (PM/CM/CBM/Emergency)', 'type' => 'page', 'route' => '/eam/work-orders/types',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Types']]],
                ['code' => 'wo-tasks', 'name' => 'Tasks & Sub-Tasks', 'type' => 'page', 'route' => '/eam/work-orders/tasks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tasks'],
                        ['code' => 'create', 'name' => 'Create Task'],
                        ['code' => 'complete', 'name' => 'Complete Task'],
                    ]],
                ['code' => 'wo-labor', 'name' => 'Labor & Time', 'type' => 'page', 'route' => '/eam/work-orders/labor',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Labor'],
                        ['code' => 'log-time', 'name' => 'Log Time'],
                    ]],
                ['code' => 'wo-materials', 'name' => 'Materials (IMS Issue)', 'type' => 'page', 'route' => '/eam/work-orders/materials',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Materials'],
                        ['code' => 'request', 'name' => 'Request Materials'],
                        ['code' => 'issue', 'name' => 'Issue Materials'],
                        ['code' => 'return', 'name' => 'Return Materials'],
                    ]],
                ['code' => 'wo-tools', 'name' => 'Tools Required', 'type' => 'page', 'route' => '/eam/work-orders/tools',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tool Requirements']]],
                ['code' => 'wo-permits', 'name' => 'Permits & LOTO (Compliance Link)', 'type' => 'page', 'route' => '/eam/work-orders/permits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Permits'],
                        ['code' => 'attach-permit', 'name' => 'Attach PTW'],
                        ['code' => 'loto-isolate', 'name' => 'Apply LOTO'],
                        ['code' => 'loto-release', 'name' => 'Release LOTO'],
                    ]],
                ['code' => 'wo-attachments', 'name' => 'Attachments & Photos', 'type' => 'page', 'route' => '/eam/work-orders/attachments',
                    'actions' => [['code' => 'upload', 'name' => 'Upload'], ['code' => 'view', 'name' => 'View']]],
                ['code' => 'wo-failure-reporting', 'name' => 'Failure Reporting (ISO 14224)', 'type' => 'page', 'route' => '/eam/work-orders/failure-reporting',
                    'actions' => [['code' => 'record', 'name' => 'Record Failure']]],
                ['code' => 'wo-closure', 'name' => 'Closure & Sign-Off', 'type' => 'page', 'route' => '/eam/work-orders/closure',
                    'actions' => [['code' => 'sign-off', 'name' => 'Sign Off Closure']]],
            ],
        ],

        // ==================== 7. WORK REQUESTS ====================
        [
            'code' => 'work-requests', 'name' => 'Work Requests',
            'description' => 'Work requests from operators / tenants / customers before WO approval',
            'icon' => 'DocumentPlusIcon', 'route' => '/eam/work-requests', 'priority' => 7,
            'components' => [
                ['code' => 'wr-list', 'name' => 'Work Requests', 'type' => 'page', 'route' => '/eam/work-requests',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Work Requests'],
                        ['code' => 'create', 'name' => 'Submit Work Request'],
                        ['code' => 'update', 'name' => 'Update Work Request'],
                        ['code' => 'approve', 'name' => 'Approve Work Request'],
                        ['code' => 'reject', 'name' => 'Reject Work Request'],
                        ['code' => 'convert-to-wo', 'name' => 'Convert to Work Order'],
                    ]],
                ['code' => 'wr-triage', 'name' => 'Triage & Prioritization', 'type' => 'page', 'route' => '/eam/work-requests/triage',
                    'actions' => [['code' => 'triage', 'name' => 'Triage Work Request']]],
            ],
        ],

        // ==================== 8. PREVENTIVE MAINTENANCE ====================
        [
            'code' => 'preventive-maintenance', 'name' => 'Preventive Maintenance (PM)',
            'description' => 'PM schedules, time/usage-based triggers, auto-generated WOs',
            'icon' => 'CalendarDaysIcon', 'route' => '/eam/pm', 'priority' => 8,
            'components' => [
                ['code' => 'pm-schedules', 'name' => 'PM Schedules', 'type' => 'page', 'route' => '/eam/pm/schedules',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Schedules'],
                        ['code' => 'create', 'name' => 'Create Schedule'],
                        ['code' => 'update', 'name' => 'Update Schedule'],
                        ['code' => 'delete', 'name' => 'Delete Schedule'],
                        ['code' => 'pause', 'name' => 'Pause Schedule'],
                        ['code' => 'resume', 'name' => 'Resume Schedule'],
                    ]],
                ['code' => 'pm-templates', 'name' => 'PM Templates & Job Plans', 'type' => 'page', 'route' => '/eam/pm/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Job Plan Templates']]],
                ['code' => 'pm-generation', 'name' => 'PM Generation (Auto-WO)', 'type' => 'page', 'route' => '/eam/pm/generation',
                    'actions' => [['code' => 'run', 'name' => 'Run PM Generation'], ['code' => 'view-forecast', 'name' => 'View PM Forecast']]],
                ['code' => 'pm-compliance', 'name' => 'PM Compliance', 'type' => 'page', 'route' => '/eam/pm/compliance',
                    'actions' => [['code' => 'view', 'name' => 'View PM Compliance'], ['code' => 'export', 'name' => 'Export PM Compliance']]],
            ],
        ],

        // ==================== 9. PREDICTIVE MAINTENANCE ====================
        [
            'code' => 'predictive-maintenance', 'name' => 'Predictive Maintenance (PdM)',
            'description' => 'IoT-driven predictions, RUL, ML models → auto-WO (consumes aero-iot)',
            'icon' => 'SparklesIcon', 'route' => '/eam/pdm', 'priority' => 9,
            'components' => [
                ['code' => 'pdm-dashboard', 'name' => 'PdM Dashboard', 'type' => 'page', 'route' => '/eam/pdm/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View PdM Dashboard']]],
                ['code' => 'pdm-triggers', 'name' => 'PdM Triggers (from IoT)', 'type' => 'page', 'route' => '/eam/pdm/triggers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Triggers'],
                        ['code' => 'acknowledge', 'name' => 'Acknowledge Trigger'],
                        ['code' => 'create-wo', 'name' => 'Create Work Order from Trigger'],
                    ]],
                ['code' => 'pdm-recommendations', 'name' => 'Prescriptive Recommendations', 'type' => 'page', 'route' => '/eam/pdm/recommendations',
                    'actions' => [['code' => 'view', 'name' => 'View Recommendations'], ['code' => 'act', 'name' => 'Act on Recommendation']]],
            ],
        ],

        // ==================== 10. CORRECTIVE / CBM ====================
        [
            'code' => 'corrective-cbm', 'name' => 'Corrective & Condition-Based',
            'description' => 'Breakdown (CM), emergency, condition-based maintenance (CBM)',
            'icon' => 'ExclamationTriangleIcon', 'route' => '/eam/corrective-cbm', 'priority' => 10,
            'components' => [
                ['code' => 'breakdown', 'name' => 'Breakdowns (CM)', 'type' => 'page', 'route' => '/eam/corrective-cbm/breakdown',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Breakdowns'],
                        ['code' => 'report', 'name' => 'Report Breakdown'],
                        ['code' => 'escalate', 'name' => 'Escalate'],
                    ]],
                ['code' => 'cbm-rules', 'name' => 'CBM Rules (from IoT)', 'type' => 'page', 'route' => '/eam/corrective-cbm/cbm',
                    'actions' => [['code' => 'manage', 'name' => 'Manage CBM Rules']]],
                ['code' => 'emergency-wo', 'name' => 'Emergency Work Orders', 'type' => 'page', 'route' => '/eam/corrective-cbm/emergency',
                    'actions' => [['code' => 'view', 'name' => 'View Emergency WOs'], ['code' => 'dispatch', 'name' => 'Dispatch Emergency']]],
            ],
        ],

        // ==================== 11. TURNAROUND / SHUTDOWN (→ Project link) ====================
        [
            'code' => 'turnaround-shutdown', 'name' => 'Turnaround / Shutdown',
            'description' => 'Major maintenance events: scope freeze, WO bundling, link to aero-project',
            'icon' => 'ArrowPathRoundedSquareIcon', 'route' => '/eam/turnaround', 'priority' => 11,
            'components' => [
                ['code' => 'turnaround-list', 'name' => 'Turnaround Events', 'type' => 'page', 'route' => '/eam/turnaround',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Turnarounds'],
                        ['code' => 'create', 'name' => 'Create Turnaround'],
                        ['code' => 'freeze-scope', 'name' => 'Freeze Scope'],
                        ['code' => 'execute', 'name' => 'Execute Turnaround'],
                        ['code' => 'close', 'name' => 'Close Turnaround'],
                        ['code' => 'link-project', 'name' => 'Link to Project'],
                    ]],
                ['code' => 'scope-management', 'name' => 'Scope Management', 'type' => 'page', 'route' => '/eam/turnaround/scope',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Scope']]],
            ],
        ],

        // ==================== 12. RELIABILITY ENGINEERING ====================
        [
            'code' => 'reliability', 'name' => 'Reliability Engineering',
            'description' => 'RCM, FMECA, Weibull analysis, MTBF/MTTR, bad-actor analytics',
            'icon' => 'ShieldCheckIcon', 'route' => '/eam/reliability', 'priority' => 12,
            'components' => [
                ['code' => 'rcm', 'name' => 'Reliability-Centered Maintenance (RCM)', 'type' => 'page', 'route' => '/eam/reliability/rcm',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RCM Studies'],
                        ['code' => 'create', 'name' => 'Create RCM Study'],
                        ['code' => 'update', 'name' => 'Update RCM Study'],
                        ['code' => 'approve', 'name' => 'Approve RCM Study'],
                    ]],
                ['code' => 'fmeca', 'name' => 'FMECA', 'type' => 'page', 'route' => '/eam/reliability/fmeca',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View FMECA'],
                        ['code' => 'create', 'name' => 'Create FMECA'],
                        ['code' => 'calculate-rpn', 'name' => 'Calculate RPN'],
                    ]],
                ['code' => 'weibull', 'name' => 'Weibull Analysis', 'type' => 'page', 'route' => '/eam/reliability/weibull',
                    'actions' => [['code' => 'run', 'name' => 'Run Analysis'], ['code' => 'view', 'name' => 'View Results']]],
                ['code' => 'mtbf-mttr', 'name' => 'MTBF / MTTR Analytics', 'type' => 'page', 'route' => '/eam/reliability/mtbf-mttr',
                    'actions' => [['code' => 'view', 'name' => 'View MTBF/MTTR']]],
                ['code' => 'bad-actors', 'name' => 'Bad Actor Analytics', 'type' => 'page', 'route' => '/eam/reliability/bad-actors',
                    'actions' => [['code' => 'view', 'name' => 'View Bad Actors']]],
                ['code' => 'root-cause', 'name' => 'Root Cause Analysis (RCA)', 'type' => 'page', 'route' => '/eam/reliability/rca',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RCAs'],
                        ['code' => 'create', 'name' => 'Open RCA'],
                        ['code' => 'close', 'name' => 'Close RCA'],
                    ]],
            ],
        ],

        // ==================== 13. INSPECTIONS & ROUNDS ====================
        [
            'code' => 'inspections-rounds', 'name' => 'Inspections & Operator Rounds',
            'description' => 'Operator rounds, inspection routes, mobile checklists',
            'icon' => 'ClipboardDocumentCheckIcon', 'route' => '/eam/inspections', 'priority' => 13,
            'components' => [
                ['code' => 'inspection-list', 'name' => 'Inspection Schedules', 'type' => 'page', 'route' => '/eam/inspections',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inspections'],
                        ['code' => 'create', 'name' => 'Schedule Inspection'],
                        ['code' => 'conduct', 'name' => 'Conduct Inspection'],
                    ]],
                ['code' => 'operator-rounds', 'name' => 'Operator Rounds', 'type' => 'page', 'route' => '/eam/inspections/rounds',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rounds'],
                        ['code' => 'start', 'name' => 'Start Round'],
                        ['code' => 'complete', 'name' => 'Complete Round'],
                    ]],
                ['code' => 'routes', 'name' => 'Inspection Routes', 'type' => 'page', 'route' => '/eam/inspections/routes',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Routes']]],
                ['code' => 'checklists', 'name' => 'Inspection Checklists', 'type' => 'page', 'route' => '/eam/inspections/checklists',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Checklists']]],
            ],
        ],

        // ==================== 14. METER READINGS ====================
        [
            'code' => 'meter-readings', 'name' => 'Meter Readings',
            'description' => 'Manual and IoT-driven meter readings for usage-based PM',
            'icon' => 'ChartBarSquareIcon', 'route' => '/eam/meters', 'priority' => 14,
            'components' => [
                ['code' => 'meter-register', 'name' => 'Meter Register', 'type' => 'page', 'route' => '/eam/meters',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Meters'],
                        ['code' => 'create', 'name' => 'Add Meter'],
                        ['code' => 'update', 'name' => 'Update Meter'],
                    ]],
                ['code' => 'readings', 'name' => 'Readings', 'type' => 'page', 'route' => '/eam/meters/readings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Readings'],
                        ['code' => 'capture', 'name' => 'Capture Reading'],
                        ['code' => 'import-iot', 'name' => 'Import from IoT'],
                    ]],
                ['code' => 'meter-triggers', 'name' => 'Meter-Based Triggers', 'type' => 'page', 'route' => '/eam/meters/triggers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Triggers']]],
            ],
        ],

        // ==================== 15. LABOR & CRAFTS ====================
        [
            'code' => 'labor-crafts', 'name' => 'Labor & Crafts',
            'description' => 'Craft register, labor rates, crews (→ aero-hrm workforce)',
            'icon' => 'UserGroupIcon', 'route' => '/eam/labor', 'priority' => 15,
            'components' => [
                ['code' => 'crafts', 'name' => 'Crafts / Trades', 'type' => 'page', 'route' => '/eam/labor/crafts',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Crafts']]],
                ['code' => 'labor-rates', 'name' => 'Labor Rates', 'type' => 'page', 'route' => '/eam/labor/rates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Labor Rates']]],
                ['code' => 'crews', 'name' => 'Crews (HRM Link)', 'type' => 'page', 'route' => '/eam/labor/crews',
                    'actions' => [['code' => 'view', 'name' => 'View Crews'], ['code' => 'sync-hrm', 'name' => 'Sync from HRM']]],
            ],
        ],

        // ==================== 16. COSTS & BUDGETS ====================
        [
            'code' => 'costs-budgets', 'name' => 'Maintenance Costs & Budgets',
            'description' => 'WO cost rollup, maintenance budgets, cost-to-Finance posting',
            'icon' => 'CurrencyDollarIcon', 'route' => '/eam/costs', 'priority' => 16,
            'components' => [
                ['code' => 'wo-costs', 'name' => 'Work Order Costs', 'type' => 'page', 'route' => '/eam/costs/wo',
                    'actions' => [['code' => 'view', 'name' => 'View WO Costs'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'asset-costs', 'name' => 'Asset Cost History', 'type' => 'page', 'route' => '/eam/costs/asset',
                    'actions' => [['code' => 'view', 'name' => 'View Asset Costs']]],
                ['code' => 'maintenance-budgets', 'name' => 'Maintenance Budgets', 'type' => 'page', 'route' => '/eam/costs/budgets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Budgets'],
                        ['code' => 'create', 'name' => 'Create Budget'],
                        ['code' => 'approve', 'name' => 'Approve Budget'],
                    ]],
                ['code' => 'cost-posting', 'name' => 'Cost Posting (→ Finance GL)', 'type' => 'page', 'route' => '/eam/costs/posting',
                    'actions' => [['code' => 'post', 'name' => 'Post Costs to Finance']]],
            ],
        ],

        // ==================== 17. WARRANTY ====================
        [
            'code' => 'warranty', 'name' => 'Warranty Management',
            'description' => 'Asset warranties, claims, warranty work orders',
            'icon' => 'ShieldCheckIcon', 'route' => '/eam/warranty', 'priority' => 17,
            'components' => [
                ['code' => 'warranties', 'name' => 'Warranties', 'type' => 'page', 'route' => '/eam/warranty',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Warranties'],
                        ['code' => 'register', 'name' => 'Register Warranty'],
                        ['code' => 'update', 'name' => 'Update Warranty'],
                        ['code' => 'remind', 'name' => 'Send Expiry Reminder'],
                    ]],
                ['code' => 'warranty-claims', 'name' => 'Warranty Claims', 'type' => 'page', 'route' => '/eam/warranty/claims',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Claims'],
                        ['code' => 'file', 'name' => 'File Claim'],
                        ['code' => 'track', 'name' => 'Track Claim'],
                    ]],
            ],
        ],

        // ==================== 18. SPARE PARTS BRIDGE ====================
        [
            'code' => 'spare-parts-bridge', 'name' => 'Spare Parts Bridge (IMS)',
            'description' => 'EAM-side view into aero-ims MRO spare parts & reservations',
            'icon' => 'CubeIcon', 'route' => '/eam/spares', 'priority' => 18,
            'components' => [
                ['code' => 'spares-availability', 'name' => 'Spares Availability', 'type' => 'page', 'route' => '/eam/spares',
                    'actions' => [['code' => 'view', 'name' => 'View Spares Availability']]],
                ['code' => 'spares-reservation', 'name' => 'Reserve Spares for WO', 'type' => 'page', 'route' => '/eam/spares/reserve',
                    'actions' => [['code' => 'reserve', 'name' => 'Reserve Spares'], ['code' => 'release', 'name' => 'Release Reservation']]],
                ['code' => 'asset-to-spare-map', 'name' => 'Asset → Spare Mapping', 'type' => 'page', 'route' => '/eam/spares/mapping',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Mapping']]],
            ],
        ],

        // ==================== 19. MOBILE & BARCODE / QR ====================
        [
            'code' => 'mobile-barcode', 'name' => 'Mobile & Barcode/QR',
            'description' => 'Field technician mobile app, offline mode, barcode / QR scanning',
            'icon' => 'DevicePhoneMobileIcon', 'route' => '/eam/mobile', 'priority' => 19,
            'components' => [
                ['code' => 'mobile-app', 'name' => 'Mobile Technician App', 'type' => 'page', 'route' => '/eam/mobile',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Mobile App']]],
                ['code' => 'qr-barcode', 'name' => 'QR / Barcode Scanning', 'type' => 'page', 'route' => '/eam/mobile/scan',
                    'actions' => [['code' => 'scan', 'name' => 'Scan QR/Barcode'], ['code' => 'print-tags', 'name' => 'Print Asset Tags']]],
                ['code' => 'offline-sync', 'name' => 'Offline Sync', 'type' => 'page', 'route' => '/eam/mobile/offline',
                    'actions' => [['code' => 'sync', 'name' => 'Force Sync']]],
            ],
        ],

        // ==================== 20. REPORTS & ANALYTICS ====================
        [
            'code' => 'reports-analytics', 'name' => 'EAM Reports & Analytics',
            'description' => 'KPIs, asset reports, maintenance compliance, cost reports',
            'icon' => 'DocumentChartBarIcon', 'route' => '/eam/reports', 'priority' => 20,
            'components' => [
                ['code' => 'asset-reports', 'name' => 'Asset Reports', 'type' => 'page', 'route' => '/eam/reports/assets',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'maintenance-reports', 'name' => 'Maintenance Reports', 'type' => 'page', 'route' => '/eam/reports/maintenance',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'compliance-reports', 'name' => 'Compliance Reports', 'type' => 'page', 'route' => '/eam/reports/compliance',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'cost-reports', 'name' => 'Cost Reports', 'type' => 'page', 'route' => '/eam/reports/costs',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'export', 'name' => 'Export']]],
                ['code' => 'custom-reports', 'name' => 'Custom Reports', 'type' => 'page', 'route' => '/eam/reports/custom',
                    'actions' => [
                        ['code' => 'create', 'name' => 'Create Report'],
                        ['code' => 'schedule', 'name' => 'Schedule Report Delivery'],
                    ]],
            ],
        ],

        // ==================== 21. INTEGRATIONS ====================
        [
            'code' => 'integrations', 'name' => 'EAM Integrations',
            'description' => 'Cross-package bridges, SCADA / historian, GIS, OEM connectors',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/eam/integrations', 'priority' => 21,
            'components' => [
                ['code' => 'cross-package-bridges', 'name' => 'Cross-Package Bridges', 'type' => 'page', 'route' => '/eam/integrations/bridges',
                    'actions' => [['code' => 'view', 'name' => 'View Bridge Status'], ['code' => 'configure', 'name' => 'Configure Bridge']]],
                ['code' => 'scada-historian', 'name' => 'SCADA / Historian', 'type' => 'page', 'route' => '/eam/integrations/scada',
                    'actions' => [['code' => 'configure', 'name' => 'Configure SCADA']]],
                ['code' => 'gis', 'name' => 'GIS / Mapping', 'type' => 'page', 'route' => '/eam/integrations/gis',
                    'actions' => [['code' => 'configure', 'name' => 'Configure GIS']]],
                ['code' => 'oem-connectors', 'name' => 'OEM Connectors', 'type' => 'page', 'route' => '/eam/integrations/oem',
                    'actions' => [['code' => 'manage', 'name' => 'Manage OEM Connectors']]],
            ],
        ],

        // ==================== 22. SETTINGS ====================
        [
            'code' => 'settings', 'name' => 'EAM Settings',
            'description' => 'WO numbering, workflows, approval chains, custom fields',
            'icon' => 'CogIcon', 'route' => '/eam/settings', 'priority' => 99,
            'components' => [
                ['code' => 'wo-numbering', 'name' => 'Work Order Numbering', 'type' => 'page', 'route' => '/eam/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']]],
                ['code' => 'approval-chains', 'name' => 'Approval Chains', 'type' => 'page', 'route' => '/eam/settings/approvals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Approvals']]],
                ['code' => 'custom-fields', 'name' => 'Custom Fields', 'type' => 'page', 'route' => '/eam/settings/custom-fields',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Custom Fields']]],
                ['code' => 'workflows', 'name' => 'Workflows & Statuses', 'type' => 'page', 'route' => '/eam/settings/workflows',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/eam/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Package EAM Coordination Map
    |--------------------------------------------------------------------------
    | This is the authoritative list of EAM capabilities consumed FROM other
    | packages and capabilities provided TO them. Each entry uses the
    | key-pattern <domain>.<feature> → <package>[.<submodule>.<component>].
    |
    | The other packages declare mirror entries in their own eam_integration
    | map, allowing the aero-eam orchestrator (and HRMAC) to wire things up
    | at runtime without hard-coupling.
    */
    'eam_integration' => [
        // Capabilities EAM provides to other packages
        'provides' => [
            'eam.asset_registry' => 'asset-registry.assets',
            'eam.asset_hierarchy' => 'asset-hierarchy.hierarchy-tree',
            'eam.asset_criticality' => 'asset-criticality.criticality-analysis',
            'eam.asset_bom' => 'asset-criticality.asset-bom',
            'eam.functional_locations' => 'asset-hierarchy.functional-locations',
            'eam.failure_codes' => 'failure-codes',
            'eam.work_orders' => 'work-orders.wo-list',
            'eam.work_requests' => 'work-requests.wr-list',
            'eam.work_order_materials' => 'work-orders.wo-materials',
            'eam.work_order_labor' => 'work-orders.wo-labor',
            'eam.work_order_permits' => 'work-orders.wo-permits',
            'eam.work_order_costs' => 'costs-budgets.wo-costs',
            'eam.work_order_events' => 'work-orders.wo-list',
            'eam.work_order_docs' => 'work-orders.wo-attachments',
            'eam.maintenance_schedule' => 'preventive-maintenance.pm-schedules',
            'eam.pm_compliance' => 'preventive-maintenance.pm-compliance',
            'eam.pdm_triggers' => 'predictive-maintenance.pdm-triggers',
            'eam.breakdown' => 'corrective-cbm.breakdown',
            'eam.turnaround' => 'turnaround-shutdown.turnaround-list',
            'eam.reliability_kpis' => 'reliability.mtbf-mttr',
            'eam.rcm' => 'reliability.rcm',
            'eam.fmeca' => 'reliability.fmeca',
            'eam.inspections' => 'inspections-rounds.inspection-list',
            'eam.operator_rounds' => 'inspections-rounds.operator-rounds',
            'eam.meter_readings' => 'meter-readings.readings',
            'eam.warranty' => 'warranty.warranties',
            'eam.warranty_claims' => 'warranty.warranty-claims',
            'eam.spares_reservation' => 'spare-parts-bridge.spares-reservation',
            'eam.mobile_app' => 'mobile-barcode.mobile-app',
            'eam.qa' => 'inspections-rounds.checklists',
        ],

        // Capabilities EAM consumes FROM other packages
        'consumes' => [
            // aero-core
            'core.users' => 'aero-core',
            'core.roles' => 'aero-core',
            'core.audit_logs' => 'aero-core',
            'core.notifications' => 'aero-core',
            'core.file_storage' => 'aero-core',

            // aero-finance
            'finance.asset_register' => 'aero-finance',
            'finance.depreciation_schedule' => 'aero-finance',
            'finance.capex_projects' => 'aero-finance',
            'finance.asset_insurance' => 'aero-finance',
            'finance.asset_lease' => 'aero-finance',
            'finance.cost_centers' => 'aero-finance',
            'finance.gl_posting' => 'aero-finance',

            // aero-scm
            'scm.purchase_orders' => 'aero-scm',
            'scm.requisitions' => 'aero-scm',
            'scm.emergency_procurement' => 'aero-scm',
            'scm.service_procurement' => 'aero-scm',
            'scm.vendors' => 'aero-scm',
            'scm.approved_vendor_list' => 'aero-scm',
            'scm.contractors' => 'aero-scm',
            'scm.amc_contracts' => 'aero-scm',
            'scm.slas' => 'aero-scm',

            // aero-ims
            'ims.mro_spare_parts' => 'aero-ims',
            'ims.critical_spares' => 'aero-ims',
            'ims.stock_on_hand' => 'aero-ims',
            'ims.work_order_issues' => 'aero-ims',
            'ims.work_order_kits' => 'aero-ims',
            'ims.point_of_use_bins' => 'aero-ims',
            'ims.serial_tracking' => 'aero-ims',
            'ims.reservations' => 'aero-ims',

            // aero-iot
            'iot.device_telemetry' => 'aero-iot',
            'iot.asset_health' => 'aero-iot',
            'iot.vibration' => 'aero-iot',
            'iot.thermal' => 'aero-iot',
            'iot.oil_analysis' => 'aero-iot',
            'iot.meter_readings' => 'aero-iot',
            'iot.rul_forecast' => 'aero-iot',
            'iot.failure_prediction' => 'aero-iot',
            'iot.anomaly_detection' => 'aero-iot',
            'iot.prescriptive_actions' => 'aero-iot',
            'iot.digital_twin' => 'aero-iot',
            'iot.asset_binding' => 'aero-iot',
            'iot.geofences' => 'aero-iot',
            'iot.asset_tracking' => 'aero-iot',

            // aero-hrm
            'hrm.technicians' => 'aero-hrm',
            'hrm.skills' => 'aero-hrm',
            'hrm.certifications' => 'aero-hrm',
            'hrm.trade_authorizations' => 'aero-hrm',
            'hrm.workforce_scheduling' => 'aero-hrm',
            'hrm.skill_based_dispatch' => 'aero-hrm',
            'hrm.timesheets' => 'aero-hrm',
            'hrm.training' => 'aero-hrm',
            'hrm.compliance_training' => 'aero-hrm',
            'hrm.safety_incidents' => 'aero-hrm',
            'hrm.safety_near_miss' => 'aero-hrm',
            'hrm.safety_hazards' => 'aero-hrm',
            'hrm.safety_risk_assessments' => 'aero-hrm',
            'hrm.safety_permit_to_work' => 'aero-hrm',
            'hrm.safety_loto' => 'aero-hrm',
            'hrm.safety_ppe' => 'aero-hrm',
            'hrm.safety_toolbox_talks' => 'aero-hrm',
            'hrm.tools_equipment' => 'aero-hrm',
            'hrm.vehicles' => 'aero-hrm',
            'hrm.asset_transfers' => 'aero-hrm',
            'hrm.asset_audits' => 'aero-hrm',
            'hrm.contractor_sign_in' => 'aero-hrm',

            // aero-quality
            'quality.inspections' => 'aero-quality',
            'quality.checklists' => 'aero-quality',
            'quality.ncr' => 'aero-quality',
            'quality.capa' => 'aero-quality',
            'quality.spc' => 'aero-quality',
            'quality.calibration' => 'aero-quality',
            'quality.calibration_schedule' => 'aero-quality',
            'quality.calibration_oot' => 'aero-quality',
            'quality.supplier_quality' => 'aero-quality',
            'quality.audits' => 'aero-quality',
            'quality.asset_commissioning' => 'aero-quality',
            'quality.reliability' => 'aero-quality',
            'quality.fmea' => 'aero-quality',

            // aero-compliance
            'compliance.incidents' => 'aero-compliance',
            'compliance.hazards' => 'aero-compliance',
            'compliance.jsa' => 'aero-compliance',
            'compliance.permit_to_work' => 'aero-compliance',
            'compliance.isolations' => 'aero-compliance',
            'compliance.environmental' => 'aero-compliance',
            'compliance.contractor_insurance' => 'aero-compliance',
            'compliance.contractor_induction' => 'aero-compliance',
            'compliance.asset_permits' => 'aero-compliance',
            'compliance.statutory_inspections' => 'aero-compliance',
            'compliance.asset_recalls' => 'aero-compliance',
            'compliance.workforce_certs' => 'aero-compliance',
            'compliance.risks' => 'aero-compliance',

            // aero-real-estate
            'facility.assets' => 'aero-real-estate',
            'facility.preventive_maintenance' => 'aero-real-estate',
            'facility.work_orders' => 'aero-real-estate',
            'facility.inspections' => 'aero-real-estate',
            'facility.space_planning' => 'aero-real-estate',
            'facility.utilities_meters' => 'aero-real-estate',
            'facility.vendors' => 'aero-real-estate',

            // aero-crm
            'crm.installed_base' => 'aero-crm',
            'crm.warranty' => 'aero-crm',
            'crm.service_contracts' => 'aero-crm',
            'crm.service_history' => 'aero-crm',
            'crm.support_tickets' => 'aero-crm',
            'crm.sla_policies' => 'aero-crm',

            // aero-project
            'project.capex_projects' => 'aero-project',
            'project.turnaround' => 'aero-project',
            'project.maintenance' => 'aero-project',
            'project.tasks' => 'aero-project',
            'project.gantt' => 'aero-project',
            'project.resources' => 'aero-project',
            'project.timesheets' => 'aero-project',
            'project.site_telemetry' => 'aero-project',
            'project.risk_forecast' => 'aero-project',
            'project.hse' => 'aero-project',

            // aero-dms
            'dms.asset_manuals' => 'aero-dms',
            'dms.drawings_cad' => 'aero-dms',
            'dms.as_built' => 'aero-dms',
            'dms.asset_certificates' => 'aero-dms',
            'dms.om_docs' => 'aero-dms',
            'dms.controlled_docs' => 'aero-dms',
            'dms.e_signature' => 'aero-dms',

            // aero-blockchain
            'blockchain.asset_provenance' => 'aero-blockchain',
            'blockchain.chain_of_custody' => 'aero-blockchain',
            'blockchain.asset_tokenization' => 'aero-blockchain',
            'blockchain.supply_chain_trace' => 'aero-blockchain',
            'blockchain.audit_chain' => 'aero-blockchain',

            // aero-assistant
            'assistant.eam_agent' => 'aero-assistant',
            'assistant.tools' => 'aero-assistant',
            'assistant.knowledge_base' => 'aero-assistant',

            // aero-rfi
            'rfi.inspection' => 'aero-rfi',
            'rfi.information' => 'aero-rfi',
            'rfi.contractor_work_request' => 'aero-rfi',
            'rfi.contractor_rfi' => 'aero-rfi',
            'rfi.submittals' => 'aero-rfi',
            'rfi.punch_list' => 'aero-rfi',

            // aero-pos (POS hardware assets)
            'pos.hardware_register' => 'aero-pos',
            'pos.device_health' => 'aero-pos',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Package Feature Matrix
    |--------------------------------------------------------------------------
    | High-level map of which packages contribute to which EAM domain.
    */
    'eam_feature_matrix' => [
        'asset_management' => ['aero-eam', 'aero-finance', 'aero-hrm', 'aero-real-estate', 'aero-pos', 'aero-crm'],
        'maintenance_management' => ['aero-eam', 'aero-real-estate', 'aero-pos'],
        'work_order_management' => ['aero-eam', 'aero-real-estate'],
        'mro_spares' => ['aero-ims', 'aero-scm'],
        'condition_monitoring' => ['aero-iot', 'aero-eam'],
        'reliability_engineering' => ['aero-eam', 'aero-quality'],
        'calibration' => ['aero-quality', 'aero-eam'],
        'safety_hse' => ['aero-hrm', 'aero-compliance', 'aero-project'],
        'permit_to_work_loto' => ['aero-hrm', 'aero-compliance', 'aero-eam'],
        'contractor_management' => ['aero-scm', 'aero-compliance', 'aero-rfi', 'aero-hrm'],
        'workforce_scheduling' => ['aero-hrm', 'aero-eam'],
        'technician_skills' => ['aero-hrm'],
        'financial_backbone' => ['aero-finance'],
        'statutory_compliance' => ['aero-compliance'],
        'asset_documentation' => ['aero-dms'],
        'asset_provenance' => ['aero-blockchain'],
        'ai_copilot' => ['aero-assistant'],
        'capex_projects' => ['aero-finance', 'aero-project', 'aero-eam'],
        'turnaround_shutdown' => ['aero-project', 'aero-eam'],
        'customer_assets' => ['aero-crm', 'aero-eam'],
        'facility_management' => ['aero-real-estate', 'aero-eam'],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'eam_admin_role' => 'eam-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'eam-access'],
    ],
];
