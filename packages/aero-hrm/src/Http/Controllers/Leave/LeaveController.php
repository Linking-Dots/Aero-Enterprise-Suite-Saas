<?php

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Events\Leave\LeaveApproved;
use Aero\HRM\Events\Leave\LeaveCancelled;
use Aero\HRM\Events\Leave\LeaveRejected;
use Aero\HRM\Events\Leave\LeaveRequested;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Resources\LeaveResource;
use Aero\HRM\Http\Resources\LeaveResourceCollection;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\LeaveSetting;
use Aero\HRM\Services\EmployeeResolutionService;
use Aero\HRM\Services\LeaveApprovalService;
use Aero\HRM\Services\LeaveBalanceService;
use Aero\HRM\Services\LeaveCrudService;
use Aero\HRM\Services\LeaveOverlapService;
use Aero\HRM\Services\LeaveQueryService;
use Aero\HRM\Services\LeaveSummaryService;
use Aero\HRM\Services\LeaveValidationService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LeaveController extends Controller
{
    protected LeaveValidationService $validationService;

    protected LeaveOverlapService $overlapService;

    protected LeaveCrudService $crudService;

    protected LeaveQueryService $queryService;

    protected LeaveSummaryService $summaryService;

    protected LeaveApprovalService $approvalService;

    protected LeaveBalanceService $balanceService;

    protected EmployeeResolutionService $employeeResolver;

    /**
     * Resolve configured user model to avoid cross-package coupling.
     */
    protected function userModel(): string
    {
        return config('hrm.user_model', config('auth.providers.users.model'));
    }

    public function __construct(
        LeaveValidationService $validationService,
        LeaveOverlapService $overlapService,
        LeaveCrudService $crudService,
        LeaveQueryService $queryService,
        LeaveSummaryService $summaryService,
        LeaveApprovalService $approvalService,
        LeaveBalanceService $balanceService,
        EmployeeResolutionService $employeeResolver
    ) {
        $this->validationService = $validationService;
        $this->overlapService = $overlapService;
        $this->crudService = $crudService;
        $this->queryService = $queryService;
        $this->summaryService = $summaryService;
        $this->approvalService = $approvalService;
        $this->balanceService = $balanceService;
        $this->employeeResolver = $employeeResolver;
    }

    public function index1(): \Inertia\Response
    {
        return Inertia::render('HRM/LeavesEmployee', [
            'title' => 'My Leaves',
            'allUsers' => $this->userModel()::all(),

        ]);
    }

    public function paginate(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $leaveData = $this->queryService->getLeaveRecords($request);

            // Debug log the structure returned by LeaveQueryService
            Log::info('LeaveController - leaveData structure:', [
                'leavesData_keys' => array_keys($leaveData['leavesData'] ?? []),
                'publicHolidays_count' => count($leaveData['leavesData']['publicHolidays'] ?? []),
                'publicHolidays_sample' => array_slice($leaveData['leavesData']['publicHolidays'] ?? [], 0, 3),
            ]);

            $response = [
                'leaves' => new LeaveResourceCollection($leaveData['leaveRecords']),
                'leavesData' => $leaveData['leavesData'],
                'departments' => Department::all('id', 'name'),
                'success' => true,
            ];

            // Add message if provided by the service
            if (isset($leaveData['message'])) {
                $response['message'] = $leaveData['message'];
            }

            return response()->json($response, 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while retrieving leave data.',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $stats = $this->queryService->getLeaveStatistics($request);

            return response()->json([
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while retrieving leave data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = $this->validationService->validateLeaveRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], 422);
        }

        try {
            $fromDate = Carbon::parse($request->input('fromDate'));
            $toDate = Carbon::parse($request->input('toDate'));
            $userId = $request->input('user_id');
            $daysCount = $request->input('daysCount');
            $leaveTypeString = $request->input('leaveType');

            // Get leave type ID (support both 'name' and 'type' columns for compatibility)
            $leaveTypeId = LeaveSetting::where('name', $leaveTypeString)->value('id');
            if (! $leaveTypeId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid leave type selected',
                ], 422);
            }

            // Check for sufficient leave balance
            // Get the Employee record for balance checking (LeaveBalanceService requires Employee model)
            $employee = Employee::where('user_id', $userId)->first();
            if ($employee && ! $this->balanceService->hasSufficientBalance($employee, $leaveTypeId, $daysCount, $fromDate->year)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient leave balance. Please check your available leave days.',
                    'message' => 'Insufficient leave balance',
                ], 422);
            }

            // Check for overlapping leaves
            $overlapError = $this->overlapService->getOverlapErrorMessage($userId, $fromDate, $toDate);
            if ($overlapError) {
                return response()->json([
                    'success' => false,
                    'error' => $overlapError,
                    'message' => 'Leave dates overlap with existing leave or holiday',
                ], 422);
            }

            // Create new leave
            $newLeave = $this->crudService->createLeave($request->all());

            // Fire event to update balance
            event(new LeaveRequested($newLeave));

            // Get updated leave records using the same service as paginate method
            $leaveData = $this->queryService->getLeaveRecords($request);

            return response()->json([
                'success' => true,
                'message' => 'Leave application submitted successfully',
                'leave' => array_merge(
                    (new LeaveResource($newLeave->load('employee')))->toArray($request),
                    [
                        'month' => is_string($newLeave->from_date)
                            ? date('F', strtotime($newLeave->from_date))
                            : $newLeave->from_date->format('F'),
                        'year' => is_string($newLeave->from_date)
                            ? date('Y', strtotime($newLeave->from_date))
                            : $newLeave->from_date->year,
                        'leave_type' => LeaveSetting::find($newLeave->leave_type)?->type,
                    ]
                ),
                'leaves' => new LeaveResourceCollection($leaveData['leaveRecords']),
                'leavesData' => $leaveData['leavesData'],
                'departments' => Department::all('id', 'name'),
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while submitting the leave data.',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $updatedLeave = $this->crudService->updateLeave($request->input('id'), $request->all());

            // Get updated leave records using the same service as paginate method
            $leaveData = $this->queryService->getLeaveRecords($request);

            return response()->json([
                'success' => true,
                'message' => 'Leave application updated successfully',
                'leave' => array_merge(
                    (new LeaveResource($updatedLeave->load('employee')))->toArray($request),
                    [
                        'month' => is_string($updatedLeave->from_date)
                            ? date('F', strtotime($updatedLeave->from_date))
                            : $updatedLeave->from_date->format('F'),
                        'year' => is_string($updatedLeave->from_date)
                            ? date('Y', strtotime($updatedLeave->from_date))
                            : $updatedLeave->from_date->year,
                        'leave_type' => LeaveSetting::find($updatedLeave->leave_type)?->type,
                    ]
                ),
                'leaves' => new LeaveResourceCollection($leaveData['leaveRecords']),
                'leavesData' => $leaveData['leavesData'],
                'departments' => Department::all('id', 'name'),
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while updating the leave.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        $leaveId = $request->input('id');
        $status = $request->input('status');

        $result = $this->crudService->updateLeaveStatus(
            $leaveId,
            $status,
            Auth::id()
        );

        // Fire appropriate event for balance tracking
        if ($result['success']) {
            $leave = Leave::find($leaveId);
            if ($leave) {
                if ($status === 'Approved') {
                    event(new LeaveApproved($leave));
                } elseif ($status === 'Rejected') {
                    event(new LeaveRejected($leave));
                }
            }
        }

        return response()->json(['message' => $result['message']]);
    }

    public function delete(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->validationService->validateDeleteRequest($request);
            $leaveId = $request->query('id');

            // Get leave before deletion to fire event
            $leave = Leave::find($leaveId);

            $this->crudService->deleteLeave($leaveId);

            // Fire cancellation event for balance tracking
            if ($leave) {
                event(new LeaveCancelled($leave));
            }

            $leaveData = $this->queryService->getLeaveRecords($request);

            return response()->json([
                'success' => true,
                'message' => 'Leave application deleted successfully',
                'leaves' => new LeaveResourceCollection($leaveData['leaveRecords']),
                'leavesData' => $leaveData['leavesData'],
                'departments' => Department::all('id', 'name'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while deleting the leave.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function leaveSummary(Request $request)
    {
        $filters = [
            'year' => $request->input('year', now()->year),
            'department_id' => $request->input('department_id'),
            'employee_id' => $request->input('employee_id'),
            'status' => $request->input('status'),
            'leave_type' => $request->input('leave_type'),
        ];

        $summaryData = $this->summaryService->generateLeaveSummary($filters);

        return Inertia::render('HRM/TimeOff/Summary', [
            'title' => 'Leave Summary',
            'summaryData' => $summaryData,
        ]);
    }

    public function bulkApprove(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'leave_ids' => 'required|array',
                'leave_ids.*' => 'integer|exists:leave_applications,id',
            ]);

            $leaveIds = $request->input('leave_ids');
            $updatedCount = 0;

            foreach ($leaveIds as $leaveId) {
                $result = $this->crudService->updateLeaveStatus($leaveId, 'Approved', Auth::id());
                if ($result['updated']) {
                    $updatedCount++;
                }
            }

            return response()->json([
                'message' => "{$updatedCount} leave(s) approved successfully",
                'updated_count' => $updatedCount,
                'total_requested' => count($leaveIds),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while approving leaves.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkReject(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'leave_ids' => 'required|array',
                'leave_ids.*' => 'integer|exists:leave_applications,id',
            ]);

            $leaveIds = $request->input('leave_ids');
            $updatedCount = 0;

            foreach ($leaveIds as $leaveId) {
                $result = $this->crudService->updateLeaveStatus($leaveId, 'Declined', Auth::id());
                if ($result['updated']) {
                    $updatedCount++;
                }
            }

            return response()->json([
                'message' => "{$updatedCount} leave(s) rejected successfully",
                'updated_count' => $updatedCount,
                'total_requested' => count($leaveIds),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'An error occurred while rejecting leaves.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'year' => $request->input('year', now()->year),
            'department_id' => $request->input('department_id'),
            'employee_id' => $request->input('employee_id'),
            'status' => $request->input('status'),
            'leave_type' => $request->input('leave_type'),
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeaveSummaryExport($filters),
            'Leave_Summary_'.($filters['year'] ?? now()->year).'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = [
            'year' => $request->input('year', now()->year),
            'department_id' => $request->input('department_id'),
            'employee_id' => $request->input('employee_id'),
            'status' => $request->input('status'),
            'leave_type' => $request->input('leave_type'),
        ];

        $summaryData = $this->summaryService->generateLeaveSummary($filters);

        $pdf = PDF::loadView('leave_summary_pdf', [
            'title' => 'Leave Summary - '.($filters['year'] ?? now()->year),
            'generatedOn' => now()->format('F d, Y h:i A'),
            'summaryData' => $summaryData,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Leave_Summary_'.($filters['year'] ?? now()->year).'.pdf');
    }

    /**
     * Approve leave request at current approval level
     */
    public function approveLeave(Request $request, $id)
    {
        try {
            $leave = Leave::findOrFail($id);
            $approver = Auth::user();
            $comments = $request->input('comments');

            $result = $this->approvalService->approve($leave, $approver, $comments);

            if ($result['success']) {
                return response()->json($result, 200);
            }

            return response()->json($result, 403);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the leave.',
            ], 500);
        }
    }

    /**
     * Reject leave request
     */
    public function rejectLeave(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|min:10',
            ]);

            $leave = Leave::findOrFail($id);
            $approver = Auth::user();
            $reason = $request->input('reason');

            $result = $this->approvalService->reject($leave, $approver, $reason);

            if ($result['success']) {
                return response()->json($result, 200);
            }

            return response()->json($result, 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the leave.',
            ], 500);
        }
    }

    /**
     * Get pending approvals for current user
     */
    public function pendingApprovals()
    {
        try {
            $user = Auth::user();
            $employee = $this->employeeResolver->resolveFromUserId($user->id);
            $pendingLeaves = $this->approvalService->getPendingApprovalsForEmployee($employee);
            $stats = $this->approvalService->getApprovalStats($employee);

            return response()->json([
                'success' => true,
                'pending_leaves' => $pendingLeaves,
                'stats' => $stats,
            ], 200);
        } catch (\Aero\HRM\Exceptions\UserNotOnboardedException $e) {
            // User is not onboarded as employee - return empty data gracefully
            return response()->json([
                'success' => true,
                'pending_leaves' => [],
                'stats' => [
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'total' => 0,
                ],
                'message' => 'User is not onboarded as an employee.',
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching pending approvals.',
            ], 500);
        }
    }

    /**
     * Get leave analytics data
     */
    public function getAnalytics(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $departmentId = $request->input('department_id');

            $analytics = [
                'monthly_trends' => $this->getMonthlyTrends($year, $departmentId),
                'department_comparison' => $this->getDepartmentComparison($year),
                'leave_type_distribution' => $this->getLeaveTypeDistribution($year, $departmentId),
                'absenteeism_rate' => $this->getAbsenteeismRate($year, $departmentId),
                'peak_periods' => $this->getPeakPeriods($year, $departmentId),
            ];

            return response()->json([
                'success' => true,
                'analytics' => $analytics,
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching analytics.',
            ], 500);
        }
    }

    /**
     * Get monthly leave trends
     */
    protected function getMonthlyTrends($year, $departmentId = null)
    {
        $query = Leave::whereYear('from_date', $year);

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return collect(range(1, 12))->map(function ($month) use ($query) {
            $monthQuery = clone $query;

            return [
                'month' => Carbon::create(null, $month)->format('M'),
                'leaves_taken' => $monthQuery->whereMonth('from_date', $month)
                    ->where('status', 'approved')
                    ->sum('no_of_days'),
                'leaves_approved' => $monthQuery->whereMonth('from_date', $month)
                    ->where('status', 'approved')
                    ->count(),
            ];
        });
    }

    /**
     * Get department comparison
     */
    protected function getDepartmentComparison($year)
    {
        return Department::withCount(['users as average_days' => function ($query) use ($year) {
            $query->join('leaves', 'users.id', '=', 'leaves.user_id')
                ->whereYear('leaves.from_date', $year)
                ->where('leaves.status', 'approved')
                ->select(DB::raw('AVG(leaves.no_of_days)'));
        }])
            ->having('average_days', '>', 0)
            ->get()
            ->map(function ($dept) {
                return [
                    'department' => $dept->name,
                    'average_days' => round($dept->average_days ?? 0, 2),
                ];
            });
    }

    /**
     * Get leave type distribution
     */
    protected function getLeaveTypeDistribution($year, $departmentId = null)
    {
        $query = Leave::whereYear('from_date', $year)
            ->where('status', 'approved');

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->join('leave_settings', 'leaves.leave_type', '=', 'leave_settings.id')
            ->select('leave_settings.name', DB::raw('count(*) as count'))
            ->groupBy('leave_settings.name')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->name,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get absenteeism rate
     */
    protected function getAbsenteeismRate($year, $departmentId = null)
    {
        $workingDays = 260; // Approximate working days in a year

        $query = Leave::whereYear('from_date', $year)
            ->where('status', 'approved');

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $totalLeaveDays = $query->sum('no_of_days');
        $employeeCount = User::when($departmentId, function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })->count();

        if ($employeeCount === 0) {
            return 0;
        }

        return ($totalLeaveDays / ($employeeCount * $workingDays)) * 100;
    }

    /**
     * Get peak leave periods
     */
    protected function getPeakPeriods($year, $departmentId = null)
    {
        $query = Leave::whereYear('from_date', $year)
            ->where('status', 'approved');

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->selectRaw('MONTH(from_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::create(null, $item->month)->format('F'),
                    'count' => $item->count,
                    'reason' => null,
                ];
            });
    }

    /**
     * Get leave balances for a user
     */
    public function getBalances(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $userId = $request->input('user_id', Auth::id());
            $year = $request->input('year', now()->year);

            $user = User::findOrFail($userId);
            $balances = $this->balanceService->getAllBalances($user, $year);

            return response()->json([
                'success' => true,
                'balances' => $balances->map(function ($balance) {
                    return [
                        'id' => $balance->id,
                        'leave_type' => $balance->leaveSetting->name,
                        'leave_type_code' => $balance->leaveSetting->code,
                        'leave_setting_id' => $balance->leave_setting_id,
                        'year' => $balance->year,
                        'allocated' => (float) $balance->allocated,
                        'used' => (float) $balance->used,
                        'pending' => (float) $balance->pending,
                        'available' => (float) $balance->available,
                        'carried_forward' => (float) $balance->carried_forward,
                        'encashed' => (float) $balance->encashed,
                    ];
                }),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch leave balances',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
