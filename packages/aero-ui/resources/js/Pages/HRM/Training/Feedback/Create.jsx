import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Select,
  Textarea, Toggle, Button, Text, Eyebrow, Card, CardBody,
} from '@aero/ui';

const RATING_OPTIONS = [
  { value: '',  label: 'Select rating' },
  { value: '1', label: '1 — Poor' },
  { value: '2', label: '2 — Below Average' },
  { value: '3', label: '3 — Average' },
  { value: '4', label: '4 — Good' },
  { value: '5', label: '5 — Excellent' },
];

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

export default function FeedbackCreate({ enrollment }) {
  const { data, setData, post, processing, errors } = useForm({
    content_rating:    '',
    instructor_rating: '',
    overall_rating:    '',
    would_recommend:   true,
    what_worked:       '',
    what_didnt_work:   '',
    suggestions:       '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.training.feedback.store', enrollment.id));
  }

  return (
    <FormPageLayout
      title="Training Feedback"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Training' },
        { label: 'Feedback' },
      ]}
      actions={
        <HStack gap={2}>
          <Button type="submit" intent="primary" loading={processing} onClick={submit}>
            Submit Feedback
          </Button>
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Read-only session info */}
          <Card>
            <CardBody>
              <VStack gap={2}>
                <Eyebrow>Training Session</Eyebrow>
                <HStack gap={4}>
                  <VStack gap={1}>
                    <Text tone="secondary">Course</Text>
                    <Text>{enrollment.session?.course?.title ?? '—'}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Session Date</Text>
                    <Text>{fmtDatetime(enrollment.session?.starts_at)}</Text>
                  </VStack>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          {/* Ratings */}
          <VStack gap={4}>
            <Eyebrow>Ratings</Eyebrow>

            <HStack gap={3}>
              <Field label="Content Quality" error={errors.content_rating} required>
                <Select
                  options={RATING_OPTIONS}
                  value={data.content_rating}
                  onChange={e => setData('content_rating', e.target.value)}
                />
              </Field>

              <Field label="Instructor Quality" error={errors.instructor_rating} required>
                <Select
                  options={RATING_OPTIONS}
                  value={data.instructor_rating}
                  onChange={e => setData('instructor_rating', e.target.value)}
                />
              </Field>

              <Field label="Overall Experience" error={errors.overall_rating} required>
                <Select
                  options={RATING_OPTIONS}
                  value={data.overall_rating}
                  onChange={e => setData('overall_rating', e.target.value)}
                />
              </Field>
            </HStack>

            <Toggle
              label="Would you recommend this training?"
              checked={data.would_recommend}
              onChange={e => setData('would_recommend', e.target.checked)}
            />
          </VStack>

          {/* Open-ended */}
          <VStack gap={4}>
            <Eyebrow>Your Thoughts</Eyebrow>

            <Field label="What worked well?" error={errors.what_worked} hint="Optional">
              <Textarea
                value={data.what_worked}
                onChange={e => setData('what_worked', e.target.value)}
                placeholder="Highlights, standout moments, effective exercises..."
                rows={3}
              />
            </Field>

            <Field label="What could be improved?" error={errors.what_didnt_work} hint="Optional">
              <Textarea
                value={data.what_didnt_work}
                onChange={e => setData('what_didnt_work', e.target.value)}
                placeholder="Gaps, pacing issues, unclear content..."
                rows={3}
              />
            </Field>

            <Field label="Suggestions" error={errors.suggestions} hint="Optional — ideas for future sessions">
              <Textarea
                value={data.suggestions}
                onChange={e => setData('suggestions', e.target.value)}
                placeholder="Topics to cover, tools to use, format changes..."
                rows={3}
              />
            </Field>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

FeedbackCreate.layout = page => <App title="Training Feedback">{page}</App>;
