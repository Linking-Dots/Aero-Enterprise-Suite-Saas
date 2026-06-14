import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Badge, Button, Field, Textarea, Toggle, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  scheduled: 'warning',
  completed: 'success',
  cancelled: 'neutral',
};

function statusLabel(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function ExitInterviewsShow({ interview, questionnaire }) {
  const canUpdate = useHRMAC('hrm.exit-interviews.exit-interview-list.update');

  const isScheduled = interview.status === 'scheduled';

  const recordForm = useForm({
    summary:            '',
    eligible_for_rehire: false,
    responses:          '',
  });

  function submitRecord(e) {
    e.preventDefault();
    recordForm.post(route('hrm.exit-interviews.record', interview.id), {
      preserveScroll: true,
    });
  }

  return (
    <DetailPageLayout
      title="Exit Interview"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Exit Interviews', href: route('hrm.exit-interviews.index') },
        { label: interview.employee?.name ?? `Interview #${interview.id}` },
      ]}
      actions={
        <Button
          intent="soft"
          onClick={() => router.get(route('hrm.exit-interviews.index'))}
        >
          Back to List
        </Button>
      }
    >
      <VStack gap={5}>
        {/* Interview Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Text size="lg">{interview.employee?.name}</Text>
                <Badge intent={STATUS_INTENT[interview.status] ?? 'neutral'}>
                  {statusLabel(interview.status)}
                </Badge>
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Employee"      value={interview.employee?.name} />
                <InfoRow label="Scheduled For" value={interview.scheduled_for} />
                <InfoRow label="Interviewer"   value={interview.interviewer?.name} />
                <InfoRow label="Created At"    value={interview.created_at} />
              </HStack>

              {interview.summary && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Summary</Text>
                  <Text tone="secondary">{interview.summary}</Text>
                </VStack>
              )}

              {interview.eligible_for_rehire !== null && interview.eligible_for_rehire !== undefined && (
                <HStack gap={2} align="center">
                  <Text size="xs" tone="tertiary">Eligible for Rehire:</Text>
                  <Badge intent={interview.eligible_for_rehire ? 'success' : 'danger'}>
                    {interview.eligible_for_rehire ? 'Yes' : 'No'}
                  </Badge>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Questionnaire (if provided) */}
        {questionnaire && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Text size="sm" tone="secondary">Questionnaire</Text>
                {Array.isArray(questionnaire)
                  ? questionnaire.map((item, idx) => (
                      <VStack key={idx} gap={1}>
                        <Text size="sm">{item.question ?? `Q${idx + 1}`}</Text>
                        <Text tone="secondary">{item.answer ?? '—'}</Text>
                      </VStack>
                    ))
                  : <Text tone="secondary">{JSON.stringify(questionnaire)}</Text>
                }
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Record Responses (if scheduled and user has update) */}
        {isScheduled && canUpdate && (
          <Card>
            <CardBody>
              <form onSubmit={submitRecord}>
                <VStack gap={4}>
                  <Text size="sm" tone="secondary">Record Interview Responses</Text>
                  {Object.keys(recordForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}

                  <Field label="Summary" error={recordForm.errors.summary} required>
                    <Textarea
                      value={recordForm.data.summary}
                      onChange={e => recordForm.setData('summary', e.target.value)}
                      placeholder="Overall summary of the exit interview"
                      rows={4}
                    />
                  </Field>

                  <Toggle
                    label="Eligible for Rehire"
                    checked={recordForm.data.eligible_for_rehire}
                    onChange={checked => recordForm.setData('eligible_for_rehire', checked)}
                  />

                  <Field
                    label="Responses"
                    error={recordForm.errors.responses}
                    hint="Record additional responses or questionnaire answers."
                  >
                    <Textarea
                      value={recordForm.data.responses}
                      onChange={e => recordForm.setData('responses', e.target.value)}
                      placeholder="Additional responses and notes from the interview"
                      rows={5}
                    />
                  </Field>

                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={recordForm.processing}>
                      Complete Interview
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}
      </VStack>
    </DetailPageLayout>
  );
}

ExitInterviewsShow.layout = page => <App title="Exit Interview">{page}</App>;
