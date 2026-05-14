<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborDeployment extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'daily_work_id',
        'work_layer_id',
        'skill_category',
        'trade',
        'head_count',
        'man_hours',
        'hours_worked_per_person',
        'overtime_hours',
        'start_chainage_m',
        'end_chainage_m',
        'work_location',
        'task_assigned',
        'productivity_rate',
        'productivity_unit',
        'contractor_name',
        'supervisor_name',
        'safety_briefing_done',
        'ppe_provided',
        'remarks',
    ];

    protected $casts = [
        'man_hours' => 'decimal:2',
        'hours_worked_per_person' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'productivity_rate' => 'decimal:2',
        'safety_briefing_done' => 'boolean',
        'ppe_provided' => 'boolean',
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
     * Calculate productivity per man-hour.
     */
    public function getProductivityPerManHourAttribute(): ?float
    {
        if (! $this->productivity_rate || $this->man_hours == 0) {
            return null;
        }

        return $this->productivity_rate / $this->man_hours;
    }

    /**
     * Get total overtime hours for the deployment.
     */
    public function getTotalOvertimeHoursAttribute(): float
    {
        return $this->overtime_hours * $this->head_count;
    }

    /**
     * Check safety compliance.
     */
    public function getSafetyCompliantAttribute(): bool
    {
        return $this->safety_briefing_done && $this->ppe_provided;
    }
}
