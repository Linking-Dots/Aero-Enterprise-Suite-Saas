import { router, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, Badge, Select, Pagination, Text, Mono,
} from '@aero/ui';
import { useHRMAC } from '@/hooks/useHRMAC';

const STATUS_OPTIONS = [
  { value: '',         label: 'All Statuses' },
  { value: 'draft',    label: 'Draft' },
  { value: 'approved', label: 'Approved' },
  { value: 'locked',   label: 'Locked' },
];

function statusIntent(run) {
  if (run.locked_at) return 'neutral';
  switch (run.status) {
    case 'approved': return 'success';
    case 'draft':    return 'warning';
    default:         return 'neutral';
  }
}

function statusLabel(run) {
  if (run.locked_at) return 'Locked';
  return run.status.charAt(0).toUpperCase() + run.status.slice(1);
}

export default function RunsIndex({ runs, filters }) {
  const canExecute = useHRMAC('hrm.payroll.payroll-run.execute');

  const totalPages  = runs.last_page    ?? 1;
  const currentPage = runs.current_page ?? 1;

  function applyFilter(status) {
    router.get(route('hrm.payroll.runs.index'), { status }, { preserveState: true });
  }

  const columns = [
    { key: 'label', label: 'Label' },
    {
      key: 'period', label: 'Period',
      render: row => (
        <Mono>
          {row.period_start} &rarr; {row.period_end}
        </Mono>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row)}>{statusLabel(row)}</Badge>
      ),
    },
    {
      key: 'total_gross', label: 'Total Gross',
      render: row => Number(row.total_gross ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'total_net', label: 'Total Net',
      render: row => Number(row.total_net ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.payroll.runs.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Payroll Runs"
      breadcrumb={[{ label: 'HRM' }, { label: 'Payroll' }, { label: 'Runs' }]}
      actions={
        <HStack gap={3} align="center">
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilter(e.target.value)}
          />
          {canExecute && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.payroll.runs.create'))}
            >
              New Run
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={runs.data ?? []}
          empty="No payroll runs found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page =>
              router.get(
                route('hrm.payroll.runs.index'),
                { page, status: filters?.status ?? '' },
                { preserveState: true },
              )
            }
          />
        )
      }
    />
  );
}

RunsIndex.layout = page => <App title="Payroll Runs">{page}</App>;
