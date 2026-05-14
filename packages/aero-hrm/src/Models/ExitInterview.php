<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Exit Interview Model
 *
 * Captures exit interview data for departing employees.
 */
class ExitInterview extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'offboarding_id',
        'interview_date',
        'interviewer_id',
        'departure_reason',
        'departure_reason_details',
        'would_recommend',
        'would_return',
        'overall_satisfaction',
        'management_rating',
        'work_environment_rating',
        'compensation_rating',
        'career_growth_rating',
        'work_life_balance_rating',
        'team_collaboration_rating',
        'liked_most',
        'liked_least',
        'improvement_suggestions',
        'new_employer',
        'new_position',
        'new_salary_range',
        'reason_for_new_job',
        'exit_feedback_summary',
        'confidential_notes',
        'is_confidential',
        'status',
        'follow_up_required',
        'follow_up_notes',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'would_recommend' => 'boolean',
            'would_return' => 'boolean',
            'overall_satisfaction' => 'integer',
            'management_rating' => 'integer',
            'work_environment_rating' => 'integer',
            'compensation_rating' => 'integer',
            'career_growth_rating' => 'integer',
            'work_life_balance_rating' => 'integer',
            'team_collaboration_rating' => 'integer',
            'is_confidential' => 'boolean',
            'follow_up_required' => 'boolean',
            'improvement_suggestions' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Departure reasons.
     */
    public const REASON_BETTER_OPPORTUNITY = 'better_opportunity';

    public const REASON_COMPENSATION = 'compensation';

    public const REASON_CAREER_GROWTH = 'career_growth';

    public const REASON_MANAGEMENT = 'management';

    public const REASON_WORK_LIFE_BALANCE = 'work_life_balance';

    public const REASON_RELOCATION = 'relocation';

    public const REASON_PERSONAL = 'personal';

    public const REASON_RETIREMENT = 'retirement';

    public const REASON_HEALTH = 'health';

    public const REASON_LAYOFF = 'layoff';

    public const REASON_TERMINATION = 'termination';

    public const REASON_OTHER = 'other';

    /**
     * Status options.
     */
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function offboarding(): BelongsTo
    {
        return $this->belongsTo(Offboarding::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'interviewer_id');
    }

    /**
     * Calculate average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        $ratings = [
            $this->management_rating,
            $this->work_environment_rating,
            $this->compensation_rating,
            $this->career_growth_rating,
            $this->work_life_balance_rating,
            $this->team_collaboration_rating,
        ];

        $validRatings = array_filter($ratings, fn ($r) => $r !== null);

        if (empty($validRatings)) {
            return 0;
        }

        return round(array_sum($validRatings) / count($validRatings), 1);
    }

    /**
     * Scope for completed interviews.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for requiring follow-up.
     */
    public function scopeRequiringFollowUp($query)
    {
        return $query->where('follow_up_required', true);
    }

    /**
     * Get departure reason display.
     */
    public function getDepartureReasonDisplayAttribute(): string
    {
        return match ($this->departure_reason) {
            self::REASON_BETTER_OPPORTUNITY => 'Better Career Opportunity',
            self::REASON_COMPENSATION => 'Compensation & Benefits',
            self::REASON_CAREER_GROWTH => 'Limited Career Growth',
            self::REASON_MANAGEMENT => 'Management Issues',
            self::REASON_WORK_LIFE_BALANCE => 'Work-Life Balance',
            self::REASON_RELOCATION => 'Relocation',
            self::REASON_PERSONAL => 'Personal Reasons',
            self::REASON_RETIREMENT => 'Retirement',
            self::REASON_HEALTH => 'Health Reasons',
            self::REASON_LAYOFF => 'Layoff/Redundancy',
            self::REASON_TERMINATION => 'Termination',
            self::REASON_OTHER => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->departure_reason ?? '')),
        };
    }
}
