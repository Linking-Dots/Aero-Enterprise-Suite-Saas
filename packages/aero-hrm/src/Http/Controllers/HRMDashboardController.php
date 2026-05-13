<?php

namespace Aero\HRM\Http\Controllers;

use Aero\HRM\Models\Attendance;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\PerformanceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRM Dashboard Controller
 *
 * Provides the main HR Manager dashboard with:
 * - Employee statistics
 * - Leave management overview
 * - Attendance trends
 * - Department breakdowns
 */
class HRMDashboardController extends Controller
{
    /**
     * Display the HRM Dashboard.
     */
    public function index(): Response
    {
        $today = Carbon::today();

        // Get employee stats
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();

        // Get leave stats
        $pendingLeaves = Leave::where('status', 'pending')->count();
        $approvedLeaves = Leave::where('status', 'approved')
            ->whereMonth('created_at', $today->month)
            ->count();

        // Get employees on leave today
        $onLeaveToday = Leave::where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->count();

        // Get pending leave requests for display
        $pendingLeaveRequests = Leave::with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee_name' => $leave->employee?->name ?? 'Unknown',
                    'employee_avatar' => $leave->employee?->avatar_url,
                    'leave_type' => $leave->leaveType?->name ?? 'Leave',
                    'days' => $leave->total_days ?? 1,
                    'from_date' => $leave->from_date?->format('M d'),
                    'to_date' => $leave->to_date?->format('M d'),
                    'status' => $leave->status,
                ];
            });

        // Get attendance stats for today
        $presentToday = Attendance::whereDate('date', $today)
            ->whereNotNull('punchin')
            ->count();

        $absentToday = $activeEmployees - $presentToday - $onLeaveToday;
        $absentToday = max(0, $absentToday);

        $lateToday = Attendance::whereDate('date', $today)
            ->where('is_late', true)
            ->count();

        // Calculate average attendance rate for the month
        $workingDays = $this->getWorkingDaysInMonth($today);
        $totalPossibleAttendance = $activeEmployees * $workingDays;
        $actualAttendance = Attendance::whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->whereNotNull('punchin')
            ->count();
        $averageAttendance = $totalPossibleAttendance > 0
            ? round(($actualAttendance / $totalPossibleAttendance) * 100)
            : 0;

        // Get department stats
        $departments = Department::withCount(['employees' => function ($query) {
            $query->where('status', 'active');
        }])
            ->take(10)
            ->get()
            ->map(function ($dept) use ($today) {
                // Calculate department attendance rate
                $deptEmployees = $dept->employees_count;
                if ($deptEmployees > 0) {
                    $deptAttendance = Attendance::whereDate('date', $today)
                        ->whereHas('employee', function ($q) use ($dept) {
                            $q->where('department_id', $dept->id);
                        })
                        ->whereNotNull('punchin')
                        ->count();
                    $attendanceRate = round(($deptAttendance / $deptEmployees) * 100);
                } else {
                    $attendanceRate = 0;
                }

                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'employee_count' => $deptEmployees,
                    'attendance_rate' => $attendanceRate,
                ];
            });

        // Performance review stats - Temporarily disabled due to missing column
        // $upcomingReviews = PerformanceReview::with(['employee'])
        //     ->where('status', 'scheduled')
        //     ->where('review_start_date', '>=', $today)
        //     ->orderBy('review_start_date')
        //     ->take(5)
        //     ->get();
        $upcomingReviews = collect(); // Empty collection for now

        // Additional HR metrics
        $openPositions = 0; // Can be integrated with recruitment module
        $pendingExpenses = 0; // Can be integrated with expense module
        $newHiresThisMonth = Employee::whereMonth('joining_date', $today->month)
            ->whereYear('joining_date', $today->year)
            ->count();

        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('HRM/Dashboard', [
            'title' => 'HRM Dashboard',
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'activeEmployees' => $activeEmployees,
                'onLeaveToday' => $onLeaveToday,
                'pendingLeaves' => $pendingLeaves,
                'approvedLeaves' => $approvedLeaves,
                'presentToday' => $presentToday,
                'absentToday' => $absentToday,
                'lateToday' => $lateToday,
                'averageAttendance' => $averageAttendance,
                'openPositions' => $openPositions,
                'pendingExpenses' => $pendingExpenses,
                'newHiresThisMonth' => $newHiresThisMonth,
            ],
            'pendingLeaves' => $pendingLeaveRequests,
            'departmentStats' => $departments,
            'upcomingReviews' => $upcomingReviews,
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }

    /**
     * Get dashboard stats via API (for async loading).
     */
    public function stats(Request $request)
    {
        $today = Carbon::today();

        // Get employee stats
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();

        // Leave stats
        $pendingLeaves = Leave::where('status', 'pending')->count();
        $onLeaveToday = Leave::where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->count();

        // Attendance stats
        $presentToday = Attendance::whereDate('date', $today)
            ->whereNotNull('punchin')
            ->count();
        $lateToday = Attendance::whereDate('date', $today)
            ->where('is_late', true)
            ->count();
        $absentToday = max(0, $activeEmployees - $presentToday - $onLeaveToday);

        // Average attendance
        $workingDays = $this->getWorkingDaysInMonth($today);
        $totalPossibleAttendance = $activeEmployees * $workingDays;
        $actualAttendance = Attendance::whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->whereNotNull('punchin')
            ->count();
        $averageAttendance = $totalPossibleAttendance > 0
            ? round(($actualAttendance / $totalPossibleAttendance) * 100)
            : 0;

        // Department stats
        $departments = Department::withCount(['employees' => function ($query) {
            $query->where('status', 'active');
        }])
            ->take(10)
            ->get()
            ->map(function ($dept) use ($today) {
                $deptEmployees = $dept->employees_count;
                if ($deptEmployees > 0) {
                    $deptAttendance = Attendance::whereDate('date', $today)
                        ->whereHas('employee', function ($q) use ($dept) {
                            $q->where('department_id', $dept->id);
                        })
                        ->whereNotNull('punchin')
                        ->count();
                    $attendanceRate = round(($deptAttendance / $deptEmployees) * 100);
                } else {
                    $attendanceRate = 0;
                }

                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'employee_count' => $deptEmployees,
                    'attendance_rate' => $attendanceRate,
                ];
            });

        // Recent activities (simplified)
        $recentActivities = [];

        return response()->json([
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'activeEmployees' => $activeEmployees,
                'onLeaveToday' => $onLeaveToday,
                'pendingLeaves' => $pendingLeaves,
                'presentToday' => $presentToday,
                'absentToday' => $absentToday,
                'lateToday' => $lateToday,
                'averageAttendance' => $averageAttendance,
                'openPositions' => 0,
                'pendingExpenses' => 0,
                'newHiresThisMonth' => Employee::whereMonth('joining_date', $today->month)
                    ->whereYear('joining_date', $today->year)
                    ->count(),
            ],
            'departments' => $departments,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Get working days in a month (excluding weekends).
     */
    private function getWorkingDaysInMonth(Carbon $date): int
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $workingDays = 0;

        while ($start->lte($end)) {
            if (! $start->isWeekend()) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }
}
