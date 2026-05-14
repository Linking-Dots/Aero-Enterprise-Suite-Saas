<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryStructure extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'salary_component_id',
        'amount',
        'percentage_value',
        'calculation_type',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage_value' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee (user)
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the salary component
     */
    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    /**
     * Calculate the actual amount for this structure
     */
    public function calculateAmount(array $context = []): float
    {
        // Use override calculation type if set, otherwise use component's
        $calculationType = $this->calculation_type ?? $this->salaryComponent->calculation_type;

        switch ($calculationType) {
            case 'fixed':
                return $this->amount ?? $this->salaryComponent->default_amount ?? 0;

            case 'percentage':
                $percentageValue = $this->percentage_value ?? $this->salaryComponent->percentage_value;
                $baseAmount = $this->getBaseAmount($context);

                return $baseAmount * ($percentageValue / 100);

            default:
                return $this->salaryComponent->calculateAmount(0, $context);
        }
    }

    /**
     * Get base amount for percentage calculations
     */
    protected function getBaseAmount(array $context): float
    {
        $percentageOf = $this->salaryComponent->percentage_of;

        if (! $percentageOf) {
            return 0;
        }

        return match ($percentageOf) {
            'basic' => $context['basic_salary'] ?? 0,
            'gross' => $context['gross_salary'] ?? 0,
            'ctc' => $context['ctc'] ?? 0,
            default => 0,
        };
    }

    /**
     * Scope to get active structures
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get structures effective on a specific date
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
     * Scope to get earnings
     */
    public function scopeEarnings($query)
    {
        return $query->whereHas('salaryComponent', function ($q) {
            $q->where('type', 'earning');
        });
    }

    /**
     * Scope to get deductions
     */
    public function scopeDeductions($query)
    {
        return $query->whereHas('salaryComponent', function ($q) {
            $q->where('type', 'deduction');
        });
    }
}
