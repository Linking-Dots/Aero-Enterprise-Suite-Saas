<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Succession Candidate Model
 *
 * Links employees to succession plans with readiness assessments.
 */
class SuccessionCandidate extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'succession_plan_id',
        'employee_id',
        'readiness_level',
        'development_priority',
        'strengths',
        'development_areas',
        'development_plan',
        'mentor_id',
        'assessment_date',
        'assessment_score',
        'assessment_notes',
        'status',
        'nominated_by',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'development_areas' => 'array',
            'development_plan' => 'array',
            'assessment_date' => 'date',
            'assessment_score' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Readiness levels.
     */
    public const READINESS_READY_NOW = 'ready_now';

    public const READINESS_READY_1_YEAR = 'ready_1_year';

    public const READINESS_READY_2_YEARS = 'ready_2_years';

    public const READINESS_READY_3_PLUS = 'ready_3_plus';

    public const READINESS_NOT_READY = 'not_ready';

    /**
     * Development priorities.
     */
    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_LOW = 'low';

    /**
     * Candidate statuses.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_IN_DEVELOPMENT = 'in_development';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_PROMOTED = 'promoted';

    public const STATUS_REMOVED = 'removed';

    public function successionPlan(): BelongsTo
    {
        return $this->belongsTo(SuccessionPlan::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mentor_id');
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'nominated_by');
    }

    /**
     * Scope for ready-now candidates.
     */
    public function scopeReadyNow($query)
    {
        return $query->where('readiness_level', self::READINESS_READY_NOW);
    }

    /**
     * Scope for active candidates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get readiness level display text.
     */
    public function getReadinessDisplayAttribute(): string
    {
        return match ($this->readiness_level) {
            self::READINESS_READY_NOW => 'Ready Now',
            self::READINESS_READY_1_YEAR => 'Ready in 1 Year',
            self::READINESS_READY_2_YEARS => 'Ready in 2 Years',
            self::READINESS_READY_3_PLUS => 'Ready in 3+ Years',
            self::READINESS_NOT_READY => 'Not Ready',
            default => 'Unknown',
        };
    }
}
