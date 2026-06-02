import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'open',               label: 'Open' },
  { value: 'awaiting_response',  label: 'Awaiting Response' },
  { value: 'under_review',       label: 'Under Review' },
  { value: 'closed',             label: 'Closed' },
];

const STATUS_INTENT = {
  open:               'warning',
  awaiting_response:  'primary',
  under_review:       'info',
  closed:             'success',
};

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

export default function CasesIndex({ cases, filters, actionTypes }) {
  const canCreate = useHRMAC('hrm.disciplinary.disciplinary-cases.create');

  const actionTypeOptions = [
    { value: '', label: 'All Action Types' },
    ...(actionTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.disciplinary.cases.index'),
      { ...filters, ...overrides, page: 1 },
      { preserveState: true, replace: true },
    );
  }

  const columns = [
    {
      key: 'reference', label: 'Reference',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.disciplinary.cases.show', row.id))}
        >
          {row.reference ?? `#${row.id}`}
        </Button>
      ),
    },
    { key: 'employee',    label: 'Employee',    render: row => row.employee?.name ?? '—' },
    { key: 'action_type', label: 'Action Type', render: row => row.actionType?.name ?? '—' },
    { key: 'subject',     label: 'Subject',     render: row => row.subject ?? '—' },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
    { key: 'incident_date', label: 'Incident Date', render: row => row.incident_date ?? '—' },
  ];

  return (
    <IndexPageLayout
      title="Disciplinary Cases"
      breadcrumb={[{ label: 'HRM' }, { label: 'Disciplinary' }, { label: 'Cases' }]}
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.disciplinary.cases.create'))}
          >
            New Case
          </Button>
        )
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              placeholder="Search cases..."
              defaultValue={filters?.search ?? ''}
              onBlur={e => applyFilters({ search: e.target.value })}
            />
          </Field>
          <Field label="Status">
            <Select
              options={STATUS_OPTIONS}
              value={filters?.status ?? ''}
              onChange={e => applyFilters({ status: e.target.value })}
            />
          </Field>
          <Field label="Action Type">
            <Select
              options={actionTypeOptions}
              value={filters?.action_type_id ?? ''}
              onChange={e => applyFilters({ action_type_id: e.target.value })}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={cases.data ?? []}
          empty="No disciplinary cases found."
        />
      }
      pagination={
        (cases.last_page ?? 1) > 1 && (
          <Pagination
            total={cases.last_page ?? 1}
            page={cases.current_page ?? 1}
            onChange={p => applyFilters({ page: p })}
          />
        )
      }
    />
  );
}

CasesIndex.layout = page => <App title="Disciplinary Cases">{page}</App>;
