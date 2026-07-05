<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Concerns\ProvidesLeaveRailStats;
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
    use ProvidesLeaveRailStats;

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

        $statusCounts = LeaveApplication::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return Inertia::render('HRM/Leave/Applications/Index', [
            'applications' => $applications,
            'leaveTypes' => LeaveType::select('id', 'name')->get(),
            'statuses' => ['pending', 'approved', 'rejected', 'cancelled'],
            'filters' => $filters,
            'stats' => [
                'total'    => (int) $statusCounts->sum(),
                'pending'  => (int) ($statusCounts['pending'] ?? 0),
                'approved' => (int) ($statusCounts['approved'] ?? 0),
                'rejected' => (int) ($statusCounts['rejected'] ?? 0),
            ],
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

    public function show(): Response
    {
        $application = LeaveApplication::findOrFail(request()->route('application'));
        $this->authorize('hrm.leaves.leave-requests.view');
        $application->load(['employee.user', 'leaveType', 'approver', 'rejector']);

        return Inertia::render('HRM/Leave/Applications/Show', [
            'application' => [
                'id'               => $application->id,
                'employee_name'    => $application->employee?->user?->name,
                'employee_code'    => $application->employee?->employee_code,
                'leave_type'       => $application->leaveType?->name,
                'leave_type_color' => $application->leaveType?->color,
                'start_date'       => $application->start_date?->toDateString(),
                'end_date'         => $application->end_date?->toDateString(),
                'total_days'       => (float) $application->total_days,
                'status'           => $application->status,
                'reason'           => $application->reason,
                'rejection_reason' => $application->rejection_reason,
                'created_at'       => $application->created_at?->toDateString(),
                'approver_name'    => $application->approver?->name,
                'rejector_name'    => $application->rejector?->name,
            ],
            'permissions' => [
                'canApprove' => request()->user()->can('hrm.leaves.leave-requests.approve'),
            ],
            'stats' => $this->leaveRailStats(),
        ]);
    }

    public function approve(): RedirectResponse
    {
        $application = LeaveApplication::findOrFail(request()->route('application'));
        abort_unless(request()->user()->can('hrm.leaves.leave-requests.approve'), 403);
        $this->service->approve($application);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(RejectLeaveRequest $request): RedirectResponse
    {
        $application = LeaveApplication::findOrFail($request->route('application'));
        $this->service->reject($application, $request->string('reason')->toString());

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(): RedirectResponse
    {
        $application = LeaveApplication::findOrFail(request()->route('application'));
        abort_unless(
            $application->employee->user_id === request()->user()->id
                || request()->user()->can('hrm.leaves.leave-requests.update'),
            403,
        );
        $this->service->cancel($application);

        return back()->with('success', 'Leave cancelled.');
    }
}
