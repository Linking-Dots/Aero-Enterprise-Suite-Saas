import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Mono,
  Input,
  Select,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  pending:   'neutral',
  running:   'warning',
  completed: 'success',
  failed:    'danger',
};

export default function WorkflowInstancesIndex({ instances, filters }) {
  const toast = useToast();

  const [search,     setSearch]     = useState(filters?.search     || '');
  const [statusFilter, setStatusFilter] = useState(filters?.status || '');

  const applyFilters = () => {
    router.get(route('workflow-instances.index'), { search, status: statusFilter }, {
      preserveState: true, preserveScroll: true, only: ['instances', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setStatusFilter('');
    router.get(route('workflow-instances.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['instances', 'filters'],
    });
  };

  const retryInstance = id => {
    if (!confirm('Retry this failed workflow instance?')) return;
    router.post(route('workflow-instances.retry', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Workflow instance queued for retry.'),
      onError:   () => toast.error('Failed to retry workflow instance.'),
    });
  };

  const rows = instances?.data ?? [];

  const columns = [
    {
      key: 'workflow', label: 'Workflow', width: '22%',
      render: row => (
        <Text size="sm">{row.workflow_name ?? row.workflow?.name ?? '—'}</Text>
      ),
    },
    {
      key: 'triggered_by', label: 'Triggered By', width: '18%',
      render: row => (
        <Text size="sm" tone="secondary">{row.triggered_by ?? '—'}</Text>
      ),
    },
    {
      key: 'current_step', label: 'Current Step', width: '18%',
      render: row => (
        row.current_step
          ? <Mono size="sm">{row.current_step}</Mono>
          : <Text size="sm" tone="secondary">—</Text>
      ),
    },
    {
      key: 'status', label: 'Status', width: '14%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'created_at', label: 'Started', width: '16%',
      render: row => (
        <Text size="sm">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</Text>
      ),
    },
    {
      key: 'actions', label: '', width: '12%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          <Button
            intent="soft"
            size="sm"
            onClick={() => router.get(route('workflow-instances.show', row.id))}
          >
            View
          </Button>
          {row.status === 'failed' && (
            <Button
              intent="ghost"
              size="sm"
              leftIcon="refresh"
              onClick={() => retryInstance(row.id)}
            >
              Retry
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Workflow Instances"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Workflows', href: route('core.workflows.index') },
        { label: 'Instances' },
      ]}
      description="Track running and historical workflow execution instances."
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search workflow name…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
            options={[
              { value: '',          label: 'All Statuses' },
              { value: 'pending',   label: 'Pending'   },
              { value: 'running',   label: 'Running'   },
              { value: 'completed', label: 'Completed' },
              { value: 'failed',    label: 'Failed'    },
            ]}
          />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={rows}
          empty="No workflow instances found."
        />
      }
      pagination={
        instances?.last_page > 1 && (
          <Pagination
            page={instances.current_page}
            total={instances.last_page}
            onChange={page => router.get(route('workflow-instances.index'), { page, search, status: statusFilter }, {
              preserveState: true, preserveScroll: true, only: ['instances'],
            })}
          />
        )
      }
    />
  );
}

WorkflowInstancesIndex.layout = page => (
  <App title="Workflow Instances">{page}</App>
);
