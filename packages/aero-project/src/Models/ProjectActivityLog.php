<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Project Activity Log Model
 *
 * Tracks all changes and activities within a project for audit trail.
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 */
class ProjectActivityLog extends TenantModel
{
    protected $table = 'project_activity_log';

    protected $fillable = [
        'project_id',
        'subject_type',
        'subject_id',
        'causer_id',
        'event',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Event type constants.
     */
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_UNASSIGNED = 'unassigned';

    public const EVENT_COMMENT_ADDED = 'comment_added';

    public const EVENT_ATTACHMENT_ADDED = 'attachment_added';

    public const EVENT_ATTACHMENT_REMOVED = 'attachment_removed';

    public const EVENT_MEMBER_ADDED = 'member_added';

    public const EVENT_MEMBER_REMOVED = 'member_removed';

    public const EVENT_MILESTONE_REACHED = 'milestone_reached';

    public const EVENT_SPRINT_STARTED = 'sprint_started';

    public const EVENT_SPRINT_COMPLETED = 'sprint_completed';

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the subject entity (task, milestone, risk, etc.).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get old values from properties.
     */
    public function getOldAttribute(): ?array
    {
        return $this->properties['old'] ?? null;
    }

    /**
     * Get new values from properties.
     */
    public function getNewAttribute(): ?array
    {
        return $this->properties['new'] ?? null;
    }

    /**
     * Get changed fields.
     */
    public function getChangedFieldsAttribute(): array
    {
        if (! $this->old || ! $this->new) {
            return [];
        }

        return array_keys(array_diff_assoc($this->new, $this->old));
    }

    /**
     * Get human-readable description of the activity.
     */
    public function getDescriptionAttribute(): string
    {
        $subjectType = class_basename($this->subject_type);

        return match ($this->event) {
            self::EVENT_CREATED => "Created {$subjectType}",
            self::EVENT_UPDATED => "Updated {$subjectType}",
            self::EVENT_DELETED => "Deleted {$subjectType}",
            self::EVENT_STATUS_CHANGED => $this->getStatusChangeDescription(),
            self::EVENT_ASSIGNED => "Assigned {$subjectType}",
            self::EVENT_UNASSIGNED => "Unassigned {$subjectType}",
            self::EVENT_COMMENT_ADDED => "Added comment on {$subjectType}",
            self::EVENT_ATTACHMENT_ADDED => "Added attachment to {$subjectType}",
            self::EVENT_ATTACHMENT_REMOVED => "Removed attachment from {$subjectType}",
            self::EVENT_MEMBER_ADDED => 'Added team member',
            self::EVENT_MEMBER_REMOVED => 'Removed team member',
            self::EVENT_MILESTONE_REACHED => 'Reached milestone',
            self::EVENT_SPRINT_STARTED => 'Started sprint',
            self::EVENT_SPRINT_COMPLETED => 'Completed sprint',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }

    /**
     * Get status change description.
     */
    protected function getStatusChangeDescription(): string
    {
        $old = $this->properties['old']['status'] ?? 'unknown';
        $new = $this->properties['new']['status'] ?? 'unknown';

        return "Changed status from '{$old}' to '{$new}'";
    }

    /**
     * Get event icon for UI.
     */
    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'plus-circle',
            self::EVENT_UPDATED => 'pencil',
            self::EVENT_DELETED => 'trash',
            self::EVENT_STATUS_CHANGED => 'arrow-path',
            self::EVENT_ASSIGNED => 'user-plus',
            self::EVENT_UNASSIGNED => 'user-minus',
            self::EVENT_COMMENT_ADDED => 'chat-bubble-left',
            self::EVENT_ATTACHMENT_ADDED => 'paper-clip',
            self::EVENT_ATTACHMENT_REMOVED => 'paper-clip',
            self::EVENT_MEMBER_ADDED => 'users',
            self::EVENT_MEMBER_REMOVED => 'users',
            self::EVENT_MILESTONE_REACHED => 'flag',
            self::EVENT_SPRINT_STARTED => 'play',
            self::EVENT_SPRINT_COMPLETED => 'check-circle',
            default => 'information-circle',
        };
    }

    /**
     * Get event color for UI.
     */
    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED, self::EVENT_MEMBER_ADDED => 'success',
            self::EVENT_DELETED, self::EVENT_MEMBER_REMOVED, self::EVENT_ATTACHMENT_REMOVED => 'danger',
            self::EVENT_STATUS_CHANGED, self::EVENT_ASSIGNED => 'primary',
            self::EVENT_MILESTONE_REACHED, self::EVENT_SPRINT_COMPLETED => 'warning',
            default => 'default',
        };
    }

    /**
     * Log an activity.
     */
    public static function log(
        int $projectId,
        string $event,
        Model $subject,
        ?int $causerId = null,
        array $properties = []
    ): self {
        return static::create([
            'project_id' => $projectId,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'causer_id' => $causerId ?? auth()->id(),
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    /**
     * Log a model creation.
     */
    public static function logCreated(int $projectId, Model $subject, ?int $causerId = null): self
    {
        return static::log($projectId, self::EVENT_CREATED, $subject, $causerId, [
            'new' => $subject->toArray(),
        ]);
    }

    /**
     * Log a model update.
     */
    public static function logUpdated(int $projectId, Model $subject, array $oldValues, ?int $causerId = null): self
    {
        return static::log($projectId, self::EVENT_UPDATED, $subject, $causerId, [
            'old' => $oldValues,
            'new' => $subject->toArray(),
        ]);
    }

    /**
     * Log a status change.
     */
    public static function logStatusChanged(
        int $projectId,
        Model $subject,
        string $oldStatus,
        string $newStatus,
        ?int $causerId = null
    ): self {
        return static::log($projectId, self::EVENT_STATUS_CHANGED, $subject, $causerId, [
            'old' => ['status' => $oldStatus],
            'new' => ['status' => $newStatus],
        ]);
    }

    /**
     * Scope for specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope for specific subject.
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope for specific event type.
     */
    public function scopeOfEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for recent activities.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by causer.
     */
    public function scopeByCauser($query, int $userId)
    {
        return $query->where('causer_id', $userId);
    }
}
