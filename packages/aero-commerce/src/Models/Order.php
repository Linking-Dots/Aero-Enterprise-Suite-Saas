<?php

namespace Aero\Commerce\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_orders';

    protected $fillable = [
        'order_number', 'customer_id', 'user_id', 'order_status', 'payment_status',
        'fulfillment_status', 'subtotal', 'tax_amount', 'shipping_amount',
        'discount_amount', 'total_amount', 'currency', 'exchange_rate',
        'billing_address', 'shipping_address', 'notes', 'order_date',
        'shipped_date', 'delivered_date', 'cart_id', 'coupon_code',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'user_id' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'billing_address' => 'json',
        'shipping_address' => 'json',
        'order_date' => 'datetime',
        'shipped_date' => 'datetime',
        'delivered_date' => 'datetime',
        'cart_id' => 'integer',
    ];

    const ORDER_STATUS_PENDING = 'pending';

    const ORDER_STATUS_CONFIRMED = 'confirmed';

    const ORDER_STATUS_PROCESSING = 'processing';

    const ORDER_STATUS_SHIPPED = 'shipped';

    const ORDER_STATUS_DELIVERED = 'delivered';

    const ORDER_STATUS_CANCELLED = 'cancelled';

    const ORDER_STATUS_REFUNDED = 'refunded';

    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_PARTIAL = 'partial';

    const PAYMENT_STATUS_FAILED = 'failed';

    const PAYMENT_STATUS_REFUNDED = 'refunded';

    const FULFILLMENT_STATUS_PENDING = 'pending';

    const FULFILLMENT_STATUS_PROCESSING = 'processing';

    const FULFILLMENT_STATUS_SHIPPED = 'shipped';

    const FULFILLMENT_STATUS_DELIVERED = 'delivered';

    const FULFILLMENT_STATUS_RETURNED = 'returned';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cart()
    {
        return $this->belongsTo(ShoppingCart::class, 'cart_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function canBeCancelled()
    {
        return in_array($this->order_status, [self::ORDER_STATUS_PENDING, self::ORDER_STATUS_CONFIRMED]);
    }

    public function canBeRefunded()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID &&
               $this->order_status !== self::ORDER_STATUS_REFUNDED;
    }
}
