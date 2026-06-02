import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Input, Select, Pagination, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',            label: 'All Statuses' },
  { value: 'available',   label: 'Available' },
  { value: 'allocated',   label: 'Allocated' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'retired',     label: 'Retired' },
];

function statusIntent(status) {
  switch (status) {
    case 'available':   return 'success';
    case 'allocated':   return 'info';
    case 'maintenance': return 'warning';
    case 'retired':     return 'neutral';
    default:            return 'neutral';
  }
}

export default function AssetsIndex({ assets, filters, categories }) {
  const canCreate = useHRMAC('hrm.assets.asset-inventory.create');

  const categoryOptions = [
    { value: '', label: 'All Categories' },
    ...(categories ?? []).map(c => ({ value: String(c.id), label: c.name })),
  ];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.assets.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = assets.last_page    ?? 1;
  const currentPage = assets.current_page ?? 1;

  const columns = [
    {
      key: 'tag', label: 'Tag',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.assets.show', row.id))}
        >
          {row.tag}
        </Button>
      ),
    },
    {
      key: 'name', label: 'Name',
      render: row => <Text>{row.name}</Text>,
    },
    {
      key: 'category', label: 'Category',
      render: row => <Text>{row.category?.name ?? '—'}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {row.status ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'current_holder', label: 'Current Holder',
      render: row => (
        <Text>{row.current_allocation?.employee?.user?.name ?? '—'}</Text>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Asset Inventory"
      breadcrumb={[{ label: 'HRM' }, { label: 'Assets' }, { label: 'Inventory' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            placeholder="Search assets..."
            defaultValue={filters?.q ?? ''}
            onBlur={e => applyFilters({ q: e.target.value, page: 1 })}
          />
          <Select
            options={categoryOptions}
            value={filters?.category_id ? String(filters.category_id) : ''}
            onChange={e => applyFilters({ category_id: e.target.value, page: 1 })}
          />
          <Select
            options={STATUS_OPTIONS}
            value={filters?.status ?? ''}
            onChange={e => applyFilters({ status: e.target.value, page: 1 })}
          />
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.assets.create'))}
            >
              New Asset
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={assets.data ?? []}
          empty="No assets found."
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

AssetsIndex.layout = page => <App title="Asset Inventory">{page}</App>;
