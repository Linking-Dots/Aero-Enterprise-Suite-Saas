<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_devices';

    protected $fillable = [
        'device_id', 'device_name', 'device_type_id', 'manufacturer', 'model',
        'serial_number', 'mac_address', 'ip_address', 'firmware_version',
        'location_name', 'latitude', 'longitude', 'altitude', 'status',
        'connection_type', 'network_id', 'last_seen', 'battery_level',
        'signal_strength', 'configuration', 'metadata', 'is_active',
        'installation_date', 'warranty_expiry', 'maintenance_schedule',
        'created_by',
    ];

    protected $casts = [
        'device_type_id' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'altitude' => 'decimal:2',
        'last_seen' => 'datetime',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'configuration' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'installation_date' => 'date',
        'warranty_expiry' => 'date',
        'maintenance_schedule' => 'json',
        'created_by' => 'integer',
    ];

    const STATUS_ONLINE = 'online';

    const STATUS_OFFLINE = 'offline';

    const STATUS_MAINTENANCE = 'maintenance';

    const STATUS_ERROR = 'error';

    const STATUS_DECOMMISSIONED = 'decommissioned';

    const STATUS_PROVISIONING = 'provisioning';

    const CONNECTION_WIFI = 'wifi';

    const CONNECTION_ETHERNET = 'ethernet';

    const CONNECTION_CELLULAR = 'cellular';

    const CONNECTION_BLUETOOTH = 'bluetooth';

    const CONNECTION_LORA = 'lora';

    const CONNECTION_ZIGBEE = 'zigbee';

    const CONNECTION_MQTT = 'mqtt';

    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function network()
    {
        return $this->belongsTo(DeviceNetwork::class, 'network_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function telemetryData()
    {
        return $this->hasMany(DeviceTelemetry::class);
    }

    public function alerts()
    {
        return $this->hasMany(DeviceAlert::class);
    }

    public function commands()
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function firmware()
    {
        return $this->hasMany(DeviceFirmware::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(DeviceMaintenance::class);
    }

    public function isOnline()
    {
        return $this->status === self::STATUS_ONLINE;
    }

    public function isOffline()
    {
        return $this->status === self::STATUS_OFFLINE;
    }

    public function hasError()
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function isInMaintenance()
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function getUptimePercentageAttribute()
    {
        $totalTime = now()->diffInMinutes($this->created_at);
        if ($totalTime === 0) {
            return 100;
        }

        $downtime = $this->telemetryData()
            ->where('metric_name', 'uptime')
            ->where('metric_value', '0')
            ->count();

        return round((($totalTime - $downtime) / $totalTime) * 100, 2);
    }

    public function getLocationDescriptionAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return $this->location_name.' ('.$this->latitude.', '.$this->longitude.')';
        }

        return $this->location_name ?: 'Location not set';
    }

    public function getLatestTelemetryAttribute()
    {
        return $this->telemetryData()
            ->orderBy('timestamp', 'desc')
            ->limit(10)
            ->get();
    }

    public function getBatteryStatusAttribute()
    {
        if (! $this->battery_level) {
            return 'N/A';
        }

        return match (true) {
            $this->battery_level >= 80 => 'Excellent',
            $this->battery_level >= 60 => 'Good',
            $this->battery_level >= 40 => 'Fair',
            $this->battery_level >= 20 => 'Low',
            default => 'Critical'
        };
    }

    public function getSignalQualityAttribute()
    {
        if (! $this->signal_strength) {
            return 'N/A';
        }

        return match (true) {
            $this->signal_strength >= -50 => 'Excellent',
            $this->signal_strength >= -60 => 'Good',
            $this->signal_strength >= -70 => 'Fair',
            $this->signal_strength >= -80 => 'Weak',
            default => 'Very Weak'
        };
    }

    public function updateLastSeen()
    {
        $this->update(['last_seen' => now()]);
    }

    public function sendCommand($command, $parameters = [])
    {
        return $this->commands()->create([
            'command_name' => $command,
            'parameters' => $parameters,
            'status' => DeviceCommand::STATUS_PENDING,
            'sent_at' => now(),
        ]);
    }

    public function recordTelemetry($metricName, $metricValue, $unit = null)
    {
        return $this->telemetryData()->create([
            'metric_name' => $metricName,
            'metric_value' => $metricValue,
            'unit' => $unit,
            'timestamp' => now(),
        ]);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', self::STATUS_OFFLINE);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('device_type_id', $typeId);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location_name', 'like', '%'.$location.'%');
    }

    public function scopeRecentlyActive($query, $minutes = 30)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }

    public function scopeLowBattery($query, $threshold = 20)
    {
        return $query->where('battery_level', '<=', $threshold)
            ->whereNotNull('battery_level');
    }
}
