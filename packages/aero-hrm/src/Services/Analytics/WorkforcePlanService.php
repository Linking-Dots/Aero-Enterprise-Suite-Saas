<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\WorkforcePlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkforcePlanService
{
    /**
     * Plan vs actual headcount per department for a given fiscal year.
     *
     * @return Collection<int, array{department_id: int, department: string, fiscal_year: int, target_headcount: int, target_hires: int, target_attrition: int, actual_headcount: int}>
     */
    public function planVsActual(int $year): Collection
    {
        // Subquery: actual active headcount per department
        $actualSub = DB::table('employees')
            ->select('department_id', DB::raw('COUNT(*) as actual_headcount'))
            ->where('status', 'active')
            ->groupBy('department_id');

        return WorkforcePlan::where('hrm_workforce_plans.fiscal_year', $year)
            ->join('departments', 'departments.id', '=', 'hrm_workforce_plans.department_id')
            ->joinSub($actualSub, 'actuals', function ($join): void {
                $join->on('actuals.department_id', '=', 'hrm_workforce_plans.department_id');
            })
            ->select([
                'hrm_workforce_plans.department_id',
                'departments.name as department',
                'hrm_workforce_plans.fiscal_year',
                'hrm_workforce_plans.target_headcount',
                'hrm_workforce_plans.target_hires',
                'hrm_workforce_plans.target_attrition',
                DB::raw('COALESCE(actuals.actual_headcount, 0) as actual_headcount'),
            ])
            ->get()
            ->map(fn ($row) => [
                'department_id' => (int) $row->department_id,
                'department' => $row->department,
                'fiscal_year' => (int) $row->fiscal_year,
                'target_headcount' => (int) $row->target_headcount,
                'target_hires' => (int) $row->target_hires,
                'target_attrition' => (int) $row->target_attrition,
                'actual_headcount' => (int) $row->actual_headcount,
            ]);
    }
}
