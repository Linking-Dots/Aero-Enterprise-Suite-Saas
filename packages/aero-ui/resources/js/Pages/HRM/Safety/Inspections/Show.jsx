import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Mono, Badge, Button, Field, Input, Select,
  Textarea, Alert, Eyebrow,
} from '@aero/ui';

const SEVERITY_INTENT = {
  low:      'info',
  medium:   'warning',
  high:     'amber',
  critical: 'danger',
};

const FINDING_STATUS_INTENT = {
  open:     'danger',
  resolved: 'success',
};

const SEVERITY_OPTIONS = [
  { value: 'low',      label: 'Low' },
  { value: 'medium',   label: 'Medium' },
  { value: 'high',     label: 'High' },
  { value: 'critical', label: 'Critical' },
];

const CATEGORY_OPTIONS = [
  { value: '',              label: 'Select category' },
  { value: 'fire_safety',   label: 'Fire Safety' },
  { value: 'electrical',    label: 'Electrical' },
  { value: 'ergonomics',    label: 'Ergonomics' },
  { value: 'chemical',      label: 'Chemical / Hazmat' },
  { value: 'housekeeping',  label: 'Housekeeping' },
  { value: 'ppe',           label: 'PPE' },
  { value: 'machinery',     label: 'Machinery' },
  { value: 'other',         label: 'Other' },
];

function statusLabel(s) {
  return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

const EMPTY_FINDING = { category: '', description: '', severity: 'medium', due_date: '' };

export default function InspectionsShow({ inspection, findings }) {
  const canUpdate = useHRMAC('hrm.safety.safety-inspections.update');

  const [newFindings, setNewFindings] = useState([]);

  const submitForm = useForm({
    conducted_date: '',
    notes:          '',
    findings:       [],
  });

  function addFinding() {
    setNewFindings(prev => [...prev, { ...EMPTY_FINDING }]);
  }

  function updateFinding(index, field, value) {
    setNewFindings(prev => prev.map((f, i) => i === index ? { ...f, [field]: value } : f));
  }

  function removeFinding(index) {
    setNewFindings(prev => prev.filter((_, i) => i !== index));
  }

  function submitFindings(e) {
    e.preventDefault();
    submitForm.setData('findings', newFindings);
    // Post with latest findings in the data
    router.post(
      route('hrm.safety.inspections.findings', inspection.id),
      {
        conducted_date: submitForm.data.conducted_date,
        notes:          submitForm.data.notes,
        findings:       newFindings,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          submitForm.reset();
          setNewFindings([]);
        },
      },
    );
  }

  const inspectionStatusIntent = {
    scheduled:   'info',
    in_progress: 'warning',
    completed:   'success',
  };

  return (
    <DetailPageLayout
      title={inspection.title ?? `Inspection #${inspection.id}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Safety' },
        { label: 'Inspections', href: route('hrm.safety.inspections.index') },
        { label: inspection.title ?? `#${inspection.id}` },
      ]}
      actions={
        <Button
          intent="soft"
          onClick={() => router.get(route('hrm.safety.inspections.index'))}
        >
          Back to Inspections
        </Button>
      }
    >
      <VStack gap={5}>

        {/* Inspection Details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Badge intent={inspectionStatusIntent[inspection.status] ?? 'neutral'}>
                  {statusLabel(inspection.status)}
                </Badge>
              </HStack>

              <HStack gap={6} wrap>
                <InfoRow label="Reference"      value={inspection.reference} />
                <InfoRow label="Scheduled Date" value={fmtDate(inspection.scheduled_date)} />
                <InfoRow label="Inspector"      value={inspection.inspector?.name ?? inspection.inspector?.user?.name} />
                <InfoRow label="Department"     value={inspection.department?.name} />
                <InfoRow label="Location"       value={inspection.location} />
                {inspection.conducted_date && (
                  <InfoRow label="Conducted Date" value={fmtDate(inspection.conducted_date)} />
                )}
              </HStack>

              {inspection.notes && (
                <VStack gap={1}>
                  <Text size="xs" tone="tertiary">Notes</Text>
                  <Text tone="secondary">{inspection.notes}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Existing Findings */}
        {(findings ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Findings ({findings.length})</Eyebrow>
                {findings.map((finding, i) => (
                  <VStack key={finding.id ?? i} gap={2}>
                    <HStack gap={2} align="center">
                      <Badge intent={SEVERITY_INTENT[finding.severity] ?? 'neutral'}>
                        {finding.severity ?? '—'}
                      </Badge>
                      <Badge intent={FINDING_STATUS_INTENT[finding.status] ?? 'neutral'}>
                        {statusLabel(finding.status)}
                      </Badge>
                      {finding.category && (
                        <Text size="sm" tone="secondary">{finding.category}</Text>
                      )}
                    </HStack>
                    <Text tone="secondary">{finding.description}</Text>
                    {finding.due_date && (
                      <Mono size="xs" tone="tertiary">Due: {fmtDate(finding.due_date)}</Mono>
                    )}
                  </VStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Submit Findings Form (update guard) */}
        {canUpdate && inspection.status !== 'completed' && (
          <Card>
            <CardBody>
              <form onSubmit={submitFindings}>
                <VStack gap={5}>
                  <Eyebrow>Submit Inspection Findings</Eyebrow>

                  <HStack gap={3} wrap>
                    <Field label="Conducted Date" error={submitForm.errors.conducted_date} required>
                      <Input
                        type="date"
                        value={submitForm.data.conducted_date}
                        onChange={e => submitForm.setData('conducted_date', e.target.value)}
                      />
                    </Field>
                  </HStack>

                  <Field label="Notes" error={submitForm.errors.notes} hint="General inspection notes">
                    <Textarea
                      value={submitForm.data.notes}
                      onChange={e => submitForm.setData('notes', e.target.value)}
                      placeholder="Overall inspection observations..."
                      rows={3}
                    />
                  </Field>

                  {/* Dynamic findings */}
                  <VStack gap={3}>
                    <HStack gap={3} align="center">
                      <Eyebrow>New Findings</Eyebrow>
                    </HStack>

                    {newFindings.length === 0 && (
                      <Text tone="secondary">No findings added yet. Click Add Finding to record an issue.</Text>
                    )}

                    {newFindings.map((finding, index) => (
                      <Card key={index}>
                        <CardBody>
                          <VStack gap={3}>
                            <HStack gap={3} wrap>
                              <Field label="Category" error={submitForm.errors[`findings.${index}.category`]} required>
                                <Select
                                  options={CATEGORY_OPTIONS}
                                  value={finding.category}
                                  onChange={e => updateFinding(index, 'category', e.target.value)}
                                />
                              </Field>

                              <Field label="Severity" error={submitForm.errors[`findings.${index}.severity`]} required>
                                <Select
                                  options={SEVERITY_OPTIONS}
                                  value={finding.severity}
                                  onChange={e => updateFinding(index, 'severity', e.target.value)}
                                />
                              </Field>

                              <Field label="Due Date" error={submitForm.errors[`findings.${index}.due_date`]}>
                                <Input
                                  type="date"
                                  value={finding.due_date}
                                  onChange={e => updateFinding(index, 'due_date', e.target.value)}
                                />
                              </Field>
                            </HStack>

                            <Field label="Description" error={submitForm.errors[`findings.${index}.description`]} required>
                              <Textarea
                                value={finding.description}
                                onChange={e => updateFinding(index, 'description', e.target.value)}
                                placeholder="Describe the finding or hazard observed..."
                                rows={2}
                              />
                            </Field>

                            <HStack>
                              <Button
                                type="button"
                                intent="danger"
                                size="sm"
                                onClick={() => removeFinding(index)}
                              >
                                Remove Finding
                              </Button>
                            </HStack>
                          </VStack>
                        </CardBody>
                      </Card>
                    ))}

                    <Button
                      type="button"
                      intent="soft"
                      size="sm"
                      onClick={addFinding}
                    >
                      Add Finding
                    </Button>
                  </VStack>

                  {Object.keys(submitForm.errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}

                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={submitForm.processing}>
                      Submit Findings
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

InspectionsShow.layout = page => <App title="Inspection Detail">{page}</App>;
