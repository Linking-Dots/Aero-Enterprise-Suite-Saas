import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, Card, CardHeader, CardBody,
  VStack, HStack, Text, Button, Badge, Field, Input, Textarea, Radio,
} from '@aero/ui';

function ElectionCard({ benefit, election, onChange, disabled }) {
  return (
    <Card>
      <CardHeader>
        <HStack gap={4} align="center">
          <VStack gap={1}>
            <Text weight="semibold">{benefit.name}</Text>
            {benefit.description && (
              <Text tone="secondary" size="sm">{benefit.description}</Text>
            )}
          </VStack>
          <Text weight="semibold">
            ${Number(benefit.employee_cost).toFixed(2)}/{benefit.frequency}
          </Text>
        </HStack>
      </CardHeader>
      <CardBody>
        <VStack gap={3}>
          <HStack gap={4}>
            <Radio
              name={`status-${benefit.id}`}
              value="enrolled"
              checked={election.status === 'enrolled'}
              onChange={() => onChange({ status: 'enrolled' })}
              disabled={disabled}
              label="Enroll"
            />
            <Radio
              name={`status-${benefit.id}`}
              value="waived"
              checked={election.status === 'waived'}
              onChange={() => onChange({ status: 'waived' })}
              disabled={disabled}
              label="Waive"
            />
          </HStack>

          {election.status === 'enrolled' && benefit.allows_dependents && (
            <Field label="Number of Dependents">
              <Input
                type="number"
                min="0"
                max="20"
                value={election.dependents_count}
                onChange={e => onChange({ dependents_count: parseInt(e.target.value, 10) || 0 })}
                disabled={disabled}
              />
            </Field>
          )}

          {election.status === 'waived' && (
            <Field label="Waiver Reason">
              <Textarea
                value={election.waiver_reason}
                onChange={e => onChange({ waiver_reason: e.target.value })}
                disabled={disabled}
                rows={2}
              />
            </Field>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function OpenEnrollmentIndex({ period, eligibleBenefits, myElections }) {
  const canEnroll = useHRMAC('hrm.benefits.open-enrollment.edit');

  const { data, setData, post, processing } = useForm({
    period_id: period?.id ?? null,
    elections: (eligibleBenefits ?? []).map(b => {
      const existing = (myElections ?? []).find(e => e.benefit_id === b.id);
      return {
        benefit_id:       b.id,
        status:           existing?.status ?? 'enrolled',
        dependents_count: existing?.dependents_count ?? 0,
        waiver_reason:    existing?.waiver_reason ?? '',
      };
    }),
  });

  function setElection(idx, patch) {
    setData('elections', data.elections.map((e, i) => (i === idx ? { ...e, ...patch } : e)));
  }

  if (!period) {
    return (
      <IndexPageLayout
        title="Open Enrollment"
        breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Open Enrollment' }]}
        table={
          <Card>
            <CardBody>
              <VStack gap={2} align="center">
                <Text size="lg" weight="semibold">No Active Enrollment Period</Text>
                <Text tone="secondary">
                  There is no open enrollment window at this time. Check back later.
                </Text>
              </VStack>
            </CardBody>
          </Card>
        }
      />
    );
  }

  return (
    <IndexPageLayout
      title={period.name}
      breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Open Enrollment' }]}
      filters={
        <Card>
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
            </HStack>
          </CardBody>
        </Card>
      }
      table={
        <VStack gap={4}>
          {(eligibleBenefits ?? []).length === 0 && (
            <Card>
              <CardBody>
                <Text tone="secondary">
                  You are not eligible for any benefits in this enrollment period.
                </Text>
              </CardBody>
            </Card>
          )}

          {(eligibleBenefits ?? []).map((benefit, idx) => (
            <ElectionCard
              key={benefit.id}
              benefit={benefit}
              election={data.elections[idx] ?? { status: 'enrolled', dependents_count: 0, waiver_reason: '' }}
              onChange={patch => setElection(idx, patch)}
              disabled={!canEnroll || processing}
            />
          ))}

          {(eligibleBenefits ?? []).length > 0 && canEnroll && (
            <Button
              intent="primary"
              loading={processing}
              onClick={() => post(route('hrm.benefits.open-enrollment.enroll'))}
            >
              Submit My Elections
            </Button>
          )}
        </VStack>
      }
    />
  );
}

OpenEnrollmentIndex.layout = page => <App title="Open Enrollment">{page}</App>;
