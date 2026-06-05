import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  KPI,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SUB_STATUS_INTENT = {
  active:    'success',
  trialing:  'primary',
  past_due:  'danger',
  cancelled: 'neutral',
  paused:    'warning',
};

const INV_STATUS_INTENT = {
  paid:    'success',
  open:    'warning',
  draft:   'neutral',
  overdue: 'danger',
  void:    'neutral',
};

export default function BillingDashboard({ stats, recent_subscriptions, recent_invoices }) {
  const subscriptionColumns = [
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
      key: 'plan',
      label: 'Plan',
      render: (row) => <Text>{row.plan?.name ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={SUB_STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'started_at',
      label: 'Started',
      render: (row) => (
        <Mono tone="secondary">
          {row.started_at ? new Date(row.started_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  const invoiceColumns = [
    {
      key: 'number',
      label: 'Invoice #',
      render: (row) => <Mono>{row.number ?? row.id}</Mono>,
    },
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => <Text>{row.tenant?.name ?? '—'}</Text>,
    },
    {
      key: 'amount',
      label: 'Amount',
      render: (row) => (
        <Mono>{row.amount != null ? `$${Number(row.amount).toFixed(2)}` : '—'}</Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={INV_STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'due_date',
      label: 'Due',
      render: (row) => (
        <Mono tone="secondary">
          {row.due_date ? new Date(row.due_date).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  return (
    <DashboardLayout
      title="Billing Dashboard"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Billing' },
      ]}
      description="Revenue metrics and billing activity at a glance."
    >
      <VStack gap={6}>

        {/* KPI Cards — combined */}
        <HStack gap={4} wrap>
          <KPI
            label="Total MRR"
            value={`$${Number(stats?.mrr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          />
          <KPI
            label="Total ARR"
            value={`$${Number(stats?.arr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          />
          <KPI
            label="Active Subscriptions"
            value={stats?.active_subs ?? 0}
          />
          <KPI
            label="Pending Invoices"
            value={stats?.pending_invoices ?? 0}
          />
          <KPI
            label="Overdue Invoices"
            value={stats?.overdue ?? 0}
            intent="danger"
          />
        </HStack>

        {/* MRR Breakdown — plan vs product */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>MRR Breakdown</Eyebrow>
              <HStack gap={4} wrap>
                <KPI
                  label="Plan MRR"
                  value={`$${Number(stats?.plan_mrr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                />
                <KPI
                  label="Product MRR"
                  value={`$${Number(stats?.product_mrr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                />
                <KPI
                  label="Plan ARR"
                  value={`$${Number(stats?.plan_arr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                />
                <KPI
                  label="Product ARR"
                  value={`$${Number(stats?.product_arr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                />
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* Recent Subscriptions */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2}>
                <Eyebrow>Recent Subscriptions</Eyebrow>
                <Button
                  intent="ghost"
                  size="sm"
                  onClick={() => router.get(route('platform.admin.billing.subscriptions.index'))}
                >
                  View All
                </Button>
              </HStack>
              <DataTable
                columns={subscriptionColumns}
                rows={recent_subscriptions ?? []}
                empty="No recent subscriptions."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Recent Invoices */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2}>
                <Eyebrow>Recent Invoices</Eyebrow>
                <Button
                  intent="ghost"
                  size="sm"
                  onClick={() => router.get(route('platform.admin.billing.invoices.index'))}
                >
                  View All
                </Button>
              </HStack>
              <DataTable
                columns={invoiceColumns}
                rows={recent_invoices ?? []}
                empty="No recent invoices."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </DashboardLayout>
  );
}

BillingDashboard.layout = page => <App title="Billing Dashboard">{page}</App>;
