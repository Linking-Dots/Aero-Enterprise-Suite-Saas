<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\Contracts\ModuleSummaryProvider;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Leave;

class HrmDashboardSummaryProvider implements ModuleSummaryProvider
{
    public function getDashboardSummary(): array
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $departments = Department::where('is_active', true)->count();

        $pendingLeaves = 0;
        try {
            $pendingLeaves = Leave::where('status', 'pending')->count();
        } catch (\Throwable) {
        }

        $alerts = [];
        if ($pendingLeaves > 10) {
            $alerts[] = "{$pendingLeaves} leave requests pending approval";
        }

        $probationEnding = Employee::where('status', 'active')
            ->whereNotNull('probation_end_date')
            ->whereBetween('probation_end_date', [now(), now()->addDays(30)])
            ->count();

        if ($probationEnding > 0) {
            $alerts[] = "{$probationEnding} probation periods ending soon";
        }

        return [
            'key' => 'hrm',
            'label' => 'Human Resources',
            'icon' => 'UsersIcon',
            'route' => 'tenant.hrm.dashboard',
            'stats' => [
                'employees' => $totalEmployees,
                'active' => $activeEmployees,
                'departments' => $departments,
                'pendingLeaves' => $pendingLeaves,
            ],
            'alerts' => $alerts,
            'pendingCount' => $pendingLeaves,
        ];
    }
}
