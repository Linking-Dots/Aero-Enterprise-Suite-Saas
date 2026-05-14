<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrackingEvent extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_shipment_tracking_events';

    protected $fillable = [
        'order_shipment_id', 'event_type', 'event_description', 'event_location',
        'event_timestamp', 'carrier_status_code', 'additional_data',
    ];

    protected $casts = [
        'order_shipment_id' => 'integer',
        'event_timestamp' => 'datetime',
        'additional_data' => 'json',
    ];

    const EVENT_LABEL_CREATED = 'label_created';

    const EVENT_PICKED_UP = 'picked_up';

    const EVENT_IN_TRANSIT = 'in_transit';

    const EVENT_OUT_FOR_DELIVERY = 'out_for_delivery';

    const EVENT_DELIVERED = 'delivered';

    const EVENT_DELIVERY_ATTEMPTED = 'delivery_attempted';

    const EVENT_EXCEPTION = 'exception';

    const EVENT_RETURNED = 'returned';

    public function orderShipment()
    {
        return $this->belongsTo(OrderShipment::class);
    }

    public function isDeliveryEvent()
    {
        return $this->event_type === self::EVENT_DELIVERED;
    }

    public function isExceptionEvent()
    {
        return $this->event_type === self::EVENT_EXCEPTION;
    }

    public function getFormattedTimestampAttribute()
    {
        return $this->event_timestamp->format('M j, Y g:i A');
    }

    public function scopeByType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeDelivered($query)
    {
        return $query->where('event_type', self::EVENT_DELIVERED);
    }

    public function scopeExceptions($query)
    {
        return $query->where('event_type', self::EVENT_EXCEPTION);
    }
}
