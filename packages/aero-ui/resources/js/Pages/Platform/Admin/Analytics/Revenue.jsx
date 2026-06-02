import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  KPI,
  Select,
  Badge,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const BUCKET_OPTIONS = [
  { value: 'day',   label: 'Daily' },
  { value: 'week',  label: 'Weekly' },
  { value: 'month', label: 'Monthly' },
];

export default function Revenue({ data, range, bucket }) {
  const canExport = useHRMAC('platform-analytics.revenue-reports.export');

  function setBucket(b) {
    router.get(
      route('platform.admin.analytics.revenue'),
      { ...range, bucket: b },
      { preserveState: true, preserveScroll: true }
    );
  }

  function setRange(field, value) {
    router.get(
      route('platform.admin.analytics.revenue'),
      { ...range, [field]: value, bucket },
      { preserveState: true, preserveScroll: true }
    );
  }

  const trendColumns = [
    {
      key: 'period',
      label: 'Period',
      render: (row) => <Mono>{row.period}</Mono>,
    },
    {
      key: 'mrr',
      label: 'MRR',
      render: (row) => (
        <Mono>{`$${Number(row.mrr).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}</Mono>
      ),
    },
    {
      key: 'revenue',
      label: 'Revenue',
      render: (row) => (
        <Mono>{`$${Number(row.revenue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}</Mono>
      ),
    },
  ];

  const byPlanColumns = [
    {
      key: 'name',
      label: 'Plan',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'tenants',
      label: 'Tenants',
      render: (row) => <Mono>{row.tenants}</Mono>,
    },
    {
      key: 'mrr',
      label: 'Plan MRR',
      render: (row) => (
        <Mono>{`$${Number(row.mrr).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}</Mono>
      ),
    },
  ];

  const byProductColumns = [
    {
      key: 'name',
      label: 'Product',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'tenants',
      label: 'Tenants',
      render: (row) => <Mono>{row.tenants}</Mono>,
    },
    {
      key: 'mrr',
      label: 'Product MRR',
      render: (row) => (
        <Mono>{`$${Number(row.mrr).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}</Mono>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Revenue Reports"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Analytics', href: route('platform.admin.analytics.index') },
        { label: 'Revenue' },
      ]}
      description="Revenue trend, plan breakdown, and product breakdown analytics."
      actions={
        canExport && (
          <Button
            intent="soft"
            onClick={() =>
              router.get(
                route('platform.admin.analytics.revenue'),
                { ...range, bucket, export: 'csv' }
              )
            }
          >
            Export CSV
          </Button>
        )
      }
    >
      <VStack gap={6}>

        {/* Churn summary */}
        <HStack gap={4} wrap>
          <KPI
            label="New Tenants (period)"
            value={data?.churn?.new ?? 0}
            intent="success"
          />
          <KPI
            label="Churned Tenants (period)"
            value={data?.churn?.churned ?? 0}
            intent={data?.churn?.churned > 0 ? 'danger' : 'success'}
          />
        </HStack>

        {/* Date range + bucket selector */}
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
              <Select
                value={bucket}
                onChange={e => setBucket(e.target.value)}
                options={BUCKET_OPTIONS}
              />
            </HStack>
          </CardBody>
        </Card>

        {/* Revenue trend table */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Revenue Trend ({bucket})</Eyebrow>
              <DataTable
                columns={trendColumns}
                rows={data?.trend ?? []}
                empty="No data for this period."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* By Plan */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Revenue by Plan</Eyebrow>
              <DataTable
                columns={byPlanColumns}
                rows={data?.by_plan ?? []}
                empty="No active plan subscriptions."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* By Product */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Revenue by Product</Eyebrow>
              <DataTable
                columns={byProductColumns}
                rows={data?.by_product ?? []}
                empty="No active product subscriptions."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

Revenue.layout = page => <App title="Revenue Reports">{page}</App>;
