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
  Mono,
  Field,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:    'success',
  trialing:  'primary',
  past_due:  'danger',
  cancelled: 'neutral',
  paused:    'warning',
};

export default function Subscriptions({ subscriptions, filters, plans }) {
  const toast = useToast();
  const canCancel  = useHRMAC('billing-management.subscriptions.cancel');
  const canUpgrade = useHRMAC('billing-management.subscriptions.upgrade');

  const [status, setStatus]             = useState(filters?.status ?? '');
  const [subType, setSubType]           = useState(filters?.subscription_type ?? '');

  const [cancelTarget, setCancelTarget] = useState(null);
  const [cancelReason, setCancelReason] = useState('');
  const [cancelling,   setCancelling]   = useState(false);

  const [upgradeTarget, setUpgradeTarget] = useState(null);
  const [upgradePlanId, setUpgradePlanId] = useState('');
  const [upgrading,     setUpgrading]     = useState(false);

  function applyFilters() {
    router.get(
      route('platform.admin.billing.subscriptions.index'),
      { status, subscription_type: subType },
      { preserveState: true, preserveScroll: true, only: ['subscriptions', 'filters'] }
    );
  }

  function resetFilters() {
    setStatus('');
    setSubType('');
    router.get(route('platform.admin.billing.subscriptions.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['subscriptions', 'filters'],
    });
  }

  function confirmCancel() {
    if (!cancelTarget) return;
    setCancelling(true);
    router.post(
      route('platform.admin.billing.subscriptions.cancel', cancelTarget.id),
      { reason: cancelReason },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Subscription cancelled.');
          setCancelTarget(null);
          setCancelReason('');
        },
        onError: () => toast.error('Failed to cancel subscription.'),
        onFinish: () => setCancelling(false),
      }
    );
  }

  function confirmUpgrade() {
    if (!upgradeTarget || !upgradePlanId) return;
    setUpgrading(true);
    router.post(
      route('platform.admin.billing.subscriptions.upgrade', upgradeTarget.id),
      { plan_id: upgradePlanId },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Subscription upgraded.');
          setUpgradeTarget(null);
          setUpgradePlanId('');
        },
        onError: () => toast.error('Failed to upgrade subscription.'),
        onFinish: () => setUpgrading(false),
      }
    );
  }

  const statusOptions = [
    { value: '',          label: 'All Statuses' },
    { value: 'active',    label: 'Active' },
    { value: 'trialing',  label: 'Trialing' },
    { value: 'past_due',  label: 'Past Due' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'paused',    label: 'Paused' },
  ];

  const typeOptions = [
    { value: '',        label: 'All Types' },
    { value: 'plan',    label: 'Plan Subscription' },
    { value: 'product', label: 'Product Subscription' },
  ];

  const planOptions = [
    { value: '', label: 'Select plan...' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const TYPE_INTENT = { plan: 'primary', product: 'amber' };

  const columns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.tenant_id))}
        >
          {row.tenant?.name ?? row.tenant_id}
        </Button>
      ),
    },
    {
      key: 'type',
      label: 'Type',
      render: (row) => {
        const t = row.subscription_type ?? (row.plan_id ? 'plan' : 'product');
        return (
          <Badge intent={TYPE_INTENT[t] ?? 'neutral'}>
            {t === 'product' ? `Product: ${row.product?.name ?? '—'}` : 'Plan'}
          </Badge>
        );
      },
    },
    {
      key: 'plan',
      label: 'Plan / Product',
      render: (row) => {
        if (row.subscription_type === 'product' || (!row.plan_id && row.product)) {
          return <Text>{row.product?.name ?? '—'}</Text>;
        }
        return <Text>{row.plan?.name ?? '—'}</Text>;
      },
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'renews_at',
      label: 'Renews',
      render: (row) => (
        <Mono tone="secondary">
          {row.renews_at ? new Date(row.renews_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canUpgrade && row.status !== 'cancelled' && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => {
                setUpgradeTarget(row);
                setUpgradePlanId(String(row.plan_id ?? ''));
              }}
            >
              Upgrade
            </Button>
          )}
          {canCancel && row.status !== 'cancelled' && (
            <Button
              intent="danger"
              size="sm"
              onClick={() => setCancelTarget(row)}
            >
              Cancel
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Subscriptions"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Billing', href: route('platform.admin.billing.subscriptions.index') },
        { label: 'Subscriptions' },
      ]}
      description="Manage all tenant plan and product subscriptions. Both a plan and a product subscription are required for module access."
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Select
            value={subType}
            onChange={e => setSubType(e.target.value)}
            options={typeOptions}
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={statusOptions}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={subscriptions?.data ?? []}
          empty="No subscriptions found."
        />

        {subscriptions?.last_page > 1 && (
          <Pagination
            page={subscriptions.current_page}
            total={subscriptions.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.billing.subscriptions.index'),
                { page, status, subscription_type: subType },
                { preserveState: true, preserveScroll: true, only: ['subscriptions'] }
              )
            }
          />
        )}
      </VStack>

      {/* Cancel Modal */}
      <Modal
        open={!!cancelTarget}
        onClose={() => setCancelTarget(null)}
        title="Cancel Subscription"
        description={`Cancel the subscription for "${cancelTarget?.tenant?.name ?? cancelTarget?.tenant_id}"? This action will end their access at the next billing cycle.`}
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={cancelling}
              disabled={cancelling}
              onClick={confirmCancel}
            >
              Confirm Cancel
            </Button>
            <Button intent="ghost" onClick={() => setCancelTarget(null)}>
              Back
            </Button>
          </HStack>
        }
      >
        <Field label="Cancellation Reason" htmlFor="cancel_reason">
          <Input
            id="cancel_reason"
            value={cancelReason}
            onChange={e => setCancelReason(e.target.value)}
            placeholder="Optional — non-payment, downgrade, etc."
          />
        </Field>
      </Modal>

      {/* Upgrade Modal */}
      <Modal
        open={!!upgradeTarget}
        onClose={() => setUpgradeTarget(null)}
        title="Upgrade Subscription"
        description={`Select a new plan for "${upgradeTarget?.tenant?.name ?? upgradeTarget?.tenant_id}".`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={upgrading}
              disabled={upgrading || !upgradePlanId}
              onClick={confirmUpgrade}
            >
              Confirm Upgrade
            </Button>
            <Button intent="ghost" onClick={() => setUpgradeTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="New Plan" htmlFor="upgrade_plan">
          <Select
            id="upgrade_plan"
            value={upgradePlanId}
            onChange={e => setUpgradePlanId(e.target.value)}
            options={planOptions}
          />
        </Field>
      </Modal>
    </IndexPageLayout>
  );
}

Subscriptions.layout = page => <App title="Subscriptions">{page}</App>;
