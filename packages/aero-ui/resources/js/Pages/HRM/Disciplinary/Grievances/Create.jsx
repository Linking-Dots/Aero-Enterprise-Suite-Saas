import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field,
  Input, Select, Textarea, Button, Alert, Text,
} from '@aero/ui';

const CONFIDENTIALITY_OPTIONS = [
  { value: 'standard',      label: 'Standard' },
  { value: 'confidential',  label: 'Confidential' },
  { value: 'anonymous',     label: 'Anonymous' },
];

export default function GrievancesCreate({ categories }) {
  const canUpdate = useHRMAC('hrm.grievances.grievance-list.update');

  const { data, setData, post, processing, errors } = useForm({
    category:              '',
    subject:               '',
    description:           '',
    confidentiality:       'standard',
    against_employee_id:   '',
  });

  if (!canUpdate) {
    return (
      <FormPageLayout
        title="File Grievance"
        breadcrumb={[{ label: 'HRM' }, { label: 'Grievances', href: route('hrm.grievances.index') }, { label: 'File Grievance' }]}
      >
        <Alert intent="danger" title="Access Denied">
          <Text tone="secondary">You do not have permission to file grievances.</Text>
        </Alert>
      </FormPageLayout>
    );
  }

  const categoryOptions = [
    { value: '', label: 'Select category' },
    ...(categories ?? []).map(c =>
      typeof c === 'string'
        ? { value: c, label: c.charAt(0).toUpperCase() + c.slice(1) }
        : { value: String(c.id ?? c.value), label: c.name ?? c.label }
    ),
  ];

  function submit(e) {
    e.preventDefault();
    post(route('hrm.grievances.store'));
  }

  return (
    <FormPageLayout
      title="File Grievance"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Grievances', href: route('hrm.grievances.index') },
        { label: 'File Grievance' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {Object.keys(errors).length > 0 && (
            <Alert intent="danger" title="Please fix the errors below." />
          )}

          <Field label="Category" error={errors.category} required>
            <Select
              options={categoryOptions}
              value={data.category}
              onChange={e => setData('category', e.target.value)}
            />
          </Field>

          <Field label="Subject" error={errors.subject} required>
            <Input
              value={data.subject}
              onChange={e => setData('subject', e.target.value)}
              placeholder="Brief subject of the grievance"
            />
          </Field>

          <Field label="Description" error={errors.description} required>
            <Textarea
              value={data.description}
              onChange={e => setData('description', e.target.value)}
              placeholder="Detailed description of the grievance"
              rows={5}
            />
          </Field>

          <Field
            label="Confidentiality"
            error={errors.confidentiality}
            hint="'Anonymous' removes the filer's identity from all communications."
          >
            <Select
              options={CONFIDENTIALITY_OPTIONS}
              value={data.confidentiality}
              onChange={e => setData('confidentiality', e.target.value)}
            />
          </Field>

          <Field
            label="Against Employee (optional)"
            error={errors.against_employee_id}
            hint="Enter the employee ID if this grievance is filed against a specific person."
          >
            <Input
              type="number"
              value={data.against_employee_id}
              onChange={e => setData('against_employee_id', e.target.value)}
              placeholder="Employee ID (optional)"
            />
          </Field>

          <HStack gap={2}>
            <Button type="submit" intent="primary" loading={processing}>
              Submit Grievance
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.grievances.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

GrievancesCreate.layout = page => <App title="File Grievance">{page}</App>;
