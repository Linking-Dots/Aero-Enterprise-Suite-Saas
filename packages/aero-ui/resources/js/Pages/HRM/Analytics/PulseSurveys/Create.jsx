import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, Button, HStack, VStack,
  Field, Input, Select, Toggle, Text, Eyebrow, Alert,
} from '@aero/ui';

const QUESTION_TYPE_OPTIONS = [
  { value: 'scale',  label: 'Scale (1–10)' },
  { value: 'text',   label: 'Open Text' },
  { value: 'choice', label: 'Multiple Choice' },
];

export default function PulseSurveysCreate({ departments }) {
  const canCreate = useHRMAC('hrm.analytics.pulse-surveys.create');

  const deptOptions = [
    { value: '', label: 'All Departments' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    title:           '',
    description:     '',
    anonymous:       true,
    closes_at:       '',
    audience_dept_id: '',
    questions:       [],
  });

  function addQuestion() {
    setData('questions', [
      ...data.questions,
      { type: 'scale', text: '' },
    ]);
  }

  function updateQuestion(index, field, value) {
    const updated = data.questions.map((q, i) =>
      i === index ? { ...q, [field]: value } : q,
    );
    setData('questions', updated);
  }

  function removeQuestion(index) {
    setData('questions', data.questions.filter((_, i) => i !== index));
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.analytics.pulse-surveys.store'));
  }

  return (
    <IndexPageLayout
      title="New Pulse Survey"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Analytics' },
        { label: 'Pulse Surveys', href: route('hrm.analytics.pulse-surveys.index') },
        { label: 'Create' },
      ]}
      table={
        <form onSubmit={submit}>
          <VStack gap={5}>
            {errors.general && (
              <Alert intent="danger" title="Error">
                {errors.general}
              </Alert>
            )}

            <Field label="Title" htmlFor="title" error={errors.title} required>
              <Input
                id="title"
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="Survey title"
                error={!!errors.title}
              />
            </Field>

            <Field label="Description" htmlFor="description" error={errors.description} hint="Optional">
              <Input
                id="description"
                value={data.description}
                onChange={e => setData('description', e.target.value)}
                placeholder="Brief description (optional)"
              />
            </Field>

            <Field label="Closes At" htmlFor="closes_at" error={errors.closes_at} required>
              <Input
                id="closes_at"
                type="date"
                value={data.closes_at}
                onChange={e => setData('closes_at', e.target.value)}
                error={!!errors.closes_at}
              />
            </Field>

            <Field label="Audience Department" htmlFor="audience_dept_id" error={errors.audience_dept_id} hint="Optional — leave blank for all">
              <Select
                id="audience_dept_id"
                options={deptOptions}
                value={data.audience_dept_id}
                onChange={e => setData('audience_dept_id', e.target.value)}
              />
            </Field>

            <Field label="Anonymous responses">
              <Toggle
                label="Collect responses anonymously"
                checked={data.anonymous}
                onChange={e => setData('anonymous', e.target.checked)}
              />
            </Field>

            <VStack gap={3}>
              <HStack gap={3} align="center">
                <Eyebrow>Questions</Eyebrow>
                <Button
                  intent="soft"
                  size="sm"
                  type="button"
                  onClick={addQuestion}
                >
                  Add Question
                </Button>
              </HStack>

              {data.questions.length === 0 && (
                <Text tone="secondary">No questions added yet. Click "Add Question" to begin.</Text>
              )}

              {data.questions.map((q, i) => (
                <HStack key={i} gap={3} align="center" wrap>
                  <Field label={`Q${i + 1} Type`} htmlFor={`q-type-${i}`}>
                    <Select
                      id={`q-type-${i}`}
                      options={QUESTION_TYPE_OPTIONS}
                      value={q.type}
                      onChange={e => updateQuestion(i, 'type', e.target.value)}
                    />
                  </Field>
                  <Field label={`Q${i + 1} Text`} htmlFor={`q-text-${i}`}>
                    <Input
                      id={`q-text-${i}`}
                      value={q.text}
                      onChange={e => updateQuestion(i, 'text', e.target.value)}
                      placeholder="Enter question text..."
                    />
                  </Field>
                  <Button
                    intent="danger"
                    size="sm"
                    type="button"
                    onClick={() => removeQuestion(i)}
                  >
                    Remove
                  </Button>
                </HStack>
              ))}
            </VStack>

            <HStack gap={3}>
              <Button
                intent="primary"
                type="submit"
                loading={processing}
                disabled={processing}
              >
                Create Survey
              </Button>
              <Button
                intent="ghost"
                type="button"
                onClick={() => router.get(route('hrm.analytics.pulse-surveys.index'))}
              >
                Cancel
              </Button>
            </HStack>
          </VStack>
        </form>
      }
    />
  );
}

PulseSurveysCreate.layout = page => <App title="New Pulse Survey">{page}</App>;
