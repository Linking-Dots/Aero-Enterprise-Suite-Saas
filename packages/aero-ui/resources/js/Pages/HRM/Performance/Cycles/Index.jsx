import { router, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, Pagination,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'active': return 'primary';
    case 'closed': return 'neutral';
    default:       return 'neutral'; // draft
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function CyclesIndex({ cycles }) {
  const columns = [
    { key: 'name', label: 'Name' },
    {
      key: 'template', label: 'Template',
      render: row => row.template?.name ?? '—',
    },
    {
      key: 'period', label: 'Period',
      render: row => `${row.starts_on} — ${row.ends_on}`,
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
          onClick={() => router.get(route('hrm.performance.cycles.show', row.id))}
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages   = cycles.last_page    ?? 1;
  const currentPage  = cycles.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Performance Cycles"
      breadcrumb={[{ label: 'HRM' }, { label: 'Performance' }, { label: 'Cycles' }]}
      actions={
        <Button
          as={Link}
          href={route('hrm.performance.cycles.create')}
          intent="primary"
        >
          New Cycle
        </Button>
      }
      table={
        <DataTable
          columns={columns}
          rows={cycles.data ?? []}
          empty="No performance cycles found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.performance.cycles.index'), { page }, { preserveState: true })}
          />
        )
      }
    />
  );
}

CyclesIndex.layout = page => <App title="Performance Cycles">{page}</App>;
