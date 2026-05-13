<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyTenant extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_tenants';

    protected $fillable = [
        'tenant_number', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'mobile_phone', 'emergency_contact_name', 'emergency_contact_phone',
        'employer', 'monthly_income', 'credit_score', 'background_check_date',
        'background_check_status', 'references', 'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'monthly_income' => 'decimal:2',
        'credit_score' => 'integer',
        'background_check_date' => 'date',
        'references' => 'json',
        'created_by' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_DECLINED = 'declined';

    const STATUS_EVICTED = 'evicted';

    const BACKGROUND_APPROVED = 'approved';

    const BACKGROUND_PENDING = 'pending';

    const BACKGROUND_FAILED = 'failed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leases()
    {
        return $this->hasMany(LeaseAgreement::class, 'tenant_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

    public function rentPayments()
    {
        return $this->hasManyThrough(RentPayment::class, LeaseAgreement::class, 'tenant_id', 'lease_agreement_id');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getCurrentLeaseAttribute()
    {
        return $this->leases()
            ->where('status', LeaseAgreement::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    public function getCurrentPropertyAttribute()
    {
        $currentLease = $this->getCurrentLeaseAttribute();

        return $currentLease ? $currentLease->property : null;
    }

    public function hasActiveLease()
    {
        return $this->getCurrentLeaseAttribute() !== null;
    }

    public function isBackgroundCheckApproved()
    {
        return $this->background_check_status === self::BACKGROUND_APPROVED;
    }

    public function getCreditRatingAttribute()
    {
        if (! $this->credit_score) {
            return 'Unknown';
        }

        return match (true) {
            $this->credit_score >= 800 => 'Excellent',
            $this->credit_score >= 740 => 'Very Good',
            $this->credit_score >= 670 => 'Good',
            $this->credit_score >= 580 => 'Fair',
            default => 'Poor'
        };
    }

    public function getIncomeToRentRatioAttribute()
    {
        $currentLease = $this->getCurrentLeaseAttribute();
        if (! $currentLease || ! $this->monthly_income) {
            return null;
        }

        return round(($currentLease->monthly_rent / $this->monthly_income) * 100, 2);
    }

    public function getTotalRentPaidAttribute()
    {
        return $this->rentPayments()->sum('amount_paid');
    }

    public function getOutstandingBalanceAttribute()
    {
        $totalDue = $this->rentPayments()->sum('amount_due');
        $totalPaid = $this->rentPayments()->sum('amount_paid');

        return $totalDue - $totalPaid;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWithActiveLease($query)
    {
        return $query->whereHas('leases', function ($q) {
            $q->where('status', LeaseAgreement::STATUS_ACTIVE)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());
        });
    }

    public function scopeApprovedBackground($query)
    {
        return $query->where('background_check_status', self::BACKGROUND_APPROVED);
    }
}
