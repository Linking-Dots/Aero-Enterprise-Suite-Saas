<?php

namespace Aero\HRM\Http\Controllers\Attendance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\OvertimeRequest;
use Aero\HRM\Services\Attendance\OvertimeApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');

        $requests = OvertimeRequest::query()
            ->with('employee.user:id,name')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Attendance/Overtime/Index', [
            'requests' => $requests,
            'filters' => ['status' => $request->input('status')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');

        return Inertia::render('HRM/Attendance/Overtime/Create');
    }

    public function store(Request $request, OvertimeApprovalService $svc): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');

        $data = $request->validate([
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:12'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee profile.');

        $svc->create([...$data, 'employee_id' => $employee->id]);

        return redirect()->route('hrm.attendance.overtime.index')
            ->with('success', 'Overtime request submitted.');
    }

    public function approve(OvertimeRequest $overtime, OvertimeApprovalService $svc): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.approve');
        $svc->approve($overtime);

        return back()->with('success', 'Overtime approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtime, OvertimeApprovalService $svc): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.approve');
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->reject($overtime, $data['reason']);

        return back()->with('success', 'Overtime rejected.');
    }
}
