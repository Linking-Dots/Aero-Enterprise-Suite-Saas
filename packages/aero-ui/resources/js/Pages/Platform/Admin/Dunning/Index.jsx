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
  Eyebrow,
  Select,
  Modal,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  failed:          'danger',
  uncollectible:   'neutral',
  pending:         'warning',
  retrying:        'info',
};

export default function DunningIndex({ stats, failedPayments, rules, filters }) {
  const toast          = useToast();
  const canRetry       = useHRMAC('dunning.failed-payments.retry');
  const canUncollect   = useHRMAC('dunning.failed-payments.mark-uncollectible');
  const canManageRules = useHRMAC('dunning.dunning-rules.manage');

  const [retryTarget,       setRetryTarget]       = useState(null);
  const [retrying,          setRetrying]           = useState(false);
  const [uncollectTarget,   setUncollectTarget]   = useState(null);
  const [uncollecting,      setUncollecting]      = useState(false);
  const [tenantFilter,      setTenantFilter]      = useState(filters?.tenant_id ?? '');

  function applyFilters() {
    router.get(
      route('platform.admin.dunning.index'),
      { tenant_id: tenantFilter },
      { preserveState: true, preserveScroll: true, only: ['failedPayments', 'filters'] }
    );
  }

  function resetFilters() {
    setTenantFilter('');
    router.get(route('platform.admin.dunning.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['failedPayments', 'filters'],
    });
  }

  function confirmRetry() {
    if (!retryTarget) return;
    setRetrying(true);
    router.post(
      route('platform.admin.dunning.failed-payments.retry', retryTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Retry dispatched for invoice ${retryTarget.number ?? retryTarget.id}.`);
          setRetryTarget(null);
        },
        onError: () => toast.error('Failed to dispatch retry.'),
        onFinish: () => setRetrying(false),
      }
    );
  }

  function confirmUncollect() {
    if (!uncollectTarget) return;
    setUncollecting(true);
    router.post(
      route('platform.admin.dunning.failed-payments.uncollectible', uncollectTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Invoice ${uncollectTarget.number ?? uncollectTarget.id} marked uncollectible.`);
          setUncollectTarget(null);
        },
        onError: () => toast.error('Failed to mark uncollectible.'),
        onFinish: () => setUncollecting(false),
      }
    );
  }

  const paymentColumns = [
    {
      key: 'invoice',
      label: 'Invoice',
      render: (row) => <Mono>{row.number ?? row.id}</Mono>,
    },
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
      key: 'amount',
      label: 'Amount',
      render: (row) => <Mono>${Number(row.amount).toFixed(2)}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'failed_at',
      label: 'Failed At',
      render: (row) => (
        <Mono>{row.failed_at ? new Date(row.failed_at).toLocaleDateString() : '—'}</Mono>
      ),
    },
    {
      key: 'retry_count',
      label: 'Retries',
      render: (row) => <Mono>{row.retry_count ?? 0}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canRetry && row.status !== 'uncollectible' && (
            <Button intent="soft" size="sm" onClick={() => setRetryTarget(row)}>
              Retry
            </Button>
          )}
          {canUncollect && row.status !== 'uncollectible' && (
            <Button intent="danger" size="sm" onClick={() => setUncollectTarget(row)}>
              Uncollectible
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const ruleColumns = [
    {
      key: 'order_index',
      label: '#',
      render: (row) => <Mono>{row.order_index + 1}</Mono>,
    },
    {
      key: 'day_offset',
      label: 'Day Offset',
      render: (row) => <Mono>Day {row.day_offset}</Mono>,
    },
    {
      key: 'action',
      label: 'Action',
      render: (row) => <Badge intent="neutral">{row.action}</Badge>,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: () => (
        canManageRules && (
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.dunning.rules.index'))}
          >
            Edit
          </Button>
        )
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Dunning"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Dunning' },
      ]}
      description="Monitor failed payments, manage retry rules, and recover revenue."
      actions={
        canManageRules && (
          <Button
            intent="soft"
            leftIcon="settings"
            onClick={() => router.get(route('platform.admin.dunning.rules.index'))}
          >
            Manage Rules
          </Button>
        )
      }
    >
      <VStack gap={6}>
        {/* Stats tiles */}
        <HStack gap={4} wrap>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>Failed Payments</Eyebrow>
                <Text size="xl">{stats?.failed_count ?? 0}</Text>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>Revenue at Risk</Eyebrow>
                <Text size="xl">${Number(stats?.revenue_at_risk ?? 0).toFixed(2)}</Text>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>Recovered (30d)</Eyebrow>
                <Text size="xl">${Number(stats?.recovered_30d ?? 0).toFixed(2)}</Text>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>Affected Tenants</Eyebrow>
                <Text size="xl">{stats?.affected_tenants ?? 0}</Text>
              </VStack>
            </CardBody>
          </Card>
        </HStack>

        {/* Dunning Rules summary */}
        <VStack gap={3}>
          <HStack gap={3}>
            <Eyebrow>Retry Rules</Eyebrow>
            {canManageRules && (
              <Button
                intent="ghost"
                size="sm"
                onClick={() => router.get(route('platform.admin.dunning.rules.index'))}
              >
                Edit All Rules
              </Button>
            )}
          </HStack>
          <DataTable
            columns={ruleColumns}
            rows={rules ?? []}
            empty="No dunning rules configured."
          />
        </VStack>

        {/* Failed Payments */}
        <VStack gap={3}>
          <Eyebrow>Failed Payments</Eyebrow>

          <HStack gap={3}>
            <Select
              value={tenantFilter}
              onChange={e => setTenantFilter(e.target.value)}
              options={[
                { value: '', label: 'All Tenants' },
                ...(stats?.top_affected_tenants ?? []).map(t => ({
                  value: t.tenant_id,
                  label: t.tenant?.name ?? t.tenant_id,
                })),
              ]}
            />
            <Button intent="soft" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost" onClick={resetFilters}>Reset</Button>
          </HStack>

          <DataTable
            columns={paymentColumns}
            rows={failedPayments?.data ?? []}
            empty="No failed payments found."
          />

          {(failedPayments?.last_page ?? 1) > 1 && (
            <Pagination
              page={failedPayments.current_page}
              total={failedPayments.last_page}
              onChange={page =>
                router.get(
                  route('platform.admin.dunning.index'),
                  { page, tenant_id: tenantFilter },
                  { preserveState: true, preserveScroll: true, only: ['failedPayments'] }
                )
              }
            />
          )}
        </VStack>
      </VStack>

      {/* Retry Confirm Modal */}
      <Modal
        open={!!retryTarget}
        onClose={() => setRetryTarget(null)}
        title="Retry Payment"
        description={`Retry payment for invoice ${retryTarget?.number ?? retryTarget?.id} (tenant: ${retryTarget?.tenant?.name ?? retryTarget?.tenant_id})? A payment retry job will be dispatched immediately.`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={retrying} disabled={retrying} onClick={confirmRetry}>
              Retry Payment
            </Button>
            <Button intent="ghost" onClick={() => setRetryTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Mark Uncollectible Confirm Modal */}
      <Modal
        open={!!uncollectTarget}
        onClose={() => setUncollectTarget(null)}
        title="Mark as Uncollectible"
        description={`Mark invoice ${uncollectTarget?.number ?? uncollectTarget?.id} as uncollectible? This will suspend only the owning subscription and stop further retry attempts. This action is irreversible.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={uncollecting} disabled={uncollecting} onClick={confirmUncollect}>
              Mark Uncollectible
            </Button>
            <Button intent="ghost" onClick={() => setUncollectTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

DunningIndex.layout = page => <App title="Dunning">{page}</App>;
