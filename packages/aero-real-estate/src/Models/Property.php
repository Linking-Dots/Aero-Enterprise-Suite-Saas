<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_properties';

    protected $fillable = [
        'property_number', 'owner_id', 'agent_id', 'property_type', 'status',
        'address_line_1', 'address_line_2', 'city', 'state', 'postal_code',
        'country', 'latitude', 'longitude', 'bedrooms', 'bathrooms',
        'square_feet', 'lot_size', 'year_built', 'purchase_price',
        'current_value', 'rental_rate', 'hoa_fee', 'property_taxes',
        'description', 'amenities', 'parking_spaces', 'created_by',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'agent_id' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'bedrooms' => 'integer',
        'bathrooms' => 'decimal:1',
        'square_feet' => 'integer',
        'lot_size' => 'decimal:2',
        'year_built' => 'integer',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'rental_rate' => 'decimal:2',
        'hoa_fee' => 'decimal:2',
        'property_taxes' => 'decimal:2',
        'amenities' => 'json',
        'parking_spaces' => 'integer',
        'created_by' => 'integer',
    ];

    const TYPE_SINGLE_FAMILY = 'single_family';

    const TYPE_CONDO = 'condo';

    const TYPE_TOWNHOUSE = 'townhouse';

    const TYPE_APARTMENT = 'apartment';

    const TYPE_COMMERCIAL = 'commercial';

    const TYPE_LAND = 'land';

    const STATUS_AVAILABLE = 'available';

    const STATUS_RENTED = 'rented';

    const STATUS_SOLD = 'sold';

    const STATUS_OFF_MARKET = 'off_market';

    const STATUS_MAINTENANCE = 'maintenance';

    public function owner()
    {
        return $this->belongsTo(PropertyOwner::class, 'owner_id');
    }

    public function agent()
    {
        return $this->belongsTo(RealEstateAgent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function listings()
    {
        return $this->hasMany(PropertyListing::class);
    }

    public function leases()
    {
        return $this->hasMany(LeaseAgreement::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function photos()
    {
        return $this->hasMany(PropertyPhoto::class);
    }

    public function inspections()
    {
        return $this->hasMany(PropertyInspection::class);
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

    public function getCurrentLeaseAttribute()
    {
        return $this->leases()
            ->where('status', LeaseAgreement::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    public function isRented()
    {
        return $this->status === self::STATUS_RENTED;
    }

    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function getPricePerSqFtAttribute()
    {
        if ($this->square_feet > 0 && $this->current_value > 0) {
            return round($this->current_value / $this->square_feet, 2);
        }

        return 0;
    }

    public function getMonthlyExpensesAttribute()
    {
        return ($this->hoa_fee ?? 0) + (($this->property_taxes ?? 0) / 12);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeRented($query)
    {
        return $query->where('status', self::STATUS_RENTED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('property_type', $type);
    }

    public function scopeInPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('current_value', [$minPrice, $maxPrice]);
    }
}
