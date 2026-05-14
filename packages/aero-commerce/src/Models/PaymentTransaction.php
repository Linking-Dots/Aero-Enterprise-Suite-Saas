<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_payment_transactions';

    protected $fillable = [
        'order_payment_id', 'payment_gateway_id', 'transaction_type', 'gateway_transaction_id',
        'amount', 'currency', 'status', 'gateway_request', 'gateway_response',
        'processed_at', 'failure_reason',
    ];

    protected $casts = [
        'order_payment_id' => 'integer',
        'payment_gateway_id' => 'integer',
        'amount' => 'decimal:2',
        'gateway_request' => 'json',
        'gateway_response' => 'json',
        'processed_at' => 'datetime',
    ];

    const TYPE_AUTHORIZE = 'authorize';

    const TYPE_CAPTURE = 'capture';

    const TYPE_SALE = 'sale';

    const TYPE_REFUND = 'refund';

    const TYPE_VOID = 'void';

    const STATUS_PENDING = 'pending';

    const STATUS_SUCCESS = 'success';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function isSuccessful()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
}
