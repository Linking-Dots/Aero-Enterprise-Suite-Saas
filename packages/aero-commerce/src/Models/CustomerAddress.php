<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_customer_addresses';

    protected $fillable = [
        'customer_id', 'type', 'first_name', 'last_name', 'company_name',
        'address_line_1', 'address_line_2', 'city', 'state', 'postal_code',
        'country', 'phone', 'is_default_billing', 'is_default_shipping',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_default_billing' => 'boolean',
        'is_default_shipping' => 'boolean',
    ];

    const TYPE_BILLING = 'billing';

    const TYPE_SHIPPING = 'shipping';

    const TYPE_BOTH = 'both';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

    public function canBeUsedForBilling()
    {
        return in_array($this->type, [self::TYPE_BILLING, self::TYPE_BOTH]);
    }

    public function canBeUsedForShipping()
    {
        return in_array($this->type, [self::TYPE_SHIPPING, self::TYPE_BOTH]);
    }

    public function scopeDefaultBilling($query)
    {
        return $query->where('is_default_billing', true);
    }

    public function scopeDefaultShipping($query)
    {
        return $query->where('is_default_shipping', true);
    }

    public function scopeForBilling($query)
    {
        return $query->whereIn('type', [self::TYPE_BILLING, self::TYPE_BOTH]);
    }

    public function scopeForShipping($query)
    {
        return $query->whereIn('type', [self::TYPE_SHIPPING, self::TYPE_BOTH]);
    }
}
