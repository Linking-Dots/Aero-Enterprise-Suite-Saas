import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, Card, CardBody, Field, Input, Button,
  HStack, VStack, Checkbox, Text,
} from '@aero/ui';

export default function EnrollmentPeriodsCreate({ benefits }) {
  const canCreate = useHRMAC('hrm.benefits.enrollment-periods.edit');

  const { data, setData, post, processing, errors } = useForm({
    name:               '',
    starts_at:          '',
    ends_at:            '',
    coverage_starts_at: '',
    coverage_ends_at:   '',
    benefit_ids:        [],
  });

  function toggleBenefit(id) {
    setData(
      'benefit_ids',
      data.benefit_ids.includes(id)
        ? data.benefit_ids.filter(x => x !== id)
        : [...data.benefit_ids, id],
    );
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.benefits.enrollment-periods.store'));
  }

  if (!canCreate) {
    return (
      <FormPageLayout title="New Enrollment Period">
        <Card>
          <CardBody>
            <Text tone="secondary">You do not have permission to create enrollment periods.</Text>
          </CardBody>
        </Card>
      </FormPageLayout>
    );
  }

  return (
    <FormPageLayout
      title="New Enrollment Period"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Benefits' },
        { label: 'Enrollment Periods', href: route('hrm.benefits.enrollment-periods.index') },
        { label: 'New' },
      ]}
    >
      <Card>
        <CardBody>
          <form onSubmit={submit}>
            <VStack gap={4}>
              <Field label="Period Name" error={errors.name} required>
                <Input
                  value={data.name}
                  onChange={e => setData('name', e.target.value)}
                  placeholder="2026 Open Enrollment"
                />
              </Field>

              <HStack gap={4}>
                <Field label="Enrollment Opens" error={errors.starts_at} required>
                  <Input
                    type="date"
                    value={data.starts_at}
                    onChange={e => setData('starts_at', e.target.value)}
                  />
                </Field>
                <Field label="Enrollment Closes" error={errors.ends_at} required>
                  <Input
                    type="date"
                    value={data.ends_at}
                    onChange={e => setData('ends_at', e.target.value)}
                  />
                </Field>
              </HStack>

              <HStack gap={4}>
                <Field label="Coverage Starts" error={errors.coverage_starts_at} required>
                  <Input
                    type="date"
                    value={data.coverage_starts_at}
                    onChange={e => setData('coverage_starts_at', e.target.value)}
                  />
                </Field>
                <Field label="Coverage Ends" error={errors.coverage_ends_at} required>
                  <Input
                    type="date"
                    value={data.coverage_ends_at}
                    onChange={e => setData('coverage_ends_at', e.target.value)}
                  />
                </Field>
              </HStack>

              <Field label="Benefits to Include" error={errors.benefit_ids} required>
                <VStack gap={2}>
                  {(benefits ?? []).map(b => (
                    <HStack key={b.id} gap={2} align="center">
                      <Checkbox
                        checked={data.benefit_ids.includes(b.id)}
                        onChange={() => toggleBenefit(b.id)}
                      />
                      <Text>{b.name} <Text as="span" tone="secondary">({b.category})</Text></Text>
                    </HStack>
                  ))}
                </VStack>
              </Field>

              <HStack gap={3}>
                <Button type="submit" intent="primary" loading={processing}>
                  Create Period
                </Button>
                <Button intent="neutral" onClick={() => router.visit(route('hrm.benefits.enrollment-periods.index'))}>
                  Cancel
                </Button>
              </HStack>
            </VStack>
          </form>
        </CardBody>
      </Card>
    </FormPageLayout>
  );
}

EnrollmentPeriodsCreate.layout = page => <App title="New Enrollment Period">{page}</App>;
