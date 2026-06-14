import { router, useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select, Button, Text, Toggle,
} from '@aero/ui';

export default function InterviewsCreate({ application_id: propApplicationId, interviewers, types }) {
  const { ziggy } = usePage().props;
  const queryApplicationId = ziggy?.query?.application_id ?? null;
  const resolvedApplicationId = propApplicationId ?? queryApplicationId ?? '';

  const typeOptions = [
    { value: '', label: 'Select type' },
    ...(types ?? []).map(t => ({ value: t, label: t })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    application_id:    resolvedApplicationId,
    scheduled_at:      '',
    duration_minutes:  '60',
    type:              '',
    location:          '',
    interviewer_ids:   [],
  });

  function toggleInterviewer(id) {
    const ids = data.interviewer_ids.includes(id)
      ? data.interviewer_ids.filter(i => i !== id)
      : [...data.interviewer_ids, id];
    setData('interviewer_ids', ids);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.recruitment.interviews.store'));
  }

  return (
    <FormPageLayout
      title="Schedule Interview"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Recruitment' },
        { label: 'Interviews', href: route('hrm.recruitment.interviews.index') },
        { label: 'Schedule' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {/* Hidden application_id — stored in form data and submitted */}
          {resolvedApplicationId && (
            <input type="hidden" name="application_id" value={resolvedApplicationId} />
          )}

          <HStack gap={4} wrap>
            <Field label="Scheduled At" htmlFor="scheduled_at" error={errors.scheduled_at} required>
              <Input
                id="scheduled_at"
                type="datetime-local"
                value={data.scheduled_at}
                onChange={e => setData('scheduled_at', e.target.value)}
                error={!!errors.scheduled_at}
              />
            </Field>
            <Field label="Duration (minutes)" htmlFor="duration_minutes" error={errors.duration_minutes} required>
              <Input
                id="duration_minutes"
                type="number"
                value={data.duration_minutes}
                onChange={e => setData('duration_minutes', e.target.value)}
                placeholder="60"
                error={!!errors.duration_minutes}
              />
            </Field>
          </HStack>

          <Field label="Interview Type" htmlFor="type" error={errors.type} required>
            <Select
              id="type"
              options={typeOptions}
              value={data.type}
              onChange={e => setData('type', e.target.value)}
            />
          </Field>

          <Field label="Location / Link" htmlFor="location" error={errors.location}>
            <Input
              id="location"
              value={data.location}
              onChange={e => setData('location', e.target.value)}
              placeholder="e.g. Conference Room A or https://meet.example.com/abc"
              error={!!errors.location}
            />
          </Field>

          <Field label="Interviewers" error={errors.interviewer_ids}>
            <VStack gap={2}>
              {(interviewers ?? []).length === 0 && (
                <Text tone="secondary">No interviewers available.</Text>
              )}
              {(interviewers ?? []).map(iv => (
                <Toggle
                  key={iv.id}
                  label={iv.user?.name ?? `Interviewer ${iv.id}`}
                  checked={data.interviewer_ids.includes(iv.id)}
                  onChange={() => toggleInterviewer(iv.id)}
                />
              ))}
            </VStack>
          </Field>

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Schedule Interview
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.recruitment.interviews.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

InterviewsCreate.layout = page => <App title="Schedule Interview">{page}</App>;
