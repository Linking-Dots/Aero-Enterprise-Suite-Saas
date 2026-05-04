/**
 * Audit & Activity Logs — Inertia-standard list view.
 *
 * All data comes as Inertia props from AuditLogController::index().
 * Filtering, pagination and tab switching use router.get() to reload
 * page props while preserving client state.
 */
import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Tabs,
  DataTable,
  KPI,
  SearchInput, Select, DatePicker,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';

const TAB_ACTIVITY = 'activity';
const TAB_SECURITY = 'security';

export default function AuditLogsIndex({ stats, tab, logs, meta, filters: initialFilters }) {
  const toast = useToast();

  const [search, setSearch] = useState(initialFilters?.search ?? '');
  const [actionType, setActionType] = useState(initialFilters?.action ?? '');
  const [eventType, setEventType] = useState(initialFilters?.event_type ?? '');
  const [dateFrom, setDateFrom] = useState(initialFilters?.date_from ?? '');
  const [dateTo, setDateTo] = useState(initialFilters?.date_to ?? '');

  const applyFilters = (page = 1, newTab = tab) => {
    const params = { tab: newTab, per_page: 15, page };
    if (search) params.search = search;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;

    if (newTab === TAB_ACTIVITY && actionType) {
      params.action = actionType;
    }
    if (newTab === TAB_SECURITY && eventType) {
      params.event_type = eventType;
    }

    router.get(route('core.audit-logs.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['logs', 'meta', 'filters', 'tab'],
    });
  };

  const handleTabChange = (newTab) => {
    setSearch('');
    setActionType('');
    setEventType('');
    setDateFrom('');
    setDateTo('');
    applyFilters(1, newTab);
  };

  const handlePageChange = (page) => {
    applyFilters(page);
  };

  const handleExport = () => {
    toast.info('Export functionality coming soon.');
  };

  const handleReset = () => {
    setSearch('');
    setActionType('');
    setEventType('');
    setDateFrom('');
    setDateTo('');
    applyFilters(1);
  };

  const activityColumns = [
    { key: 'created_at', label: 'Time', width: '15%', render: (row) => row.created_at ? new Date(row.created_at).toLocaleString() : '—' },
    { key: 'causer_name', label: 'User', width: '15%', render: (row) => row.causer_name || row.causer_email || 'System' },
    { key: 'log_name', label: 'Action', width: '12%', render: (row) => <Badge>{row.log_name || '—'}</Badge> },
    { key: 'description', label: 'Description', width: '30%' },
    { key: 'subject_type', label: 'Subject', width: '15%', render: (row) => `${row.subject_type?.split('\\')?.pop() || ''} #${row.subject_id || ''}` },
    { key: 'properties', label: 'Details', width: '13%', render: (row) => row.properties ? JSON.stringify(row.properties).slice(0, 40) + (JSON.stringify(row.properties).length > 40 ? '…' : '') : '—' },
  ];

  const securityColumns = [
    { key: 'created_at', label: 'Time', width: '15%', render: (row) => row.created_at ? new Date(row.created_at).toLocaleString() : '—' },
    { key: 'user_name', label: 'User', width: '15%', render: (row) => row.user_name || row.user_email || '—' },
    { key: 'event_type', label: 'Event', width: '12%', render: (row) => <Badge intent="warning">{row.event_type || '—'}</Badge> },
    { key: 'description', label: 'Description', width: '30%' },
    { key: 'ip_address', label: 'IP Address', width: '15%', mono: true },
    { key: 'user_agent', label: 'User Agent', width: '13%', render: (row) => row.user_agent ? row.user_agent.slice(0, 30) + (row.user_agent.length > 30 ? '…' : '') : '—' },
  ];

  const kpis = [
    <KPI key="total" label="Total Activities" value={stats?.total_activities ?? 0} />,
    <KPI key="today" label="Today" value={stats?.today_activities ?? 0} />,
    <KPI key="security" label="Security Events" value={stats?.security_events ?? 0} />,
    <KPI key="active" label="Active Users" value={stats?.active_users_today ?? 0} />,
  ];

  const filterBar = (
    <HStack gap={3} align="end" wrap>
      <SearchInput
        value={search}
        onChange={e => setSearch(e.target.value)}
        placeholder="Search logs..."
        onKeyDown={e => e.key === 'Enter' && applyFilters(1)}
        style={{ minWidth: 200, flex: 1 }}
      />

      {tab === TAB_ACTIVITY && (
        <Select
          value={actionType}
          onChange={e => setActionType(e.target.value)}
          style={{ minWidth: 140 }}
          options={[
            { value: '', label: 'All actions' },
            { value: 'created', label: 'Created' },
            { value: 'updated', label: 'Updated' },
            { value: 'deleted', label: 'Deleted' },
            { value: 'login', label: 'Login' },
            { value: 'logout', label: 'Logout' },
          ]}
        />
      )}

      {tab === TAB_SECURITY && (
        <Select
          value={eventType}
          onChange={e => setEventType(e.target.value)}
          style={{ minWidth: 140 }}
          options={[
            { value: '', label: 'All events' },
            { value: 'login_failed', label: 'Login Failed' },
            { value: 'login_success', label: 'Login Success' },
            { value: 'password_changed', label: 'Password Changed' },
            { value: 'suspicious', label: 'Suspicious' },
          ]}
        />
      )}

      <DatePicker value={dateFrom} onChange={e => setDateFrom(e.target.value)} placeholder="From date" />
      <DatePicker value={dateTo} onChange={e => setDateTo(e.target.value)} placeholder="To date" />

      <Button variant="primary" onClick={() => applyFilters(1)}>
        Search
      </Button>
      <Button variant="secondary" onClick={handleReset}>
        Reset
      </Button>
    </HStack>
  );

  return (
    <IndexPageLayout
      title="Audit & Activity Logs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Audit & Activity Logs' },
      ]}
      description="Review system activity, user actions, and security events."
      actions={
        <Button variant="secondary" onClick={handleExport}>
          Export
        </Button>
      }
      kpis={kpis}
      filters={
        <VStack gap={4}>
          <Tabs
            tabs={[
              { value: TAB_ACTIVITY, label: 'Activity Logs', count: meta?.total ?? 0 },
              { value: TAB_SECURITY, label: 'Security Logs' },
            ]}
            value={tab ?? TAB_ACTIVITY}
            onChange={handleTabChange}
          />
          {filterBar}
        </VStack>
      }
      table={
        <DataTable
          columns={tab === TAB_SECURITY ? securityColumns : activityColumns}
          rows={logs ?? []}
          empty="No logs found. Try adjusting your filters."
        />
      }
      pagination={
        meta?.last_page > 1 && (
          <Pagination
            page={meta?.current_page ?? 1}
            total={meta?.last_page ?? 1}
            onChange={handlePageChange}
          />
        )
      }
    />
  );
}

AuditLogsIndex.layout = page => (
  <App title="Audit & Activity Logs">{page}</App>
);
