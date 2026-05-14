<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Career Progression Model
 *
 * Tracks an employee's progress along a career path.
 */
class EmployeeCareerProgression extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'career_path_id',
        'current_milestone_id',
        'target_milestone_id',
        'status',
        'progress_percentage',
        'started_at',
        'target_completion_date',
        'completed_requirements',
        'pending_requirements',
        'mentor_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'target_completion_date' => 'date',
            'completed_requirements' => 'array',
            'pending_requirements' => 'array',
            'progress_percentage' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function currentMilestone(): BelongsTo
    {
        return $this->belongsTo(CareerPathMilestone::class, 'current_milestone_id');
    }

    public function targetMilestone(): BelongsTo
    {
        return $this->belongsTo(CareerPathMilestone::class, 'target_milestone_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mentor_id');
    }
}
