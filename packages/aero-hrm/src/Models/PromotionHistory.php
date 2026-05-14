<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Promotion History Model
 *
 * Tracks employee promotions with position and compensation changes.
 */
class PromotionHistory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_history';

    protected $fillable = [
        'employee_id',
        'promotion_type',
        'previous_designation_id',
        'new_designation_id',
        'previous_department_id',
        'new_department_id',
        'previous_grade_id',
        'new_grade_id',
        'previous_salary',
        'new_salary',
        'effective_date',
        'reason',
        'performance_rating',
        'approved_by',
        'approval_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'previous_salary' => 'decimal:2',
            'new_salary' => 'decimal:2',
            'effective_date' => 'date',
            'approval_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Promotion types.
     */
    public const TYPE_VERTICAL = 'vertical';

    public const TYPE_LATERAL = 'lateral';

    public const TYPE_DRY = 'dry'; // Title change without salary

    public const TYPE_GRADE = 'grade'; // Grade promotion

    /**
     * Status options.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'previous_designation_id');
    }

    public function newDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'new_designation_id');
    }

    public function previousDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'previous_department_id');
    }

    public function newDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    public function previousGrade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'previous_grade_id');
    }

    public function newGrade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'new_grade_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function compensationHistory(): HasOne
    {
        return $this->hasOne(CompensationHistory::class, 'promotion_id');
    }

    /**
     * Calculate salary increase percentage.
     */
    public function getSalaryIncreasePercentageAttribute(): float
    {
        if (! $this->previous_salary || $this->previous_salary == 0) {
            return 0;
        }

        return round((($this->new_salary - $this->previous_salary) / $this->previous_salary) * 100, 2);
    }

    /**
     * Scope for completed promotions.
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
}
