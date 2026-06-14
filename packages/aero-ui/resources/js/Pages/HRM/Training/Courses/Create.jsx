import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Textarea, Toggle, Button, Text, Eyebrow,
} from '@aero/ui';

const DELIVERY_MODES = [
  { value: '',           label: 'Select mode' },
  { value: 'in_person',  label: 'In Person' },
  { value: 'virtual',    label: 'Virtual' },
  { value: 'self_paced', label: 'Self-Paced' },
];

const MATERIAL_KIND_OPTIONS = [
  { value: 'link', label: 'Link' },
  { value: 'file', label: 'File' },
];

export default function CoursesCreate({ categories }) {
  const categoryOptions = [
    { value: '',  label: 'Select category' },
    ...(categories ?? []).map(c => ({ value: String(c.id), label: c.name })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    title:            '',
    category_id:      '',
    summary:          '',
    delivery_mode:    '',
    duration_minutes: '60',
    is_mandatory:     false,
    is_active:        true,
    materials:        [],
  });

  function addMaterial() {
    setData('materials', [...data.materials, { kind: 'link', label: '', url: '' }]);
  }

  function updateMaterial(index, field, value) {
    const updated = data.materials.map((m, i) => i === index ? { ...m, [field]: value } : m);
    setData('materials', updated);
  }

  function removeMaterial(index) {
    setData('materials', data.materials.filter((_, i) => i !== index));
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.training.courses.store'));
  }

  return (
    <FormPageLayout
      title="New Course"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Training' },
        { label: 'Courses', href: route('hrm.training.courses.index') },
        { label: 'New Course' },
      ]}
      actions={
        <HStack gap={2}>
          <Button type="button" intent="ghost" onClick={() => router.get(route('hrm.training.courses.index'))}>
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Create Course
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Basics */}
          <VStack gap={4}>
            <Eyebrow>Basics</Eyebrow>

            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="e.g. React Fundamentals"
              />
            </Field>

            <HStack gap={3}>
              <Field label="Category" error={errors.category_id}>
                <Select
                  options={categoryOptions}
                  value={data.category_id}
                  onChange={e => setData('category_id', e.target.value)}
                />
              </Field>

              <Field label="Delivery Mode" error={errors.delivery_mode} required>
                <Select
                  options={DELIVERY_MODES}
                  value={data.delivery_mode}
                  onChange={e => setData('delivery_mode', e.target.value)}
                />
              </Field>

              <Field label="Duration (minutes)" error={errors.duration_minutes} required>
                <Input
                  type="number"
                  value={data.duration_minutes}
                  onChange={e => setData('duration_minutes', e.target.value)}
                  placeholder="60"
                />
              </Field>
            </HStack>

            <Field label="Summary" error={errors.summary} hint="Optional — brief description shown in listings">
              <Textarea
                value={data.summary}
                onChange={e => setData('summary', e.target.value)}
                placeholder="What will participants learn?"
                rows={3}
              />
            </Field>
          </VStack>

          {/* Settings */}
          <VStack gap={4}>
            <Eyebrow>Settings</Eyebrow>

            <HStack gap={4} wrap>
              <Toggle
                label="Mandatory"
                checked={data.is_mandatory}
                onChange={e => setData('is_mandatory', e.target.checked)}
              />
              <Toggle
                label="Active"
                checked={data.is_active}
                onChange={e => setData('is_active', e.target.checked)}
              />
            </HStack>
          </VStack>

          {/* Materials */}
          <VStack gap={4}>
            <HStack gap={3} align="center">
              <Eyebrow>Materials</Eyebrow>
              <Text tone="secondary" size="sm">URL-based materials only (file upload not included)</Text>
            </HStack>

            {data.materials.length === 0 && (
              <Text tone="secondary">No materials added yet.</Text>
            )}

            {data.materials.map((mat, index) => (
              <HStack key={index} gap={3} align="center">
                <Field label="Kind" error={errors[`materials.${index}.kind`]}>
                  <Select
                    options={MATERIAL_KIND_OPTIONS}
                    value={mat.kind}
                    onChange={e => updateMaterial(index, 'kind', e.target.value)}
                  />
                </Field>

                <Field label="Label" error={errors[`materials.${index}.label`]} required>
                  <Input
                    value={mat.label}
                    onChange={e => updateMaterial(index, 'label', e.target.value)}
                    placeholder="e.g. Slides PDF"
                  />
                </Field>

                {mat.kind === 'link' && (
                  <Field label="URL" error={errors[`materials.${index}.url`]}>
                    <Input
                      type="url"
                      value={mat.url}
                      onChange={e => updateMaterial(index, 'url', e.target.value)}
                      placeholder="https://..."
                    />
                  </Field>
                )}

                <Button
                  type="button"
                  intent="danger"
                  size="sm"
                  onClick={() => removeMaterial(index)}
                >
                  Remove
                </Button>
              </HStack>
            ))}

            <Button type="button" intent="soft" size="sm" onClick={addMaterial}>
              Add Material
            </Button>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

CoursesCreate.layout = page => <App title="New Course">{page}</App>;
