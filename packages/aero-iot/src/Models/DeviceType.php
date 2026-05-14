<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceType extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_types';

    protected $fillable = [
        'type_code', 'type_name', 'description', 'category', 'manufacturer',
        'default_configuration', 'supported_protocols', 'sensor_capabilities',
        'power_requirements', 'connectivity_options', 'icon', 'is_active',
        'created_by',
    ];

    protected $casts = [
        'default_configuration' => 'json',
        'supported_protocols' => 'json',
        'sensor_capabilities' => 'json',
        'power_requirements' => 'json',
        'connectivity_options' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const CATEGORY_SENSOR = 'sensor';

    const CATEGORY_ACTUATOR = 'actuator';

    const CATEGORY_GATEWAY = 'gateway';

    const CATEGORY_CONTROLLER = 'controller';

    const CATEGORY_MONITOR = 'monitor';

    const CATEGORY_CAMERA = 'camera';

    const CATEGORY_ENVIRONMENTAL = 'environmental';

    const CATEGORY_INDUSTRIAL = 'industrial';

    const CATEGORY_SMART_HOME = 'smart_home';

    const CATEGORY_WEARABLE = 'wearable';

    const CATEGORY_VEHICLE = 'vehicle';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function getActiveDevicesCountAttribute()
    {
        return $this->devices()->where('is_active', true)->count();
    }

    public function getOnlineDevicesCountAttribute()
    {
        return $this->devices()->where('status', Device::STATUS_ONLINE)->count();
    }

    public function supportsProtocol($protocol)
    {
        return is_array($this->supported_protocols) &&
               in_array($protocol, $this->supported_protocols);
    }

    public function hasCapability($capability)
    {
        return is_array($this->sensor_capabilities) &&
               in_array($capability, $this->sensor_capabilities);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByManufacturer($query, $manufacturer)
    {
        return $query->where('manufacturer', $manufacturer);
    }
}
