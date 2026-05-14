# Inertia Standard — Controller + Page Contract

Every feature in AEOS365 follows this contract exactly. No exceptions.

---

## Controller Contract

### Rules

1. Every `index()` passes ALL data needed for the page as Inertia props — no lazy `fetch()` on the frontend
2. All collections are eager-loaded — no N+1 queries
3. All paginated results use `.withQueryString()` so filters survive page changes
4. Filter parameters are passed back as `filters` prop so the page can restore UI state
5. `store()` and `update()` redirect using `to_route()` with a flash message
6. `destroy()` redirects back to index with a flash message
7. Validation lives in a dedicated **Form Request** class — never inline `$request->validate()`
8. Policies or `abort(403)` guard every method — never trust the frontend HRMAC check alone

### Full CRUD Controller Pattern

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Employee\StoreEmployeeRequest;
use Aero\HRM\Http\Requests\Employee\UpdateEmployeeRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('HRM/Employees/Index', [
            'employees' => Employee::query()
                ->with(['department:id,name', 'designation:id,title', 'user:id,name,email,avatar'])
                ->when($request->search, fn ($q, $s) =>
                    $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%"))
                )
                ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'departments' => Department::active()->get(['id', 'name']),
            'designations' => Designation::active()->get(['id', 'title']),
            'filters' => $request->only(['search', 'department_id', 'status']),
            'stats' => [
                'total'      => Employee::count(),
                'active'     => Employee::where('status', 'active')->count(),
                'on_leave'   => Employee::onLeaveToday()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Employees/Create', [
            'departments'    => Department::active()->get(['id', 'name']),
            'designations'   => Designation::active()->get(['id', 'title']),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return to_route('hrm.employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): Response
    {
        return Inertia::render('HRM/Employees/Show', [
            'employee' => $employee->load([
                'department', 'designation', 'user',
                'bankDetails', 'emergencyContacts', 'documents',
            ]),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('HRM/Employees/Edit', [
            'employee'     => $employee->load(['department', 'designation', 'user']),
            'departments'  => Department::active()->get(['id', 'name']),
            'designations' => Designation::active()->get(['id', 'title']),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return to_route('hrm.employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return to_route('hrm.employees.index')
            ->with('success', 'Employee deleted.');
    }
}
```

### Form Request Pattern

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // HRMAC checked via middleware or policy
    }

    public function rules(): array
    {
        return [
            'user_id'        => ['required', 'exists:users,id'],
            'department_id'  => ['required', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'joining_date'   => ['required', 'date'],
            'status'         => ['required', 'in:active,inactive'],
            'employee_id'    => ['nullable', 'string', 'unique:employees,employee_id'],
        ];
    }
}
```

---

## Page Contract

### Rules

1. **Zero `fetch()` calls** — all data comes from Inertia props passed by the controller
2. **`useHRMAC` on every destructive/create action** — never render a button the user can't use
3. **Filter state from props** — restore `search`, `status`, etc. from the `filters` prop
4. **Filtering uses `router.get()` with `only:`** — partial reload, never full page refresh
5. **Pagination uses `router.get()` via `onPageChange`** — same partial reload pattern
6. **Forms use `useForm` from `@inertiajs/react`** — never raw `fetch` POST
7. **Flash messages** — read from `usePage().props.flash` and display via `useToast`
8. **Import alias `@/`** maps to `packages/aero-ui/resources/js/` — use it everywhere
9. **Every page exports a `default` function** named after the page
10. **Wrap in `<App>`** — always the outermost element

### Full Index Page Pattern

```jsx
import { useState } from 'react';
import { router, Link, usePage } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  Input,
  Select,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '@/hooks/useHRMAC.js';
import App from '@/Pages/App.jsx';

export default function EmployeesIndex({ employees, departments, designations, filters, stats }) {
  const toast   = useToast();
  const { props: { flash } } = usePage();

  const canCreate = useHRMAC('hrm.employee_management.employees.create');
  const canEdit   = useHRMAC('hrm.employee_management.employees.edit');
  const canDelete = useHRMAC('hrm.employee_management.employees.delete');

  const [search, setSearch] = useState(filters.search ?? '');
  const [deptId, setDeptId] = useState(filters.department_id ?? '');
  const [status, setStatus] = useState(filters.status ?? '');

  // Show flash on mount
  useState(() => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error)   toast.error(flash.error);
  }, [flash]);

  // Partial reload — only refreshes `employees` and `filters` props
  const applyFilters = (overrides = {}) => {
    router.get(
      route('hrm.employees.index'),
      { search, department_id: deptId, status, ...overrides },
      { preserveState: true, preserveScroll: true, only: ['employees', 'filters'] }
    );
  };

  const destroy = (id) => {
    if (!confirm('Delete this employee? This cannot be undone.')) return;
    router.delete(route('hrm.employees.destroy', id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Employee deleted.'),
      onError:   () => toast.error('Could not delete employee.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title="Employees"
        stats={[
          { label: 'Total',    value: stats.total    },
          { label: 'Active',   value: stats.active   },
          { label: 'On Leave', value: stats.on_leave },
        ]}
        actions={
          canCreate && (
            <Button as={Link} href={route('hrm.employees.create')} intent="primary">
              Add Employee
            </Button>
          )
        }
      >
        {/* ── Filters ─────────────────────────────────── */}
        <HStack gap={3} style={{ marginBottom: 16 }}>
          <Input
            placeholder="Search name or email…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select
            value={deptId}
            onChange={e => { setDeptId(e.target.value); applyFilters({ department_id: e.target.value }); }}
          >
            <option value="">All Departments</option>
            {departments.map(d => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
          </Select>
          <Select
            value={status}
            onChange={e => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="terminated">Terminated</option>
          </Select>
          <Button onClick={() => applyFilters()}>Search</Button>
          <Button intent="ghost" onClick={() => {
            setSearch(''); setDeptId(''); setStatus('');
            applyFilters({ search: '', department_id: '', status: '' });
          }}>Reset</Button>
        </HStack>

        {/* ── Table ───────────────────────────────────── */}
        <DataTable
          data={employees.data}
          pagination={employees}
          onPageChange={page => applyFilters({ page })}
          emptyState="No employees found. Try adjusting your filters."
          columns={[
            {
              key: 'name',
              label: 'Employee',
              render: (_, row) => (
                <HStack gap={2} align="center">
                  <span>{row.user?.name ?? '—'}</span>
                </HStack>
              ),
            },
            {
              key: 'department',
              label: 'Department',
              render: (_, row) => row.department?.name ?? '—',
            },
            {
              key: 'designation',
              label: 'Designation',
              render: (_, row) => row.designation?.title ?? '—',
            },
            {
              key: 'status',
              label: 'Status',
              render: (_, row) => (
                <Badge intent={row.status === 'active' ? 'success' : row.status === 'terminated' ? 'danger' : 'neutral'}>
                  {row.status}
                </Badge>
              ),
            },
            {
              key: 'actions',
              label: '',
              render: (_, row) => (
                <HStack gap={1} justify="end">
                  {canEdit && (
                    <Button size="xs" intent="ghost" as={Link} href={route('hrm.employees.edit', row.id)}>
                      Edit
                    </Button>
                  )}
                  {canDelete && (
                    <Button size="xs" intent="ghost" tone="danger" onClick={() => destroy(row.id)}>
                      Delete
                    </Button>
                  )}
                </HStack>
              ),
            },
          ]}
        />
      </IndexPageLayout>
    </App>
  );
}
```

### Full Create/Edit Form Pattern

```jsx
import { router, Link } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  FormSection,
  Button,
  Input,
  Select,
  HStack,
  Text,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function EmployeesCreate({ departments, designations }) {
  const form = useForm({
    user_id:        '',
    department_id:  '',
    designation_id: '',
    joining_date:   '',
    status:         'active',
    employee_id:    '',
  });

  const submit = (e) => {
    e.preventDefault();
    form.post(route('hrm.employees.store'), {
      onError: () => {}, // errors auto-populate form.errors
    });
  };

  return (
    <App>
      <FormPageLayout
        title="Add Employee"
        breadcrumbs={[
          { label: 'Employees', href: route('hrm.employees.index') },
          { label: 'Add Employee' },
        ]}
        actions={
          <HStack gap={2}>
            <Button intent="ghost" as={Link} href={route('hrm.employees.index')}>Cancel</Button>
            <Button intent="primary" onClick={submit} loading={form.processing}>Save Employee</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <FormSection title="Basic Information" description="Employee identity and employment details.">
            <Select
              label="Department"
              value={form.data.department_id}
              onChange={e => form.setData('department_id', e.target.value)}
              error={form.errors.department_id}
              required
            >
              <option value="">Select department…</option>
              {departments.map(d => (
                <option key={d.id} value={d.id}>{d.name}</option>
              ))}
            </Select>

            <Select
              label="Designation"
              value={form.data.designation_id}
              onChange={e => form.setData('designation_id', e.target.value)}
              error={form.errors.designation_id}
            >
              <option value="">Select designation…</option>
              {designations.map(d => (
                <option key={d.id} value={d.id}>{d.title}</option>
              ))}
            </Select>

            <Input
              label="Joining Date"
              type="date"
              value={form.data.joining_date}
              onChange={e => form.setData('joining_date', e.target.value)}
              error={form.errors.joining_date}
              required
            />

            <Select
              label="Status"
              value={form.data.status}
              onChange={e => form.setData('status', e.target.value)}
              error={form.errors.status}
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </Select>
          </FormSection>
        </form>
      </FormPageLayout>
    </App>
  );
}
```

---

## Flash Message Convention

Controllers always use `->with('success', '...')` or `->with('error', '...')`.

Pages read flash in a `useEffect` on mount:

```jsx
import { usePage } from '@inertiajs/react';
import { useToast } from '@aero/ui';

// Inside the page component:
const { props: { flash } } = usePage();
const toast = useToast();

useEffect(() => {
  if (flash?.success) toast.success(flash.success);
  if (flash?.error)   toast.error(flash.error);
}, [flash]);
```

---

## What Is Forbidden

| ❌ Never do this | ✅ Do this instead |
|-----------------|-------------------|
| `fetch('/api/employees')` in a page | Pass `employees` as Inertia prop from controller |
| `axios.get()` in a page | Same as above |
| `$request->validate([...])` inline | Use a Form Request class |
| `response()->json(...)` for page routes | Use `Inertia::render(...)` |
| `router.visit()` for filter changes | `router.get()` with `only: [...]` |
| `useState` for data that comes from server | Use Inertia props directly |
| Missing HRMAC guard on action buttons | Always `useHRMAC(...)` before rendering actions |
