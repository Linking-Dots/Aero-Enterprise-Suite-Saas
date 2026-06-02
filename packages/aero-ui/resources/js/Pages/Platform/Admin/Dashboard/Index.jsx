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
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const HEALTH_INTENT = { ok: 'success', warn: 'warning', error: 'danger', unknown: 'neutral' };

const STATUS_INTENT = {
  active:    'success',
  trial:     'warning',
  suspended: 'danger',
  inactive:  'neutral',
};

export default function DashboardIndex({ stats, recentTenants, systemHealth }) {
  const canViewTenants = useHRMAC('platform-dashboard.dashboard-overview.view');

  const recentColumns = [
    {
      key: 'name',
      label: 'Tenant',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.tenants.show', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'subdomain',
      label: 'Subdomain',
      render: (row) => <Mono tone="secondary">{row.subdomain}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'created_at',
      label: 'Joined',
      render: (row) => (
        <Mono tone="secondary">
          {row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  const healthEntries = Object.entries(systemHealth ?? {});

  return (
    <DashboardLayout
      title="Platform Dashboard"
      breadcrumb={[{ label: 'Platform Admin' }, { label: 'Dashboard' }]}
      description="Live MRR/ARR snapshot, system health, and recent tenant activity."
    >
      <VStack gap={6}>

        {/* Top KPIs */}
        <HStack gap={4} wrap>
          <KPI
            label="MRR"
            value={`$${Number(stats?.mrr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          />
          <KPI
            label="ARR"
            value={`$${Number(stats?.arr ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          />
          <KPI
            label="Active Tenants"
            value={stats?.active_tenants ?? 0}
          />
          <KPI
            label="Churn Rate (30d)"
            value={`${stats?.churn_rate_pct ?? 0}%`}
            intent={stats?.churn_rate_pct > 5 ? 'danger' : 'success'}
          />
          <KPI
            label="New Tenants (7d)"
            value={stats?.new_tenants_7d ?? 0}
          />
          <KPI
            label="Trial Tenants"
            value={stats?.trial_tenants ?? 0}
            intent="warning"
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
                <KPI
                  label="MRR Growth"
                  value={`${stats?.mrr_growth >= 0 ? '+' : ''}${stats?.mrr_growth ?? 0}%`}
                  intent={stats?.mrr_growth >= 0 ? 'success' : 'danger'}
                />
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* System Health */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>System Health</Eyebrow>
              <HStack gap={4} wrap>
                {healthEntries.map(([service, h]) => (
                  <VStack key={service} gap={1}>
                    <Text tone="secondary" size="sm">{service.charAt(0).toUpperCase() + service.slice(1)}</Text>
                    <Badge intent={HEALTH_INTENT[h?.status] ?? 'neutral'}>
                      {h?.status ?? 'unknown'}
                    </Badge>
                    {h?.latency_ms != null && (
                      <Mono tone="secondary">{h.latency_ms}ms</Mono>
                    )}
                    {h?.pending != null && (
                      <Text tone="secondary" size="sm">pending: {h.pending} / failed: {h.failed}</Text>
                    )}
                    {h?.used_pct != null && (
                      <Text tone="secondary" size="sm">{h.used_pct}% used — {h.free_gb}GB free</Text>
                    )}
                  </VStack>
                ))}
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* Recent Tenants */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2}>
                <Eyebrow>Recent Tenants</Eyebrow>
                <Button
                  intent="ghost"
                  size="sm"
                  onClick={() => router.get(route('platform.admin.tenants.index'))}
                >
                  View All
                </Button>
              </HStack>
              <DataTable
                columns={recentColumns}
                rows={recentTenants ?? []}
                empty="No tenants yet."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </DashboardLayout>
  );
}

DashboardIndex.layout = page => <App title="Platform Dashboard">{page}</App>;
