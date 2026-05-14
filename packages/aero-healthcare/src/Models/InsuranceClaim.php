<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceClaim extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_insurance_claims';

    protected $fillable = [
        'billing_id', 'insurance_provider_id', 'claim_number', 'claim_type',
        'submitted_amount', 'approved_amount', 'paid_amount', 'denied_amount',
        'patient_responsibility', 'status', 'submitted_at', 'processed_at',
        'denial_reason', 'appeal_deadline', 'notes', 'created_by',
    ];

    protected $casts = [
        'billing_id' => 'integer',
        'insurance_provider_id' => 'integer',
        'submitted_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'denied_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'appeal_deadline' => 'date',
        'created_by' => 'integer',
    ];

    const TYPE_PROFESSIONAL = 'professional';

    const TYPE_INSTITUTIONAL = 'institutional';

    const TYPE_DENTAL = 'dental';

    const TYPE_VISION = 'vision';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_APPROVED = 'approved';

    const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    const STATUS_DENIED = 'denied';

    const STATUS_PAID = 'paid';

    const STATUS_APPEALED = 'appealed';

    public function billing()
    {
        return $this->belongsTo(MedicalBilling::class);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isApproved()
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIALLY_APPROVED]);
    }

    public function isDenied()
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function canBeAppealed()
    {
        return $this->isDenied() &&
               $this->appeal_deadline &&
               $this->appeal_deadline >= now()->toDateString();
    }

    public function getApprovalRateAttribute()
    {
        if ($this->submitted_amount == 0) {
            return 0;
        }

        return round(($this->approved_amount / $this->submitted_amount) * 100, 2);
    }

    public function getDaysToProcessAttribute()
    {
        if ($this->submitted_at && $this->processed_at) {
            return $this->submitted_at->diffInDays($this->processed_at);
        }

        return null;
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PARTIALLY_APPROVED]);
    }

    public function scopeDenied($query)
    {
        return $query->where('status', self::STATUS_DENIED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }
}
