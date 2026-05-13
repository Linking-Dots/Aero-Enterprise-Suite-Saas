<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Project Sprint Model
 *
 * Represents sprints/iterations for Agile project methodology.
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 */
class ProjectSprint extends TenantModel
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
     * Status constants.
     */
    public const STATUS_PLANNED = 'planned';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the project that owns the sprint.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get tasks assigned to this sprint.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'sprint_id');
    }

    /**
     * Calculate sprint progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->capacity_points <= 0) {
            return 0;
        }

        return min(100, ($this->completed_points / $this->capacity_points) * 100);
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->capacity_points - $this->completed_points);
    }

    /**
     * Get sprint duration in days.
     */
    public function getDurationDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get days remaining in sprint.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Check if sprint is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if sprint is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if sprint is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->end_date?->isPast() ?? false;
    }

    /**
     * Start the sprint.
     */
    public function start(): self
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'start_date' => $this->start_date ?? now(),
        ]);

        return $this;
    }

    /**
     * Complete the sprint.
     */
    public function complete(array $retrospective = []): self
    {
        // Calculate velocity
        $velocity = $this->completed_points;
        if ($this->duration_days > 0) {
            $velocity = $this->completed_points / ($this->duration_days / 14); // Normalize to 2-week sprint
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'velocity' => $velocity,
            'retrospective' => array_merge($this->retrospective ?? [], $retrospective),
        ]);

        return $this;
    }

    /**
     * Cancel the sprint.
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        return $this;
    }

    /**
     * Add retrospective note.
     */
    public function addRetrospectiveNote(string $category, string $note): self
    {
        $retrospective = $this->retrospective ?? [];
        $retrospective[$category] = $retrospective[$category] ?? [];
        $retrospective[$category][] = $note;

        $this->update(['retrospective' => $retrospective]);

        return $this;
    }

    /**
     * Calculate and update velocity based on completed points.
     */
    public function recalculateVelocity(): void
    {
        $completedTasks = $this->tasks()->where('status', 'completed')->get();
        $completedPoints = $completedTasks->sum('story_points');

        $this->update(['completed_points' => $completedPoints]);
    }

    /**
     * Scope for active sprints.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for planned sprints.
     */
    public function scopePlanned($query)
    {
        return $query->where('status', self::STATUS_PLANNED);
    }

    /**
     * Scope for completed sprints.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for current or upcoming sprints.
     */
    public function scopeCurrentOrUpcoming($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNED, self::STATUS_ACTIVE])
            ->where('end_date', '>=', now());
    }

    /**
     * Get status options for dropdowns.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PLANNED => 'default',
            self::STATUS_ACTIVE => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'default',
        };
    }
}
