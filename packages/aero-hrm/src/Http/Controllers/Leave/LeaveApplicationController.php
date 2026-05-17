<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\RejectLeaveRequest;
use Aero\HRM\Http\Requests\Leave\StoreLeaveApplicationRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveType;
use Aero\HRM\Services\LeaveApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApplicationController extends Controller
{
    public function __construct(private readonly LeaveApplicationService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.leaves.leave-requests.view');

        $filters = $request->only(['status', 'leave_type_id', 'from', 'to']);

        $applications = LeaveApplication::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['leave_type_id'] ?? null, fn ($q, $id) => $q->where('leave_type_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('end_date', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Leave/Applications/Index', [
            'applications' => $applications,
            'leaveTypes' => LeaveType::select('id', 'name')->get(),
            'statuses' => ['pending', 'approved', 'rejected', 'cancelled'],
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Leave/Applications/Create', [
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::with('user:id,name')->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => ($e->user?->name ?? '—').' ('.$e->employee_code.')']),
        ]);
    }

    public function store(StoreLeaveApplicationRequest $request): RedirectResponse
    {
        $app = $this->service->create($request->validated());

        return redirect()->route('hrm.leave.applications.show', $app)
            ->with('success', 'Leave application submitted.');
    }

    public function show(LeaveApplication $application): Response
    {
        $this->authorize('hrm.leaves.leave-requests.view');
        $application->load(['employee.user', 'leaveType', 'approver', 'rejector']);

        return Inertia::render('HRM/Leave/Applications/Show', [
            'application' => $application,
            'permissions' => [
                'canApprove' => request()->user()->can('hrm.leaves.leave-requests.approve'),
            ],
        ]);
    }

    public function approve(LeaveApplication $application): RedirectResponse
    {
        abort_unless(request()->user()->can('hrm.leaves.leave-requests.approve'), 403);
        $this->service->approve($application);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(RejectLeaveRequest $request, LeaveApplication $application): RedirectResponse
    {
        $this->service->reject($application, $request->string('reason')->toString());

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(LeaveApplication $application): RedirectResponse
    {
        abort_unless(
            $application->employee->user_id === request()->user()->id
                || request()->user()->can('hrm.leaves.leave-requests.update'),
            403,
        );
        $this->service->cancel($application);

        return back()->with('success', 'Leave cancelled.');
    }
}
