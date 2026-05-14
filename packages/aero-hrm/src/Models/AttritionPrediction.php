<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attrition Prediction Model
 *
 * Tracks historical attrition predictions for each employee.
 * Used for model accuracy tracking and trend analysis.
 */
class AttritionPrediction extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'predicted_probability',
        'prediction_horizon_days',
        'feature_importance',
        'model_inputs',
        'model_version',
        'was_correct',
        'actual_departure_date',
        'predicted_at',
    ];

    protected function casts(): array
    {
        return [
            'predicted_probability' => 'decimal:4',
            'feature_importance' => 'array',
            'model_inputs' => 'array',
            'was_correct' => 'boolean',
            'actual_departure_date' => 'date',
            'predicted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRiskLevelAttribute(): string
    {
        $probability = $this->predicted_probability;

        if ($probability >= 0.75) {
            return 'critical';
        } elseif ($probability >= 0.50) {
            return 'high';
        } elseif ($probability >= 0.25) {
            return 'medium';
        }

        return 'low';
    }

    public function getTopFactorsAttribute(): array
    {
        $factors = $this->feature_importance ?? [];
        arsort($factors);

        return array_slice($factors, 0, 5, true);
    }
}
