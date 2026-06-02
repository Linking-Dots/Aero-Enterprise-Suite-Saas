import {
  IndexPageLayout,
  DataTable,
  Card,
  CardBody,
  HStack, VStack,
  Text, Mono,
  Alert,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Usage({ stats }) {
  const topEndpointColumns = [
    {
      key: 'endpoint', label: 'Endpoint', width: '70%',
      render: row => <Mono size="sm">{row.endpoint}</Mono>,
    },
    {
      key: 'count', label: 'Requests', width: '30%', align: 'right',
      render: row => <Text size="sm">{row.count?.toLocaleString() ?? 0}</Text>,
    },
  ];

  const byKeyColumns = [
    {
      key: 'key_name', label: 'Key Name', width: '70%',
      render: row => <Text size="sm">{row.key_name}</Text>,
    },
    {
      key: 'count', label: 'Requests', width: '30%', align: 'right',
      render: row => <Text size="sm">{row.count?.toLocaleString() ?? 0}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="API Usage"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'API Usage' },
      ]}
      description="Monitor API request volume and usage patterns across endpoints and keys."
      kpis={[
        <Stat
          key="today"
          title="Requests Today"
          value={stats?.total_requests_today?.toLocaleString() ?? 0}
          icon="bolt"
        />,
        <Stat
          key="week"
          title="Requests This Week"
          value={stats?.total_requests_week?.toLocaleString() ?? 0}
          icon="chartBar"
          iconTone="success"
        />,
      ]}
    >
      <VStack gap={4}>
        {stats?.note && (
          <Alert intent="info" title={stats.note} />
        )}

        <Card>
          <CardBody>
            <VStack gap={3}>
              <Text size="sm" tone="secondary">Top Endpoints</Text>
              <DataTable
                columns={topEndpointColumns}
                rows={stats?.top_endpoints ?? []}
                empty="No endpoint data available."
              />
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={3}>
              <Text size="sm" tone="secondary">Requests by Key</Text>
              <DataTable
                columns={byKeyColumns}
                rows={stats?.requests_by_key ?? []}
                empty="No key usage data available."
              />
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </IndexPageLayout>
  );
}

Usage.layout = page => (
  <App title="API Usage">{page}</App>
);
