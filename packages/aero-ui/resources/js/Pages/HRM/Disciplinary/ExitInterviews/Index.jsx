import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Pagination, Badge,
} from '@aero/ui';

const STATUS_INTENT = {
  scheduled:  'warning',
  completed:  'success',
  cancelled:  'neutral',
};

function statusLabel(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
}

export default function ExitInterviewsIndex({ interviews }) {
  const canCreate = useHRMAC('hrm.exit-interviews.exit-interview-list.create');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.exit-interviews.index'),
      { ...overrides },
      { preserveState: true, replace: true },
    );
  }

  const columns = [
    { key: 'employee',      label: 'Employee',      render: row => row.employee?.name ?? '—' },
    { key: 'scheduled_for', label: 'Scheduled For', render: row => row.scheduled_for ?? '—' },
    { key: 'interviewer',   label: 'Interviewer',   render: row => row.interviewer?.name ?? '—' },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          size="sm"
          intent="soft"
          onClick={() => router.get(route('hrm.exit-interviews.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Exit Interviews"
      breadcrumb={[{ label: 'HRM' }, { label: 'Exit Interviews' }]}
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.exit-interviews.create'))}
          >
            Schedule Interview
          </Button>
        )
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              placeholder="Search by employee name..."
              onBlur={e => applyFilters({ search: e.target.value })}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={interviews.data ?? []}
          empty="No exit interviews found."
        />
      }
      pagination={
        (interviews.last_page ?? 1) > 1 && (
          <Pagination
            total={interviews.last_page ?? 1}
            page={interviews.current_page ?? 1}
            onChange={p => applyFilters({ page: p })}
          />
        )
      }
    />
  );
}

ExitInterviewsIndex.layout = page => <App title="Exit Interviews">{page}</App>;
