<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderShipment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_order_shipments';

    protected $fillable = [
        'order_id', 'shipping_method_id', 'carrier_id', 'tracking_number',
        'shipped_at', 'estimated_delivery_at', 'delivered_at', 'status',
        'weight', 'dimensions', 'shipping_cost', 'insurance_cost',
        'created_by', 'notes',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'shipping_method_id' => 'integer',
        'carrier_id' => 'integer',
        'shipped_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'shipping_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'created_by' => 'integer',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_SHIPPED = 'shipped';

    const STATUS_IN_TRANSIT = 'in_transit';

    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_RETURNED = 'returned';

    const STATUS_LOST = 'lost';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trackingEvents()
    {
        return $this->hasMany(ShipmentTrackingEvent::class);
    }

    public function getTrackingUrlAttribute()
    {
        if ($this->carrier && $this->tracking_number) {
            return $this->carrier->generateTrackingUrl($this->tracking_number);
        }

        return null;
    }

    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isInTransit()
    {
        return in_array($this->status, [
            self::STATUS_SHIPPED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
        ]);
    }

    public function getDaysInTransitAttribute()
    {
        if ($this->shipped_at && $this->delivered_at) {
            return $this->shipped_at->diffInDays($this->delivered_at);
        }

        if ($this->shipped_at && ! $this->delivered_at) {
            return $this->shipped_at->diffInDays(now());
        }

        return null;
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SHIPPED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
        ]);
    }
}
