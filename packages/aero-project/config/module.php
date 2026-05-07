<?php

return [
    'code' => 'project',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Enterprise Project Intelligence',
    'description' => 'Full-lifecycle PM: projects, tasks (kanban/scrum/waterfall), milestones, resources, costs, BIM, RFI, CapEx & turnaround projects for EAM, with AI forecasting and linear scheduling.',
    'version' => '2.1.0',
    'category' => 'business',
    'icon' => 'PresentationChartLineIcon',
    'priority' => 13,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('PROJECT_MODULE_ENABLED', true),
    'min_plan' => 'enterprise',
    'minimum_plan' => 'enterprise',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => '/project',

    /*
    |--------------------------------------------------------------------------
    | Self-Service Navigation Items
    |--------------------------------------------------------------------------
    |
    | User-facing "My *" pages for the Project module.
    |
    */
    'self_service' => [
        [
            'code' => 'my-tasks',
            'name' => 'My Tasks',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => '/project/my-tasks',
            'priority' => 20,
        ],
        [
            'code' => 'my-projects',
            'name' => 'My Projects',
            'icon' => 'FolderIcon',
            'route' => '/project/my-projects',
            'priority' => 21,
        ],
        [
            'code' => 'my-timesheets',
            'name' => 'My Timesheets',
            'icon' => 'ClockIcon',
            'route' => '/project/my-timesheets',
            'priority' => 22,
        ],
    ],

    // ==================== Submodules ====================
    'submodules' => [

        // ==================== 0. DASHBOARD ====================
        [
            'code' => 'dashboard',
            'name' => 'Project Dashboard',
            'description' => 'Portfolio KPIs, status rollups, risk flags',
            'icon' => 'HomeIcon',
            'route' => '/project/dashboard',
            'priority' => 0,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'project-dashboard', 'name' => 'Project Dashboard', 'type' => 'page', 'route' => '/project/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']],
                ],
                [
                    'code' => 'portfolio-dashboard', 'name' => 'Portfolio Dashboard', 'type' => 'page', 'route' => '/project/portfolio',
                    'actions' => [['code' => 'view', 'name' => 'View Portfolio Dashboard']],
                ],
            ],
        ],

        // ==================== 1. PROJECTS ====================
        [
            'code' => 'projects',
            'name' => 'Projects',
            'description' => 'Project registry with lifecycle, templates, and phases',
            'icon' => 'FolderIcon',
            'route' => '/project/projects',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'project-list', 'name' => 'All Projects', 'type' => 'page', 'route' => '/project/projects',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Projects'],
                        ['code' => 'create', 'name' => 'Create Project'],
                        ['code' => 'update', 'name' => 'Update Project'],
                        ['code' => 'delete', 'name' => 'Delete Project'],
                        ['code' => 'archive', 'name' => 'Archive Project'],
                        ['code' => 'clone', 'name' => 'Clone Project'],
                        ['code' => 'export', 'name' => 'Export Project'],
                    ],
                ],
                [
                    'code' => 'project-templates', 'name' => 'Project Templates', 'type' => 'page', 'route' => '/project/projects/templates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates']],
                ],
                [
                    'code' => 'project-phases', 'name' => 'Phases / Stages', 'type' => 'page', 'route' => '/project/projects/phases',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Phases']],
                ],
                [
                    'code' => 'project-members', 'name' => 'Project Members & Roles', 'type' => 'page', 'route' => '/project/projects/members',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Members'],
                        ['code' => 'add', 'name' => 'Add Member'],
                        ['code' => 'remove', 'name' => 'Remove Member'],
                    ],
                ],
                /* EAM: CapEx projects */
                [
                    'code' => 'capex-projects', 'name' => 'CapEx Projects', 'type' => 'page', 'route' => '/project/projects/capex',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CapEx Projects'],
                        ['code' => 'create', 'name' => 'Create CapEx Project'],
                        ['code' => 'link-asset', 'name' => 'Link to Asset'],
                        ['code' => 'capitalize', 'name' => 'Trigger Capitalization'],
                    ],
                ],
                /* EAM: turnaround / shutdown (major maintenance project) */
                [
                    'code' => 'turnaround-shutdown', 'name' => 'Turnaround / Shutdown Projects', 'type' => 'page', 'route' => '/project/projects/turnaround',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Turnaround Projects'],
                        ['code' => 'create', 'name' => 'Create Turnaround'],
                        ['code' => 'freeze-scope', 'name' => 'Freeze Scope'],
                        ['code' => 'execute', 'name' => 'Execute Turnaround'],
                        ['code' => 'close', 'name' => 'Close Turnaround'],
                    ],
                ],
                /* EAM: maintenance projects */
                [
                    'code' => 'maintenance-projects', 'name' => 'Maintenance Projects', 'type' => 'page', 'route' => '/project/projects/maintenance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Maintenance Projects'],
                        ['code' => 'create', 'name' => 'Create Maintenance Project'],
                    ],
                ],
            ],
        ],

        // ==================== 2. TASKS (Kanban/Scrum/Waterfall) ====================
        [
            'code' => 'tasks',
            'name' => 'Tasks & Issues',
            'description' => 'Task boards (Kanban, Scrum, List, Calendar), issues & bugs, assignments',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => '/project/tasks',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'task-list', 'name' => 'Task List', 'type' => 'page', 'route' => '/project/tasks',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tasks'],
                        ['code' => 'create', 'name' => 'Create Task'],
                        ['code' => 'update', 'name' => 'Update Task'],
                        ['code' => 'delete', 'name' => 'Delete Task'],
                        ['code' => 'assign', 'name' => 'Assign Task'],
                        ['code' => 'comment', 'name' => 'Comment'],
                        ['code' => 'attach', 'name' => 'Attach File'],
                        ['code' => 'log-time', 'name' => 'Log Time'],
                    ],
                ],
                [
                    'code' => 'kanban-board', 'name' => 'Kanban Board', 'type' => 'page', 'route' => '/project/tasks/kanban',
                    'actions' => [['code' => 'view', 'name' => 'View Kanban'], ['code' => 'move', 'name' => 'Move Card']],
                ],
                [
                    'code' => 'scrum-board', 'name' => 'Scrum / Sprint Board', 'type' => 'page', 'route' => '/project/tasks/scrum',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Sprints'],
                        ['code' => 'plan-sprint', 'name' => 'Plan Sprint'],
                        ['code' => 'start-sprint', 'name' => 'Start Sprint'],
                        ['code' => 'close-sprint', 'name' => 'Close Sprint'],
                    ],
                ],
                [
                    'code' => 'issues-bugs', 'name' => 'Issues & Bugs', 'type' => 'page', 'route' => '/project/tasks/issues',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Issues'],
                        ['code' => 'create', 'name' => 'Report Issue'],
                        ['code' => 'resolve', 'name' => 'Resolve Issue'],
                        ['code' => 'close', 'name' => 'Close Issue'],
                    ],
                ],
                [
                    'code' => 'dependencies', 'name' => 'Task Dependencies', 'type' => 'page', 'route' => '/project/tasks/dependencies',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Dependencies']],
                ],
                [
                    'code' => 'checklists', 'name' => 'Checklists & Subtasks', 'type' => 'feature', 'route' => null,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Checklists']],
                ],
            ],
        ],

        // ==================== 3. MILESTONES ====================
        [
            'code' => 'milestones',
            'name' => 'Milestones & Deliverables',
            'description' => 'Track key milestones and deliverables',
            'icon' => 'FlagIcon',
            'route' => '/project/milestones',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'milestone-list', 'name' => 'Milestones', 'type' => 'page', 'route' => '/project/milestones',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Milestones'],
                        ['code' => 'create', 'name' => 'Create Milestone'],
                        ['code' => 'update', 'name' => 'Update Milestone'],
                        ['code' => 'delete', 'name' => 'Delete Milestone'],
                        ['code' => 'mark-complete', 'name' => 'Mark Complete'],
                    ],
                ],
                [
                    'code' => 'deliverables', 'name' => 'Deliverables', 'type' => 'page', 'route' => '/project/milestones/deliverables',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Deliverables']],
                ],
            ],
        ],

        // ==================== 4. RESOURCES ====================
        [
            'code' => 'resources',
            'name' => 'Resource Management',
            'description' => 'Resource allocation, capacity, skills, and workload',
            'icon' => 'UserGroupIcon',
            'route' => '/project/resources',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'resource-allocation', 'name' => 'Resource Allocation', 'type' => 'page', 'route' => '/project/resources/allocation',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Allocations'],
                        ['code' => 'allocate', 'name' => 'Allocate Resource'],
                        ['code' => 'release', 'name' => 'Release Resource'],
                    ],
                ],
                [
                    'code' => 'capacity-planning', 'name' => 'Capacity Planning', 'type' => 'page', 'route' => '/project/resources/capacity',
                    'actions' => [['code' => 'view', 'name' => 'View Capacity'], ['code' => 'forecast', 'name' => 'Forecast Capacity']],
                ],
                [
                    'code' => 'workload-balance', 'name' => 'Workload Balancing', 'type' => 'page', 'route' => '/project/resources/workload',
                    'actions' => [['code' => 'view', 'name' => 'View Workload'], ['code' => 'rebalance', 'name' => 'Rebalance Workload']],
                ],
            ],
        ],

        // ==================== 5. TIMESHEETS ====================
        [
            'code' => 'timesheets',
            'name' => 'Timesheets',
            'description' => 'Time tracking, approvals, billable/non-billable',
            'icon' => 'ClockIcon',
            'route' => '/project/timesheets',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'timesheet-list', 'name' => 'Timesheets', 'type' => 'page', 'route' => '/project/timesheets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Timesheets'],
                        ['code' => 'create', 'name' => 'Create Timesheet'],
                        ['code' => 'submit', 'name' => 'Submit Timesheet'],
                        ['code' => 'approve', 'name' => 'Approve Timesheet'],
                        ['code' => 'reject', 'name' => 'Reject Timesheet'],
                        ['code' => 'export', 'name' => 'Export Timesheets'],
                    ],
                ],
                [
                    'code' => 'timer', 'name' => 'Live Timer', 'type' => 'feature', 'route' => null,
                    'actions' => [['code' => 'start', 'name' => 'Start Timer'], ['code' => 'stop', 'name' => 'Stop Timer']],
                ],
            ],
        ],

        // ==================== 6. BUDGETS & COSTS ====================
        [
            'code' => 'budgets-costs',
            'name' => 'Budgets & Costs',
            'description' => 'Project budgets, actuals, change orders, invoicing',
            'icon' => 'CurrencyDollarIcon',
            'route' => '/project/budgets-costs',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'project-budgets', 'name' => 'Project Budgets', 'type' => 'page', 'route' => '/project/budgets-costs/budgets',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Budgets'],
                        ['code' => 'create', 'name' => 'Create Budget'],
                        ['code' => 'update', 'name' => 'Update Budget'],
                        ['code' => 'approve', 'name' => 'Approve Budget'],
                        ['code' => 'lock', 'name' => 'Lock Budget'],
                    ],
                ],
                [
                    'code' => 'cost-actuals', 'name' => 'Cost Actuals', 'type' => 'page', 'route' => '/project/budgets-costs/actuals',
                    'actions' => [['code' => 'view', 'name' => 'View Cost Actuals'], ['code' => 'export', 'name' => 'Export Actuals']],
                ],
                [
                    'code' => 'change-orders', 'name' => 'Change Orders', 'type' => 'page', 'route' => '/project/budgets-costs/change-orders',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Change Orders'],
                        ['code' => 'create', 'name' => 'Create Change Order'],
                        ['code' => 'approve', 'name' => 'Approve Change Order'],
                        ['code' => 'reject', 'name' => 'Reject Change Order'],
                    ],
                ],
                [
                    'code' => 'project-invoicing', 'name' => 'Project Invoicing', 'type' => 'page', 'route' => '/project/budgets-costs/invoicing',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Project Invoices'],
                        ['code' => 'create', 'name' => 'Create Invoice'],
                        ['code' => 'send', 'name' => 'Send Invoice'],
                    ],
                ],
                [
                    'code' => 'vendor-contracts', 'name' => 'Vendor / Subcontractor Contracts', 'type' => 'page', 'route' => '/project/budgets-costs/contracts',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Contracts']],
                ],
            ],
        ],

        // ==================== 7. COLLABORATION ====================
        [
            'code' => 'collaboration',
            'name' => 'Collaboration',
            'description' => 'Discussions, files, announcements, meetings',
            'icon' => 'ChatBubbleLeftRightIcon',
            'route' => '/project/collaboration',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'discussions', 'name' => 'Discussions', 'type' => 'page', 'route' => '/project/collaboration/discussions',
                    'actions' => [['code' => 'view', 'name' => 'View'], ['code' => 'post', 'name' => 'Post']],
                ],
                [
                    'code' => 'files', 'name' => 'Project Files', 'type' => 'page', 'route' => '/project/collaboration/files',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Files'],
                        ['code' => 'upload', 'name' => 'Upload File'],
                        ['code' => 'delete', 'name' => 'Delete File'],
                        ['code' => 'version', 'name' => 'Version File'],
                    ],
                ],
                [
                    'code' => 'announcements', 'name' => 'Announcements', 'type' => 'page', 'route' => '/project/collaboration/announcements',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Announcements']],
                ],
                [
                    'code' => 'meetings', 'name' => 'Meetings & Minutes', 'type' => 'page', 'route' => '/project/collaboration/meetings',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Meetings'],
                        ['code' => 'schedule', 'name' => 'Schedule Meeting'],
                        ['code' => 'record-mom', 'name' => 'Record Minutes'],
                    ],
                ],
            ],
        ],

        // ==================== 1. CORE: Intelligent Scheduling (NOVELTY: CPM + LINEAR) ====================
        [
            'code' => 'smart-scheduling',
            'name' => 'Intelligent Scheduling',
            'description' => 'Hybrid Critical Path Method (CPM) and Time-Location (Linear) scheduling for infrastructure.',
            'icon' => 'CalendarDaysIcon',
            'route' => '/project/scheduling',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'gantt-cpm',
                    'name' => 'CPM Gantt & PERT',
                    'description' => 'Interactive Gantt with auto-critical path calculation',
                    'route' => '/project/scheduling/gantt',
                    'icon' => 'ChartBarIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'simulate', 'name' => 'Monte Carlo Simulation', 'description' => 'Run schedule risk simulations'],
                        ['code' => 'baseline', 'name' => 'Manage Baselines', 'description' => 'Compare planned vs actual'],
                    ],
                ],
                [
                    'code' => 'linear-scheduler',
                    'name' => 'Time-Location Diagram', // High novelty for road/rail/pipeline projects
                    'description' => 'Visual linear scheduling for repetitive horizontal projects',
                    'route' => '/project/scheduling/linear',
                    'icon' => 'ArrowsRightLeftIcon',
                    'type' => 'page',
                    'actions' => [['code' => 'optimize', 'name' => 'Optimize Flow', 'description' => 'Detect clash in location-time matrix']],
                ],
            ],
        ],

        // ==================== 2. FINANCIAL: Advanced BOQ & Cost Control (PATENTABLE) ====================
        [
            'code' => 'boq-measurements',
            'name' => 'BOQ & Smart Certification',
            'description' => 'Bill of Quantities with chainage verification and blockchain-ready certification.',
            'icon' => 'CalculatorIcon',
            'route' => '/project/boq-measurements',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'measurement-list',
                    'name' => 'Measurement Book (MB)',
                    'description' => 'Digital MB with geo-fenced entry validation',
                    'route' => '/project/boq-measurements',
                    'icon' => 'TableCellsIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'verify', 'name' => 'Verify', 'description' => 'Multi-tier verification'],
                        ['code' => 'audit_trail', 'name' => 'Audit Log', 'description' => 'Immutable change history'],
                    ],
                ],
                [
                    'code' => 'earned-value',
                    'name' => 'EVM Analysis', // Earned Value Management
                    'description' => 'Real-time SPI (Schedule Performance Index) and CPI (Cost Performance Index) calculation',
                    'route' => '/project/boq-measurements/evm',
                    'icon' => 'CurrencyDollarIcon',
                    'type' => 'widget',
                ],
            ],
        ],

        // ==================== 3. ENGINEERING: BIM & Digital Twin (HIGH NOVELTY) ====================
        [
            'code' => 'digital-engineering',
            'name' => 'BIM & Engineering',
            'description' => '3D Model integration and technical documentation control.',
            'icon' => 'CubeTransparentIcon',
            'route' => '/project/engineering',
            'priority' => 15,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'bim-viewer',
                    'name' => '3D Model Viewer',
                    'description' => 'IFC/Revit model viewer with layer isolation',
                    'route' => '/project/engineering/bim',
                    'icon' => 'ViewfinderCircleIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'clash-detect', 'name' => 'Clash Detection', 'description' => 'Identify design conflicts'],
                        ['code' => 'tag-issue', 'name' => 'Tag Issue', 'description' => 'Pin tasks to 3D coordinates'],
                    ],
                ],
                [
                    'code' => 'rfi-manager',
                    'name' => 'RFI Management',
                    'description' => 'Request for Information workflow linked to BOQ items',
                    'route' => '/project/engineering/rfi',
                    'icon' => 'QuestionMarkCircleIcon',
                    'type' => 'page',
                ],
            ],
        ],

        // ==================== 4. OPERATIONS: Resource & IoT Telemetry ====================
        [
            'code' => 'site-operations',
            'name' => 'Site Operations & IoT',
            'description' => 'Live tracking of labor, machinery, and material movement.',
            'icon' => 'TruckIcon',
            'route' => '/project/operations',
            'priority' => 25,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'resource-heatmap',
                    'name' => 'Resource Heatmap',
                    'description' => 'AI-driven load balancing for manpower and machinery',
                    'route' => '/project/operations/resources',
                    'icon' => 'UserGroupIcon',
                    'type' => 'chart',
                ],
                [
                    'code' => 'iot-feed',
                    'name' => 'Equipment Telemetry',
                    'description' => 'Live feed from connected machinery (Fuel, Runtime, Idle)',
                    'route' => '/project/operations/telemetry',
                    'icon' => 'SignalIcon',
                    'type' => 'stream',
                ],
            ],
        ],

        // ==================== 5. RISK & AI: Predictive Analytics (NOVELTY) ====================
        [
            'code' => 'risk-intelligence',
            'name' => 'AI Risk Intelligence',
            'description' => 'Predictive models for delay and cost overruns.',
            'icon' => 'CpuChipIcon',
            'route' => '/project/risk',
            'priority' => 30,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'predictive-delay',
                    'name' => 'Delay Forecaster',
                    'description' => 'ML model analyzing weather, supply chain, and historical pace',
                    'route' => '/project/risk/forecast',
                    'icon' => 'CloudArrowDownIcon',
                    'type' => 'dashboard',
                ],
                [
                    'code' => 'safety-monitor',
                    'name' => 'HSE Compliance',
                    'description' => 'Health, Safety & Environment incident tracking and prevention',
                    'route' => '/project/risk/hse',
                    'icon' => 'ShieldCheckIcon',
                    'type' => 'page',
                ],
            ],
        ],

        // ==================== BOQ Items Master (Preserved) ====================
        [
            'code' => 'boq-items',
            'name' => 'BOQ Master Data',
            'description' => 'Central repository of cost items and rate analysis',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/project/boq-items',
            'priority' => 20,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'item-list',
                    'name' => 'Item Library',
                    'description' => 'View and manage BOQ items',
                    'route' => '/project/boq-items',
                    'icon' => 'ListBulletIcon',
                    'type' => 'page',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'description' => 'View BOQ items'],
                        ['code' => 'import', 'name' => 'Import', 'description' => 'Import from Excel/CSV'],
                        ['code' => 'rate-analysis', 'name' => 'Rate Analysis', 'description' => 'Breakdown of material/labor costs'],
                    ],
                ],
            ],
        ],

        // ==================== PORTFOLIO / PROGRAM MANAGEMENT ====================
        [
            'code' => 'portfolio',
            'name' => 'Portfolio & Program Management',
            'description' => 'Multi-project portfolio view, strategic alignment, prioritization',
            'icon' => 'Squares2X2Icon',
            'route' => '/project/portfolio',
            'priority' => 35,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'portfolio-list', 'name' => 'Portfolios', 'type' => 'page', 'route' => '/project/portfolio',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Portfolios'],
                        ['code' => 'create', 'name' => 'Create Portfolio'],
                        ['code' => 'update', 'name' => 'Update Portfolio'],
                        ['code' => 'delete', 'name' => 'Delete Portfolio'],
                    ],
                ],
                [
                    'code' => 'programs', 'name' => 'Programs', 'type' => 'page', 'route' => '/project/portfolio/programs',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Programs']],
                ],
                [
                    'code' => 'prioritization', 'name' => 'Project Prioritization', 'type' => 'page', 'route' => '/project/portfolio/prioritization',
                    'actions' => [['code' => 'run', 'name' => 'Run Prioritization'], ['code' => 'view', 'name' => 'View Scoring']],
                ],
            ],
        ],

        // ==================== REPORTS ====================
        [
            'code' => 'reports',
            'name' => 'Project Reports',
            'description' => 'Status reports, variance reports, EVM, custom reports',
            'icon' => 'DocumentChartBarIcon',
            'route' => '/project/reports',
            'priority' => 40,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'status-reports', 'name' => 'Status Reports', 'type' => 'page', 'route' => '/project/reports/status',
                    'actions' => [['code' => 'view', 'name' => 'View Reports'], ['code' => 'generate', 'name' => 'Generate Report']],
                ],
                [
                    'code' => 'variance-reports', 'name' => 'Variance Reports', 'type' => 'page', 'route' => '/project/reports/variance',
                    'actions' => [['code' => 'view', 'name' => 'View Variance'], ['code' => 'export', 'name' => 'Export Variance']],
                ],
                [
                    'code' => 'custom-reports', 'name' => 'Custom Reports Builder', 'type' => 'page', 'route' => '/project/reports/custom',
                    'actions' => [['code' => 'create', 'name' => 'Create Report'], ['code' => 'schedule', 'name' => 'Schedule Delivery']],
                ],
            ],
        ],

        // ==================== INTEGRATIONS ====================
        [
            'code' => 'integrations',
            'name' => 'Integrations',
            'description' => 'MS Project, Primavera, Jira, GitHub, BIM viewers',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => '/project/integrations',
            'priority' => 45,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'ms-project', 'name' => 'MS Project / Primavera', 'type' => 'page', 'route' => '/project/integrations/ms-project',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']],
                ],
                [
                    'code' => 'jira-sync', 'name' => 'Jira / GitHub / GitLab Sync', 'type' => 'page', 'route' => '/project/integrations/devtools',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']],
                ],
                [
                    'code' => 'calendar-sync', 'name' => 'Calendar Sync (Google / Outlook)', 'type' => 'page', 'route' => '/project/integrations/calendar',
                    'actions' => [['code' => 'configure', 'name' => 'Configure']],
                ],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'Project Settings',
            'description' => 'Workflows, numbering, custom fields, approvals',
            'icon' => 'CogIcon',
            'route' => '/project/settings',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'workflows', 'name' => 'Workflows & Statuses', 'type' => 'page', 'route' => '/project/settings/workflows',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Workflows']],
                ],
                [
                    'code' => 'numbering', 'name' => 'Document Numbering', 'type' => 'page', 'route' => '/project/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']],
                ],
                [
                    'code' => 'custom-fields', 'name' => 'Custom Fields', 'type' => 'page', 'route' => '/project/settings/custom-fields',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Custom Fields']],
                ],
                [
                    'code' => 'approvals', 'name' => 'Approval Workflows', 'type' => 'page', 'route' => '/project/settings/approvals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Approvals']],
                ],
                [
                    'code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/project/settings/general',
                    'actions' => [['code' => 'view', 'name' => 'View Settings'], ['code' => 'update', 'name' => 'Update Settings']],
                ],
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
            'project.capex_projects' => 'projects.capex-projects',
            'project.turnaround' => 'projects.turnaround-shutdown',
            'project.maintenance' => 'projects.maintenance-projects',
            'project.tasks' => 'tasks.task-list',
            'project.gantt' => 'smart-scheduling.gantt-cpm',
            'project.resources' => 'resources.resource-allocation',
            'project.timesheets' => 'timesheets.timesheet-list',
            'project.boq' => 'boq-measurements.measurement-list',
            'project.evm' => 'boq-measurements.earned-value',
            'project.rfi' => 'digital-engineering.rfi-manager',
            'project.bim' => 'digital-engineering.bim-viewer',
            'project.site_telemetry' => 'site-operations.iot-feed',
            'project.risk_forecast' => 'risk-intelligence.predictive-delay',
            'project.hse' => 'risk-intelligence.safety-monitor',
        ],
        'consumes' => [
            'eam.work_orders' => 'aero-eam',
            'eam.asset_registry' => 'aero-eam',
            'finance.capex_requests' => 'aero-finance',
            'finance.budgets' => 'aero-finance',
            'scm.purchase_orders' => 'aero-scm',
            'hrm.workforce_scheduling' => 'aero-hrm',
            'iot.device_telemetry' => 'aero-iot',
            'rfi.rfi_workflow' => 'aero-rfi',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'project_admin_role' => 'project-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'project-access'],
    ],
];
