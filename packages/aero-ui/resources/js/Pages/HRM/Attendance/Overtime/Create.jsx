import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Textarea, Button, Alert,
} from '@aero/ui';

export default function OvertimeCreate() {
  const { data, setData, post, processing, errors } = useForm({
    work_date: '',
    hours:     '',
    reason:    '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.attendance.overtime.store'));
  }

  return (
    <FormPageLayout
      title="Request Overtime"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Attendance' },
        { label: 'Overtime', href: route('hrm.attendance.overtime.index') },
        { label: 'New Request' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}

          <Field label="Work Date" error={errors.work_date} required>
            <Input
              type="date"
              value={data.work_date}
              onChange={e => setData('work_date', e.target.value)}
            />
          </Field>

          <Field
            label="Hours"
            error={errors.hours}
            hint="Minimum 0.25 hours, maximum 12 hours"
            required
          >
            <Input
              type="number"
              value={data.hours}
              onChange={e => setData('hours', e.target.value)}
              placeholder="e.g. 2.5"
            />
          </Field>

          <Field label="Reason" error={errors.reason}>
            <Textarea
              value={data.reason}
              onChange={e => setData('reason', e.target.value)}
              placeholder="Briefly describe the reason for overtime"
              rows={4}
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Submit Request
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.attendance.overtime.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

OvertimeCreate.layout = page => <App title="Request Overtime">{page}</App>;
