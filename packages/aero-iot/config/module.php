<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IoT Module — Internet of Things Management
    |--------------------------------------------------------------------------
    |
    | Manages IoT devices, sensors, gateways, telemetry data,
    | alerts, networks, and device maintenance.
    |
    */

    'code' => 'iot',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'IoT Management',
    'description' => 'IoT platform: device/sensor/gateway management, real-time telemetry, alerts, condition monitoring, predictive maintenance, digital twins, firmware OTA, and edge compute — EAM-aligned.',
    'version' => '2.0.0',
    'category' => 'industry',
    'icon' => 'CpuChipIcon',
    'priority' => 31,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('IOT_MODULE_ENABLED', true),
    'min_plan' => null,
    'minimum_plan' => null,
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => 'iot',

    'submodules' => [

        // ==================== IOT DASHBOARD ====================
        [
            'code' => 'iot-dashboard',
            'name' => 'IoT Dashboard',
            'description' => 'Real-time overview of devices, sensors, telemetry, and alerts.',
            'icon' => 'ChartPieIcon',
            'route' => 'tenant.iot.dashboard',
            'priority' => 1,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== DEVICES ====================
        [
            'code' => 'devices',
            'name' => 'Devices',
            'description' => 'IoT device registration, monitoring, command dispatch, and bulk operations.',
            'icon' => 'DevicePhoneMobileIcon',
            'route' => 'tenant.iot.devices.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'device-list',
                    'name' => 'Device List',
                    'description' => 'View and manage all IoT devices.',
                    'route_name' => 'tenant.iot.devices.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'command', 'name' => 'Send Command', 'is_active' => true],
                        ['code' => 'restart', 'name' => 'Restart Device', 'is_active' => true],
                        ['code' => 'shutdown', 'name' => 'Shutdown Device', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'device-bulk-operations',
                    'name' => 'Bulk Operations',
                    'description' => 'Perform bulk commands, updates, and deletions on devices.',
                    'route_name' => 'tenant.iot.devices.bulk.command',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'bulk-command', 'name' => 'Bulk Command', 'is_active' => true],
                        ['code' => 'bulk-update', 'name' => 'Bulk Update', 'is_active' => true],
                        ['code' => 'bulk-delete', 'name' => 'Bulk Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== SENSORS ====================
        [
            'code' => 'sensors',
            'name' => 'Sensors',
            'description' => 'Sensor registration, calibration, threshold configuration, and data export.',
            'icon' => 'SignalIcon',
            'route' => 'tenant.iot.sensors.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'sensor-list',
                    'name' => 'Sensor List',
                    'description' => 'View and manage all sensors.',
                    'route_name' => 'tenant.iot.sensors.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'calibrate', 'name' => 'Calibrate', 'is_active' => true],
                        ['code' => 'threshold', 'name' => 'Set Threshold', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export Data', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== TELEMETRY ====================
        [
            'code' => 'telemetry',
            'name' => 'Telemetry',
            'description' => 'Real-time telemetry data viewing, analytics, and data purging.',
            'icon' => 'ArrowTrendingUpIcon',
            'route' => 'tenant.iot.telemetry.index',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'telemetry-overview',
                    'name' => 'Telemetry Overview',
                    'description' => 'View telemetry data across all devices and sensors.',
                    'route_name' => 'tenant.iot.telemetry.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                        ['code' => 'purge', 'name' => 'Purge Old Data', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'telemetry-analytics',
                    'name' => 'Telemetry Analytics',
                    'description' => 'Advanced telemetry analytics and trend analysis.',
                    'route_name' => 'tenant.iot.telemetry.analytics',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ALERTS ====================
        [
            'code' => 'alerts',
            'name' => 'Alerts',
            'description' => 'Alert monitoring, acknowledgment, resolution, escalation, and rule management.',
            'icon' => 'BellAlertIcon',
            'route' => 'tenant.iot.alerts.index',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'alert-list',
                    'name' => 'Alert List',
                    'description' => 'View and manage all IoT alerts.',
                    'route_name' => 'tenant.iot.alerts.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'acknowledge', 'name' => 'Acknowledge', 'is_active' => true],
                        ['code' => 'resolve', 'name' => 'Resolve', 'is_active' => true],
                        ['code' => 'suppress', 'name' => 'Suppress', 'is_active' => true],
                        ['code' => 'escalate', 'name' => 'Escalate', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'alert-rules',
                    'name' => 'Alert Rules',
                    'description' => 'Configure alert rules and thresholds.',
                    'route_name' => 'tenant.iot.alerts.rules',
                    'priority' => 2,
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

        // ==================== GATEWAYS ====================
        [
            'code' => 'gateways',
            'name' => 'Gateways',
            'description' => 'IoT gateway management, health monitoring, and connected device listing.',
            'icon' => 'ServerIcon',
            'route' => 'tenant.iot.gateways.index',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'gateway-list',
                    'name' => 'Gateway List',
                    'description' => 'View and manage all IoT gateways.',
                    'route_name' => 'tenant.iot.gateways.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'restart', 'name' => 'Restart Gateway', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== NETWORKS ====================
        [
            'code' => 'networks',
            'name' => 'Networks',
            'description' => 'IoT network topology and configuration management.',
            'icon' => 'GlobeAltIcon',
            'route' => 'tenant.iot.networks.index',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'network-list',
                    'name' => 'Network List',
                    'description' => 'View and manage IoT networks.',
                    'route_name' => 'tenant.iot.networks.index',
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

        // ==================== DEVICE TYPES ====================
        [
            'code' => 'device-types',
            'name' => 'Device Types',
            'description' => 'Device type catalog and classification management.',
            'icon' => 'TagIcon',
            'route' => 'tenant.iot.device-types.index',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'device-type-list',
                    'name' => 'Device Type List',
                    'description' => 'View and manage device type definitions.',
                    'route_name' => 'tenant.iot.device-types.index',
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

        // ==================== REPORTS ====================
        [
            'code' => 'iot-reports',
            'name' => 'Reports',
            'description' => 'Device usage, sensor performance, alert summaries, and maintenance schedules.',
            'icon' => 'ChartBarIcon',
            'route' => 'tenant.iot.reports.index',
            'priority' => 9,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'report-list',
                    'name' => 'Report List',
                    'description' => 'View available IoT reports.',
                    'route_name' => 'tenant.iot.reports.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== CONDITION MONITORING (EAM) ====================
        [
            'code' => 'condition-monitoring',
            'name' => 'Condition Monitoring',
            'description' => 'Continuous monitoring of asset health via sensors — vibration, temperature, pressure, current, oil analysis.',
            'icon' => 'HeartIcon',
            'route' => 'tenant.iot.condition-monitoring.index',
            'priority' => 11,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'asset-health',
                    'name' => 'Asset Health Dashboard',
                    'description' => 'Real-time asset health index and anomaly indicators.',
                    'route_name' => 'tenant.iot.condition-monitoring.asset-health',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Asset Health', 'is_active' => true],
                        ['code' => 'drill-down', 'name' => 'Drill Down', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'vibration-monitoring',
                    'name' => 'Vibration Monitoring',
                    'description' => 'ISO 10816 compliant vibration analysis.',
                    'route_name' => 'tenant.iot.condition-monitoring.vibration',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Thresholds', 'is_active' => true],
                        ['code' => 'fft', 'name' => 'Run FFT Analysis', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'thermal-monitoring',
                    'name' => 'Thermal Monitoring',
                    'description' => 'Temperature trending & thermal anomaly detection.',
                    'route_name' => 'tenant.iot.condition-monitoring.thermal',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'configure', 'name' => 'Configure Thresholds', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'oil-analysis',
                    'name' => 'Oil / Lubricant Analysis',
                    'description' => 'Track oil samples and contamination metrics.',
                    'route_name' => 'tenant.iot.condition-monitoring.oil',
                    'priority' => 4,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'record', 'name' => 'Record Sample', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'meter-readings',
                    'name' => 'Meter Readings (Runtime / Cycles / Distance)',
                    'description' => 'Capture usage-based meter readings for PM triggers.',
                    'route_name' => 'tenant.iot.condition-monitoring.meters',
                    'priority' => 5,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Meter Readings', 'is_active' => true],
                        ['code' => 'capture', 'name' => 'Capture Reading', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export Readings', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'condition-rules',
                    'name' => 'Condition Rules & SPC',
                    'description' => 'Statistical process control and composite rules across signals.',
                    'route_name' => 'tenant.iot.condition-monitoring.rules',
                    'priority' => 6,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'manage', 'name' => 'Manage Rules', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== PREDICTIVE MAINTENANCE (EAM) ====================
        [
            'code' => 'predictive-maintenance',
            'name' => 'Predictive Maintenance',
            'description' => 'ML-driven failure prediction, remaining-useful-life, prescriptive recommendations.',
            'icon' => 'SparklesIcon',
            'route' => 'tenant.iot.predictive.index',
            'priority' => 12,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'rul-forecast',
                    'name' => 'Remaining Useful Life (RUL)',
                    'description' => 'RUL forecasts per asset.',
                    'route_name' => 'tenant.iot.predictive.rul',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View RUL', 'is_active' => true],
                        ['code' => 'run', 'name' => 'Run Prediction', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'failure-prediction',
                    'name' => 'Failure Prediction',
                    'description' => 'Probability-of-failure models.',
                    'route_name' => 'tenant.iot.predictive.failure',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Predictions', 'is_active' => true],
                        ['code' => 'run', 'name' => 'Run Prediction', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'anomaly-detection',
                    'name' => 'Anomaly Detection',
                    'description' => 'Unsupervised anomaly detection in telemetry streams.',
                    'route_name' => 'tenant.iot.predictive.anomalies',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Anomalies', 'is_active' => true],
                        ['code' => 'acknowledge', 'name' => 'Acknowledge', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'ml-models',
                    'name' => 'ML Models',
                    'description' => 'Manage training & deployment of predictive models.',
                    'route_name' => 'tenant.iot.predictive.models',
                    'priority' => 4,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Models', 'is_active' => true],
                        ['code' => 'train', 'name' => 'Train Model', 'is_active' => true],
                        ['code' => 'deploy', 'name' => 'Deploy Model', 'is_active' => true],
                        ['code' => 'rollback', 'name' => 'Rollback Model', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'prescriptive-actions',
                    'name' => 'Prescriptive Recommendations',
                    'description' => 'Auto-recommended maintenance actions.',
                    'route_name' => 'tenant.iot.predictive.prescriptive',
                    'priority' => 5,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Recommendations', 'is_active' => true],
                        ['code' => 'trigger-wo', 'name' => 'Trigger Work Order', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== DIGITAL TWIN (EAM) ====================
        [
            'code' => 'digital-twin',
            'name' => 'Digital Twin',
            'description' => 'Virtual asset models with real-time state, 3D visualization, and simulation.',
            'icon' => 'CubeTransparentIcon',
            'route' => 'tenant.iot.digital-twin.index',
            'priority' => 13,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'twin-list',
                    'name' => 'Twin Registry',
                    'description' => 'Manage digital twin definitions.',
                    'route_name' => 'tenant.iot.digital-twin.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Twins', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Twin', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Twin', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Twin', 'is_active' => true],
                        ['code' => 'bind-asset', 'name' => 'Bind to Asset', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'twin-visualization',
                    'name' => '3D Visualization',
                    'description' => '3D visualization and state overlay.',
                    'route_name' => 'tenant.iot.digital-twin.visualize',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View 3D', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'simulation',
                    'name' => 'Simulation & What-If',
                    'description' => 'Simulate operational scenarios on the twin.',
                    'route_name' => 'tenant.iot.digital-twin.simulation',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Simulations', 'is_active' => true],
                        ['code' => 'run', 'name' => 'Run Simulation', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ASSET BINDING (EAM) ====================
        [
            'code' => 'asset-binding',
            'name' => 'Asset Binding',
            'description' => 'Bind IoT devices/sensors to EAM assets (M:N mapping, lifecycle events).',
            'icon' => 'LinkIcon',
            'route' => 'tenant.iot.asset-binding.index',
            'priority' => 14,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'device-asset-map',
                    'name' => 'Device-to-Asset Mapping',
                    'description' => 'Manage bindings between IoT devices and EAM assets.',
                    'route_name' => 'tenant.iot.asset-binding.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Bindings', 'is_active' => true],
                        ['code' => 'bind', 'name' => 'Create Binding', 'is_active' => true],
                        ['code' => 'unbind', 'name' => 'Remove Binding', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== FIRMWARE OTA ====================
        [
            'code' => 'firmware',
            'name' => 'Firmware & OTA Updates',
            'description' => 'Firmware artifacts, OTA campaigns, rollout rings, rollback.',
            'icon' => 'CloudArrowDownIcon',
            'route' => 'tenant.iot.firmware.index',
            'priority' => 15,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'firmware-artifacts',
                    'name' => 'Firmware Artifacts',
                    'description' => 'Upload & manage firmware images.',
                    'route_name' => 'tenant.iot.firmware.artifacts',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'upload', 'name' => 'Upload Firmware', 'is_active' => true],
                        ['code' => 'sign', 'name' => 'Sign Artifact', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'ota-campaigns',
                    'name' => 'OTA Campaigns',
                    'description' => 'Rollout firmware in phased rings.',
                    'route_name' => 'tenant.iot.firmware.campaigns',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Campaigns', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Campaign', 'is_active' => true],
                        ['code' => 'launch', 'name' => 'Launch Campaign', 'is_active' => true],
                        ['code' => 'pause', 'name' => 'Pause Campaign', 'is_active' => true],
                        ['code' => 'rollback', 'name' => 'Rollback Campaign', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== DEVICE SECURITY ====================
        [
            'code' => 'device-security',
            'name' => 'Device Security & Identity',
            'description' => 'Device certificates, keys, provisioning, revocation, and policies.',
            'icon' => 'ShieldCheckIcon',
            'route' => 'tenant.iot.security.index',
            'priority' => 16,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'certificates',
                    'name' => 'Certificates & Keys',
                    'description' => 'PKI for devices.',
                    'route_name' => 'tenant.iot.security.certificates',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'issue', 'name' => 'Issue Certificate', 'is_active' => true],
                        ['code' => 'revoke', 'name' => 'Revoke Certificate', 'is_active' => true],
                        ['code' => 'rotate', 'name' => 'Rotate Keys', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'provisioning',
                    'name' => 'Zero-Touch Provisioning',
                    'description' => 'Auto-provision devices at first connection.',
                    'route_name' => 'tenant.iot.security.provisioning',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Provisioning', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'security-policies',
                    'name' => 'Security Policies',
                    'description' => 'Access and encryption policies.',
                    'route_name' => 'tenant.iot.security.policies',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Policies', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== EDGE COMPUTE & DATA PIPELINES ====================
        [
            'code' => 'edge-compute',
            'name' => 'Edge Compute & Data Pipelines',
            'description' => 'Edge functions, stream processing, aggregation pipelines, retention.',
            'icon' => 'BoltIcon',
            'route' => 'tenant.iot.edge.index',
            'priority' => 17,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'edge-functions',
                    'name' => 'Edge Functions',
                    'description' => 'Deploy functions to gateways.',
                    'route_name' => 'tenant.iot.edge.functions',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'deploy', 'name' => 'Deploy Function', 'is_active' => true],
                        ['code' => 'rollback', 'name' => 'Rollback Function', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'pipelines',
                    'name' => 'Data Pipelines',
                    'description' => 'Ingest, transform, and route telemetry.',
                    'route_name' => 'tenant.iot.edge.pipelines',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Pipelines', 'is_active' => true],
                        ['code' => 'manage', 'name' => 'Manage Pipelines', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'retention-policies',
                    'name' => 'Data Retention',
                    'description' => 'Raw / aggregated retention policies.',
                    'route_name' => 'tenant.iot.edge.retention',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'manage', 'name' => 'Manage Retention', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== GEOFENCING & LOCATION ====================
        [
            'code' => 'geofencing',
            'name' => 'Geofencing & Asset Tracking',
            'description' => 'Location-based alerts and asset movement tracking.',
            'icon' => 'MapPinIcon',
            'route' => 'tenant.iot.geofencing.index',
            'priority' => 18,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'geofences',
                    'name' => 'Geofences',
                    'description' => 'Define & monitor geofences.',
                    'route_name' => 'tenant.iot.geofencing.fences',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'asset-tracking',
                    'name' => 'Real-Time Asset Tracking',
                    'description' => 'Map view of moving assets.',
                    'route_name' => 'tenant.iot.geofencing.tracking',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Tracking Map', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== COMMANDS & AUTOMATIONS ====================
        [
            'code' => 'commands-automations',
            'name' => 'Commands & Automations',
            'description' => 'Device commands, scheduled commands, automation rules.',
            'icon' => 'BoltIcon',
            'route' => 'tenant.iot.automations.index',
            'priority' => 19,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'command-history',
                    'name' => 'Command History',
                    'route_name' => 'tenant.iot.automations.commands',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'retry', 'name' => 'Retry Command', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'automation-rules',
                    'name' => 'Automation Rules',
                    'route_name' => 'tenant.iot.automations.rules',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Rule', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Rule', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Rule', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'iot-settings',
            'name' => 'Settings',
            'description' => 'IoT module configuration and notification preferences.',
            'icon' => 'Cog6ToothIcon',
            'route' => 'tenant.iot.settings.index',
            'priority' => 10,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'iot-general-settings',
                    'name' => 'General Settings',
                    'description' => 'Configure general IoT module settings.',
                    'route_name' => 'tenant.iot.settings.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'iot-notification-settings',
                    'name' => 'Notification Settings',
                    'description' => 'Configure IoT alert notification preferences.',
                    'route_name' => 'tenant.iot.settings.notifications',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'iot-protocol-settings',
                    'name' => 'Protocol Settings (MQTT / CoAP / LoRa)',
                    'description' => 'Configure supported IoT protocols.',
                    'route_name' => 'tenant.iot.settings.protocols',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
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
            'iot.device_telemetry' => 'telemetry.telemetry-overview',
            'iot.asset_health' => 'condition-monitoring.asset-health',
            'iot.vibration' => 'condition-monitoring.vibration-monitoring',
            'iot.thermal' => 'condition-monitoring.thermal-monitoring',
            'iot.oil_analysis' => 'condition-monitoring.oil-analysis',
            'iot.meter_readings' => 'condition-monitoring.meter-readings',
            'iot.condition_rules' => 'condition-monitoring.condition-rules',
            'iot.rul_forecast' => 'predictive-maintenance.rul-forecast',
            'iot.failure_prediction' => 'predictive-maintenance.failure-prediction',
            'iot.anomalies' => 'predictive-maintenance.anomaly-detection',
            'iot.prescriptive_actions' => 'predictive-maintenance.prescriptive-actions',
            'iot.digital_twin' => 'digital-twin.twin-list',
            'iot.asset_binding' => 'asset-binding.device-asset-map',
            'iot.geofences' => 'geofencing.geofences',
            'iot.asset_tracking' => 'geofencing.asset-tracking',
            'iot.automation_rules' => 'commands-automations.automation-rules',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'eam.work_order_trigger' => 'aero-eam',
            'eam.maintenance_schedule' => 'aero-eam',
            'ims.spare_parts_location' => 'aero-ims',
            'finance.asset_register' => 'aero-finance',
            'hrm.technician_dispatch' => 'aero-hrm',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'iot_admin_role' => 'iot-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'iot-access'],
    ],
];
