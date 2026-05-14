<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_shipping_methods';

    protected $fillable = [
        'name', 'description', 'carrier_id', 'method_type', 'base_cost',
        'cost_per_kg', 'cost_per_km', 'free_shipping_threshold',
        'estimated_delivery_days_min', 'estimated_delivery_days_max',
        'is_active', 'is_trackable', 'requires_signature', 'created_by',
    ];

    protected $casts = [
        'carrier_id' => 'integer',
        'base_cost' => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'estimated_delivery_days_min' => 'integer',
        'estimated_delivery_days_max' => 'integer',
        'is_active' => 'boolean',
        'is_trackable' => 'boolean',
        'requires_signature' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_STANDARD = 'standard';

    const TYPE_EXPRESS = 'express';

    const TYPE_OVERNIGHT = 'overnight';

    const TYPE_SAME_DAY = 'same_day';

    const TYPE_PICKUP = 'pickup';

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function calculateShippingCost($weight, $distance, $orderValue = null)
    {
        $cost = $this->base_cost;

        if ($this->cost_per_kg && $weight) {
            $cost += $this->cost_per_kg * $weight;
        }

        if ($this->cost_per_km && $distance) {
            $cost += $this->cost_per_km * $distance;
        }

        // Apply free shipping threshold
        if ($orderValue && $this->free_shipping_threshold && $orderValue >= $this->free_shipping_threshold) {
            $cost = 0;
        }

        return max(0, $cost);
    }

    public function getEstimatedDeliveryRange()
    {
        if ($this->estimated_delivery_days_min === $this->estimated_delivery_days_max) {
            return $this->estimated_delivery_days_min.' day'.($this->estimated_delivery_days_min > 1 ? 's' : '');
        }

        return $this->estimated_delivery_days_min.'-'.$this->estimated_delivery_days_max.' days';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrackable($query)
    {
        return $query->where('is_trackable', true);
    }
}
