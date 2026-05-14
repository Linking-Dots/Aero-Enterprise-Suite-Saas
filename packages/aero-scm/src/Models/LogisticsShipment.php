<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsShipment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'carrier_id',
        'tracking_number',
        'shippable_type',
        'shippable_id',
        'from_location_id',
        'to_location_id',
        'shipping_method',
        'shipping_cost',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimensions_unit',
        'from_address',
        'to_address',
        'ship_date',
        'estimated_delivery',
        'delivered_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'ship_date' => 'datetime',
        'estimated_delivery' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the carrier for this shipment.
     */
    public function carrier()
    {
        return $this->belongsTo(LogisticsCarrier::class, 'carrier_id');
    }

    /**
     * Get the from location.
     */
    public function fromLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    /**
     * Get the to location.
     */
    public function toLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    /**
     * Get the shippable model (polymorphic).
     */
    public function shippable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get shipments by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get in transit shipments.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope to get delivered shipments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Check if shipment is delivered.
     */
    public function isDelivered()
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if shipment is in transit.
     */
    public function isInTransit()
    {
        return $this->status === 'in_transit';
    }

    /**
     * Get calculated volume.
     */
    public function getVolumeAttribute()
    {
        if ($this->length && $this->width && $this->height) {
            return $this->length * $this->width * $this->height;
        }

        return null;
    }

    /**
     * Get tracking URL if available.
     */
    public function getTrackingUrlAttribute()
    {
        if ($this->carrier && $this->tracking_number && $this->carrier->tracking_url_format) {
            return str_replace('{tracking_number}', $this->tracking_number, $this->carrier->tracking_url_format);
        }

        return null;
    }
}
