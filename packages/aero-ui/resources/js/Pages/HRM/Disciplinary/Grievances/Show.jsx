import { useForm, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Mono, Badge, Button, Field, Select, Textarea, Toggle, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  filed:               'warning',
  under_investigation: 'info',
  resolved:            'success',
  dismissed:           'neutral',
};

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

export default function GrievancesShow({ grievance, timeline, investigators }) {
  const canInvestigate = useHRMAC('hrm.grievances.grievance-list.investigate');

  const isFiledOrInvestigating = ['filed', 'under_investigation'].includes(grievance.status);
  const isUnderInvestigation   = grievance.status === 'under_investigation';

  const assignForm  = useForm({ investigator_id: '' });
  const resolveForm = useForm({ resolution_notes: '', dismiss: false });

  function submitAssign(e) {
    e.preventDefault();
    assignForm.post(route('hrm.grievances.assign', grievance.id), {
      preserveScroll: true,
      onSuccess: () => assignForm.reset(),
    });
  }

  function submitResolve(e) {
    e.preventDefault();
    resolveForm.post(route('hrm.grievances.resolve', grievance.id), {
      preserveScroll: true,
    });
  }

  return (
    <DetailPageLayout
      title={`Grievance: ${grievance.reference ?? `#${grievance.id}`}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Grievances', href: route('hrm.grievances.index') },
        { label: grievance.reference ?? `#${grievance.id}` },
      ]}
      actions={
        <Button
          intent="soft"
          onClick={() => router.get(route('hrm.grievances.index'))}
        >
          Back to Grievances
        </Button>
      }
    >
      <VStack gap={5}>
        {/* Grievance Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Text size="lg">{grievance.subject}</Text>
                <Badge intent={STATUS_INTENT[grievance.status] ?? 'neutral'}>
                  {statusLabel(grievance.status)}
                </Badge>
                {grievance.confidentiality && grievance.confidentiality !== 'standard' && (
                  <Badge intent={grievance.confidentiality === 'anonymous' ? 'danger' : 'warning'}>
                    {grievance.confidentiality}
                  </Badge>
                )}
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Reference"    value={grievance.reference} />
                <InfoRow label="Category"     value={grievance.category} />
                <InfoRow label="Filed By"     value={grievance.filedBy?.name ?? (grievance.confidentiality === 'anonymous' ? 'Anonymous' : '—')} />
                <InfoRow label="Investigator" value={grievance.investigator?.name} />
                <InfoRow label="Filed At"     value={grievance.created_at} />
                {grievance.resolved_at && (
                  <InfoRow label="Resolved At" value={grievance.resolved_at} />
                )}
              </HStack>

              {grievance.description && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Description</Text>
                  <Text tone="secondary">{grievance.description}</Text>
                </VStack>
              )}

              {grievance.resolution_notes && (
                <Alert intent="info" title="Resolution Notes">
                  {grievance.resolution_notes}
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

        {/* Assign Investigator (filed or under_investigation + canInvestigate) */}
        {isFiledOrInvestigating && canInvestigate && (
          <Card>
            <CardBody>
              <form onSubmit={submitAssign}>
                <VStack gap={4}>
                  <Text size="sm" tone="secondary">Assign Investigator</Text>
                  {Object.keys(assignForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}
                  <Field label="Investigator" error={assignForm.errors.investigator_id} required>
                    <Select
                      options={[
                        { value: '', label: 'Select investigator' },
                        ...(investigators ?? []).map(u => ({ value: String(u.id), label: u.name })),
                      ]}
                      value={String(assignForm.data.investigator_id ?? '')}
                      onChange={e => assignForm.setData('investigator_id', e.target.value)}
                    />
                  </Field>
                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={assignForm.processing}>
                      Assign Investigator
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

        {/* Resolve Form (under_investigation + canInvestigate) */}
        {isUnderInvestigation && canInvestigate && (
          <Card>
            <CardBody>
              <form onSubmit={submitResolve}>
                <VStack gap={4}>
                  <Text size="sm" tone="secondary">Resolve Grievance</Text>
                  {Object.keys(resolveForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}
                  <Field label="Resolution Notes" error={resolveForm.errors.resolution_notes} required>
                    <Textarea
                      value={resolveForm.data.resolution_notes}
                      onChange={e => resolveForm.setData('resolution_notes', e.target.value)}
                      placeholder="Summary of findings and resolution"
                      rows={4}
                    />
                  </Field>
                  <Toggle
                    label="Dismiss this grievance (no further action)"
                    checked={resolveForm.data.dismiss}
                    onChange={checked => resolveForm.setData('dismiss', checked)}
                  />
                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={resolveForm.processing}>
                      {resolveForm.data.dismiss ? 'Dismiss Grievance' : 'Resolve Grievance'}
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

GrievancesShow.layout = page => <App title="Grievance">{page}</App>;
