<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compensation Adjustment Model
 *
 * Individual salary adjustments within a compensation review.
 */
class CompensationAdjustment extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'compensation_review_id',
        'employee_id',
        'current_salary',
        'proposed_salary',
        'approved_salary',
        'adjustment_type',
        'adjustment_reason',
        'percentage_increase',
        'effective_date',
        'status',
        'manager_recommendation',
        'hr_recommendation',
        'proposed_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_salary' => 'decimal:2',
            'proposed_salary' => 'decimal:2',
            'approved_salary' => 'decimal:2',
            'percentage_increase' => 'decimal:2',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function compensationReview(): BelongsTo
    {
        return $this->belongsTo(CompensationReview::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate percentage increase
     */
    public function calculatePercentageIncrease(): float
    {
        if ($this->current_salary <= 0) {
            return 0;
        }

        return (($this->proposed_salary - $this->current_salary) / $this->current_salary) * 100;
    }
}
