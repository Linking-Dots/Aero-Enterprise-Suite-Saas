<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Services\EmployeeDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeDashboardController extends Controller
{
    public function __construct(
        protected EmployeeDashboardService $dashboardService,
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)
            ->with(['department', 'designation', 'user', 'managerEmployee.user'])
            ->first();

        if (! $employee) {
            return Inertia::render('HRM/Employee/Dashboard', [
                'title' => 'My Dashboard',
                'employee' => null,
                'dynamicWidgets' => [],
            ]);
        }

        // Immediate props (critical for initial render)
        $attendanceData = $this->dashboardService->getAttendanceData($employee);
        $leaveData = $this->dashboardService->getLeaveData($employee);
        $quickActions = $this->dashboardService->getQuickActions($employee);
        $profile = $this->dashboardService->getEmployeeProfile($employee);
        $holidayData = $this->dashboardService->getHolidayAndEventData();

        // Deferred props (loaded after initial render)
        return Inertia::render('HRM/Employee/Dashboard', [
            'title' => 'My Dashboard',
            'employee' => $profile,
            'quickActions' => $quickActions,
            'dynamicWidgets' => [],

            // Immediate data
            ...$attendanceData,
            ...$leaveData,
            ...$holidayData,

            // Deferred props for heavier sections
            'payrollData' => Inertia::defer(fn () => $this->dashboardService->getPayrollData($employee)),
            'performanceData' => Inertia::defer(fn () => $this->dashboardService->getPerformanceData($employee)),
            'trainingData' => Inertia::defer(fn () => $this->dashboardService->getTrainingData($employee)),
            'expenseData' => Inertia::defer(fn () => $this->dashboardService->getExpenseData($employee)),
            'assetData' => Inertia::defer(fn () => $this->dashboardService->getAssetData($employee)),
            'documentData' => Inertia::defer(fn () => $this->dashboardService->getDocumentData($employee)),
            'careerData' => Inertia::defer(fn () => $this->dashboardService->getCareerData($employee)),
            'feedbackData' => Inertia::defer(fn () => $this->dashboardService->getFeedbackData($employee)),
            'onboardingData' => Inertia::defer(fn () => $this->dashboardService->getOnboardingData($employee)),
            'teamData' => Inertia::defer(fn () => $this->dashboardService->getTeamData($employee)),
            'overtimeData' => Inertia::defer(fn () => $this->dashboardService->getOvertimeData($employee)),
            'grievanceData' => Inertia::defer(fn () => $this->dashboardService->getGrievanceData($employee)),
            'surveyData' => Inertia::defer(fn () => $this->dashboardService->getSurveyData($employee)),
            'benefitData' => Inertia::defer(fn () => $this->dashboardService->getBenefitData($employee)),
            'managerApprovals' => Inertia::defer(fn () => $this->dashboardService->getManagerApprovals($employee)),
        ]);
    }

    public function attendanceChart(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        return response()->json($this->dashboardService->getAttendanceData($employee));
    }
}
