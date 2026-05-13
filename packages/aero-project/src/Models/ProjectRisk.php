<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Project Risk Model
 *
 * Represents risks and issues that may affect project delivery.
 * Uses a unified table for both risks (potential) and issues (occurred).
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 */
class ProjectRisk extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_risks';

    protected $fillable = [
        'project_id',
        'type',
        'title',
        'description',
        'status',
        'probability',
        'impact',
        'mitigation_plan',
        'contingency_plan',
        'owner_id',
        'reported_by',
        'identified_date',
        'target_resolution_date',
        'resolved_date',
        'related_task_id',
    ];

    protected $casts = [
        'identified_date' => 'date',
        'target_resolution_date' => 'date',
        'resolved_date' => 'date',
    ];

    protected $attributes = [
        'type' => 'risk',
        'status' => 'open',
        'probability' => 'medium',
        'impact' => 'medium',
    ];

    /**
     * Type constants.
     */
    public const TYPE_RISK = 'risk';

    public const TYPE_ISSUE = 'issue';

    /**
     * Status constants.
     */
    public const STATUS_OPEN = 'open';

    public const STATUS_MITIGATING = 'mitigating';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ACCEPTED = 'accepted';

    /**
     * Probability/Impact levels.
     */
    public const LEVEL_LOW = 'low';

    public const LEVEL_MEDIUM = 'medium';

    public const LEVEL_HIGH = 'high';

    public const LEVEL_CRITICAL = 'critical';

    /**
     * Get the project that owns the risk.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the related task (if any).
     */
    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'related_task_id');
    }

    /**
     * Get comments for this risk.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }

    /**
     * Get attachments for this risk.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ProjectAttachment::class, 'attachable');
    }

    /**
     * Get watchers for this risk.
     */
    public function watchers(): MorphMany
    {
        return $this->morphMany(ProjectWatcher::class, 'watchable');
    }

    /**
     * Calculate the risk score (probability × impact).
     */
    public function getRiskScoreAttribute(): int
    {
        $levels = [
            self::LEVEL_LOW => 1,
            self::LEVEL_MEDIUM => 2,
            self::LEVEL_HIGH => 3,
            self::LEVEL_CRITICAL => 4,
        ];

        $prob = $levels[$this->probability] ?? 2;
        $imp = $levels[$this->impact] ?? 2;

        return $prob * $imp;
    }

    /**
     * Get risk severity label based on score.
     */
    public function getSeverityLabelAttribute(): string
    {
        $score = $this->risk_score;

        if ($score >= 12) {
            return 'Critical';
        }
        if ($score >= 8) {
            return 'High';
        }
        if ($score >= 4) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Get severity color for UI.
     */
    public function getSeverityColorAttribute(): string
    {
        $score = $this->risk_score;

        if ($score >= 12) {
            return 'danger';
        }
        if ($score >= 8) {
            return 'warning';
        }
        if ($score >= 4) {
            return 'primary';
        }

        return 'success';
    }

    /**
     * Check if risk is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->target_resolution_date || $this->resolved_date) {
            return false;
        }

        return $this->target_resolution_date->isPast();
    }

    /**
     * Check if risk is a true risk (not an issue).
     */
    public function isRisk(): bool
    {
        return $this->type === self::TYPE_RISK;
    }

    /**
     * Check if this is an issue (occurred risk).
     */
    public function isIssue(): bool
    {
        return $this->type === self::TYPE_ISSUE;
    }

    /**
     * Convert risk to issue (when risk materializes).
     */
    public function convertToIssue(): self
    {
        $this->update(['type' => self::TYPE_ISSUE]);

        return $this;
    }

    /**
     * Scope for risks only.
     */
    public function scopeRisks($query)
    {
        return $query->where('type', self::TYPE_RISK);
    }

    /**
     * Scope for issues only.
     */
    public function scopeIssues($query)
    {
        return $query->where('type', self::TYPE_ISSUE);
    }

    /**
     * Scope for open items.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for high priority (score >= 8).
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('probability', [self::LEVEL_HIGH, self::LEVEL_CRITICAL])
            ->orWhereIn('impact', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope for overdue items.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('resolved_date')
            ->whereNotNull('target_resolution_date')
            ->where('target_resolution_date', '<', now());
    }

    /**
     * Get type options for dropdowns.
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_RISK => 'Risk',
            self::TYPE_ISSUE => 'Issue',
        ];
    }

    /**
     * Get status options for dropdowns.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_MITIGATING => 'Mitigating',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ACCEPTED => 'Accepted',
        ];
    }

    /**
     * Get probability options for dropdowns.
     */
    public static function getProbabilityOptions(): array
    {
        return [
            self::LEVEL_LOW => 'Low',
            self::LEVEL_MEDIUM => 'Medium',
            self::LEVEL_HIGH => 'High',
            self::LEVEL_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get impact options for dropdowns.
     */
    public static function getImpactOptions(): array
    {
        return self::getProbabilityOptions(); // Same scale
    }
}
