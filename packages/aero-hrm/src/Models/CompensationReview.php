<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compensation Review Model
 *
 * Manages compensation review cycles and planning.
 */
class CompensationReview extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'review_type',
        'cycle_year',
        'start_date',
        'end_date',
        'budget_amount',
        'budget_percentage',
        'status',
        'guidelines',
        'approval_workflow',
        'department_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget_amount' => 'decimal:2',
            'budget_percentage' => 'decimal:2',
            'guidelines' => 'array',
            'approval_workflow' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CompensationAdjustment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get total proposed adjustments
     */
    public function getTotalProposedAmount(): float
    {
        return $this->adjustments()->sum('new_salary') - $this->adjustments()->sum('current_salary');
    }
}
