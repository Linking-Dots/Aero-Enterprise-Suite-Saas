import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, Card, CardBody, Field, Input, Select, Textarea,
  Toggle, Button, HStack, VStack, Text,
} from '@aero/ui';

const FREQ_OPTIONS = ['monthly', 'biweekly', 'weekly', 'annual'].map(f => ({
  value: f,
  label: f.charAt(0).toUpperCase() + f.slice(1),
}));

export default function CatalogCreate({ categories }) {
  const canEdit = useHRMAC('hrm.benefits.benefit-catalog.edit');

  const categoryOptions = (categories ?? []).map(c => ({
    value: c,
    label: c.charAt(0).toUpperCase() + c.slice(1),
  }));

  const { data, setData, post, processing, errors } = useForm({
    code:              '',
    name:              '',
    category:          '',
    description:       '',
    provider:          '',
    employee_cost:     '',
    employer_cost:     '',
    frequency:         'monthly',
    allows_dependents: false,
    dependent_cost:    '',
    active:            true,
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.benefits.catalog.store'));
  }

  if (!canEdit) {
    return (
      <FormPageLayout title="New Benefit">
        <Card>
          <CardBody>
            <Text tone="secondary">You do not have permission to create benefits.</Text>
          </CardBody>
        </Card>
      </FormPageLayout>
    );
  }

  return (
    <FormPageLayout
      title="New Benefit"
      breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Catalog', href: route('hrm.benefits.catalog.index') }, { label: 'New' }]}
    >
      <Card>
        <CardBody>
          <form onSubmit={submit}>
            <VStack gap={4}>
              <HStack gap={4}>
                <Field label="Code" error={errors.code} required>
                  <Input
                    value={data.code}
                    onChange={e => setData('code', e.target.value)}
                    placeholder="HEALTH_PPO"
                  />
                </Field>
                <Field label="Name" error={errors.name} required>
                  <Input
                    value={data.name}
                    onChange={e => setData('name', e.target.value)}
                    placeholder="Health PPO Plan"
                  />
                </Field>
              </HStack>

              <HStack gap={4}>
                <Field label="Category" error={errors.category} required>
                  <Select
                    options={categoryOptions}
                    value={data.category}
                    onChange={e => setData('category', e.target.value)}
                  />
                </Field>
                <Field label="Frequency" error={errors.frequency} required>
                  <Select
                    options={FREQ_OPTIONS}
                    value={data.frequency}
                    onChange={e => setData('frequency', e.target.value)}
                  />
                </Field>
              </HStack>

              <Field label="Description" error={errors.description}>
                <Textarea
                  value={data.description}
                  onChange={e => setData('description', e.target.value)}
                  rows={3}
                />
              </Field>

              <Field label="Provider" error={errors.provider}>
                <Input
                  value={data.provider}
                  onChange={e => setData('provider', e.target.value)}
                />
              </Field>

              <HStack gap={4}>
                <Field label="Employee Cost" error={errors.employee_cost} required>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.employee_cost}
                    onChange={e => setData('employee_cost', e.target.value)}
                  />
                </Field>
                <Field label="Employer Cost" error={errors.employer_cost} required>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.employer_cost}
                    onChange={e => setData('employer_cost', e.target.value)}
                  />
                </Field>
              </HStack>

              <Field label="Allows Dependents">
                <Toggle
                  checked={data.allows_dependents}
                  onChange={e => setData('allows_dependents', e.target.checked)}
                />
              </Field>

              {data.allows_dependents && (
                <Field label="Dependent Cost (per dependent)" error={errors.dependent_cost}>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.dependent_cost}
                    onChange={e => setData('dependent_cost', e.target.value)}
                  />
                </Field>
              )}

              <Field label="Active">
                <Toggle
                  checked={data.active}
                  onChange={e => setData('active', e.target.checked)}
                />
              </Field>

              <HStack gap={3}>
                <Button type="submit" intent="primary" loading={processing}>
                  Create Benefit
                </Button>
                <Button intent="neutral" onClick={() => router.visit(route('hrm.benefits.catalog.index'))}>
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

CatalogCreate.layout = page => <App title="New Benefit">{page}</App>;
