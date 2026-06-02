import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select, Toggle, Button, Text, Checkbox,
} from '@aero/ui';

export default function CyclesCreate({ templates, employees }) {
  const { data, setData, post, processing, errors } = useForm({
    name:         '',
    template_id:  '',
    starts_on:    '',
    ends_on:      '',
    employee_ids: [],
    activate_now: false,
  });

  function toggleEmployee(id) {
    const ids = data.employee_ids.includes(id)
      ? data.employee_ids.filter(e => e !== id)
      : [...data.employee_ids, id];
    setData('employee_ids', ids);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.performance.cycles.store'));
  }

  const templateOptions = [
    { value: '', label: 'Select a template' },
    ...templates.map(t => ({ value: String(t.id), label: t.name })),
  ];

  return (
    <FormPageLayout
      title="New Performance Cycle"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Performance' },
        { label: 'Cycles', href: route('hrm.performance.cycles.index') },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          <Field label="Cycle Name" htmlFor="name" error={errors.name} required>
            <Input
              id="name"
              value={data.name}
              onChange={e => setData('name', e.target.value)}
              placeholder="e.g. H1 2026 Performance Review"
              error={!!errors.name}
            />
          </Field>

          <Field label="Template" htmlFor="template_id" error={errors.template_id} required>
            <Select
              id="template_id"
              options={templateOptions}
              value={data.template_id}
              onChange={e => setData('template_id', e.target.value)}
            />
          </Field>

          <HStack gap={4} wrap>
            <Field label="Start Date" htmlFor="starts_on" error={errors.starts_on} required>
              <Input
                id="starts_on"
                type="date"
                value={data.starts_on}
                onChange={e => setData('starts_on', e.target.value)}
                error={!!errors.starts_on}
              />
            </Field>
            <Field label="End Date" htmlFor="ends_on" error={errors.ends_on} required>
              <Input
                id="ends_on"
                type="date"
                value={data.ends_on}
                onChange={e => setData('ends_on', e.target.value)}
                error={!!errors.ends_on}
              />
            </Field>
          </HStack>

          <Field label="Participants" error={errors.employee_ids}>
            <VStack gap={2}>
              {employees.length === 0 && (
                <Text tone="secondary">No employees available.</Text>
              )}
              {employees.map(emp => (
                <Checkbox
                  key={emp.id}
                  label={`${emp.first_name} ${emp.last_name}`}
                  checked={data.employee_ids.includes(emp.id)}
                  onChange={() => toggleEmployee(emp.id)}
                />
              ))}
            </VStack>
          </Field>

          <Toggle
            label="Activate immediately after creating"
            checked={data.activate_now}
            onChange={v => setData('activate_now', v)}
          />

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Create Cycle
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.performance.cycles.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

CyclesCreate.layout = page => <App title="New Performance Cycle">{page}</App>;
