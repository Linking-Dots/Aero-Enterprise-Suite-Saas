import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field,
  Input, Select, Button, Alert, Text,
} from '@aero/ui';

export default function ExitInterviewsCreate({ employees }) {
  const canCreate = useHRMAC('hrm.exit-interviews.exit-interview-list.create');

  const { data, setData, post, processing, errors } = useForm({
    employee_id:    '',
    scheduled_for:  '',
    interviewer_id: '',
  });

  if (!canCreate) {
    return (
      <FormPageLayout
        title="Schedule Exit Interview"
        breadcrumb={[{ label: 'HRM' }, { label: 'Exit Interviews', href: route('hrm.exit-interviews.index') }, { label: 'Schedule' }]}
      >
        <Alert intent="danger" title="Access Denied">
          <Text tone="secondary">You do not have permission to schedule exit interviews.</Text>
        </Alert>
      </FormPageLayout>
    );
  }

  const employeeOptions = [
    { value: '', label: 'Select departing employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.name })),
  ];

  const interviewerOptions = [
    { value: '', label: 'Assign interviewer (optional)' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.name })),
  ];

  function submit(e) {
    e.preventDefault();
    post(route('hrm.exit-interviews.store'));
  }

  return (
    <FormPageLayout
      title="Schedule Exit Interview"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Exit Interviews', href: route('hrm.exit-interviews.index') },
        { label: 'Schedule' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}

          <Field label="Departing Employee" error={errors.employee_id} required>
            <Select
              options={employeeOptions}
              value={data.employee_id}
              onChange={e => setData('employee_id', e.target.value)}
            />
          </Field>

          <Field label="Scheduled Date & Time" error={errors.scheduled_for} required>
            <Input
              type="datetime-local"
              value={data.scheduled_for}
              onChange={e => setData('scheduled_for', e.target.value)}
            />
          </Field>

          <Field label="Interviewer" error={errors.interviewer_id} hint="Optional — leave blank to assign later.">
            <Select
              options={interviewerOptions}
              value={data.interviewer_id}
              onChange={e => setData('interviewer_id', e.target.value)}
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Schedule Interview
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.exit-interviews.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

ExitInterviewsCreate.layout = page => <App title="Schedule Exit Interview">{page}</App>;
