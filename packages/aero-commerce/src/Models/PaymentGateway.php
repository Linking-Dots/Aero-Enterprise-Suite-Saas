<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_payment_gateways';

    protected $fillable = [
        'name', 'provider', 'configuration', 'supported_currencies',
        'supported_payment_types', 'transaction_fee_percentage', 'transaction_fee_fixed',
        'is_active', 'is_sandbox', 'webhook_secret', 'created_by',
    ];

    protected $casts = [
        'configuration' => 'encrypted:json',
        'supported_currencies' => 'json',
        'supported_payment_types' => 'json',
        'transaction_fee_percentage' => 'decimal:4',
        'transaction_fee_fixed' => 'decimal:2',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
        'created_by' => 'integer',
    ];

    const PROVIDER_STRIPE = 'stripe';

    const PROVIDER_PAYPAL = 'paypal';

    const PROVIDER_SQUARE = 'square';

    const PROVIDER_BRAINTREE = 'braintree';

    const PROVIDER_AUTHORIZE_NET = 'authorize_net';

    const PROVIDER_RAZORPAY = 'razorpay';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function supportsCurrency($currency)
    {
        return in_array(strtoupper($currency), $this->supported_currencies ?? []);
    }

    public function supportsPaymentType($paymentType)
    {
        return in_array($paymentType, $this->supported_payment_types ?? []);
    }

    public function calculateTransactionFee($amount)
    {
        $percentageFee = ($amount * $this->transaction_fee_percentage) / 100;

        return $percentageFee + $this->transaction_fee_fixed;
    }

    public function getConfigValue($key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProduction($query)
    {
        return $query->where('is_sandbox', false);
    }
}
