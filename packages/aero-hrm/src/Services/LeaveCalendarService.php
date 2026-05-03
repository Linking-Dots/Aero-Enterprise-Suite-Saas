<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\Core\Support\TenantCache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Leave Calendar Service
 *
 * Provides team leave calendar view and availability insights.
 *
 * Features:
 * - Team leave calendar generation
 * - Availability forecasting
 * - Leave conflict detection
 * - Holiday integration
 * - Department/team filtering
 * - Export capabilities (iCal, CSV)
 * - Capacity planning insights
 *
 * Usage:
 * ```php
 * $calendarService = app(LeaveCalendarService::class);
 *
 * // Get team calendar for a month
 * $calendar = $calendarService->getTeamCalendar($departmentId, '2024-01');
 *
 * // Check availability
 * $availability = $calendarService->getAvailability($teamId, $date);
 *
 * // Detect conflicts
 * $conflicts = $calendarService->detectConflicts($employeeId, $fromDate, $toDate);
 * ```
 */
class LeaveCalendarService
{
    /**
     * Leave status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Calendar view types.
     */
    public const VIEW_MONTH = 'month';

    public const VIEW_WEEK = 'week';

    public const VIEW_DAY = 'day';

    /**
     * Minimum team capacity threshold (percentage).
     */
    protected int $minCapacityThreshold = 50;

    /**
     * Cache duration in minutes.
     */
    protected int $cacheDuration = 30;

    /**
     * Get team calendar for a given period.
     *
     * @param  int|array|null  $departmentIds  Department ID(s) or null for all
     * @param  string  $month  Format: 'YYYY-MM'
     * @param  array  $options  Additional options
     */
    public function getTeamCalendar(
        int|array|null $departmentIds,
        string $month,
        array $options = []
    ): array {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $includeWeekends = $options['include_weekends'] ?? false;
        $includeHolidays = $options['include_holidays'] ?? true;
        $groupBy = $options['group_by'] ?? 'date'; // 'date', 'employee', 'department'

        $cacheKey = $this->getCacheKey('calendar', $departmentIds, $month, $options);

        return TenantCache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use (
            $departmentIds,
            $startDate,
            $endDate,
            $includeWeekends,
            $includeHolidays,
            $groupBy
        ) {
            // Get leaves for the period
            $leaves = $this->getLeavesForPeriod($departmentIds, $startDate, $endDate);

            // Get holidays if requested
            $holidays = $includeHolidays ? $this->getHolidays($startDate, $endDate) : [];

            // Get all employees in scope
            $employees = $this->getEmployees($departmentIds);

            // Build calendar data
            $calendar = $this->buildCalendarData(
                $startDate,
                $endDate,
                $leaves,
                $employees,
                $holidays,
                $includeWeekends,
                $groupBy
            );

            return [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'month' => $startDate->format('F Y'),
                ],
                'calendar' => $calendar,
                'summary' => $this->calculateSummary($leaves, $employees, $startDate, $endDate),
                'holidays' => $holidays,
            ];
        });
    }

    /**
     * Get availability for a specific date or period.
     */
    public function getAvailability(
        int|array|null $departmentIds,
        Carbon|string $date,
        Carbon|string|null $endDate = null
    ): array {
        $startDate = Carbon::parse($date);
        $endDate = $endDate ? Carbon::parse($endDate) : $startDate;

        $employees = $this->getEmployees($departmentIds);
        $totalEmployees = $employees->count();

        $leaves = $this->getLeavesForPeriod($departmentIds, $startDate, $endDate);

        $availability = [];

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }

            $dateStr = $day->toDateString();
            $onLeave = $this->getEmployeesOnLeave($leaves, $day);
            $onLeaveCount = count($onLeave);

            $available = $totalEmployees - $onLeaveCount;
            $capacityPercent = $totalEmployees > 0
                ? round(($available / $totalEmployees) * 100, 1)
                : 100;

            $availability[$dateStr] = [
                'date' => $dateStr,
                'day_name' => $day->format('l'),
                'total_employees' => $totalEmployees,
                'on_leave' => $onLeaveCount,
                'available' => $available,
                'capacity_percent' => $capacityPercent,
                'is_low_capacity' => $capacityPercent < $this->minCapacityThreshold,
                'employees_on_leave' => array_values($onLeave),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_employees' => $totalEmployees,
            'daily_availability' => array_values($availability),
            'low_capacity_days' => collect($availability)
                ->filter(fn ($day) => $day['is_low_capacity'])
                ->count(),
        ];
    }

    /**
     * Detect leave conflicts for an employee.
     */
    public function detectConflicts(
        int $employeeId,
        Carbon|string $fromDate,
        Carbon|string $toDate,
        array $options = []
    ): array {
        $fromDate = Carbon::parse($fromDate);
        $toDate = Carbon::parse($toDate);

        // Get employee's department
        $employee = $this->getEmployee($employeeId);

        if (! $employee) {
            return ['has_conflicts' => false, 'conflicts' => []];
        }

        $departmentId = $employee->department_id;
        $minCapacity = $options['min_capacity'] ?? $this->minCapacityThreshold;

        // Get team members
        $teamMembers = $this->getEmployees($departmentId);
        $teamSize = $teamMembers->count();

        // Get existing approved leaves
        $existingLeaves = $this->getLeavesForPeriod(
            $departmentId,
            $fromDate,
            $toDate,
            [self::STATUS_APPROVED]
        );

        $conflicts = [];
        $period = CarbonPeriod::create($fromDate, $toDate);

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }

            $dateStr = $day->toDateString();
            $onLeave = $this->getEmployeesOnLeave($existingLeaves, $day);
            $onLeaveCount = count($onLeave);

            // Add the requesting employee
            $potentialOnLeave = $onLeaveCount + 1;
            $potentialAvailable = $teamSize - $potentialOnLeave;
            $potentialCapacity = $teamSize > 0
                ? round(($potentialAvailable / $teamSize) * 100, 1)
                : 100;

            if ($potentialCapacity < $minCapacity) {
                $conflicts[] = [
                    'date' => $dateStr,
                    'day_name' => $day->format('l'),
                    'current_on_leave' => $onLeaveCount,
                    'team_size' => $teamSize,
                    'potential_capacity' => $potentialCapacity,
                    'employees_on_leave' => array_values($onLeave),
                    'conflict_type' => 'low_capacity',
                    'message' => "Approving this leave would reduce team capacity to {$potentialCapacity}% on {$dateStr}",
                ];
            }

            // Check for specific blocklist (e.g., managers can't be out same day)
            $blocklist = $this->checkBlocklistConflicts($employeeId, $onLeave, $day);
            if (! empty($blocklist)) {
                $conflicts = array_merge($conflicts, $blocklist);
            }
        }

        // Check for holiday conflicts
        $holidays = $this->getHolidays($fromDate, $toDate);
        $holidayConflicts = [];

        foreach ($holidays as $holiday) {
            $holidayDate = Carbon::parse($holiday['date']);

            if ($holidayDate->between($fromDate, $toDate)) {
                $holidayConflicts[] = [
                    'date' => $holiday['date'],
                    'conflict_type' => 'holiday',
                    'message' => "This date ({$holiday['date']}) is already a holiday: {$holiday['name']}",
                ];
            }
        }

        $allConflicts = array_merge($conflicts, $holidayConflicts);

        return [
            'has_conflicts' => ! empty($allConflicts),
            'conflict_count' => count($allConflicts),
            'conflicts' => $allConflicts,
            'can_approve' => empty($conflicts), // Holiday overlaps are warnings, not blockers
        ];
    }

    /**
     * Get leave balance and forecast for an employee.
     */
    public function getLeaveBalanceForecast(int $employeeId, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        // Get leave balances (would come from your leave balance model)
        $balances = $this->getEmployeeLeaveBalances($employeeId, $year);

        // Get approved and pending leaves
        $leaves = DB::table('leave_requests')
            ->where('employee_id', $employeeId)
            ->whereYear('from_date', $year)
            ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PENDING])
            ->get();

        $usedByType = [];
        $pendingByType = [];

        foreach ($leaves as $leave) {
            $days = $this->calculateLeaveDays($leave->from_date, $leave->to_date);

            if ($leave->status === self::STATUS_APPROVED) {
                $usedByType[$leave->leave_type] = ($usedByType[$leave->leave_type] ?? 0) + $days;
            } else {
                $pendingByType[$leave->leave_type] = ($pendingByType[$leave->leave_type] ?? 0) + $days;
            }
        }

        $forecast = [];

        foreach ($balances as $type => $balance) {
            $used = $usedByType[$type] ?? 0;
            $pending = $pendingByType[$type] ?? 0;
            $available = $balance['total'] - $used;
            $effectiveAvailable = $available - $pending;

            $forecast[$type] = [
                'leave_type' => $type,
                'total_entitlement' => $balance['total'],
                'used' => $used,
                'pending' => $pending,
                'available' => $available,
                'effective_available' => max(0, $effectiveAvailable),
                'carryover' => $balance['carryover'] ?? 0,
                'expires_at' => $balance['expires_at'] ?? null,
            ];
        }

        return [
            'employee_id' => $employeeId,
            'year' => $year,
            'balances' => $forecast,
            'total_used' => array_sum($usedByType),
            'total_pending' => array_sum($pendingByType),
        ];
    }

    /**
     * Export calendar to iCal format.
     */
    public function exportToICal(int|array|null $departmentIds, string $month): string
    {
        $calendar = $this->getTeamCalendar($departmentIds, $month);

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//aeos365//Leave Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:Team Leave Calendar\r\n";

        foreach ($calendar['calendar'] as $day) {
            foreach ($day['leaves'] ?? [] as $leave) {
                $ical .= "BEGIN:VEVENT\r\n";
                $ical .= 'UID:'.uniqid('leave-', true)."@aero\r\n";
                $ical .= 'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n";
                $ical .= 'DTSTART;VALUE=DATE:'.str_replace('-', '', $day['date'])."\r\n";
                $ical .= 'DTEND;VALUE=DATE:'.str_replace('-', '', $day['date'])."\r\n";
                $ical .= 'SUMMARY:'.$leave['employee_name'].' - '.$leave['leave_type']." Leave\r\n";
                $ical .= 'DESCRIPTION:'.($leave['reason'] ?? 'On leave')."\r\n";
                $ical .= "STATUS:CONFIRMED\r\n";
                $ical .= "END:VEVENT\r\n";
            }
        }

        // Add holidays
        foreach ($calendar['holidays'] as $holiday) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= 'UID:holiday-'.md5($holiday['date'].$holiday['name'])."@aero\r\n";
            $ical .= 'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n";
            $ical .= 'DTSTART;VALUE=DATE:'.str_replace('-', '', $holiday['date'])."\r\n";
            $ical .= 'DTEND;VALUE=DATE:'.str_replace('-', '', $holiday['date'])."\r\n";
            $ical .= 'SUMMARY:🎉 '.$holiday['name']."\r\n";
            $ical .= "CATEGORIES:HOLIDAY\r\n";
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Export calendar to CSV format.
     */
    public function exportToCSV(int|array|null $departmentIds, string $month): string
    {
        $calendar = $this->getTeamCalendar($departmentIds, $month);

        $csv = "Date,Day,Employee,Department,Leave Type,Status\n";

        foreach ($calendar['calendar'] as $day) {
            foreach ($day['leaves'] ?? [] as $leave) {
                $csv .= implode(',', [
                    $day['date'],
                    $day['day_name'],
                    '"'.str_replace('"', '""', $leave['employee_name']).'"',
                    '"'.str_replace('"', '""', $leave['department'] ?? '').'"',
                    $leave['leave_type'],
                    $leave['status'],
                ])."\n";
            }
        }

        return $csv;
    }

    /**
     * Get capacity forecast for planning.
     */
    public function getCapacityForecast(int|array|null $departmentIds, int $daysAhead = 30): array
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays($daysAhead)->endOfDay();

        $employees = $this->getEmployees($departmentIds);
        $totalEmployees = $employees->count();

        $leaves = $this->getLeavesForPeriod($departmentIds, $startDate, $endDate, [self::STATUS_APPROVED]);
        $pendingLeaves = $this->getLeavesForPeriod($departmentIds, $startDate, $endDate, [self::STATUS_PENDING]);

        $forecast = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }

            $dateStr = $day->toDateString();
            $approvedOnLeave = count($this->getEmployeesOnLeave($leaves, $day));
            $pendingOnLeave = count($this->getEmployeesOnLeave($pendingLeaves, $day));

            $confirmedAvailable = $totalEmployees - $approvedOnLeave;
            $worstCase = $totalEmployees - $approvedOnLeave - $pendingOnLeave;

            $forecast[] = [
                'date' => $dateStr,
                'day_name' => $day->format('D'),
                'confirmed_available' => $confirmedAvailable,
                'confirmed_capacity' => $totalEmployees > 0
                    ? round(($confirmedAvailable / $totalEmployees) * 100, 1)
                    : 100,
                'worst_case_available' => max(0, $worstCase),
                'worst_case_capacity' => $totalEmployees > 0
                    ? round((max(0, $worstCase) / $totalEmployees) * 100, 1)
                    : 100,
                'pending_leaves' => $pendingOnLeave,
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_employees' => $totalEmployees,
            'forecast' => $forecast,
            'critical_dates' => collect($forecast)
                ->filter(fn ($f) => $f['confirmed_capacity'] < $this->minCapacityThreshold)
                ->values()
                ->all(),
        ];
    }

    /**
     * Set minimum capacity threshold.
     */
    public function setMinCapacityThreshold(int $percent): self
    {
        $this->minCapacityThreshold = $percent;

        return $this;
    }

    /**
     * Build calendar data structure.
     */
    protected function buildCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        Collection $leaves,
        Collection $employees,
        array $holidays,
        bool $includeWeekends,
        string $groupBy
    ): array {
        $calendar = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        $holidayDates = collect($holidays)->pluck('name', 'date')->all();
        $totalEmployees = $employees->count();

        foreach ($period as $day) {
            if (! $includeWeekends && $day->isWeekend()) {
                continue;
            }

            $dateStr = $day->toDateString();
            $dayLeaves = $this->getEmployeesOnLeave($leaves, $day, true);
            $onLeaveCount = count($dayLeaves);
            $available = $totalEmployees - $onLeaveCount;

            $calendar[] = [
                'date' => $dateStr,
                'day_name' => $day->format('l'),
                'day_short' => $day->format('D'),
                'day_number' => $day->day,
                'is_weekend' => $day->isWeekend(),
                'is_holiday' => isset($holidayDates[$dateStr]),
                'holiday_name' => $holidayDates[$dateStr] ?? null,
                'is_today' => $day->isToday(),
                'total_employees' => $totalEmployees,
                'on_leave' => $onLeaveCount,
                'available' => $available,
                'capacity_percent' => $totalEmployees > 0
                    ? round(($available / $totalEmployees) * 100, 1)
                    : 100,
                'leaves' => $dayLeaves,
            ];
        }

        if ($groupBy === 'employee') {
            return $this->groupCalendarByEmployee($calendar, $employees);
        }

        if ($groupBy === 'department') {
            return $this->groupCalendarByDepartment($calendar);
        }

        return $calendar;
    }

    /**
     * Group calendar by employee.
     */
    protected function groupCalendarByEmployee(array $calendar, Collection $employees): array
    {
        $grouped = [];

        foreach ($employees as $employee) {
            $employeeLeaves = [];

            foreach ($calendar as $day) {
                $dayLeaves = collect($day['leaves'])
                    ->where('employee_id', $employee->id)
                    ->first();

                if ($dayLeaves) {
                    $employeeLeaves[] = [
                        'date' => $day['date'],
                        'leave_type' => $dayLeaves['leave_type'],
                        'status' => $dayLeaves['status'],
                    ];
                }
            }

            $grouped[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'department' => $employee->department->name ?? null,
                'leaves' => $employeeLeaves,
                'total_leave_days' => count($employeeLeaves),
            ];
        }

        return $grouped;
    }

    /**
     * Group calendar by department.
     */
    protected function groupCalendarByDepartment(array $calendar): array
    {
        // Implementation for department grouping
        return $calendar;
    }

    /**
     * Get leaves for a period.
     */
    protected function getLeavesForPeriod(
        int|array|null $departmentIds,
        Carbon $startDate,
        Carbon $endDate,
        array $statuses = [self::STATUS_APPROVED, self::STATUS_PENDING]
    ): Collection {
        $query = DB::table('leave_requests')
            ->join('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->whereIn('leave_requests.status', $statuses)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('leave_requests.from_date', [$startDate, $endDate])
                    ->orWhereBetween('leave_requests.to_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('leave_requests.from_date', '<=', $startDate)
                            ->where('leave_requests.to_date', '>=', $endDate);
                    });
            })
            ->select('leave_requests.*', 'employees.name as employee_name', 'employees.department_id');

        if ($departmentIds !== null) {
            $departmentIds = is_array($departmentIds) ? $departmentIds : [$departmentIds];
            $query->whereIn('employees.department_id', $departmentIds);
        }

        return $query->get();
    }

    /**
     * Get employees on leave for a specific date.
     */
    protected function getEmployeesOnLeave(Collection $leaves, Carbon $date, bool $includeDetails = false): array
    {
        $onLeave = [];

        foreach ($leaves as $leave) {
            $fromDate = Carbon::parse($leave->from_date);
            $toDate = Carbon::parse($leave->to_date);

            if ($date->between($fromDate, $toDate)) {
                if ($includeDetails) {
                    $onLeave[] = [
                        'employee_id' => $leave->employee_id,
                        'employee_name' => $leave->employee_name,
                        'leave_type' => $leave->leave_type ?? 'Annual',
                        'status' => $leave->status,
                        'reason' => $leave->reason ?? null,
                        'department' => $leave->department_id ?? null,
                    ];
                } else {
                    $onLeave[$leave->employee_id] = [
                        'id' => $leave->employee_id,
                        'name' => $leave->employee_name,
                    ];
                }
            }
        }

        return $onLeave;
    }

    /**
     * Calculate summary statistics.
     */
    protected function calculateSummary(Collection $leaves, Collection $employees, Carbon $startDate, Carbon $endDate): array
    {
        $totalLeaveDays = 0;
        $byType = [];
        $byDepartment = [];

        foreach ($leaves as $leave) {
            $days = $this->calculateLeaveDays(
                max($startDate->toDateString(), $leave->from_date),
                min($endDate->toDateString(), $leave->to_date)
            );

            $totalLeaveDays += $days;

            $type = $leave->leave_type ?? 'Annual';
            $byType[$type] = ($byType[$type] ?? 0) + $days;

            $dept = $leave->department_id ?? 'Unknown';
            $byDepartment[$dept] = ($byDepartment[$dept] ?? 0) + $days;
        }

        return [
            'total_employees' => $employees->count(),
            'total_leave_days' => $totalLeaveDays,
            'unique_employees_on_leave' => $leaves->pluck('employee_id')->unique()->count(),
            'by_type' => $byType,
            'by_department' => $byDepartment,
            'average_leaves_per_employee' => $employees->count() > 0
                ? round($totalLeaveDays / $employees->count(), 2)
                : 0,
        ];
    }

    /**
     * Get holidays for a period.
     */
    protected function getHolidays(Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('holidays')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn ($h) => [
                'date' => $h->date,
                'name' => $h->name,
                'type' => $h->type ?? 'public',
            ])
            ->all();
    }

    /**
     * Get employees by department.
     */
    protected function getEmployees(int|array|null $departmentIds): Collection
    {
        $query = DB::table('employees')
            ->where('status', 'active');

        if ($departmentIds !== null) {
            $departmentIds = is_array($departmentIds) ? $departmentIds : [$departmentIds];
            $query->whereIn('department_id', $departmentIds);
        }

        return $query->get();
    }

    /**
     * Get single employee.
     */
    protected function getEmployee(int $employeeId)
    {
        return DB::table('employees')->find($employeeId);
    }

    /**
     * Check blocklist conflicts.
     */
    protected function checkBlocklistConflicts(int $employeeId, array $onLeave, Carbon $date): array
    {
        // Example: Check if employee's direct manager is also on leave
        // This would be customized based on business rules
        return [];
    }

    /**
     * Get employee leave balances.
     */
    protected function getEmployeeLeaveBalances(int $employeeId, int $year): array
    {
        $balances = DB::table('leave_balances')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();

        $result = [];

        foreach ($balances as $balance) {
            $result[$balance->leave_type] = [
                'total' => $balance->total_days,
                'carryover' => $balance->carryover_days ?? 0,
                'expires_at' => $balance->expires_at ?? null,
            ];
        }

        // Default balances if not found
        if (empty($result)) {
            $result = [
                'annual' => ['total' => 20, 'carryover' => 0],
                'sick' => ['total' => 10, 'carryover' => 0],
                'personal' => ['total' => 5, 'carryover' => 0],
            ];
        }

        return $result;
    }

    /**
     * Calculate leave days between dates.
     */
    protected function calculateLeaveDays(string $fromDate, string $toDate): int
    {
        $start = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);

        $days = 0;
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $day) {
            if (! $day->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Generate cache key.
     */
    protected function getCacheKey(string $type, mixed $departments, string $month, array $options): string
    {
        $deptKey = is_array($departments) ? implode('-', $departments) : ($departments ?? 'all');

        return "leave_calendar:{$type}:{$deptKey}:{$month}:".md5(json_encode($options));
    }
}
