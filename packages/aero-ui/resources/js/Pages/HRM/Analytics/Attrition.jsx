import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Input, Select, Badge, Text, Pagination,
} from '@aero/ui';

function bandIntent(band) {
  switch (band) {
    case 'low':      return 'success';
    case 'medium':   return 'warning';
    case 'high':     return 'danger';
    case 'critical': return 'danger';
    default:         return 'neutral';
  }
}

export default function Attrition({ risks, filters, bands }) {
  const canView = useHRMAC('hrm.analytics.attrition.view');

  const bandOptions = [
    { value: '', label: 'All Bands' },
    ...(bands ?? []).map(b => ({ value: b, label: b.charAt(0).toUpperCase() + b.slice(1) })),
  ];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.analytics.attrition'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = risks.last_page    ?? 1;
  const currentPage = risks.current_page ?? 1;

  const columns = [
    {
      key: 'employee',
      label: 'Employee',
      render: row => (
        <Text>
          {row.employee
            ? `${row.employee.first_name} ${row.employee.last_name}`
            : '—'}
        </Text>
      ),
    },
    {
      key: 'score',
      label: 'Risk Score',
      render: row => <Text>{Math.round((row.score ?? 0) * 100)}%</Text>,
    },
    {
      key: 'band',
      label: 'Band',
      render: row => (
        <Badge intent={bandIntent(row.band)}>
          {row.band ? row.band.charAt(0).toUpperCase() + row.band.slice(1) : '—'}
        </Badge>
      ),
    },
    {
      key: 'factors',
      label: 'Top Factors',
      render: row => {
        const top = Array.isArray(row.factors) ? row.factors.slice(0, 2) : [];
        return (
          <VStack gap={1}>
            {top.length > 0
              ? top.map((f, i) => (
                  <Text key={i} tone="secondary">
                    {f.name} ({Math.round((f.weight ?? 0) * 100)}%)
                  </Text>
                ))
              : <Text tone="secondary">—</Text>}
          </VStack>
        );
      },
    },
  ];

  return (
    <IndexPageLayout
      title="Attrition Risk"
      breadcrumb={[{ label: 'HRM' }, { label: 'Analytics' }, { label: 'Attrition' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            placeholder="Search employees..."
            defaultValue={filters?.search ?? ''}
            onBlur={e => applyFilters({ search: e.target.value, page: 1 })}
          />
          <Select
            options={bandOptions}
            value={filters?.band ?? ''}
            onChange={e => applyFilters({ band: e.target.value, page: 1 })}
          />
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.analytics.dashboard'))}
          >
            Back to Dashboard
          </Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={risks.data ?? []}
          empty="No attrition risk data found."
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

Attrition.layout = page => <App title="Attrition Risk">{page}</App>;
