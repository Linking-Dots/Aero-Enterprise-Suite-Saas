<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Payroll;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Concerns\ProvidesPayrollRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\PayrollRun;
use Aero\HRM\Services\Payroll\PayrollApprovalService;
use Aero\HRM\Services\Payroll\PayrollRunGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use ProvidesPayrollRailStats;

    public function __construct(
        private readonly PayrollRunGenerator $generator,
        private readonly PayrollApprovalService $approvalService,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.view');

        $status = $request->string('status')->toString() ?: null;

        $runs = PayrollRun::query()
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PayrollRun $r) => [
                'id'           => $r->id,
                'label'        => $r->label,
                'period_start' => $r->period_start->toDateString(),
                'period_end'   => $r->period_end->toDateString(),
                'status'       => $r->status,
                'total_gross'  => $r->total_gross,
                'total_net'    => $r->total_net,
                'is_locked'    => $r->isLocked(),
            ]);

        return Inertia::render('HRM/Payroll/Runs/Index', [
            'runs' => $runs,
            'filters' => ['status' => $status],
            'stats' => [
                'total'    => PayrollRun::count(),
                'approved' => PayrollRun::where('status', PayrollRun::STATUS_APPROVED)->count(),
                'net_paid' => (float) PayrollRun::where('status', PayrollRun::STATUS_APPROVED)->sum('total_net'),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.execute');

        $employees = Employee::query()
            ->with('user:id,name')
            ->where('status', 'active')
            ->select('id', 'user_id', 'employee_code')
            ->get()
            ->map(fn (Employee $e) => [
                'id'            => $e->id,
                'employee_code' => $e->employee_code,
                'name'          => $e->user?->name,
            ]);

        return Inertia::render('HRM/Payroll/Runs/Create', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.execute');

        $data = $request->validate([
            'label'            => ['required', 'string', 'max:100'],
            'period_start'     => ['required', 'date'],
            'period_end'       => ['required', 'date', 'after_or_equal:period_start'],
            'employee_ids'     => ['required', 'array', 'min:1'],
            'employee_ids.*'   => ['integer', Rule::exists('employees', 'id')],
        ]);

        $run = $this->generator->create(
            payload: [
                'label'        => $data['label'],
                'period_start' => $data['period_start'],
                'period_end'   => $data['period_end'],
            ],
            employeeIds: $data['employee_ids'],
        );

        return redirect()->route('hrm.payroll.runs.show', $run);
    }

    public function show(): Response
    {
        $run = PayrollRun::findOrFail(request()->route('run'));
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.view');

        $run->load(['payslips.employee.user:id,name']);

        $this->audit->logAccess(
            resourceType: 'payroll_run',
            resourceId: $run->id,
            subjectLabel: $run->label,
            fields: ['total_gross', 'total_net', 'payslips'],
        );

        return Inertia::render('HRM/Payroll/Runs/Show', [
            'run'      => [
                'id'           => $run->id,
                'label'        => $run->label,
                'period_start' => $run->period_start->toDateString(),
                'period_end'   => $run->period_end->toDateString(),
                'status'       => $run->status,
                'total_gross'  => $run->total_gross,
                'total_net'    => $run->total_net,
                'is_locked'    => $run->isLocked(),
                'approved_at'  => $run->approved_at?->toIso8601String(),
            ],
            'payslips' => $run->payslips->map(fn ($slip) => [
                'id'               => $slip->id,
                'employee_id'      => $slip->employee_id,
                'employee_name'    => $slip->employee?->user?->name,
                'employee_code'    => $slip->employee?->employee_code,
                'gross'            => $slip->gross,
                'tax'              => $slip->tax,
                'deductions_total' => $slip->deductions_total,
                'net'              => $slip->net,
            ]),
            'stats' => $this->payrollRailStats(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $run = PayrollRun::findOrFail($request->route('run'));
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.execute');

        if ($run->isLocked()) {
            abort(403, 'This payroll run is locked and cannot be modified.');
        }

        $data = $request->validate([
            'label'        => ['required', 'string', 'max:100'],
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after_or_equal:period_start'],
            'status'       => ['required', Rule::in([PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_REVIEW])],
        ]);

        DB::transaction(function () use ($run, $data): void {
            $before = $run->only(['label', 'period_start', 'period_end', 'status']);
            $run->fill($data)->save();

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'updated',
                subject: $run,
                description: "Payroll run '{$run->label}' updated.",
                before: $before,
                after: $run->only(['label', 'period_start', 'period_end', 'status']),
            );
        });

        return back();
    }

    public function approve(Request $request): RedirectResponse
    {
        $run = PayrollRun::findOrFail($request->route('run'));
        Gate::authorize('hrmac', 'hrm.payroll.payroll-run.lock');

        $this->approvalService->approve($run);

        return redirect()->route('hrm.payroll.runs.show', $run);
    }
}
