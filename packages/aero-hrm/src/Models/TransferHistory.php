<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transfer History Model
 *
 * Tracks employee transfers between departments, locations, or branches.
 */
class TransferHistory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'transfer_history';

    protected $fillable = [
        'employee_id',
        'transfer_type',
        'from_department_id',
        'to_department_id',
        'from_location',
        'to_location',
        'from_branch',
        'to_branch',
        'from_manager_id',
        'to_manager_id',
        'reason',
        'effective_date',
        'end_date',
        'is_temporary',
        'requested_by',
        'approved_by',
        'approval_date',
        'status',
        'notes',
        'relocation_support',
        'relocation_amount',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'end_date' => 'date',
            'approval_date' => 'date',
            'is_temporary' => 'boolean',
            'relocation_support' => 'boolean',
            'relocation_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Transfer types.
     */
    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_LOCATION = 'location';

    public const TYPE_BRANCH = 'branch';

    public const TYPE_INTERNATIONAL = 'international';

    public const TYPE_PROJECT = 'project';

    /**
     * Status options.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_manager_id');
    }

    public function toManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_manager_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Check if transfer is active.
     */
    public function getIsActiveAttribute(): bool
    {
        if ($this->is_temporary && $this->end_date) {
            return now()->between($this->effective_date, $this->end_date);
        }

        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Scope for completed transfers.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for temporary transfers.
     */
    public function scopeTemporary($query)
    {
        return $query->where('is_temporary', true);
    }
}
