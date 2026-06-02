import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Mono, Badge, Button, Field, Textarea, Alert, Eyebrow,
} from '@aero/ui';

const SEVERITY_INTENT = {
  low:      'info',
  medium:   'warning',
  high:     'amber',
  critical: 'danger',
};

const STATUS_INTENT = {
  open:                  'danger',
  under_investigation:   'warning',
  closed:                'success',
};

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

function typeLabel(type) {
  switch (type) {
    case 'injury':           return 'Injury';
    case 'near_miss':        return 'Near Miss';
    case 'property_damage':  return 'Property Damage';
    default:                 return type ?? '—';
  }
}

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function IncidentsShow({ incident, investigations }) {
  const canUpdate  = useHRMAC('hrm.safety.safety-incidents.update');
  const canResolve = useHRMAC('hrm.safety.safety-incidents.resolve');

  const isClosed = incident.status === 'closed';

  const investigateForm = useForm({
    root_cause:        '',
    corrective_action: '',
  });

  const closeForm = useForm({});

  function submitInvestigation(e) {
    e.preventDefault();
    investigateForm.post(route('hrm.safety.incidents.investigate', incident.id), {
      preserveScroll: true,
      onSuccess: () => investigateForm.reset(),
    });
  }

  function submitClose(e) {
    e.preventDefault();
    closeForm.post(route('hrm.safety.incidents.close', incident.id), {
      preserveScroll: true,
    });
  }

  return (
    <DetailPageLayout
      title={`Incident: ${incident.reference ?? `#${incident.id}`}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Safety' },
        { label: 'Incidents', href: route('hrm.safety.incidents.index') },
        { label: incident.reference ?? `#${incident.id}` },
      ]}
      actions={
        <Button
          intent="soft"
          onClick={() => router.get(route('hrm.safety.incidents.index'))}
        >
          Back to Incidents
        </Button>
      }
    >
      <VStack gap={5}>

        {/* Incident Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Badge intent={SEVERITY_INTENT[incident.severity] ?? 'neutral'}>
                  {incident.severity ?? '—'}
                </Badge>
                <Badge intent={STATUS_INTENT[incident.status] ?? 'neutral'}>
                  {statusLabel(incident.status)}
                </Badge>
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Reference"    value={incident.reference} />
                <InfoRow label="Type"         value={typeLabel(incident.type)} />
                <InfoRow label="Occurred At"  value={fmtDatetime(incident.occurred_at)} />
                <InfoRow label="Location"     value={incident.location} />
                <InfoRow label="Department"   value={incident.department?.name} />
                <InfoRow label="Reported By"  value={incident.reporter?.name} />
              </HStack>

              {incident.description && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Description</Text>
                  <Text tone="secondary">{incident.description}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Investigations */}
        {(investigations ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Investigations</Eyebrow>
                {investigations.map((inv, i) => (
                  <VStack key={inv.id ?? i} gap={2}>
                    <HStack gap={2} align="center">
                      <Mono size="xs" tone="tertiary">{fmtDatetime(inv.created_at)}</Mono>
                      {inv.investigator?.name && (
                        <Text size="sm" tone="secondary">{inv.investigator.name}</Text>
                      )}
                    </HStack>
                    {inv.root_cause && (
                      <VStack gap={0}>
                        <Text size="xs" tone="tertiary">Root Cause</Text>
                        <Text tone="secondary">{inv.root_cause}</Text>
                      </VStack>
                    )}
                    {inv.corrective_action && (
                      <VStack gap={0}>
                        <Text size="xs" tone="tertiary">Corrective Action</Text>
                        <Text tone="secondary">{inv.corrective_action}</Text>
                      </VStack>
                    )}
                  </VStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Add Investigation (update guard, not closed) */}
        {!isClosed && canUpdate && (
          <Card>
            <CardBody>
              <form onSubmit={submitInvestigation}>
                <VStack gap={4}>
                  <Eyebrow>Add Investigation Notes</Eyebrow>
                  {Object.keys(investigateForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}
                  <Field label="Root Cause" error={investigateForm.errors.root_cause} required>
                    <Textarea
                      value={investigateForm.data.root_cause}
                      onChange={e => investigateForm.setData('root_cause', e.target.value)}
                      placeholder="Describe the root cause of this incident..."
                      rows={3}
                    />
                  </Field>
                  <Field label="Corrective Action" error={investigateForm.errors.corrective_action} required>
                    <Textarea
                      value={investigateForm.data.corrective_action}
                      onChange={e => investigateForm.setData('corrective_action', e.target.value)}
                      placeholder="Describe corrective actions taken or planned..."
                      rows={3}
                    />
                  </Field>
                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={investigateForm.processing}>
                      Save Investigation
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

        {/* Close Incident (resolve guard, not closed) */}
        {!isClosed && canResolve && (
          <Card>
            <CardBody>
              <form onSubmit={submitClose}>
                <VStack gap={4}>
                  <Eyebrow>Close Incident</Eyebrow>
                  <Text tone="secondary">
                    Closing this incident will mark it as resolved. This action cannot be undone.
                  </Text>
                  <HStack gap={2}>
                    <Button type="submit" intent="danger" loading={closeForm.processing}>
                      Close Incident
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

IncidentsShow.layout = page => <App title="Incident Detail">{page}</App>;
