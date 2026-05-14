<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingAddress extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_shipping_addresses';

    protected $fillable = [
        'customer_id', 'type', 'first_name', 'last_name', 'company_name',
        'address_line_1', 'address_line_2', 'city', 'state', 'postal_code',
        'country', 'phone', 'special_instructions', 'is_default', 'is_verified',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
    ];

    const TYPE_HOME = 'home';

    const TYPE_WORK = 'work';

    const TYPE_OTHER = 'other';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;

        if ($this->address_line_2) {
            $address .= ', '.$this->address_line_2;
        }

        $address .= ', '.$this->city.', '.$this->state.' '.$this->postal_code;

        if ($this->country && $this->country !== 'US') {
            $address .= ', '.$this->country;
        }

        return $address;
    }

    public function getFormattedAddressAttribute()
    {
        $lines = [];

        if ($this->full_name) {
            $lines[] = $this->full_name;
        }

        if ($this->company_name) {
            $lines[] = $this->company_name;
        }

        $lines[] = $this->address_line_1;

        if ($this->address_line_2) {
            $lines[] = $this->address_line_2;
        }

        $lines[] = $this->city.', '.$this->state.' '.$this->postal_code;

        if ($this->country && $this->country !== 'US') {
            $lines[] = $this->country;
        }

        return implode('\n', $lines);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
