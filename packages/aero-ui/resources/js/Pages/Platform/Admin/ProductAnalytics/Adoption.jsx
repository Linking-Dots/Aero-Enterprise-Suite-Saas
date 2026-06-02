import {
  DashboardLayout,
  DataTable,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  KPI,
  Badge,
  Progress,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Adoption({ data }) {
  const topFeatures = data?.top_features ?? [];

  const featureColumns = [
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
      label: 'Tenants Using',
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
      label: '',
      render: (row) => {
        const pct = row.adoption_pct ?? 0;
        if (pct >= 75) return <Badge intent="success">High</Badge>;
        if (pct >= 40) return <Badge intent="warning">Medium</Badge>;
        return <Badge intent="neutral">Low</Badge>;
      },
    },
  ];

  return (
    <DashboardLayout
      title="Adoption Metrics"
      breadcrumb={[
        { label: 'Platform Admin' },
        { label: 'Product Analytics' },
        { label: 'Adoption Metrics' },
      ]}
      description="DAU ratio and top feature adoption across active tenants."
    >
      <VStack gap={6}>

        {/* DAU KPIs */}
        <HStack gap={4} wrap>
          <KPI
            label="Active Tenants"
            value={data?.active_tenants ?? 0}
          />
          <KPI
            label="Tenants Using Features (30d)"
            value={data?.dau_30d ?? 0}
            intent="primary"
          />
          <KPI
            label="Platform Adoption (30d)"
            value={`${data?.adoption_pct ?? 0}%`}
            intent={
              (data?.adoption_pct ?? 0) >= 70
                ? 'success'
                : (data?.adoption_pct ?? 0) >= 40
                ? 'warning'
                : 'danger'
            }
          />
        </HStack>

        {/* Adoption rate bar */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Overall Platform Adoption</Eyebrow>
              <HStack gap={3}>
                <Progress value={data?.adoption_pct ?? 0} />
                <Text>{data?.adoption_pct ?? 0}% of active tenants used at least one feature in the last 30 days</Text>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* Top features */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Top Features by Adoption</Eyebrow>
              <DataTable
                columns={featureColumns}
                rows={topFeatures}
                empty="No feature usage data available for the last 30 days."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </DashboardLayout>
  );
}

Adoption.layout = page => <App title="Adoption Metrics">{page}</App>;
