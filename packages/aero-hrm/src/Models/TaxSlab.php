<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxSlab extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_slabs';

    protected $fillable = [
        'name',
        'min_income',
        'max_income',
        'tax_rate',
        'regime',
        'is_active',
        'country',
        'state',
        'financial_year',
        'description',
    ];

    protected $casts = [
        'min_income' => 'decimal:2',
        'max_income' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get tax slabs for a specific financial year and regime
     */
    public static function getActiveSlabs($financialYear = null, $regime = 'new')
    {
        $query = self::where('is_active', true)
            ->where('regime', $regime);

        if ($financialYear) {
            $query->where('financial_year', $financialYear);
        }

        return $query->orderBy('min_income')->get();
    }

    /**
     * Calculate tax for a given income using specific regime
     */
    public static function calculateTax($income, $financialYear = null, $regime = 'new')
    {
        $slabs = self::getActiveSlabs($financialYear, $regime);

        $totalTax = 0;
        $remainingIncome = $income;

        foreach ($slabs as $slab) {
            if ($remainingIncome <= 0) {
                break;
            }

            $slabRange = $slab->max_income - $slab->min_income;
            $taxableInSlab = min($remainingIncome, $slabRange);
            $tax = $taxableInSlab * ($slab->tax_rate / 100);
            $totalTax += $tax;
            $remainingIncome -= $taxableInSlab;
        }

        return round($totalTax, 2);
    }

    /**
     * Get slab for a specific income level
     */
    public static function getSlabForIncome($income, $regime = 'new', $financialYear = null)
    {
        $query = self::where('is_active', true)
            ->where('regime', $regime)
            ->where('min_income', '<=', $income)
            ->where('max_income', '>', $income);

        if ($financialYear) {
            $query->where('financial_year', $financialYear);
        }

        return $query->first();
    }

    /**
     * Get all available financial years
     */
    public static function getAvailableFinancialYears()
    {
        return self::where('is_active', true)
            ->distinct()
            ->pluck('financial_year')
            ->sort()
            ->values();
    }

    /**
     * Scope to filter by regime
     */
    public function scopeRegime($query, $regime)
    {
        return $query->where('regime', $regime);
    }

    /**
     * Scope to filter by financial year
     */
    public function scopeFinancialYear($query, $year)
    {
        return $query->where('financial_year', $year);
    }

    /**
     * Scope to filter active slabs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
