<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends TenantModel
{
    use HasFactory;

    protected $table = 'payroll_deductions';

    protected $fillable = [
        'payroll_id',
        'deduction_type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the payroll that owns the deduction.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get formatted deduction type.
     */
    public function getFormattedTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->deduction_type));
    }

    /**
     * Deduction types.
     */
    public static function deductionTypes()
    {
        return [
            'provident_fund' => 'Provident Fund (PF)',
            'employee_state_insurance' => 'Employee State Insurance (ESI)',
            'income_tax' => 'Income Tax (TDS)',
            'professional_tax' => 'Professional Tax',
            'loan_deduction' => 'Loan Deduction',
            'advance_deduction' => 'Advance Deduction',
            'late_coming_fine' => 'Late Coming Fine',
            'absent_deduction' => 'Absent Deduction',
            'uniform_deduction' => 'Uniform Deduction',
            'canteen_deduction' => 'Canteen Deduction',
            'other' => 'Other Deduction',
        ];
    }
}
