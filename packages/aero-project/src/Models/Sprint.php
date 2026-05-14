<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sprint Model
 *
 * Represents an iteration/sprint in Agile project management.
 * Sprints are time-boxed periods for completing planned work.
 */
class Sprint extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_sprints';

    protected $fillable = [
        'project_id',
        'name',
        'goal',
        'start_date',
        'end_date',
        'status',
        'capacity_points',
        'completed_points',
        'velocity',
        'retrospective',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity_points' => 'integer',
        'completed_points' => 'integer',
        'velocity' => 'decimal:2',
        'retrospective' => 'array',
    ];

    protected $attributes = [
        'status' => 'planned',
        'completed_points' => 0,
    ];

    /**
     * Get the project this sprint belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all tasks assigned to this sprint.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'sprint_id');
    }

    /**
     * Calculate sprint progress as percentage.
     */
    public function getProgressAttribute(): int
    {
        if ($this->capacity_points === 0 || $this->capacity_points === null) {
            return 0;
        }

        return (int) min(100, ($this->completed_points / $this->capacity_points) * 100);
    }

    /**
     * Check if sprint is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if sprint is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->end_date->isPast();
    }

    /**
     * Get remaining days in sprint.
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Calculate velocity based on completed work.
     */
    public function calculateVelocity(): float
    {
        if ($this->status !== 'completed') {
            return 0;
        }

        return (float) $this->completed_points;
    }

    /**
     * Scope for active sprints.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for sprints by project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
