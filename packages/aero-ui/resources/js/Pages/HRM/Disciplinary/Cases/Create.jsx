import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field, Input,
  Select, Textarea, Button, Alert, Text,
} from '@aero/ui';

export default function CasesCreate({ actionTypes, employees }) {
  const canCreate = useHRMAC('hrm.disciplinary.disciplinary-cases.create');

  const { data, setData, post, processing, errors } = useForm({
    employee_id:    '',
    action_type_id: '',
    incident_date:  '',
    subject:        '',
    description:    '',
  });

  if (!canCreate) {
    return (
      <FormPageLayout
        title="New Disciplinary Case"
        breadcrumb={[{ label: 'HRM' }, { label: 'Disciplinary' }, { label: 'Cases', href: route('hrm.disciplinary.cases.index') }, { label: 'New' }]}
      >
        <Alert intent="danger" title="Access Denied">
          <Text tone="secondary">You do not have permission to create disciplinary cases.</Text>
        </Alert>
      </FormPageLayout>
    );
  }

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.name })),
  ];

  const actionTypeOptions = [
    { value: '', label: 'Select action type' },
    ...(actionTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  function submit(e) {
    e.preventDefault();
    post(route('hrm.disciplinary.cases.store'));
  }

  return (
    <FormPageLayout
      title="New Disciplinary Case"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Disciplinary' },
        { label: 'Cases', href: route('hrm.disciplinary.cases.index') },
        { label: 'New Case' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}

          <Field label="Employee" error={errors.employee_id} required>
            <Select
              options={employeeOptions}
              value={data.employee_id}
              onChange={e => setData('employee_id', e.target.value)}
            />
          </Field>

          <Field label="Action Type" error={errors.action_type_id} required>
            <Select
              options={actionTypeOptions}
              value={data.action_type_id}
              onChange={e => setData('action_type_id', e.target.value)}
            />
          </Field>

          <Field label="Incident Date" error={errors.incident_date} required>
            <Input
              type="date"
              value={data.incident_date}
              onChange={e => setData('incident_date', e.target.value)}
            />
          </Field>

          <Field label="Subject" error={errors.subject} required>
            <Input
              value={data.subject}
              onChange={e => setData('subject', e.target.value)}
              placeholder="Brief subject of the case"
            />
          </Field>

          <Field label="Description" error={errors.description} required>
            <Textarea
              value={data.description}
              onChange={e => setData('description', e.target.value)}
              placeholder="Detailed description of the incident"
              rows={5}
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Open Case
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.disciplinary.cases.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

CasesCreate.layout = page => <App title="New Disciplinary Case">{page}</App>;
