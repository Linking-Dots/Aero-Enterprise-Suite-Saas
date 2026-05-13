<?php

namespace Aero\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pulse Survey Model
 *
 * Quick, frequent check-in surveys for employee engagement.
 */
class PulseSurvey extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'questions',
        'frequency',
        'target_departments',
        'target_designations',
        'is_anonymous',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'response_count',
        'completion_rate',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'target_departments' => 'array',
            'target_designations' => 'array',
            'is_anonymous' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'completion_rate' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Frequency options.
     */
    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_BIWEEKLY = 'biweekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_ONE_TIME = 'one_time';

    /**
     * Status options.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PulseSurveyResponse::class);
    }

    /**
     * Scope for active surveys.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Check if employee is eligible for this survey.
     */
    public function isEmployeeEligible(Employee $employee): bool
    {
        // Check department filter
        if (! empty($this->target_departments)) {
            if (! in_array($employee->department_id, $this->target_departments)) {
                return false;
            }
        }

        // Check designation filter
        if (! empty($this->target_designations)) {
            if (! in_array($employee->designation_id, $this->target_designations)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update completion statistics.
     */
    public function updateStatistics(): void
    {
        $this->response_count = $this->responses()->count();

        // Calculate completion rate based on eligible employees
        $eligibleCount = $this->getEligibleEmployeesCount();
        if ($eligibleCount > 0) {
            $this->completion_rate = ($this->response_count / $eligibleCount) * 100;
        }

        $this->save();
    }

    /**
     * Get count of eligible employees.
     */
    public function getEligibleEmployeesCount(): int
    {
        $query = Employee::where('status', 'active');

        if (! empty($this->target_departments)) {
            $query->whereIn('department_id', $this->target_departments);
        }

        if (! empty($this->target_designations)) {
            $query->whereIn('designation_id', $this->target_designations);
        }

        return $query->count();
    }
}
