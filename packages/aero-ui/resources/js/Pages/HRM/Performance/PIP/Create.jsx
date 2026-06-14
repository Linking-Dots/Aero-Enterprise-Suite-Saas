import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Textarea, Select, Button,
} from '@aero/ui';

export default function PIPCreate({ employees }) {
  const { data, setData, post, processing, errors } = useForm({
    employee_id: '',
    title:       '',
    objectives:  '',
    start_date:  '',
    end_date:    '',
    notes:       '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.performance.pip.store'));
  }

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({
      value: String(e.id),
      label: e.user?.name
        ? `${e.user.name} (${e.employee_code})`
        : `#${e.employee_code}`,
    })),
  ];

  return (
    <FormPageLayout
      title="New Performance Improvement Plan"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Performance' },
        { label: 'PIPs', href: route('hrm.performance.pip.index') },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          <Field label="Employee" htmlFor="employee_id" error={errors.employee_id} required>
            <Select
              id="employee_id"
              options={employeeOptions}
              value={data.employee_id}
              onChange={e => setData('employee_id', e.target.value)}
            />
          </Field>

          <Field label="Title" htmlFor="title" error={errors.title} required>
            <Input
              id="title"
              value={data.title}
              onChange={e => setData('title', e.target.value)}
              placeholder="Brief descriptive title for this PIP"
              error={!!errors.title}
            />
          </Field>

          <Field label="Objectives" htmlFor="objectives" error={errors.objectives} required hint="List the specific improvements expected">
            <Textarea
              id="objectives"
              value={data.objectives}
              onChange={e => setData('objectives', e.target.value)}
              placeholder="Define clear, measurable objectives..."
            />
          </Field>

          <HStack gap={4} wrap>
            <Field label="Start Date" htmlFor="start_date" error={errors.start_date} required>
              <Input
                id="start_date"
                type="date"
                value={data.start_date}
                onChange={e => setData('start_date', e.target.value)}
                error={!!errors.start_date}
              />
            </Field>
            <Field label="End Date" htmlFor="end_date" error={errors.end_date} required>
              <Input
                id="end_date"
                type="date"
                value={data.end_date}
                onChange={e => setData('end_date', e.target.value)}
                error={!!errors.end_date}
              />
            </Field>
          </HStack>

          <Field label="Notes" htmlFor="notes" error={errors.notes} hint="Optional additional context or support resources">
            <Textarea
              id="notes"
              value={data.notes}
              onChange={e => setData('notes', e.target.value)}
              placeholder="Support plan, check-in schedule, resources..."
            />
          </Field>

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Create PIP
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.performance.pip.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

PIPCreate.layout = page => <App title="New PIP">{page}</App>;
