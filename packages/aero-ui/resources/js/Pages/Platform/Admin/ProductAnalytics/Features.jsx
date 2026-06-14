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
  Progress,
  Badge,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const DAYS_OPTIONS = [
  { value: '7',  label: 'Last 7 days' },
  { value: '30', label: 'Last 30 days' },
  { value: '90', label: 'Last 90 days' },
];

export default function Features({ rows, days }) {
  const canExport = useHRMAC('product-analytics.feature-usage.export');

  function setDays(d) {
    router.get(
      route('platform.admin.product-analytics.features'),
      { days: d },
      { preserveState: true, preserveScroll: true }
    );
  }

  const topAdoption = (rows ?? []).reduce((max, r) => Math.max(max, r.adoption_pct ?? 0), 0);

  const columns = [
    {
      key: 'feature_code',
      label: 'Feature',
      render: (row) => <Mono>{row.feature_code}</Mono>,
    },
    {
      key: 'events',
      label: 'Events',
      render: (row) => <Mono>{(row.events ?? 0).toLocaleString()}</Mono>,
    },
    {
      key: 'tenants',
      label: 'Tenants',
      render: (row) => <Text>{row.tenants}</Text>,
    },
    {
      key: 'adoption_pct',
      label: 'Adoption',
      render: (row) => (
        <HStack gap={2}>
          <Progress value={row.adoption_pct ?? 0} />
          <Mono>{row.adoption_pct ?? 0}%</Mono>
        </HStack>
      ),
    },
    {
      key: 'tier',
      label: 'Tier',
      render: (row) => {
        const pct = row.adoption_pct ?? 0;
        if (pct >= 75) return <Badge intent="success">High</Badge>;
        if (pct >= 40) return <Badge intent="warning">Medium</Badge>;
        return <Badge intent="neutral">Low</Badge>;
      },
    },
  ];

  return (
    <IndexPageLayout
      title="Feature Usage"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Product Analytics' },
        { label: 'Feature Usage' },
      ]}
      description="Track which features tenants use most — sorted by adoption rate."
      actions={
        <HStack gap={2}>
          <Select
            value={String(days)}
            onChange={e => setDays(e.target.value)}
            options={DAYS_OPTIONS}
          />
          {canExport && (
            <Button
              intent="soft"
              onClick={() =>
                router.get(
                  route('platform.admin.product-analytics.features'),
                  { days, export: 'csv' }
                )
              }
            >
              Export CSV
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={6}>

        <HStack gap={4} wrap>
          <KPI label="Total Features" value={(rows ?? []).length} />
          <KPI label="Peak Adoption" value={`${topAdoption}%`} intent="success" />
        </HStack>

        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Feature Usage (last {days} days)</Eyebrow>
              <DataTable
                columns={columns}
                rows={rows ?? []}
                empty="No feature usage events recorded for this period."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

Features.layout = page => <App title="Feature Usage">{page}</App>;
