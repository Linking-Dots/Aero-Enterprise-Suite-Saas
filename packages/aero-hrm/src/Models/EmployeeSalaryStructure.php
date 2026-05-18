<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryStructure extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_component_id',
        'value',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee that owns this salary structure row.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the salary component.
     */
    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    /**
     * Scope to get active structures.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get structures effective on a specific date.
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?? now();

        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Scope to get earnings.
     */
    public function scopeEarnings($query)
    {
        return $query->whereHas('salaryComponent', function ($q) {
            $q->where('type', 'earning');
        });
    }

    /**
     * Scope to get deductions.
     */
    public function scopeDeductions($query)
    {
        return $query->whereHas('salaryComponent', function ($q) {
            $q->where('type', 'deduction');
        });
    }
}
