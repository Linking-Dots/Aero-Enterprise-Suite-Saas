<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteInstruction extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'daily_work_id',
        'work_layer_id',
        'instruction_number',
        'title',
        'description',
        'issued_by',
        'issued_date',
        'target_completion_date',
        'priority',
        'category',
        'start_chainage_m',
        'end_chainage_m',
        'work_location',
        'status',
        'contractor_response',
        'response_date',
        'actual_completion_date',
        'cost_impact',
        'time_impact_days',
        'remarks',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'target_completion_date' => 'date',
        'response_date' => 'date',
        'actual_completion_date' => 'date',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'cost_impact' => 'decimal:2',
        'time_impact_days' => 'integer',
    ];

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    /**
     * Check if instruction is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->target_completion_date || $this->status === 'completed') {
            return false;
        }

        return now()->isAfter($this->target_completion_date);
    }

    /**
     * Get days until/past deadline.
     */
    public function getDaysToDeadlineAttribute(): ?int
    {
        if (! $this->target_completion_date) {
            return null;
        }

        $diff = now()->diffInDays($this->target_completion_date, false);

        return (int) $diff;
    }

    /**
     * Check if response is pending.
     */
    public function getResponsePendingAttribute(): bool
    {
        return in_array($this->status, ['issued', 'acknowledged']) && ! $this->response_date;
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'issued' => 'warning',
            'acknowledged' => 'primary',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'default',
        };
    }

    /**
     * Get priority color for UI.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'primary',
            'low' => 'default',
            default => 'default',
        };
    }
}
