import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  Badge,
  Select,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const MONTHS_OPTIONS = [
  { value: '3', label: '3 months' },
  { value: '6', label: '6 months' },
  { value: '12', label: '12 months' },
];

function retentionIntent(pct) {
  if (pct >= 80) return 'success';
  if (pct >= 60) return 'primary';
  if (pct >= 40) return 'warning';
  return 'danger';
}

export default function Cohorts({ matrix, months }) {
  function setMonths(m) {
    router.get(
      route('platform.admin.product-analytics.cohorts'),
      { months: m },
      { preserveState: true, preserveScroll: true }
    );
  }

  const numMonths = Number(months ?? 6);

  return (
    <IndexPageLayout
      title="Cohort Retention"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Product Analytics' },
        { label: 'Cohort Retention' },
      ]}
      description="Retention heatmap showing how tenant cohorts retain over time."
      actions={
        <Select
          value={String(numMonths)}
          onChange={e => setMonths(e.target.value)}
          options={MONTHS_OPTIONS}
        />
      }
    >
      <VStack gap={6}>
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Cohort Retention ({numMonths} months)</Eyebrow>
              <div className="aeos-overflow-x-auto">
                <table className="aeos-cohort-table">
                  <thead>
                    <tr>
                      <th className="aeos-cohort-th">Cohort</th>
                      <th className="aeos-cohort-th">Size</th>
                      {Array.from({ length: numMonths }, (_, i) => (
                        <th key={i} className="aeos-cohort-th">M{i}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {(matrix ?? []).length === 0 ? (
                      <tr>
                        <td className="aeos-cohort-td" colSpan={numMonths + 2}>
                          <Text tone="secondary">No cohort data available.</Text>
                        </td>
                      </tr>
                    ) : (
                      (matrix ?? []).map(row => (
                        <tr key={row.cohort}>
                          <td className="aeos-cohort-td"><Mono>{row.cohort}</Mono></td>
                          <td className="aeos-cohort-td"><Mono>{row.size}</Mono></td>
                          {(row.months ?? []).map(m => (
                            <td key={m.month} className="aeos-cohort-td">
                              <Badge intent={retentionIntent(m.pct)}>
                                {m.pct}%
                              </Badge>
                            </td>
                          ))}
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </VStack>
          </CardBody>
        </Card>

        {/* Legend */}
        <Card>
          <CardBody>
            <VStack gap={2}>
              <Eyebrow>Retention Legend</Eyebrow>
              <HStack gap={3} wrap>
                <HStack gap={1}>
                  <Badge intent="success">80%+</Badge>
                  <Text tone="secondary">High retention</Text>
                </HStack>
                <HStack gap={1}>
                  <Badge intent="primary">60–79%</Badge>
                  <Text tone="secondary">Good retention</Text>
                </HStack>
                <HStack gap={1}>
                  <Badge intent="warning">40–59%</Badge>
                  <Text tone="secondary">At risk</Text>
                </HStack>
                <HStack gap={1}>
                  <Badge intent="danger">Below 40%</Badge>
                  <Text tone="secondary">High churn</Text>
                </HStack>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

Cohorts.layout = page => <App title="Cohort Retention">{page}</App>;
