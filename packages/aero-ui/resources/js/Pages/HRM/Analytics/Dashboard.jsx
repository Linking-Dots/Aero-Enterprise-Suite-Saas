import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Text, Eyebrow, Badge, Card,
} from '@aero/ui';

function StatCard({ label, value }) {
  return (
    <Card>
      <VStack gap={1}>
        <Eyebrow>{label}</Eyebrow>
        <Text size="xl">{value}</Text>
      </VStack>
    </Card>
  );
}

function ProgressBar({ value, max }) {
  const pct = max > 0 ? Math.round((value / max) * 100) : 0;
  return (
    <HStack gap={2} align="center">
      <div className="aeos-progress-track">
        <div className="aeos-progress-fill" style={{ width: `${pct}%` }} />
      </div>
      <Text>{value}</Text>
    </HStack>
  );
}

export default function AnalyticsDashboard({ kpis, turnover, distribution }) {
  const canView = useHRMAC('hrm.analytics.dashboard.view');

  const femalePct = kpis.genderRatio
    ? Math.round(
        ((kpis.genderRatio.female ?? 0) /
          Math.max(
            (kpis.genderRatio.male ?? 0) +
              (kpis.genderRatio.female ?? 0) +
              (kpis.genderRatio.other ?? 0),
            1,
          )) *
          100,
      )
    : 0;

  const tenureYears = kpis.avgTenureDays
    ? (kpis.avgTenureDays / 365).toFixed(1)
    : '0.0';

  const deptCount = Array.isArray(kpis.byDept) ? kpis.byDept.length : 0;

  const maxHeadcount = Array.isArray(distribution)
    ? Math.max(...distribution.map(d => d.headcount), 1)
    : 1;

  const turnoverColumns = [
    { key: 'month', label: 'Month', render: row => <Text>{row.month}</Text> },
    { key: 'leavers', label: 'Leavers', render: row => <Text>{row.leavers}</Text> },
  ];

  const distColumns = [
    { key: 'name', label: 'Department', render: row => <Text>{row.name}</Text> },
    {
      key: 'headcount',
      label: 'Headcount',
      render: row => <ProgressBar value={row.headcount} max={maxHeadcount} />,
    },
  ];

  return (
    <IndexPageLayout
      title="HR Analytics Dashboard"
      breadcrumb={[{ label: 'HRM' }, { label: 'Analytics' }, { label: 'Dashboard' }]}
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.analytics.attrition'))}
          >
            Attrition
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.analytics.dei'))}
          >
            DEI
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.analytics.pulse-surveys.index'))}
          >
            Pulse Surveys
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.analytics.workforce-planning.index'))}
          >
            Workforce Planning
          </Button>
        </HStack>
      }
      table={
        <VStack gap={6}>
          <HStack gap={4} wrap>
            <StatCard label="Active Headcount" value={kpis.active ?? 0} />
            <StatCard label="Avg Tenure (years)" value={tenureYears} />
            <StatCard label="Female %" value={`${femalePct}%`} />
            <StatCard label="Departments" value={deptCount} />
          </HStack>

          <VStack gap={2}>
            <Eyebrow>Monthly Turnover</Eyebrow>
            {turnover === null ? (
              <Text tone="secondary">Loading...</Text>
            ) : (
              <DataTable
                columns={turnoverColumns}
                rows={turnover ?? []}
                empty="No turnover data available."
              />
            )}
          </VStack>

          <VStack gap={2}>
            <Eyebrow>Department Distribution</Eyebrow>
            {distribution === null ? (
              <Text tone="secondary">Loading...</Text>
            ) : (
              <DataTable
                columns={distColumns}
                rows={distribution ?? []}
                empty="No department data available."
              />
            )}
          </VStack>
        </VStack>
      }
    />
  );
}

AnalyticsDashboard.layout = page => <App title="HR Analytics Dashboard">{page}</App>;
