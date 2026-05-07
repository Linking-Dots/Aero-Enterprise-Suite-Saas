<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Management System (IMS) Module Configuration
    |--------------------------------------------------------------------------
    | EAM-relevant: spare parts (MRO inventory), kitting for work orders,
    | consumables, bin locations near asset, critical-spare safety stock.
    */

    'code' => 'ims',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Inventory Management',
    'description' => 'Multi-warehouse inventory with item master, stock, movements, valuation, lot/serial, MRO spare parts, kitting, and WMS',
    'icon' => 'CubeIcon',
    'route_prefix' => '/ims',
    'category' => 'business',
    'priority' => 16,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('IMS_MODULE_ENABLED', true),
    'version' => '2.0.0',
    'min_plan' => 'professional',
    'minimum_plan' => 'professional',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'submodules' => [

        /* 1 DASHBOARD */
        [
            'code' => 'dashboard', 'name' => 'Inventory Dashboard',
            'description' => 'Stock health, turnover, low stock, expiring items',
            'icon' => 'HomeIcon', 'route' => '/ims/dashboard', 'priority' => 1,
            'components' => [
                ['code' => 'ims-dashboard', 'name' => 'Inventory Dashboard', 'type' => 'page', 'route' => '/ims/dashboard',
                    'actions' => [['code' => 'view', 'name' => 'View Dashboard']]],
                ['code' => 'mro-dashboard', 'name' => 'MRO Spares Dashboard', 'type' => 'page', 'route' => '/ims/dashboard/mro',
                    'actions' => [['code' => 'view', 'name' => 'View MRO Dashboard']]],
            ],
        ],

        /* 2 ITEM MASTER */
        [
            'code' => 'item-master', 'name' => 'Item Master',
            'description' => 'SKU catalog, categories, variants, attributes, UoM',
            'icon' => 'TagIcon', 'route' => '/ims/items', 'priority' => 2,
            'components' => [
                ['code' => 'items', 'name' => 'Items / SKUs', 'type' => 'page', 'route' => '/ims/items',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Items'],
                        ['code' => 'create', 'name' => 'Create Item'],
                        ['code' => 'update', 'name' => 'Update Item'],
                        ['code' => 'delete', 'name' => 'Delete Item'],
                        ['code' => 'clone', 'name' => 'Clone Item'],
                        ['code' => 'import', 'name' => 'Import Items'],
                        ['code' => 'export', 'name' => 'Export Items'],
                        ['code' => 'print-label', 'name' => 'Print Barcode Label'],
                    ]],
                ['code' => 'categories', 'name' => 'Categories', 'type' => 'page', 'route' => '/ims/categories',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Categories']]],
                ['code' => 'variants', 'name' => 'Variants & Attributes', 'type' => 'page', 'route' => '/ims/items/variants',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Variants']]],
                ['code' => 'uom', 'name' => 'Units of Measure', 'type' => 'page', 'route' => '/ims/uom',
                    'actions' => [['code' => 'manage', 'name' => 'Manage UoM']]],
                /* EAM: critical spare parts register */
                ['code' => 'mro-spare-parts', 'name' => 'MRO Spare Parts Register', 'type' => 'page', 'route' => '/ims/items/mro',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Spare Parts'],
                        ['code' => 'create', 'name' => 'Add Spare Part'],
                        ['code' => 'classify-criticality', 'name' => 'Classify Criticality'],
                        ['code' => 'link-asset', 'name' => 'Link Spare to Asset'],
                        ['code' => 'link-bom', 'name' => 'Link Spare to Asset BOM'],
                    ]],
                ['code' => 'item-images', 'name' => 'Item Images & Media', 'type' => 'page', 'route' => '/ims/items/media',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Item Media']]],
                ['code' => 'alternate-items', 'name' => 'Alternate / Substitute Items', 'type' => 'page', 'route' => '/ims/items/alternates',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Alternate Items']]],
            ],
        ],

        /* 3 WAREHOUSES & LOCATIONS */
        [
            'code' => 'warehouses', 'name' => 'Warehouses & Locations',
            'description' => 'Warehouses, zones, bins, storage hierarchy',
            'icon' => 'BuildingLibraryIcon', 'route' => '/ims/warehouses', 'priority' => 3,
            'components' => [
                ['code' => 'warehouses', 'name' => 'Warehouses', 'type' => 'page', 'route' => '/ims/warehouses',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Warehouses'],
                        ['code' => 'create', 'name' => 'Create Warehouse'],
                        ['code' => 'update', 'name' => 'Update Warehouse'],
                        ['code' => 'delete', 'name' => 'Delete Warehouse'],
                    ]],
                ['code' => 'zones', 'name' => 'Zones', 'type' => 'page', 'route' => '/ims/warehouses/zones',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Zones']]],
                ['code' => 'locations', 'name' => 'Storage Locations / Bins', 'type' => 'page', 'route' => '/ims/warehouses/locations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Locations'],
                        ['code' => 'manage', 'name' => 'Manage Locations'],
                        ['code' => 'print-label', 'name' => 'Print Location Label'],
                    ]],
                /* EAM: stockrooms near asset (point-of-use storage) */
                ['code' => 'point-of-use', 'name' => 'Point-of-Use Storage (Asset-Adjacent)', 'type' => 'page', 'route' => '/ims/warehouses/point-of-use',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Point-of-Use Bins']]],
            ],
        ],

        /* 4 STOCK MANAGEMENT */
        [
            'code' => 'stock', 'name' => 'Stock Management',
            'description' => 'Stock on hand, available, reserved, in-transit',
            'icon' => 'Squares2X2Icon', 'route' => '/ims/stock', 'priority' => 4,
            'components' => [
                ['code' => 'stock-on-hand', 'name' => 'Stock on Hand', 'type' => 'page', 'route' => '/ims/stock',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Stock'],
                        ['code' => 'export', 'name' => 'Export Stock'],
                        ['code' => 'search', 'name' => 'Search Stock'],
                    ]],
                ['code' => 'reservations', 'name' => 'Stock Reservations', 'type' => 'page', 'route' => '/ims/stock/reservations',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Reservations'],
                        ['code' => 'create', 'name' => 'Create Reservation'],
                        ['code' => 'release', 'name' => 'Release Reservation'],
                    ]],
                ['code' => 'low-stock', 'name' => 'Low-Stock / Reorder Alerts', 'type' => 'page', 'route' => '/ims/stock/low-stock',
                    'actions' => [['code' => 'view', 'name' => 'View Low Stock'], ['code' => 'configure', 'name' => 'Configure Thresholds']]],
                ['code' => 'expiring-items', 'name' => 'Expiring Items', 'type' => 'page', 'route' => '/ims/stock/expiring',
                    'actions' => [['code' => 'view', 'name' => 'View Expiring']]],
                /* EAM: critical spare availability */
                ['code' => 'critical-spares', 'name' => 'Critical Spares Availability', 'type' => 'page', 'route' => '/ims/stock/critical-spares',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Critical Spare Status'],
                        ['code' => 'alert', 'name' => 'Configure Criticality Alerts'],
                    ]],
            ],
        ],

        /* 5 MOVEMENTS */
        [
            'code' => 'movements', 'name' => 'Stock Movements',
            'description' => 'Receipts, issues, transfers, adjustments, returns',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/ims/movements', 'priority' => 5,
            'components' => [
                ['code' => 'receipts', 'name' => 'Goods Receipts', 'type' => 'page', 'route' => '/ims/movements/receipts',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Receipts'],
                        ['code' => 'create', 'name' => 'Create Receipt'],
                        ['code' => 'post', 'name' => 'Post Receipt'],
                    ]],
                ['code' => 'issues', 'name' => 'Stock Issues', 'type' => 'page', 'route' => '/ims/movements/issues',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Issues'],
                        ['code' => 'create', 'name' => 'Create Issue'],
                        ['code' => 'post', 'name' => 'Post Issue'],
                    ]],
                /* EAM: issues to work orders */
                ['code' => 'work-order-issues', 'name' => 'Issues to Work Orders', 'type' => 'page', 'route' => '/ims/movements/work-order-issues',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View WO Issues'],
                        ['code' => 'issue', 'name' => 'Issue to Work Order'],
                        ['code' => 'return', 'name' => 'Return from Work Order'],
                    ]],
                ['code' => 'transfers', 'name' => 'Inter-Warehouse Transfers', 'type' => 'page', 'route' => '/ims/movements/transfers',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Transfers'],
                        ['code' => 'create', 'name' => 'Create Transfer'],
                        ['code' => 'approve', 'name' => 'Approve Transfer'],
                        ['code' => 'ship', 'name' => 'Ship Transfer'],
                        ['code' => 'receive', 'name' => 'Receive Transfer'],
                    ]],
                ['code' => 'adjustments', 'name' => 'Stock Adjustments', 'type' => 'page', 'route' => '/ims/movements/adjustments',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Adjustments'],
                        ['code' => 'create', 'name' => 'Create Adjustment'],
                        ['code' => 'approve', 'name' => 'Approve Adjustment'],
                        ['code' => 'post', 'name' => 'Post Adjustment'],
                    ]],
                ['code' => 'movement-logs', 'name' => 'Movement Logs / Stock Card', 'type' => 'page', 'route' => '/ims/movements/logs',
                    'actions' => [['code' => 'view', 'name' => 'View Movement Logs'], ['code' => 'export', 'name' => 'Export Movement Logs']]],
            ],
        ],

        /* 6 SERIAL & LOT TRACKING */
        [
            'code' => 'traceability', 'name' => 'Serial / Lot / Batch Traceability',
            'description' => 'Serialized tracking, lot/batch genealogy, expiry',
            'icon' => 'QrCodeIcon', 'route' => '/ims/traceability', 'priority' => 6,
            'components' => [
                ['code' => 'serials', 'name' => 'Serial Numbers', 'type' => 'page', 'route' => '/ims/traceability/serials',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Serials'],
                        ['code' => 'track', 'name' => 'Track Serial'],
                        ['code' => 'history', 'name' => 'View Serial History'],
                    ]],
                ['code' => 'lots', 'name' => 'Lots / Batches', 'type' => 'page', 'route' => '/ims/traceability/lots',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Lots'],
                        ['code' => 'track', 'name' => 'Track Lot Genealogy'],
                    ]],
                ['code' => 'expiry', 'name' => 'Expiry Management', 'type' => 'page', 'route' => '/ims/traceability/expiry',
                    'actions' => [['code' => 'view', 'name' => 'View Expiring Lots'], ['code' => 'dispose', 'name' => 'Dispose Expired']]],
                ['code' => 'recall', 'name' => 'Product Recall', 'type' => 'page', 'route' => '/ims/traceability/recall',
                    'actions' => [['code' => 'initiate', 'name' => 'Initiate Recall'], ['code' => 'trace', 'name' => 'Trace Affected Units']]],
            ],
        ],

        /* 7 VALUATION & COSTING */
        [
            'code' => 'valuation', 'name' => 'Valuation & Costing',
            'description' => 'FIFO, LIFO, WAVG, standard cost, landed cost',
            'icon' => 'BanknotesIcon', 'route' => '/ims/valuation', 'priority' => 7,
            'components' => [
                ['code' => 'valuation-methods', 'name' => 'Valuation Methods', 'type' => 'page', 'route' => '/ims/valuation/methods',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Valuation Methods']]],
                ['code' => 'landed-cost', 'name' => 'Landed Cost', 'type' => 'page', 'route' => '/ims/valuation/landed-cost',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Landed Cost']]],
                ['code' => 'cost-revaluation', 'name' => 'Cost Revaluation', 'type' => 'page', 'route' => '/ims/valuation/revaluation',
                    'actions' => [['code' => 'run', 'name' => 'Run Revaluation']]],
                ['code' => 'inventory-valuation-report', 'name' => 'Inventory Valuation Report', 'type' => 'page', 'route' => '/ims/valuation/report',
                    'actions' => [['code' => 'view', 'name' => 'View Report'], ['code' => 'export', 'name' => 'Export Report']]],
            ],
        ],

        /* 8 CYCLE COUNT & PHYSICAL INVENTORY */
        [
            'code' => 'counting', 'name' => 'Cycle Count & Physical Inventory',
            'description' => 'Scheduled cycle counts, blind counts, full physical inventory',
            'icon' => 'ClipboardDocumentCheckIcon', 'route' => '/ims/counting', 'priority' => 8,
            'components' => [
                ['code' => 'cycle-count', 'name' => 'Cycle Counts', 'type' => 'page', 'route' => '/ims/counting/cycle',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Cycle Counts'],
                        ['code' => 'schedule', 'name' => 'Schedule Cycle Count'],
                        ['code' => 'conduct', 'name' => 'Conduct Count'],
                        ['code' => 'post', 'name' => 'Post Variance'],
                    ]],
                ['code' => 'physical-inventory', 'name' => 'Full Physical Inventory', 'type' => 'page', 'route' => '/ims/counting/physical',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Physical Inventory'],
                        ['code' => 'initiate', 'name' => 'Initiate Count'],
                        ['code' => 'freeze', 'name' => 'Freeze Stock'],
                        ['code' => 'reconcile', 'name' => 'Reconcile Variances'],
                    ]],
                ['code' => 'count-sheets', 'name' => 'Count Sheets', 'type' => 'page', 'route' => '/ims/counting/sheets',
                    'actions' => [['code' => 'print', 'name' => 'Print Count Sheets']]],
            ],
        ],

        /* 9 KITTING & BOM */
        [
            'code' => 'kitting', 'name' => 'Kitting & Assembly',
            'description' => 'Kit definitions, light assembly, WO kits',
            'icon' => 'CubeTransparentIcon', 'route' => '/ims/kitting', 'priority' => 9,
            'components' => [
                ['code' => 'kit-definitions', 'name' => 'Kit Definitions (BOM)', 'type' => 'page', 'route' => '/ims/kitting/kits',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Kit Definitions']]],
                ['code' => 'assemble', 'name' => 'Assemble Kits', 'type' => 'page', 'route' => '/ims/kitting/assemble',
                    'actions' => [['code' => 'assemble', 'name' => 'Assemble Kit'], ['code' => 'disassemble', 'name' => 'Disassemble Kit']]],
                /* EAM: pre-built work order kits */
                ['code' => 'work-order-kits', 'name' => 'Work Order Kits', 'type' => 'page', 'route' => '/ims/kitting/work-order-kits',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View WO Kits'],
                        ['code' => 'configure', 'name' => 'Configure WO Kit Template'],
                        ['code' => 'pick', 'name' => 'Pick Kit for WO'],
                    ]],
            ],
        ],

        /* 10 REPLENISHMENT & PLANNING */
        [
            'code' => 'planning', 'name' => 'Replenishment & Planning',
            'description' => 'Min/max, reorder point, safety stock, ABC/XYZ, EOQ',
            'icon' => 'ArrowTrendingUpIcon', 'route' => '/ims/planning', 'priority' => 10,
            'components' => [
                ['code' => 'reorder-rules', 'name' => 'Reorder Rules', 'type' => 'page', 'route' => '/ims/planning/reorder',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Reorder Rules']]],
                ['code' => 'safety-stock', 'name' => 'Safety Stock', 'type' => 'page', 'route' => '/ims/planning/safety-stock',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Safety Stock']]],
                ['code' => 'abc-xyz', 'name' => 'ABC / XYZ Analysis', 'type' => 'page', 'route' => '/ims/planning/abc-xyz',
                    'actions' => [['code' => 'run', 'name' => 'Run Analysis'], ['code' => 'view', 'name' => 'View Results']]],
                ['code' => 'eoq', 'name' => 'EOQ Calculation', 'type' => 'page', 'route' => '/ims/planning/eoq',
                    'actions' => [['code' => 'calculate', 'name' => 'Calculate EOQ']]],
                ['code' => 'replenishment-run', 'name' => 'Replenishment Run', 'type' => 'page', 'route' => '/ims/planning/run',
                    'actions' => [['code' => 'run', 'name' => 'Run Replenishment']]],
            ],
        ],

        /* 11 CONSIGNMENT / VMI / RENTAL */
        [
            'code' => 'consignment-vmi', 'name' => 'Consignment / VMI / Rental',
            'description' => 'Consignment stock, vendor-managed inventory, rental assets',
            'icon' => 'ArrowPathRoundedSquareIcon', 'route' => '/ims/consignment-vmi', 'priority' => 11,
            'components' => [
                ['code' => 'consignment', 'name' => 'Consignment Stock', 'type' => 'page', 'route' => '/ims/consignment',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Consignment']]],
                ['code' => 'vmi', 'name' => 'Vendor-Managed Inventory (VMI)', 'type' => 'page', 'route' => '/ims/vmi',
                    'actions' => [['code' => 'manage', 'name' => 'Manage VMI']]],
                /* EAM: equipment rental out to customers / in from vendors */
                ['code' => 'rental-assets', 'name' => 'Rental Assets', 'type' => 'page', 'route' => '/ims/rental',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Rentals'],
                        ['code' => 'rent-out', 'name' => 'Rent Out'],
                        ['code' => 'return', 'name' => 'Accept Return'],
                        ['code' => 'invoice', 'name' => 'Invoice Rental'],
                    ]],
            ],
        ],

        /* 12 WAREHOUSE OPERATIONS (WMS) */
        [
            'code' => 'wms', 'name' => 'Warehouse Operations (WMS)',
            'description' => 'Receive, put-away, pick, pack, ship, wave planning',
            'icon' => 'TruckIcon', 'route' => '/ims/wms', 'priority' => 12,
            'components' => [
                ['code' => 'receive', 'name' => 'Receive', 'type' => 'page', 'route' => '/ims/wms/receive',
                    'actions' => [['code' => 'receive', 'name' => 'Receive Inbound']]],
                ['code' => 'putaway', 'name' => 'Put-Away', 'type' => 'page', 'route' => '/ims/wms/putaway',
                    'actions' => [['code' => 'putaway', 'name' => 'Put-Away Stock']]],
                ['code' => 'pick', 'name' => 'Pick', 'type' => 'page', 'route' => '/ims/wms/pick',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pick Lists'],
                        ['code' => 'pick', 'name' => 'Pick Items'],
                        ['code' => 'short-pick', 'name' => 'Record Short Pick'],
                    ]],
                ['code' => 'pack', 'name' => 'Pack', 'type' => 'page', 'route' => '/ims/wms/pack',
                    'actions' => [['code' => 'pack', 'name' => 'Pack Order']]],
                ['code' => 'ship', 'name' => 'Ship', 'type' => 'page', 'route' => '/ims/wms/ship',
                    'actions' => [['code' => 'ship', 'name' => 'Ship Order']]],
                ['code' => 'wave-planning', 'name' => 'Wave Planning', 'type' => 'page', 'route' => '/ims/wms/waves',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Waves']]],
                ['code' => 'mobile-wms', 'name' => 'Mobile WMS (Scanner)', 'type' => 'page', 'route' => '/ims/wms/mobile',
                    'actions' => [['code' => 'use', 'name' => 'Use Mobile WMS']]],
            ],
        ],

        /* 13 RETURNS / RMA */
        [
            'code' => 'returns', 'name' => 'Returns & RMA',
            'description' => 'Customer returns, RMA workflow, disposition',
            'icon' => 'ArrowUturnLeftIcon', 'route' => '/ims/returns', 'priority' => 13,
            'components' => [
                ['code' => 'rma', 'name' => 'RMA Requests', 'type' => 'page', 'route' => '/ims/returns/rma',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RMAs'],
                        ['code' => 'create', 'name' => 'Create RMA'],
                        ['code' => 'approve', 'name' => 'Approve RMA'],
                        ['code' => 'receive', 'name' => 'Receive Return'],
                    ]],
                ['code' => 'disposition', 'name' => 'Disposition (Restock / Repair / Scrap)', 'type' => 'page', 'route' => '/ims/returns/disposition',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Disposition']]],
            ],
        ],

        /* 14 BARCODE / RFID / IoT */
        [
            'code' => 'barcode-rfid', 'name' => 'Barcode / RFID / IoT',
            'description' => 'Label design, printing, scanner config, RFID tags, IoT beacons',
            'icon' => 'QrCodeIcon', 'route' => '/ims/barcode-rfid', 'priority' => 14,
            'components' => [
                ['code' => 'label-designer', 'name' => 'Label Designer', 'type' => 'page', 'route' => '/ims/barcode/labels',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Labels'], ['code' => 'print', 'name' => 'Print Labels']]],
                ['code' => 'scanner-config', 'name' => 'Scanner Configuration', 'type' => 'page', 'route' => '/ims/barcode/scanners',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Scanner']]],
                ['code' => 'rfid', 'name' => 'RFID Tags', 'type' => 'page', 'route' => '/ims/rfid',
                    'actions' => [['code' => 'manage', 'name' => 'Manage RFID Tags']]],
                ['code' => 'iot-beacons', 'name' => 'IoT Location Beacons', 'type' => 'page', 'route' => '/ims/iot-beacons',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Beacons']]],
            ],
        ],

        /* 15 ANALYTICS */
        [
            'code' => 'analytics', 'name' => 'Inventory Analytics',
            'description' => 'Turnover, days-on-hand, slow-movers, dead stock, fill rate',
            'icon' => 'ChartPieIcon', 'route' => '/ims/analytics', 'priority' => 15,
            'components' => [
                ['code' => 'turnover', 'name' => 'Turnover & Days-on-Hand', 'type' => 'page', 'route' => '/ims/analytics/turnover',
                    'actions' => [['code' => 'view', 'name' => 'View Turnover']]],
                ['code' => 'dead-stock', 'name' => 'Slow-Movers / Dead Stock', 'type' => 'page', 'route' => '/ims/analytics/dead-stock',
                    'actions' => [['code' => 'view', 'name' => 'View Dead Stock'], ['code' => 'export', 'name' => 'Export Dead Stock']]],
                ['code' => 'fill-rate', 'name' => 'Fill Rate & Stockouts', 'type' => 'page', 'route' => '/ims/analytics/fill-rate',
                    'actions' => [['code' => 'view', 'name' => 'View Fill Rate']]],
                ['code' => 'mro-analytics', 'name' => 'MRO Spare Parts Analytics', 'type' => 'page', 'route' => '/ims/analytics/mro',
                    'actions' => [['code' => 'view', 'name' => 'View MRO Analytics'], ['code' => 'export', 'name' => 'Export MRO Analytics']]],
                ['code' => 'ai-demand', 'name' => 'AI Demand Prediction', 'type' => 'page', 'route' => '/ims/analytics/ai-demand',
                    'actions' => [['code' => 'view', 'name' => 'View AI Predictions'], ['code' => 'run', 'name' => 'Run Prediction']]],
                ['code' => 'custom-reports', 'name' => 'Custom Reports', 'type' => 'page', 'route' => '/ims/analytics/custom',
                    'actions' => [['code' => 'create', 'name' => 'Create Report'], ['code' => 'export', 'name' => 'Export Report']]],
            ],
        ],

        /* 16 INTEGRATIONS */
        [
            'code' => 'integrations', 'name' => 'Integrations',
            'description' => '3PL, marketplace, EDI, WMS bridges',
            'icon' => 'ArrowsRightLeftIcon', 'route' => '/ims/integrations', 'priority' => 16,
            'components' => [
                ['code' => 'three-pl', 'name' => '3PL Providers', 'type' => 'page', 'route' => '/ims/integrations/3pl',
                    'actions' => [['code' => 'configure', 'name' => 'Configure 3PL']]],
                ['code' => 'marketplace', 'name' => 'Marketplace Sync', 'type' => 'page', 'route' => '/ims/integrations/marketplace',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Marketplace'], ['code' => 'sync', 'name' => 'Sync Now']]],
                ['code' => 'ecommerce', 'name' => 'E-Commerce Sync', 'type' => 'page', 'route' => '/ims/integrations/ecommerce',
                    'actions' => [['code' => 'configure', 'name' => 'Configure E-Commerce']]],
            ],
        ],

        /* 17 SETTINGS */
        [
            'code' => 'settings', 'name' => 'Inventory Settings',
            'description' => 'Numbering, movement policies, valuation default, barcode standards',
            'icon' => 'CogIcon', 'route' => '/ims/settings', 'priority' => 99,
            'components' => [
                ['code' => 'numbering', 'name' => 'Document Numbering', 'type' => 'page', 'route' => '/ims/settings/numbering',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Numbering']]],
                ['code' => 'movement-policies', 'name' => 'Movement Policies', 'type' => 'page', 'route' => '/ims/settings/movement-policies',
                    'actions' => [['code' => 'manage', 'name' => 'Manage Policies']]],
                ['code' => 'valuation-default', 'name' => 'Default Valuation Method', 'type' => 'page', 'route' => '/ims/settings/valuation',
                    'actions' => [['code' => 'configure', 'name' => 'Configure Default Valuation']]],
                ['code' => 'general', 'name' => 'General Settings', 'type' => 'page', 'route' => '/ims/settings/general',
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
            'mro.spare_parts' => 'item-master.mro-spare-parts',
            'mro.critical_spares' => 'stock.critical-spares',
            'mro.stock_availability' => 'stock.stock-on-hand',
            'mro.issues_to_work_orders' => 'movements.work-order-issues',
            'mro.work_order_kits' => 'kitting.work-order-kits',
            'mro.point_of_use_bins' => 'warehouses.point-of-use',
            'inventory.valuation' => 'valuation.inventory-valuation-report',
            'inventory.serial_tracking' => 'traceability.serials',
            'inventory.reservations' => 'stock.reservations',
            'rental.assets' => 'consignment-vmi.rental-assets',
        ],
        'consumes' => [
            'eam.work_orders' => 'aero-eam',
            'eam.asset_bom' => 'aero-eam',
            'scm.purchase_orders' => 'aero-scm',
            'scm.goods_receipt' => 'aero-scm',
            'finance.inventory_valuation' => 'aero-finance',
            'iot.beacon_location' => 'aero-iot',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Settings (kept)
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        'track_serial_numbers' => env('IMS_TRACK_SERIAL_NUMBERS', true),
        'track_batch_numbers' => env('IMS_TRACK_BATCH_NUMBERS', true),
        'enable_multi_warehouse' => env('IMS_MULTI_WAREHOUSE', true),
        'low_stock_threshold' => env('IMS_LOW_STOCK_THRESHOLD', 10),
    ],

    'stock_movements' => [
        'require_approval' => env('IMS_REQUIRE_APPROVAL', false),
        'auto_adjust_cost' => env('IMS_AUTO_ADJUST_COST', true),
        'movement_types' => ['in', 'out', 'transfer', 'adjustment'],
    ],

    'warehouses_config' => [
        'enable_locations' => env('IMS_WAREHOUSE_LOCATIONS', true),
        'enable_zones' => env('IMS_WAREHOUSE_ZONES', true),
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'inventory_admin_role' => 'inventory-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'ims-access'],
    ],
];
