<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use App\Models\PerformanceCompetency;
use App\Models\PerformanceCompetencyCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReviewTemplate extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'default_for_department_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the department this template is the default for.
     */
    public function defaultForDepartment()
    {
        return $this->belongsTo(Department::class, 'default_for_department_id');
    }

    /**
     * Get the competency categories in this template.
     */
    public function competencyCategories()
    {
        return $this->hasMany(PerformanceCompetencyCategory::class, 'template_id');
    }

    /**
     * Get the competencies in this template.
     */
    public function competencies()
    {
        return $this->hasManyThrough(
            PerformanceCompetency::class,
            PerformanceCompetencyCategory::class,
            'template_id',
            'category_id'
        );
    }

    /**
     * Get the reviews using this template.
     */
    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class, 'template_id');
    }
}
