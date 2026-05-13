<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceTrainingRecord extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'record_id',
        'employee_id',
        'training_title',
        'training_description',
        'training_type',
        'training_category',
        'provider',
        'instructor',
        'scheduled_date',
        'completion_date',
        'expiry_date',
        'status',
        'score',
        'passing_score',
        'duration_hours',
        'cost',
        'certificate_number',
        'certificate_url',
        'notes',
        'reminder_sent',
        'last_reminder_date',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'expiry_date' => 'date',
        'last_reminder_date' => 'date',
        'reminder_sent' => 'boolean',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'metadata' => 'json',
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXPIRED = 'expired';

    // Training type constants
    const TYPE_MANDATORY = 'mandatory';

    const TYPE_OPTIONAL = 'optional';

    const TYPE_REFRESHER = 'refresher';

    const TYPE_CERTIFICATION = 'certification';

    // Category constants
    const CATEGORY_SAFETY = 'safety';

    const CATEGORY_COMPLIANCE = 'compliance';

    const CATEGORY_REGULATORY = 'regulatory';

    const CATEGORY_QUALITY = 'quality';

    const CATEGORY_SECURITY = 'security';

    const CATEGORY_ENVIRONMENTAL = 'environmental';

    const CATEGORY_ETHICS = 'ethics';

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Check if training is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    /**
     * Check if training is expiring soon (within 30 days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date &&
            $this->expiry_date <= now()->addDays($days) &&
            $this->expiry_date > now();
    }

    /**
     * Check if training is overdue
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_date < now() &&
            $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if employee passed the training
     */
    public function isPassed(): bool
    {
        return $this->status === self::STATUS_COMPLETED &&
            $this->score >= $this->passing_score;
    }

    /**
     * Check if employee failed the training
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED ||
            ($this->status === self::STATUS_COMPLETED && $this->score < $this->passing_score);
    }

    /**
     * Calculate days until expiry
     */
    public function daysUntilExpiry(): int
    {
        if ($this->expiry_date) {
            return now()->diffInDays($this->expiry_date, false);
        }

        return 0;
    }

    /**
     * Calculate days since completion
     */
    public function daysSinceCompletion(): int
    {
        if ($this->completion_date) {
            return $this->completion_date->diffInDays(now());
        }

        return 0;
    }

    /**
     * Get completion percentage (for in-progress training)
     */
    public function getCompletionPercentage(): float
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100.0;
        }

        if ($this->status === self::STATUS_IN_PROGRESS) {
            // This could be calculated based on modules completed or time spent
            // For now, return a default value
            return 50.0;
        }

        return 0.0;
    }

    /**
     * Scope for mandatory training
     */
    public function scopeMandatory($query)
    {
        return $query->where('training_type', self::TYPE_MANDATORY);
    }

    /**
     * Scope for expired training
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope for completed training
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed training
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for overdue training
     */
    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope for passed training
     */
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->whereColumn('score', '>=', 'passing_score');
    }

    /**
     * Scope for certification training
     */
    public function scopeCertification($query)
    {
        return $query->where('training_type', self::TYPE_CERTIFICATION);
    }
}
