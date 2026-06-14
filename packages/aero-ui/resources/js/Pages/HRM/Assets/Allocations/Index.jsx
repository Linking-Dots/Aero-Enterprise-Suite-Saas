import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, HStack,
  Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',         label: 'All' },
  { value: 'active',   label: 'Active' },
  { value: 'returned', label: 'Returned' },
];

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function AllocationsIndex({ allocations, filters }) {
  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.assets.allocations.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = allocations.last_page    ?? 1;
  const currentPage = allocations.current_page ?? 1;

  const columns = [
    {
      key: 'asset_tag', label: 'Asset Tag',
      render: row => <Text>{row.asset?.tag ?? '—'}</Text>,
    },
    {
      key: 'asset_name', label: 'Asset Name',
      render: row => <Text>{row.asset?.name ?? '—'}</Text>,
    },
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'allocated_at', label: 'Allocated At',
      render: row => <Text>{fmtDate(row.allocated_at)}</Text>,
    },
    {
      key: 'returned_at', label: 'Returned At',
      render: row => row.returned_at
        ? <Text>{fmtDate(row.returned_at)}</Text>
        : <Badge intent="info">Active</Badge>,
    },
  ];

  return (
    <IndexPageLayout
      title="Asset Allocations"
      breadcrumb={[{ label: 'HRM' }, { label: 'Assets' }, { label: 'Allocations' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={allocations.data ?? []}
          empty="No allocations found."
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

AllocationsIndex.layout = page => <App title="Asset Allocations">{page}</App>;
