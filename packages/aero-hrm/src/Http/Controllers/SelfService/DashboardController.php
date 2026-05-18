<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\TrainingEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends SelfServiceController
{
    public function index(Request $request): Response
    {
        $employee = $this->employee();

        $latestPayslip = Payslip::where('employee_id', $employee->id)
            ->latest('period_end')
            ->first();

        $pendingLeavesCount = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', LeaveApplication::STATUS_PENDING)
            ->count();

        $upcomingTrainings = TrainingEnrollment::where('employee_id', $employee->id)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', now()))
            ->with('session.course')
            ->limit(5)
            ->get();

        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->with('leaveType:id,name')
            ->get();

        return Inertia::render('HRM/SelfService/Dashboard', [
            'employee' => $employee->load(['user:id,name,email', 'department:id,name', 'designation:id,title']),
            'latestPayslip' => $latestPayslip,
            'pendingLeavesCount' => $pendingLeavesCount,
            'upcomingTrainings' => $upcomingTrainings,
            'leaveBalances' => $leaveBalances,
        ]);
    }
}
