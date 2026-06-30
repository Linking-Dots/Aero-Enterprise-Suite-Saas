import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Input,
  Select,
  Text,
  Mono,
  Stat,
  Tabs,
  EmptyState,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EVENT_INTENT = {
  login: 'success', logout: 'neutral', login_failed: 'danger',
  password_changed: 'warning', created: 'success', updated: 'neutral',
  deleted: 'danger', exported: 'warning', impersonated: 'amber',
  login_success: 'success', mfa_failed: 'danger', mfa_success: 'success',
  suspicious: 'danger', permission_denied: 'warning', token_revoked: 'warning',
};

const EVENT_TYPE_OPTIONS = [
  { value: '', label: 'All Event Types' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
  { value: 'login_failed', label: 'Login Failed' },
  { value: 'password_changed', label: 'Password Changed' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'exported', label: 'Exported' },
  { value: 'impersonated', label: 'Impersonated' },
];

const SECURITY_EVENT_OPTIONS = [
  { value: '', label: 'All Security Events' },
  { value: 'login_failed', label: 'Login Failed' },
  { value: 'login_success', label: 'Login Success' },
  { value: 'logout', label: 'Logout' },
  { value: 'password_changed', label: 'Password Changed' },
  { value: 'mfa_failed', label: 'MFA Failed' },
  { value: 'mfa_success', label: 'MFA Success' },
  { value: 'suspicious', label: 'Suspicious Activity' },
  { value: 'impersonated', label: 'Impersonation' },
  { value: 'permission_denied', label: 'Permission Denied' },
  { value: 'token_revoked', label: 'Token Revoked' },
];

const truncate = (s, n) => (s ? (s.length > n ? s.slice(0, n) + '…' : s) : '—');

export default function AuditLogsIndex({ stats, tab: initialTab, logs, filters }) {
  const toast = useToast();

  const canViewSecurity = useHRMAC('core.audit_logs.security_logs.view');
  const canViewQueue    = useHRMAC('core.audit_logs.queue_monitor.view');
  const canExportLogs   = useHRMAC('core.audit_logs.activity_logs.export');
  // Keep this hook unconditional (Rules of Hooks); combine with the view gate below.
  const canExportSecPerm = useHRMAC('core.audit_logs.security_logs.export');
  const canExportSec    = canViewSecurity && canExportSecPerm;
  const canRetry        = useHRMAC('core.audit_logs.queue_monitor.retry');
  const canFlush        = useHRMAC('core.audit_logs.queue_monitor.flush');

  const [tab, setTab]         = useState(initialTab || 'business');
  const [search, setSearch]   = useState(filters?.search ?? '');
  const [eventType, setEvent] = useState(filters?.event_type ?? '');
  const [dateFrom, setFrom]   = useState(filters?.date_from ?? '');
  const [dateTo, setTo]       = useState(filters?.date_to ?? '');

  const [tableLoading, setTableLoading] = useState(false);
  const [retrying, setRetrying] = useState(null);
  const [flushing, setFlushing] = useState(false);

  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const isQueue = tab === 'queues';
  const isSecurity = tab === 'security';

  const reload = (next = {}) => {
    const params = { tab, ...next };
    if (!isQueue) {
      if (search)    params.search     = search;
      if (eventType) params.event_type = eventType;
      if (dateFrom)  params.date_from  = dateFrom;
      if (dateTo)    params.date_to    = dateTo;
    }
    router.get(route('core.audit-logs.index'), params, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const switchTab = next => {
    setTab(next);
    router.get(route('core.audit-logs.index'), { tab: next }, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const applyFilters = () => reload({ page: 1 });
  const resetFilters = () => {
    setSearch(''); setEvent(''); setFrom(''); setTo('');
    router.get(route('core.audit-logs.index'), { tab }, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const exportLogs = () => {
    const params = {};
    if (eventType) params.event_type = eventType;
    if (dateFrom)  params.date_from  = dateFrom;
    if (dateTo)    params.date_to    = dateTo;
    window.open(route('core.audit-logs.export', params), '_blank');
  };

  const retryJob = id => {
    setRetrying(id);
    router.post(route('core.audit-logs.queues.retry', id), {}, {
      preserveState: true, preserveScroll: true,
      onSuccess: () => toast.success('Job queued for retry.'),
      onError:   () => toast.error('Failed to retry job.'),
      onFinish:  () => setRetrying(null),
    });
  };

  const flushAll = () => {
    if (!confirm('Permanently delete all failed jobs? This cannot be undone.')) return;
    setFlushing(true);
    router.post(route('core.audit-logs.queues.flush'), {}, {
      preserveState: true, preserveScroll: true,
      onSuccess: () => toast.success('All failed jobs flushed.'),
      onError:   () => toast.error('Failed to flush jobs.'),
      onFinish:  () => setFlushing(false),
    });
  };

  const logColumns = [
    { key: 'actor_name', label: 'Actor', width: '18%',
      render: row => <Text size="sm">{row.actor_name || row.actor_email || 'System'}</Text> },
    { key: 'event_type', label: 'Event', width: '15%',
      render: row => <Badge intent={EVENT_INTENT[row.event_type] ?? 'neutral'}>{row.event_type || '—'}</Badge> },
    { key: 'action', label: 'Action', width: '19%',
      render: row => <Text size="sm">{row.action || '—'}</Text> },
    { key: 'subject_label', label: 'Subject', width: '18%',
      render: row => <Text size="sm" tone="secondary">{truncate(row.subject_label, 40)}</Text> },
    { key: 'actor_ip', label: 'IP', width: '14%',
      render: row => <Mono size="sm">{row.actor_ip || '—'}</Mono> },
    { key: 'created_at', label: 'Time', width: '16%',
      render: row => <Mono size="sm">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</Mono> },
  ];

  const queueColumns = [
    { key: 'id', label: 'ID', width: '8%', render: row => <Mono size="sm">{row.id}</Mono> },
    { key: 'queue', label: 'Queue', width: '13%', render: row => <Badge intent="neutral">{row.queue || 'default'}</Badge> },
    { key: 'payload', label: 'Job', width: '24%',
      render: row => {
        const raw = typeof row.payload === 'string' ? row.payload : JSON.stringify(row.payload ?? {});
        return <Mono size="sm">{truncate(raw, 80)}</Mono>;
      } },
    { key: 'exception', label: 'Exception', width: '27%',
      render: row => <Text size="sm" tone="secondary">{truncate(row.exception, 100)}</Text> },
    { key: 'failed_at', label: 'Failed At', width: '14%',
      render: row => <Mono size="sm">{row.failed_at ? new Date(row.failed_at).toLocaleString() : '—'}</Mono> },
    canRetry && { key: 'actions', label: '', width: '12%', align: 'right',
      render: row => (
        <Button intent="soft" size="sm" type="button" loading={retrying === row.id}
          leftIcon="arrowPath" onClick={() => retryJob(row.id)}>Retry</Button>
      ) },
  ].filter(Boolean);

  const tabs = [
    { value: 'business', label: 'Activity' },
    { value: 'model',    label: 'Model changes' },
    { value: 'access',   label: 'Access' },
    canViewSecurity && { value: 'security', label: 'Security' },
    canViewQueue    && { value: 'queues',   label: 'Queue' },
  ].filter(Boolean);

  const rows = logs?.data ?? [];
  const hasFilter = !!(search || eventType || dateFrom || dateTo);
  const showExport = (tab === 'business' && canExportLogs) || (isSecurity && canExportSec);

  return (
    <IndexPageLayout
      title="Audit & Activity Logs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Audit Logs' },
      ]}
      description="System activity, model changes, sensitive-data access, security events, and the job queue."
      tabs={<Tabs value={tab} tabs={tabs} onChange={switchTab} />}
      actions={
        <HStack gap={2}>
          {showExport && (
            <Button intent="ghost" type="button" leftIcon="download" onClick={exportLogs}>Export</Button>
          )}
          {isQueue && canFlush && (
            <Button intent="danger" type="button" leftIcon="trash" loading={flushing} onClick={flushAll}>
              Flush all
            </Button>
          )}
        </HStack>
      }
      kpis={[
        <Stat key="today" title="Events today"     value={stats?.business_events_today ?? 0} icon="clock" />,
        <Stat key="total" title="Total events"     value={stats?.business_events_total ?? 0} icon="document" />,
        <Stat key="model" title="Model changes"    value={stats?.model_changes_today ?? 0} icon="pencil" />,
        <Stat key="acc"   title="Sensitive access" value={stats?.sensitive_accesses_today ?? 0} icon="user" iconTone="amber" />,
      ]}
      filters={
        isQueue ? null : (
          <HStack gap={3} align="end" wrap>
            <Input placeholder="Search actor, subject…" value={search} leftIcon="search"
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()} />
            <Select value={eventType} onChange={e => setEvent(e.target.value)}
              options={isSecurity ? SECURITY_EVENT_OPTIONS : EVENT_TYPE_OPTIONS} />
            <Input type="date" value={dateFrom} onChange={e => setFrom(e.target.value)} />
            <Input type="date" value={dateTo}   onChange={e => setTo(e.target.value)} />
            <Button intent="primary" type="button" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost"   type="button" onClick={resetFilters}>Reset</Button>
          </HStack>
        )
      }
      table={
        !tableLoading && rows.length === 0 ? (
          isQueue ? (
            <EmptyState icon="check" title="Queue is clean" description="No failed jobs to show." />
          ) : hasFilter ? (
            <EmptyState icon="filter" title="No matching events"
              description="Try adjusting your search, event type, or date range."
              action={<Button intent="ghost" type="button" onClick={resetFilters}>Reset filters</Button>} />
          ) : (
            <EmptyState icon="document" title="No events yet"
              description="Activity will appear here as users interact with the system." />
          )
        ) : (
          <DataTable columns={isQueue ? queueColumns : logColumns} rows={rows} loading={tableLoading} />
        )
      }
      pagination={
        logs?.last_page > 1 && (
          <Pagination page={logs.current_page} total={logs.last_page}
            onChange={page => reload({ page })} />
        )
      }
    />
  );
}

AuditLogsIndex.layout = page => <App title="Audit & Activity Logs">{page}</App>;
