import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack, Box,
  Text,
  Input,
  Select,
  Modal,
  Menu,
  Stat,
  Avatar,
  EmptyState,
  useToast,
  useHRMAC,
} from '@aero/ui';
import { EllipsisHorizontalIcon } from '@heroicons/react/24/outline';
import App from '@/Pages/App.jsx';
import TenantsRail from './TenantsRail.jsx';

/* Status → Badge intent + human label. Covers every lifecycle state the seeder
   and lifecycle service can produce. */
const STATUS = {
  active:       { intent: 'success', label: 'Active' },
  trial:        { intent: 'primary', label: 'Trial' },
  pending:      { intent: 'warning', label: 'Pending' },
  provisioning: { intent: 'indigo',  label: 'Provisioning' },
  suspended:    { intent: 'danger',  label: 'Suspended' },
  frozen:       { intent: 'neutral', label: 'Frozen' },
  failed:       { intent: 'danger',  label: 'Failed' },
  archived:     { intent: 'neutral', label: 'Archived' },
  cancelled:    { intent: 'neutral', label: 'Cancelled' },
};

const CURRENCY_SYMBOL = { USD: '$', GBP: '£', EUR: '€', BDT: '৳', AUD: 'A$', CAD: 'C$' };

/* Per-row MRR in the subscription's own currency; "—" when no live plan sub. */
function fmtRowMrr(mrr, currency) {
  if (mrr === null || mrr === undefined) return '—';
  const sym = CURRENCY_SYMBOL[currency] ?? '$';
  return `${sym}${Number(mrr).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

/* Headline MRR — compact USD-equivalent for the KPI strip. */
function fmtKpiMrr(v) {
  const n = Number(v ?? 0);
  if (n >= 1000) return `$${(n / 1000).toLocaleString(undefined, { maximumFractionDigits: 1 })}k`;
  return `$${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function TenantsIndex({ tenants, stats, filters, plans }) {
  const toast = useToast();
  const canCreate      = useHRMAC('tenants.tenant-list.create');
  const canView        = useHRMAC('tenants.tenant-list.view');
  const canSuspend     = useHRMAC('tenants.tenant-list.suspend');
  const canActivate    = useHRMAC('tenants.tenant-list.activate');
  const canDelete      = useHRMAC('tenants.tenant-list.delete');
  const canImpersonate = useHRMAC('tenants.tenant-list.impersonate');
  const canBulk        = useHRMAC('tenant-operations.bulk-actions.bulk-suspend');

  const [search, setSearch] = useState(filters?.search ?? '');
  const [status, setStatus] = useState(filters?.status ?? '');
  const [planId, setPlanId] = useState(filters?.plan_id ?? '');
  const [selectedIds, setSelectedIds] = useState([]);

  // Single-tenant suspend needs a reason (route validates it); bulk suspend does not.
  const [bulkOpen, setBulkOpen] = useState(false);
  const [suspendTarget, setSuspendTarget] = useState(null);
  const [suspendReason, setSuspendReason] = useState('');

  // Table skeleton while any partial reload (filter/page) is in flight.
  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const rows = tenants?.data ?? [];
  const hasActiveFilter = !!(search || status || planId);

  function applyFilters() {
    router.get(route('platform.admin.tenants.index'), { search, status, plan_id: planId }, {
      preserveState: true, preserveScroll: true, only: ['tenants', 'filters'],
    });
  }

  function resetFilters() {
    setSearch(''); setStatus(''); setPlanId('');
    router.get(route('platform.admin.tenants.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['tenants', 'filters'],
    });
  }

  function toggleSelect(id) {
    setSelectedIds(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);
  }
  function toggleAll() {
    const allIds = rows.map(t => t.id);
    setSelectedIds(selectedIds.length === allIds.length ? [] : allIds);
  }

  function executeBulkSuspend() {
    router.post(route('platform.admin.tenants.bulk.execute'),
      { operation: 'suspend', tenant_ids: selectedIds },
      {
        preserveState: true, preserveScroll: true,
        onSuccess: () => { toast.success(`${selectedIds.length} tenant(s) queued for suspension.`); setSelectedIds([]); setBulkOpen(false); },
        onError:   () => toast.error('Bulk suspend failed.'),
      });
  }

  function openSuspend(row) { setSuspendTarget(row); setSuspendReason(''); }
  function confirmSuspend() {
    if (!suspendTarget) return;
    router.post(route('platform.admin.tenants.suspend', suspendTarget.id), { reason: suspendReason }, {
      preserveScroll: true,
      onSuccess: () => { toast.success(`${suspendTarget.name} suspended.`); setSuspendTarget(null); },
      onError:   () => toast.error('Could not suspend tenant.'),
    });
  }

  function activateTenant(row) {
    router.post(route('platform.admin.tenants.activate', row.id), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success(`${row.name} activated.`),
      onError:   () => toast.error('Could not activate tenant.'),
    });
  }

  function impersonate(row) {
    router.post(route('platform.admin.tenants.impersonate', row.id), {}, {
      onError: () => toast.error('Could not impersonate tenant.'),
    });
  }

  function deleteTenant(row) {
    if (!confirm(`Purge ${row.name}? This removes the tenant and cannot be undone.`)) return;
    router.delete(route('platform.admin.tenants.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => toast.success(`${row.name} purged.`),
      onError:   () => toast.error('Could not purge tenant.'),
    });
  }

  const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'trial', label: 'Trial' },
    { value: 'pending', label: 'Pending' },
    { value: 'provisioning', label: 'Provisioning' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'failed', label: 'Failed' },
    { value: 'archived', label: 'Archived' },
  ];
  const planOptions = [
    { value: '', label: 'All plans' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const allSelected = rows.length > 0 && selectedIds.length === rows.length;

  const columns = [
    canBulk && {
      key: 'select', label: (
        <input type="checkbox" checked={allSelected} onChange={toggleAll} aria-label="Select all tenants" />
      ), width: '40px',
      render: row => (
        <input
          type="checkbox"
          checked={selectedIds.includes(row.id)}
          onChange={() => toggleSelect(row.id)}
          aria-label={`Select ${row.name}`}
        />
      ),
    },
    {
      key: 'name', label: 'Tenant', width: '26%',
      render: row => (
        <HStack gap={3} align="center">
          <Avatar name={row.name} size={28} />
          <VStack gap={0}>
            <Text size="sm" weight={600}>{row.name}</Text>
            {row.subdomain && <Text size="xs" tone="tertiary" mono>{row.subdomain}</Text>}
          </VStack>
        </HStack>
      ),
    },
    {
      key: 'domain', label: 'Domain', width: '20%',
      render: row => row.domain
        ? <Text size="sm" tone="secondary">{row.domain}</Text>
        : <Text size="sm" tone="tertiary">—</Text>,
    },
    {
      key: 'plan', label: 'Plan',
      render: row => row.plan
        ? <Text size="sm">{row.plan}</Text>
        : <Text size="sm" tone="tertiary">—</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => {
        const s = STATUS[row.status] ?? { intent: 'neutral', label: row.status };
        return <Badge intent={s.intent} dot>{s.label}</Badge>;
      },
    },
    {
      key: 'mrr', label: 'MRR', align: 'right',
      render: row => (
        <Text size="sm" mono tone={row.mrr == null ? 'tertiary' : 'primary'}>
          {fmtRowMrr(row.mrr, row.currency)}
        </Text>
      ),
    },
    {
      key: 'created_at', label: 'Joined',
      render: row => (
        <Text size="sm" tone="secondary">
          {row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}
        </Text>
      ),
    },
    {
      key: 'actions', label: '', width: '110px', align: 'right',
      render: row => {
        const isSuspended = row.status === 'suspended' || row.status === 'frozen';
        const menuItems = [
          canView && { label: 'View profile', onClick: () => router.visit(route('platform.admin.tenants.show', row.id)) },
          canImpersonate && !isSuspended && { label: 'Impersonate', onClick: () => impersonate(row) },
          canActivate && isSuspended && { label: 'Activate', onClick: () => activateTenant(row) },
          canSuspend && !isSuspended && row.status !== 'archived' && { label: 'Suspend', onClick: () => openSuspend(row) },
          canDelete && { divider: true },
          canDelete && { label: 'Purge tenant', danger: true, onClick: () => deleteTenant(row) },
        ].filter(Boolean);

        return (
          <HStack gap={1} justify="end" align="center">
            {canView && (
              <Button intent="soft" size="sm" onClick={() => router.visit(route('platform.admin.tenants.show', row.id))}>
                View
              </Button>
            )}
            {menuItems.length > 0 && (
              <Menu
                align="end"
                trigger={
                  <Button intent="ghost" size="sm" aria-label="More actions">
                    <EllipsisHorizontalIcon className="aeos-icon-sm" />
                  </Button>
                }
                items={menuItems}
              />
            )}
          </HStack>
        );
      },
    },
  ].filter(Boolean);

  return (
    <>
    <IndexPageLayout
      title="Tenants"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenants' },
      ]}
      description="Every tenant account and its lifecycle — status, plan, and recurring revenue at a glance."
      actions={
        canCreate && (
          <Button intent="primary" onClick={() => router.visit(route('platform.admin.tenants.create'))}>
            New tenant
          </Button>
        )
      }
      kpis={[
        <Stat key="total"  title="Total tenants"    value={stats?.total ?? 0}
              icon="users"       description={`${stats?.active ?? 0} active · ${stats?.byStatus?.trial ?? 0} trial`} />,
        <Stat key="active" title="Active tenants"   value={stats?.active ?? 0}
              icon="checkCircle" iconTone="success" description="Currently serving users" />,
        <Stat key="mrr"    title="Recurring revenue" value={fmtKpiMrr(stats?.mrr)}
              icon="chartBar"    iconTone="indigo"  description="MRR · active + trialing" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap="wrap">
          <Input
            placeholder="Search name, email, subdomain…"
            leftIcon="search"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select value={status} onChange={e => setStatus(e.target.value)} options={statusOptions} />
          <Select value={planId} onChange={e => setPlanId(e.target.value)} options={planOptions} />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <VStack gap={3}>
          {selectedIds.length > 0 && canBulk && (
            <HStack gap={2} align="center" className="aeos-table-bulkbar">
              <Text size="sm" weight={600}>{selectedIds.length} selected</Text>
              <Button intent="danger" size="sm" onClick={() => setBulkOpen(true)}>Suspend</Button>
              <Button intent="ghost" size="sm" onClick={() => router.visit(route('platform.admin.tenants.bulk.history'))}>
                Bulk history
              </Button>
              <Box grow />
              <Button intent="ghost" size="sm" onClick={() => setSelectedIds([])}>Clear</Button>
            </HStack>
          )}

          {!tableLoading && rows.length === 0 ? (
            hasActiveFilter ? (
              <EmptyState
                icon="inbox"
                title="No tenants match your filters"
                description="Try adjusting your search, status, or plan filters."
                action={<Button intent="ghost" onClick={resetFilters}>Reset filters</Button>}
              />
            ) : (
              <EmptyState
                icon="inbox"
                title="No tenants yet"
                description="Tenants appear here once they sign up or you provision them directly."
                action={canCreate && (
                  <Button intent="primary" onClick={() => router.visit(route('platform.admin.tenants.create'))}>
                    New tenant
                  </Button>
                )}
              />
            )
          ) : (
            <DataTable columns={columns} rows={rows} loading={tableLoading} />
          )}
        </VStack>
      }
      pagination={
        tenants?.last_page > 1 && (
          <Pagination
            page={tenants.current_page}
            total={tenants.last_page}
            onChange={page => router.get(route('platform.admin.tenants.index'),
              { page, search, status, plan_id: planId },
              { preserveState: true, preserveScroll: true, only: ['tenants'] })}
          />
        )
      }
    />

      {/* Bulk suspend confirmation */}
      <Modal
        open={bulkOpen}
        onClose={() => setBulkOpen(false)}
        title="Suspend tenants"
        description={`Suspend ${selectedIds.length} tenant(s)? Their users will be blocked from signing in until reactivated.`}
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setBulkOpen(false)}>Cancel</Button>
            <Button intent="danger" onClick={executeBulkSuspend}>Suspend {selectedIds.length}</Button>
          </HStack>
        }
      />

      {/* Single-tenant suspend (reason required) */}
      <Modal
        open={!!suspendTarget}
        onClose={() => setSuspendTarget(null)}
        title={suspendTarget ? `Suspend ${suspendTarget.name}` : 'Suspend tenant'}
        description="Users will be blocked from signing in until the tenant is reactivated."
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setSuspendTarget(null)}>Cancel</Button>
            <Button intent="danger" onClick={confirmSuspend} disabled={!suspendReason.trim()}>Suspend</Button>
          </HStack>
        }
      >
        <VStack gap={1}>
          <Text as="label" size="sm" weight={600} htmlFor="suspend-reason">Reason</Text>
          <Input
            id="suspend-reason"
            placeholder="e.g. Non-payment, policy violation…"
            value={suspendReason}
            onChange={e => setSuspendReason(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && suspendReason.trim() && confirmSuspend()}
            autoFocus
          />
          <Text size="xs" tone="tertiary">Recorded on the tenant’s audit trail.</Text>
        </VStack>
      </Modal>
    </>
  );
}

TenantsIndex.layout = page => (
  <App title="Tenants" railTitle="Tenant management" rail={<TenantsRail />}>{page}</App>
);
