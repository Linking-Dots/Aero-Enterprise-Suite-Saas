<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\HRM\Exceptions\UserNotOnboardedException;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Holiday;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\LeaveSetting;
use Aero\Contracts\RoleModuleAccessInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Leave Query Service
 *
 * REFACTORED: Employee-centric approach with no hardcoded role checks.
 * All authorization goes through HRMAuthorizationService and RoleModuleAccessService.
 */
class LeaveQueryService
{
    public function __construct(
        private EmployeeResolutionService $employeeResolver,
        private HRMAuthorizationService $authService,
        private ?RoleModuleAccessInterface $moduleAccessService = null
    ) {
        // Resolve from container if not injected
        if ($this->moduleAccessService === null) {
            $this->moduleAccessService = app(RoleModuleAccessInterface::class);
        }
    }

    /**
     * Get leave records with pagination and filtering
     *
     * REFACTORED: Uses Employee as entry point, authorization via HRMAuthorizationService
     * Admin users who are not employees can still view all leaves if they have module access.
     * Super Administrator bypasses all checks.
     */
    public function getLeaveRecords(Request $request, int $perPage = 30, int $page = 1, ?string $employee = '', ?int $year = null, ?string $month = null): array
    {
        $user = $request->user();
        $currentEmployee = null;
        $isNonEmployeeAdmin = false;

        // Resolve Employee from authenticated user
        try {
            $currentEmployee = $this->employeeResolver->resolveFromRequest($request);
        } catch (UserNotOnboardedException $e) {
            // Check if user has access via module access system (Super Admin bypasses all, or user has HRM leaves access)
            if ($user && $this->userHasLeaveModuleAccess($user)) {
                $isNonEmployeeAdmin = true;
            } else {
                throw $e; // Propagate - user is not onboarded and doesn't have module access
            }
        }

        // Check authorization - can they view all leaves or just their own?
        $canViewAllLeaves = $isNonEmployeeAdmin || ($currentEmployee && $this->authService->canViewAllLeaves($currentEmployee));
        $canManageLeaves = $isNonEmployeeAdmin || ($currentEmployee && $this->authService->canManageLeaves($currentEmployee));

        // Determine if this is an admin view based on request parameters and permissions
        $specificUserId = $request->get('user_id');
        $requestsAllView = $request->get('admin_view', false) ||
                           (! $specificUserId && $request->get('view_all', false)) ||
                           $request->header('X-Admin-View') === 'true';

        // User can only view all if they have the permission
        $isAdminView = $requestsAllView && $canViewAllLeaves;

        $perPage = $request->get('perPage', $perPage);
        $page = $request->get('employee') ? 1 : $request->get('page', $page);
        $employee = $request->get('employee', $employee) ?? '';
        $year = $request->get('year', $year);
        $month = $request->get('month', $month);
        $status = $request->get('status');
        $department = $request->get('department');
        $leaveType = $request->get('leave_type');
        $specificUserId = $request->get('user_id');

        $currentYear = $year ?: ($month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->year : now()->year);

        // Build query - leaves table uses user_id, join with employees via user_id
        $leavesQuery = Leave::with(['user', 'leaveSetting'])
            ->join('leave_settings', 'leaves.leave_type', '=', 'leave_settings.id')
            ->leftJoin('employees', 'leaves.user_id', '=', 'employees.user_id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->leftJoin('designations', 'employees.designation_id', '=', 'designations.id')
            ->select('leaves.*', 'leave_settings.name as leave_type_name', 'employees.id as employee_id', 'departments.name as department_name', 'designations.title as designation_name');

        // If a specific user_id is provided, filter by that user
        if ($specificUserId) {
            $leavesQuery->where('leaves.user_id', $specificUserId);
        }
        // Otherwise apply standard authorization rules
        elseif (! $isAdminView) {
            // Non-admin users must have an employee record to view their own leaves
            if (! $currentEmployee) {
                return [
                    'leaveRecords' => new LengthAwarePaginator([], 0, $perPage, $page),
                    'leavesData' => [
                        'leaveTypes' => [],
                        'leaveCountsWithRemainingByUser' => [],
                        'publicHolidays' => [],
                    ],
                    'message' => 'User is not onboarded as an employee',
                ];
            }
            $leavesQuery->where('leaves.user_id', $currentEmployee->user_id);
        }

        $currentEmployeeId = $currentEmployee?->id;
        $this->applyDateFilters($leavesQuery, $year, $month, $isAdminView, $currentEmployee?->user_id);
        $this->applyEmployeeFilter($leavesQuery, $employee);
        $this->applyStatusFilter($leavesQuery, $status);
        $this->applyLeaveTypeFilter($leavesQuery, $leaveType);
        $this->applyDepartmentFilter($leavesQuery, $department);

        $leaveRecords = $leavesQuery->orderByDesc('leaves.from_date')
            ->paginate($perPage, ['*'], 'page', $page);

        $leaveTypes = LeaveSetting::all();
        $leaveCountsWithRemainingByUser = $this->calculateLeaveCounts($year, $currentYear, $currentEmployee, $specificUserId);

        // Get public holidays for the current year
        $publicHolidays = Holiday::active()
            ->currentYear()
            ->get()
            ->flatMap(function ($holiday) {
                $dates = [];
                $startDate = \Carbon\Carbon::parse($holiday->date);
                $endDate = \Carbon\Carbon::parse($holiday->end_date ?? $holiday->date);

                while ($startDate->lte($endDate)) {
                    $dates[] = $startDate->format('Y-m-d');
                    $startDate->addDay();
                }

                return $dates;
            })->toArray();

        // Debug log for holiday data
        Log::info('LeaveQueryService - Holiday debug:', [
            'current_year' => $currentYear,
            'holiday_count_from_db' => Holiday::active()->currentYear()->count(),
            'processed_holiday_dates_count' => count($publicHolidays),
            'sample_holidays' => array_slice($publicHolidays, 0, 5),
            'august_holidays' => array_filter($publicHolidays, function ($date) {
                return str_starts_with($date, '2025-08');
            }),
        ]);

        // Process the data to fix date issues
        $processedLeaveRecords = $leaveRecords->getCollection()->map(function ($leave) {
            // Check if from_date and to_date have 'T18:00:00' pattern
            if (is_string($leave->from_date) && strpos($leave->from_date, 'T18:00:00') !== false) {
                $leave->from_date = date('Y-m-d', strtotime($leave->from_date.' +1 day'));
            }

            if (is_string($leave->to_date) && strpos($leave->to_date, 'T18:00:00') !== false) {
                $leave->to_date = date('Y-m-d', strtotime($leave->to_date.' +1 day'));
            }

            return $leave;
        });

        $leaveRecords->setCollection($processedLeaveRecords);

        // Handle empty datasets appropriately
        $message = null;
        if ($leaveRecords->isEmpty()) {
            if ($specificUserId) {
                $message = 'No leave records found for the selected user.';
            } elseif (! $isAdminView) {
                $message = 'You have no leave records for the selected period.';
            } else {
                $message = 'No leave records found for the selected criteria.';
            }
        }

        return [
            'leaveRecords' => $leaveRecords, // Return paginated result directly
            'leavesData' => [
                'leaveTypes' => $leaveTypes,
                'leaveCountsByUser' => $leaveCountsWithRemainingByUser,
                'publicHolidays' => $publicHolidays,
            ],
            'message' => $message, // Include appropriate message for empty data
        ];
    }

    /**
     * Apply date filters to the query
     */
    private function applyDateFilters($query, ?int $year, ?string $month, bool $isAdmin, ?int $userId): void
    {
        // Debug logging
        Log::info('LeaveQueryService - applyDateFilters called', [
            'year' => $year,
            'month' => $month,
            'isAdmin' => $isAdmin,
            'userId' => $userId,
        ]);

        // Priority: month filter first, then year filter
        if ($month) {
            // Apply month filter for both admin and employee views
            try {
                // Use correct Carbon parsing for Y-m format
                $monthStart = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
                $monthEnd = Carbon::createFromFormat('Y-m-d', $month.'-01')->endOfMonth();

                $range = [$monthStart, $monthEnd];

                Log::info('LeaveQueryService - applying month filter', [
                    'month' => $month,
                    'range_start' => $range[0]->toDateString(),
                    'range_end' => $range[1]->toDateString(),
                ]);

                $query->whereBetween('leaves.from_date', $range);
            } catch (\Exception $e) {
                // If month format is invalid, fall back to year filtering
                Log::warning('Invalid month format provided: '.$month);
                if ($year) {
                    $query->whereYear('leaves.from_date', $year);
                }
            }
        } elseif ($year) {
            // Apply year filter if no month is specified
            Log::info('LeaveQueryService - applying year filter', ['year' => $year]);
            $query->whereYear('leaves.from_date', $year);
        }

        // Note: User-specific filtering is already handled in the main query logic
        // so we don't need to reapply user_id filters here
    }

    /**
     * Apply employee filter to the query
     * Searches user name since leaves are linked to users, not employees directly
     */
    private function applyEmployeeFilter($query, ?string $employee): void
    {
        if ($employee) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%$employee%"));
        }
    }

    /**
     * Apply status filter to the query
     */
    private function applyStatusFilter($query, $status): void
    {
        if (! empty($status)) {
            if (is_array($status)) {
                // Flatten all mapped statuses for all selected keys
                $statusMap = [
                    'pending' => ['New', 'Pending'],
                    'approved' => ['Approved'],
                    'rejected' => ['Declined', 'Rejected'],
                    'new' => ['New'],
                ];

                $mappedStatuses = [];

                foreach ($status as $stat) {
                    if (isset($statusMap[$stat])) {
                        $mappedStatuses = array_merge($mappedStatuses, $statusMap[$stat]);
                    } else {
                        $mappedStatuses[] = ucfirst($stat);
                    }
                }

                $mappedStatuses = array_unique($mappedStatuses);

                $query->whereIn('leaves.status', $mappedStatuses);
            } else {
                // Previous single string logic
                if ($status !== 'all') {
                    $statusMap = [
                        'pending' => ['New', 'Pending'],
                        'approved' => ['Approved'],
                        'rejected' => ['Declined', 'Rejected'],
                        'new' => ['New'],
                    ];

                    if (isset($statusMap[$status])) {
                        $query->whereIn('leaves.status', $statusMap[$status]);
                    } else {
                        $query->where('leaves.status', ucfirst($status));
                    }
                }
            }
        }
    }

    /**
     * Apply leave type filter to the query
     */
    private function applyLeaveTypeFilter($query, $leaveType): void
    {
        // Only apply filter if leaveType has actual values
        if (! empty($leaveType) && $leaveType !== 'all') {
            if (is_array($leaveType)) {
                // If 'all' is in the array, don't apply any filtering (show all leave types)
                // "All" takes precedence over specific selections
                if (in_array('all', $leaveType)) {
                    return; // Don't apply any leave type filtering
                }

                // Filter array to remove empty values and 'all'
                $validTypes = array_filter($leaveType, function ($type) {
                    return ! empty($type) && $type !== 'all';
                });

                if (count($validTypes) > 0) {
                    // Use the already joined leave_settings table
                    $query->where(function ($q) use ($validTypes) {
                        foreach ($validTypes as $type) {
                            $q->orWhere('leave_settings.name', 'like', "%$type%");
                        }
                    });
                }
            } elseif (! is_array($leaveType) && $leaveType !== 'all') {
                // Use the already joined leave_settings table
                $query->where('leave_settings.name', 'like', "%$leaveType%");
            }
        }
        // If leaveType is empty, null, or contains 'all', don't apply any filtering (show all leave types)
    }

    private function applyDepartmentFilter($query, $department): void
    {
        if (! empty($department)) {
            if (is_array($department)) {
                $query->whereHas('employee', function ($q) use ($department) {
                    $q->whereIn('department_id', $department);
                });
            } elseif ($department !== 'all') {
                $query->whereHas('employee', function ($q) use ($department) {
                    $q->where('department_id', '=', $department);
                });
            }
        }
    }

    /**
     * Calculate leave counts and remaining days for employees
     *
     * REFACTORED: No hardcoded role checks, uses HRMAuthorizationService
     */
    private function calculateLeaveCounts(?int $year, int $currentYear, ?Employee $currentEmployee, ?int $specificUserId = null): array
    {
        // Use specific user ID if provided, otherwise use current employee's user_id
        $targetUserId = $specificUserId ?: $currentEmployee?->user_id;

        // Check if employee can view all leaves (admin/manager level)
        // Non-employee admins can view all leaves
        $calculateForAllUsers = is_null($specificUserId) && (
            ! $currentEmployee || $this->authService->canViewAllLeaves($currentEmployee)
        );

        if ($calculateForAllUsers) {
            // Calculate for all employees (admin view)
            // Note: leaves table has user_id, not employee_id - join via user_id
            $allLeaves = Leave::with('leaveSetting')
                ->join('leave_settings', 'leaves.leave_type', '=', 'leave_settings.id')
                ->leftJoin('employees', 'leaves.user_id', '=', 'employees.user_id')
                ->whereYear('leaves.from_date', $currentYear)
                ->get();
        } else {
            // Calculate for specific employee only
            try {
                $targetEmployee = $this->employeeResolver->resolveFromUserId($targetUserId);

                // Note: leaves table has user_id, not employee_id - filter by user_id
                $allLeaves = Leave::with('leaveSetting')
                    ->join('leave_settings', 'leaves.leave_type', '=', 'leave_settings.id')
                    ->where('leaves.user_id', $targetEmployee->user_id)
                    ->whereYear('leaves.from_date', $currentYear)
                    ->get();
            } catch (UserNotOnboardedException) {
                // User not onboarded, return empty
                return [
                    'leaveCounts' => [],
                    'remainingLeaves' => [],
                ];
            }
        }

        $leaveTypes = LeaveSetting::all();

        // Leave counts aggregation
        $leaveCountsByUser = [];
        foreach ($allLeaves as $leave) {
            // Skip leaves without valid leave settings
            if (! $leave->leaveSetting) {
                continue;
            }

            $type = $leave->leaveSetting->type ?? 'Unknown';
            $userId = $leave->user_id;
            $leaveCountsByUser[$userId][$type] = ($leaveCountsByUser[$userId][$type] ?? 0) + $leave->no_of_days;
        }

        $leaveCountsWithRemainingByUser = [];

        if ($calculateForAllUsers) {
            // For admin view, calculate for all users with leaves
            $allUserIds = array_unique(array_keys($leaveCountsByUser));
            foreach ($allUserIds as $userId) {
                $counts = $leaveCountsByUser[$userId] ?? [];
                $leaveCountsWithRemainingByUser[$userId] = $leaveTypes->map(function ($type) use ($counts) {
                    $used = $counts[$type->type] ?? 0;

                    return [
                        'leave_type' => $type->type,
                        'total_days' => $type->days,
                        'days_used' => $used,
                        'remaining_days' => max(0, $type->days - $used),
                    ];
                })->toArray();
            }
        } else {
            // For specific user, always include their data even if no leaves exist
            $counts = $leaveCountsByUser[$targetUserId] ?? [];
            $leaveCountsWithRemainingByUser[$targetUserId] = $leaveTypes->map(function ($type) use ($counts) {
                $used = $counts[$type->type] ?? 0;

                return [
                    'leave_type' => $type->type,
                    'total_days' => $type->days,
                    'days_used' => $used,
                    'remaining_days' => max(0, $type->days - $used),
                ];
            })->toArray();
        }

        return $leaveCountsWithRemainingByUser;
    }

    /**
     * Get leave statistics for admin dashboard
     */
    public function getLeaveStatistics(Request $request): array
    {
        $user = Auth::user();

        // Determine if this is an admin view based on request parameters
        $isAdminView = $request->get('admin_view', false) ||
                       $request->get('view_all', false) ||
                       $request->header('X-Admin-View') === 'true';

        $isAdmin = $isAdminView && $user;

        $month = $request->get('month');
        $year = $request->get('year', now()->year);
        $employee = $request->get('employee');
        $leaveType = $request->get('leave_type');

        // Use join like in the main query for consistency
        $query = Leave::join('leave_settings', 'leaves.leave_type', '=', 'leave_settings.id')
            ->join('users', 'leaves.user_id', '=', 'users.id') // Ensure user exists
            ->select('leaves.*', 'leave_settings.name as leave_type_name');

        // Base filtering
        if (! $isAdmin) {
            $query->where('leaves.user_id', $user->id);
        }

        // Apply filters
        if ($month) {
            $monthStart = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Y-m-d', $month.'-01')->endOfMonth();
            $query->whereBetween('leaves.from_date', [$monthStart, $monthEnd]);
        } elseif ($year) {
            $query->whereYear('leaves.from_date', $year);
        }

        if ($employee) {
            $query->whereHas('employee', fn ($q) => $q->where('name', 'like', "%$employee%"));
        }

        if (! empty($leaveType) && $leaveType !== 'all') {
            // Use the joined table for filtering
            if (is_array($leaveType)) {
                // If 'all' is in the array, don't apply any filtering (show all leave types)
                // "All" takes precedence over specific selections
                if (! in_array('all', $leaveType)) {
                    // Filter array to remove empty values and 'all'
                    $validTypes = array_filter($leaveType, function ($type) {
                        return ! empty($type) && $type !== 'all';
                    });

                    if (count($validTypes) > 0) {
                        $query->where(function ($q) use ($validTypes) {
                            foreach ($validTypes as $type) {
                                $q->orWhere('leave_settings.name', 'like', "%$type%");
                            }
                        });
                    }
                }
                // If 'all' is in array, don't apply any leave type filtering
            } elseif (! is_array($leaveType) && $leaveType !== 'all') {
                $query->where('leave_settings.name', 'like', "%$leaveType%");
            }
        }

        // Get status counts
        $stats = [
            'pending' => (clone $query)->whereIn('leaves.status', ['New', 'Pending'])->count(),
            'approved' => (clone $query)->where('leaves.status', 'Approved')->count(),
            'rejected' => (clone $query)->whereIn('leaves.status', ['Declined', 'Rejected'])->count(),
            'total' => (clone $query)->count(),
        ];

        return $stats;
    }

    /**
     * Check if user has access to the HRM leaves module via the module access system.
     * Super Administrator bypasses all module access checks.
     */
    protected function userHasLeaveModuleAccess(mixed $user): bool
    {
        if (! $user || ! $this->moduleAccessService) {
            return false;
        }

        // Check for HRM module access (includes leaves sub-module)
        // Super Administrator is automatically handled by userCanAccessSubModule
        return $this->moduleAccessService->userCanAccessSubModule($user, 'hrm', 'time-off')
            || $this->moduleAccessService->userCanAccessSubModule($user, 'hrm', 'leaves')
            || $this->moduleAccessService->userCanAccessModule($user, 'hrm');
    }
}
