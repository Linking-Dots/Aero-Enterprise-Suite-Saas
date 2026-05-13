<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPayment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_order_payments';

    protected $fillable = [
        'order_id', 'payment_method_id', 'payment_gateway_id', 'transaction_id',
        'amount', 'currency', 'status', 'gateway_response', 'processed_at',
        'refunded_amount', 'refunded_at', 'notes',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'payment_method_id' => 'integer',
        'payment_gateway_id' => 'integer',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'gateway_response' => 'json',
        'processed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isSuccessful()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeRefunded()
    {
        return $this->status === self::STATUS_COMPLETED &&
               $this->refunded_amount < $this->amount;
    }

    public function getRemainingRefundableAmountAttribute()
    {
        return $this->amount - ($this->refunded_amount ?? 0);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
