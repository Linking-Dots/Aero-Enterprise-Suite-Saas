<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectIssue extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'reported_by',
        'assigned_to',
        'reported_date',
        'resolution_date',
        'resolution',
        'tags',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reported_date' => 'date',
        'resolution_date' => 'date',
        'tags' => 'array',
    ];

    /**
     * Get the project that owns the issue.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who reported the issue.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user assigned to the issue.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the tasks related to this issue.
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_issues', 'issue_id', 'task_id')
            ->withTimestamps();
    }

    /**
     * Get comments for this issue.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }

    /**
     * Get attachments for this issue (polymorphic).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ProjectAttachment::class, 'attachable');
    }

    /**
     * Get watchers for this issue.
     */
    public function watchers(): MorphMany
    {
        return $this->morphMany(ProjectWatcher::class, 'watchable');
    }

    /**
     * Get issue status text.
     */
    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'reopened' => 'Reopened',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Get issue priority text.
     */
    public function getPriorityTextAttribute(): string
    {
        $priorityMap = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];

        return $priorityMap[$this->priority] ?? $this->priority;
    }

    /**
     * Get issue type text.
     */
    public function getTypeTextAttribute(): string
    {
        $typeMap = [
            'bug' => 'Bug',
            'feature' => 'Feature Request',
            'task' => 'Task',
            'improvement' => 'Improvement',
            'question' => 'Question',
        ];

        return $typeMap[$this->type] ?? $this->type;
    }
}
