<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Models\LeaveApplication;

class AttritionRiskService
{
    /**
     * Compute rule-based attrition risk score for an employee.
     *
     * @return array{score: float, band: string, factors: list<array{name: string, value: float, description: string}>}
     */
    public function score(Employee $employee): array
    {
        $factors = [];
        $total = 0.0;

        // --- Factor 1: Tenure (< 1 year = higher risk) ---
        $tenureDays = $employee->date_of_joining
            ? (float) $employee->date_of_joining->diffInDays(now())
            : 365.0;

        if ($tenureDays < 180) {
            $val = 0.25;
        } elseif ($tenureDays < 365) {
            $val = 0.15;
        } else {
            $val = 0.0;
        }
        $factors[] = ['name' => 'short_tenure', 'value' => $val, 'description' => 'Employee tenure < 1 year'];
        $total += $val;

        // --- Factor 2: Employment status ---
        if ($employee->status !== 'active') {
            $val = 0.20;
            $factors[] = ['name' => 'inactive_status', 'value' => $val, 'description' => 'Employee not in active status'];
            $total += $val;
        }

        // --- Factor 3: Low performance rating ---
        /** @var HrmPerformanceReview|null $lastReview */
        $lastReview = HrmPerformanceReview::where('employee_id', $employee->id)
            ->latest('id')
            ->first();

        if ($lastReview && $lastReview->final_rating !== null && (float) $lastReview->final_rating < 2.5) {
            $val = 0.20;
            $factors[] = ['name' => 'low_performance', 'value' => $val, 'description' => 'Latest performance rating below 2.5'];
            $total += $val;
        }

        // --- Factor 4: Leave anomaly (many leaves in past 90 days) ---
        $recentLeaves = LeaveApplication::where('employee_id', $employee->id)
            ->whereDate('start_date', '>=', now()->subDays(90))
            ->count();

        if ($recentLeaves >= 5) {
            $val = 0.15;
            $factors[] = ['name' => 'leave_anomaly', 'value' => $val, 'description' => '5+ leave applications in last 90 days'];
            $total += $val;
        }

        // --- Factor 5: Manager churn (stub — no managerHistory relationship yet) ---
        $val = 0.1;
        $factors[] = ['name' => 'manager_churn', 'value' => $val, 'description' => 'Manager history unavailable (stub)'];
        $total += $val;

        // --- Factor 6: Comp ratio (stub — salary/jobBand not yet available) ---
        $val = 0.3;
        $factors[] = ['name' => 'comp_ratio', 'value' => $val, 'description' => 'Compensation ratio unavailable (stub)'];
        $total += $val;

        // Clamp to [0, 1]
        $score = min(1.0, $total);

        $band = match (true) {
            $score >= 0.75 => 'critical',
            $score >= 0.50 => 'high',
            $score >= 0.25 => 'medium',
            default => 'low',
        };

        return [
            'score' => round($score, 4),
            'band' => $band,
            'factors' => $factors,
        ];
    }
}
