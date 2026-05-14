<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workforce Plan Model
 *
 * Strategic workforce planning and headcount forecasting.
 */
class WorkforcePlan extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'plan_year',
        'plan_type',
        'department_id',
        'start_date',
        'end_date',
        'current_headcount',
        'planned_headcount',
        'budget_amount',
        'status',
        'assumptions',
        'risks',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'budget_amount' => 'decimal:2',
            'assumptions' => 'array',
            'risks' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(WorkforcePlanPosition::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get headcount variance
     */
    public function getHeadcountVariance(): int
    {
        return $this->planned_headcount - $this->current_headcount;
    }
}
