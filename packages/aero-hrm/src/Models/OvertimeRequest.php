<?php

namespace Aero\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Overtime Request Model
 *
 * Manages overtime requests and approvals.
 */
class OvertimeRequest extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'request_date',
        'start_time',
        'end_time',
        'planned_hours',
        'actual_hours',
        'overtime_type',
        'reason',
        'project_task_reference',
        'rate_multiplier',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'planned_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'rate_multiplier' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Calculate overtime pay
     */
    public function calculateOvertimePay(float $hourlyRate): float
    {
        $hours = $this->actual_hours ?? $this->planned_hours;

        return $hours * $hourlyRate * $this->rate_multiplier;
    }
}
