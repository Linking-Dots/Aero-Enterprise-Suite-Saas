import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Textarea, Button, Eyebrow,
} from '@aero/ui';

const TYPE_OPTIONS = [
  { value: '',                label: 'Select type' },
  { value: 'injury',          label: 'Injury' },
  { value: 'near_miss',       label: 'Near Miss' },
  { value: 'property_damage', label: 'Property Damage' },
];

const SEVERITY_OPTIONS = [
  { value: '',         label: 'Select severity' },
  { value: 'low',      label: 'Low' },
  { value: 'medium',   label: 'Medium' },
  { value: 'high',     label: 'High' },
  { value: 'critical', label: 'Critical' },
];

export default function IncidentsCreate({ departments }) {
  const departmentOptions = [
    { value: '', label: 'Select department (optional)' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    occurred_at:    '',
    type:           '',
    severity:       '',
    description:    '',
    department_id:  '',
    location:       '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.safety.incidents.store'));
  }

  return (
    <FormPageLayout
      title="Report Incident"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Safety' },
        { label: 'Incidents', href: route('hrm.safety.incidents.index') },
        { label: 'Report Incident' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.safety.incidents.index'))}
          >
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Submit Report
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Incident Details */}
          <VStack gap={4}>
            <Eyebrow>Incident Details</Eyebrow>

            <HStack gap={3} wrap>
              <Field label="Occurred At" error={errors.occurred_at} required>
                <Input
                  type="datetime-local"
                  value={data.occurred_at}
                  onChange={e => setData('occurred_at', e.target.value)}
                />
              </Field>

              <Field label="Type" error={errors.type} required>
                <Select
                  options={TYPE_OPTIONS}
                  value={data.type}
                  onChange={e => setData('type', e.target.value)}
                />
              </Field>

              <Field label="Severity" error={errors.severity} required>
                <Select
                  options={SEVERITY_OPTIONS}
                  value={data.severity}
                  onChange={e => setData('severity', e.target.value)}
                />
              </Field>
            </HStack>

            <Field label="Description" error={errors.description} required>
              <Textarea
                value={data.description}
                onChange={e => setData('description', e.target.value)}
                placeholder="Describe what happened in detail..."
                rows={5}
              />
            </Field>
          </VStack>

          {/* Location & Department */}
          <VStack gap={4}>
            <Eyebrow>Location</Eyebrow>

            <HStack gap={3} wrap>
              <Field label="Location" error={errors.location} hint="Optional — specific location where it occurred">
                <Input
                  value={data.location}
                  onChange={e => setData('location', e.target.value)}
                  placeholder="e.g. Warehouse B, Floor 2"
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

IncidentsCreate.layout = page => <App title="Report Incident">{page}</App>;
