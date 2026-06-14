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
  Eyebrow,
  Field,
  Input,
  Select,
  Modal,
  Card,
  CardBody,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EVENT_OPTIONS = [
  { value: '',                    label: 'All Security Events' },
  { value: 'LOGIN_SUCCESS',       label: 'Login Success' },
  { value: 'LOGIN_FAILED',        label: 'Login Failed' },
  { value: 'LOGOUT',              label: 'Logout' },
  { value: 'MFA_VERIFIED',        label: 'MFA Verified' },
  { value: 'MFA_FAILED',          label: 'MFA Failed' },
  { value: 'PASSWORD_CHANGED',    label: 'Password Changed' },
  { value: 'ROLE_ASSIGNED',       label: 'Role Assigned' },
  { value: 'ROLE_REVOKED',        label: 'Role Revoked' },
  { value: 'PERMISSION_CHANGED',  label: 'Permission Changed' },
  { value: 'IP_BLOCKED',          label: 'IP Blocked' },
  { value: 'IMPERSONATION_START', label: 'Impersonation Started' },
  { value: 'IMPERSONATION_END',   label: 'Impersonation Ended' },
  { value: 'SESSION_TERMINATED',  label: 'Session Terminated' },
  { value: 'SSO_LOGIN',           label: 'SSO Login' },
  { value: 'API_KEY_CREATED',     label: 'API Key Created' },
  { value: 'API_KEY_REVOKED',     label: 'API Key Revoked' },
];

const SECURITY_EVENT_INTENT = {
  LOGIN_SUCCESS:       'success',
  LOGIN_FAILED:        'danger',
  LOGOUT:              'neutral',
  MFA_VERIFIED:        'success',
  MFA_FAILED:          'danger',
  PASSWORD_CHANGED:    'warning',
  ROLE_ASSIGNED:       'warning',
  ROLE_REVOKED:        'danger',
  PERMISSION_CHANGED:  'warning',
  IP_BLOCKED:          'danger',
  IMPERSONATION_START: 'amber',
  IMPERSONATION_END:   'neutral',
  SESSION_TERMINATED:  'warning',
  SSO_LOGIN:           'success',
  API_KEY_CREATED:     'success',
  API_KEY_REVOKED:     'danger',
};

function eventIntent(event) {
  return SECURITY_EVENT_INTENT[event] ?? 'neutral';
}

export default function SecurityAudit({ logs, filters, eventTypes }) {
  const toast     = useToast();
  const canExport = useHRMAC('platform-security.staff-sessions.view');

  const [form,   setForm]   = useState(filters ?? {});
  const [detail, setDetail] = useState(null);

  const eventOptions = eventTypes && eventTypes.length > 0
    ? [
        { value: '', label: 'All Security Events' },
        ...eventTypes.map(e => ({ value: e, label: e.replace(/_/g, ' ') })),
      ]
    : EVENT_OPTIONS;

  function applyFilters() {
    router.get(
      route('platform.admin.security.audit'),
      form,
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function resetFilters() {
    setForm({});
    router.get(
      route('platform.admin.security.audit'),
      {},
      { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] }
    );
  }

  function exportCsv() {
    const params = new URLSearchParams(
      Object.entries(form).filter(([, v]) => v)
    ).toString();
    window.location.href = route('platform.admin.security.audit.export') + (params ? '?' + params : '');
  }

  const columns = [
    {
      key: 'created_at',
      label: 'Timestamp',
      render: (row) => <Mono>{row.created_at}</Mono>,
    },
    {
      key: 'event',
      label: 'Event',
      render: (row) => (
        <Badge intent={eventIntent(row.event_type ?? row.event)}>
          {(row.event_type ?? row.event)?.replace(/_/g, ' ')}
        </Badge>
      ),
    },
    {
      key: 'actor',
      label: 'Actor',
      render: (row) => (
        <Text>
          {row.actor_name
            ? `${row.actor_name} #${row.actor_id}`
            : row.actor_id
              ? `#${row.actor_id}`
              : '—'}
        </Text>
      ),
    },
    {
      key: 'subject',
      label: 'Subject',
      render: (row) => (
        <Text tone="secondary">
          {row.subject_type
            ? `${row.subject_type} #${row.subject_id ?? '—'}`
            : '—'}
        </Text>
      ),
    },
    {
      key: 'actor_ip',
      label: 'IP',
      render: (row) => <Mono>{row.actor_ip ?? '—'}</Mono>,
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => <Text tone="secondary">{row.description ?? '—'}</Text>,
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
      title="Security Audit Log"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Platform Security', href: route('platform.admin.security.dashboard') },
        { label: 'Audit Log' },
      ]}
      description="Authentication events, permission changes, IP blocks, and impersonation activity."
      actions={
        canExport && (
          <Button intent="soft" onClick={exportCsv}>
            Export CSV
          </Button>
        )
      }
    >
      <VStack gap={4}>
        {/* Filters */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Filters</Eyebrow>
              <HStack gap={3} wrap>
                <Select
                  value={form.event ?? ''}
                  onChange={e => setForm({ ...form, event: e.target.value })}
                  options={eventOptions}
                />
                <Input
                  placeholder="Actor ID or name"
                  value={form.actor ?? ''}
                  onChange={e => setForm({ ...form, actor: e.target.value })}
                />
                <Input
                  placeholder="IP Address"
                  value={form.ip ?? ''}
                  onChange={e => setForm({ ...form, ip: e.target.value })}
                />
                <Input
                  type="date"
                  value={form.from ?? ''}
                  onChange={e => setForm({ ...form, from: e.target.value })}
                />
                <Input
                  type="date"
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
          empty="No security audit entries found."
        />

        {(logs?.last_page ?? 1) > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.security.audit'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['logs'] }
              )
            }
          />
        )}
      </VStack>

      {/* Detail Modal */}
      <Modal
        open={!!detail}
        onClose={() => setDetail(null)}
        title={`Security Event #${detail?.id}`}
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
                  {(detail.event_type ?? detail.event)?.replace(/_/g, ' ')}
                </Badge>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">Timestamp</Text>
                <Mono>{detail.created_at}</Mono>
              </VStack>
            </HStack>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">Actor</Text>
                <Text>
                  {detail.actor_name
                    ? `${detail.actor_name} (#${detail.actor_id})`
                    : detail.actor_id ?? '—'}
                </Text>
              </VStack>
              <VStack gap={0}>
                <Text tone="secondary">IP Address</Text>
                <Mono>{detail.actor_ip ?? '—'}</Mono>
              </VStack>
            </HStack>
            {detail.subject_type && (
              <VStack gap={0}>
                <Text tone="secondary">Subject</Text>
                <Text>{detail.subject_type} #{detail.subject_id ?? '—'}</Text>
              </VStack>
            )}
            <VStack gap={0}>
              <Text tone="secondary">Description</Text>
              <Text>{detail.description ?? '—'}</Text>
            </VStack>
            {detail.actor_user_agent && (
              <VStack gap={0}>
                <Text tone="secondary">User Agent</Text>
                <Text tone="secondary">{detail.actor_user_agent}</Text>
              </VStack>
            )}
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

SecurityAudit.layout = page => <App title="Security Audit Log">{page}</App>;
