import { router, Link } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, HStack, Field, Input, Select, Pagination,
} from '@aero/ui';
import { ArrowRightIcon } from '@heroicons/react/24/outline';

const STATUS_COLORS = {
  active:      'success',
  probation:   'warning',
  on_leave:    'neutral',
  terminated:  'danger',
  resigned:    'neutral',
};

export default function EmployeesIndex({ employees, filters, departments, statuses, employmentTypes }) {
  const [search, setSearch] = useState(filters.search ?? '');

  function applyFilters(overrides = {}) {
    router.get(route('hrm.employees.index'), {
      ...filters,
      search: search || undefined,
      ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true });
  }

  const columns = [
    { key: 'employee_code', label: 'Code' },
    { key: 'name',          label: 'Name' },
    { key: 'department',    label: 'Department' },
    { key: 'designation',   label: 'Designation' },
    { key: 'employment_type', label: 'Type' },
    {
      key: 'status', label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_COLORS[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    { key: 'date_of_joining', label: 'Joined' },
    {
      key: 'actions', label: '',
      render: (row) => (
        <Button
          as={Link}
          href={route('hrm.employees.show', row.id)}
          size="sm"
          intent="ghost"
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages = employees.last_page ?? 1;
  const currentPage = employees.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Employees"
      breadcrumb={[{ label: 'HRM' }, { label: 'Employees' }]}
      actions={
        <Button as={Link} href={route('hrm.employees.create')} intent="primary">
          New Employee
        </Button>
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()}
              placeholder="Name, code, email"
            />
          </Field>
          <Field label="Department">
            <Select
              value={filters.department_id ?? ''}
              onChange={e => applyFilters({ department_id: e.target.value || undefined })}
            >
              <option value="">All departments</option>
              {departments.map(d => (
                <option key={d.id} value={d.id}>{d.name}</option>
              ))}
            </Select>
          </Field>
          <Field label="Status">
            <Select
              value={filters.status ?? ''}
              onChange={e => applyFilters({ status: e.target.value || undefined })}
            >
              <option value="">All statuses</option>
              {statuses.map(s => (
                <option key={s} value={s}>{s}</option>
              ))}
            </Select>
          </Field>
          <Field label="Type">
            <Select
              value={filters.employment_type ?? ''}
              onChange={e => applyFilters({ employment_type: e.target.value || undefined })}
            >
              <option value="">All types</option>
              {employmentTypes.map(t => (
                <option key={t} value={t}>{t}</option>
              ))}
            </Select>
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={employees.data ?? []}
          empty="No employees match the current filters."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => applyFilters({ page })}
          />
        )
      }
    />
  );
}

EmployeesIndex.layout = page => <App title="Employees">{page}</App>;
