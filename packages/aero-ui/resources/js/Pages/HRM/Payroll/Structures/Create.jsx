import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Field, Input, Button, Badge, Text, Card,
} from '@aero/ui';

export default function StructuresCreate({ components }) {
  const { data, setData, post, processing, errors } = useForm({
    name:          '',
    basic:         '',
    component_ids: [],
  });

  function toggleComponent(id) {
    setData('component_ids', data.component_ids.includes(id)
      ? data.component_ids.filter(c => c !== id)
      : [...data.component_ids, id],
    );
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.payroll.structures.store'));
  }

  return (
    <>
      <form onSubmit={submit}>
        <VStack gap={6}>
          <div className="payroll-page-header">
            <HStack gap={2} align="center">
              <Box grow>
                <Text size="lg">New Payroll Structure</Text>
              </Box>
              <HStack gap={2}>
                <Button type="submit" intent="primary" loading={processing}>
                  Create Structure
                </Button>
                <Button
                  type="button"
                  intent="ghost"
                  onClick={() => router.get(route('hrm.payroll.structures.index'))}
                >
                  Cancel
                </Button>
              </HStack>
            </HStack>
          </div>

          <HStack gap={4}>
            <Field label="Structure Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. Standard Monthly"
              />
            </Field>

            <Field label="Basic Salary" error={errors.basic} required>
              <Input
                type="number"
                value={String(data.basic)}
                onChange={e => setData('basic', e.target.value)}
                placeholder="0.00"
                leftIcon="currencyDollar"
              />
            </Field>
          </HStack>

          <Field
            label="Pay Components"
            error={errors.component_ids}
            hint={`${data.component_ids.length} selected`}
          >
            <Card>
              <div className="payroll-component-grid">
                {(components ?? []).map(comp => (
                  <label
                    key={comp.id}
                    className={`payroll-component-item${data.component_ids.includes(comp.id) ? ' selected' : ''}`}
                  >
                    <input
                      type="checkbox"
                      checked={data.component_ids.includes(comp.id)}
                      onChange={() => toggleComponent(comp.id)}
                      className="w-4 h-4 flex-shrink-0 accent-aeos-primary"
                    />
                    <Box grow>
                      <VStack gap={1}>
                        <Text size="sm">{comp.name}</Text>
                        <HStack gap={1} align="center">
                          <Badge intent={comp.kind === 'earning' ? 'success' : 'danger'}>
                            {comp.kind}
                          </Badge>
                          <Text tone="secondary" size="xs">{comp.code}</Text>
                        </HStack>
                      </VStack>
                    </Box>
                  </label>
                ))}
                {(components ?? []).length === 0 && (
                  <Text tone="secondary">No pay components available. Create components first.</Text>
                )}
              </div>
            </Card>
          </Field>
        </VStack>
      </form>
    </>
  );
}

StructuresCreate.layout = page => <App title="New Payroll Structure">{page}</App>;
