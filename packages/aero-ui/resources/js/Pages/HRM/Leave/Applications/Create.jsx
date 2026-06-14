import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select, Textarea, Button, Alert, Text,
} from '@aero/ui';

export default function ApplicationsCreate({ employees, leaveTypes }) {
  const { data, setData, post, processing, errors } = useForm({
    employee_id:     '',
    leave_type_id:   '',
    start_date:      '',
    end_date:        '',
    reason:          '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.leave.applications.store'));
  }

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.name })),
  ];

  const typeOptions = [
    { value: '', label: 'Select leave type' },
    ...(leaveTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  return (
    <FormPageLayout
      title="Apply for Leave"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Leave' },
        { label: 'Applications', href: route('hrm.leave.applications.index') },
        { label: 'New Application' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}

          <Field label="Employee" error={errors.employee_id} required>
            <Select
              value={data.employee_id}
              onChange={e => setData('employee_id', e.target.value)}
            >
              {employeeOptions.map(o => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
          </Field>

          <Field label="Leave Type" error={errors.leave_type_id} required>
            <Select
              value={data.leave_type_id}
              onChange={e => setData('leave_type_id', e.target.value)}
            >
              {typeOptions.map(o => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
          </Field>

          <HStack gap={3}>
            <Field label="Start Date" error={errors.start_date} required>
              <Input
                type="date"
                value={data.start_date}
                onChange={e => setData('start_date', e.target.value)}
              />
            </Field>

            <Field label="End Date" error={errors.end_date} required>
              <Input
                type="date"
                value={data.end_date}
                onChange={e => setData('end_date', e.target.value)}
              />
            </Field>
          </HStack>

          <Field label="Reason" error={errors.reason}>
            <Textarea
              value={data.reason}
              onChange={e => setData('reason', e.target.value)}
              placeholder="Provide a brief reason for your leave request"
              rows={4}
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Submit Application
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.leave.applications.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

ApplicationsCreate.layout = page => <App title="Apply for Leave">{page}</App>;
