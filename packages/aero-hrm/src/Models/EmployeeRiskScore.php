<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Employee Risk Scores Model
 *
 * Central tracking of AI-computed risk metrics for each employee.
 * Aggregates attrition risk, burnout risk, and engagement scores.
 */
class EmployeeRiskScore extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'attrition_risk_score',
        'attrition_risk_factors',
        'attrition_calculated_at',
        'burnout_risk_score',
        'burnout_risk_factors',
        'burnout_calculated_at',
        'engagement_score',
        'engagement_factors',
        'engagement_calculated_at',
        'flight_risk_level',
        'recommended_actions',
        'performance_trend',
    ];

    protected function casts(): array
    {
        return [
            'attrition_risk_factors' => 'array',
            'burnout_risk_factors' => 'array',
            'engagement_factors' => 'array',
            'attrition_calculated_at' => 'datetime',
            'burnout_calculated_at' => 'datetime',
            'engagement_calculated_at' => 'datetime',
            'attrition_risk_score' => 'decimal:2',
            'burnout_risk_score' => 'decimal:2',
            'engagement_score' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attritionPredictions(): HasMany
    {
        return $this->hasMany(AttritionPrediction::class, 'employee_id', 'employee_id');
    }

    public function isHighRisk(): bool
    {
        return in_array($this->flight_risk_level, ['high', 'critical']);
    }

    public function needsIntervention(): bool
    {
        return $this->attrition_risk_score >= 70 || $this->burnout_risk_score >= 70;
    }
}
