<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Project Label Model
 *
 * Represents labels/tags for categorizing tasks within a project.
 */
class ProjectLabel extends TenantModel
{
    use HasFactory;

    protected $table = 'project_labels';

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'description',
    ];

    protected $attributes = [
        'color' => '#6366f1',
    ];

    /**
     * Predefined color options for labels.
     */
    public const COLORS = [
        'primary' => '#6366f1',
        'secondary' => '#8b5cf6',
        'success' => '#22c55e',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'info' => '#0ea5e9',
        'pink' => '#ec4899',
        'teal' => '#14b8a6',
        'orange' => '#f97316',
        'gray' => '#6b7280',
    ];

    /**
     * Get the project that owns the label.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get tasks with this label.
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_labels', 'label_id', 'task_id')
            ->withTimestamps();
    }

    /**
     * Get count of tasks with this label.
     */
    public function getTaskCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get contrast text color (white or black) based on background color.
     */
    public function getTextColorAttribute(): string
    {
        $hex = ltrim($this->color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Scope to find by name (case-insensitive).
     */
    public function scopeNamed($query, string $name)
    {
        return $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
    }

    /**
     * Get available color options.
     */
    public static function getColorOptions(): array
    {
        return self::COLORS;
    }
}
