<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingRate extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_shipping_rates';

    protected $fillable = [
        'shipping_method_id', 'from_postal_code', 'to_postal_code',
        'weight_min', 'weight_max', 'distance_min', 'distance_max',
        'rate', 'surcharge', 'is_active',
    ];

    protected $casts = [
        'shipping_method_id' => 'integer',
        'weight_min' => 'decimal:2',
        'weight_max' => 'decimal:2',
        'distance_min' => 'decimal:2',
        'distance_max' => 'decimal:2',
        'rate' => 'decimal:2',
        'surcharge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function appliesToWeight($weight)
    {
        return ($this->weight_min === null || $weight >= $this->weight_min) &&
               ($this->weight_max === null || $weight <= $this->weight_max);
    }

    public function appliesToDistance($distance)
    {
        return ($this->distance_min === null || $distance >= $this->distance_min) &&
               ($this->distance_max === null || $distance <= $this->distance_max);
    }

    public function appliesToRoute($fromPostalCode, $toPostalCode)
    {
        if ($this->from_postal_code && $this->from_postal_code !== $fromPostalCode) {
            return false;
        }

        if ($this->to_postal_code && $this->to_postal_code !== $toPostalCode) {
            return false;
        }

        return true;
    }

    public function getTotalRateAttribute()
    {
        return $this->rate + ($this->surcharge ?? 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWeight($query, $weight)
    {
        return $query->where(function ($q) use ($weight) {
            $q->whereNull('weight_min')
                ->orWhere('weight_min', '<=', $weight);
        })->where(function ($q) use ($weight) {
            $q->whereNull('weight_max')
                ->orWhere('weight_max', '>=', $weight);
        });
    }
}
