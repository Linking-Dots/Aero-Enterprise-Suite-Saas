import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  CardHeader,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  KPI,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ErrorLogAnalytics({ analytics }) {
  const openCount     = analytics?.open_count     ?? 0;
  const resolvedCount = analytics?.resolved_count ?? 0;
  const total         = openCount + resolvedCount;

  const byTypeColumns = [
    {
      key: 'type',
      label: 'Error Type',
      render: (row) => <Badge intent="neutral">{row.type}</Badge>,
    },
    {
      key: 'count',
      label: 'Count',
      render: (row) => <Text>{row.count}</Text>,
    },
  ];

  const trendColumns = [
    {
      key: 'day',
      label: 'Day',
      render: (row) => <Mono>{row.day}</Mono>,
    },
    {
      key: 'count',
      label: 'Errors',
      render: (row) => <Text>{row.count}</Text>,
    },
  ];

  const tenantColumns = [
    {
      key: 'tenant_id',
      label: 'Tenant',
      render: (row) => <Mono>{row.tenant_id}</Mono>,
    },
    {
      key: 'count',
      label: 'Errors',
      render: (row) => <Text>{row.count}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Error Analytics"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Error Logs', href: route('platform.admin.error-logs.index') },
        { label: 'Analytics' },
      ]}
      description="Aggregated error metrics and trends across all tenants."
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('platform.admin.error-logs.index'))}
        >
          Back to Logs
        </Button>
      }
    >
      <VStack gap={4}>
        {/* KPI Summary */}
        <HStack gap={4} wrap>
          <KPI label="Total Errors" value={total} />
          <KPI label="Open" value={openCount} />
          <KPI label="Resolved" value={resolvedCount} />
          <KPI
            label="Resolution Rate"
            value={total > 0 ? `${Math.round((resolvedCount / total) * 100)}%` : '—'}
          />
        </HStack>

        <HStack gap={4} wrap align="start">
          {/* Errors by type */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Errors by Type (top 20)</Eyebrow>
                <DataTable
                  columns={byTypeColumns}
                  rows={analytics?.by_type ?? []}
                  empty="No data."
                />
              </VStack>
            </CardBody>
          </Card>

          {/* Top tenants */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Top Tenants by Error Count</Eyebrow>
                <DataTable
                  columns={tenantColumns}
                  rows={analytics?.top_tenants ?? []}
                  empty="No data."
                />
              </VStack>
            </CardBody>
          </Card>
        </HStack>

        {/* 30-day trend */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Error Trend (last 30 days)</Eyebrow>
              <DataTable
                columns={trendColumns}
                rows={analytics?.trend ?? []}
                empty="No data for the last 30 days."
              />
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </IndexPageLayout>
  );
}

ErrorLogAnalytics.layout = page => <App title="Error Analytics">{page}</App>;
