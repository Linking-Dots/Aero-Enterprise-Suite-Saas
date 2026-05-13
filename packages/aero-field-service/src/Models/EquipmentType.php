<?php

namespace Aero\FieldService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentType extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_equipment_types';

    protected $fillable = [
        'type_name', 'category', 'description', 'default_service_interval_days',
        'typical_lifespan_years', 'maintenance_requirements', 'safety_requirements',
        'skill_requirements', 'is_active',
    ];

    protected $casts = [
        'default_service_interval_days' => 'integer',
        'typical_lifespan_years' => 'integer',
        'maintenance_requirements' => 'json',
        'safety_requirements' => 'json',
        'skill_requirements' => 'json',
        'is_active' => 'boolean',
    ];

    const CATEGORY_HVAC = 'hvac';

    const CATEGORY_ELECTRICAL = 'electrical';

    const CATEGORY_PLUMBING = 'plumbing';

    const CATEGORY_MECHANICAL = 'mechanical';

    const CATEGORY_COMPUTER = 'computer';

    const CATEGORY_SAFETY = 'safety';

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function maintenanceTemplates()
    {
        return $this->hasMany(EquipmentMaintenanceTemplate::class);
    }

    public function requiredSkills()
    {
        return $this->belongsToMany(TechnicianSkill::class, 'field_service_equipment_type_skills');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getActiveEquipmentCountAttribute()
    {
        return $this->equipment()->where('status', Equipment::STATUS_ACTIVE)->count();
    }
}
