<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RealEstateAgent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_agents';

    protected $fillable = [
        'agent_number', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'mobile_phone', 'license_number', 'license_state',
        'license_expiry', 'brokerage_id', 'specialization', 'commission_rate',
        'bio', 'languages_spoken', 'service_areas', 'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'license_expiry' => 'date',
        'brokerage_id' => 'integer',
        'commission_rate' => 'decimal:4',
        'languages_spoken' => 'json',
        'service_areas' => 'json',
        'created_by' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    const SPECIALIZATION_RESIDENTIAL = 'residential';

    const SPECIALIZATION_COMMERCIAL = 'commercial';

    const SPECIALIZATION_LUXURY = 'luxury';

    const SPECIALIZATION_INVESTMENT = 'investment';

    const SPECIALIZATION_FIRST_TIME = 'first_time_buyers';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brokerage()
    {
        return $this->belongsTo(RealEstateBrokerage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function listings()
    {
        return $this->hasMany(PropertyListing::class, 'agent_id');
    }

    public function clients()
    {
        return $this->belongsToMany(PropertyClient::class, 'real_estate_agent_clients')
            ->withPivot('relationship_type', 'start_date');
    }

    public function transactions()
    {
        return $this->hasMany(PropertyTransaction::class, 'agent_id');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isLicenseValid()
    {
        return $this->license_expiry && $this->license_expiry->isFuture();
    }

    public function getTotalSalesAttribute()
    {
        return $this->transactions()
            ->where('transaction_type', PropertyTransaction::TYPE_SALE)
            ->where('status', PropertyTransaction::STATUS_CLOSED)
            ->count();
    }

    public function getTotalSalesVolumeAttribute()
    {
        return $this->transactions()
            ->where('transaction_type', PropertyTransaction::TYPE_SALE)
            ->where('status', PropertyTransaction::STATUS_CLOSED)
            ->sum('sale_price');
    }

    public function getActiveListingsCountAttribute()
    {
        return $this->listings()
            ->where('status', PropertyListing::STATUS_ACTIVE)
            ->count();
    }

    public function servesArea($area)
    {
        return in_array($area, $this->service_areas ?? []);
    }

    public function speaksLanguage($language)
    {
        return in_array($language, $this->languages_spoken ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeValidLicense($query)
    {
        return $query->where('license_expiry', '>', now());
    }
}
