<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'milestone_id',
        'parent_task_id',
        'name',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'assigned_to',
        'progress',
        'tags',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
        'progress' => 'integer',
        'tags' => 'array',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the milestone that owns the task.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class);
    }

    /**
     * Get the parent task.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    /**
     * Get the subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the comments for the task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ProjectTaskComment::class, 'task_id');
    }

    /**
     * Get the attachments for the task.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectTaskAttachment::class, 'task_id');
    }

    /**
     * Get the issues related to this task.
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(ProjectIssue::class, 'project_task_issues', 'task_id', 'issue_id')
            ->withTimestamps();
    }

    /**
     * Get task status text.
     */
    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            'completed' => 'Completed',
            'blocked' => 'Blocked',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Get task priority text.
     */
    public function getPriorityTextAttribute(): string
    {
        $priorityMap = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];

        return $priorityMap[$this->priority] ?? $this->priority;
    }
}
