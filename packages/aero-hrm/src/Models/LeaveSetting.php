<?php

namespace Aero\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveSetting extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'annual_quota',
        'accrual_type',
        'carry_forward_allowed',
        'max_carry_forward_days',
        'encashment_allowed',
        'requires_approval',
        'min_days_notice',
        'max_consecutive_days',
        'allow_half_day',
        'is_paid',
        'is_active',
        'color',
        'description',
        // Legacy fields for backward compatibility
        'type',
        'days',
        'eligibility',
        'carry_forward',
        'earned_leave',
        'auto_approve',
        'special_conditions',
    ];

    protected $casts = [
        'annual_quota' => 'integer',
        'carry_forward_allowed' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'encashment_allowed' => 'boolean',
        'requires_approval' => 'boolean',
        'min_days_notice' => 'integer',
        'max_consecutive_days' => 'integer',
        'allow_half_day' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        // Legacy casts
        'days' => 'integer',
        'carry_forward' => 'boolean',
        'earned_leave' => 'boolean',
        'auto_approve' => 'boolean',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type');
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
