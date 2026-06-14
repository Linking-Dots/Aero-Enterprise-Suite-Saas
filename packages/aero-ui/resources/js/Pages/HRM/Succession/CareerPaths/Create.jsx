import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Textarea, Button, Text, Eyebrow,
} from '@aero/ui';

export default function CareerPathsCreate({ roles }) {
  const roleOptions = [
    { value: '', label: 'Select target role' },
    ...(roles ?? []).map(r => ({ value: String(r.id), label: r.name })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    name:           '',
    description:    '',
    target_role_id: '',
    milestones:     [],
  });

  function addMilestone() {
    setData('milestones', [...data.milestones, { name: '' }]);
  }

  function updateMilestone(index, value) {
    const updated = data.milestones.map((m, i) => i === index ? { ...m, name: value } : m);
    setData('milestones', updated);
  }

  function removeMilestone(index) {
    setData('milestones', data.milestones.filter((_, i) => i !== index));
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.career-paths.store'));
  }

  return (
    <FormPageLayout
      title="New Career Path"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Succession' },
        { label: 'Career Paths', href: route('hrm.career-paths.index') },
        { label: 'New Career Path' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.career-paths.index'))}
          >
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Create Career Path
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Core Details */}
          <VStack gap={4}>
            <Eyebrow>Details</Eyebrow>

            <Field label="Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. Senior Engineer Track"
              />
            </Field>

            <Field label="Target Role" error={errors.target_role_id}>
              <Select
                options={roleOptions}
                value={data.target_role_id}
                onChange={e => setData('target_role_id', e.target.value)}
              />
            </Field>

            <Field label="Description" error={errors.description}>
              <Textarea
                value={data.description}
                onChange={e => setData('description', e.target.value)}
                placeholder="Describe this career path and its goals…"
                rows={3}
              />
            </Field>
          </VStack>

          {/* Milestones */}
          <VStack gap={4}>
            <HStack gap={3} align="center">
              <Eyebrow>Milestones</Eyebrow>
              <Text tone="secondary" size="sm">Define the progression checkpoints</Text>
            </HStack>

            {data.milestones.length === 0 && (
              <Text tone="secondary">No milestones added yet.</Text>
            )}

            {data.milestones.map((milestone, index) => (
              <HStack key={index} gap={3} align="center">
                <Field
                  label={`Milestone ${index + 1}`}
                  error={errors[`milestones.${index}.name`]}
                  required
                >
                  <Input
                    value={milestone.name}
                    onChange={e => updateMilestone(index, e.target.value)}
                    placeholder="e.g. Complete code review training"
                  />
                </Field>
                <Button
                  type="button"
                  intent="danger"
                  size="sm"
                  onClick={() => removeMilestone(index)}
                >
                  Remove
                </Button>
              </HStack>
            ))}

            <Button type="button" intent="soft" size="sm" onClick={addMilestone}>
              Add Milestone
            </Button>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

CareerPathsCreate.layout = page => <App title="New Career Path">{page}</App>;
