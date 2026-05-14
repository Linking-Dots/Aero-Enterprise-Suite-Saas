<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Behavioral Anomaly Model
 *
 * Detects and tracks unusual patterns in employee behavior.
 * Supports early intervention and performance management.
 */
class BehavioralAnomaly extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'anomaly_type',
        'anomaly_score',
        'baseline_value',
        'actual_value',
        'deviation_percentage',
        'context_data',
        'description',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'anomaly_date',
    ];

    protected function casts(): array
    {
        return [
            'context_data' => 'array',
            'anomaly_score' => 'decimal:2',
            'baseline_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'deviation_percentage' => 'decimal:2',
            'anomaly_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public const ANOMALY_TYPES = [
        'attendance_pattern' => 'Attendance Pattern Change',
        'absence_frequency' => 'Increased Absence Frequency',
        'performance_drop' => 'Performance Decline',
        'communication_change' => 'Communication Pattern Change',
        'overtime_spike' => 'Unusual Overtime',
        'productivity_variance' => 'Productivity Variance',
        'leave_pattern' => 'Unusual Leave Pattern',
        'engagement_drop' => 'Engagement Decline',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function isSevere(): bool
    {
        return $this->anomaly_score >= 75;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'false_positive']);
    }

    public function getAnomalyTypeLabel(): string
    {
        return self::ANOMALY_TYPES[$this->anomaly_type] ?? $this->anomaly_type;
    }
}
