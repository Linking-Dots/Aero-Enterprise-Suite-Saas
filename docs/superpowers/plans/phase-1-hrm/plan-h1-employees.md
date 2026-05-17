# Plan H-1 — Employee CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Employee CRUD surface (list, create, edit, profile with 5 tabs, soft delete, restore) wired to HRMAC, AuditService, EncryptedField PII casts, and the `@aero/ui` Inertia layout.

**Architecture:** Thin Inertia controller (`EmployeeController`) delegates listing, persistence, and side-effects to an `EmployeeService` action class while keeping a single `Employee` Eloquent model (extending `TenantModel`) as the source of truth. PII fields (`passport_no`, `visa_no`, `emirates_id`, `national_id`, `bank_account_number`) are encrypted at rest via `EncryptedField` and masked in Inertia props when the caller lacks the corresponding HRMAC scope. React pages live in `packages/aero-ui/resources/js/Pages/HRM/Employees/` and use the shared `App` layout plus HeroUI primitives.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migration: encrypted PII columns + soft delete

**Files:**
- Create: `packages/aero-hrm/database/migrations/2026_05_17_000001_add_pii_and_softdeletes_to_employees_table.php`

- [ ] Generate migration that widens PII columns to `text` (encrypted ciphertext is longer than plaintext) and adds `deleted_at` + `national_id` + `bank_account_number` if missing:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('employees', 'national_id')) {
                $table->text('national_id')->nullable()->after('emirates_id');
            }
            if (!Schema::hasColumn('employees', 'bank_account_number')) {
                $table->text('bank_account_number')->nullable()->after('basic_salary');
            }
            // Widen existing PII columns to TEXT for encrypted payloads
            foreach (['passport_no', 'visa_no', 'emirates_id'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->text($col)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('employees', 'national_id')) {
                $table->dropColumn('national_id');
            }
            if (Schema::hasColumn('employees', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
        });
    }
};
```

- [ ] Run migration against the test sqlite to verify it boots:
```bash
cd packages/aero-hrm && ../../vendor/bin/phpunit --filter=DummyMigrationBoot --bootstrap=tests/bootstrap.php
```

- [ ] Commit:
```bash
git add packages/aero-hrm/database/migrations/2026_05_17_000001_add_pii_and_softdeletes_to_employees_table.php
git commit -m "feat(aero-hrm): widen PII columns + soft delete on employees"
```

---

## Task 2 — Model: EncryptedField casts + scopes

**Files:**
- Modify: `packages/aero-hrm/src/Models/Employee.php`

- [ ] Add `EncryptedField` casts and helpful scopes. Insert (or replace) the `$casts` block and add `scopeFilter`:
```php
use Aero\Core\Encryption\EncryptedField;
use Illuminate\Database\Eloquent\Builder;

// inside class Employee
protected $casts = [
    'date_of_joining'       => 'date',
    'date_of_leaving'       => 'date',
    'probation_end_date'    => 'date',
    'confirmation_date'     => 'date',
    'contract_start_date'   => 'date',
    'contract_end_date'     => 'date',
    'date_of_birth'         => 'date',
    'passport_expiry'       => 'date',
    'visa_expiry'           => 'date',
    'emirates_id_expiry'    => 'date',
    'basic_salary'          => 'decimal:2',

    // PII — encrypted at rest
    'passport_no'           => EncryptedField::class,
    'visa_no'               => EncryptedField::class,
    'emirates_id'           => EncryptedField::class,
    'national_id'           => EncryptedField::class,
    'bank_account_number'   => EncryptedField::class,
];

public function scopeFilter(Builder $query, array $filters): Builder
{
    return $query
        ->when($filters['search'] ?? null, function (Builder $q, string $term) {
            $q->where(function (Builder $sub) use ($term) {
                $sub->where('employee_code', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $u) => $u
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            });
        })
        ->when($filters['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
        ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
        ->when($filters['employment_type'] ?? null, fn (Builder $q, $t) => $q->where('employment_type', $t));
}
```

- [ ] Add `bank_account_number` and `national_id` to `$fillable`.

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Models/Employee.php
git commit -m "feat(aero-hrm): encrypt PII fields + filterable scope on Employee"
```

---

## Task 3 — Form Requests + Service

**Files:**
- Create: `packages/aero-hrm/src/Http/Requests/StoreEmployeeRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/UpdateEmployeeRequest.php`
- Create: `packages/aero-hrm/src/Services/EmployeeService.php`

- [ ] Write `StoreEmployeeRequest`:
```php
<?php

namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.employees.list.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'           => ['required', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')],
            'department_id'     => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id'    => ['nullable', 'integer', 'exists:designations,id'],
            'manager_id'        => ['nullable', 'integer', 'exists:employees,id'],
            'employee_code'     => ['required', 'string', 'max:32', Rule::unique('employees', 'employee_code')],
            'date_of_joining'   => ['required', 'date'],
            'employment_type'   => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'status'            => ['required', Rule::in(['active', 'probation', 'on_leave', 'terminated', 'resigned'])],
            'basic_salary'      => ['required', 'numeric', 'min:0'],
            'work_location'     => ['nullable', 'string', 'max:120'],
            'shift'             => ['nullable', 'string', 'max:120'],
            'passport_no'       => ['nullable', 'string', 'max:64'],
            'visa_no'           => ['nullable', 'string', 'max:64'],
            'emirates_id'       => ['nullable', 'string', 'max:32'],
            'national_id'       => ['nullable', 'string', 'max:32'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

- [ ] Write `UpdateEmployeeRequest` (same shape, but `unique` rules ignore the route-bound employee id and `user_id` is optional):
```php
<?php

namespace Aero\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.employees.detail.edit') ?? false;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'department_id'     => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id'    => ['nullable', 'integer', 'exists:designations,id'],
            'manager_id'        => ['nullable', 'integer', 'different:employee', 'exists:employees,id'],
            'employee_code'     => ['required', 'string', 'max:32', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'date_of_joining'   => ['required', 'date'],
            'employment_type'   => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'status'            => ['required', Rule::in(['active', 'probation', 'on_leave', 'terminated', 'resigned'])],
            'basic_salary'      => ['required', 'numeric', 'min:0'],
            'work_location'     => ['nullable', 'string', 'max:120'],
            'shift'             => ['nullable', 'string', 'max:120'],
            'passport_no'       => ['nullable', 'string', 'max:64'],
            'visa_no'           => ['nullable', 'string', 'max:64'],
            'emirates_id'       => ['nullable', 'string', 'max:32'],
            'national_id'       => ['nullable', 'string', 'max:32'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

- [ ] Write `EmployeeService` (audit-aware mutations):
```php
<?php

namespace Aero\HRM\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Employee;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create($data);

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $employee,
                description: "Employee {$employee->employee_code} created",
                after: $employee->only(['employee_code', 'department_id', 'designation_id', 'status']),
            );

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $before = $employee->only(array_keys($data));
            $employee->fill($data)->save();

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'updated',
                subject: $employee,
                description: "Employee {$employee->employee_code} updated",
                before: $before,
                after: $employee->only(array_keys($data)),
            );

            return $employee->fresh();
        });
    }

    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->delete();

            $this->audit->log(
                event: AuditEventType::RECORD_DELETED->value,
                action: 'deleted',
                subject: $employee,
                description: "Employee {$employee->employee_code} soft-deleted",
            );
        });
    }

    public function restore(int $employeeId): Employee
    {
        return DB::transaction(function () use ($employeeId) {
            /** @var Employee $employee */
            $employee = Employee::withTrashed()->findOrFail($employeeId);
            $employee->restore();

            $this->audit->log(
                event: AuditEventType::RECORD_RESTORED->value,
                action: 'restored',
                subject: $employee,
                description: "Employee {$employee->employee_code} restored",
            );

            return $employee;
        });
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Requests/StoreEmployeeRequest.php \
       packages/aero-hrm/src/Http/Requests/UpdateEmployeeRequest.php \
       packages/aero-hrm/src/Services/EmployeeService.php
git commit -m "feat(aero-hrm): EmployeeService + form requests with HRMAC authorize"
```

---

## Task 4 — Controller rewrite + Routes + HRMAC

**Files:**
- Modify: `packages/aero-hrm/src/Http/Controllers/Employee/EmployeeController.php`
- Modify: `packages/aero-hrm/routes/tenant.php`
- Modify: `packages/aero-hrm/config/module.php` (HRMAC paths)

- [ ] Replace `EmployeeController` entirely with the Inertia controller:
```php
<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\StoreEmployeeRequest;
use Aero\HRM\Http\Requests\UpdateEmployeeRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
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
                'id'              => $e->id,
                'employee_code'   => $e->employee_code,
                'name'            => $e->user?->name,
                'email'           => $e->user?->email,
                'department'      => $e->department?->name,
                'designation'     => $e->designation?->title,
                'employment_type' => $e->employment_type,
                'status'          => $e->status,
                'date_of_joining' => optional($e->date_of_joining)->toDateString(),
            ]);

        return Inertia::render('HRM/Employees/Index', [
            'employees'   => $employees,
            'filters'     => $filters,
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'statuses'    => ['active', 'probation', 'on_leave', 'terminated', 'resigned'],
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

    public function show(Request $request, Employee $employee): Response
    {
        $this->authorize('hrm.employees.detail.view');

        $employee->load(['user', 'department', 'designation', 'manager.user']);

        $canViewBank = $request->user()->can('hrm.employees.bank-details.view');
        $canViewDocs = $request->user()->can('hrm.employees.documents.view');

        // Mask PII unless caller has explicit scope
        $payload = $employee->toArray();
        if (! $canViewBank) {
            $payload['bank_account_number'] = null;
        }
        if (! $request->user()->can('hrm.employees.detail.view')) {
            $payload['passport_no']  = null;
            $payload['emirates_id']  = null;
            $payload['national_id']  = null;
        }

        // Audit any sensitive field access actually returned
        $viewed = array_filter([
            $canViewBank ? 'bank_account_number' : null,
            $payload['passport_no'] ? 'passport_no' : null,
            $payload['emirates_id'] ? 'emirates_id' : null,
        ]);
        if ($viewed) {
            $this->audit->logAccess(
                resourceType: 'employee',
                resourceId: $employee->id,
                subjectLabel: $employee->user?->name,
                fields: array_values($viewed),
            );
        }

        return Inertia::render('HRM/Employees/Show', [
            'employee'      => $payload,
            'permissions'   => [
                'canEdit'        => $request->user()->can('hrm.employees.detail.edit'),
                'canViewBank'    => $canViewBank,
                'canEditBank'    => $request->user()->can('hrm.employees.bank-details.edit'),
                'canViewDocs'    => $canViewDocs,
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

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee = $this->service->update($employee, $request->validated());

        return redirect()
            ->route('hrm.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('hrm.employees.detail.edit');
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
            'departments'     => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'designations'    => Designation::query()->select('id', 'title')->orderBy('title')->get(),
            'managers'        => Employee::query()
                ->with('user:id,name')
                ->select('id', 'user_id', 'employee_code')
                ->orderBy('employee_code')
                ->get()
                ->map(fn (Employee $e) => [
                    'id'    => $e->id,
                    'label' => "{$e->employee_code} — ".($e->user?->name ?? '—'),
                ]),
            'statuses'        => ['active', 'probation', 'on_leave', 'terminated', 'resigned'],
            'employmentTypes' => ['full_time', 'part_time', 'contract', 'intern'],
        ];
    }
}
```

- [ ] Add routes to `packages/aero-hrm/routes/tenant.php`:
```php
use Aero\HRM\Http\Controllers\Employee\EmployeeController;

Route::middleware(['auth', 'tenant'])->prefix('hrm')->name('hrm.')->group(function () {
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/',           [EmployeeController::class, 'index'])->middleware('hrmac:hrm.employees.list.view')->name('index');
        Route::get('/create',     [EmployeeController::class, 'create'])->middleware('hrmac:hrm.employees.list.edit')->name('create');
        Route::post('/',          [EmployeeController::class, 'store'])->middleware('hrmac:hrm.employees.list.edit')->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->middleware('hrmac:hrm.employees.detail.view')->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('hrmac:hrm.employees.detail.edit')->name('edit');
        Route::put('/{employee}',     [EmployeeController::class, 'update'])->middleware('hrmac:hrm.employees.detail.edit')->name('update');
        Route::delete('/{employee}',  [EmployeeController::class, 'destroy'])->middleware('hrmac:hrm.employees.detail.edit')->name('destroy');
        Route::post('/{employee}/restore', [EmployeeController::class, 'restore'])->middleware('hrmac:hrm.employees.detail.edit')->name('restore')->withTrashed();
    });
});
```

- [ ] Register HRMAC nodes in `packages/aero-hrm/config/module.php` under the `employees` submodule:
```php
'employees' => [
    'label' => 'Employees',
    'components' => [
        'list'         => ['actions' => ['view', 'edit']],
        'detail'       => ['actions' => ['view', 'edit']],
        'bank-details' => ['actions' => ['view', 'edit']],
        'documents'    => ['actions' => ['view', 'edit']],
    ],
],
```

- [ ] Run static analysis on the changed files:
```bash
./vendor/bin/phpstan analyse packages/aero-hrm/src/Http/Controllers/Employee/EmployeeController.php packages/aero-hrm/src/Services/EmployeeService.php --level=6
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Employee/EmployeeController.php \
       packages/aero-hrm/routes/tenant.php \
       packages/aero-hrm/config/module.php
git commit -m "feat(aero-hrm): rewrite EmployeeController with HRMAC + audit + PII masking"
```

---

## Task 5 — React pages: Index + Create + Edit

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/Employees/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Employees/Create.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Employees/Edit.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Employees/_form.jsx` (shared form)

- [ ] Write the list page `Index.jsx`:
```jsx
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../App.jsx';
import {
  Card, CardBody, CardHeader,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Input, Select, SelectItem, Button, Chip, Pagination,
} from '@aero/ui';

export default function EmployeesIndex({ employees, filters, departments, statuses, employmentTypes }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [departmentId, setDepartmentId] = useState(filters.department_id ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [employmentType, setEmploymentType] = useState(filters.employment_type ?? '');

  const apply = () =>
    router.get(route('hrm.employees.index'), {
      search: search || undefined,
      department_id: departmentId || undefined,
      status: status || undefined,
      employment_type: employmentType || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });

  return (
    <>
      <Head title="Employees" />
      <Card>
        <CardHeader className="flex justify-between items-center">
          <h1 className="text-2xl font-semibold">Employees</h1>
          <Button as={Link} href={route('hrm.employees.create')} color="primary">New Employee</Button>
        </CardHeader>
        <CardBody className="gap-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <Input label="Search" value={search} onValueChange={setSearch} onBlur={apply} placeholder="Name, code, email" />
            <Select label="Department" selectedKeys={departmentId ? [String(departmentId)] : []}
                    onSelectionChange={(k) => { setDepartmentId([...k][0] ?? ''); apply(); }}>
              <SelectItem key="">All</SelectItem>
              {departments.map(d => <SelectItem key={d.id}>{d.name}</SelectItem>)}
            </Select>
            <Select label="Status" selectedKeys={status ? [status] : []}
                    onSelectionChange={(k) => { setStatus([...k][0] ?? ''); apply(); }}>
              <SelectItem key="">All</SelectItem>
              {statuses.map(s => <SelectItem key={s}>{s}</SelectItem>)}
            </Select>
            <Select label="Employment Type" selectedKeys={employmentType ? [employmentType] : []}
                    onSelectionChange={(k) => { setEmploymentType([...k][0] ?? ''); apply(); }}>
              <SelectItem key="">All</SelectItem>
              {employmentTypes.map(t => <SelectItem key={t}>{t}</SelectItem>)}
            </Select>
          </div>

          <Table aria-label="Employees">
            <TableHeader>
              <TableColumn>Code</TableColumn>
              <TableColumn>Name</TableColumn>
              <TableColumn>Department</TableColumn>
              <TableColumn>Designation</TableColumn>
              <TableColumn>Type</TableColumn>
              <TableColumn>Status</TableColumn>
              <TableColumn>Joined</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No employees match the current filters.">
              {employees.data.map(e => (
                <TableRow key={e.id}>
                  <TableCell>{e.employee_code}</TableCell>
                  <TableCell>{e.name}</TableCell>
                  <TableCell>{e.department ?? '—'}</TableCell>
                  <TableCell>{e.designation ?? '—'}</TableCell>
                  <TableCell>{e.employment_type}</TableCell>
                  <TableCell><Chip size="sm">{e.status}</Chip></TableCell>
                  <TableCell>{e.date_of_joining ?? '—'}</TableCell>
                  <TableCell>
                    <Button size="sm" as={Link} href={route('hrm.employees.show', e.id)} variant="light">View</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          <div className="flex justify-end">
            <Pagination
              total={employees.last_page}
              page={employees.current_page}
              onChange={(p) => router.get(route('hrm.employees.index'), { ...filters, page: p }, { preserveState: true })}
            />
          </div>
        </CardBody>
      </Card>
    </>
  );
}

EmployeesIndex.layout = page => <App title="Employees">{page}</App>;
```

- [ ] Write shared form `_form.jsx`:
```jsx
import { Input, Select, SelectItem, Textarea, Button, Card, CardBody, Tabs, Tab } from '@aero/ui';

export default function EmployeeForm({ data, setData, errors, processing, submit, departments, designations, managers, statuses, employmentTypes, mode = 'create' }) {
  return (
    <form onSubmit={submit} className="space-y-6">
      <Tabs aria-label="Employee fields">
        <Tab key="employment" title="Employment">
          <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input label="Employee Code" value={data.employee_code} onValueChange={(v) => setData('employee_code', v)} errorMessage={errors.employee_code} />
            <Input type="date" label="Date of Joining" value={data.date_of_joining ?? ''} onValueChange={(v) => setData('date_of_joining', v)} errorMessage={errors.date_of_joining} />
            <Select label="Department" selectedKeys={data.department_id ? [String(data.department_id)] : []}
                    onSelectionChange={(k) => setData('department_id', [...k][0] ?? null)} errorMessage={errors.department_id}>
              {departments.map(d => <SelectItem key={d.id}>{d.name}</SelectItem>)}
            </Select>
            <Select label="Designation" selectedKeys={data.designation_id ? [String(data.designation_id)] : []}
                    onSelectionChange={(k) => setData('designation_id', [...k][0] ?? null)} errorMessage={errors.designation_id}>
              {designations.map(d => <SelectItem key={d.id}>{d.title}</SelectItem>)}
            </Select>
            <Select label="Manager" selectedKeys={data.manager_id ? [String(data.manager_id)] : []}
                    onSelectionChange={(k) => setData('manager_id', [...k][0] ?? null)} errorMessage={errors.manager_id}>
              {managers.map(m => <SelectItem key={m.id}>{m.label}</SelectItem>)}
            </Select>
            <Select label="Employment Type" selectedKeys={data.employment_type ? [data.employment_type] : []}
                    onSelectionChange={(k) => setData('employment_type', [...k][0])} errorMessage={errors.employment_type}>
              {employmentTypes.map(t => <SelectItem key={t}>{t}</SelectItem>)}
            </Select>
            <Select label="Status" selectedKeys={data.status ? [data.status] : []}
                    onSelectionChange={(k) => setData('status', [...k][0])} errorMessage={errors.status}>
              {statuses.map(s => <SelectItem key={s}>{s}</SelectItem>)}
            </Select>
            <Input type="number" step="0.01" label="Basic Salary" value={data.basic_salary ?? ''} onValueChange={(v) => setData('basic_salary', v)} errorMessage={errors.basic_salary} />
            <Input label="Work Location" value={data.work_location ?? ''} onValueChange={(v) => setData('work_location', v)} errorMessage={errors.work_location} />
            <Input label="Shift" value={data.shift ?? ''} onValueChange={(v) => setData('shift', v)} errorMessage={errors.shift} />
          </CardBody></Card>
        </Tab>
        <Tab key="identity" title="Identity (PII)">
          <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input label="Passport No"   value={data.passport_no ?? ''}   onValueChange={(v) => setData('passport_no', v)}   errorMessage={errors.passport_no} />
            <Input label="Visa No"       value={data.visa_no ?? ''}       onValueChange={(v) => setData('visa_no', v)}       errorMessage={errors.visa_no} />
            <Input label="Emirates ID"   value={data.emirates_id ?? ''}   onValueChange={(v) => setData('emirates_id', v)}   errorMessage={errors.emirates_id} />
            <Input label="National ID"   value={data.national_id ?? ''}   onValueChange={(v) => setData('national_id', v)}   errorMessage={errors.national_id} />
            <Input label="Bank Account"  value={data.bank_account_number ?? ''} onValueChange={(v) => setData('bank_account_number', v)} errorMessage={errors.bank_account_number} />
          </CardBody></Card>
        </Tab>
        <Tab key="notes" title="Notes">
          <Card><CardBody>
            <Textarea label="Notes" value={data.notes ?? ''} onValueChange={(v) => setData('notes', v)} errorMessage={errors.notes} />
          </CardBody></Card>
        </Tab>
      </Tabs>

      <div className="flex justify-end gap-2">
        <Button type="submit" color="primary" isLoading={processing}>
          {mode === 'create' ? 'Create Employee' : 'Save Changes'}
        </Button>
      </div>
    </form>
  );
}
```

- [ ] Write `Create.jsx`:
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../App.jsx';
import EmployeeForm from './_form.jsx';

export default function CreateEmployee({ departments, designations, managers, statuses, employmentTypes }) {
  const { data, setData, post, processing, errors } = useForm({
    user_id: null,
    employee_code: '',
    date_of_joining: '',
    department_id: null,
    designation_id: null,
    manager_id: null,
    employment_type: 'full_time',
    status: 'active',
    basic_salary: 0,
    work_location: '',
    shift: '',
    passport_no: '',
    visa_no: '',
    emirates_id: '',
    national_id: '',
    bank_account_number: '',
    notes: '',
  });

  const submit = (e) => { e.preventDefault(); post(route('hrm.employees.store')); };

  return (
    <>
      <Head title="New Employee" />
      <h1 className="text-2xl font-semibold mb-4">New Employee</h1>
      <EmployeeForm
        mode="create"
        data={data} setData={setData} errors={errors} processing={processing}
        submit={submit}
        departments={departments} designations={designations} managers={managers}
        statuses={statuses} employmentTypes={employmentTypes}
      />
    </>
  );
}
CreateEmployee.layout = page => <App title="New Employee">{page}</App>;
```

- [ ] Write `Edit.jsx`:
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../App.jsx';
import EmployeeForm from './_form.jsx';

export default function EditEmployee({ employee, departments, designations, managers, statuses, employmentTypes }) {
  const { data, setData, put, processing, errors } = useForm({
    employee_code: employee.employee_code,
    date_of_joining: employee.date_of_joining ?? '',
    department_id: employee.department_id,
    designation_id: employee.designation_id,
    manager_id: employee.manager_id,
    employment_type: employee.employment_type,
    status: employee.status,
    basic_salary: employee.basic_salary,
    work_location: employee.work_location ?? '',
    shift: employee.shift ?? '',
    passport_no: employee.passport_no ?? '',
    visa_no: employee.visa_no ?? '',
    emirates_id: employee.emirates_id ?? '',
    national_id: employee.national_id ?? '',
    bank_account_number: employee.bank_account_number ?? '',
    notes: employee.notes ?? '',
  });

  const submit = (e) => { e.preventDefault(); put(route('hrm.employees.update', employee.id)); };

  return (
    <>
      <Head title={`Edit ${employee.employee_code}`} />
      <h1 className="text-2xl font-semibold mb-4">Edit Employee</h1>
      <EmployeeForm
        mode="edit"
        data={data} setData={setData} errors={errors} processing={processing}
        submit={submit}
        departments={departments} designations={designations} managers={managers}
        statuses={statuses} employmentTypes={employmentTypes}
      />
    </>
  );
}
EditEmployee.layout = page => <App title="Edit Employee">{page}</App>;
```

- [ ] Build the UI bundle to verify imports resolve:
```bash
npm run build --workspace=packages/aero-ui
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/Employees/Index.jsx \
       packages/aero-ui/resources/js/Pages/HRM/Employees/Create.jsx \
       packages/aero-ui/resources/js/Pages/HRM/Employees/Edit.jsx \
       packages/aero-ui/resources/js/Pages/HRM/Employees/_form.jsx
git commit -m "feat(aero-ui): employee list/create/edit Inertia pages"
```

---

## Task 6 — React page: Show (5 tabs)

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/Employees/Show.jsx`

- [ ] Write `Show.jsx` with five tabs (Overview, Documents, Bank Details, Emergency Contacts, Employment History):
```jsx
import { Head, Link } from '@inertiajs/react';
import App from '../../App.jsx';
import { Card, CardBody, CardHeader, Tabs, Tab, Avatar, Chip, Button, Divider } from '@aero/ui';

function MaskedField({ label, value, can }) {
  return (
    <div>
      <p className="text-xs text-default-500">{label}</p>
      <p className="font-medium">{can ? (value ?? '—') : '••••••'}</p>
    </div>
  );
}

export default function ShowEmployee({ employee, permissions }) {
  return (
    <>
      <Head title={employee.user?.name ?? employee.employee_code} />

      <Card className="mb-4">
        <CardBody className="flex flex-col md:flex-row items-start md:items-center gap-4">
          <Avatar size="lg" name={employee.user?.name} />
          <div className="flex-1">
            <h1 className="text-2xl font-semibold">{employee.user?.name ?? '—'}</h1>
            <p className="text-default-500">{employee.designation?.title ?? '—'} · {employee.department?.name ?? '—'}</p>
            <div className="flex gap-2 mt-2">
              <Chip size="sm">{employee.employee_code}</Chip>
              <Chip size="sm" color="primary">{employee.status}</Chip>
              <Chip size="sm" variant="flat">{employee.employment_type}</Chip>
            </div>
          </div>
          {permissions.canEdit && (
            <Button as={Link} href={route('hrm.employees.edit', employee.id)} color="primary">Edit</Button>
          )}
        </CardBody>
      </Card>

      <Tabs aria-label="Employee profile">
        <Tab key="overview" title="Overview">
          <Card><CardBody className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <MaskedField label="Email"       value={employee.user?.email} can />
            <MaskedField label="Joined"      value={employee.date_of_joining} can />
            <MaskedField label="Manager"     value={employee.manager?.user?.name} can />
            <MaskedField label="Work Location" value={employee.work_location} can />
            <MaskedField label="Shift"       value={employee.shift} can />
            <MaskedField label="Salary"      value={employee.basic_salary} can />
            <Divider className="col-span-full my-2" />
            <MaskedField label="Passport No" value={employee.passport_no} can={!!employee.passport_no} />
            <MaskedField label="Emirates ID" value={employee.emirates_id} can={!!employee.emirates_id} />
            <MaskedField label="National ID" value={employee.national_id} can={!!employee.national_id} />
          </CardBody></Card>
        </Tab>

        <Tab key="documents" title="Documents">
          <Card><CardBody>
            {permissions.canViewDocs
              ? <p className="text-default-500">Document list goes here (linked to EmployeeDocumentController).</p>
              : <p className="text-warning">You do not have permission to view documents.</p>}
          </CardBody></Card>
        </Tab>

        <Tab key="bank" title="Bank Details">
          <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <MaskedField label="Bank Account" value={employee.bank_account_number} can={permissions.canViewBank} />
            {permissions.canEditBank && (
              <Button as={Link} href={route('hrm.employees.edit', employee.id)} variant="flat">Update bank details</Button>
            )}
          </CardBody></Card>
        </Tab>

        <Tab key="emergency" title="Emergency Contacts">
          <Card><CardBody>
            <p className="text-default-500">Emergency contact list (linked to EmergencyContact model).</p>
          </CardBody></Card>
        </Tab>

        <Tab key="history" title="Employment History">
          <Card><CardBody>
            <p className="text-default-500">Timeline of department/designation/manager changes.</p>
          </CardBody></Card>
        </Tab>
      </Tabs>
    </>
  );
}
ShowEmployee.layout = page => <App title="Employee">{page}</App>;
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/Employees/Show.jsx
git commit -m "feat(aero-ui): employee profile page with 5 tabs + PII masking"
```

---

## Task 7 — Feature tests

**Files:**
- Create: `packages/aero-hrm/tests/Feature/Employee/EmployeeControllerTest.php`

- [ ] Write the test suite (8 methods):
```php
<?php

namespace Aero\HRM\Tests\Feature\Employee;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    private function actingAsHR(array $abilities = ['hrm.employees.list.view', 'hrm.employees.list.edit', 'hrm.employees.detail.view', 'hrm.employees.detail.edit']): User
    {
        $user = User::factory()->create();
        foreach ($abilities as $a) {
            $user->givePermissionTo($a);
        }
        $this->actingAs($user);
        return $user;
    }

    public function test_index_returns_paginated_employees_with_filters(): void
    {
        $this->actingAsHR();
        Employee::factory()->count(25)->create(['status' => 'active']);
        Employee::factory()->count(5)->create(['status' => 'terminated']);

        $this->get(route('hrm.employees.index', ['status' => 'active']))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('HRM/Employees/Index')
                ->where('filters.status', 'active')
                ->has('employees.data', 20));
    }

    public function test_create_form_is_rendered(): void
    {
        $this->actingAsHR();

        $this->get(route('hrm.employees.create'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Employees/Create'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAsHR();

        $this->post(route('hrm.employees.store'), [])
            ->assertSessionHasErrors(['user_id', 'employee_code', 'date_of_joining', 'employment_type', 'status', 'basic_salary']);
    }

    public function test_store_creates_employee_and_redirects(): void
    {
        $this->actingAsHR();
        $user  = User::factory()->create();
        $dept  = Department::factory()->create();
        $desig = Designation::factory()->create();

        $this->post(route('hrm.employees.store'), [
            'user_id'         => $user->id,
            'employee_code'   => 'EMP-001',
            'date_of_joining' => '2026-01-01',
            'department_id'   => $dept->id,
            'designation_id'  => $desig->id,
            'employment_type' => 'full_time',
            'status'          => 'active',
            'basic_salary'    => 5000,
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-001']);
    }

    public function test_show_renders_profile(): void
    {
        $this->actingAsHR();
        $employee = Employee::factory()->create();

        $this->get(route('hrm.employees.show', $employee))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('HRM/Employees/Show')
                ->where('employee.id', $employee->id));
    }

    public function test_show_masks_bank_details_without_permission(): void
    {
        $this->actingAsHR(['hrm.employees.detail.view']); // no bank-details.view
        $employee = Employee::factory()->create(['bank_account_number' => 'AE0123456789']);

        $this->get(route('hrm.employees.show', $employee))
            ->assertInertia(fn ($p) => $p
                ->where('employee.bank_account_number', null)
                ->where('permissions.canViewBank', false));
    }

    public function test_update_modifies_employee(): void
    {
        $this->actingAsHR();
        $employee = Employee::factory()->create(['employment_type' => 'full_time']);

        $this->put(route('hrm.employees.update', $employee), [
            'employee_code'   => $employee->employee_code,
            'date_of_joining' => $employee->date_of_joining->toDateString(),
            'employment_type' => 'part_time',
            'status'          => 'active',
            'basic_salary'    => 6000,
        ])->assertRedirect();

        $this->assertSame('part_time', $employee->fresh()->employment_type);
    }

    public function test_destroy_soft_deletes_and_restore_brings_back(): void
    {
        $this->actingAsHR();
        $employee = Employee::factory()->create();

        $this->delete(route('hrm.employees.destroy', $employee))->assertRedirect();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        $this->post(route('hrm.employees.restore', $employee->id))->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }
}
```

- [ ] Run the test suite:
```bash
cd packages/aero-hrm && ../../vendor/bin/phpunit --filter=EmployeeControllerTest --testdox
```

- [ ] Commit:
```bash
git add packages/aero-hrm/tests/Feature/Employee/EmployeeControllerTest.php
git commit -m "test(aero-hrm): EmployeeController feature tests (index/create/store/show/update/destroy/restore/PII mask)"
```

---

## Task 8 — Playwright smoke + final verification

**Files:**
- Create: `tests/e2e/hrm/employees.spec.ts`

- [ ] Write the smoke test:
```ts
import { test, expect } from '@playwright/test';

test.describe('HRM Employees', () => {
  test('list page renders and create form opens', async ({ page }) => {
    await page.goto('/hrm/employees');
    await expect(page.getByRole('heading', { name: 'Employees' })).toBeVisible();

    await page.getByRole('link', { name: 'New Employee' }).click();
    await expect(page).toHaveURL(/\/hrm\/employees\/create/);
    await expect(page.getByRole('heading', { name: 'New Employee' })).toBeVisible();
  });

  test('show page renders profile tabs', async ({ page }) => {
    await page.goto('/hrm/employees/1');
    await expect(page.getByRole('tab', { name: 'Overview' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Documents' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Bank Details' })).toBeVisible();
  });
});
```

- [ ] Run e2e:
```bash
npx playwright test tests/e2e/hrm/employees.spec.ts
```

- [ ] Full backend regression for the package:
```bash
cd packages/aero-hrm && ../../vendor/bin/phpunit --testdox
```

- [ ] Commit:
```bash
git add tests/e2e/hrm/employees.spec.ts
git commit -m "test(e2e): playwright smoke for employees list/create/show"
```

---

## Acceptance checklist

- [ ] All 8 PHPUnit test methods green.
- [ ] Routes secured with `hrmac:hrm.employees.*` middleware.
- [ ] PII columns encrypted via `EncryptedField`.
- [ ] Bank/passport fields masked in Inertia props when caller lacks scope.
- [ ] AuditService called on create / update / delete / restore / sensitive view.
- [ ] All four React pages live under `Pages/HRM/Employees/` and use `App` layout.
- [ ] PHPStan level 6 passes on touched files.
