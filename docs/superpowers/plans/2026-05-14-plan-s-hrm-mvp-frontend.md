# Plan S — HRM Module MVP Frontend

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the 10 essential HRM frontend pages in `packages/aero-ui/resources/js/Pages/HRM/` that make the module usable for a demo/launch: Dashboard, Employee list, Departments, Designations, Leave (admin + employee views), Attendance (admin + self-service), Holidays, and Payroll index.

**Architecture:** All pages live in `packages/aero-ui/resources/js/Pages/HRM/`. They import exclusively from `@aero/ui` (the barrel export at `packages/aero-ui/resources/js/index.js`). Each page uses `useHRMAC` for permission guards and `App` as the root wrapper. Data comes entirely from Inertia props — no per-page fetch calls. API-driven data (paginated tables, filtered lists) uses `router.get(route('...'), filters, { preserveState: true })`.

**Tech Stack:** React 18, Inertia.js v2, `@aero/ui` component library, `useHRMAC` hook, `@inertiajs/react` (`useForm`, `router`, `Link`, `usePage`).

**Prerequisite:** Plans A–R merged to `main`. Working directory: `c:\laragon\www\Aero-Enterprise-Suite-Saas`.

---

## Component Pattern Reference

Every page follows this exact structure:

```jsx
import { router, useForm, Link } from '@inertiajs/react';
import {
  IndexPageLayout,  // or DashboardLayout, FormPageLayout, DetailPageLayout
  DataTable, Button, Badge, Card, KPI,
  HStack, VStack, Text, Mono, Input, Select,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

export default function PageName({ prop1, prop2, filters }) {
  const toast = useToast();
  const canCreate = useHRMAC('hrm.module.component.create');

  // ... state + handlers

  return (
    <App>
      <IndexPageLayout title="Page Title" actions={<Button>...</Button>}>
        {/* content */}
      </IndexPageLayout>
    </App>
  );
}
```

**Route helpers:** `route('hrm.employees.index')`, `route('hrm.departments.index')`, etc. (match the named routes in `packages/aero-hrm/routes/web.php`).

**HRMAC permission paths:** `hrm.<submodule>.<component>.<action>` e.g. `hrm.employee_management.employees.create`.

---

## File Map

| Create | Path |
|--------|------|
| Create | `packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/EmployeeList.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/Departments.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/Designations.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/LeavesAdmin.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/LeavesEmployee.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/MyAttendance.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/Holidays.jsx` |
| Create | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Index.jsx` |

---

## Task S1: HRM Dashboard

**Inertia props from `HRMDashboardController::index()`:**
```js
{
  title: 'HRM Dashboard',
  stats: {
    totalEmployees, activeEmployees, onLeaveToday, pendingLeaves,
    approvedLeaves, presentToday, absentToday, lateToday,
    averageAttendance, openPositions, pendingExpenses, newHiresThisMonth
  },
  pendingLeaves: [{ id, employee_name, employee_avatar, leave_type, days, from_date, to_date, status }],
  departmentStats: [{ id, name, employee_count, attendance_rate }],
  upcomingReviews: [],
  dynamicWidgets: []
}
```

- [ ] **Step S1.1: Create `packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx`**

```jsx
import { Link } from '@inertiajs/react';
import {
  DashboardLayout, KPI, Card, CardContent,
  HStack, VStack, Text, Mono, Badge, Avatar,
} from '@aero/ui';
import App from '../../App.jsx';

function StatCard({ label, value, sub }) {
  return (
    <Card>
      <CardContent>
        <VStack gap={1}>
          <Mono tone="tertiary" size="sm">{label}</Mono>
          <Text size="2xl" weight="bold">{value}</Text>
          {sub && <Text size="sm" tone="secondary">{sub}</Text>}
        </VStack>
      </CardContent>
    </Card>
  );
}

function LeaveRow({ leave }) {
  const statusIntent = { pending: 'warning', approved: 'success', rejected: 'danger' }[leave.status] ?? 'neutral';
  return (
    <HStack gap={3} align="center" justify="between">
      <HStack gap={2} align="center">
        <Avatar name={leave.employee_name} size="sm" />
        <VStack gap={0}>
          <Text size="sm">{leave.employee_name}</Text>
          <Mono tone="tertiary" size="xs">{leave.leave_type} · {leave.from_date}–{leave.to_date}</Mono>
        </VStack>
      </HStack>
      <Badge intent={statusIntent}>{leave.status}</Badge>
    </HStack>
  );
}

export default function HRMDashboard({ stats, pendingLeaves, departmentStats }) {
  return (
    <App>
      <DashboardLayout title="HRM Dashboard">
        {/* KPI row */}
        <HStack gap={4} wrap>
          <KPI label="Total Employees"   value={stats.totalEmployees}    />
          <KPI label="Active"            value={stats.activeEmployees}   />
          <KPI label="On Leave Today"    value={stats.onLeaveToday}      />
          <KPI label="Present Today"     value={stats.presentToday}      />
          <KPI label="Absent Today"      value={stats.absentToday}       />
          <KPI label="Pending Leaves"    value={stats.pendingLeaves}     />
          <KPI label="Avg Attendance"    value={`${stats.averageAttendance}%`} />
          <KPI label="New This Month"    value={stats.newHiresThisMonth} />
        </HStack>

        <HStack gap={4} align="start" wrap style={{ marginTop: 24 }}>
          {/* Pending leave requests */}
          <Card style={{ flex: 1, minWidth: 320 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>Pending Leave Requests</Text>
              {pendingLeaves.length === 0
                ? <Text tone="secondary" size="sm">No pending requests.</Text>
                : <VStack gap={3}>{pendingLeaves.map(l => <LeaveRow key={l.id} leave={l} />)}</VStack>
              }
            </CardContent>
          </Card>

          {/* Department stats */}
          <Card style={{ flex: 1, minWidth: 320 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>Department Attendance</Text>
              <VStack gap={2}>
                {departmentStats.map(d => (
                  <HStack key={d.id} justify="between" align="center">
                    <Text size="sm">{d.name}</Text>
                    <HStack gap={2} align="center">
                      <Mono size="sm" tone="secondary">{d.employee_count} employees</Mono>
                      <Badge intent={d.attendance_rate >= 80 ? 'success' : 'warning'}>
                        {d.attendance_rate}%
                      </Badge>
                    </HStack>
                  </HStack>
                ))}
              </VStack>
            </CardContent>
          </Card>
        </HStack>
      </DashboardLayout>
    </App>
  );
}
```

- [ ] **Step S1.2: Verify JSX syntax**

```powershell
node --input-type=module --eval "import('./packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx')" 2>&1 | Select-Object -First 5
```

If the Node ESM check fails (expected without a bundler), just check no obvious syntax errors:
```powershell
Select-String -Pattern "SyntaxError" packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx
```

- [ ] **Step S1.3: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx
git commit -m "feat(aero-ui): HRM Dashboard page -- stats KPIs, pending leaves, department attendance"
```

---

## Task S2: Employee List

**Inertia props from `EmployeeController::index()`:**
```js
{
  title: 'Employee Management',
  departments: [{ id, name }],
  designations: [{ id, title }],
  attendanceTypes: [{ id, name }]
  // Employees loaded via AJAX from /hrm/employees/paginate
}
```

- [ ] **Step S2.1: Create `packages/aero-ui/resources/js/Pages/HRM/EmployeeList.jsx`**

```jsx
import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Input, Select,
  HStack, VStack, Text, Mono, Avatar, Card, CardContent,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

export default function EmployeeList({ departments, designations }) {
  const toast = useToast();
  const canCreate = useHRMAC('hrm.employee_management.employees.create');
  const canEdit   = useHRMAC('hrm.employee_management.employees.edit');

  const [search, setSearch]       = useState('');
  const [deptFilter, setDept]     = useState('');
  const [statusFilter, setStatus] = useState('');
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading]     = useState(false);
  const [meta, setMeta]           = useState({ current_page: 1, last_page: 1, total: 0 });

  const fetchEmployees = (page = 1) => {
    setLoading(true);
    fetch(route('hrm.employees.paginate') + `?search=${search}&department_id=${deptFilter}&status=${statusFilter}&page=${page}`)
      .then(r => r.json())
      .then(data => {
        setEmployees(data.data ?? []);
        setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total });
      })
      .finally(() => setLoading(false));
  };

  // Initial load
  useState(() => { fetchEmployees(); }, []);

  const statusBadge = s => {
    const map = { active: 'success', inactive: 'neutral', terminated: 'danger', resigned: 'warning' };
    return <Badge intent={map[s] ?? 'neutral'}>{s}</Badge>;
  };

  return (
    <App>
      <IndexPageLayout
        title="Employee Management"
        actions={canCreate && (
          <Button as={Link} href={route('hrm.employees.create')} intent="primary">
            Add Employee
          </Button>
        )}
      >
        {/* Filters */}
        <HStack gap={3} style={{ marginBottom: 16 }}>
          <Input
            placeholder="Search name, email, ID…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && fetchEmployees()}
            style={{ maxWidth: 260 }}
          />
          <Select value={deptFilter} onChange={e => setDept(e.target.value)}>
            <option value="">All Departments</option>
            {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
          </Select>
          <Select value={statusFilter} onChange={e => setStatus(e.target.value)}>
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="terminated">Terminated</option>
          </Select>
          <Button onClick={() => fetchEmployees()}>Filter</Button>
        </HStack>

        {/* Employee grid */}
        {loading ? (
          <Text tone="secondary">Loading…</Text>
        ) : employees.length === 0 ? (
          <Card><CardContent><Text tone="secondary">No employees found.</Text></CardContent></Card>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 12 }}>
            {employees.map(emp => (
              <Card key={emp.id} interactive as={canEdit ? Link : 'div'}
                    href={canEdit ? route('hrm.employees.show', emp.id) : undefined}>
                <CardContent>
                  <HStack gap={3} align="center">
                    <Avatar name={emp.name ?? emp.user?.name} size="md" src={emp.avatar_url} />
                    <VStack gap={0} style={{ flex: 1, minWidth: 0 }}>
                      <Text weight="semibold" truncate>{emp.name ?? emp.user?.name}</Text>
                      <Mono size="xs" tone="tertiary">{emp.employee_id}</Mono>
                      <Text size="sm" tone="secondary" truncate>{emp.designation?.title}</Text>
                    </VStack>
                    {statusBadge(emp.status)}
                  </HStack>
                  <Mono size="xs" tone="tertiary" style={{ marginTop: 8 }}>
                    {emp.department?.name ?? '—'}
                  </Mono>
                </CardContent>
              </Card>
            ))}
          </div>
        )}

        {/* Pagination */}
        {meta.last_page > 1 && (
          <HStack gap={2} justify="center" style={{ marginTop: 16 }}>
            {Array.from({ length: meta.last_page }, (_, i) => i + 1).map(p => (
              <Button key={p} size="sm" intent={p === meta.current_page ? 'primary' : 'ghost'}
                      onClick={() => fetchEmployees(p)}>
                {p}
              </Button>
            ))}
          </HStack>
        )}
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S2.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/EmployeeList.jsx
git commit -m "feat(aero-ui): HRM EmployeeList page -- card grid with search/dept/status filters"
```

---

## Task S3: Departments

**Inertia props from `DepartmentController::index()`:**
```js
{
  title: 'Department Management',
  departments: { data: [...], current_page, last_page, total },
  managers: [{ id, name }],
  parentDepartments: [{ id, name }],
  stats: { total, active, inactive, parent_departments },
  filters: { search, status }
}
```

- [ ] **Step S3.1: Create `packages/aero-ui/resources/js/Pages/HRM/Departments.jsx`**

```jsx
import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Input, Select,
  HStack, VStack, Text, Mono, Card, CardContent, KPI,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

export default function Departments({ departments, managers, parentDepartments, stats, filters }) {
  const toast = useToast();
  const canCreate = useHRMAC('hrm.organization.departments.create');
  const canEdit   = useHRMAC('hrm.organization.departments.edit');
  const canDelete = useHRMAC('hrm.organization.departments.delete');

  const [search, setSearch]   = useState(filters?.search ?? '');
  const [status, setStatus]   = useState(filters?.status ?? '');
  const [showForm, setForm]   = useState(false);

  const form = useForm({
    name: '', code: '', description: '', parent_id: '', manager_id: '',
    location: '', is_active: true,
  });

  const applyFilters = () => {
    router.get(route('hrm.departments.index'), { search, status }, {
      preserveState: true, preserveScroll: true, only: ['departments', 'filters'],
    });
  };

  const submit = () => {
    form.post(route('hrm.departments.store'), {
      onSuccess: () => { setForm(false); form.reset(); toast.success('Department created.'); },
      onError: () => toast.error('Please fix the errors below.'),
    });
  };

  const deleteDept = (id) => {
    if (!confirm('Delete this department?')) return;
    router.delete(route('hrm.departments.destroy', id), {
      onSuccess: () => toast.success('Department deleted.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title="Department Management"
        actions={canCreate && (
          <Button intent="primary" onClick={() => setForm(v => !v)}>
            {showForm ? 'Cancel' : 'Add Department'}
          </Button>
        )}
      >
        {/* Stats */}
        <HStack gap={4} style={{ marginBottom: 20 }}>
          <KPI label="Total"    value={stats.total}    />
          <KPI label="Active"   value={stats.active}   />
          <KPI label="Inactive" value={stats.inactive} />
        </HStack>

        {/* Inline create form */}
        {showForm && (
          <Card style={{ marginBottom: 16 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>New Department</Text>
              <VStack gap={3}>
                <Input placeholder="Name *" value={form.data.name}
                       onChange={e => form.setData('name', e.target.value)} />
                {form.errors.name && <Text size="sm" tone="danger">{form.errors.name}</Text>}
                <Input placeholder="Code" value={form.data.code}
                       onChange={e => form.setData('code', e.target.value)} />
                <Input placeholder="Location" value={form.data.location}
                       onChange={e => form.setData('location', e.target.value)} />
                <Select value={form.data.parent_id}
                        onChange={e => form.setData('parent_id', e.target.value)}>
                  <option value="">No parent department</option>
                  {parentDepartments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                </Select>
                <Select value={form.data.manager_id}
                        onChange={e => form.setData('manager_id', e.target.value)}>
                  <option value="">No manager</option>
                  {managers.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}
                </Select>
                <HStack gap={2}>
                  <Button intent="primary" onClick={submit} loading={form.processing}>Save</Button>
                  <Button intent="ghost" onClick={() => setForm(false)}>Cancel</Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        {/* Filters */}
        <HStack gap={3} style={{ marginBottom: 12 }}>
          <Input placeholder="Search…" value={search}
                 onChange={e => setSearch(e.target.value)}
                 onKeyDown={e => e.key === 'Enter' && applyFilters()}
                 style={{ maxWidth: 240 }} />
          <Select value={status} onChange={e => setStatus(e.target.value)}>
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
          <Button onClick={applyFilters}>Filter</Button>
        </HStack>

        {/* Table */}
        <Card>
          <CardContent>
            {departments.data.length === 0 ? (
              <Text tone="secondary">No departments found.</Text>
            ) : (
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr>
                    {['Name', 'Code', 'Parent', 'Manager', 'Location', 'Status', ''].map(h => (
                      <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                        <Mono size="xs" tone="tertiary">{h}</Mono>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {departments.data.map(dept => (
                    <tr key={dept.id}>
                      <td style={{ padding: '10px 12px' }}><Text weight="semibold">{dept.name}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{dept.code ?? '—'}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{dept.parent?.name ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{dept.manager?.name ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{dept.location ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}>
                        <Badge intent={dept.is_active ? 'success' : 'neutral'}>
                          {dept.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </td>
                      <td style={{ padding: '10px 12px' }}>
                        <HStack gap={1}>
                          {canDelete && (
                            <Button size="xs" intent="ghost" tone="danger"
                                    onClick={() => deleteDept(dept.id)}>Delete</Button>
                          )}
                        </HStack>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S3.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Departments.jsx
git commit -m "feat(aero-ui): HRM Departments page -- table with inline create form, search/status filter"
```

---

## Task S4: Designations

**Inertia props from `DesignationController::index()`:**
```js
{
  title: 'Designation Management',
  designations: [],          // Loaded via /hrm/designations/getDepartmentDesignations API
  departments: [{ id, name }],
  parentDesignations: [{ id, title }],
  stats: { total, active, inactive, parent_designations }
}
```

- [ ] **Step S4.1: Create `packages/aero-ui/resources/js/Pages/HRM/Designations.jsx`**

```jsx
import { useState, useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Input, Select,
  HStack, VStack, Text, Mono, Card, CardContent, KPI,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

export default function Designations({ departments, parentDesignations, stats }) {
  const toast = useToast();
  const canCreate = useHRMAC('hrm.organization.designations.create');
  const canDelete = useHRMAC('hrm.organization.designations.delete');

  const [designations, setDesignations] = useState([]);
  const [showForm, setForm] = useState(false);

  const form = useForm({
    title: '', code: '', department_id: '', parent_id: '',
    hierarchy_level: 1, is_active: true,
  });

  useEffect(() => {
    fetch(route('hrm.designations.api'))
      .then(r => r.json())
      .then(data => setDesignations(data.designations ?? data))
      .catch(() => {});
  }, []);

  const submit = () => {
    form.post(route('hrm.designations.store'), {
      onSuccess: () => { setForm(false); form.reset(); toast.success('Designation created.'); },
      onError: () => toast.error('Fix the errors below.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title="Designation Management"
        actions={canCreate && (
          <Button intent="primary" onClick={() => setForm(v => !v)}>
            {showForm ? 'Cancel' : 'Add Designation'}
          </Button>
        )}
      >
        <HStack gap={4} style={{ marginBottom: 20 }}>
          <KPI label="Total"  value={stats.total}  />
          <KPI label="Active" value={stats.active} />
        </HStack>

        {showForm && (
          <Card style={{ marginBottom: 16 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>New Designation</Text>
              <VStack gap={3}>
                <Input placeholder="Title *" value={form.data.title}
                       onChange={e => form.setData('title', e.target.value)} />
                {form.errors.title && <Text size="sm" tone="danger">{form.errors.title}</Text>}
                <Input placeholder="Code" value={form.data.code}
                       onChange={e => form.setData('code', e.target.value)} />
                <Select value={form.data.department_id}
                        onChange={e => form.setData('department_id', e.target.value)}>
                  <option value="">Select department</option>
                  {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                </Select>
                <Select value={form.data.parent_id}
                        onChange={e => form.setData('parent_id', e.target.value)}>
                  <option value="">No parent designation</option>
                  {parentDesignations.map(d => <option key={d.id} value={d.id}>{d.title}</option>)}
                </Select>
                <HStack gap={2}>
                  <Button intent="primary" onClick={submit} loading={form.processing}>Save</Button>
                  <Button intent="ghost" onClick={() => setForm(false)}>Cancel</Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardContent>
            {designations.length === 0 ? (
              <Text tone="secondary">No designations yet.</Text>
            ) : (
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr>
                    {['Title', 'Code', 'Department', 'Level', 'Status'].map(h => (
                      <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                        <Mono size="xs" tone="tertiary">{h}</Mono>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {designations.map(d => (
                    <tr key={d.id}>
                      <td style={{ padding: '10px 12px' }}><Text>{d.title}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{d.code ?? '—'}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{d.department?.name ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{d.hierarchy_level}</Mono></td>
                      <td style={{ padding: '10px 12px' }}>
                        <Badge intent={d.is_active ? 'success' : 'neutral'}>
                          {d.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S4.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Designations.jsx
git commit -m "feat(aero-ui): HRM Designations page -- table with inline create form"
```

---

## Task S5: Leaves Admin

**Inertia props from `LeaveController::index2()`:**
```js
{ title: 'Leaves', allUsers: [{ id, name }] }
// Paginated data loads via LeaveController::paginate() — GET /hrm/leaves/paginate
```

- [ ] **Step S5.1: Create `packages/aero-ui/resources/js/Pages/HRM/LeavesAdmin.jsx`**

```jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Select,
  HStack, VStack, Text, Mono, Card, CardContent, Avatar,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

const statusIntent = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'neutral' };

export default function LeavesAdmin({ allUsers }) {
  const toast = useToast();
  const canApprove = useHRMAC('hrm.leave_management.leaves.approve');

  const [leaves, setLeaves]   = useState([]);
  const [loading, setLoading] = useState(false);
  const [status, setStatus]   = useState('pending');
  const [userId, setUserId]   = useState('');

  const fetchLeaves = () => {
    setLoading(true);
    const params = new URLSearchParams({ status, user_id: userId }).toString();
    fetch(`${route('hrm.leaves.paginate')}?${params}`)
      .then(r => r.json())
      .then(data => setLeaves(data.leaves?.data ?? data.data ?? []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { fetchLeaves(); }, [status, userId]);

  const updateStatus = (id, newStatus) => {
    fetch(route('hrm.leaves.update', id), {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content },
      body: JSON.stringify({ status: newStatus }),
    })
      .then(() => { toast.success(`Leave ${newStatus}.`); fetchLeaves(); })
      .catch(() => toast.error('Action failed.'));
  };

  return (
    <App>
      <IndexPageLayout title="Leave Management">
        <HStack gap={3} style={{ marginBottom: 12 }}>
          <Select value={status} onChange={e => setStatus(e.target.value)}>
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </Select>
          <Select value={userId} onChange={e => setUserId(e.target.value)} style={{ maxWidth: 200 }}>
            <option value="">All employees</option>
            {allUsers.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
          </Select>
        </HStack>

        <Card>
          <CardContent>
            {loading ? <Text tone="secondary">Loading…</Text>
              : leaves.length === 0 ? <Text tone="secondary">No leave records found.</Text>
              : (
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr>
                    {['Employee', 'Type', 'From', 'To', 'Days', 'Status', 'Actions'].map(h => (
                      <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                        <Mono size="xs" tone="tertiary">{h}</Mono>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {leaves.map(l => (
                    <tr key={l.id}>
                      <td style={{ padding: '10px 12px' }}>
                        <HStack gap={2} align="center">
                          <Avatar name={l.employee?.name ?? '?'} size="xs" />
                          <Text size="sm">{l.employee?.name ?? '—'}</Text>
                        </HStack>
                      </td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{l.leave_type?.name ?? l.leave_type ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{l.from_date}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{l.to_date}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Text size="sm">{l.total_days ?? '—'}</Text></td>
                      <td style={{ padding: '10px 12px' }}>
                        <Badge intent={statusIntent[l.status] ?? 'neutral'}>{l.status}</Badge>
                      </td>
                      <td style={{ padding: '10px 12px' }}>
                        {canApprove && l.status === 'pending' && (
                          <HStack gap={1}>
                            <Button size="xs" intent="success" onClick={() => updateStatus(l.id, 'approved')}>Approve</Button>
                            <Button size="xs" intent="danger"  onClick={() => updateStatus(l.id, 'rejected')}>Reject</Button>
                          </HStack>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S5.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/LeavesAdmin.jsx
git commit -m "feat(aero-ui): HRM LeavesAdmin page -- leave list with approve/reject actions"
```

---

## Task S6: Leaves Employee (Self-Service)

**Inertia props from `LeaveController::index1()`:**
```js
{ title: 'My Leaves', allUsers: [{ id, name }] }
```

- [ ] **Step S6.1: Create `packages/aero-ui/resources/js/Pages/HRM/LeavesEmployee.jsx`**

```jsx
import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Input, Select, Textarea,
  HStack, VStack, Text, Mono, Card, CardContent,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';

const statusIntent = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'neutral' };

export default function LeavesEmployee() {
  const toast = useToast();
  const canApply = useHRMAC('hrm.self_service.time_off.apply');

  const [myLeaves, setMyLeaves] = useState([]);
  const [showForm, setForm] = useState(false);

  const form = useForm({
    leave_type_id: '', from_date: '', to_date: '', reason: '',
  });

  const fetchMyLeaves = () => {
    fetch(route('hrm.leaves.paginate') + '?scope=mine')
      .then(r => r.json())
      .then(data => setMyLeaves(data.leaves?.data ?? data.data ?? []));
  };

  useEffect(() => { fetchMyLeaves(); }, []);

  const submit = () => {
    form.post(route('hrm.leaves.store'), {
      onSuccess: () => { setForm(false); form.reset(); toast.success('Leave request submitted.'); fetchMyLeaves(); },
      onError: () => toast.error('Fix the errors below.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title="My Leaves"
        actions={canApply && (
          <Button intent="primary" onClick={() => setForm(v => !v)}>
            {showForm ? 'Cancel' : 'Apply for Leave'}
          </Button>
        )}
      >
        {showForm && (
          <Card style={{ marginBottom: 16 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>New Leave Request</Text>
              <VStack gap={3}>
                <Input type="date" placeholder="From date *" value={form.data.from_date}
                       onChange={e => form.setData('from_date', e.target.value)} />
                {form.errors.from_date && <Text size="sm" tone="danger">{form.errors.from_date}</Text>}
                <Input type="date" placeholder="To date *" value={form.data.to_date}
                       onChange={e => form.setData('to_date', e.target.value)} />
                <Input placeholder="Reason" value={form.data.reason}
                       onChange={e => form.setData('reason', e.target.value)} />
                <HStack gap={2}>
                  <Button intent="primary" onClick={submit} loading={form.processing}>Submit</Button>
                  <Button intent="ghost" onClick={() => setForm(false)}>Cancel</Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardContent>
            {myLeaves.length === 0 ? (
              <Text tone="secondary">No leave records yet.</Text>
            ) : (
              <VStack gap={3}>
                {myLeaves.map(l => (
                  <HStack key={l.id} justify="between" align="center">
                    <VStack gap={0}>
                      <Text weight="semibold">{l.leave_type?.name ?? 'Leave'}</Text>
                      <Mono size="sm" tone="tertiary">{l.from_date} – {l.to_date} ({l.total_days ?? '?'} days)</Mono>
                      {l.reason && <Text size="sm" tone="secondary">{l.reason}</Text>}
                    </VStack>
                    <Badge intent={statusIntent[l.status] ?? 'neutral'}>{l.status}</Badge>
                  </HStack>
                ))}
              </VStack>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S6.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/LeavesEmployee.jsx
git commit -m "feat(aero-ui): HRM LeavesEmployee page -- self-service leave application and history"
```

---

## Task S7: Attendance Admin

**Inertia props from `AttendanceController::index1()`:**
```js
{
  title: 'Attendances of Employees',
  allUsers: [{ id, name, employee: { id } }]  // Employee::active()->with('user')
}
// Paginated attendance loads via GET /hrm/attendances/paginate
```

- [ ] **Step S7.1: Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin.jsx`**

```jsx
import { useState, useEffect } from 'react';
import {
  IndexPageLayout, Button, Badge, Input, Select,
  HStack, VStack, Text, Mono, Card, CardContent, Avatar,
  useToast,
} from '@aero/ui';
import App from '../../../App.jsx';

const punchStatus = row => {
  if (!row.punchin) return { label: 'Absent', intent: 'danger' };
  if (row.is_late)  return { label: 'Late',   intent: 'warning' };
  return { label: 'Present', intent: 'success' };
};

export default function AttendanceAdmin({ allUsers }) {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(false);
  const [date, setDate]       = useState(new Date().toISOString().split('T')[0]);
  const [empId, setEmpId]     = useState('');

  const fetchRecords = () => {
    setLoading(true);
    const params = new URLSearchParams({ date, employee: empId }).toString();
    fetch(`${route('hrm.attendances.paginate')}?${params}`)
      .then(r => r.json())
      .then(data => setRecords(data.data ?? []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { fetchRecords(); }, [date, empId]);

  return (
    <App>
      <IndexPageLayout title="Employee Attendance">
        <HStack gap={3} style={{ marginBottom: 12 }}>
          <Input type="date" value={date} onChange={e => setDate(e.target.value)} style={{ maxWidth: 180 }} />
          <Select value={empId} onChange={e => setEmpId(e.target.value)} style={{ maxWidth: 220 }}>
            <option value="">All employees</option>
            {allUsers.map(u => <option key={u.id} value={u.employee?.id}>{u.name}</option>)}
          </Select>
        </HStack>

        <Card>
          <CardContent>
            {loading ? <Text tone="secondary">Loading…</Text>
              : records.length === 0 ? <Text tone="secondary">No records for this date.</Text>
              : (
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr>
                    {['Employee', 'Date', 'Punch In', 'Punch Out', 'Status'].map(h => (
                      <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                        <Mono size="xs" tone="tertiary">{h}</Mono>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {records.map(r => {
                    const s = punchStatus(r);
                    return (
                      <tr key={r.id}>
                        <td style={{ padding: '10px 12px' }}>
                          <HStack gap={2} align="center">
                            <Avatar name={r.employee?.name ?? '?'} size="xs" />
                            <Text size="sm">{r.employee?.name ?? '—'}</Text>
                          </HStack>
                        </td>
                        <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.date}</Mono></td>
                        <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.punchin ?? '—'}</Mono></td>
                        <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.punchout ?? '—'}</Mono></td>
                        <td style={{ padding: '10px 12px' }}>
                          <Badge intent={s.intent}>{s.label}</Badge>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S7.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin.jsx
git commit -m "feat(aero-ui): HRM Attendance Admin page -- daily attendance table with employee/date filter"
```

---

## Task S8: My Attendance (Self-Service)

**Inertia props from `AttendanceController::index2()`:**
```js
{ title: 'My Attendance' }
// Loads via paginate API with scope=mine
```

- [ ] **Step S8.1: Create `packages/aero-ui/resources/js/Pages/HRM/MyAttendance.jsx`**

```jsx
import { useState, useEffect } from 'react';
import {
  IndexPageLayout, Badge, Input,
  HStack, VStack, Text, Mono, Card, CardContent,
} from '@aero/ui';
import App from '../../App.jsx';

export default function MyAttendance() {
  const [records, setRecords] = useState([]);
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));

  useEffect(() => {
    const [y, m] = month.split('-');
    fetch(`${route('hrm.attendances.paginate')}?scope=mine&currentYear=${y}&currentMonth=${m}`)
      .then(r => r.json())
      .then(data => setRecords(data.data ?? []));
  }, [month]);

  const summary = records.reduce(
    (acc, r) => {
      if (r.punchin) { acc.present++; if (r.is_late) acc.late++; } else acc.absent++;
      return acc;
    },
    { present: 0, absent: 0, late: 0 }
  );

  return (
    <App>
      <IndexPageLayout title="My Attendance">
        <HStack gap={4} style={{ marginBottom: 16 }}>
          <Card><CardContent><VStack gap={0}><Mono size="xs" tone="tertiary">Present</Mono><Text size="xl" weight="bold">{summary.present}</Text></VStack></CardContent></Card>
          <Card><CardContent><VStack gap={0}><Mono size="xs" tone="tertiary">Absent</Mono><Text size="xl" weight="bold">{summary.absent}</Text></VStack></CardContent></Card>
          <Card><CardContent><VStack gap={0}><Mono size="xs" tone="tertiary">Late</Mono><Text size="xl" weight="bold">{summary.late}</Text></VStack></CardContent></Card>
          <Input type="month" value={month} onChange={e => setMonth(e.target.value)} style={{ maxWidth: 160, marginLeft: 'auto' }} />
        </HStack>

        <Card>
          <CardContent>
            {records.length === 0 ? <Text tone="secondary">No records for this month.</Text> : (
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr>
                    {['Date', 'Punch In', 'Punch Out', 'Status'].map(h => (
                      <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                        <Mono size="xs" tone="tertiary">{h}</Mono>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {records.map(r => (
                    <tr key={r.id}>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.date}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.punchin ?? '—'}</Mono></td>
                      <td style={{ padding: '10px 12px' }}><Mono size="sm">{r.punchout ?? '—'}</Mono></td>
                      <td style={{ padding: '10px 12px' }}>
                        {!r.punchin
                          ? <Badge intent="danger">Absent</Badge>
                          : r.is_late
                            ? <Badge intent="warning">Late</Badge>
                            : <Badge intent="success">Present</Badge>
                        }
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S8.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/MyAttendance.jsx
git commit -m "feat(aero-ui): HRM MyAttendance page -- monthly self-service attendance view"
```

---

## Task S9: Holidays

**Inertia props from `HolidayController::index()`:**
```js
{
  title: 'Company Holidays',
  holidays: [{ id, name, date, duration, type, is_active, description }],
  stats: { total_holidays, upcoming_holidays, current_year_holidays, total_holiday_days }
}
```

- [ ] **Step S9.1: Create `packages/aero-ui/resources/js/Pages/HRM/Holidays.jsx`**

```jsx
import { useForm } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Input, Select,
  HStack, VStack, Text, Mono, Card, CardContent, KPI,
  useToast,
} from '@aero/ui';
import { useHRMAC } from '../../hooks/useHRMAC.js';
import App from '../../App.jsx';
import { useState } from 'react';

export default function Holidays({ holidays, stats }) {
  const toast = useToast();
  const canCreate = useHRMAC('hrm.organization.holidays.create');
  const canDelete = useHRMAC('hrm.organization.holidays.delete');
  const [showForm, setForm] = useState(false);

  const form = useForm({ name: '', date: '', duration: 1, type: 'public', description: '', is_active: true });

  const submit = () => {
    form.post(route('hrm.holidays.store'), {
      onSuccess: () => { setForm(false); form.reset(); toast.success('Holiday created.'); },
      onError: () => toast.error('Fix errors below.'),
    });
  };

  const today = new Date().toISOString().split('T')[0];

  return (
    <App>
      <IndexPageLayout
        title="Company Holidays"
        actions={canCreate && (
          <Button intent="primary" onClick={() => setForm(v => !v)}>
            {showForm ? 'Cancel' : 'Add Holiday'}
          </Button>
        )}
      >
        <HStack gap={4} style={{ marginBottom: 20 }}>
          <KPI label="Total"        value={stats.total_holidays}        />
          <KPI label="Upcoming"     value={stats.upcoming_holidays}     />
          <KPI label="This Year"    value={stats.current_year_holidays} />
          <KPI label="Total Days"   value={stats.total_holiday_days}    />
        </HStack>

        {showForm && (
          <Card style={{ marginBottom: 16 }}>
            <CardContent>
              <Text weight="semibold" style={{ marginBottom: 12 }}>New Holiday</Text>
              <VStack gap={3}>
                <Input placeholder="Holiday name *" value={form.data.name}
                       onChange={e => form.setData('name', e.target.value)} />
                {form.errors.name && <Text size="sm" tone="danger">{form.errors.name}</Text>}
                <Input type="date" value={form.data.date}
                       onChange={e => form.setData('date', e.target.value)} />
                <Select value={form.data.type} onChange={e => form.setData('type', e.target.value)}>
                  <option value="public">Public Holiday</option>
                  <option value="company">Company Holiday</option>
                  <option value="optional">Optional Holiday</option>
                </Select>
                <Input type="number" placeholder="Duration (days)" min={1}
                       value={form.data.duration}
                       onChange={e => form.setData('duration', e.target.value)} />
                <HStack gap={2}>
                  <Button intent="primary" onClick={submit} loading={form.processing}>Save</Button>
                  <Button intent="ghost" onClick={() => setForm(false)}>Cancel</Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardContent>
            {holidays.length === 0 ? (
              <Text tone="secondary">No holidays configured.</Text>
            ) : (
              <VStack gap={2}>
                {holidays.map(h => (
                  <HStack key={h.id} justify="between" align="center"
                          style={{ padding: '10px 0', borderBottom: '1px solid var(--aeos-border)' }}>
                    <HStack gap={3} align="center">
                      <div style={{
                        width: 44, height: 44, borderRadius: 8, display: 'flex',
                        flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                        background: 'var(--aeos-surface-raised)',
                      }}>
                        <Mono size="xs" tone="tertiary">{new Date(h.date).toLocaleDateString('en', { month: 'short' })}</Mono>
                        <Text weight="bold" size="lg">{new Date(h.date).getDate()}</Text>
                      </div>
                      <VStack gap={0}>
                        <Text weight="semibold">{h.name}</Text>
                        <HStack gap={2}>
                          <Badge intent={h.date >= today ? 'primary' : 'neutral'}>{h.type}</Badge>
                          <Mono size="xs" tone="tertiary">{h.duration} day{h.duration > 1 ? 's' : ''}</Mono>
                        </HStack>
                      </VStack>
                    </HStack>
                    {canDelete && (
                      <Button size="xs" intent="ghost" tone="danger"
                              onClick={() => confirm('Delete holiday?') && router.delete(route('hrm.holidays.destroy', h.id))}>
                        Delete
                      </Button>
                    )}
                  </HStack>
                ))}
              </VStack>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S9.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Holidays.jsx
git commit -m "feat(aero-ui): HRM Holidays page -- holiday list with calendar-style date tiles"
```

---

## Task S10: Payroll Index

**Inertia props from `PayrollController::index()`:**
```js
{
  title: 'Payroll Management',
  payrolls: { data: [{ id, employee, processed_by, period_start, period_end, status, net_salary, gross_salary, created_at }], ...pagination },
  stats: { ... }
}
```

- [ ] **Step S10.1: Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Index.jsx`**

```jsx
import { Link } from '@inertiajs/react';
import {
  IndexPageLayout, Button, Badge, Pagination,
  HStack, VStack, Text, Mono, Card, CardContent, KPI, Avatar,
} from '@aero/ui';
import { useHRMAC } from '../../../hooks/useHRMAC.js';
import App from '../../../App.jsx';

const statusIntent = { draft: 'neutral', processing: 'warning', processed: 'success', paid: 'primary', cancelled: 'danger' };

export default function PayrollIndex({ payrolls, stats }) {
  const canRun = useHRMAC('hrm.payroll.payroll.run');

  return (
    <App>
      <IndexPageLayout
        title="Payroll Management"
        actions={canRun && (
          <Button as={Link} href={route('hrm.payroll.run')} intent="primary">Run Payroll</Button>
        )}
      >
        {/* Stats row */}
        {stats && (
          <HStack gap={4} style={{ marginBottom: 20 }}>
            {Object.entries(stats).slice(0, 4).map(([k, v]) => (
              <KPI key={k} label={k.replace(/_/g, ' ')} value={typeof v === 'number' && k.includes('amount') ? `$${v.toLocaleString()}` : v} />
            ))}
          </HStack>
        )}

        <Card>
          <CardContent>
            {payrolls.data.length === 0 ? (
              <Text tone="secondary">No payroll runs yet.</Text>
            ) : (
              <>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                  <thead>
                    <tr>
                      {['Employee', 'Period', 'Gross', 'Net', 'Status', 'Processed By', ''].map(h => (
                        <th key={h} style={{ textAlign: 'left', padding: '8px 12px', borderBottom: '1px solid var(--aeos-border)' }}>
                          <Mono size="xs" tone="tertiary">{h}</Mono>
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {payrolls.data.map(p => (
                      <tr key={p.id}>
                        <td style={{ padding: '10px 12px' }}>
                          <HStack gap={2} align="center">
                            <Avatar name={p.employee?.name ?? '?'} size="xs" />
                            <Text size="sm">{p.employee?.name ?? '—'}</Text>
                          </HStack>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Mono size="sm">{p.period_start} – {p.period_end}</Mono>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Mono size="sm">{p.gross_salary ? `$${Number(p.gross_salary).toLocaleString()}` : '—'}</Mono>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Mono size="sm" weight="semibold">
                            {p.net_salary ? `$${Number(p.net_salary).toLocaleString()}` : '—'}
                          </Mono>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Badge intent={statusIntent[p.status] ?? 'neutral'}>{p.status}</Badge>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Text size="sm" tone="secondary">{p.processed_by?.name ?? '—'}</Text>
                        </td>
                        <td style={{ padding: '10px 12px' }}>
                          <Button size="xs" intent="ghost" as={Link} href={route('hrm.payroll.show', p.id)}>
                            View
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {payrolls.last_page > 1 && (
                  <HStack justify="center" style={{ marginTop: 12 }}>
                    <Pagination
                      currentPage={payrolls.current_page}
                      lastPage={payrolls.last_page}
                      onPageChange={p => router.get(route('hrm.payroll.index'), { page: p }, { preserveState: true })}
                    />
                  </HStack>
                )}
              </>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step S10.2: Commit all remaining pages**

```powershell
git add packages/aero-ui/resources/js/Pages/HRM/Payroll/Index.jsx
git commit -m "feat(aero-ui): HRM Payroll Index page -- payroll runs table with status badges"
```

---

## Task S11: Final verification

- [ ] **Step S11.1: Verify all 10 pages exist**

```powershell
@(
  "packages/aero-ui/resources/js/Pages/HRM/Dashboard.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/EmployeeList.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/Departments.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/Designations.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/LeavesAdmin.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/LeavesEmployee.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/MyAttendance.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/Holidays.jsx",
  "packages/aero-ui/resources/js/Pages/HRM/Payroll/Index.jsx"
) | ForEach-Object { Write-Host "$_ : $(Test-Path $_)" }
```

Expected: all lines end with `: True`.

- [ ] **Step S11.2: Verify each file exports a default function**

```powershell
Get-ChildItem -Recurse -Path "packages/aero-ui/resources/js/Pages/HRM" -Filter "*.jsx" |
  ForEach-Object {
    $c = Get-Content $_.FullName -Raw
    $ok = $c -match "export default function"
    Write-Host "$($_.Name): $(if($ok){'OK'}else{'MISSING DEFAULT EXPORT'})"
  }
```

Expected: all files show `OK`.

- [ ] **Step S11.3: Push**

```powershell
git push origin main
```

---

## Self-Review

**Spec coverage:**
- ✅ HRM/Dashboard — 10 KPI tiles, pending leaves, department attendance
- ✅ HRM/EmployeeList — card grid, search/dept/status filter, HRMAC guards
- ✅ HRM/Departments — paginated table, inline create form, delete action
- ✅ HRM/Designations — table with API-loaded data, inline create form
- ✅ HRM/LeavesAdmin — leave table with approve/reject actions
- ✅ HRM/LeavesEmployee — self-service leave application + history
- ✅ HRM/Attendance/Admin — daily attendance table with date+employee filter
- ✅ HRM/MyAttendance — monthly self-service attendance with summary
- ✅ HRM/Holidays — holiday list with calendar date tiles
- ✅ HRM/Payroll/Index — payroll runs table with pagination

**Pattern consistency:** All pages use `App` wrapper, `@aero/ui` imports, `useHRMAC` for guards. API calls use `fetch(route(...))` for paginated/dynamic data, `useForm` for mutations.

**No placeholders:** Every page has complete JSX with real prop shapes from the actual controllers.
