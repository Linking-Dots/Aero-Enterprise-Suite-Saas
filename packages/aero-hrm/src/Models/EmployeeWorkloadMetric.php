<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Workload Metrics Model
 *
 * Tracks daily/weekly/monthly workload for burnout risk analysis.
 */
class EmployeeWorkloadMetric extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'metric_date',
        'period_type',
        'scheduled_hours',
        'actual_hours_worked',
        'overtime_hours',
        'break_time_hours',
        'tasks_assigned',
        'tasks_completed',
        'tasks_overdue',
        'active_projects',
        'meeting_hours',
        'meeting_count',
        'utilization_rate',
        'overtime_ratio',
        'task_completion_rate',
        'consecutive_overtime_flag',
        'days_without_leave',
        'weekend_work_flag',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'scheduled_hours' => 'decimal:2',
            'actual_hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'break_time_hours' => 'decimal:2',
            'meeting_hours' => 'decimal:2',
            'utilization_rate' => 'decimal:2',
            'overtime_ratio' => 'decimal:2',
            'task_completion_rate' => 'decimal:2',
            'consecutive_overtime_flag' => 'boolean',
            'weekend_work_flag' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isOverworked(): bool
    {
        return $this->utilization_rate > 110 || $this->overtime_ratio > 25;
    }

    public function hasBurnoutIndicators(): bool
    {
        return $this->consecutive_overtime_flag ||
               $this->days_without_leave > 60 ||
               ($this->weekend_work_flag && $this->overtime_hours > 10);
    }

    public function getProductivityScore(): float
    {
        if ($this->tasks_assigned === 0) {
            return 100;
        }

        return min(100, ($this->tasks_completed / $this->tasks_assigned) * 100);
    }
}
