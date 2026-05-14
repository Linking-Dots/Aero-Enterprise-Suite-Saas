<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceLocation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_locations';

    protected $fillable = [
        'customer_id', 'location_name', 'location_type', 'address_line_1',
        'address_line_2', 'city', 'state', 'postal_code', 'country',
        'latitude', 'longitude', 'contact_person', 'contact_phone',
        'contact_email', 'access_instructions', 'operating_hours',
        'is_active', 'time_zone',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'operating_hours' => 'json',
        'is_active' => 'boolean',
    ];

    const TYPE_OFFICE = 'office';

    const TYPE_WAREHOUSE = 'warehouse';

    const TYPE_RETAIL = 'retail';

    const TYPE_MANUFACTURING = 'manufacturing';

    const TYPE_RESIDENTIAL = 'residential';

    const TYPE_OTHER = 'other';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function workOrders()
    {
        return $this->hasMany(ServiceWorkOrder::class);
    }

    public function serviceAgreements()
    {
        return $this->hasMany(ServiceAgreement::class);
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', '.$this->address_line_2;
        }
        $address .= ', '.$this->city.', '.$this->state.' '.$this->postal_code;

        return $address;
    }

    public function getCoordinatesAttribute()
    {
        return ['lat' => $this->latitude, 'lng' => $this->longitude];
    }

    public function hasCoordinates()
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
