import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input,
  Button, Text, Eyebrow, Toggle,
} from '@aero/ui';

export default function AnnouncementsCreate({ departments }) {
  const { data, setData, post, processing, errors } = useForm({
    title:                 '',
    body:                  '',
    is_global:             true,
    target_department_ids: [],
  });

  const departmentOptions = [
    { value: '', label: 'Select department...' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  function toggleDepartment(id) {
    const numId = Number(id);
    const current = data.target_department_ids ?? [];
    if (current.includes(numId)) {
      setData('target_department_ids', current.filter(x => x !== numId));
    } else {
      setData('target_department_ids', [...current, numId]);
    }
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.announcements.store'));
  }

  const selectedDepartments = data.target_department_ids ?? [];

  return (
    <FormPageLayout
      title="New Announcement"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Announcements', href: route('hrm.announcements.index') },
        { label: 'New Announcement' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.announcements.index'))}
          >
            Cancel
          </Button>
          <Button
            type="submit"
            intent="primary"
            loading={processing}
            onClick={submit}
          >
            Post Announcement
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          <VStack gap={4}>
            <Eyebrow>Announcement Details</Eyebrow>

            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="Announcement title"
              />
            </Field>

            <Field label="Body" error={errors.body} required>
              <Input
                value={data.body}
                onChange={e => setData('body', e.target.value)}
                placeholder="Announcement content"
              />
            </Field>

            <Toggle
              label="Global announcement (visible to all employees)"
              checked={data.is_global}
              onChange={e => setData('is_global', e.target.checked)}
            />
          </VStack>

          {!data.is_global && (
            <VStack gap={4}>
              <Eyebrow>Target Departments</Eyebrow>

              <Text tone="secondary">
                Select one or more departments to target with this announcement.
              </Text>

              {errors.target_department_ids && (
                <Text tone="secondary">{errors.target_department_ids}</Text>
              )}

              {(departments ?? []).length === 0 ? (
                <Text tone="secondary">No departments available.</Text>
              ) : (
                <VStack gap={2}>
                  {(departments ?? []).map(dept => (
                    <Toggle
                      key={dept.id}
                      label={dept.name}
                      checked={selectedDepartments.includes(dept.id)}
                      onChange={() => toggleDepartment(dept.id)}
                    />
                  ))}
                </VStack>
              )}
            </VStack>
          )}

        </VStack>
      </form>
    </FormPageLayout>
  );
}

AnnouncementsCreate.layout = page => <App title="New Announcement">{page}</App>;
