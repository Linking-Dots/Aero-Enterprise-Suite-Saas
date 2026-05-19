<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmBenefitEnrollment extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_benefit_enrollments';

    const STATUS_ENROLLED = 'enrolled';

    const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'employee_id', 'period_id', 'benefit_id', 'status',
        'dependents_count', 'employee_cost_snapshot', 'employer_cost_snapshot',
        'waiver_reason', 'elected_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_cost_snapshot' => 'decimal:2',
            'employer_cost_snapshot' => 'decimal:2',
            'elected_at' => 'datetime',
            'dependents_count' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(HrmBenefit::class, 'benefit_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(HrmBenefitEnrollmentPeriod::class, 'period_id');
    }
}
