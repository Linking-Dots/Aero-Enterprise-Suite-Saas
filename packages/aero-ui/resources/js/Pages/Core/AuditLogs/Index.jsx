import { useState } from 'react';
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
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EVENT_TYPE_OPTIONS = [
  { value: '',                  label: 'All Event Types' },
  { value: 'login',             label: 'Login' },
  { value: 'logout',            label: 'Logout' },
  { value: 'login_failed',      label: 'Login Failed' },
  { value: 'password_changed',  label: 'Password Changed' },
  { value: 'created',           label: 'Created' },
  { value: 'updated',           label: 'Updated' },
  { value: 'deleted',           label: 'Deleted' },
  { value: 'exported',          label: 'Exported' },
  { value: 'impersonated',      label: 'Impersonated' },
];

const EVENT_INTENT = {
  login:            'success',
  logout:           'neutral',
  login_failed:     'danger',
  password_changed: 'warning',
  created:          'success',
  updated:          'neutral',
  deleted:          'danger',
  exported:         'warning',
  impersonated:     'amber',
};

export default function AuditLogsIndex({ logs, filters }) {
  const [search,     setSearch]     = useState(filters?.search     ?? '');
  const [eventType,  setEventType]  = useState(filters?.event_type ?? '');
  const [dateFrom,   setDateFrom]   = useState(filters?.from       ?? '');
  const [dateTo,     setDateTo]     = useState(filters?.to         ?? '');

  const applyFilters = (page = 1) => {
    const params = { page };
    if (search)    params.search     = search;
    if (eventType) params.event_type = eventType;
    if (dateFrom)  params.from       = dateFrom;
    if (dateTo)    params.to         = dateTo;

    router.get(route('core.audit-logs.activity'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['logs', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch('');
    setEventType('');
    setDateFrom('');
    setDateTo('');
    router.get(route('core.audit-logs.activity'), {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['logs', 'filters'],
    });
  };

  const columns = [
    {
      key: 'actor_name',
      label: 'Actor',
      width: '18%',
      render: row => (
        <Text size="sm">{row.actor_name || row.actor_email || 'System'}</Text>
      ),
    },
    {
      key: 'event_type',
      label: 'Event',
      width: '14%',
      render: row => (
        <Badge intent={EVENT_INTENT[row.event_type] ?? 'neutral'}>
          {row.event_type || '—'}
        </Badge>
      ),
    },
    {
      key: 'action',
      label: 'Action',
      width: '20%',
      render: row => <Text size="sm">{row.action || '—'}</Text>,
    },
    {
      key: 'subject_label',
      label: 'Subject',
      width: '18%',
      render: row => (
        <Text size="sm" tone="secondary">
          {row.subject_label ? row.subject_label.slice(0, 40) + (row.subject_label.length > 40 ? '…' : '') : '—'}
        </Text>
      ),
    },
    {
      key: 'actor_ip',
      label: 'IP',
      width: '14%',
      render: row => <Mono size="sm">{row.actor_ip || '—'}</Mono>,
    },
    {
      key: 'created_at',
      label: 'Time',
      width: '16%',
      render: row => (
        <Mono size="sm">
          {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
        </Mono>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Activity Logs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Audit Logs', href: route('core.audit-logs.activity') },
        { label: 'Activity' },
      ]}
      description="Full record of all actor activity across the platform."
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search actor name or email…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters(1)}
            leftIcon="magnifyingGlass"
          />
          <Select
            value={eventType}
            onChange={e => setEventType(e.target.value)}
            options={EVENT_TYPE_OPTIONS}
          />
          <Input
            type="date"
            value={dateFrom}
            onChange={e => setDateFrom(e.target.value)}
            placeholder="From"
          />
          <Input
            type="date"
            value={dateTo}
            onChange={e => setDateTo(e.target.value)}
            placeholder="To"
          />
          <Button intent="primary" onClick={() => applyFilters(1)}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={logs?.data ?? []}
          empty="No activity logs found. Try adjusting your filters."
        />
      }
      pagination={
        logs?.next_page_url || logs?.prev_page_url ? (
          <HStack gap={2}>
            <Button
              intent="ghost"
              size="sm"
              disabled={!logs?.prev_page_url}
              onClick={() => router.get(logs.prev_page_url, {}, { preserveState: true })}
              leftIcon="chevronLeft"
            >
              Previous
            </Button>
            <Text size="sm" tone="secondary">
              {logs?.from ?? 0}–{logs?.to ?? 0} of {logs?.total ?? 0}
            </Text>
            <Button
              intent="ghost"
              size="sm"
              disabled={!logs?.next_page_url}
              onClick={() => router.get(logs.next_page_url, {}, { preserveState: true })}
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

AuditLogsIndex.layout = page => (
  <App title="Activity Logs">{page}</App>
);
