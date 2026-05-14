<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Career Path Milestone Model
 *
 * Defines milestones/steps within a career path.
 */
class CareerPathMilestone extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'career_path_id',
        'designation_id',
        'sequence',
        'title',
        'description',
        'min_experience_months',
        'required_skills',
        'required_certifications',
        'required_training',
        'salary_range_min',
        'salary_range_max',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'required_certifications' => 'array',
            'required_training' => 'array',
            'salary_range_min' => 'decimal:2',
            'salary_range_max' => 'decimal:2',
        ];
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
