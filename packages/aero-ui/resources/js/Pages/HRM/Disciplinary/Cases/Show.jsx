import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Mono, Badge, Button, Field, Select, Textarea, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  open:               'warning',
  awaiting_response:  'primary',
  under_review:       'info',
  closed:             'success',
};

const OUTCOME_OPTIONS = [
  { value: '',           label: 'Select outcome' },
  { value: 'resolved',   label: 'Resolved' },
  { value: 'dismissed',  label: 'Dismissed' },
  { value: 'escalated',  label: 'Escalated' },
  { value: 'no_action',  label: 'No Action Taken' },
];

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function CasesShow({ case: caseData, timeline, documents }) {
  const canUpdate = useHRMAC('hrm.disciplinary.disciplinary-cases.update');
  const canClose  = useHRMAC('hrm.disciplinary.disciplinary-cases.close');

  const isClosed = caseData.status === 'closed';

  const responseForm = useForm({ response: '' });
  const closeForm    = useForm({ outcome: '', closure_notes: '' });

  function submitResponse(e) {
    e.preventDefault();
    responseForm.post(route('hrm.disciplinary.cases.respond', caseData.id), {
      preserveScroll: true,
      onSuccess: () => responseForm.reset(),
    });
  }

  function submitClose(e) {
    e.preventDefault();
    closeForm.post(route('hrm.disciplinary.cases.close', caseData.id), {
      preserveScroll: true,
    });
  }

  return (
    <DetailPageLayout
      title={`Case: ${caseData.reference ?? `#${caseData.id}`}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Disciplinary' },
        { label: 'Cases', href: route('hrm.disciplinary.cases.index') },
        { label: caseData.reference ?? `#${caseData.id}` },
      ]}
      actions={
        <Button
          intent="soft"
          onClick={() => router.get(route('hrm.disciplinary.cases.index'))}
        >
          Back to Cases
        </Button>
      }
    >
      <VStack gap={5}>
        {/* Case Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Text size="lg">{caseData.subject}</Text>
                <Badge intent={STATUS_INTENT[caseData.status] ?? 'neutral'}>
                  {statusLabel(caseData.status)}
                </Badge>
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Reference"    value={caseData.reference} />
                <InfoRow label="Employee"     value={caseData.employee?.name} />
                <InfoRow label="Action Type"  value={caseData.actionType?.name} />
                <InfoRow label="Incident Date" value={caseData.incident_date} />
                <InfoRow label="Opened On"    value={caseData.created_at} />
                {caseData.closed_at && (
                  <InfoRow label="Closed On"  value={caseData.closed_at} />
                )}
              </HStack>

              {caseData.description && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Description</Text>
                  <Text tone="secondary">{caseData.description}</Text>
                </VStack>
              )}

              {caseData.outcome && (
                <Alert intent="info" title={`Outcome: ${statusLabel(caseData.outcome)}`}>
                  {caseData.closure_notes}
                </Alert>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Timeline */}
        {(timeline ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Text size="sm" tone="secondary">Activity Timeline</Text>
                {timeline.map((event) => (
                  <VStack key={event.id} gap={1}>
                    <HStack gap={2} align="center">
                      <Mono size="xs" tone="tertiary">{event.created_at}</Mono>
                      <Badge intent="neutral">{event.type ?? 'event'}</Badge>
                      <Text size="sm" tone="secondary">{event.actor?.name ?? 'System'}</Text>
                    </HStack>
                    {event.notes && <Text tone="secondary">{event.notes}</Text>}
                  </VStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Documents */}
        {(documents ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Text size="sm" tone="secondary">Documents</Text>
                {documents.map((doc) => (
                  <HStack key={doc.id} gap={3} align="center">
                    <Text>{doc.name ?? doc.filename}</Text>
                    <Text size="xs" tone="tertiary">
                      Uploaded by {doc.uploader?.name ?? '—'} on {doc.created_at}
                    </Text>
                  </HStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Record Response (if not closed and user has update) */}
        {!isClosed && canUpdate && (
          <Card>
            <CardBody>
              <form onSubmit={submitResponse}>
                <VStack gap={4}>
                  <Text size="sm" tone="secondary">Record Response</Text>
                  {Object.keys(responseForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}
                  <Field label="Response / Notes" error={responseForm.errors.response} required>
                    <Textarea
                      value={responseForm.data.response}
                      onChange={e => responseForm.setData('response', e.target.value)}
                      placeholder="Record the employee's response or investigation notes"
                      rows={4}
                    />
                  </Field>
                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={responseForm.processing}>
                      Submit Response
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

        {/* Close Case (if not closed and user has close) */}
        {!isClosed && canClose && (
          <Card>
            <CardBody>
              <form onSubmit={submitClose}>
                <VStack gap={4}>
                  <Text size="sm" tone="secondary">Close Case</Text>
                  {Object.keys(closeForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}
                  <Field label="Outcome" error={closeForm.errors.outcome} required>
                    <Select
                      options={OUTCOME_OPTIONS}
                      value={closeForm.data.outcome}
                      onChange={e => closeForm.setData('outcome', e.target.value)}
                    />
                  </Field>
                  <Field label="Closure Notes" error={closeForm.errors.closure_notes}>
                    <Textarea
                      value={closeForm.data.closure_notes}
                      onChange={e => closeForm.setData('closure_notes', e.target.value)}
                      placeholder="Summary of findings and actions taken"
                      rows={4}
                    />
                  </Field>
                  <HStack gap={2}>
                    <Button type="submit" intent="danger" loading={closeForm.processing}>
                      Close Case
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

CasesShow.layout = page => <App title="Disciplinary Case">{page}</App>;
