<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalBilling extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_medical_billing';

    protected $fillable = [
        'patient_id', 'provider_id', 'appointment_id', 'invoice_number',
        'service_date', 'procedure_codes', 'diagnosis_codes', 'total_amount',
        'insurance_amount', 'patient_amount', 'copay_amount', 'deductible_amount',
        'status', 'billed_at', 'paid_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'appointment_id' => 'integer',
        'service_date' => 'date',
        'procedure_codes' => 'json',
        'diagnosis_codes' => 'json',
        'total_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
        'copay_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'billed_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_by' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PAID = 'paid';

    const STATUS_PARTIALLY_PAID = 'partially_paid';

    const STATUS_REJECTED = 'rejected';

    const STATUS_APPEALED = 'appealed';

    const STATUS_WRITTEN_OFF = 'written_off';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class, 'billing_id');
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class, 'billing_id');
    }

    public function getOutstandingAmountAttribute()
    {
        $totalPaid = $this->payments()->sum('amount');

        return $this->total_amount - $totalPaid;
    }

    public function getInsurancePortionAttribute()
    {
        return $this->total_amount - $this->patient_amount;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue($days = 30)
    {
        return $this->billed_at &&
               $this->billed_at->addDays($days) < now() &&
               ! $this->isPaid();
    }

    public function getDaysSinceBilledAttribute()
    {
        return $this->billed_at ? $this->billed_at->diffInDays(now()) : null;
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_WRITTEN_OFF]);
    }

    public function scopeOverdue($query, $days = 30)
    {
        return $query->where('billed_at', '<', now()->subDays($days))
            ->whereNotIn('status', [self::STATUS_PAID, self::STATUS_WRITTEN_OFF]);
    }
}
