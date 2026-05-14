<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Insight Model
 *
 * AI-generated insights and alerts for HR decision-making.
 */
class AIInsight extends TenantModel
{
    protected $table = 'ai_insights';

    protected $fillable = [
        'insight_type',
        'severity',
        'scope',
        'employee_id',
        'department_id',
        'manager_id',
        'title',
        'description',
        'data_points',
        'recommended_actions',
        'confidence_score',
        'status',
        'actioned_by',
        'actioned_at',
        'action_taken',
        'insight_date',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'data_points' => 'array',
            'recommended_actions' => 'array',
            'confidence_score' => 'decimal:2',
            'actioned_at' => 'datetime',
            'insight_date' => 'date',
            'valid_until' => 'date',
        ];
    }

    public const INSIGHT_TYPES = [
        'attrition_alert' => 'Attrition Risk Alert',
        'burnout_warning' => 'Burnout Warning',
        'anomaly_detected' => 'Behavioral Anomaly',
        'mobility_opportunity' => 'Mobility Opportunity',
        'engagement_trend' => 'Engagement Trend',
        'team_health' => 'Team Health Issue',
        'department_risk' => 'Department Risk',
        'org_wide_pattern' => 'Organization-Wide Pattern',
    ];

    public const SEVERITY_COLORS = [
        'info' => 'primary',
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'actioned_by');
    }

    public function isActionable(): bool
    {
        return in_array($this->status, ['new', 'viewed', 'acknowledged']);
    }

    public function isCritical(): bool
    {
        return in_array($this->severity, ['high', 'critical']);
    }

    public function getInsightTypeLabel(): string
    {
        return self::INSIGHT_TYPES[$this->insight_type] ?? $this->insight_type;
    }

    public function getSeverityColor(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? 'default';
    }
}
