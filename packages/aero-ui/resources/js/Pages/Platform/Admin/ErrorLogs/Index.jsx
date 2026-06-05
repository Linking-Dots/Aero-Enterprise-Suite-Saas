import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Pagination,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Input,
  Select,
  Checkbox,
  Modal,
  Alert,
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'open', label: 'Open' },
  { value: 'resolved', label: 'Resolved' },
];

function statusIntent(status) {
  return status === 'resolved' ? 'success' : 'warning';
}

export default function ErrorLogsIndex({ logs, filters }) {
  const toast         = useToast();
  const canResolve    = useHRMAC('error-monitoring.error-log-list.resolve');
  const canDelete     = useHRMAC('error-monitoring.error-log-list.delete');

  const [form, setForm]           = useState(filters ?? {});
  const [selected, setSelected]   = useState([]);
  const [resolveTarget, setResolveTarget] = useState(null);
  const [deleteTarget, setDeleteTarget]   = useState(null);
  const [bulkAction, setBulkAction]       = useState(null); // 'resolve' | 'delete'
  const [submitting, setSubmitting]       = useState(false);

  function toggleRow(id) {
    setSelected(s => s.includes(id) ? s.filter(i => i !== id) : [...s, id]);
  }

  function toggleAll() {
    const allIds = (logs?.data ?? []).map(r => r.id);
    setSelected(s => s.length === allIds.length ? [] : allIds);
  }

  function applyFilters() {
    router.get(
      route('platform.admin.error-logs.index'),
      form,
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function resetFilters() {
    setForm({});
    router.get(
      route('platform.admin.error-logs.index'),
      {},
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function confirmResolve(log) {
    setResolveTarget(log);
  }

  function confirmDelete(log) {
    setDeleteTarget(log);
  }

  function doResolve() {
    setSubmitting(true);
    router.post(
      route('platform.admin.error-logs.resolve', resolveTarget.id),
      {},
      {
        onSuccess: () => { setResolveTarget(null); toast.success('Error log resolved.'); },
        onError: () => toast.error('Failed to resolve error log.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.error-logs.destroy', deleteTarget.id),
      {
        onSuccess: () => { setDeleteTarget(null); toast.success('Error log deleted.'); },
        onError: () => toast.error('Failed to delete error log.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  function doBulkResolve() {
    setSubmitting(true);
    router.post(
      route('platform.admin.error-logs.bulk-resolve'),
      { ids: selected },
      {
        onSuccess: () => { setBulkAction(null); setSelected([]); toast.success('Selected errors resolved.'); },
        onError: () => toast.error('Bulk resolve failed.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  function doBulkDelete() {
    setSubmitting(true);
    router.post(
      route('platform.admin.error-logs.bulk-destroy'),
      { ids: selected },
      {
        onSuccess: () => { setBulkAction(null); setSelected([]); toast.success('Selected errors deleted.'); },
        onError: () => toast.error('Bulk delete failed.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  const allIds = (logs?.data ?? []).map(r => r.id);
  const allSelected = allIds.length > 0 && selected.length === allIds.length;

  const columns = [
    {
      key: 'select',
      label: (
        <Checkbox
          checked={allSelected}
          onChange={toggleAll}
        />
      ),
      render: (row) => (
        <Checkbox
          checked={selected.includes(row.id)}
          onChange={() => toggleRow(row.id)}
        />
      ),
    },
    {
      key: 'type',
      label: 'Type',
      render: (row) => <Badge intent="neutral">{row.type}</Badge>,
    },
    {
      key: 'message',
      label: 'Message',
      render: (row) => (
        <a href={route('platform.admin.error-logs.show', row.id)} className="aeos-text-primary">
          <Text>{row.message}</Text>
        </a>
      ),
    },
    {
      key: 'tenant_id',
      label: 'Tenant',
      render: (row) => <Mono>{row.tenant_id ?? '—'}</Mono>,
    },
    {
      key: 'occurrence_count',
      label: 'Count',
      render: (row) => <Text>{row.occurrence_count}</Text>,
    },
    {
      key: 'last_occurred_at',
      label: 'Last Seen',
      render: (row) => <Mono>{row.last_occurred_at}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={statusIntent(row.status)}>{row.status}</Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canResolve && row.status === 'open' && (
            <Button intent="soft" size="sm" onClick={() => confirmResolve(row)}>
              Resolve
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => confirmDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Error Logs"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Error Logs' },
      ]}
      description="Monitor and manage application errors across all tenants."
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.error-logs.analytics'))}
          >
            Analytics
          </Button>
          {canResolve && selected.length > 0 && (
            <Button intent="soft" onClick={() => setBulkAction('resolve')}>
              Resolve {selected.length}
            </Button>
          )}
          {canDelete && selected.length > 0 && (
            <Button intent="danger" onClick={() => setBulkAction('delete')}>
              Delete {selected.length}
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3} wrap>
                <Select
                  value={form.status ?? ''}
                  onChange={e => setForm({ ...form, status: e.target.value })}
                  options={STATUS_OPTIONS}
                />
                <Input
                  placeholder="Tenant ID"
                  value={form.tenant_id ?? ''}
                  onChange={e => setForm({ ...form, tenant_id: e.target.value })}
                />
                <Input
                  placeholder="Error type"
                  value={form.type ?? ''}
                  onChange={e => setForm({ ...form, type: e.target.value })}
                />
                <Input
                  type="date"
                  placeholder="From"
                  value={form.from ?? ''}
                  onChange={e => setForm({ ...form, from: e.target.value })}
                />
                <Input
                  type="date"
                  placeholder="To"
                  value={form.to ?? ''}
                  onChange={e => setForm({ ...form, to: e.target.value })}
                />
              </HStack>
              <HStack gap={2}>
                <Button intent="soft" onClick={applyFilters}>Apply Filters</Button>
                <Button intent="ghost" onClick={resetFilters}>Reset</Button>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <DataTable
          columns={columns}
          rows={logs?.data ?? []}
          empty="No error logs."
        />

        {(logs?.last_page ?? 1) > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.error-logs.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['logs'] }
              )
            }
          />
        )}
      </VStack>

      {/* Single resolve confirmation */}
      <Modal
        open={!!resolveTarget}
        onClose={() => setResolveTarget(null)}
        title={`Resolve error #${resolveTarget?.id}?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setResolveTarget(null)}>Cancel</Button>
            <Button intent="soft" loading={submitting} disabled={submitting} onClick={doResolve}>
              Resolve
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will mark error #{resolveTarget?.id} as resolved and stop further notifications.
        </Text>
      </Modal>

      {/* Single delete confirmation */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title={`Delete error #${deleteTarget?.id}?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doDelete}>
              Delete
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This permanently deletes the error log entry. This action cannot be undone.
        </Text>
      </Modal>

      {/* Bulk resolve confirmation */}
      <Modal
        open={bulkAction === 'resolve'}
        onClose={() => setBulkAction(null)}
        title={`Resolve ${selected.length} errors?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setBulkAction(null)}>Cancel</Button>
            <Button intent="soft" loading={submitting} disabled={submitting} onClick={doBulkResolve}>
              Resolve All
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will mark {selected.length} selected error{selected.length !== 1 ? 's' : ''} as resolved.
        </Text>
      </Modal>

      {/* Bulk delete confirmation */}
      <Modal
        open={bulkAction === 'delete'}
        onClose={() => setBulkAction(null)}
        title={`Delete ${selected.length} errors?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setBulkAction(null)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doBulkDelete}>
              Delete All
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This permanently deletes {selected.length} error log{selected.length !== 1 ? 's' : ''}. This action cannot be undone.
        </Text>
      </Modal>
    </IndexPageLayout>
  );
}

ErrorLogsIndex.layout = page => <App title="Error Logs">{page}</App>;
