<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Overtime Record Model
 *
 * Tracks employee overtime hours, approvals, and compensation.
 */
class OvertimeRecord extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'overtime_type',
        'rate_multiplier',
        'reason',
        'project_id',
        'task_description',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'compensated',
        'compensation_type',
        'compensation_amount',
        'payroll_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'hours' => 'decimal:2',
            'rate_multiplier' => 'decimal:2',
            'approved_at' => 'datetime',
            'compensated' => 'boolean',
            'compensation_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Overtime types.
     */
    public const TYPE_WEEKDAY = 'weekday';

    public const TYPE_WEEKEND = 'weekend';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_NIGHT = 'night';

    public const TYPE_EMERGENCY = 'emergency';

    /**
     * Status options.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Compensation types.
     */
    public const COMP_MONETARY = 'monetary';

    public const COMP_TIME_OFF = 'time_off';

    public const COMP_BOTH = 'both';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Calculate overtime amount.
     */
    public function calculateAmount(float $hourlyRate): float
    {
        return $this->hours * $hourlyRate * $this->rate_multiplier;
    }

    /**
     * Get default rate multiplier for overtime type.
     */
    public static function getDefaultMultiplier(string $type): float
    {
        return match ($type) {
            self::TYPE_WEEKDAY => 1.5,
            self::TYPE_WEEKEND => 2.0,
            self::TYPE_HOLIDAY => 2.5,
            self::TYPE_NIGHT => 1.75,
            self::TYPE_EMERGENCY => 2.0,
            default => 1.5,
        };
    }

    /**
     * Scope for pending approval.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved records.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for uncompensated records.
     */
    public function scopeUncompensated($query)
    {
        return $query->where('compensated', false)->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
