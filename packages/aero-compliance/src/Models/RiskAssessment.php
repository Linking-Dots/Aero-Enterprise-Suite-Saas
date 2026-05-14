<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskAssessment extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'risk_id',
        'name',
        'description',
        'category',
        'likelihood',
        'impact',
        'risk_score',
        'risk_level',
        'status',
        'assessment_date',
        'next_review_date',
        'owner_id',
        'reviewer_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'next_review_date' => 'date',
        'metadata' => 'json',
        'likelihood' => 'integer',
        'impact' => 'integer',
        'risk_score' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_ARCHIVED = 'archived';

    // Risk level constants
    const LEVEL_LOW = 'low';

    const LEVEL_MEDIUM = 'medium';

    const LEVEL_HIGH = 'high';

    const LEVEL_CRITICAL = 'critical';

    // Category constants
    const CATEGORY_OPERATIONAL = 'operational';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_REGULATORY = 'regulatory';

    const CATEGORY_STRATEGIC = 'strategic';

    const CATEGORY_TECHNOLOGY = 'technology';

    /**
     * Get the owner of the risk assessment
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the reviewer of the risk assessment
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the mitigation actions for this risk
     */
    public function mitigationActions(): HasMany
    {
        return $this->hasMany(RiskMitigationAction::class);
    }

    /**
     * Calculate risk score based on likelihood and impact
     */
    public function calculateRiskScore(): float
    {
        return $this->likelihood * $this->impact;
    }

    /**
     * Determine risk level based on score
     */
    public function determineRiskLevel(): string
    {
        $score = $this->calculateRiskScore();

        if ($score >= 20) {
            return self::LEVEL_CRITICAL;
        }
        if ($score >= 15) {
            return self::LEVEL_HIGH;
        }
        if ($score >= 10) {
            return self::LEVEL_MEDIUM;
        }

        return self::LEVEL_LOW;
    }

    /**
     * Scope for active assessments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED]);
    }

    /**
     * Scope for high risk assessments
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope for overdue reviews
     */
    public function scopeOverdueReview($query)
    {
        return $query->where('next_review_date', '<', now());
    }
}
