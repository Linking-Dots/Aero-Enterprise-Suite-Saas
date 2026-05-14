<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAccrualTransaction extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'accrual_rule_id',
        'transaction_type',
        'days',
        'balance_before',
        'balance_after',
        'period_month',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'period_month' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveSetting::class, 'leave_type_id');
    }

    public function accrualRule(): BelongsTo
    {
        return $this->belongsTo(LeaveAccrualRule::class, 'accrual_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
