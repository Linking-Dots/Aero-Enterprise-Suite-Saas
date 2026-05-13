<?php

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'category',
        'budget_type',
        'initial_budget',
        'allocated_budget',
        'spent_amount',
        'remaining_budget',
        'currency',
        'description',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_budget' => 'decimal:2',
        'allocated_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the project that owns the budget.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the budget expenses.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectBudgetExpense::class, 'budget_id');
    }

    /**
     * Calculate budget utilization percentage.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_budget <= 0) {
            return 0;
        }

        return ($this->spent_amount / $this->allocated_budget) * 100;
    }

    /**
     * Check if budget is over allocated.
     */
    public function getIsOverBudgetAttribute(): bool
    {
        return $this->spent_amount > $this->allocated_budget;
    }

    /**
     * Get budget status color.
     */
    public function getStatusColorAttribute(): string
    {
        $utilization = $this->utilization_percentage;

        if ($utilization >= 100) {
            return 'red';
        } elseif ($utilization >= 80) {
            return 'orange';
        } elseif ($utilization >= 60) {
            return 'yellow';
        } else {
            return 'green';
        }
    }

    /**
     * Update spent amount based on expenses.
     */
    public function updateSpentAmount(): void
    {
        $this->spent_amount = $this->expenses()->sum('amount');
        $this->remaining_budget = $this->allocated_budget - $this->spent_amount;
        $this->save();
    }

    /**
     * Scope for active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for over-budget items.
     */
    public function scopeOverBudget($query)
    {
        return $query->whereRaw('spent_amount > allocated_budget');
    }
}
