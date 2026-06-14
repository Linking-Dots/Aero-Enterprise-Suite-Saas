import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
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
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:   'success',
  archived: 'neutral',
  draft:    'warning',
};

const SUB_STATUS_INTENT = {
  active:    'success',
  trialing:  'primary',
  past_due:  'danger',
  cancelled: 'neutral',
  paused:    'warning',
};

export default function PlansShow({ plan, metrics }) {
  const canEdit = useHRMAC('plan-management.plan-list.edit');

  const subscriberColumns = [
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
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={SUB_STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
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
    {
      key: 'renews_at',
      label: 'Renews',
      render: (row) => (
        <Mono tone="secondary">
          {row.renews_at ? new Date(row.renews_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  return (
    <DetailPageLayout
      title={plan.name}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Plans', href: route('platform.admin.plans.index') },
        { label: plan.name },
      ]}
      description={plan.description ?? ''}
      actions={
        <HStack gap={2}>
          <Badge intent={STATUS_INTENT[plan.status] ?? 'neutral'}>{plan.status}</Badge>
          {canEdit && (
            <Button
              intent="soft"
              leftIcon="pencil"
              onClick={() => router.get(route('platform.admin.plans.edit', plan.id))}
            >
              Edit Plan
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={6}>

        {/* KPI Row */}
        <HStack gap={4}>
          <KPI
            label="Active Subscribers"
            value={metrics?.active_subscribers ?? 0}
          />
          <KPI
            label="MRR"
            value={`$${Number(metrics?.mrr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          />
        </HStack>

        {/* Plan Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Plan Details</Eyebrow>
              <dl className="pa1-dl-grid">
                <dt><Text tone="secondary">Monthly Price</Text></dt>
                <dd>
                  <Mono>{plan.price_monthly != null ? `$${Number(plan.price_monthly).toFixed(2)}` : '—'}</Mono>
                </dd>

                <dt><Text tone="secondary">Yearly Price</Text></dt>
                <dd>
                  <Mono>{plan.price_yearly != null ? `$${Number(plan.price_yearly).toFixed(2)}` : '—'}</Mono>
                </dd>

                <dt><Text tone="secondary">Trial Days</Text></dt>
                <dd><Text>{plan.trial_days ?? '0'}</Text></dd>

                <dt><Text tone="secondary">Max Users</Text></dt>
                <dd><Text>{plan.max_users ?? 'Unlimited'}</Text></dd>

                <dt><Text tone="secondary">Max Storage</Text></dt>
                <dd><Text>{plan.max_storage_gb != null ? `${plan.max_storage_gb} GB` : 'Unlimited'}</Text></dd>

                <dt><Text tone="secondary">Max API Calls / mo</Text></dt>
                <dd><Text>{plan.max_api_calls_per_mo ?? 'Unlimited'}</Text></dd>
              </dl>
            </VStack>
          </CardBody>
        </Card>

        {/* Subscribers Table */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Subscribers</Eyebrow>
              <DataTable
                columns={subscriberColumns}
                rows={plan.subscriptions ?? []}
                empty="No active subscribers on this plan."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </DetailPageLayout>
  );
}

PlansShow.layout = page => (
  <App title={page.props.plan?.name ?? 'Plan Detail'}>{page}</App>
);
