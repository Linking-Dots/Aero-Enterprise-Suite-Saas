import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select, Button, Textarea,
} from '@aero/ui';

export default function JobsCreate({ departments, statuses, types }) {
  const { data, setData, post, processing, errors } = useForm({
    title:            '',
    department_id:    '',
    type:             '',
    location:         '',
    description:      '',
    salary_min:       '',
    salary_max:       '',
    salary_currency:  'USD',
    positions:        '1',
    status:           'draft',
    posting_date:     '',
    closing_date:     '',
  });

  const deptOptions = [
    { value: '', label: 'Select department' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];
  const typeOptions = [
    { value: '', label: 'Select type' },
    ...(types ?? []).map(t => ({ value: t, label: t })),
  ];
  const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'open',  label: 'Open' },
    ...(statuses ?? [])
      .filter(s => s !== 'draft' && s !== 'open')
      .map(s => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) })),
  ];

  function submit(e) {
    e.preventDefault();
    post(route('hrm.recruitment.jobs.store'));
  }

  return (
    <FormPageLayout
      title="New Job Posting"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Recruitment' },
        { label: 'Jobs', href: route('hrm.recruitment.jobs.index') },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          <Field label="Job Title" htmlFor="title" error={errors.title} required>
            <Input
              id="title"
              value={data.title}
              onChange={e => setData('title', e.target.value)}
              placeholder="e.g. Senior Backend Engineer"
              error={!!errors.title}
            />
          </Field>

          <HStack gap={4} wrap>
            <Field label="Department" htmlFor="department_id" error={errors.department_id} required>
              <Select
                id="department_id"
                options={deptOptions}
                value={data.department_id}
                onChange={e => setData('department_id', e.target.value)}
              />
            </Field>
            <Field label="Employment Type" htmlFor="type" error={errors.type} required>
              <Select
                id="type"
                options={typeOptions}
                value={data.type}
                onChange={e => setData('type', e.target.value)}
              />
            </Field>
          </HStack>

          <Field label="Location" htmlFor="location" error={errors.location}>
            <Input
              id="location"
              value={data.location}
              onChange={e => setData('location', e.target.value)}
              placeholder="e.g. Remote, New York, NY"
              error={!!errors.location}
            />
          </Field>

          <Field label="Description" htmlFor="description" error={errors.description} required>
            <Textarea
              id="description"
              value={data.description}
              onChange={e => setData('description', e.target.value)}
              placeholder="Job description, responsibilities, requirements..."
              error={!!errors.description}
            />
          </Field>

          <HStack gap={4} wrap>
            <Field label="Salary Min" htmlFor="salary_min" error={errors.salary_min}>
              <Input
                id="salary_min"
                type="number"
                value={data.salary_min}
                onChange={e => setData('salary_min', e.target.value)}
                placeholder="0"
                error={!!errors.salary_min}
              />
            </Field>
            <Field label="Salary Max" htmlFor="salary_max" error={errors.salary_max}>
              <Input
                id="salary_max"
                type="number"
                value={data.salary_max}
                onChange={e => setData('salary_max', e.target.value)}
                placeholder="0"
                error={!!errors.salary_max}
              />
            </Field>
            <Field label="Currency" htmlFor="salary_currency" error={errors.salary_currency}>
              <Input
                id="salary_currency"
                value={data.salary_currency}
                onChange={e => setData('salary_currency', e.target.value)}
                placeholder="USD"
                error={!!errors.salary_currency}
              />
            </Field>
          </HStack>

          <HStack gap={4} wrap>
            <Field label="Open Positions" htmlFor="positions" error={errors.positions} required>
              <Input
                id="positions"
                type="number"
                value={data.positions}
                onChange={e => setData('positions', e.target.value)}
                placeholder="1"
                error={!!errors.positions}
              />
            </Field>
            <Field label="Status" htmlFor="status" error={errors.status} required>
              <Select
                id="status"
                options={statusOptions}
                value={data.status}
                onChange={e => setData('status', e.target.value)}
              />
            </Field>
          </HStack>

          <HStack gap={4} wrap>
            <Field label="Posting Date" htmlFor="posting_date" error={errors.posting_date} required>
              <Input
                id="posting_date"
                type="date"
                value={data.posting_date}
                onChange={e => setData('posting_date', e.target.value)}
                error={!!errors.posting_date}
              />
            </Field>
            <Field label="Closing Date" htmlFor="closing_date" error={errors.closing_date}>
              <Input
                id="closing_date"
                type="date"
                value={data.closing_date}
                onChange={e => setData('closing_date', e.target.value)}
                error={!!errors.closing_date}
              />
            </Field>
          </HStack>

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Create Job
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.recruitment.jobs.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

JobsCreate.layout = page => <App title="New Job Posting">{page}</App>;
