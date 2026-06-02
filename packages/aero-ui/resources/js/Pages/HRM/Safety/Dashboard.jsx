import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Text, Eyebrow, Badge, Card, CardBody, Alert,
} from '@aero/ui';

function StatCard({ label, value, sub }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={1}>
          <Eyebrow>{label}</Eyebrow>
          <Text size="xl">{value ?? '—'}</Text>
          {sub && <Text tone="secondary" size="sm">{sub}</Text>}
        </VStack>
      </CardBody>
    </Card>
  );
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

const SEVERITY_INTENT = {
  low:      'info',
  medium:   'warning',
  high:     'amber',
  critical: 'danger',
};

export default function SafetyDashboard({ ltifr, openFindings, incidentTrend, totalThisYear }) {
  const canView = useHRMAC('hrm.safety.safety-incidents.view');

  const trendColumns = [
    { key: 'month',    label: 'Month',    render: row => <Text>{row.month ?? '—'}</Text> },
    { key: 'count',    label: 'Incidents', render: row => <Text>{row.count ?? 0}</Text> },
    {
      key: 'severity',
      label: 'Highest Severity',
      render: row => row.highest_severity ? (
        <Badge intent={SEVERITY_INTENT[row.highest_severity] ?? 'neutral'}>
          {row.highest_severity}
        </Badge>
      ) : <Text tone="secondary">—</Text>,
    },
  ];

  if (!canView) {
    return (
      <IndexPageLayout
        title="Safety Dashboard"
        breadcrumb={[{ label: 'HRM' }, { label: 'Safety' }, { label: 'Dashboard' }]}
      >
        <Alert intent="danger" title="Access Denied">
          You do not have permission to view the safety dashboard.
        </Alert>
      </IndexPageLayout>
    );
  }

  return (
    <IndexPageLayout
      title="Safety Dashboard"
      breadcrumb={[{ label: 'HRM' }, { label: 'Safety' }, { label: 'Dashboard' }]}
      actions={
        <HStack gap={2}>
          <Button
            intent="soft"
            onClick={() => router.get(route('hrm.safety.incidents.index'))}
          >
            View Incidents
          </Button>
          <Button
            intent="soft"
            onClick={() => router.get(route('hrm.safety.inspections.index'))}
          >
            View Inspections
          </Button>
        </HStack>
      }
    >
      <VStack gap={6}>
        {/* KPI Cards */}
        <HStack gap={4} wrap>
          <StatCard
            label="LTIFR"
            value={ltifr != null ? Number(ltifr).toFixed(2) : '—'}
            sub="Lost Time Injury Frequency Rate"
          />
          <StatCard
            label="Open Findings"
            value={openFindings ?? 0}
            sub="Unresolved inspection findings"
          />
          <StatCard
            label="Total Incidents This Year"
            value={totalThisYear ?? 0}
            sub="Reported incidents YTD"
          />
          <StatCard
            label="Months Tracked"
            value={(incidentTrend ?? []).length}
            sub="Months with incident data"
          />
        </HStack>

        {/* Trend Table */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Incident Trend</Eyebrow>
              {(incidentTrend ?? []).length === 0 ? (
                <Text tone="secondary">No trend data available.</Text>
              ) : (
                <DataTable
                  columns={trendColumns}
                  rows={incidentTrend}
                  empty="No trend data."
                />
              )}
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </IndexPageLayout>
  );
}

SafetyDashboard.layout = page => <App title="Safety Dashboard">{page}</App>;
