import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Tabs,
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
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PII_FIELDS = [
  'account_number', 'routing_number', 'tax_id', 'national_id',
  'medical_notes', 'byoc_db_host', 'byoc_db_user', 'byoc_db_password',
];

const SUBJECT_TYPE_OPTIONS = [
  { value: '', label: 'All Subject Types' },
  { value: 'Employee', label: 'Employee' },
  { value: 'Tenant', label: 'Tenant' },
  { value: 'User', label: 'User' },
];

export default function AccessLogsIndex({ logs, filters, pii }) {
  const toast       = useToast();
  const canExport   = useHRMAC(pii ? 'access-logs.pii-access.export' : 'access-logs.access-log-list.export');
  const [form, setForm] = useState(filters ?? {});

  const TAB_OPTIONS = [
    { value: 'all', label: 'All Access' },
    { value: 'pii', label: 'PII Only' },
  ];

  function switchTab(key) {
    if (key === 'pii') {
      router.get(route('platform.admin.access-logs.pii'), {}, { preserveState: false });
    } else {
      router.get(route('platform.admin.access-logs.index'), {}, { preserveState: false });
    }
  }

  function applyFilters() {
    const target = pii
      ? 'platform.admin.access-logs.pii'
      : 'platform.admin.access-logs.index';
    router.get(route(target), form, { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] });
  }

  function resetFilters() {
    setForm({});
    const target = pii
      ? 'platform.admin.access-logs.pii'
      : 'platform.admin.access-logs.index';
    router.get(route(target), {}, { preserveState: true, preserveScroll: true, only: ['logs', 'filters'] });
  }

  function exportCsv() {
    const params = new URLSearchParams(
      Object.entries({ ...form, pii: pii ? 1 : 0 }).filter(([, v]) => v != null && v !== '')
    ).toString();
    window.location.href = route('platform.admin.access-logs.export') + (params ? '?' + params : '');
  }

  const columns = [
    {
      key: 'created_at',
      label: 'Timestamp',
      render: (row) => <Mono>{row.created_at}</Mono>,
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
        <Text>{row.subject_type ? `${row.subject_type} #${row.subject_id ?? '—'}` : '—'}</Text>
      ),
    },
    {
      key: 'field_accessed',
      label: 'Field Accessed',
      render: (row) => (
        <Badge intent={PII_FIELDS.includes(row.field_accessed) ? 'warning' : 'neutral'}>
          {row.field_accessed ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'actor_ip',
      label: 'IP Address',
      render: (row) => <Mono>{row.actor_ip ?? '—'}</Mono>,
    },
    {
      key: 'actor_user_agent',
      label: 'User Agent',
      render: (row) => (
        <Text tone="secondary">{row.actor_user_agent ?? '—'}</Text>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title={pii ? 'PII Access Logs' : 'Access Logs'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Access Logs' },
      ]}
      description={pii
        ? 'Audit trail of PII field accesses across the platform.'
        : 'Audit trail of all resource accesses across the platform.'}
      actions={
        canExport && (
          <Button intent="soft" onClick={exportCsv}>
            Export CSV
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <Tabs
          tabs={TAB_OPTIONS}
          value={pii ? 'pii' : 'all'}
          onChange={switchTab}
        />

        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3} wrap>
                <Select
                  value={form.subject_type ?? ''}
                  onChange={e => setForm({ ...form, subject_type: e.target.value })}
                  options={SUBJECT_TYPE_OPTIONS}
                />
                <Input
                  placeholder="Field accessed"
                  value={form.field ?? ''}
                  onChange={e => setForm({ ...form, field: e.target.value })}
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
          empty="No access log entries."
        />

        {(logs?.last_page ?? 1) > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page =>
              router.get(
                pii
                  ? route('platform.admin.access-logs.pii')
                  : route('platform.admin.access-logs.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['logs'] }
              )
            }
          />
        )}
      </VStack>
    </IndexPageLayout>
  );
}

AccessLogsIndex.layout = page => <App title="Access Logs">{page}</App>;
