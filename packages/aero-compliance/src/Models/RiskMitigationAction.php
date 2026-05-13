<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMitigationAction extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'risk_assessment_id',
        'action_id',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'due_date',
        'completion_date',
        'progress_percentage',
        'cost_estimate',
        'actual_cost',
        'effectiveness_rating',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
        'progress_percentage' => 'integer',
        'cost_estimate' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'effectiveness_rating' => 'decimal:2',
        'metadata' => 'json',
    ];

    // Status constants
    const STATUS_PLANNED = 'planned';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_OVERDUE = 'overdue';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    /**
     * Get the risk assessment this action belongs to
     */
    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class);
    }

    /**
     * Get the user assigned to this action
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if the action is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Calculate days until due date
     */
    public function daysUntilDue(): int
    {
        if ($this->due_date) {
            return now()->diffInDays($this->due_date, false);
        }

        return 0;
    }

    /**
     * Scope for overdue actions
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Scope for high priority actions
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Scope for active actions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNED, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope for completed actions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
