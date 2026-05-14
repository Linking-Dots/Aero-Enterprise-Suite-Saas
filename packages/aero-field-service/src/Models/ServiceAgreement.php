<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAgreement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_agreements';

    protected $fillable = [
        'agreement_number', 'customer_id', 'service_location_id', 'agreement_type',
        'title', 'description', 'start_date', 'end_date', 'status',
        'billing_frequency', 'contract_value', 'currency', 'response_time_hours',
        'coverage_hours', 'included_services', 'excluded_services',
        'terms_and_conditions', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'service_location_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
        'response_time_hours' => 'integer',
        'coverage_hours' => 'json',
        'included_services' => 'json',
        'excluded_services' => 'json',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_FULL_SERVICE = 'full_service';

    const TYPE_WARRANTY = 'warranty';

    const TYPE_ON_DEMAND = 'on_demand';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_SUSPENDED = 'suspended';

    const BILLING_MONTHLY = 'monthly';

    const BILLING_QUARTERLY = 'quarterly';

    const BILLING_ANNUALLY = 'annually';

    const BILLING_PER_SERVICE = 'per_service';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function workOrders()
    {
        return $this->hasMany(ServiceWorkOrder::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'field_service_agreement_equipment');
    }

    public function renewals()
    {
        return $this->hasMany(ServiceAgreementRenewal::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->start_date <= now()->toDateString() &&
               $this->end_date >= now()->toDateString();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->end_date <= now()->addDays($days)->toDateString();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->end_date) {
            return now()->diffInDays($this->end_date, false);
        }

        return null;
    }
}
