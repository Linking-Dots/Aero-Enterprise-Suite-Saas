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

export default function AuditLogsQueues({ failed_jobs }) {
  const toast    = useToast();
  const [flushing,  setFlushing]  = useState(false);
  const [retrying,  setRetrying]  = useState(null); // job id being retried

  const retryJob = id => {
    setRetrying(id);
    router.post(route('core.audit-logs.queues.retry', id), {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Job queued for retry.'),
      onError:   () => toast.error('Failed to retry job.'),
      onFinish:  () => setRetrying(null),
    });
  };

  const flushAll = () => {
    if (!confirm('Permanently delete all failed jobs? This cannot be undone.')) return;
    setFlushing(true);
    router.post(route('core.audit-logs.queues.flush'), {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('All failed jobs flushed.'),
      onError:   () => toast.error('Failed to flush jobs.'),
      onFinish:  () => setFlushing(false),
    });
  };

  const columns = [
    {
      key: 'id',
      label: 'ID',
      width: '8%',
      render: row => <Mono size="sm">{row.id}</Mono>,
    },
    {
      key: 'queue',
      label: 'Queue',
      width: '14%',
      render: row => <Badge intent="neutral">{row.queue || 'default'}</Badge>,
    },
    {
      key: 'payload',
      label: 'Job',
      width: '24%',
      render: row => {
        const raw = typeof row.payload === 'string' ? row.payload : JSON.stringify(row.payload ?? {});
        const snippet = raw.slice(0, 80) + (raw.length > 80 ? '…' : '');
        return <Mono size="sm">{snippet}</Mono>;
      },
    },
    {
      key: 'exception',
      label: 'Exception',
      width: '28%',
      render: row => {
        const exc = row.exception ?? '';
        const snippet = exc.slice(0, 100) + (exc.length > 100 ? '…' : '');
        return <Text size="sm" tone="secondary">{snippet || '—'}</Text>;
      },
    },
    {
      key: 'failed_at',
      label: 'Failed At',
      width: '14%',
      render: row => (
        <Mono size="sm">
          {row.failed_at ? new Date(row.failed_at).toLocaleString() : '—'}
        </Mono>
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
          loading={retrying === row.id}
          onClick={() => retryJob(row.id)}
          leftIcon="arrowPath"
        >
          Retry
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Failed Jobs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Audit Logs', href: route('core.audit-logs.activity') },
        { label: 'Queues' },
      ]}
      description="Monitor and manage failed queue jobs."
      actions={
        <Button
          intent="danger"
          loading={flushing}
          onClick={flushAll}
          leftIcon="trash"
        >
          Flush All
        </Button>
      }
      table={
        <DataTable
          columns={columns}
          rows={failed_jobs?.data ?? []}
          empty="No failed jobs. The queue is clean."
        />
      }
      pagination={
        failed_jobs?.next_page_url || failed_jobs?.prev_page_url ? (
          <HStack gap={2}>
            <Button
              intent="ghost"
              size="sm"
              disabled={!failed_jobs?.prev_page_url}
              onClick={() => router.get(failed_jobs.prev_page_url, {}, { preserveState: true })}
              leftIcon="chevronLeft"
            >
              Previous
            </Button>
            <Text size="sm" tone="secondary">
              {failed_jobs?.total ?? 0} total failed jobs
            </Text>
            <Button
              intent="ghost"
              size="sm"
              disabled={!failed_jobs?.next_page_url}
              onClick={() => router.get(failed_jobs.next_page_url, {}, { preserveState: true })}
              rightIcon="chevronRight"
            >
              Next
            </Button>
          </HStack>
        ) : null
      }
    />
  );
}

AuditLogsQueues.layout = page => (
  <App title="Failed Jobs">{page}</App>
);
