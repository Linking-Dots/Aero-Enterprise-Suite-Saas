<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaseAgreement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_lease_agreements';

    protected $fillable = [
        'property_id', 'tenant_id', 'agent_id', 'lease_number', 'lease_type',
        'start_date', 'end_date', 'monthly_rent', 'security_deposit',
        'pet_deposit', 'late_fee', 'grace_period_days', 'lease_terms',
        'status', 'signed_date', 'move_in_date', 'move_out_date',
        'early_termination_fee', 'renewal_terms', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'tenant_id' => 'integer',
        'agent_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'pet_deposit' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'grace_period_days' => 'integer',
        'lease_terms' => 'json',
        'signed_date' => 'date',
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'early_termination_fee' => 'decimal:2',
        'renewal_terms' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_FIXED_TERM = 'fixed_term';

    const TYPE_MONTH_TO_MONTH = 'month_to_month';

    const TYPE_PERIODIC = 'periodic';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_TERMINATED = 'terminated';

    const STATUS_RENEWED = 'renewed';

    const STATUS_CANCELLED = 'cancelled';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(PropertyTenant::class);
    }

    public function agent()
    {
        return $this->belongsTo(RealEstateAgent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rentPayments()
    {
        return $this->hasMany(RentPayment::class);
    }

    public function renewals()
    {
        return $this->hasMany(LeaseRenewal::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->start_date <= now()->toDateString() &&
               $this->end_date >= now()->toDateString();
    }

    public function isExpired()
    {
        return $this->end_date < now()->toDateString();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->end_date <= now()->addDays($days)->toDateString() &&
               $this->status === self::STATUS_ACTIVE;
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->end_date) {
            return now()->diffInDays($this->end_date, false);
        }

        return null;
    }

    public function getLeaseDurationAttribute()
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getTotalDepositAttribute()
    {
        return ($this->security_deposit ?? 0) + ($this->pet_deposit ?? 0);
    }

    public function getOutstandingRentAttribute()
    {
        $totalRent = $this->rentPayments()->sum('amount_due');
        $totalPaid = $this->rentPayments()->sum('amount_paid');

        return $totalRent - $totalPaid;
    }

    public function getCurrentMonthPaymentAttribute()
    {
        return $this->rentPayments()
            ->whereYear('due_date', now()->year)
            ->whereMonth('due_date', now()->month)
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }
}
