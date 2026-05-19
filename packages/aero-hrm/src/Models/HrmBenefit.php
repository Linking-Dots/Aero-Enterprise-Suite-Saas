<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmBenefit extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_benefits';

    protected $fillable = [
        'code', 'name', 'category', 'description', 'provider',
        'employee_cost', 'employer_cost', 'frequency',
        'allows_dependents', 'dependent_cost', 'eligibility_rules', 'active',
    ];

    protected function casts(): array
    {
        return [
            'employee_cost' => 'decimal:2',
            'employer_cost' => 'decimal:2',
            'dependent_cost' => 'decimal:2',
            'allows_dependents' => 'boolean',
            'active' => 'boolean',
            'eligibility_rules' => 'array',
        ];
    }

    public function enrollmentPeriods(): BelongsToMany
    {
        return $this->belongsToMany(
            HrmBenefitEnrollmentPeriod::class,
            'hrm_benefit_enrollment_period_benefits',
            'benefit_id',
            'period_id'
        )->withPivot('required');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HrmBenefitEnrollment::class, 'benefit_id');
    }
}
