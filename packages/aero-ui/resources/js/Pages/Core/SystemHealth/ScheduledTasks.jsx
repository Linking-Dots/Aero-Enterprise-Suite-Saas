import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  Text,
  Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  running:  'success',
  idle:     'neutral',
  failed:   'danger',
  overdue:  'warning',
  disabled: 'neutral',
};

export default function SystemHealthScheduledTasks({ tasks }) {
  const toast    = useToast();
  const [running, setRunning] = useState(null); // task name being run

  const runTask = name => {
    setRunning(name);
    router.post(route('core.system-health.scheduled-tasks.run', { name }), {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success(`Task "${name}" dispatched.`),
      onError:   () => toast.error(`Failed to run "${name}".`),
      onFinish:  () => setRunning(null),
    });
  };

  const columns = [
    {
      key: 'name',
      label: 'Task Name',
      width: '24%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'expression',
      label: 'Schedule',
      width: '16%',
      render: row => <Mono size="sm">{row.expression || '—'}</Mono>,
    },
    {
      key: 'last_run_at',
      label: 'Last Run',
      width: '18%',
      render: row => (
        <Mono size="sm">
          {row.last_run_at ? new Date(row.last_run_at).toLocaleString() : 'Never'}
        </Mono>
      ),
    },
    {
      key: 'next_run_at',
      label: 'Next Run',
      width: '18%',
      render: row => (
        <Mono size="sm">
          {row.next_run_at ? new Date(row.next_run_at).toLocaleString() : '—'}
        </Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      width: '12%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status ?? 'unknown'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '12%',
      align: 'right',
      render: row => (
        <Button
          intent="soft"
          size="sm"
          loading={running === row.name}
          onClick={() => runTask(row.name)}
          leftIcon="play"
        >
          Run Now
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Scheduled Tasks"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Health', href: route('core.system-health.index') },
        { label: 'Scheduled Tasks' },
      ]}
      description="Monitor and manually trigger Laravel scheduled commands."
      table={
        <DataTable
          columns={columns}
          rows={tasks ?? []}
          empty="No scheduled tasks registered."
        />
      }
    />
  );
}

SystemHealthScheduledTasks.layout = page => (
  <App title="Scheduled Tasks">{page}</App>
);
