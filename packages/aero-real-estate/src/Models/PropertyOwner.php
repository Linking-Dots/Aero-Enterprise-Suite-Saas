<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyOwner extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_owners';

    protected $fillable = [
        'owner_number', 'user_id', 'owner_type', 'first_name', 'last_name',
        'company_name', 'email', 'phone', 'mobile_phone', 'address_line_1',
        'address_line_2', 'city', 'state', 'postal_code', 'country',
        'tax_id', 'preferred_contact_method', 'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'created_by' => 'integer',
    ];

    const TYPE_INDIVIDUAL = 'individual';

    const TYPE_CORPORATION = 'corporation';

    const TYPE_LLC = 'llc';

    const TYPE_PARTNERSHIP = 'partnership';

    const TYPE_TRUST = 'trust';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    const CONTACT_EMAIL = 'email';

    const CONTACT_PHONE = 'phone';

    const CONTACT_MAIL = 'mail';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function getDisplayNameAttribute()
    {
        if ($this->owner_type === self::TYPE_INDIVIDUAL) {
            return trim($this->first_name.' '.$this->last_name);
        }

        return $this->company_name;
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

    public function getTotalPropertiesAttribute()
    {
        return $this->properties()->count();
    }

    public function getTotalPropertyValueAttribute()
    {
        return $this->properties()->sum('current_value');
    }

    public function getMonthlyRentalIncomeAttribute()
    {
        return $this->properties()
            ->where('status', Property::STATUS_RENTED)
            ->sum('rental_rate');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('owner_type', self::TYPE_INDIVIDUAL);
    }

    public function scopeCompanies($query)
    {
        return $query->where('owner_type', '!=', self::TYPE_INDIVIDUAL);
    }
}
