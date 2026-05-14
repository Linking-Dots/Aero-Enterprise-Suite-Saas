<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Workforce Plan Position Model
 *
 * Individual position requirements within a workforce plan.
 */
class WorkforcePlanPosition extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'workforce_plan_id',
        'designation_id',
        'department_id',
        'position_type',
        'current_count',
        'planned_count',
        'hiring_priority',
        'required_by_date',
        'estimated_salary',
        'skills_required',
        'justification',
        'status',
        'linked_job_id',
    ];

    protected function casts(): array
    {
        return [
            'required_by_date' => 'date',
            'estimated_salary' => 'decimal:2',
            'skills_required' => 'array',
        ];
    }

    public function workforcePlan(): BelongsTo
    {
        return $this->belongsTo(WorkforcePlan::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function linkedJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'linked_job_id');
    }

    /**
     * Get headcount gap
     */
    public function getGap(): int
    {
        return $this->planned_count - $this->current_count;
    }
}
