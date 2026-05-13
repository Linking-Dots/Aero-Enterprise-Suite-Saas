<?php

namespace Aero\FieldService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianSkill extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_technician_skills';

    protected $fillable = [
        'skill_name', 'skill_category', 'description', 'certification_required',
        'skill_level', 'is_active',
    ];

    protected $casts = [
        'certification_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    const CATEGORY_ELECTRICAL = 'electrical';

    const CATEGORY_MECHANICAL = 'mechanical';

    const CATEGORY_HVAC = 'hvac';

    const CATEGORY_PLUMBING = 'plumbing';

    const CATEGORY_SOFTWARE = 'software';

    const CATEGORY_SAFETY = 'safety';

    const LEVEL_BASIC = 'basic';

    const LEVEL_INTERMEDIATE = 'intermediate';

    const LEVEL_ADVANCED = 'advanced';

    const LEVEL_EXPERT = 'expert';

    public function technicians()
    {
        return $this->belongsToMany(Technician::class, 'field_service_technician_skill_assignments')
            ->withPivot('proficiency_level', 'certification_date', 'expiry_date');
    }

    public function workOrderRequirements()
    {
        return $this->belongsToMany(ServiceWorkOrder::class, 'field_service_work_order_skill_requirements');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('skill_category', $category);
    }
}
