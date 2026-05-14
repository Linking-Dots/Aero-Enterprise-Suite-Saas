<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveAccrualRule extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'leave_type_id',
        'name',
        'accrual_frequency',
        'accrual_rate',
        'max_balance',
        'min_service_months',
        'is_active',
        'carry_forward',
        'max_carry_forward_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'accrual_rate' => 'decimal:2',
            'max_balance' => 'decimal:2',
            'max_carry_forward_days' => 'decimal:2',
            'min_service_months' => 'integer',
            'is_active' => 'boolean',
            'carry_forward' => 'boolean',
        ];
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveSetting::class, 'leave_type_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveAccrualTransaction::class, 'accrual_rule_id');
    }

    /**
     * Scope to only active rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
