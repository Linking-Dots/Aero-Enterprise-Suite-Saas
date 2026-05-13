<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingPayment extends TenantModel
{
    use HasFactory;

    protected $table = 'healthcare_billing_payments';

    protected $fillable = [
        'billing_id', 'payment_method', 'amount', 'payment_date',
        'reference_number', 'payer_type', 'notes', 'processed_by',
    ];

    protected $casts = [
        'billing_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'processed_by' => 'integer',
    ];

    const METHOD_CASH = 'cash';

    const METHOD_CHECK = 'check';

    const METHOD_CREDIT_CARD = 'credit_card';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_INSURANCE = 'insurance';

    const PAYER_PATIENT = 'patient';

    const PAYER_INSURANCE = 'insurance';

    const PAYER_THIRD_PARTY = 'third_party';

    public function billing()
    {
        return $this->belongsTo(MedicalBilling::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isInsurancePayment()
    {
        return $this->payer_type === self::PAYER_INSURANCE;
    }

    public function isPatientPayment()
    {
        return $this->payer_type === self::PAYER_PATIENT;
    }

    public function scopeByPayerType($query, $payerType)
    {
        return $query->where('payer_type', $payerType);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
