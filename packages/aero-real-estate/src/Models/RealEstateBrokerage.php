<?php

namespace Aero\RealEstate\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RealEstateBrokerage extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_brokerages';

    protected $fillable = [
        'brokerage_name', 'license_number', 'license_state', 'license_expiry',
        'broker_name', 'address_line_1', 'address_line_2', 'city', 'state',
        'postal_code', 'country', 'phone', 'email', 'website', 'mls_access',
        'commission_structure', 'specializations', 'status', 'created_by',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'mls_access' => 'json',
        'commission_structure' => 'json',
        'specializations' => 'json',
        'created_by' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agents()
    {
        return $this->hasMany(RealEstateAgent::class, 'brokerage_id');
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

    public function isLicenseValid()
    {
        return $this->license_expiry && $this->license_expiry->isFuture();
    }

    public function getTotalAgentsAttribute()
    {
        return $this->agents()->count();
    }

    public function getActiveAgentsAttribute()
    {
        return $this->agents()->where('status', RealEstateAgent::STATUS_ACTIVE)->count();
    }

    public function getTotalSalesAttribute()
    {
        return PropertyTransaction::whereHas('agent', function ($query) {
            $query->where('brokerage_id', $this->id);
        })->where('status', PropertyTransaction::STATUS_CLOSED)->count();
    }

    public function getTotalSalesVolumeAttribute()
    {
        return PropertyTransaction::whereHas('agent', function ($query) {
            $query->where('brokerage_id', $this->id);
        })->where('status', PropertyTransaction::STATUS_CLOSED)->sum('sale_price');
    }

    public function hasMLSAccess($mls = null)
    {
        if (! $mls) {
            return ! empty($this->mls_access);
        }

        return in_array($mls, $this->mls_access ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValidLicense($query)
    {
        return $query->where('license_expiry', '>', now());
    }
}
