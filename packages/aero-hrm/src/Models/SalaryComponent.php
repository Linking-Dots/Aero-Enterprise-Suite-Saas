<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation_type',
        'percentage_of',
        'percentage_value',
        'default_amount',
        'formula',
        'is_taxable',
        'is_statutory',
        'affects_gross',
        'affects_ctc',
        'affects_epf',
        'affects_esi',
        'is_active',
        'show_in_payslip',
        'show_if_zero',
        'display_order',
        'description',
        'metadata',
    ];

    protected $casts = [
        'percentage_value' => 'decimal:4',
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
        'affects_gross' => 'boolean',
        'affects_ctc' => 'boolean',
        'affects_epf' => 'boolean',
        'affects_esi' => 'boolean',
        'is_active' => 'boolean',
        'show_in_payslip' => 'boolean',
        'show_if_zero' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get employee salary structures using this component
     */
    public function employeeSalaryStructures()
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    /**
     * Calculate amount for a given base value
     */
    public function calculateAmount(float $baseValue, array $context = []): float
    {
        switch ($this->calculation_type) {
            case 'fixed':
                return $this->default_amount ?? 0;

            case 'percentage':
                $baseAmount = $this->getBaseAmount($context);

                return $baseAmount * ($this->percentage_value / 100);

            case 'formula':
                return $this->evaluateFormula($context);

            case 'attendance':
                return $this->calculateFromAttendance($context);

            case 'slab':
                return $this->calculateFromSlab($context);

            default:
                return 0;
        }
    }

    /**
     * Get base amount for percentage calculations
     */
    protected function getBaseAmount(array $context): float
    {
        if (! $this->percentage_of) {
            return 0;
        }

        return match ($this->percentage_of) {
            'basic' => $context['basic_salary'] ?? 0,
            'gross' => $context['gross_salary'] ?? 0,
            'ctc' => $context['ctc'] ?? 0,
            default => 0,
        };
    }

    /**
     * Evaluate custom formula
     */
    protected function evaluateFormula(array $context): float
    {
        return 0;
    }

    /**
     * Calculate from attendance data
     */
    protected function calculateFromAttendance(array $context): float
    {
        $overtimeHours = $context['overtime_hours'] ?? 0;
        $dailySalary = ($context['basic_salary'] ?? 0) / ($context['working_days'] ?? 30);
        $hourlyRate = $dailySalary / 8;

        return $overtimeHours * $hourlyRate * 1.5;
    }

    /**
     * Calculate from slab
     */
    protected function calculateFromSlab(array $context): float
    {
        return 0;
    }

    /**
     * Scope to get earnings only
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }

    /**
     * Scope to get deductions only
     */
    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    /**
     * Scope to get active components
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get statutory components
     */
    public function scopeStatutory($query)
    {
        return $query->where('is_statutory', true);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
