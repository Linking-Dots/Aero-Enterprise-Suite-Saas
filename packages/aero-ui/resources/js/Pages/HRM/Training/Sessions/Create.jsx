import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input,
  Textarea, Button,
} from '@aero/ui';

export default function SessionsCreate({ course }) {
  const { data, setData, post, processing, errors } = useForm({
    starts_at:    '',
    ends_at:      '',
    location:     '',
    meeting_link: '',
    capacity:     '20',
    notes:        '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.training.sessions.store', course.id));
  }

  return (
    <FormPageLayout
      title="Schedule Session"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Training' },
        { label: 'Courses', href: route('hrm.training.courses.index') },
        { label: course.title, href: route('hrm.training.courses.show', course.id) },
        { label: 'Schedule Session' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.training.courses.show', course.id))}
          >
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Schedule
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={4}>
          <HStack gap={3}>
            <Field label="Starts At" error={errors.starts_at} required>
              <Input
                type="datetime-local"
                value={data.starts_at}
                onChange={e => setData('starts_at', e.target.value)}
              />
            </Field>

            <Field label="Ends At" error={errors.ends_at} required>
              <Input
                type="datetime-local"
                value={data.ends_at}
                onChange={e => setData('ends_at', e.target.value)}
              />
            </Field>

            <Field label="Capacity" error={errors.capacity} required>
              <Input
                type="number"
                value={data.capacity}
                onChange={e => setData('capacity', e.target.value)}
                placeholder="20"
              />
            </Field>
          </HStack>

          <HStack gap={3}>
            <Field label="Location" error={errors.location} hint="Optional — leave blank for online-only">
              <Input
                value={data.location}
                onChange={e => setData('location', e.target.value)}
                placeholder="e.g. Conference Room A"
              />
            </Field>

            <Field label="Meeting Link" error={errors.meeting_link} hint="Optional — for virtual sessions">
              <Input
                type="url"
                value={data.meeting_link}
                onChange={e => setData('meeting_link', e.target.value)}
                placeholder="https://meet.example.com/..."
              />
            </Field>
          </HStack>

          <Field label="Notes" error={errors.notes} hint="Optional — internal notes for facilitators">
            <Textarea
              value={data.notes}
              onChange={e => setData('notes', e.target.value)}
              placeholder="Any special instructions or reminders..."
              rows={4}
            />
          </Field>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

SessionsCreate.layout = page => <App title="Schedule Session">{page}</App>;
