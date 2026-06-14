import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
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
  Pill,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SIGNUP_COLUMNS = [
  {
    key: 'date',
    label: 'Date',
    render: (row) => <Mono>{row.date}</Mono>,
  },
  {
    key: 'new_tenants',
    label: 'New Tenants',
    render: (row) => (
      <Badge intent="success">{row.new_tenants}</Badge>
    ),
  },
  {
    key: 'churned',
    label: 'Churned',
    render: (row) => (
      <Badge intent={row.churned > 0 ? 'danger' : 'neutral'}>{row.churned}</Badge>
    ),
  },
  {
    key: 'net',
    label: 'Net',
    render: (row) => {
      const net = (row.new_tenants ?? 0) - (row.churned ?? 0);
      return <Badge intent={net >= 0 ? 'success' : 'danger'}>{net >= 0 ? '+' : ''}{net}</Badge>;
    },
  },
];

export default function Tenants({ data, range }) {
  function setRange(field, value) {
    router.get(
      route('platform.admin.analytics.tenants'),
      { ...range, [field]: value },
      { preserveState: true, preserveScroll: true }
    );
  }

  const totalNew    = (data?.signup_trend ?? []).reduce((s, r) => s + (r.new_tenants ?? 0), 0);
  const totalChurn  = (data?.signup_trend ?? []).reduce((s, r) => s + (r.churned ?? 0), 0);

  return (
    <IndexPageLayout
      title="Tenant Analytics"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Analytics', href: route('platform.admin.analytics.index') },
        { label: 'Tenants' },
      ]}
      description="Signup trends, churn, and plan distribution over time."
    >
      <VStack gap={6}>

        {/* Summary KPIs */}
        <HStack gap={4} wrap>
          <KPI label="New Tenants (period)" value={totalNew} intent="success" />
          <KPI label="Churned (period)" value={totalChurn} intent={totalChurn > 0 ? 'danger' : 'success'} />
          <KPI label="Net Growth" value={totalNew - totalChurn >= 0 ? `+${totalNew - totalChurn}` : totalNew - totalChurn} />
        </HStack>

        {/* Date range selector */}
        <Card>
          <CardBody>
            <HStack gap={3} wrap>
              <Text tone="secondary">From:</Text>
              <input
                type="date"
                className="aeos-text-sm"
                defaultValue={range?.from}
                onBlur={e => setRange('from', e.target.value)}
              />
              <Text tone="secondary">To:</Text>
              <input
                type="date"
                className="aeos-text-sm"
                defaultValue={range?.to}
                onBlur={e => setRange('to', e.target.value)}
              />
            </HStack>
          </CardBody>
        </Card>

        {/* Signup trend */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Signup Trend ({range?.from} to {range?.to})</Eyebrow>
              <DataTable
                columns={SIGNUP_COLUMNS}
                rows={data?.signup_trend ?? []}
                empty="No data for this period."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Plan distribution */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Plan Distribution</Eyebrow>
              <HStack gap={2} wrap>
                {(data?.plan_distribution ?? []).length === 0 ? (
                  <Text tone="secondary">No plan data available.</Text>
                ) : (
                  (data?.plan_distribution ?? []).map(p => (
                    <Badge key={p.plan_id} intent="neutral">
                      {p.plan?.name ?? `Plan #${p.plan_id}`}: {p.count}
                    </Badge>
                  ))
                )}
              </HStack>
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

Tenants.layout = page => <App title="Tenant Analytics">{page}</App>;
