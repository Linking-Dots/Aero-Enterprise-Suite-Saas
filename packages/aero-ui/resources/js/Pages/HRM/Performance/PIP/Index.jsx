import { router, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, Pagination,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'active':    return 'primary';
    case 'completed': return 'success';
    case 'failed':    return 'danger';
    case 'cancelled': return 'neutral';
    default:          return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function PIPIndex({ pips }) {
  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => row.employee?.user?.name ?? '—',
    },
    { key: 'title', label: 'Title' },
    {
      key: 'start_date', label: 'Start',
      render: row => row.start_date ?? '—',
    },
    {
      key: 'end_date', label: 'End',
      render: row => row.end_date ?? '—',
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.performance.pip.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages  = pips.last_page    ?? 1;
  const currentPage = pips.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Performance Improvement Plans"
      breadcrumb={[{ label: 'HRM' }, { label: 'Performance' }, { label: 'PIPs' }]}
      actions={
        <Button
          as={Link}
          href={route('hrm.performance.pip.create')}
          intent="primary"
        >
          New PIP
        </Button>
      }
      table={
        <DataTable
          columns={columns}
          rows={pips.data ?? []}
          empty="No performance improvement plans found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.performance.pip.index'), { page }, { preserveState: true })}
          />
        )
      }
    />
  );
}

PIPIndex.layout = page => <App title="Performance Improvement Plans">{page}</App>;
