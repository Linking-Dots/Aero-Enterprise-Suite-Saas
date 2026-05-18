<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\Employee;
use Illuminate\Support\Facades\DB;

class HeadcountAnalyticsService
{
    /**
     * Return core workforce KPIs.
     *
     * @return array{active: int, avgTenureDays: float, genderRatio: array{male: int, female: int, other: int}, byDept: list<array{department: string, count: int}>}
     */
    public function kpis(): array
    {
        $active = Employee::where('status', 'active')->count();

        // DB-agnostic tenure: load all active employees and compute diff in PHP via Carbon
        $avgTenureDays = 0.0;
        if ($active > 0) {
            $total = Employee::where('status', 'active')
                ->whereNotNull('date_of_joining')
                ->get(['date_of_joining'])
                ->avg(fn (Employee $e) => $e->date_of_joining
                    ? (float) $e->date_of_joining->diffInDays(now())
                    : 0.0
                );
            $avgTenureDays = round((float) ($total ?? 0.0), 2);
        }

        // Gender ratio
        $genderRows = Employee::where('status', 'active')
            ->selectRaw('gender, COUNT(*) as cnt')
            ->groupBy('gender')
            ->pluck('cnt', 'gender')
            ->toArray();

        $genderRatio = [
            'male' => (int) ($genderRows['male'] ?? 0),
            'female' => (int) ($genderRows['female'] ?? 0),
            'other' => (int) ($genderRows['other'] ?? 0),
        ];

        // Department distribution
        $byDept = Employee::where('employees.status', 'active')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as department, COUNT(employees.id) as count')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department,
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();

        return [
            'active' => $active,
            'avgTenureDays' => $avgTenureDays,
            'genderRatio' => $genderRatio,
            'byDept' => $byDept,
        ];
    }
}
