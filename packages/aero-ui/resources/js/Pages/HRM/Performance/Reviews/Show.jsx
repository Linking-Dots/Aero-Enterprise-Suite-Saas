import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@aero/ui';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, Field, Input, Textarea,
  Card, CardBody, Select, Modal, Alert,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'self_submitted':    return 'warning';
    case 'manager_submitted': return 'primary';
    case 'finalized':         return 'success';
    default:                  return 'neutral';
  }
}

function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function RatingOptions({ min = 1, max = 5 }) {
  const options = [];
  for (let i = min; i <= max; i++) {
    options.push({ value: String(i), label: String(i) });
  }
  return options;
}

export default function ReviewsShow({ review, template }) {
  const canFinalize = useHRMAC('hrm.performance.appraisal-cycles.update');
  const isFinalized = review.status === 'finalized';
  const ratingMin   = template?.rating_scale?.min ?? 1;
  const ratingMax   = template?.rating_scale?.max ?? 5;

  // Self-assessment answers keyed by question index "section-question"
  const [selfAnswers, setSelfAnswers] = useState(() => {
    const init = {};
    review.self_answers?.forEach((a, i) => { init[i] = a; });
    return init;
  });

  // Manager assessment answers
  const [managerAnswers, setManagerAnswers] = useState(() => {
    const init = {};
    review.manager_answers?.forEach((a, i) => { init[i] = a; });
    return init;
  });

  const [finalRating,  setFinalRating]  = useState(review.final_rating  ?? '');
  const [finalComment, setFinalComment] = useState(review.final_comment ?? '');
  const [confirmOpen,  setConfirmOpen]  = useState(false);
  const [submitting,   setSubmitting]   = useState(false);

  function buildAnswerArray(answers, sections) {
    const flat = [];
    sections.forEach((section, si) => {
      section.questions.forEach((_, qi) => {
        const key = `${si}-${qi}`;
        flat.push(answers[key] ?? '');
      });
    });
    return flat;
  }

  function submitSelf() {
    setSubmitting(true);
    router.post(
      route('hrm.performance.reviews.self', review.id),
      { answers: buildAnswerArray(selfAnswers, template?.sections ?? []) },
      { preserveState: true, preserveScroll: true, onFinish: () => setSubmitting(false) },
    );
  }

  function submitManager() {
    setSubmitting(true);
    router.post(
      route('hrm.performance.reviews.manager', review.id),
      { answers: buildAnswerArray(managerAnswers, template?.sections ?? []) },
      { preserveState: true, preserveScroll: true, onFinish: () => setSubmitting(false) },
    );
  }

  function finalize() {
    setSubmitting(true);
    router.post(
      route('hrm.performance.reviews.finalize', review.id),
      { final_rating: finalRating, final_comment: finalComment },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { setSubmitting(false); setConfirmOpen(false); },
      },
    );
  }

  const ratingOptions = [
    { value: '', label: 'Select rating' },
    ...RatingOptions({ min: ratingMin, max: ratingMax }),
  ];

  function renderQuestions(sections, answers, setAnswers, readOnly) {
    return sections.map((section, si) => (
      <Card key={si}>
        <CardBody>
          <VStack gap={4}>
            <Eyebrow>{section.title}</Eyebrow>
            {section.questions.map((q, qi) => {
              const key = `${si}-${qi}`;
              return (
                <Field key={key} label={q.q}>
                  {q.type === 'rating' ? (
                    <Select
                      options={ratingOptions}
                      value={answers[key] ?? ''}
                      onChange={e => setAnswers(prev => ({ ...prev, [key]: e.target.value }))}
                      disabled={readOnly}
                    />
                  ) : (
                    <Textarea
                      value={answers[key] ?? ''}
                      onChange={e => setAnswers(prev => ({ ...prev, [key]: e.target.value }))}
                      placeholder="Your response..."
                      disabled={readOnly}
                    />
                  )}
                </Field>
              );
            })}
          </VStack>
        </CardBody>
      </Card>
    ));
  }

  const sections = template?.sections ?? [];

  return (
    <>
      <VStack gap={6}>
        {/* Header */}
        <div className="flex items-center justify-between pb-5 border-b border-aeos-divider">
          <HStack gap={2} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>Performance Review</Eyebrow>
                <HStack gap={2} align="center">
                  <Text size="lg">
                    {review.employee?.first_name} {review.employee?.last_name}
                  </Text>
                  <Badge intent={statusIntent(review.status)}>
                    {statusLabel(review.status)}
                  </Badge>
                </HStack>
                <Text tone="secondary">{review.cycle?.name}</Text>
              </VStack>
            </Box>
            <Button
              intent="ghost"
              leftIcon="arrowLeft"
              onClick={() => router.get(route('hrm.performance.reviews.index'))}
            >
              Back
            </Button>
          </HStack>
        </div>

        {isFinalized && (
          <Alert intent="success" title="This review has been finalized.">
            Finalized on {review.finalized_at}. Final rating: {review.final_rating}
          </Alert>
        )}

        {/* Self-assessment */}
        <VStack gap={3}>
          <Eyebrow>Self Assessment</Eyebrow>
          {renderQuestions(sections, selfAnswers, setSelfAnswers, isFinalized)}
          {!isFinalized && (
            <HStack gap={2}>
              <Button intent="primary" loading={submitting} onClick={submitSelf}>
                Submit Self Assessment
              </Button>
            </HStack>
          )}
        </VStack>

        {/* Manager assessment */}
        <VStack gap={3}>
          <Eyebrow>Manager Assessment</Eyebrow>
          {renderQuestions(sections, managerAnswers, setManagerAnswers, isFinalized)}
          {!isFinalized && (
            <HStack gap={2}>
              <Button intent="soft" loading={submitting} onClick={submitManager}>
                Submit Manager Assessment
              </Button>
            </HStack>
          )}
        </VStack>

        {/* Finalize section */}
        {canFinalize && !isFinalized && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Finalize Review</Eyebrow>
                <Field label="Final Rating" htmlFor="final_rating">
                  <Input
                    id="final_rating"
                    type="number"
                    value={finalRating}
                    onChange={e => setFinalRating(e.target.value)}
                    placeholder="1.0 – 5.0"
                  />
                </Field>
                <Field label="Final Comment" htmlFor="final_comment">
                  <Textarea
                    id="final_comment"
                    value={finalComment}
                    onChange={e => setFinalComment(e.target.value)}
                    placeholder="Overall assessment comments..."
                  />
                </Field>
                <Button intent="danger" onClick={() => setConfirmOpen(true)}>
                  Finalize Review
                </Button>
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Read-only final rating when finalized */}
        {isFinalized && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Final Assessment</Eyebrow>
                <HStack gap={4}>
                  <VStack gap={1}>
                    <Text tone="secondary">Final Rating</Text>
                    <Text size="lg">{review.final_rating}</Text>
                  </VStack>
                </HStack>
                <Field label="Final Comment">
                  <Textarea value={review.final_comment ?? ''} disabled />
                </Field>
              </VStack>
            </CardBody>
          </Card>
        )}
      </VStack>

      {/* Finalize confirm modal */}
      <Modal
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Finalize Performance Review"
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={submitting} onClick={finalize}>
              Confirm Finalize
            </Button>
            <Button intent="ghost" onClick={() => setConfirmOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text>This will permanently lock this review.</Text>
          <Text tone="secondary">
            Once finalized, no further changes can be made to the self or manager assessments.
          </Text>
        </VStack>
      </Modal>
    </>
  );
}

ReviewsShow.layout = page => <App title="Performance Review">{page}</App>;
