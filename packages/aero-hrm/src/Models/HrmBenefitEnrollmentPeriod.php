<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmBenefitEnrollmentPeriod extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_benefit_enrollment_periods';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name', 'starts_at', 'ends_at', 'coverage_starts_at', 'coverage_ends_at',
        'audience_filter', 'status', 'created_by', 'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'coverage_starts_at' => 'date',
            'coverage_ends_at' => 'date',
            'activated_at' => 'datetime',
            'audience_filter' => 'array',
        ];
    }

    public function benefits(): BelongsToMany
    {
        return $this->belongsToMany(
            HrmBenefit::class,
            'hrm_benefit_enrollment_period_benefits',
            'period_id',
            'benefit_id'
        )->withPivot('required');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HrmBenefitEnrollment::class, 'period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
