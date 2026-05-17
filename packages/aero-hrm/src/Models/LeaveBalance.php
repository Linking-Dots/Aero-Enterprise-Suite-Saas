<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends TenantModel
{
    /**
     * Explicit table name to avoid conflict with the legacy `leave_balances` table
     * created by 2025_12_02_120000_create_hrm_leave_tables.php.
     */
    protected $table = 'employee_leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'entitled',
        'used',
        'carried_forward',
        'encashed',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled' => 'decimal:2',
        'used' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'encashed' => 'decimal:2',
    ];

    protected $appends = ['remaining'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Remaining balance: entitled + carried_forward - used - encashed.
     */
    public function getRemainingAttribute(): float
    {
        return (float) $this->entitled
            + (float) $this->carried_forward
            - (float) $this->used
            - (float) $this->encashed;
    }
}
