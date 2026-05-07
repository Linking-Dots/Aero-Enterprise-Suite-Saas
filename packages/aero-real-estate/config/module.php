<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Real Estate Module — Property & Lease Management
    |--------------------------------------------------------------------------
    |
    | Manages properties, agents, listings, leases, maintenance
    | requests, and real estate reporting.
    |
    */

    'code' => 'real-estate',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Real Estate & Facilities',
    'description' => 'Properties, portfolios, leases, tenants, facility management, space planning, utilities, CAM, and EAM-aligned facility assets.',
    'version' => '2.0.0',
    'category' => 'industry',
    'icon' => 'BuildingOffice2Icon',
    'priority' => 32,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('REAL_ESTATE_MODULE_ENABLED', true),
    'min_plan' => null,
    'minimum_plan' => null,
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => 'real-estate',

    'submodules' => [

        // ==================== REAL ESTATE DASHBOARD ====================
        [
            'code' => 'real-estate-dashboard',
            'name' => 'Real Estate Dashboard',
            'description' => 'Overview of properties, listings, leases, and maintenance metrics.',
            'icon' => 'ChartPieIcon',
            'route' => 'tenant.real-estate.dashboard',
            'priority' => 1,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== PROPERTIES ====================
        [
            'code' => 'properties',
            'name' => 'Properties',
            'description' => 'Property records, details, and photo management.',
            'icon' => 'HomeModernIcon',
            'route' => 'tenant.real-estate.properties.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'property-list',
                    'name' => 'Property List',
                    'description' => 'View and manage all properties.',
                    'route_name' => 'tenant.real-estate.properties.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'property-photos',
                    'name' => 'Property Photos',
                    'description' => 'Upload and manage property photos.',
                    'route_name' => 'tenant.real-estate.properties.photos.upload',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'create', 'name' => 'Upload Photos', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Photos', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== AGENTS ====================
        [
            'code' => 'agents',
            'name' => 'Agents',
            'description' => 'Real estate agent profiles and assignment management.',
            'icon' => 'UserGroupIcon',
            'route' => 'tenant.real-estate.agents.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'agent-list',
                    'name' => 'Agent List',
                    'description' => 'View and manage all agents.',
                    'route_name' => 'tenant.real-estate.agents.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== LISTINGS ====================
        [
            'code' => 'listings',
            'name' => 'Listings',
            'description' => 'Property listings with inquiry tracking and showing scheduling.',
            'icon' => 'NewspaperIcon',
            'route' => 'tenant.real-estate.listings.index',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'listing-list',
                    'name' => 'Listing List',
                    'description' => 'View and manage all property listings.',
                    'route_name' => 'tenant.real-estate.listings.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'listing-inquiries',
                    'name' => 'Inquiries',
                    'description' => 'Manage inquiries for a listing.',
                    'route_name' => 'tenant.real-estate.listings.inquiries.store',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'create', 'name' => 'Create Inquiry', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'listing-showings',
                    'name' => 'Showings',
                    'description' => 'Schedule and manage property showings.',
                    'route_name' => 'tenant.real-estate.listings.showings.store',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'create', 'name' => 'Schedule Showing', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== LEASES ====================
        [
            'code' => 'leases',
            'name' => 'Leases',
            'description' => 'Lease agreements, renewals, and payment tracking.',
            'icon' => 'DocumentDuplicateIcon',
            'route' => 'tenant.real-estate.leases.index',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'lease-list',
                    'name' => 'Lease List',
                    'description' => 'View and manage all lease agreements.',
                    'route_name' => 'tenant.real-estate.leases.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'lease-payments',
                    'name' => 'Lease Payments',
                    'description' => 'Record and view payment history for leases.',
                    'route_name' => 'tenant.real-estate.leases.payments.index',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Payments', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Record Payment', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== MAINTENANCE ====================
        [
            'code' => 'maintenance',
            'name' => 'Maintenance',
            'description' => 'Maintenance request management, vendor assignment, and completion tracking.',
            'icon' => 'WrenchScrewdriverIcon',
            'route' => 'tenant.real-estate.maintenance.index',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'maintenance-list',
                    'name' => 'Maintenance Requests',
                    'description' => 'View and manage all maintenance requests.',
                    'route_name' => 'tenant.real-estate.maintenance.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'assign', 'name' => 'Assign Vendor', 'is_active' => true],
                        ['code' => 'complete', 'name' => 'Mark Complete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== PORTFOLIOS ====================
        [
            'code' => 'portfolios',
            'name' => 'Portfolios',
            'description' => 'Group properties into portfolios (investor, geography, type).',
            'icon' => 'Squares2X2Icon',
            'route' => 'tenant.real-estate.portfolios.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'portfolio-list',
                    'name' => 'Portfolio List',
                    'route_name' => 'tenant.real-estate.portfolios.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== UNITS ====================
        [
            'code' => 'units',
            'name' => 'Units / Suites',
            'description' => 'Unit-level details within a property (apartments, suites, floors).',
            'icon' => 'RectangleGroupIcon',
            'route' => 'tenant.real-estate.units.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'unit-list', 'name' => 'Unit List',
                    'route_name' => 'tenant.real-estate.units.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'unit-availability', 'name' => 'Unit Availability Matrix',
                    'route_name' => 'tenant.real-estate.units.availability',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Availability', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== TENANTS ====================
        [
            'code' => 'tenants-occupants',
            'name' => 'Tenants & Occupants',
            'description' => 'Tenant profiles, contacts, occupancy, screening.',
            'icon' => 'UsersIcon',
            'route' => 'tenant.real-estate.tenants.index',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'tenant-list', 'name' => 'Tenant List',
                    'route_name' => 'tenant.real-estate.tenants.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'screen', 'name' => 'Screen Tenant', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'tenant-portal', 'name' => 'Tenant Portal',
                    'route_name' => 'tenant.real-estate.tenants.portal',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== LEASE ACCOUNTING ====================
        [
            'code' => 'lease-accounting',
            'name' => 'Lease Accounting (IFRS 16 / ASC 842)',
            'description' => 'Right-of-use asset, lease liability, amortization, reassessment.',
            'icon' => 'CalculatorIcon',
            'route' => 'tenant.real-estate.lease-accounting.index',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'rou-schedule', 'name' => 'ROU Schedule',
                    'route_name' => 'tenant.real-estate.lease-accounting.rou',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'run', 'name' => 'Run Amortization', 'is_active' => true]],
                ],
                [
                    'code' => 'lease-reassessment', 'name' => 'Reassessment / Modification',
                    'route_name' => 'tenant.real-estate.lease-accounting.reassessment',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Reassessment', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== RENT ROLLS & CAM ====================
        [
            'code' => 'rent-cam',
            'name' => 'Rent Rolls & CAM',
            'description' => 'Rent rolls, Common Area Maintenance (CAM) reconciliation.',
            'icon' => 'TableCellsIcon',
            'route' => 'tenant.real-estate.rent-cam.index',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'rent-roll', 'name' => 'Rent Roll',
                    'route_name' => 'tenant.real-estate.rent-cam.rent-roll',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rent Roll', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'cam-reconciliation', 'name' => 'CAM Reconciliation',
                    'route_name' => 'tenant.real-estate.rent-cam.cam',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View CAM', 'is_active' => true],
                        ['code' => 'reconcile', 'name' => 'Run CAM Reconciliation', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'escalations', 'name' => 'Rent Escalations',
                    'route_name' => 'tenant.real-estate.rent-cam.escalations',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Escalations', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== FACILITY MANAGEMENT (EAM) ====================
        [
            'code' => 'facility-management',
            'name' => 'Facility Management (EAM)',
            'description' => 'Facility assets, preventive maintenance, inspections, work orders.',
            'icon' => 'WrenchScrewdriverIcon',
            'route' => 'tenant.real-estate.facility.index',
            'priority' => 9,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'facility-assets', 'name' => 'Facility Assets (HVAC, Elevators, Lighting)',
                    'route_name' => 'tenant.real-estate.facility.assets',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Assets', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Add Asset', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Asset', 'is_active' => true],
                        ['code' => 'link-eam', 'name' => 'Link to EAM Asset', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'preventive-maintenance', 'name' => 'Preventive Maintenance Schedule',
                    'route_name' => 'tenant.real-estate.facility.pm',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View PM Schedule', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create PM', 'is_active' => true],
                        ['code' => 'trigger-wo', 'name' => 'Trigger Work Order', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'inspections', 'name' => 'Facility Inspections',
                    'route_name' => 'tenant.real-estate.facility.inspections',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Inspections', 'is_active' => true],
                        ['code' => 'schedule', 'name' => 'Schedule Inspection', 'is_active' => true],
                        ['code' => 'conduct', 'name' => 'Conduct Inspection', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'work-orders', 'name' => 'Facility Work Orders',
                    'route_name' => 'tenant.real-estate.facility.work-orders',
                    'priority' => 4, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Work Orders', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Work Order', 'is_active' => true],
                        ['code' => 'assign', 'name' => 'Assign Work Order', 'is_active' => true],
                        ['code' => 'complete', 'name' => 'Complete Work Order', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'janitorial', 'name' => 'Janitorial / Cleaning Schedules',
                    'route_name' => 'tenant.real-estate.facility.janitorial',
                    'priority' => 5, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cleaning Schedules', 'is_active' => true]],
                ],
                [
                    'code' => 'security-services', 'name' => 'Security / Access Control',
                    'route_name' => 'tenant.real-estate.facility.security',
                    'priority' => 6, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Security Services', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== SPACE PLANNING ====================
        [
            'code' => 'space-planning',
            'name' => 'Space Planning & Occupancy',
            'description' => 'Floor plans, space allocation, occupancy tracking, hot-desking.',
            'icon' => 'MapIcon',
            'route' => 'tenant.real-estate.space.index',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'floor-plans', 'name' => 'Floor Plans',
                    'route_name' => 'tenant.real-estate.space.floor-plans',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'upload', 'name' => 'Upload Floor Plan', 'is_active' => true],
                        ['code' => 'edit', 'name' => 'Edit Floor Plan', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'space-allocation', 'name' => 'Space Allocation',
                    'route_name' => 'tenant.real-estate.space.allocation',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Allocation', 'is_active' => true],
                        ['code' => 'allocate', 'name' => 'Allocate Space', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'hot-desking', 'name' => 'Hot-Desking / Room Booking',
                    'route_name' => 'tenant.real-estate.space.booking',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bookings', 'is_active' => true],
                        ['code' => 'book', 'name' => 'Book Desk/Room', 'is_active' => true],
                        ['code' => 'cancel', 'name' => 'Cancel Booking', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'occupancy-analytics', 'name' => 'Occupancy Analytics',
                    'route_name' => 'tenant.real-estate.space.occupancy',
                    'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Occupancy', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== UTILITIES & METERING (EAM) ====================
        [
            'code' => 'utilities',
            'name' => 'Utilities & Metering',
            'description' => 'Utility meter tracking, consumption, billing allocation, tenant sub-metering.',
            'icon' => 'BoltIcon',
            'route' => 'tenant.real-estate.utilities.index',
            'priority' => 11,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'meters', 'name' => 'Meters (Electric / Water / Gas)',
                    'route_name' => 'tenant.real-estate.utilities.meters',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Meters', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Add Meter', 'is_active' => true],
                        ['code' => 'read', 'name' => 'Capture Reading', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'utility-bills', 'name' => 'Utility Bills',
                    'route_name' => 'tenant.real-estate.utilities.bills',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bills', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Record Bill', 'is_active' => true],
                        ['code' => 'allocate', 'name' => 'Allocate to Tenants', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'sub-metering', 'name' => 'Sub-Metering & Tenant Billing',
                    'route_name' => 'tenant.real-estate.utilities.sub-metering',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Sub-Metering', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== VENDOR MANAGEMENT ====================
        [
            'code' => 'vendors',
            'name' => 'Vendors & Service Providers',
            'description' => 'Facility vendors, insurance verification, service contracts.',
            'icon' => 'BuildingStorefrontIcon',
            'route' => 'tenant.real-estate.vendors.index',
            'priority' => 12,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'vendor-list', 'name' => 'Vendor List',
                    'route_name' => 'tenant.real-estate.vendors.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'service-contracts', 'name' => 'Service Contracts',
                    'route_name' => 'tenant.real-estate.vendors.contracts',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Contracts', 'is_active' => true]],
                ],
                [
                    'code' => 'vendor-insurance', 'name' => 'Insurance Verification',
                    'route_name' => 'tenant.real-estate.vendors.insurance',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Insurance', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify Certificate', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== MARKETING ====================
        [
            'code' => 'marketing',
            'name' => 'Marketing',
            'description' => 'Website syndication, virtual tours, campaigns.',
            'icon' => 'MegaphoneIcon',
            'route' => 'tenant.real-estate.marketing.index',
            'priority' => 13,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'syndication', 'name' => 'Listing Syndication',
                    'route_name' => 'tenant.real-estate.marketing.syndication',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Syndication', 'is_active' => true]],
                ],
                [
                    'code' => 'virtual-tours', 'name' => 'Virtual Tours',
                    'route_name' => 'tenant.real-estate.marketing.virtual-tours',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Virtual Tours', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== DOCUMENTS ====================
        [
            'code' => 'documents',
            'name' => 'Documents',
            'description' => 'Lease docs, titles, insurance, permits per property.',
            'icon' => 'FolderIcon',
            'route' => 'tenant.real-estate.documents.index',
            'priority' => 14,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'document-list', 'name' => 'Document List',
                    'route_name' => 'tenant.real-estate.documents.index',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'upload', 'name' => 'Upload', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== MOVES ====================
        [
            'code' => 'move-management',
            'name' => 'Move Management',
            'description' => 'Tenant move-in/out, keys, inventory, final settlement.',
            'icon' => 'ArrowsRightLeftIcon',
            'route' => 'tenant.real-estate.moves.index',
            'priority' => 15,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'move-in', 'name' => 'Move-In',
                    'route_name' => 'tenant.real-estate.moves.move-in',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Move-In', 'is_active' => true]],
                ],
                [
                    'code' => 'move-out', 'name' => 'Move-Out',
                    'route_name' => 'tenant.real-estate.moves.move-out',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Move-Out', 'is_active' => true],
                        ['code' => 'inspect', 'name' => 'Inspect Condition', 'is_active' => true],
                        ['code' => 'settle', 'name' => 'Settle Deposit', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== REPORTS ====================
        [
            'code' => 'real-estate-reports',
            'name' => 'Reports',
            'description' => 'Property, lease, financial, and facility analytics.',
            'icon' => 'ChartBarIcon',
            'route' => 'tenant.real-estate.reports',
            'priority' => 16,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'report-list',
                    'name' => 'Report List',
                    'description' => 'View available real estate reports.',
                    'route_name' => 'tenant.real-estate.reports',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'investor-reports', 'name' => 'Investor Reports',
                    'route_name' => 'tenant.real-estate.reports.investor',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'generate', 'name' => 'Generate Report', 'is_active' => true]],
                ],
                [
                    'code' => 'occupancy-reports', 'name' => 'Occupancy Reports',
                    'route_name' => 'tenant.real-estate.reports.occupancy',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'settings',
            'name' => 'Real Estate Settings',
            'description' => 'Workflows, templates, property types.',
            'icon' => 'CogIcon',
            'route' => 'tenant.real-estate.settings.index',
            'priority' => 99,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'property-types', 'name' => 'Property Types',
                    'route_name' => 'tenant.real-estate.settings.property-types',
                    'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Property Types', 'is_active' => true]],
                ],
                [
                    'code' => 'lease-templates', 'name' => 'Lease Templates',
                    'route_name' => 'tenant.real-estate.settings.lease-templates',
                    'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Templates', 'is_active' => true]],
                ],
                [
                    'code' => 'general', 'name' => 'General Settings',
                    'route_name' => 'tenant.real-estate.settings.index',
                    'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Settings', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Settings', 'is_active' => true],
                    ],
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
            'facility.assets' => 'facility-management.facility-assets',
            'facility.preventive_maintenance' => 'facility-management.preventive-maintenance',
            'facility.work_orders' => 'facility-management.work-orders',
            'facility.inspections' => 'facility-management.inspections',
            'facility.space_planning' => 'space-planning',
            'facility.occupancy' => 'space-planning.occupancy-analytics',
            'facility.utilities_meters' => 'utilities.meters',
            'facility.utility_consumption' => 'utilities.utility-bills',
            'facility.vendors' => 'vendors.vendor-list',
            'facility.service_contracts' => 'vendors.service-contracts',
            'facility.leases' => 'leases.lease-list',
            'facility.lease_rou' => 'lease-accounting.rou-schedule',
            'facility.tenants' => 'tenants-occupants.tenant-list',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_orders' => 'aero-eam',
            'finance.fixed_assets' => 'aero-finance',
            'finance.lease_amortization' => 'aero-finance',
            'ims.maintenance_parts' => 'aero-ims',
            'iot.facility_sensors' => 'aero-iot',
            'compliance.building_permits' => 'aero-compliance',
            'scm.service_contracts' => 'aero-scm',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'real_estate_admin_role' => 'real-estate-admin',
        'facility_admin_role' => 'facility-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'real-estate-access'],
    ],
];
