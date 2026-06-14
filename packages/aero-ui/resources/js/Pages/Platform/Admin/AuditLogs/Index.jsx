import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Card,
  CardBody,
  Input,
  Select,
  Modal,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EVENT_INTENT = {
  TENANT_CREATED:   'success',
  TENANT_DELETED:   'danger',
  PLAN_CREATED:     'success',
  ERROR_LOG_DELETED:'danger',
};

function eventIntent(event) {
  return EVENT_INTENT[event] ?? 'neutral';
}

function DiffView({ before, after }) {
  if (!before && !after) return <Text tone="secondary">No state diff available.</Text>;
  return (
    <VStack gap={3}>
      {before && (
        <VStack gap={1}>
          <Text tone="secondary">Before</Text>
          <pre className="overflow-auto max-h-64 text-xs font-mono aeos-glass rounded p-3">
            {JSON.stringify(before, null, 2)}
          </pre>
        </VStack>
      )}
      {after && (
        <VStack gap={1}>
          <Text tone="secondary">After</Text>
          <pre className="overflow-auto max-h-64 text-xs font-mono aeos-glass rounded p-3">
            {JSON.stringify(after, null, 2)}
          </pre>
        </VStack>
      )}
    </VStack>
  );
}

const STATUS_OPTIONS = [
  { value: '', label: 'All Events' },
];

export default function AuditLogsIndex({ logs, filters, eventTypes }) {
  const toast      = useToast();
  const canExport  = useHRMAC('audit-logs.audit-log-list.export');

  const [form, setForm]         = useState(filters ?? {});
  const [detail, setDetail]     = useState(null);

  const eventOptions = [
    { value: '', label: 'All Events' },
    ...(eventTypes ?? []).map(e => ({ value: e, label: e })),
  ];

  function applyFilters() {
    router.get(
      route('platform.admin.audit-logs.index'),
      form,
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function resetFilters() {
    const empty = {};
    setForm(empty);
    router.get(
      route('platform.admin.audit-logs.index'),
      {},
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function exportCsv() {
    const params = new URLSearchParams(
      Object.entries(form).filter(([, v]) => v)
    ).toString();
    window.location.href = route('platform.admin.audit-logs.export') + (params ? '?' + params : '');
  }

  const columns = [
    {
      key: 'created_at',
      label: 'Timestamp',
      render: (row) => <Mono>{row.created_at}</Mono>,
    },
    {
      key: 'event_type',
      label: 'Event',
      render: (row) => (
        <Badge intent={eventIntent(row.event_type ?? row.event)}>
          {row.event_type ?? row.event}
        </Badge>
      ),
    },
    {
      key: 'actor',
      label: 'Actor',
      render: (row) => (
        <Text>{row.actor_name ? `${row.actor_name} #${row.actor_id}` : (row.actor_id ? `#${row.actor_id}` : '—')}</Text>
      ),
    },
    {
      key: 'subject',
      label: 'Subject',
      render: (row) => (
        <Text>
          {row.subject_type ? `${row.subject_type} #${row.subject_id ?? '—'}` : '—'}
        </Text>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => (
        <Text tone="secondary">{row.description ?? '—'}</Text>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <Button intent="ghost" size="sm" onClick={() => setDetail(row)}>
          View
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Audit Logs"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Audit Logs' },
      ]}
      description="Full audit trail of all operator and system actions."
      actions={
        canExport && (
          <Button intent="soft" onClick={exportCsv}>
            Export CSV
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3} wrap>
                <Select
                  value={form.event ?? ''}
                  onChange={e => setForm({ ...form, event: e.target.value })}
                  options={eventOptions}
                />
                <Input
                  placeholder="Actor ID"
                  value={form.actor_id ?? ''}
                  onChange={e => setForm({ ...form, actor_id: e.target.value })}
                />
                <Input
                  placeholder="Subject type"
                  value={form.subject_type ?? ''}
                  onChange={e => setForm({ ...form, subject_type: e.target.value })}
                />
                <input
                  type="date"
                  className="aeos-text-sm"
                  value={form.from ?? ''}
                  onChange={e => setForm({ ...form, from: e.target.value })}
                />
                <input
                  type="date"
                  className="aeos-text-sm"
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
          empty="No audit log entries."
        />

        {(logs?.last_page ?? 1) > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.audit-logs.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['logs'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={!!detail}
        onClose={() => setDetail(null)}
        title={`Audit Log #${detail?.id}`}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => setDetail(null)}>Close</Button>
        }
      >
        {detail && (
          <VStack gap={3}>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">Event</Text>
                <Badge intent={eventIntent(detail.event_type ?? detail.event)}>
                  {detail.event_type ?? detail.event}
                </Badge>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">Action</Text>
                <Mono>{detail.action ?? '—'}</Mono>
              </VStack>
            </HStack>
            <VStack gap={0}>
              <Text tone="secondary">Actor</Text>
              <Text>{detail.actor_name} {detail.actor_id ? `(#${detail.actor_id})` : ''}</Text>
            </VStack>
            <VStack gap={0}>
              <Text tone="secondary">Subject</Text>
              <Text>{detail.subject_type ? `${detail.subject_type} #${detail.subject_id}` : '—'}</Text>
            </VStack>
            {detail.subject_label && (
              <VStack gap={0}>
                <Text tone="secondary">Subject Label</Text>
                <Text>{detail.subject_label}</Text>
              </VStack>
            )}
            <VStack gap={0}>
              <Text tone="secondary">Description</Text>
              <Text>{detail.description ?? '—'}</Text>
            </VStack>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">IP Address</Text>
                <Mono>{detail.actor_ip ?? '—'}</Mono>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">Timestamp</Text>
                <Mono>{detail.created_at}</Mono>
              </VStack>
            </HStack>
            {detail.actor_user_agent && (
              <VStack gap={0}>
                <Text tone="secondary">User Agent</Text>
                <Text tone="secondary">{detail.actor_user_agent}</Text>
              </VStack>
            )}
            <DiffView before={detail.before_state} after={detail.after_state} />
            {detail.metadata && (
              <VStack gap={1}>
                <Text tone="secondary">Metadata</Text>
                <pre className="overflow-auto max-h-48 text-xs font-mono aeos-glass rounded p-3">
                  {JSON.stringify(detail.metadata, null, 2)}
                </pre>
              </VStack>
            )}
          </VStack>
        )}
      </Modal>
    </IndexPageLayout>
  );
}

AuditLogsIndex.layout = page => <App title="Audit Logs">{page}</App>;
