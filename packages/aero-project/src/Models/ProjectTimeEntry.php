<?php

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeEntry extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'billable',
        'hourly_rate',
        'date',
        'approved',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'date' => 'date',
        'duration_minutes' => 'integer',
        'billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the project that owns the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task that owns the time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    /**
     * Get the user that owns the time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the time entry.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate billable amount.
     */
    public function getBillableAmountAttribute(): float
    {
        if (! $this->billable || ! $this->hourly_rate) {
            return 0;
        }

        return ($this->duration_minutes / 60) * $this->hourly_rate;
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }

    /**
     * Scope for billable entries.
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope for approved entries.
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }
}
