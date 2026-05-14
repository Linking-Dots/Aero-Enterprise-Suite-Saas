<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_equipment';

    protected $fillable = [
        'equipment_number', 'customer_id', 'service_location_id', 'equipment_type_id',
        'manufacturer', 'model', 'serial_number', 'installation_date',
        'warranty_start_date', 'warranty_end_date', 'status', 'operating_hours',
        'last_service_date', 'next_service_date', 'service_interval_days',
        'specifications', 'notes',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'service_location_id' => 'integer',
        'equipment_type_id' => 'integer',
        'installation_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'operating_hours' => 'integer',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'service_interval_days' => 'integer',
        'specifications' => 'json',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_UNDER_MAINTENANCE = 'under_maintenance';

    const STATUS_OUT_OF_ORDER = 'out_of_order';

    const STATUS_RETIRED = 'retired';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function equipmentType()
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function workOrders()
    {
        return $this->hasMany(ServiceWorkOrder::class);
    }

    public function serviceHistory()
    {
        return $this->workOrders()->where('status', ServiceWorkOrder::STATUS_COMPLETED)
            ->orderBy('actual_end_time', 'desc');
    }

    public function maintenanceSchedule()
    {
        return $this->hasMany(EquipmentMaintenanceSchedule::class);
    }

    public function parts()
    {
        return $this->belongsToMany(ServicePart::class, 'field_service_equipment_parts')
            ->withPivot('quantity', 'installed_date', 'warranty_end_date');
    }

    public function getDisplayNameAttribute()
    {
        return $this->manufacturer.' '.$this->model.' ('.$this->equipment_number.')';
    }

    public function isUnderWarranty($date = null)
    {
        $date = $date ?: now()->toDateString();

        return $this->warranty_end_date && $this->warranty_end_date >= $date;
    }

    public function isDueForService()
    {
        return $this->next_service_date && $this->next_service_date <= now()->toDateString();
    }

    public function getDaysSinceLastServiceAttribute()
    {
        if ($this->last_service_date) {
            return now()->diffInDays($this->last_service_date);
        }

        return null;
    }
}
