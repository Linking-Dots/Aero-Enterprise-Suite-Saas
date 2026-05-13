<?php

namespace Aero\IoT\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IoTGateway extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_gateways';

    protected $fillable = [
        'gateway_id', 'gateway_name', 'model', 'manufacturer', 'serial_number',
        'mac_address', 'ip_address', 'firmware_version', 'hardware_version',
        'location_name', 'latitude', 'longitude', 'status', 'connection_type',
        'supported_protocols', 'max_connections', 'current_connections',
        'uptime', 'last_heartbeat', 'configuration', 'capabilities',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'supported_protocols' => 'json',
        'max_connections' => 'integer',
        'current_connections' => 'integer',
        'uptime' => 'integer',
        'last_heartbeat' => 'datetime',
        'configuration' => 'json',
        'capabilities' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const STATUS_ONLINE = 'online';

    const STATUS_OFFLINE = 'offline';

    const STATUS_ERROR = 'error';

    const STATUS_MAINTENANCE = 'maintenance';

    const STATUS_PROVISIONING = 'provisioning';

    const CONNECTION_ETHERNET = 'ethernet';

    const CONNECTION_WIFI = 'wifi';

    const CONNECTION_CELLULAR = 'cellular';

    const CONNECTION_SATELLITE = 'satellite';

    const PROTOCOL_MQTT = 'mqtt';

    const PROTOCOL_COAP = 'coap';

    const PROTOCOL_HTTP = 'http';

    const PROTOCOL_WEBSOCKET = 'websocket';

    const PROTOCOL_ZIGBEE = 'zigbee';

    const PROTOCOL_LORA = 'lora';

    const PROTOCOL_BLUETOOTH = 'bluetooth';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function networks()
    {
        return $this->hasMany(DeviceNetwork::class, 'gateway_device_id');
    }

    public function connectedDevices()
    {
        return $this->hasManyThrough(Device::class, DeviceNetwork::class, 'gateway_device_id', 'network_id');
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

    public function getCapacityUsageAttribute()
    {
        if (! $this->max_connections) {
            return 0;
        }

        return round(($this->current_connections / $this->max_connections) * 100, 1);
    }

    public function getUptimeFormattedAttribute()
    {
        if (! $this->uptime) {
            return 'Unknown';
        }

        $days = floor($this->uptime / 86400);
        $hours = floor(($this->uptime % 86400) / 3600);
        $minutes = floor(($this->uptime % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }

    public function getLastHeartbeatStatusAttribute()
    {
        if (! $this->last_heartbeat) {
            return 'Never';
        }

        $minutesAgo = now()->diffInMinutes($this->last_heartbeat);

        return match (true) {
            $minutesAgo <= 1 => 'Just now',
            $minutesAgo <= 5 => 'Recent',
            $minutesAgo <= 15 => 'Warning',
            default => 'Stale'
        };
    }

    public function supportsProtocol($protocol)
    {
        return is_array($this->supported_protocols) &&
               in_array($protocol, $this->supported_protocols);
    }

    public function hasCapability($capability)
    {
        return is_array($this->capabilities) &&
               in_array($capability, $this->capabilities);
    }

    public function canAcceptConnection()
    {
        if (! $this->max_connections) {
            return true;
        }

        return $this->current_connections < $this->max_connections;
    }

    public function getAvailableConnectionsAttribute()
    {
        if (! $this->max_connections) {
            return 'Unlimited';
        }

        return $this->max_connections - $this->current_connections;
    }

    public function updateHeartbeat($data = [])
    {
        $updateData = [
            'last_heartbeat' => now(),
            'status' => self::STATUS_ONLINE,
        ];

        if (isset($data['current_connections'])) {
            $updateData['current_connections'] = $data['current_connections'];
        }

        if (isset($data['uptime'])) {
            $updateData['uptime'] = $data['uptime'];
        }

        $this->update($updateData);
    }

    public function incrementConnections()
    {
        $this->increment('current_connections');
    }

    public function decrementConnections()
    {
        $this->decrement('current_connections');

        // Ensure it doesn't go below 0
        if ($this->current_connections < 0) {
            $this->update(['current_connections' => 0]);
        }
    }

    public function resetConnections()
    {
        $this->update(['current_connections' => 0]);
    }

    public function getHealthScore()
    {
        $score = 100;

        // Deduct for offline status
        if ($this->isOffline()) {
            $score -= 50;
        }
        if ($this->hasError()) {
            $score -= 30;
        }

        // Deduct for stale heartbeat
        if ($this->last_heartbeat) {
            $minutesAgo = now()->diffInMinutes($this->last_heartbeat);
            if ($minutesAgo > 15) {
                $score -= 20;
            }
            if ($minutesAgo > 60) {
                $score -= 20;
            }
        } else {
            $score -= 40;
        }

        // Deduct for high capacity usage
        if ($this->capacity_usage > 90) {
            $score -= 15;
        }
        if ($this->capacity_usage > 80) {
            $score -= 10;
        }

        return max(0, $score);
    }

    public function getHealthStatusAttribute()
    {
        $score = $this->getHealthScore();

        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Fair',
            $score >= 30 => 'Poor',
            default => 'Critical'
        };
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

    public function scopeByProtocol($query, $protocol)
    {
        return $query->whereJsonContains('supported_protocols', $protocol);
    }

    public function scopeByCapability($query, $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ONLINE)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_connections')
                    ->orWhereRaw('current_connections < max_connections');
            });
    }

    public function scopeRecentHeartbeat($query, $minutes = 15)
    {
        return $query->where('last_heartbeat', '>=', now()->subMinutes($minutes));
    }

    public function scopeStaleHeartbeat($query, $minutes = 15)
    {
        return $query->where(function ($q) use ($minutes) {
            $q->whereNull('last_heartbeat')
                ->orWhere('last_heartbeat', '<', now()->subMinutes($minutes));
        });
    }

    public function scopeHighCapacity($query, $threshold = 80)
    {
        return $query->whereNotNull('max_connections')
            ->whereRaw('(current_connections / max_connections * 100) >= ?', [$threshold]);
    }
}
