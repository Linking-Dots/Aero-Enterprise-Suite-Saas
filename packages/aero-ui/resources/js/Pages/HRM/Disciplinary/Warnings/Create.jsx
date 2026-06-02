import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field,
  Input, Select, Textarea, Button, Alert, Text,
} from '@aero/ui';

export default function WarningsCreate({ actionTypes, employees }) {
  const canIssue = useHRMAC('hrm.disciplinary.warnings.issue');

  const { data, setData, post, processing, errors } = useForm({
    employee_id:    '',
    action_type_id: '',
    subject:        '',
    body:           '',
  });

  if (!canIssue) {
    return (
      <FormPageLayout
        title="Issue Warning"
        breadcrumb={[{ label: 'HRM' }, { label: 'Disciplinary' }, { label: 'Warnings', href: route('hrm.disciplinary.warnings.index') }, { label: 'Issue Warning' }]}
      >
        <Alert intent="danger" title="Access Denied">
          <Text tone="secondary">You do not have permission to issue warnings.</Text>
        </Alert>
      </FormPageLayout>
    );
  }

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.name })),
  ];

  const actionTypeOptions = [
    { value: '', label: 'None (general warning)' },
    ...(actionTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  function submit(e) {
    e.preventDefault();
    post(route('hrm.disciplinary.warnings.store'));
  }

  return (
    <FormPageLayout
      title="Issue Warning"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Disciplinary' },
        { label: 'Warnings', href: route('hrm.disciplinary.warnings.index') },
        { label: 'Issue Warning' },
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

          <Field label="Action Type" error={errors.action_type_id} hint="Optional — links warning to a configured action type.">
            <Select
              options={actionTypeOptions}
              value={data.action_type_id}
              onChange={e => setData('action_type_id', e.target.value)}
            />
          </Field>

          <Field label="Subject" error={errors.subject} required>
            <Input
              value={data.subject}
              onChange={e => setData('subject', e.target.value)}
              placeholder="Brief subject of the warning"
            />
          </Field>

          <Field label="Warning Details" error={errors.body} required>
            <Textarea
              value={data.body}
              onChange={e => setData('body', e.target.value)}
              placeholder="Full text of the warning notice"
              rows={6}
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Issue Warning
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.disciplinary.warnings.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

WarningsCreate.layout = page => <App title="Issue Warning">{page}</App>;
