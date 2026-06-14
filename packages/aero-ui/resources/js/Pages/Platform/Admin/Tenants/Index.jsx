import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Text,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:      'success',
  trial:       'primary',
  pending:     'warning',
  suspended:   'danger',
  frozen:      'neutral',
  archived:    'neutral',
  provisioning:'amber',
};

export default function TenantsIndex({ tenants, filters, plans }) {
  const toast = useToast();
  const canBulk = useHRMAC('tenant-operations.bulk-actions.bulk-suspend');
  const canCreate = useHRMAC('platform.admin.tenants.create');

  const [search, setSearch] = useState(filters?.search ?? '');
  const [status, setStatus] = useState(filters?.status ?? '');
  const [planId, setPlanId] = useState(filters?.plan_id ?? '');
  const [selectedIds, setSelectedIds] = useState([]);
  const [bulkOpen, setBulkOpen] = useState(false);

  function applyFilters() {
    router.get(
      route('platform.admin.tenants.index'),
      { search, status, plan_id: planId },
      { preserveState: true, preserveScroll: true, only: ['tenants', 'filters'] }
    );
  }

  function resetFilters() {
    setSearch('');
    setStatus('');
    setPlanId('');
    router.get(route('platform.admin.tenants.index'), {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['tenants', 'filters'],
    });
  }

  function toggleSelect(id) {
    setSelectedIds(prev =>
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  }

  function toggleAll() {
    const allIds = tenants?.data?.map(t => t.id) ?? [];
    if (selectedIds.length === allIds.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(allIds);
    }
  }

  function executeBulkSuspend() {
    router.post(
      route('platform.admin.tenants.bulk.execute'),
      { operation: 'suspend', tenant_ids: selectedIds },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`${selectedIds.length} tenants suspended.`);
          setSelectedIds([]);
          setBulkOpen(false);
        },
        onError: () => toast.error('Bulk suspend failed.'),
      }
    );
  }

  const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'trial', label: 'Trial' },
    { value: 'pending', label: 'Pending' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'frozen', label: 'Frozen' },
    { value: 'archived', label: 'Archived' },
  ];

  const planOptions = [
    { value: '', label: 'All Plans' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const allIds = tenants?.data?.map(t => t.id) ?? [];
  const allSelected = allIds.length > 0 && selectedIds.length === allIds.length;

  const columns = [
    {
      key: 'select',
      label: (
        <input
          type="checkbox"
          checked={allSelected}
          onChange={toggleAll}
          aria-label="Select all"
        />
      ),
      width: '40px',
      render: (row) => (
        <input
          type="checkbox"
          checked={selectedIds.includes(row.id)}
          onChange={() => toggleSelect(row.id)}
          aria-label={`Select ${row.name}`}
        />
      ),
    },
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'domain',
      label: 'Primary Domain',
      render: (row) => {
        const primary = row.domains?.find(d => d.is_primary) ?? row.domains?.[0];
        return primary ? <Text tone="secondary">{primary.domain}</Text> : <Text tone="secondary">—</Text>;
      },
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'created_at',
      label: 'Created',
      render: (row) => (
        <Text tone="secondary">
          {new Date(row.created_at).toLocaleDateString()}
        </Text>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Tenants"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenants' },
      ]}
      description="Manage all tenant accounts and their lifecycle."
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('platform.admin.tenants.create'))}
          >
            New Tenant
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Input
            placeholder="Search tenants..."
            leftIcon="search"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={statusOptions}
          />
          <Select
            value={planId}
            onChange={e => setPlanId(e.target.value)}
            options={planOptions}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        {selectedIds.length > 0 && canBulk && (
          <HStack gap={2}>
            <Text tone="secondary">{selectedIds.length} selected</Text>
            <Button intent="danger" size="sm" onClick={() => setBulkOpen(true)}>
              Bulk Suspend
            </Button>
            <Button
              intent="ghost"
              size="sm"
              onClick={() => router.get(route('platform.admin.tenants.bulk.history'))}
            >
              Bulk History
            </Button>
          </HStack>
        )}

        <DataTable
          columns={columns}
          rows={tenants?.data ?? []}
          empty="No tenants found."
        />

        {tenants?.last_page > 1 && (
          <Pagination
            page={tenants.current_page}
            total={tenants.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.tenants.index'),
                { page, search, status, plan_id: planId },
                { preserveState: true, preserveScroll: true, only: ['tenants'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={bulkOpen}
        onClose={() => setBulkOpen(false)}
        title="Bulk Suspend Tenants"
        description={`You are about to suspend ${selectedIds.length} tenant(s). This action will prevent their users from logging in.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={executeBulkSuspend}>
              Confirm Suspend
            </Button>
            <Button intent="ghost" onClick={() => setBulkOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

TenantsIndex.layout = page => <App title="Tenants">{page}</App>;
