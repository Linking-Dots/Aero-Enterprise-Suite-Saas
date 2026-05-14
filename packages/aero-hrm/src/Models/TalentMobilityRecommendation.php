<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Talent Mobility Recommendation Model
 *
 * AI-generated recommendations for internal talent movement.
 * Supports career development and retention strategies.
 */
class TalentMobilityRecommendation extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'recommendation_type',
        'target_department_id',
        'target_designation_id',
        'target_role_name',
        'match_score',
        'matching_skills',
        'skill_gaps',
        'development_path',
        'rationale',
        'estimated_readiness_months',
        'status',
        'created_by',
        'actioned_by',
        'actioned_at',
        'action_notes',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'matching_skills' => 'array',
            'skill_gaps' => 'array',
            'development_path' => 'array',
            'match_score' => 'decimal:2',
            'actioned_at' => 'datetime',
            'valid_until' => 'date',
        ];
    }

    public const RECOMMENDATION_TYPES = [
        'promotion' => 'Ready for Promotion',
        'lateral_move' => 'Lateral Move Opportunity',
        'skill_development' => 'Skill Development Needed',
        'mentorship' => 'Mentorship Opportunity',
        'project_assignment' => 'Project Assignment',
        'leadership_track' => 'Leadership Track Candidate',
        'retention_action' => 'Retention Action Needed',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function targetDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'target_designation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'actioned_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->valid_until === null || $this->valid_until->isFuture());
    }

    public function isHighMatch(): bool
    {
        return $this->match_score >= 80;
    }

    public function getSkillGapCount(): int
    {
        return count($this->skill_gaps ?? []);
    }
}
