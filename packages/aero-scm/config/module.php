<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supply Chain Management (SCM) Module Configuration
    |--------------------------------------------------------------------------
    | EAM-relevant: MRO (Maintenance, Repair, Operations) procurement,
    | spare parts sourcing, contractor/service procurement for maintenance,
    | supplier performance for asset-critical spares.
    */

    'code' => 'scm',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Supply Chain Management',
    'description' => 'End-to-end SCM: procurement, sourcing, vendor management, logistics, production planning, demand forecasting, and MRO for EAM',
    'icon' => 'TruckIcon',
    'route_prefix' => '/scm',
    'category' => 'business',
    'priority' => 15,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('SCM_MODULE_ENABLED', true),
    'version' => '2.0.0',
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'dashboard' => true,
        'procurement' => true,
        'sourcing' => true,
        'vendor_management' => true,
        'contract_management' => true,
        'mro_procurement' => true, // EAM
        'service_procurement' => true, // EAM contractor services
        'requisitions' => true,
        'purchase_orders' => true,
        'goods_receipt' => true,
        'supplier_portal' => true,
        'logistics' => true,
        'shipment_tracking' => true,
        'customs_trade' => true,
        'warehousing' => true,
        'production_planning' => true,
        'mrp' => true,
        'demand_forecasting' => true,
        'supplier_performance' => true,
        'spend_analytics' => true,
        'risk_compliance' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        /* 1 DASHBOARD */
        [
            'code' => 'dashboard', 'name' => 'SCM Dashboard',
            'description' => 'Procurement, spend, OTD, and supplier KPIs',
            'icon' => 'HomeIcon', 'route' => '/scm/dashboard', 'priority' => 1,
            'components' => [
                ['code' => 'scm-dashboard', 'name' => 'SCM Dashboard', 'type' => 'page', 'route' => '/scm/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
                ['code' => 'mro-dashboard', 'name' => 'MRO Dashboard', 'type' => 'page', 'route' => '/scm/dashboard/mro',
                    'actions' => [['code' => 'view', 'name' => 'View MRO Dashboard']]],
            ],
        ],

        /* 2 SOURCING */
        [
            'code' => 'sourcing', 'name' => 'Sourcing & RFx',
            'description' => 'RFI/RFQ/RFP events, bid evaluation, auctions, and supplier onboarding',
            'icon' => 'MagnifyingGlassIcon', 'route' => '/scm/sourcing', 'priority' => 2,
            'components' => [
                ['code' => 'rfx-events', 'name' => 'RFx Events', 'type' => 'page', 'route' => '/scm/sourcing/rfx',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RFx Events'],
                        ['code' => 'create', 'name' => 'Create RFx'],
                        ['code' => 'publish', 'name' => 'Publish RFx'],
                        ['code' => 'close', 'name' => 'Close RFx'],
                        ['code' => 'award', 'name' => 'Award RFx'],
                    ]],
                ['code' => 'bid-evaluation', 'name' => 'Bid Evaluation', 'type' => 'page', 'route' => '/scm/sourcing/evaluation',
                    'actions' => [['code' => 'view', 'name' => 'View Evaluations'], ['code' => 'score', 'name' => 'Score Bid']]],
                ['code' => 'reverse-auctions', 'name' => 'Reverse Auctions', 'type' => 'page', 'route' => '/scm/sourcing/auctions',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Auctions']]],
                ['code' => 'catalog-sourcing', 'name' => 'Catalog Sourcing', 'type' => 'page', 'route' => '/scm/sourcing/catalog',
                    'actions' => [['code' => 'view', 'name' => 'View Catalog'], ['code' => 'manage', 'name' => 'Manage Catalog']]],
            ],
        ],

        /* 3 VENDOR MANAGEMENT */
        [
            'code' => 'vendor-management', 'name' => 'Vendor Management',
            'description' => 'Vendor master, qualification, classifications, and compliance',
            'icon' => 'BuildingStorefrontIcon', 'route' => '/scm/vendors', 'priority' => 3,
            'components' => [
                ['code' => 'vendor-master', 'name' => 'Vendor Master', 'type' => 'page', 'route' => '/scm/vendors',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Vendors'],
                        ['code' => 'create', 'name' => 'Create Vendor'],
                        ['code' => 'update', 'name' => 'Update Vendor'],
                        ['code' => 'delete', 'name' => 'Delete Vendor'],
                        ['code' => 'approve', 'name' => 'Approve Vendor'],
                        ['code' => 'block', 'name' => 'Block Vendor'],
                        ['code' => 'import', 'name' => 'Import Vendors'],
                        ['code' => 'export', 'name' => 'Export Vendors'],
                    ]],
                ['code' => 'vendor-qualification', 'name' => 'Qualification & Onboarding', 'type' => 'page', 'route' => '/scm/vendors/qualification',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Qualifications'],
                        ['code' => 'initiate', 'name' => 'Initiate Qualification'],
                        ['code' => 'verify', 'name' => 'Verify Documents'],
                        ['code' => 'approve', 'name' => 'Approve Qualification'],
                    ]],
                ['code' => 'vendor-classifications', 'name' => 'Classifications & Categories', 'type' => 'page', 'route' => '/scm/vendors/classifications',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Classifications']]],
                /* EAM: pre-approved vendors for critical spares & maintenance services */
                ['code' => 'approved-vendor-list', 'name' => 'Approved Vendor List (AVL)', 'type' => 'page', 'route' => '/scm/vendors/avl',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View AVL'],
                        ['code' => 'add', 'name' => 'Add to AVL'],
                        ['code' => 'remove', 'name' => 'Remove from AVL'],
                        ['code' => 'export', 'name' => 'Export AVL'],
                    ]],
                ['code' => 'vendor-compliance', 'name' => 'Vendor Compliance & Certifications', 'type' => 'page', 'route' => '/scm/vendors/compliance',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Compliance'],
                        ['code' => 'track', 'name' => 'Track Certifications'],
                        ['code' => 'remind', 'name' => 'Send Renewal Reminder'],
                    ]],
                /* EAM: contractor/service vendor management */
                ['code' => 'contractor-management', 'name' => 'Contractors & Service Vendors', 'type' => 'page', 'route' => '/scm/vendors/contractors',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Contractors'],
                        ['code' => 'manage', 'name' => 'Manage Contractor'],
                        ['code' => 'verify-insurance', 'name' => 'Verify Insurance & Certifications'],
                        ['code' => 'rate', 'name' => 'Rate Contractor Performance'],
                    ]],
            ],
        ],

        /* 4 PROCUREMENT */
        [
            'code' => 'procurement', 'name' => 'Procurement',
            'description' => 'Requisitions, POs, change orders, receipts, returns',
            'icon' => 'ShoppingCartIcon', 'route' => '/scm/procurement', 'priority' => 4,
            'components' => [
                ['code' => 'requisitions', 'name' => 'Purchase Requisitions', 'type' => 'page', 'route' => '/scm/procurement/requisitions',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Requisitions'],
                        ['code' => 'create', 'name' => 'Create Requisition'],
                        ['code' => 'update', 'name' => 'Update Requisition'],
                        ['code' => 'delete', 'name' => 'Delete Requisition'],
                        ['code' => 'submit', 'name' => 'Submit for Approval'],
                        ['code' => 'approve', 'name' => 'Approve Requisition'],
                        ['code' => 'reject', 'name' => 'Reject Requisition'],
                        ['code' => 'convert-po', 'name' => 'Convert to PO'],
                    ]],
                ['code' => 'purchase-orders', 'name' => 'Purchase Orders', 'type' => 'page', 'route' => '/scm/procurement/purchase-orders',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View POs'],
                        ['code' => 'create', 'name' => 'Create PO'],
                        ['code' => 'update', 'name' => 'Update PO'],
                        ['code' => 'delete', 'name' => 'Delete PO'],
                        ['code' => 'approve', 'name' => 'Approve PO'],
                        ['code' => 'send', 'name' => 'Send PO to Vendor'],
                        ['code' => 'acknowledge', 'name' => 'Capture PO Acknowledgment'],
                        ['code' => 'cancel', 'name' => 'Cancel PO'],
                        ['code' => 'close', 'name' => 'Close PO'],
                    ]],
                ['code' => 'change-orders', 'name' => 'Change Orders', 'type' => 'page', 'route' => '/scm/procurement/change-orders',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Change Orders']]],
                ['code' => 'goods-receipt', 'name' => 'Goods Receipt (GRN)', 'type' => 'page', 'route' => '/scm/procurement/grn',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View GRNs'],
                        ['code' => 'create', 'name' => 'Create GRN'],
                        ['code' => 'update', 'name' => 'Update GRN'],
                        ['code' => 'delete', 'name' => 'Delete GRN'],
                        ['code' => 'inspect', 'name' => 'Quality Inspect Receipt'],
                    ]],
                ['code' => 'return-to-vendor', 'name' => 'Return to Vendor (RTV)', 'type' => 'page', 'route' => '/scm/procurement/rtv',
                    'actions' => [['code' => 'view', 'name' => 'View RTVs'], ['code' => 'create', 'name' => 'Create RTV']]],
                ['code' => 'invoice-match', 'name' => 'Invoice Matching (3-way)', 'type' => 'page', 'route' => '/scm/procurement/invoice-match',
                    'actions' => [['code' => 'view', 'name' => 'View Matches'], ['code' => 'match', 'name' => 'Run Matching']]],
                /* EAM: emergency/urgent procurement for breakdowns */
                ['code' => 'emergency-procurement', 'name' => 'Emergency / Urgent Procurement', 'type' => 'page', 'route' => '/scm/procurement/emergency',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Emergency Orders'],
                        ['code' => 'create', 'name' => 'Create Emergency Order'],
                        ['code' => 'approve', 'name' => 'Fast-Track Approve'],
                        ['code' => 'audit', 'name' => 'Audit Emergency Spend'],
                    ]],
                /* EAM: service procurement (labor/contractor) */
                ['code' => 'service-procurement', 'name' => 'Service Procurement (Labor / Contractor)', 'type' => 'page', 'route' => '/scm/procurement/services',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Service Orders'],
                        ['code' => 'create', 'name' => 'Create Service Order'],
                        ['code' => 'approve', 'name' => 'Approve Service Order'],
                        ['code' => 'accept', 'name' => 'Accept Service Completion'],
                    ]],
                ['code' => 'punchout', 'name' => 'PunchOut / Vendor Catalogs', 'type' => 'page', 'route' => '/scm/procurement/punchout',
                    'actions' => [['code' => 'configure', 'name' => 'Configure PunchOut']]],
            ],
        ],

        /* 5 CONTRACT MANAGEMENT */
        [
            'code' => 'contracts', 'name' => 'Contract Management',
            'description' => 'Master contracts, SLAs, price lists, renewals, and e-sign',
            'icon' => 'DocumentDuplicateIcon', 'route' => '/scm/contracts', 'priority' => 5,
            'components' => [
                ['code' => 'master-contracts', 'name' => 'Master Contracts', 'type' => 'page', 'route' => '/scm/contracts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Contracts'],
                        ['code' => 'create', 'name' => 'Create Contract'],
                        ['code' => 'update', 'name' => 'Update Contract'],
                        ['code' => 'delete', 'name' => 'Delete Contract'],
                        ['code' => 'sign', 'name' => 'Send for E-Signature'],
                        ['code' => 'renew', 'name' => 'Renew Contract'],
                        ['code' => 'terminate', 'name' => 'Terminate Contract'],
                    ]],
                ['code' => 'slas', 'name' => 'Service Level Agreements (SLAs)', 'type' => 'page', 'route' => '/scm/contracts/slas',
                    'actions' => [['code' => 'manage', 'name' => 'Manage SLAs'], ['code' => 'monitor', 'name' => 'Monitor SLA Performance']]],
                ['code' => 'price-lists', 'name' => 'Price Lists', 'type' => 'page', 'route' => '/scm/contracts/price-lists',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Price Lists']]],
                ['code' => 'renewals', 'name' => 'Contract Renewals', 'type' => 'page', 'route' => '/scm/contracts/renewals',
                    'actions' => [['code' => 'view', 'name' => 'View Upcoming Renewals'], ['code' => 'remind', 'name' => 'Send Renewal Reminder']]],
                /* EAM: maintenance contracts */
                ['code' => 'amc-contracts', 'name' => 'AMC / Service Contracts', 'type' => 'page', 'route' => '/scm/contracts/amc',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View AMCs'],
                        ['code' => 'create', 'name' => 'Create AMC'],
                        ['code' => 'link-asset', 'name' => 'Link AMC to Asset'],
                        ['code' => 'claim', 'name' => 'Record AMC Service Call'],
                    ]],
            ],
        ],

        /* 6 SUPPLIER PORTAL */
        [
            'code' => 'supplier-portal', 'name' => 'Supplier Portal',
            'description' => 'Self-service vendor portal for POs, ASNs, invoices, and payments',
            'icon' => 'UserGroupIcon', 'route' => '/scm/supplier-portal', 'priority' => 6,
            'components' => [
                ['code' => 'portal-config', 'name' => 'Portal Configuration', 'type' => 'page', 'route' => '/scm/supplier-portal/config',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal']]],
                ['code' => 'portal-users', 'name' => 'Portal Users', 'type' => 'page', 'route' => '/scm/supplier-portal/users',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Portal Users']]],
                ['code' => 'asn', 'name' => 'Advance Shipment Notices (ASN)', 'type' => 'page', 'route' => '/scm/supplier-portal/asn',
                    'actions' => [['code' => 'view', 'name' => 'View ASNs'], ['code' => 'create', 'name' => 'Create ASN']]],
            ],
        ],

        /* 7 LOGISTICS & SHIPPING */
        [
            'code' => 'logistics', 'name' => 'Logistics & Shipping',
            'description' => 'Inbound/outbound logistics, carriers, shipments, tracking',
            'icon' => 'MapIcon', 'route' => '/scm/logistics', 'priority' => 7,
            'components' => [
                ['code' => 'carriers', 'name' => 'Carriers', 'type' => 'page', 'route' => '/scm/logistics/carriers',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Carriers']]],
                ['code' => 'shipments', 'name' => 'Shipments', 'type' => 'page', 'route' => '/scm/logistics/shipments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Shipments'],
                        ['code' => 'create', 'name' => 'Create Shipment'],
                        ['code' => 'track', 'name' => 'Track Shipment'],
                        ['code' => 'close', 'name' => 'Close Shipment'],
                    ]],
                ['code' => 'freight-rates', 'name' => 'Freight Rates & Routing', 'type' => 'page', 'route' => '/scm/logistics/freight',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Freight Rates']]],
                ['code' => 'customs-trade', 'name' => 'Customs & Trade Compliance', 'type' => 'page', 'route' => '/scm/logistics/customs',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Customs Declarations'],
                        ['code' => 'file', 'name' => 'File Declaration'],
                        ['code' => 'classify', 'name' => 'HS-Code Classification'],
                    ]],
                ['code' => 'load-planning', 'name' => 'Load & Dock Planning', 'type' => 'page', 'route' => '/scm/logistics/load-planning',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Loads / Dock Schedules']]],
                ['code' => 'proof-of-delivery', 'name' => 'Proof of Delivery (POD)', 'type' => 'page', 'route' => '/scm/logistics/pod',
                    'actions' => [['code' => 'capture', 'name' => 'Capture POD'], ['code' => 'view', 'name' => 'View POD']]],
            ],
        ],

        /* 8 WAREHOUSING (bridge to aero-ims) */
        [
            'code' => 'warehousing', 'name' => 'Warehousing (SCM Bridge)',
            'description' => 'Warehouse ops tied to procurement (cross-dock, put-away, replenishment)',
            'icon' => 'BuildingLibraryIcon', 'route' => '/scm/warehousing', 'priority' => 8,
            'components' => [
                ['code' => 'putaway', 'name' => 'Put-Away', 'type' => 'page', 'route' => '/scm/warehousing/putaway',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Put-Away']]],
                ['code' => 'cross-dock', 'name' => 'Cross-Docking', 'type' => 'page', 'route' => '/scm/warehousing/cross-dock',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Cross-Dock']]],
                ['code' => 'replenishment', 'name' => 'Replenishment Planning', 'type' => 'page', 'route' => '/scm/warehousing/replenishment',
                    'actions' => [['code' => 'run', 'name' => 'Run Replenishment']]],
            ],
        ],

        /* 9 PRODUCTION & MRP */
        [
            'code' => 'production', 'name' => 'Production Planning & MRP',
            'description' => 'BOM, MRP runs, capacity, work orders (production)',
            'icon' => 'CogIcon', 'route' => '/scm/production', 'priority' => 9,
            'components' => [
                ['code' => 'bom', 'name' => 'Bill of Materials (BOM)', 'type' => 'page', 'route' => '/scm/production/bom',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View BOMs'],
                        ['code' => 'create', 'name' => 'Create BOM'],
                        ['code' => 'update', 'name' => 'Update BOM'],
                        ['code' => 'delete', 'name' => 'Delete BOM'],
                        ['code' => 'version', 'name' => 'Version BOM'],
                    ]],
                ['code' => 'mrp', 'name' => 'MRP Runs', 'type' => 'page', 'route' => '/scm/production/mrp',
                    'actions' => [['code' => 'run', 'name' => 'Run MRP'], ['code' => 'view', 'name' => 'View MRP Results']]],
                ['code' => 'capacity-planning', 'name' => 'Capacity Planning', 'type' => 'page', 'route' => '/scm/production/capacity',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Capacity']]],
                ['code' => 'production-orders', 'name' => 'Production Orders', 'type' => 'page', 'route' => '/scm/production/orders',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Production Orders'],
                        ['code' => 'create', 'name' => 'Create Production Order'],
                        ['code' => 'release', 'name' => 'Release Order'],
                        ['code' => 'close', 'name' => 'Close Order'],
                    ]],
                ['code' => 'shop-floor', 'name' => 'Shop-Floor Control', 'type' => 'page', 'route' => '/scm/production/shop-floor',
                    'actions' => [['code' => 'view', 'name' => 'View Shop Floor'], ['code' => 'report', 'name' => 'Report Operation']]],
            ],
        ],

        /* 10 DEMAND & FORECASTING */
        [
            'code' => 'demand-forecast', 'name' => 'Demand & Forecasting',
            'description' => 'Demand planning, sales & operations planning (S&OP)',
            'icon' => 'ArrowTrendingUpIcon', 'route' => '/scm/demand', 'priority' => 10,
            'components' => [
                ['code' => 'forecast', 'name' => 'Demand Forecast', 'type' => 'page', 'route' => '/scm/demand/forecast',
                    'actions' => [['code' => 'view', 'name' => 'View Forecast'], ['code' => 'generate', 'name' => 'Generate Forecast']]],
                ['code' => 'sop', 'name' => 'S&OP', 'type' => 'page', 'route' => '/scm/demand/sop',
                    'actions' => [['code' => 'manage', 'name' => 'Manage S&OP']]],
                ['code' => 'consumption-analysis', 'name' => 'Consumption Analysis', 'type' => 'page', 'route' => '/scm/demand/consumption',
                    'actions' => [['code' => 'view', 'name' => 'View Consumption']]],
            ],
        ],

        /* 11 SUPPLIER PERFORMANCE */
        [
            'code' => 'supplier-performance', 'name' => 'Supplier Performance',
            'description' => 'KPIs, scorecards, OTD, quality rating, risk scoring',
            'icon' => 'StarIcon', 'route' => '/scm/supplier-performance', 'priority' => 11,
            'components' => [
                ['code' => 'scorecards', 'name' => 'Supplier Scorecards', 'type' => 'page', 'route' => '/scm/supplier-performance/scorecards',
                    'actions' => [['code' => 'view', 'name' => 'View Scorecards'], ['code' => 'publish', 'name' => 'Publish Scorecard']]],
                ['code' => 'otif', 'name' => 'OTIF / OTD Tracking', 'type' => 'page', 'route' => '/scm/supplier-performance/otif',
                    'actions' => [['code' => 'view', 'name' => 'View OTIF'], ['code' => 'export', 'name' => 'Export OTIF']]],
                ['code' => 'quality-rating', 'name' => 'Quality Ratings', 'type' => 'page', 'route' => '/scm/supplier-performance/quality',
                    'actions' => [['code' => 'view', 'name' => 'View Ratings']]],
                ['code' => 'supplier-risk', 'name' => 'Supplier Risk Scoring', 'type' => 'page', 'route' => '/scm/supplier-performance/risk',
                    'actions' => [['code' => 'view', 'name' => 'View Risk Scores'], ['code' => 'run', 'name' => 'Run Risk Assessment']]],
                ['code' => 'corrective-actions', 'name' => 'Corrective Action Requests (SCAR)', 'type' => 'page', 'route' => '/scm/supplier-performance/scar',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View SCARs'],
                        ['code' => 'create', 'name' => 'Create SCAR'],
                        ['code' => 'close', 'name' => 'Close SCAR'],
                    ]],
            ],
        ],

        /* 12 SPEND ANALYTICS */
        [
            'code' => 'spend-analytics', 'name' => 'Spend Analytics',
            'description' => 'Spend cube, category analysis, savings tracking',
            'icon' => 'ChartPieIcon', 'route' => '/scm/spend', 'priority' => 12,
            'components' => [
                ['code' => 'spend-cube', 'name' => 'Spend Cube', 'type' => 'page', 'route' => '/scm/spend/cube',
                    'actions' => [['code' => 'view', 'name' => 'View Spend Cube'], ['code' => 'export', 'name' => 'Export Spend Cube']]],
                ['code' => 'category-analysis', 'name' => 'Category Analysis', 'type' => 'page', 'route' => '/scm/spend/categories',
                    'actions' => [['code' => 'view', 'name' => 'View Analysis']]],
                ['code' => 'savings', 'name' => 'Savings Tracker', 'type' => 'page', 'route' => '/scm/spend/savings',
                    'actions' => [['code' => 'view', 'name' => 'View Savings'], ['code' => 'record', 'name' => 'Record Saving']]],
                ['code' => 'tail-spend', 'name' => 'Tail Spend Management', 'type' => 'page', 'route' => '/scm/spend/tail',
                    'actions' => [['code' => 'view', 'name' => 'View Tail Spend']]],
            ],
        ],

        /* 13 RISK & COMPLIANCE */
        [
            'code' => 'risk-compliance', 'name' => 'Risk & Compliance',
            'description' => 'Supplier risk monitoring, ESG, conflict minerals, sanctions',
            'icon' => 'ShieldExclamationIcon', 'route' => '/scm/risk', 'priority' => 13,
            'components' => [
                ['code' => 'sanctions-screening', 'name' => 'Sanctions Screening', 'type' => 'page', 'route' => '/scm/risk/sanctions',
                    'actions' => [['code' => 'run', 'name' => 'Run Screening'], ['code' => 'view', 'name' => 'View Results']]],
                ['code' => 'esg-sustainability', 'name' => 'ESG & Sustainability', 'type' => 'page', 'route' => '/scm/risk/esg',
                    'actions' => [['code' => 'view', 'name' => 'View ESG Metrics'], ['code' => 'manage', 'name' => 'Manage ESG Data']]],
                ['code' => 'conflict-minerals', 'name' => 'Conflict Minerals (3TG)', 'type' => 'page', 'route' => '/scm/risk/conflict-minerals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Declarations']]],
                ['code' => 'business-continuity', 'name' => 'Business Continuity', 'type' => 'page', 'route' => '/scm/risk/continuity',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Continuity Plans']]],
            ],
        ],

        /* 14 INTEGRATIONS */
        [
            'code' => 'integrations', 'name' => 'Integrations',
            'description' => 'EDI, cXML, vendor APIs, shipping APIs',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/scm/integrations', 'priority' => 14,
            'components' => [
                ['code' => 'edi', 'name' => 'EDI (850/855/856/810)', 'type' => 'page', 'route' => '/scm/integrations/edi',
                    'actions' => [['code' => 'configure', 'name' => 'Configure EDI'], ['code' => 'view', 'name' => 'View EDI Transactions']]],
                ['code' => 'cxml', 'name' => 'cXML / PunchOut', 'type' => 'page', 'route' => '/scm/integrations/cxml',
                    'actions' => [['code' => 'configure', 'name' => 'Configure cXML']]],
                ['code' => 'shipping-apis', 'name' => 'Shipping Carrier APIs', 'type' => 'page', 'route' => '/scm/integrations/shipping',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Shipping API']]],
                ['code' => 'vendor-apis', 'name' => 'Vendor APIs / Webhooks', 'type' => 'page', 'route' => '/scm/integrations/vendor-apis',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Vendor APIs']]],
            ],
        ],

        /* 15 SETTINGS */
        [
            'code' => 'settings', 'name' => 'SCM Settings',
            'description' => 'PO numbering, approval chains, tolerance rules, procurement policies',
            'icon' => 'CogIcon', 'route' => '/scm/settings', 'priority' => 99,
            'components' => [
                ['code' => 'numbering', 'name' => 'Document Numbering', 'type' => 'page', 'route' => '/scm/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']]],
                ['code' => 'approval-chains', 'name' => 'Approval Chains', 'type' => 'page', 'route' => '/scm/settings/approvals',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Approval Chains']]],
                ['code' => 'tolerance-rules', 'name' => 'Tolerance & Matching Rules', 'type' => 'page', 'route' => '/scm/settings/tolerances',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Tolerances']]],
                ['code' => 'procurement-policies', 'name' => 'Procurement Policies', 'type' => 'page', 'route' => '/scm/settings/policies',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Policies']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/scm/settings/general',
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
            'mro.procurement' => 'procurement.emergency-procurement',
            'mro.service_procurement' => 'procurement.service-procurement',
            'vendor.master' => 'vendor-management.vendor-master',
            'vendor.avl' => 'vendor-management.approved-vendor-list',
            'vendor.contractors' => 'vendor-management.contractor-management',
            'contract.amc' => 'contracts.amc-contracts',
            'contract.sla' => 'contracts.slas',
            'po.purchase_order' => 'procurement.purchase-orders',
            'po.requisition' => 'procurement.requisitions',
            'po.grn' => 'procurement.goods-receipt',
            'supplier.performance' => 'supplier-performance.scorecards',
            'logistics.shipment_tracking' => 'logistics.shipments',
        ],
        'consumes' => [
            'eam.work_order_materials' => 'aero-eam',
            'eam.maintenance_contracts' => 'aero-eam',
            'ims.stock_availability' => 'aero-ims',
            'finance.ap_bills' => 'aero-finance',
            'quality.inspection_results' => 'aero-quality',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Settings (kept)
    |--------------------------------------------------------------------------
    */
    'procurement' => [
        'require_approval' => env('SCM_REQUIRE_APPROVAL', true),
        'approval_levels' => env('SCM_APPROVAL_LEVELS', 2),
        'auto_po_number' => env('SCM_AUTO_PO_NUMBER', true),
        'po_prefix' => env('SCM_PO_PREFIX', 'PO'),
    ],

    'suppliers' => [
        'rating_enabled' => env('SCM_SUPPLIER_RATING', true),
        'rating_criteria' => ['quality', 'delivery', 'price', 'service'],
    ],

    'logistics' => [
        'track_shipments' => env('SCM_TRACK_SHIPMENTS', true),
        'default_carrier' => env('SCM_DEFAULT_CARRIER', null),
    ],

    'production' => [
        'capacity_planning' => env('SCM_CAPACITY_PLANNING', true),
        'demand_forecast' => env('SCM_DEMAND_FORECAST', true),
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'scm_admin_role' => 'scm-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'scm-access'],
    ],
];
