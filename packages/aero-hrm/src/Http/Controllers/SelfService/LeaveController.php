<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Http\Requests\SelfService\StoreLeaveRequest;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends SelfServiceController
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function index(): Response
    {
        $employee = $this->employee();

        $applications = LeaveApplication::where('employee_id', $employee->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->paginate(15);

        $leaveTypes = LeaveType::where('is_active', true)->get();

        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->with('leaveType')
            ->get();

        return Inertia::render('HRM/SelfService/Leaves', [
            'applications' => $applications,
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $employee = $this->employee();
        $validated = $request->validated();

        DB::transaction(function () use ($employee, $validated): void {
            $leave = LeaveApplication::create(array_merge($validated, [
                'employee_id' => $employee->id,
                'status' => LeaveApplication::STATUS_PENDING,
            ]));

            $this->audit->log(
                event: SelfServiceAuditEvents::LEAVE_APPLIED,
                action: 'applied',
                subject: $leave,
                description: "Employee #{$employee->id} applied for leave (type: {$validated['leave_type_id']}, {$validated['start_date']} – {$validated['end_date']}).",
            );
        });

        return back();
    }

    public function cancel(LeaveApplication $leave): RedirectResponse
    {
        $employee = $this->employee();

        abort_unless((int) $leave->employee_id === $employee->id, 403, 'Forbidden.');
        abort_unless($leave->status === LeaveApplication::STATUS_PENDING, 422, 'Only pending leaves can be cancelled.');

        DB::transaction(function () use ($leave, $employee): void {
            $leave->update(['status' => LeaveApplication::STATUS_CANCELLED]);

            $this->audit->log(
                event: SelfServiceAuditEvents::LEAVE_CANCELLED,
                action: 'cancelled',
                subject: $leave,
                description: "Employee #{$employee->id} cancelled leave application #{$leave->id}.",
            );
        });

        return back();
    }
}
