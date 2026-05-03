# Aero IoT - Internet of Things Device Management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aero/iot.svg?style=flat-square)](https://packagist.org/packages/aero/iot)
[![Total Downloads](https://img.shields.io/packagist/dt/aero/iot.svg?style=flat-square)](https://packagist.org/packages/aero/iot)
[![License](https://img.shields.io/github/license/aero/iot.svg?style=flat-square)](https://github.com/aero/iot/blob/main/LICENSE)

## Overview

Aero IoT is a comprehensive Internet of Things (IoT) device management system designed for enterprise applications. It provides complete device lifecycle management, real-time telemetry collection, sensor monitoring, alert management, and edge computing capabilities.

## Features

### 🔧 Device Management
- **Complete Device Lifecycle**: Registration, provisioning, monitoring, maintenance, and decommissioning
- **Device Types & Categories**: Flexible categorization with sensors, actuators, gateways, controllers, monitors
- **Multi-Protocol Support**: MQTT, CoAP, HTTP, WebSocket, Zigbee, LoRa, Bluetooth connectivity
- **Remote Commands**: Send commands, firmware updates, configuration changes to devices
- **Batch Operations**: Bulk device management and command execution

### 📊 Telemetry & Data Collection
- **Real-time Data Streaming**: High-throughput telemetry data collection and processing
- **Time-Series Storage**: Optimized storage for sensor data with compression and archiving
- **Data Quality Management**: Quality indicators, validation, and anomaly detection
- **Aggregation & Analytics**: Statistical analysis, trend detection, and predictive insights
- **Data Retention Policies**: Configurable retention, archiving, and cleanup policies

### 🚨 Alert & Notification System
- **Threshold-based Alerts**: Configurable alert rules with multiple conditions
- **Severity Levels**: Low, Medium, High, Critical alert classification
- **Auto-resolution**: Automatic alert resolution based on conditions
- **Escalation Management**: Multi-level escalation with time-based triggers
- **Multi-channel Notifications**: Email, SMS, Slack, webhook integrations

### 🌐 Network & Gateway Management
- **IoT Gateway Support**: Dedicated gateway device management
- **Network Topology**: Hierarchical device networks with gateway-to-device relationships
- **Protocol Management**: Support for various IoT communication protocols
- **Edge Computing**: Local processing capabilities at gateway level
- **Network Health Monitoring**: Connection quality, bandwidth utilization, uptime tracking

### 🔧 Maintenance & Lifecycle
- **Predictive Maintenance**: AI-driven maintenance scheduling based on usage patterns
- **Maintenance Workflows**: Scheduled, corrective, and emergency maintenance support
- **Firmware Management**: OTA updates with rollback capabilities
- **Device Calibration**: Sensor calibration tracking and automated reminders
- **Asset Tracking**: Complete device history and audit trails

### 🔒 Security & Authentication
- **Device Authentication**: Certificate-based device authentication
- **Encrypted Communications**: End-to-end encryption for device communications
- **API Security**: Rate limiting, authentication tokens, and access controls
- **Audit Logging**: Comprehensive logging for compliance and security monitoring

### 🏢 Enterprise Features
- **Multi-tenancy**: Complete tenant isolation for SaaS deployments
- **Role-based Access Control**: Granular permissions for different user roles
- **Integration Ready**: APIs for third-party system integration
- **Scalable Architecture**: Designed for high-volume IoT deployments
- **Cloud Platform Support**: AWS IoT, Azure IoT Hub, Google Cloud IoT integration

## Installation

### Requirements
- PHP 8.1 or higher
- Laravel 11.0 or higher
- MySQL 8.0 / PostgreSQL 13+ / SQLite 3.35+
- Redis (recommended for caching and queues)
- MQTT Broker (optional, for device communication)

### Package Installation

```bash
composer require aero/iot
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="Aero\IoT\Providers\IoTServiceProvider" --tag=iot-config
```

### Run Migrations

```bash
php artisan migrate
```

### Publish Assets (Optional)

```bash
# Publish frontend assets
php artisan vendor:publish --provider="Aero\IoT\Providers\IoTServiceProvider" --tag=iot-assets

# Publish migrations for customization
php artisan vendor:publish --provider="Aero\IoT\Providers\IoTServiceProvider" --tag=iot-migrations
```

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# IoT Module Configuration
IOT_ENABLED=true

# MQTT Configuration
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=your_username
MQTT_PASSWORD=your_password
MQTT_CLIENT_ID=aero-iot

# Device Configuration
IOT_MAX_DEVICES_PER_TENANT=1000
IOT_HEARTBEAT_INTERVAL=300
IOT_OFFLINE_THRESHOLD=900
IOT_COMMAND_TIMEOUT=60

# Telemetry Configuration
IOT_TELEMETRY_ENABLED=true
IOT_TELEMETRY_RETENTION_DAYS=90
IOT_REAL_TIME_PROCESSING=true

# Alert Configuration
IOT_ALERTS_ENABLED=true
IOT_AUTO_RESOLVE_ALERTS=true
IOT_EMAIL_NOTIFICATIONS=true

# Security Configuration
IOT_DEVICE_AUTH=certificate
IOT_ENCRYPTION_ENABLED=true
IOT_API_RATE_LIMIT=true
```

### Configuration File

The main configuration is in `config/iot.php`. Key sections include:

- **MQTT Settings**: Broker connection and communication settings
- **Device Limits**: Per-tenant device limits and timeouts
- **Telemetry**: Data collection and processing configuration
- **Alerts**: Notification and escalation settings
- **Security**: Authentication and encryption options
- **Integrations**: Third-party platform configurations

## Usage

### Device Management

```php
use Aero\IoT\Models\Device;
use Aero\IoT\Models\DeviceType;

// Create a device type
$deviceType = DeviceType::create([
    'type_code' => 'TEMP_SENSOR',
    'type_name' => 'Temperature Sensor',
    'category' => DeviceType::CATEGORY_SENSOR,
    'supported_protocols' => ['mqtt', 'http'],
    'sensor_capabilities' => ['temperature', 'humidity']
]);

// Register a device
$device = Device::create([
    'device_id' => 'DEVICE_001',
    'device_name' => 'Office Temperature Sensor',
    'device_type_id' => $deviceType->id,
    'manufacturer' => 'SensorTech',
    'model' => 'ST-100',
    'location_name' => 'Office Room 101',
    'status' => Device::STATUS_ONLINE
]);

// Send a command to device
$command = $device->sendCommand('update_config', [
    'sampling_rate' => 60,
    'alert_threshold' => 25.0
]);

// Record telemetry data
$device->recordTelemetry('temperature', 23.5, 'celsius');
```

### Sensor Management

```php
use Aero\IoT\Models\Sensor;

// Create a sensor
$sensor = Sensor::create([
    'device_id' => $device->id,
    'sensor_name' => 'Temperature Probe',
    'sensor_type' => Sensor::TYPE_TEMPERATURE,
    'measurement_unit' => 'celsius',
    'min_value' => -40,
    'max_value' => 100,
    'alert_thresholds' => [
        [
            'condition' => 'greater_than',
            'value' => 30.0,
            'severity' => 'high',
            'message' => 'High temperature detected'
        ]
    ]
]);

// Record sensor reading
$sensorData = $sensor->recordReading(25.3, now());

// Check if calibration is needed
if ($sensor->needsCalibration()) {
    $sensor->calibrate(0.0, 'Factory calibration');
}
```

### Alert Management

```php
use Aero\IoT\Models\DeviceAlert;

// Get active alerts
$activeAlerts = DeviceAlert::active()
    ->bySeverity(DeviceAlert::SEVERITY_CRITICAL)
    ->with('device')
    ->get();

// Acknowledge an alert
$alert = DeviceAlert::find(1);
$alert->acknowledge($user, 'Investigating the issue');

// Resolve an alert
$alert->resolve($user, 'Issue resolved by restarting device');
```

### Gateway Management

```php
use Aero\IoT\Models\IoTGateway;
use Aero\IoT\Models\DeviceNetwork;

// Create an IoT gateway
$gateway = IoTGateway::create([
    'gateway_id' => 'GATEWAY_001',
    'gateway_name' => 'Main Building Gateway',
    'manufacturer' => 'GatewayTech',
    'model' => 'GT-500',
    'supported_protocols' => ['mqtt', 'zigbee', 'lora'],
    'max_connections' => 100,
    'status' => IoTGateway::STATUS_ONLINE
]);

// Create a network managed by the gateway
$network = DeviceNetwork::create([
    'network_name' => 'Building A Network',
    'network_type' => DeviceNetwork::TYPE_ZIGBEE,
    'gateway_device_id' => $gateway->id,
    'max_devices' => 50
]);

// Update gateway heartbeat
$gateway->updateHeartbeat([
    'current_connections' => 25,
    'uptime' => 86400 // 24 hours in seconds
]);
```

## API Endpoints

### Device API

```bash
# Get all devices
GET /api/iot/devices

# Get device details
GET /api/iot/devices/{device}

# Update device status
POST /api/iot/devices/{device}/status

# Send heartbeat
POST /api/iot/devices/{device}/heartbeat
```

### Telemetry API

```bash
# Store telemetry data
POST /api/iot/telemetry
{
    "device_id": 1,
    "metric_name": "temperature",
    "metric_value": 23.5,
    "unit": "celsius",
    "timestamp": "2024-01-01T12:00:00Z"
}

# Store batch telemetry
POST /api/iot/telemetry/batch
{
    "data": [
        {"device_id": 1, "metric_name": "temperature", "metric_value": 23.5},
        {"device_id": 1, "metric_name": "humidity", "metric_value": 65.0}
    ]
}

# Get device telemetry
GET /api/iot/telemetry/device/{device}?hours=24
```

### Command API

```bash
# Send command to device
POST /api/iot/commands
{
    "device_id": 1,
    "command_name": "restart",
    "parameters": {},
    "priority": 5
}

# Get pending commands for device
GET /api/iot/commands/device/{device}

# Acknowledge command execution
POST /api/iot/commands/{command}/acknowledge

# Mark command as completed
POST /api/iot/commands/{command}/complete
{
    "response": {"status": "success", "message": "Device restarted"}
}
```

## Frontend Integration

### Inertia.js Pages

The package includes pre-built Inertia.js pages for device management:

- `IoT/Dashboard` - Main IoT dashboard with overview
- `IoT/Devices/Index` - Device list and management
- `IoT/Devices/Show` - Device details and telemetry
- `IoT/Sensors/Index` - Sensor management
- `IoT/Alerts/Index` - Alert management and monitoring
- `IoT/Gateways/Index` - Gateway management

### React Components

Pre-built React components are available:

```jsx
import { DeviceCard } from '@/Components/IoT/DeviceCard';
import { TelemetryChart } from '@/Components/IoT/TelemetryChart';
import { AlertList } from '@/Components/IoT/AlertList';
import { SensorDataTable } from '@/Components/IoT/SensorDataTable';

function IoTDashboard({ devices, alerts, telemetry }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {devices.map(device => (
                <DeviceCard key={device.id} device={device} />
            ))}
        </div>
    );
}
```

## Integration Examples

### AWS IoT Core

```php
// Configure in config/iot.php
'integrations' => [
    'aws_iot' => [
        'enabled' => true,
        'endpoint' => 'your-iot-endpoint.iot.region.amazonaws.com',
        'thing_type' => 'AeroDevice',
    ]
]
```

### MQTT Integration

```php
use PhpMqtt\Client\MqttClient;

// MQTT client configuration
$mqtt = new MqttClient(
    config('iot.mqtt.host'),
    config('iot.mqtt.port'),
    config('iot.mqtt.client_id')
);

// Subscribe to device telemetry
$mqtt->subscribe('devices/+/telemetry', function (string $topic, string $message) {
    $data = json_decode($message, true);
    
    // Process telemetry data
    app(TelemetryService::class)->processTelemetry($data);
});
```

### Webhook Integration

```php
// In your webhook controller
public function awsIotWebhook(Request $request)
{
    $payload = $request->json()->all();
    
    if ($payload['eventType'] === 'THING_EVENT') {
        $deviceId = $payload['thingName'];
        $device = Device::where('device_id', $deviceId)->first();
        
        if ($device) {
            $device->recordTelemetry(
                $payload['attributes']['metric'],
                $payload['attributes']['value']
            );
        }
    }
}
```

## Performance Optimization

### Caching

```php
// Enable caching in config/iot.php
'performance' => [
    'caching' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
        'driver' => 'redis',
    ]
]
```

### Queue Processing

```php
// Enable background processing
'performance' => [
    'queuing' => [
        'enabled' => true,
        'queue' => 'iot',
        'connection' => 'redis',
    ]
]

// Process queued jobs
php artisan queue:work --queue=iot
```

### Database Optimization

```sql
-- Create indexes for better query performance
CREATE INDEX idx_device_telemetry_device_timestamp ON iot_device_telemetry(device_id, timestamp);
CREATE INDEX idx_device_alerts_status_severity ON iot_device_alerts(status, severity);
CREATE INDEX idx_sensor_data_sensor_timestamp ON iot_sensor_data(sensor_id, timestamp);
```

## Security Best Practices

### Device Authentication

1. **Certificate-based Authentication**: Use X.509 certificates for device authentication
2. **Token Rotation**: Implement regular token rotation for API access
3. **Rate Limiting**: Configure appropriate rate limits for device API calls
4. **Encryption**: Enable TLS/SSL for all device communications

### Data Protection

1. **Data Encryption**: Encrypt sensitive telemetry data at rest
2. **Access Controls**: Implement proper RBAC for IoT data access
3. **Audit Logging**: Enable comprehensive audit logging
4. **Data Retention**: Configure appropriate data retention policies

## Testing

### Unit Tests

```bash
# Run IoT package tests
php artisan test --filter IoT

# Run specific test class
php artisan test tests/Feature/IoT/DeviceManagementTest.php
```

### Integration Tests

```php
use Aero\IoT\Models\Device;
use Tests\TestCase;

class DeviceIntegrationTest extends TestCase
{
    public function test_device_can_send_telemetry()
    {
        $device = Device::factory()->create();
        
        $response = $this->postJson('/api/iot/telemetry', [
            'device_id' => $device->id,
            'metric_name' => 'temperature',
            'metric_value' => 25.0,
            'unit' => 'celsius'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('iot_device_telemetry', [
            'device_id' => $device->id,
            'metric_name' => 'temperature'
        ]);
    }
}
```

## Monitoring & Maintenance

### Health Checks

```php
// Check IoT system health
php artisan iot:health-check

// Monitor device connectivity
php artisan iot:check-devices

// Process pending alerts
php artisan iot:process-alerts
```

### Data Cleanup

```php
// Clean up old telemetry data
php artisan iot:cleanup-telemetry --days=90

// Archive old sensor data
php artisan iot:archive-data --days=30

// Purge resolved alerts
php artisan iot:purge-alerts --days=30
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the package
git clone https://github.com/aero/iot.git

# Install dependencies
composer install
npm install

# Run tests
php artisan test

# Build assets
npm run build
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## License

The Aero IoT package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [https://docs.aero-iot.com](https://docs.aero-iot.com)
- **Issues**: [GitHub Issues](https://github.com/aero/iot/issues)
- **Discussions**: [GitHub Discussions](https://github.com/aero/iot/discussions)
- **Email Support**: support@aero-iot.com

## Credits

- **Development Team**: aeos365 Team
- **Contributors**: [All Contributors](https://github.com/aero/iot/contributors)
- **Special Thanks**: Laravel Community, IoT Open Source Projects