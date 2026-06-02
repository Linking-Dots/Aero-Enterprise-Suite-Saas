import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Button, Eyebrow,
} from '@aero/ui';

export default function InspectionsCreate({ departments, inspectors }) {
  const departmentOptions = [
    { value: '', label: 'Select department (optional)' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  const inspectorOptions = [
    { value: '', label: 'Select inspector' },
    ...(inspectors ?? []).map(i => ({
      value: String(i.id),
      label: i.name ?? i.user?.name ?? `#${i.id}`,
    })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    title:          '',
    scheduled_date: '',
    department_id:  '',
    location:       '',
    inspector_id:   '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.safety.inspections.store'));
  }

  return (
    <FormPageLayout
      title="Create Inspection"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Safety' },
        { label: 'Inspections', href: route('hrm.safety.inspections.index') },
        { label: 'Create Inspection' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.safety.inspections.index'))}
          >
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Create Inspection
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Details */}
          <VStack gap={4}>
            <Eyebrow>Inspection Details</Eyebrow>

            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="e.g. Quarterly Fire Safety Inspection"
              />
            </Field>

            <HStack gap={3} wrap>
              <Field label="Scheduled Date" error={errors.scheduled_date} required>
                <Input
                  type="date"
                  value={data.scheduled_date}
                  onChange={e => setData('scheduled_date', e.target.value)}
                />
              </Field>

              <Field label="Inspector" error={errors.inspector_id} required>
                <Select
                  options={inspectorOptions}
                  value={data.inspector_id}
                  onChange={e => setData('inspector_id', e.target.value)}
                />
              </Field>
            </HStack>
          </VStack>

          {/* Location */}
          <VStack gap={4}>
            <Eyebrow>Location</Eyebrow>

            <HStack gap={3} wrap>
              <Field label="Location" error={errors.location} hint="Optional — area or facility being inspected">
                <Input
                  value={data.location}
                  onChange={e => setData('location', e.target.value)}
                  placeholder="e.g. Production Floor, Building A"
                />
              </Field>

              <Field label="Department" error={errors.department_id} hint="Optional">
                <Select
                  options={departmentOptions}
                  value={data.department_id}
                  onChange={e => setData('department_id', e.target.value)}
                />
              </Field>
            </HStack>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

InspectionsCreate.layout = page => <App title="Create Inspection">{page}</App>;
