<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compensation History Model
 *
 * Tracks salary changes, bonuses, and compensation adjustments over time.
 */
class CompensationHistory extends TenantModel
{
    use HasFactory;

    protected $table = 'compensation_history';

    protected $fillable = [
        'employee_id',
        'change_type',
        'previous_salary',
        'new_salary',
        'change_amount',
        'change_percentage',
        'currency',
        'reason',
        'effective_date',
        'approved_by',
        'approval_date',
        'notes',
        'performance_rating',
        'market_adjustment',
        'promotion_id',
    ];

    protected function casts(): array
    {
        return [
            'previous_salary' => 'decimal:2',
            'new_salary' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'change_percentage' => 'decimal:2',
            'effective_date' => 'date',
            'approval_date' => 'date',
            'market_adjustment' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Change types.
     */
    public const TYPE_ANNUAL_INCREASE = 'annual_increase';

    public const TYPE_PROMOTION = 'promotion';

    public const TYPE_MARKET_ADJUSTMENT = 'market_adjustment';

    public const TYPE_MERIT_INCREASE = 'merit_increase';

    public const TYPE_COST_OF_LIVING = 'cost_of_living';

    public const TYPE_DEMOTION = 'demotion';

    public const TYPE_CORRECTION = 'correction';

    public const TYPE_BONUS = 'bonus';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PromotionHistory::class, 'promotion_id');
    }

    /**
     * Calculate change percentage.
     */
    public static function calculateChangePercentage(float $previous, float $new): float
    {
        if ($previous == 0) {
            return 0;
        }

        return round((($new - $previous) / $previous) * 100, 2);
    }

    /**
     * Scope for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('effective_date', [$startDate, $endDate]);
    }

    /**
     * Get change type display.
     */
    public function getChangeTypeDisplayAttribute(): string
    {
        return match ($this->change_type) {
            self::TYPE_ANNUAL_INCREASE => 'Annual Increase',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_MARKET_ADJUSTMENT => 'Market Adjustment',
            self::TYPE_MERIT_INCREASE => 'Merit Increase',
            self::TYPE_COST_OF_LIVING => 'Cost of Living Adjustment',
            self::TYPE_DEMOTION => 'Demotion',
            self::TYPE_CORRECTION => 'Correction',
            self::TYPE_BONUS => 'Bonus',
            default => ucfirst(str_replace('_', ' ', $this->change_type)),
        };
    }
}
