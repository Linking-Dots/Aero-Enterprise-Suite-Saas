import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  HStack, VStack,
  Text, Mono,
  Input,
  Select,
  Button,
  Badge,
  Pagination,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',        label: 'All Statuses' },
  { value: 'success', label: 'Success' },
  { value: 'failed',  label: 'Failed' },
  { value: 'blocked', label: 'Blocked' },
];

const RISK_OPTIONS = [
  { value: '',        label: 'All Risk Levels' },
  { value: 'low',     label: 'Low' },
  { value: 'medium',  label: 'Medium' },
  { value: 'high',    label: 'High' },
  { value: 'critical',label: 'Critical' },
];

function statusIntent(status) {
  switch (status) {
    case 'success': return 'success';
    case 'failed':  return 'danger';
    case 'blocked': return 'warning';
    default:        return 'neutral';
  }
}

function riskIntent(level) {
  switch (level) {
    case 'critical': return 'danger';
    case 'high':     return 'warning';
    case 'medium':   return 'amber';
    case 'low':      return 'success';
    default:         return 'neutral';
  }
}

function eventTypeIntent(type) {
  if (!type) return 'neutral';
  if (type.includes('failed') || type.includes('blocked')) return 'danger';
  if (type.includes('success') || type.includes('login')) return 'success';
  return 'neutral';
}

export default function LoginActivityPage({ events, filters }) {
  const canView = useHRMAC('auth.sso_identity.login_activity.view');

  const [search,    setSearch]    = useState(filters?.search     ?? '');
  const [status,    setStatus]    = useState(filters?.status     ?? '');
  const [riskLevel, setRiskLevel] = useState(filters?.risk_level ?? '');

  function applyFilters(page = 1) {
    const params = { page };
    if (search)    params.search     = search;
    if (status)    params.status     = status;
    if (riskLevel) params.risk_level = riskLevel;

    router.get(route('core.identity.login-activity.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['events', 'filters'],
    });
  }

  function resetFilters() {
    setSearch(''); setStatus(''); setRiskLevel('');
    router.get(route('core.identity.login-activity.index'), {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['events', 'filters'],
    });
  }

  const rows = events?.data ?? [];

  const columns = [
    {
      key: 'event_type', label: 'Event', width: '16%',
      render: row => (
        <Badge intent={eventTypeIntent(row.event_type)}>
          {row.event_type ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'user', label: 'User', width: '20%',
      render: row => {
        const name  = row.metadata?.user_name  ?? row.metadata?.name  ?? null;
        const email = row.metadata?.user_email ?? row.metadata?.email ?? null;
        return (
          <VStack gap={0}>
            {name  && <Text size="sm">{name}</Text>}
            {email && <Text tone="secondary" size="xs">{email}</Text>}
            {!name && !email && <Text tone="secondary" size="sm">—</Text>}
          </VStack>
        );
      },
    },
    {
      key: 'status', label: 'Status', width: '11%',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {row.status ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'risk_level', label: 'Risk', width: '10%',
      render: row => row.risk_level
        ? <Badge intent={riskIntent(row.risk_level)}>{row.risk_level}</Badge>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'ip_address', label: 'IP Address', width: '14%',
      render: row => row.ip_address
        ? <Mono size="sm">{row.ip_address}</Mono>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'occurred_at', label: 'Time', width: '16%',
      render: row => row.occurred_at
        ? <Text size="sm">{new Date(row.occurred_at).toLocaleString()}</Text>
        : <Text tone="secondary" size="sm">—</Text>,
    },
  ];

  if (!canView) {
    return (
      <IndexPageLayout
        title="Login Activity"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'SSO & Identity', href: route('core.identity.index') },
          { label: 'Login Activity' },
        ]}
        description="Monitor authentication events across your organisation."
      >
        <Text tone="secondary">You do not have permission to view login activity.</Text>
      </IndexPageLayout>
    );
  }

  return (
    <IndexPageLayout
      title="Login Activity"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Login Activity' },
      ]}
      description="Monitor authentication events across your organisation."
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search user, email, IP…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
            leftIcon="search"
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={STATUS_OPTIONS}
          />
          <Select
            value={riskLevel}
            onChange={e => setRiskLevel(e.target.value)}
            options={RISK_OPTIONS}
          />
          <Button intent="primary" onClick={() => applyFilters()}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={rows}
          empty="No login events found."
        />
      }
      pagination={
        events?.last_page > 1 && (
          <Pagination
            page={events.current_page}
            total={events.last_page}
            onChange={page => applyFilters(page)}
          />
        )
      }
    />
  );
}

LoginActivityPage.layout = page => (
  <App title="Login Activity">{page}</App>
);
