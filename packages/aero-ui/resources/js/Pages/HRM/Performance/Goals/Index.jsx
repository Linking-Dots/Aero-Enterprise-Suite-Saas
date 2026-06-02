import { router, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, HStack, Progress, Pagination,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'achieved': return 'success';
    case 'missed':   return 'danger';
    case 'open':     return 'primary';
    default:         return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function GoalsIndex({ goals, isAdmin }) {
  function closeGoal(id, outcome) {
    router.post(
      route('hrm.performance.goals.close', id),
      { outcome },
      { preserveState: true, preserveScroll: true },
    );
  }

  const columns = [
    { key: 'title', label: 'Title' },
    {
      key: 'time_bound', label: 'Deadline',
      render: row => row.time_bound ?? '—',
    },
    {
      key: 'progress', label: 'Progress',
      render: row => (
        <HStack gap={2} align="center">
          <Progress value={row.progress ?? 0} />
          <span className="aeos-text-sm aeos-text-secondary">{row.progress ?? 0}%</span>
        </HStack>
      ),
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
        <HStack gap={2}>
          {row.status === 'open' && (
            <>
              <Button
                intent="soft"
                size="sm"
                onClick={() => closeGoal(row.id, 'achieved')}
              >
                Achieved
              </Button>
              <Button
                intent="danger"
                size="sm"
                onClick={() => closeGoal(row.id, 'missed')}
              >
                Missed
              </Button>
            </>
          )}
        </HStack>
      ),
    },
  ];

  const totalPages  = goals.last_page    ?? 1;
  const currentPage = goals.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Goals"
      breadcrumb={[{ label: 'HRM' }, { label: 'Performance' }, { label: 'Goals' }]}
      actions={
        <Button
          as={Link}
          href={route('hrm.performance.goals.create')}
          intent="primary"
        >
          New Goal
        </Button>
      }
      table={
        <DataTable
          columns={columns}
          rows={goals.data ?? []}
          empty="No goals found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.performance.goals.index'), { page }, { preserveState: true })}
          />
        )
      }
    />
  );
}

GoalsIndex.layout = page => <App title="Goals">{page}</App>;
