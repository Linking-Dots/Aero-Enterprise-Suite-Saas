import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, HStack,
  Field, Select, Input, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_INTENT = { enrolled: 'success', waived: 'warning' };

export default function EnrollmentsIndex({ enrollments, filters, periods, benefits }) {
  const canView = useHRMAC('hrm.benefits.enrollments.view');

  const periodOptions = [
    { value: '', label: 'All Periods' },
    ...(periods ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];
  const benefitOptions = [
    { value: '', label: 'All Benefits' },
    ...(benefits ?? []).map(b => ({ value: String(b.id), label: b.name })),
  ];
  const statusOptions = [
    { value: '',         label: 'All Statuses' },
    { value: 'enrolled', label: 'Enrolled' },
    { value: 'waived',   label: 'Waived' },
  ];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.benefits.enrollments.index'),
      { ...filters, ...overrides },
      { preserveState: true, replace: true },
    );
  }

  const columns = [
    {
      key: 'employee',
      label: 'Employee',
      render: row => `${row.employee?.first_name ?? ''} ${row.employee?.last_name ?? ''}`.trim() || '—',
    },
    {
      key: 'benefit',
      label: 'Benefit',
      render: row => row.benefit?.name ?? '—',
    },
    {
      key: 'period',
      label: 'Period',
      render: row => row.period?.name ?? '—',
    },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'dependents_count',
      label: 'Dependents',
    },
    {
      key: 'employee_cost_snapshot',
      label: 'Emp. Cost',
      render: row => `$${Number(row.employee_cost_snapshot).toFixed(2)}`,
    },
    {
      key: 'elected_at',
      label: 'Elected',
    },
  ];

  if (!canView) {
    return (
      <IndexPageLayout title="Benefit Enrollments">
        <Text tone="secondary">You do not have permission to view enrollment records.</Text>
      </IndexPageLayout>
    );
  }

  return (
    <IndexPageLayout
      title="Benefit Enrollments"
      breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Enrollments' }]}
      filters={
        <HStack gap={3} wrap>
          <Field label="Period">
            <Select
              options={periodOptions}
              value={filters.period_id ? String(filters.period_id) : ''}
              onChange={e => applyFilters({ period_id: e.target.value || null })}
            />
          </Field>
          <Field label="Benefit">
            <Select
              options={benefitOptions}
              value={filters.benefit_id ? String(filters.benefit_id) : ''}
              onChange={e => applyFilters({ benefit_id: e.target.value || null })}
            />
          </Field>
          <Field label="Status">
            <Select
              options={statusOptions}
              value={filters.status ?? ''}
              onChange={e => applyFilters({ status: e.target.value || null })}
            />
          </Field>
          <Field label="Search Employee">
            <Input
              placeholder="Name..."
              defaultValue={filters.search ?? ''}
              onBlur={e => applyFilters({ search: e.target.value || null })}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={enrollments.data ?? []}
          empty="No enrollments found."
        />
      }
      pagination={
        (enrollments.last_page ?? 1) > 1 && (
          <Pagination
            total={enrollments.last_page ?? 1}
            page={enrollments.current_page ?? 1}
            onChange={p => applyFilters({ page: p })}
          />
        )
      }
    />
  );
}

EnrollmentsIndex.layout = page => <App title="Benefit Enrollments">{page}</App>;
