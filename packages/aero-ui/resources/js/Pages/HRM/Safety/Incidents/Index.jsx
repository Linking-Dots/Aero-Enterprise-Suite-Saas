import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Pagination, Badge, Text,
} from '@aero/ui';

const TYPE_OPTIONS = [
  { value: '',                label: 'All Types' },
  { value: 'injury',          label: 'Injury' },
  { value: 'near_miss',       label: 'Near Miss' },
  { value: 'property_damage', label: 'Property Damage' },
];

const SEVERITY_OPTIONS = [
  { value: '',         label: 'All Severities' },
  { value: 'low',      label: 'Low' },
  { value: 'medium',   label: 'Medium' },
  { value: 'high',     label: 'High' },
  { value: 'critical', label: 'Critical' },
];

const STATUS_OPTIONS = [
  { value: '',           label: 'All Statuses' },
  { value: 'open',       label: 'Open' },
  { value: 'under_investigation', label: 'Under Investigation' },
  { value: 'closed',     label: 'Closed' },
];

function severityIntent(severity) {
  switch (severity) {
    case 'low':      return 'info';
    case 'medium':   return 'warning';
    case 'high':     return 'amber';
    case 'critical': return 'danger';
    default:         return 'neutral';
  }
}

function statusIntent(status) {
  switch (status) {
    case 'open':                  return 'danger';
    case 'under_investigation':   return 'warning';
    case 'closed':                return 'success';
    default:                      return 'neutral';
  }
}

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

function typeLabel(type) {
  switch (type) {
    case 'injury':           return 'Injury';
    case 'near_miss':        return 'Near Miss';
    case 'property_damage':  return 'Property Damage';
    default:                 return type ?? '—';
  }
}

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

export default function IncidentsIndex({ incidents, filters }) {
  const canCreate = useHRMAC('hrm.safety.safety-incidents.create');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.safety.incidents.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = incidents.last_page    ?? 1;
  const currentPage = incidents.current_page ?? 1;

  const columns = [
    {
      key: 'reference', label: 'Reference',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.safety.incidents.show', row.id))}
        >
          {row.reference ?? `#${row.id}`}
        </Button>
      ),
    },
    {
      key: 'occurred_at', label: 'Occurred At',
      render: row => <Text>{fmtDatetime(row.occurred_at)}</Text>,
    },
    {
      key: 'type', label: 'Type',
      render: row => <Text>{typeLabel(row.type)}</Text>,
    },
    {
      key: 'severity', label: 'Severity',
      render: row => (
        <Badge intent={severityIntent(row.severity)}>
          {row.severity ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Safety Incidents"
      breadcrumb={[{ label: 'HRM' }, { label: 'Safety' }, { label: 'Incidents' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            placeholder="Search incidents..."
            defaultValue={filters?.search ?? ''}
            onBlur={e => applyFilters({ search: e.target.value, page: 1 })}
          />
          <Select
            options={TYPE_OPTIONS}
            value={filters?.type ?? ''}
            onChange={e => applyFilters({ type: e.target.value, page: 1 })}
          />
          <Select
            options={SEVERITY_OPTIONS}
            value={filters?.severity ?? ''}
            onChange={e => applyFilters({ severity: e.target.value, page: 1 })}
          />
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.safety.incidents.create'))}
            >
              Report Incident
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={incidents.data ?? []}
          empty="No incidents found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => applyFilters({ page })}
          />
        )
      }
    />
  );
}

IncidentsIndex.layout = page => <App title="Safety Incidents">{page}</App>;
