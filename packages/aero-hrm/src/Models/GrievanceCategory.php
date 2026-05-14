<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Grievance Category Model
 *
 * Categories for classifying employee grievances.
 */
class GrievanceCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'requires_investigation',
        'default_priority',
        'escalation_days',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_investigation' => 'boolean',
            'is_active' => 'boolean',
            'escalation_days' => 'integer',
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function grievances(): HasMany
    {
        return $this->hasMany(Grievance::class, 'category_id');
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Get default categories for seeding.
     */
    public static function getDefaults(): array
    {
        return [
            [
                'name' => 'Workplace Harassment',
                'code' => 'WH',
                'description' => 'Sexual, verbal, or physical harassment complaints',
                'requires_investigation' => true,
                'default_priority' => 'critical',
                'escalation_days' => 3,
            ],
            [
                'name' => 'Discrimination',
                'code' => 'DC',
                'description' => 'Discrimination based on race, gender, age, religion, etc.',
                'requires_investigation' => true,
                'default_priority' => 'critical',
                'escalation_days' => 3,
            ],
            [
                'name' => 'Workplace Safety',
                'code' => 'WS',
                'description' => 'Health and safety concerns',
                'requires_investigation' => true,
                'default_priority' => 'high',
                'escalation_days' => 5,
            ],
            [
                'name' => 'Compensation & Benefits',
                'code' => 'CB',
                'description' => 'Salary, bonus, or benefits disputes',
                'requires_investigation' => false,
                'default_priority' => 'medium',
                'escalation_days' => 7,
            ],
            [
                'name' => 'Management Issues',
                'code' => 'MI',
                'description' => 'Concerns with supervisory or management practices',
                'requires_investigation' => false,
                'default_priority' => 'medium',
                'escalation_days' => 7,
            ],
            [
                'name' => 'Policy Concerns',
                'code' => 'PC',
                'description' => 'Issues with company policies or procedures',
                'requires_investigation' => false,
                'default_priority' => 'low',
                'escalation_days' => 14,
            ],
            [
                'name' => 'Workload & Stress',
                'code' => 'WL',
                'description' => 'Excessive workload or work-related stress',
                'requires_investigation' => false,
                'default_priority' => 'medium',
                'escalation_days' => 7,
            ],
            [
                'name' => 'Interpersonal Conflict',
                'code' => 'IC',
                'description' => 'Conflicts between colleagues',
                'requires_investigation' => false,
                'default_priority' => 'medium',
                'escalation_days' => 7,
            ],
            [
                'name' => 'Other',
                'code' => 'OT',
                'description' => 'Other grievances not covered by specific categories',
                'requires_investigation' => false,
                'default_priority' => 'low',
                'escalation_days' => 14,
            ],
        ];
    }
}
