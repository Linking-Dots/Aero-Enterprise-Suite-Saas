import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',            label: 'All Statuses' },
  { value: 'scheduled',   label: 'Scheduled' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed',   label: 'Completed' },
];

function statusIntent(status) {
  switch (status) {
    case 'scheduled':   return 'info';
    case 'in_progress': return 'warning';
    case 'completed':   return 'success';
    default:            return 'neutral';
  }
}

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function InspectionsIndex({ inspections, filters }) {
  const canCreate = useHRMAC('hrm.safety.safety-inspections.create');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.safety.inspections.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = inspections.last_page    ?? 1;
  const currentPage = inspections.current_page ?? 1;

  const columns = [
    {
      key: 'reference', label: 'Reference',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.safety.inspections.show', row.id))}
        >
          {row.reference ?? `#${row.id}`}
        </Button>
      ),
    },
    {
      key: 'title', label: 'Title',
      render: row => <Text>{row.title ?? '—'}</Text>,
    },
    {
      key: 'scheduled_date', label: 'Scheduled Date',
      render: row => <Text>{fmtDate(row.scheduled_date)}</Text>,
    },
    {
      key: 'inspector', label: 'Inspector',
      render: row => <Text>{row.inspector?.name ?? row.inspector?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Safety Inspections"
      breadcrumb={[{ label: 'HRM' }, { label: 'Safety' }, { label: 'Inspections' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.safety.inspections.create'))}
            >
              Create Inspection
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={inspections.data ?? []}
          empty="No inspections found."
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

InspectionsIndex.layout = page => <App title="Safety Inspections">{page}</App>;
