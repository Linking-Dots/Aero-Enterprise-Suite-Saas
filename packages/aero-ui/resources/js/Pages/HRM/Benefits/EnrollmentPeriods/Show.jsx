import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardHeader, CardBody,
  HStack, VStack, Badge, Button, Text, KPI,
} from '@aero/ui';

const STATUS_INTENT = { draft: 'neutral', active: 'success', closed: 'danger' };

export default function EnrollmentPeriodsShow({ period, stats }) {
  const canActivate = useHRMAC('hrm.benefits.enrollment-periods.activate');

  return (
    <DetailPageLayout
      title={period.name}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Benefits' },
        { label: 'Enrollment Periods', href: route('hrm.benefits.enrollment-periods.index') },
        { label: period.name },
      ]}
      status={<Badge intent={STATUS_INTENT[period.status] ?? 'neutral'}>{period.status}</Badge>}
      actions={
        canActivate && period.status === 'draft' && (
          <Button
            intent="primary"
            onClick={() => router.post(route('hrm.benefits.enrollment-periods.activate', period.id))}
          >
            Activate Period
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <Card>
          <CardHeader>
            <Text weight="semibold">Period Details</Text>
          </CardHeader>
          <CardBody>
            <HStack gap={8} wrap>
              <VStack gap={1}>
                <Text tone="secondary" size="sm">Enrollment Window</Text>
                <Text>{period.starts_at} to {period.ends_at}</Text>
              </VStack>
              <VStack gap={1}>
                <Text tone="secondary" size="sm">Coverage Period</Text>
                <Text>{period.coverage_starts_at} to {period.coverage_ends_at}</Text>
              </VStack>
              <VStack gap={1}>
                <Text tone="secondary" size="sm">Created By</Text>
                <Text>{period.creator?.name ?? '—'}</Text>
              </VStack>
            </HStack>
          </CardBody>
        </Card>

        <HStack gap={4} wrap>
          <KPI label="Enrolled"   value={stats.enrolled}  />
          <KPI label="Waived"     value={stats.waived}    />
          <KPI label="Employees"  value={stats.employees} />
        </HStack>

        <Card>
          <CardHeader>
            <Text weight="semibold">Benefits in this Period</Text>
          </CardHeader>
          <CardBody>
            <VStack gap={2}>
              {(period.benefits ?? []).map(b => (
                <HStack key={b.id} gap={2} align="center">
                  <Text>{b.name}</Text>
                  <Badge intent="neutral">{b.category}</Badge>
                </HStack>
              ))}
              {(period.benefits ?? []).length === 0 && (
                <Text tone="secondary">No benefits attached yet.</Text>
              )}
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </DetailPageLayout>
  );
}

EnrollmentPeriodsShow.layout = page => <App title="Enrollment Period">{page}</App>;
