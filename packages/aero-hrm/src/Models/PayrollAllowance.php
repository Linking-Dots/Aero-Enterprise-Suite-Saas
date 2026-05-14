<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollAllowance extends TenantModel
{
    use HasFactory;

    protected $table = 'payroll_allowances';

    protected $fillable = [
        'payroll_id',
        'allowance_type',
        'amount',
        'is_taxable',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    /**
     * Get the payroll that owns the allowance.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get formatted allowance type.
     */
    public function getFormattedTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->allowance_type));
    }

    /**
     * Allowance types.
     */
    public static function allowanceTypes()
    {
        return [
            'house_rent_allowance' => 'House Rent Allowance (HRA)',
            'transport_allowance' => 'Transport Allowance',
            'medical_allowance' => 'Medical Allowance',
            'food_allowance' => 'Food Allowance',
            'performance_bonus' => 'Performance Bonus',
            'overtime' => 'Overtime',
            'special_allowance' => 'Special Allowance',
            'dearness_allowance' => 'Dearness Allowance',
            'education_allowance' => 'Education Allowance',
            'communication_allowance' => 'Communication Allowance',
            'other' => 'Other Allowance',
        ];
    }
}
