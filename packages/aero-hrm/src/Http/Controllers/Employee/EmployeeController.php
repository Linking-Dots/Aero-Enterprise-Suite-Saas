<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\StoreEmployeeRequest;
use Aero\HRM\Http\Requests\UpdateEmployeeRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Services\EmployeeCrudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeCrudService $service,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.employees.list.view');

        $filters = $request->only(['search', 'department_id', 'status', 'employment_type']);

        $employees = Employee::query()
            ->with(['user:id,name,email', 'department:id,name', 'designation:id,title'])
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Employee $e) => [
                'id' => $e->id,
                'employee_code' => $e->employee_code,
                'name' => $e->user?->name,
                'email' => $e->user?->email,
                'department' => $e->department?->name,
                'designation' => $e->designation?->title,
                'employment_type' => $e->employment_type,
                'status' => $e->status,
                'date_of_joining' => optional($e->date_of_joining)->toDateString(),
            ]);

        return Inertia::render('HRM/Employees/Index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'statuses' => ['active', 'probation', 'on_leave', 'terminated', 'resigned'],
            'employmentTypes' => ['full_time', 'part_time', 'contract', 'intern'],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('hrm.employees.list.edit');

        return Inertia::render('HRM/Employees/Create', $this->formProps());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create($request->validated());

        return redirect()
            ->route('hrm.employees.show', $employee)
            ->with('success', "Employee {$employee->employee_code} created.");
    }

    public function show(Request $request, Employee|int|string $employee): Response
    {
        $this->authorize('hrm.employees.detail.view');

        if (! $employee instanceof Employee) {
            $employee = Employee::findOrFail($employee);
        }

        $employee->load(['user', 'department', 'designation', 'manager.user']);

        $canViewBank = $request->user()->can('hrm.employees.bank-details.view');
        $canViewDocs = $request->user()->can('hrm.employees.documents.view');

        $payload = $employee->toArray();

        // Mask PII unless caller has explicit scope
        if (! $canViewBank) {
            $payload['bank_account_number'] = null;
        }
        if (! $request->user()->can('hrm.employees.detail.view')) {
            $payload['passport_no'] = null;
            $payload['emirates_id'] = null;
            $payload['national_id'] = null;
        }

        // Audit sensitive field access
        // Note: AuditEventType::SENSITIVE_VIEWED ('security.sensitive_viewed') is used
        // as SENSITIVE_DATA_ACCESSED does not exist in the enum.
        $sensitiveFieldsViewed = array_values(array_filter([
            $canViewBank ? 'bank_account_number' : null,
            ! empty($payload['passport_no']) ? 'passport_no' : null,
            ! empty($payload['emirates_id']) ? 'emirates_id' : null,
        ]));

        if (! empty($sensitiveFieldsViewed)) {
            $this->audit->log(
                AuditEventType::SENSITIVE_VIEWED->value,
                'viewed',
                $employee,
                "Sensitive fields accessed on employee {$employee->employee_code}",
                null,
                null,
                ['fields' => $sensitiveFieldsViewed],
            );
        }

        return Inertia::render('HRM/Employees/Show', [
            'employee' => $payload,
            'permissions' => [
                'canEdit' => $request->user()->can('hrm.employees.detail.edit'),
                'canViewBank' => $canViewBank,
                'canEditBank' => $request->user()->can('hrm.employees.bank-details.edit'),
                'canViewDocs' => $canViewDocs,
            ],
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('hrm.employees.detail.edit');

        return Inertia::render('HRM/Employees/Edit', array_merge($this->formProps(), [
            'employee' => $employee->load(['user', 'department', 'designation', 'manager.user']),
        ]));
    }

    public function update(UpdateEmployeeRequest $request, Employee|int|string $employee): RedirectResponse
    {
        if (! $employee instanceof Employee) {
            $employee = Employee::findOrFail($employee);
        }
        $employee = $this->service->update($employee, $request->validated());

        return redirect()
            ->route('hrm.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    public function destroy(Employee|int|string $employee): RedirectResponse
    {
        $this->authorize('hrm.employees.detail.edit');
        if (! $employee instanceof Employee) {
            $employee = Employee::findOrFail($employee);
        }
        $this->service->delete($employee);

        return redirect()
            ->route('hrm.employees.index')
            ->with('success', 'Employee deleted.');
    }

    public function restore(int $employee): RedirectResponse
    {
        $this->authorize('hrm.employees.detail.edit');
        $restored = $this->service->restore($employee);

        return redirect()
            ->route('hrm.employees.show', $restored)
            ->with('success', 'Employee restored.');
    }

    private function formProps(): array
    {
        return [
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'designations' => Designation::query()->select('id', 'title')->orderBy('title')->get(),
            'managers' => Employee::query()
                ->with('user:id,name')
                ->select('id', 'user_id', 'employee_code')
                ->orderBy('employee_code')
                ->get()
                ->map(fn (Employee $e) => [
                    'id' => $e->id,
                    'label' => "{$e->employee_code} — ".($e->user?->name ?? '—'),
                ]),
            'statuses' => ['active', 'probation', 'on_leave', 'terminated', 'resigned'],
            'employmentTypes' => ['full_time', 'part_time', 'contract', 'intern'],
        ];
    }
}
