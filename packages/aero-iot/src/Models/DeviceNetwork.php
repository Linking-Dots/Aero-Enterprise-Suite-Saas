<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceNetwork extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_networks';

    protected $fillable = [
        'network_name', 'network_type', 'ssid', 'security_type', 'frequency',
        'channel', 'bandwidth', 'max_devices', 'gateway_device_id',
        'configuration', 'is_active', 'created_by',
    ];

    protected $casts = [
        'frequency' => 'decimal:2',
        'max_devices' => 'integer',
        'gateway_device_id' => 'integer',
        'configuration' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_WIFI = 'wifi';

    const TYPE_ETHERNET = 'ethernet';

    const TYPE_CELLULAR = 'cellular';

    const TYPE_LORA = 'lora';

    const TYPE_ZIGBEE = 'zigbee';

    const TYPE_BLUETOOTH = 'bluetooth';

    const TYPE_MESH = 'mesh';

    const SECURITY_OPEN = 'open';

    const SECURITY_WEP = 'wep';

    const SECURITY_WPA = 'wpa';

    const SECURITY_WPA2 = 'wpa2';

    const SECURITY_WPA3 = 'wpa3';

    const SECURITY_ENTERPRISE = 'enterprise';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gatewayDevice()
    {
        return $this->belongsTo(Device::class, 'gateway_device_id');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'network_id');
    }

    public function getActiveDevicesCountAttribute()
    {
        return $this->devices()->where('is_active', true)->count();
    }

    public function getOnlineDevicesCountAttribute()
    {
        return $this->devices()->where('status', Device::STATUS_ONLINE)->count();
    }

    public function getUtilizationPercentageAttribute()
    {
        if (! $this->max_devices) {
            return 0;
        }

        return round(($this->active_devices_count / $this->max_devices) * 100, 1);
    }

    public function hasCapacity()
    {
        if (! $this->max_devices) {
            return true;
        }

        return $this->active_devices_count < $this->max_devices;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('network_type', $type);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_devices')
                    ->orWhereRaw('(
                              SELECT COUNT(*) FROM iot_devices 
                              WHERE network_id = iot_device_networks.id 
                              AND is_active = 1
                          ) < max_devices');
            });
    }
}
