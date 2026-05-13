<?php

namespace Aero\Crm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealActivity extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'deal_id',
        'user_id',
        'type',
        'subject',
        'description',
        'outcome',
        'scheduled_at',
        'completed_at',
        'duration_minutes',
        'from_stage_id',
        'to_stage_id',
        'old_value',
        'new_value',
        'metadata',
        'email_message_id',
        'email_opened',
        'email_opened_at',
        'email_clicked',
        'email_clicked_at',
        'is_completed',
        'is_pinned',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'metadata' => 'array',
        'email_opened' => 'boolean',
        'email_opened_at' => 'datetime',
        'email_clicked' => 'boolean',
        'email_clicked_at' => 'datetime',
        'is_completed' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    /**
     * Activity types
     */
    const TYPE_NOTE = 'note';

    const TYPE_CALL = 'call';

    const TYPE_EMAIL = 'email';

    const TYPE_MEETING = 'meeting';

    const TYPE_TASK = 'task';

    const TYPE_STAGE_CHANGE = 'stage_change';

    const TYPE_VALUE_CHANGE = 'value_change';

    const TYPE_ASSIGNMENT_CHANGE = 'assignment_change';

    const TYPE_SYSTEM = 'system';

    /**
     * Get the deal
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the user who created the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the from stage (for stage changes)
     */
    public function fromStage()
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    /**
     * Get the to stage (for stage changes)
     */
    public function toStage()
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
    }

    /**
     * Scope for scheduled activities
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('is_completed', false);
    }

    /**
     * Scope for overdue activities
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->where('is_completed', false);
    }

    /**
     * Scope for pinned activities
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?string $outcome = null): bool
    {
        return $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'outcome' => $outcome,
        ]);
    }
}
