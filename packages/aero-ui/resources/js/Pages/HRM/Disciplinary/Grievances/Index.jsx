import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Pagination, Badge,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'filed',              label: 'Filed' },
  { value: 'under_investigation', label: 'Under Investigation' },
  { value: 'resolved',           label: 'Resolved' },
  { value: 'dismissed',          label: 'Dismissed' },
];

const STATUS_INTENT = {
  filed:               'warning',
  under_investigation: 'info',
  resolved:            'success',
  dismissed:           'neutral',
};

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

export default function GrievancesIndex({ grievances, filters }) {
  const canUpdate = useHRMAC('hrm.grievances.grievance-list.update');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.grievances.index'),
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
          onClick={() => router.get(route('hrm.grievances.show', row.id))}
        >
          {row.reference ?? `#${row.id}`}
        </Button>
      ),
    },
    { key: 'category',        label: 'Category',        render: row => row.category ?? '—' },
    { key: 'subject',         label: 'Subject',          render: row => row.subject ?? '—' },
    {
      key: 'confidentiality', label: 'Confidentiality',
      render: row => (
        <Badge intent={row.confidentiality === 'anonymous' ? 'danger' : row.confidentiality === 'confidential' ? 'warning' : 'neutral'}>
          {row.confidentiality ?? 'standard'}
        </Badge>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
    { key: 'filed_at', label: 'Filed At', render: row => row.created_at ?? '—' },
  ];

  return (
    <IndexPageLayout
      title="Grievances"
      breadcrumb={[{ label: 'HRM' }, { label: 'Grievances' }]}
      actions={
        canUpdate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.grievances.create'))}
          >
            File Grievance
          </Button>
        )
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              placeholder="Search grievances..."
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
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={grievances.data ?? []}
          empty="No grievances found."
        />
      }
      pagination={
        (grievances.last_page ?? 1) > 1 && (
          <Pagination
            total={grievances.last_page ?? 1}
            page={grievances.current_page ?? 1}
            onChange={p => applyFilters({ page: p })}
          />
        )
      }
    />
  );
}

GrievancesIndex.layout = page => <App title="Grievances">{page}</App>;
