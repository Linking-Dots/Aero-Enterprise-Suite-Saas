import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
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
import PlansRail from './PlansRail.jsx';

/* Lifecycle status → Badge intent + label. */
const STATUS = {
  active:   { intent: 'success', label: 'Active' },
  draft:    { intent: 'warning', label: 'Draft' },
  archived: { intent: 'neutral', label: 'Archived' },
};

/* Tier → Badge intent, drawn from the available badge palette. Business ships in
   the professional tier, so it reads indigo alongside Professional — by design. */
const TIER = {
  free:         { intent: 'neutral', label: 'Free' },
  starter:      { intent: 'primary', label: 'Starter' },
  professional: { intent: 'indigo',  label: 'Professional' },
  enterprise:   { intent: 'warning', label: 'Enterprise' },
};

const CURRENCY_SYMBOL = { USD: '$', GBP: '£', EUR: '€', BDT: '৳', AUD: 'A$', CAD: 'C$' };

/* Full price with currency symbol. */
function fmtMoney(v, currency) {
  const sym = CURRENCY_SYMBOL[currency] ?? '$';
  return `${sym}${Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

/* Headline MRR — compact for the KPI strip. */
function fmtKpiMrr(v) {
  const n = Number(v ?? 0);
  if (n >= 1000) return `$${(n / 1000).toLocaleString(undefined, { maximumFractionDigits: 1 })}k`;
  return `$${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function PlansIndex({ plans, stats, filters }) {
  const toast = useToast();
  const canCreate  = useHRMAC('plan-management.plan-list.create');
  const canView    = useHRMAC('plan-management.plan-details.view');
  const canEdit    = useHRMAC('plan-management.plan-list.edit');
  const canClone   = useHRMAC('plan-management.plan-list.clone');
  const canArchive = useHRMAC('plan-management.plan-list.archive');

  const [search, setSearch] = useState(filters?.search ?? '');
  const [status, setStatus] = useState(filters?.status ?? '');
  const [tier, setTier]     = useState(filters?.tier ?? '');
  const [archiveTarget, setArchiveTarget] = useState(null);

  // Table skeleton while any partial reload (filter/page) is in flight.
  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const rows = plans?.data ?? [];
  const hasActiveFilter = !!(search || status || tier);

  function applyFilters() {
    router.get(route('platform.admin.plans.index'), { search, status, tier }, {
      preserveState: true, preserveScroll: true, only: ['plans', 'filters'],
    });
  }

  function resetFilters() {
    setSearch(''); setStatus(''); setTier('');
    router.get(route('platform.admin.plans.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['plans', 'filters'],
    });
  }

  function clonePlan(plan) {
    router.post(route('platform.admin.plans.clone', plan.id), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success(`"${plan.name}" cloned as a draft.`),
      onError:   () => toast.error('Clone failed.'),
    });
  }

  function confirmArchive() {
    if (!archiveTarget) return;
    router.post(route('platform.admin.plans.archive', archiveTarget.id), {}, {
      preserveScroll: true,
      onSuccess: () => { toast.success(`"${archiveTarget.name}" archived.`); setArchiveTarget(null); },
      onError:   () => toast.error('Archive failed.'),
    });
  }

  const statusOptions = [
    { value: '',         label: 'All statuses' },
    { value: 'active',   label: 'Active' },
    { value: 'draft',    label: 'Draft' },
    { value: 'archived', label: 'Archived' },
  ];
  const tierOptions = [
    { value: '',             label: 'All tiers' },
    { value: 'free',         label: 'Free' },
    { value: 'starter',      label: 'Starter' },
    { value: 'professional', label: 'Professional' },
    { value: 'enterprise',   label: 'Enterprise' },
  ];

  const columns = [
    {
      key: 'name', label: 'Plan', width: '26%',
      render: row => (
        <HStack gap={3} align="center">
          <Avatar name={row.name} size={30} />
          <VStack gap={0}>
            <Text size="sm" weight={600}>{row.name}</Text>
            {row.slug && <Text size="xs" tone="tertiary">{row.slug}</Text>}
          </VStack>
        </HStack>
      ),
    },
    {
      key: 'tier', label: 'Tier',
      render: row => {
        const t = TIER[row.tier] ?? { intent: 'neutral', label: row.tier ?? '—' };
        return <Badge intent={t.intent} dot>{t.label}</Badge>;
      },
    },
    {
      key: 'price_monthly', label: 'Price / mo', align: 'right',
      render: row => <Text size="sm">{fmtMoney(row.price_monthly, row.currency)}</Text>,
    },
    {
      key: 'price_annual', label: 'Annual', align: 'right',
      render: row => row.price_annual > 0
        ? <Text size="sm" tone="secondary">{fmtMoney(row.price_annual, row.currency)}</Text>
        : <Text size="sm" tone="tertiary">—</Text>,
    },
    {
      key: 'active_subscribers_count', label: 'Subscribers', align: 'right',
      render: row => <Text size="sm" tone="secondary">{row.active_subscribers_count ?? 0}</Text>,
    },
    {
      key: 'mrr', label: 'MRR', align: 'right',
      render: row => (
        <Text size="sm" tone={row.mrr > 0 ? 'primary' : 'tertiary'}>
          {row.mrr > 0 ? fmtMoney(row.mrr, row.currency) : '—'}
        </Text>
      ),
    },
    {
      key: 'is_public', label: 'Visibility',
      render: row => row.is_public
        ? <Badge intent="neutral">Public</Badge>
        : <Badge intent="warning">Private</Badge>,
    },
    {
      key: 'status', label: 'Status',
      render: row => {
        const s = STATUS[row.status] ?? { intent: 'neutral', label: row.status };
        return <Badge intent={s.intent} dot>{s.label}</Badge>;
      },
    },
    {
      key: 'actions', label: '', width: '110px', align: 'right',
      render: row => {
        const menuItems = [
          canView && { label: 'View plan', onClick: () => router.visit(route('platform.admin.plans.show', row.id)) },
          canEdit && { label: 'Edit', onClick: () => router.visit(route('platform.admin.plans.edit', row.id)) },
          canClone && { label: 'Clone', onClick: () => clonePlan(row) },
          canArchive && row.status !== 'archived' && { divider: true },
          canArchive && row.status !== 'archived' && { label: 'Archive', danger: true, onClick: () => setArchiveTarget(row) },
        ].filter(Boolean);

        return (
          <HStack gap={1} justify="end" align="center">
            {canView && (
              <Button intent="soft" size="sm" onClick={() => router.visit(route('platform.admin.plans.show', row.id))}>
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
  ];

  return (
    <>
    <IndexPageLayout
      title="Plans"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Plans' },
      ]}
      description="Every subscription plan in the catalog — pricing, reach, and recurring revenue at a glance."
      actions={
        canCreate && (
          <Button intent="primary" onClick={() => router.visit(route('platform.admin.plans.create'))}>
            New plan
          </Button>
        )
      }
      kpis={[
        <Stat key="total" title="Total plans" value={stats?.total ?? 0}
              icon="layout" iconTone="indigo"
              description={`${stats?.active ?? 0} active · ${stats?.public ?? 0} public`} />,
        <Stat key="mrr" title="Plan MRR" value={fmtKpiMrr(stats?.mrr)}
              icon="chartBar" iconTone="amber"
              description="Recurring · active + trialing" />,
        <Stat key="subs" title="Subscribers" value={stats?.subscribers ?? 0}
              icon="users" iconTone="success"
              description="Active tenants on a plan" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap="wrap">
          <Input
            placeholder="Search plans by name or slug…"
            leftIcon="search"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
          />
          <Select value={status} onChange={e => setStatus(e.target.value)} options={statusOptions} />
          <Select value={tier} onChange={e => setTier(e.target.value)} options={tierOptions} />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        !tableLoading && rows.length === 0 ? (
          hasActiveFilter ? (
            <EmptyState
              icon="inbox"
              title="No plans match your filters"
              description="Try adjusting your search, status, or tier filters."
              action={<Button intent="ghost" onClick={resetFilters}>Reset filters</Button>}
            />
          ) : (
            <EmptyState
              icon="inbox"
              title="No plans yet"
              description="Create your first subscription plan to start onboarding tenants."
              action={canCreate && (
                <Button intent="primary" onClick={() => router.visit(route('platform.admin.plans.create'))}>
                  New plan
                </Button>
              )}
            />
          )
        ) : (
          <DataTable columns={columns} rows={rows} loading={tableLoading} />
        )
      }
      pagination={
        plans?.last_page > 1 && (
          <Pagination
            page={plans.current_page}
            total={plans.last_page}
            onChange={page => router.get(route('platform.admin.plans.index'),
              { page, search, status, tier },
              { preserveState: true, preserveScroll: true, only: ['plans'] })}
          />
        )
      }
    />

      <Modal
        open={!!archiveTarget}
        onClose={() => setArchiveTarget(null)}
        title={archiveTarget ? `Archive ${archiveTarget.name}` : 'Archive plan'}
        description="Existing subscribers keep their plan, but no new sign-ups will be allowed and it leaves the public picker."
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setArchiveTarget(null)}>Cancel</Button>
            <Button intent="danger" onClick={confirmArchive}>Archive plan</Button>
          </HStack>
        }
      />
    </>
  );
}

PlansIndex.layout = page => (
  <App title="Plans" railTitle="Plan management" rail={<PlansRail />}>{page}</App>
);
