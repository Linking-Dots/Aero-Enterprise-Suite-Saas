import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, Field, Select,
  Textarea, Card, Modal, Alert, Mono,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'hired':    return 'success';
    case 'rejected': return 'danger';
    case 'active':   return 'primary';
    default:         return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function ApplicationsShow({ application, timeline }) {
  const canMoveStage      = useHRMAC('hrm.recruitment.applications.stage');
  const canSchedule       = useHRMAC('hrm.recruitment.interviews.create');
  const canSendOffer      = useHRMAC('hrm.recruitment.offers.create');
  const canReject         = useHRMAC('hrm.recruitment.applications.reject');
  const canStartOnboarding = useHRMAC('hrm.recruitment.onboarding.create');

  /* Modal: Move Stage */
  const [stageModalOpen, setStageModalOpen] = useState(false);
  const [selectedStageId, setSelectedStageId] = useState('');
  const [stageNotes, setStageNotes] = useState('');
  const [movingStage, setMovingStage] = useState(false);

  /* Modal: Reject */
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [rejecting, setRejecting] = useState(false);

  const hiringStages = application?.job?.hiringStages ?? [];
  const stageOptions = [
    { value: '', label: 'Select stage' },
    ...hiringStages.map(s => ({ value: String(s.id), label: s.name })),
  ];

  function moveStage() {
    if (!selectedStageId) return;
    setMovingStage(true);
    router.post(
      route('hrm.recruitment.applications.stage', application.id),
      { stage_id: selectedStageId, notes: stageNotes },
      {
        preserveScroll: true,
        onSuccess: () => { setStageModalOpen(false); setStageNotes(''); setSelectedStageId(''); },
        onFinish:  () => setMovingStage(false),
      },
    );
  }

  function reject() {
    setRejecting(true);
    router.post(
      route('hrm.recruitment.applications.reject', application.id),
      { reason: rejectReason },
      {
        preserveScroll: true,
        onSuccess: () => { setRejectModalOpen(false); setRejectReason(''); },
        onFinish:  () => setRejecting(false),
      },
    );
  }

  const app = application;

  return (
    <>
      <style>{`
        .app-show-grid {
          display: grid;
          grid-template-columns: 1fr 1fr 1fr;
          gap: 1.25rem;
          align-items: start;
        }
        @media (max-width: 900px) {
          .app-show-grid { grid-template-columns: 1fr; }
        }
        .app-show-header {
          padding-bottom: 1.25rem;
          border-bottom: 1px solid var(--aeos-divider);
          margin-bottom: 1.5rem;
        }
        .app-section-card {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: 1rem 1.25rem;
        }
        .app-timeline-item {
          display: flex;
          gap: 0.5rem;
          padding: 0.375rem 0;
          border-bottom: 1px solid var(--aeos-divider);
        }
        .app-timeline-item:last-child { border-bottom: none; }
      `}</style>

      <VStack gap={5}>
        {/* Header */}
        <div className="app-show-header">
          <HStack gap={3} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>Application</Eyebrow>
                <HStack gap={2} align="center">
                  <Text size="lg">{app.job?.title ?? '—'}</Text>
                  <Badge intent={statusIntent(app.status)}>{statusLabel(app.status)}</Badge>
                </HStack>
              </VStack>
            </Box>
            <Button
              intent="ghost"
              leftIcon="arrowLeft"
              onClick={() => router.get(route('hrm.recruitment.jobs.index'))}
            >
              Back
            </Button>
          </HStack>
        </div>

        {app.status === 'rejected' && app.rejection_reason && (
          <Alert intent="danger" title="Application Rejected">
            {app.rejection_reason}
          </Alert>
        )}

        <div className="app-show-grid">
          {/* Left: Applicant info */}
          <div className="app-section-card">
            <VStack gap={4}>
              <Eyebrow>Applicant</Eyebrow>
              <VStack gap={2}>
                <Field label="Name">
                  <Text>{app.applicant_name ?? '—'}</Text>
                </Field>
                <Field label="Email">
                  <Text>{app.email ?? '—'}</Text>
                </Field>
                <Field label="Phone">
                  <Mono>****</Mono>
                </Field>
                <Field label="Expected Salary">
                  <Mono>****</Mono>
                </Field>
                {app.resume_url && (
                  <Field label="Resume">
                    <a
                      className="aeos-btn aeos-btn-ghost"
                      href={app.resume_url}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Download Resume
                    </a>
                  </Field>
                )}
              </VStack>
            </VStack>
          </div>

          {/* Centre: Stage & history */}
          <div className="app-section-card">
            <VStack gap={4}>
              <Eyebrow>Stage</Eyebrow>
              {app.currentStage ? (
                <Badge intent="primary">{app.currentStage.name ?? app.currentStage}</Badge>
              ) : (
                <Text tone="secondary">No stage assigned</Text>
              )}

              <Eyebrow>Stage History</Eyebrow>
              <VStack gap={1}>
                {(app.stageHistory ?? []).length === 0 && (
                  <Text tone="secondary">No history yet.</Text>
                )}
                {(app.stageHistory ?? []).map((h, i) => (
                  <div key={i} className="app-timeline-item">
                    <VStack gap={0}>
                      <Text>
                        {h.from_stage ?? 'Start'} &rarr; {h.to_stage}
                      </Text>
                      {h.notes && <Text tone="secondary">{h.notes}</Text>}
                      <HStack gap={2}>
                        <Mono tone="secondary">{h.created_at}</Mono>
                        {h.mover && <Text tone="secondary">by {h.mover}</Text>}
                      </HStack>
                    </VStack>
                  </div>
                ))}
              </VStack>

              {(app.interviews ?? []).length > 0 && (
                <>
                  <Eyebrow>Interviews</Eyebrow>
                  <VStack gap={2}>
                    {app.interviews.map((iv, i) => (
                      <div key={i} className="app-timeline-item">
                        <VStack gap={0}>
                          <Text>{iv.type ?? 'Interview'}</Text>
                          <Mono tone="secondary">{iv.scheduled_at}</Mono>
                          <Badge intent={iv.status === 'completed' ? 'success' : 'neutral'}>
                            {iv.status ?? 'scheduled'}
                          </Badge>
                        </VStack>
                      </div>
                    ))}
                  </VStack>
                </>
              )}
            </VStack>
          </div>

          {/* Right: Actions */}
          <div className="app-section-card">
            <VStack gap={3}>
              <Eyebrow>Actions</Eyebrow>
              {canMoveStage && app.status !== 'rejected' && app.status !== 'hired' && (
                <Button
                  intent="soft"
                  fullWidth
                  onClick={() => setStageModalOpen(true)}
                >
                  Move Stage
                </Button>
              )}
              {canSchedule && (
                <Button
                  intent="soft"
                  fullWidth
                  onClick={() =>
                    router.get(
                      route('hrm.recruitment.interviews.create'),
                      { application_id: app.id },
                    )
                  }
                >
                  Schedule Interview
                </Button>
              )}
              {canSendOffer && app.status !== 'rejected' && (
                <Button
                  intent="soft"
                  fullWidth
                  onClick={() =>
                    router.get(
                      route('hrm.recruitment.offers.create'),
                      { application_id: app.id },
                    )
                  }
                >
                  Send Offer
                </Button>
              )}
              {canReject && app.status !== 'rejected' && app.status !== 'hired' && (
                <Button
                  intent="danger"
                  fullWidth
                  onClick={() => setRejectModalOpen(true)}
                >
                  Reject
                </Button>
              )}
              {canStartOnboarding && app.status === 'hired' && (
                <Button
                  intent="primary"
                  fullWidth
                  onClick={() =>
                    router.get(route('hrm.recruitment.onboarding.create', app.id))
                  }
                >
                  Start Onboarding
                </Button>
              )}
            </VStack>
          </div>
        </div>
      </VStack>

      {/* Move Stage Modal */}
      <Modal
        open={stageModalOpen}
        onClose={() => setStageModalOpen(false)}
        title="Move to Stage"
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={movingStage} onClick={moveStage}>
              Move
            </Button>
            <Button intent="ghost" onClick={() => setStageModalOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Stage" htmlFor="stage_id" required>
            <Select
              id="stage_id"
              options={stageOptions}
              value={selectedStageId}
              onChange={e => setSelectedStageId(e.target.value)}
            />
          </Field>
          <Field label="Notes" htmlFor="stage_notes">
            <Textarea
              id="stage_notes"
              value={stageNotes}
              onChange={e => setStageNotes(e.target.value)}
              placeholder="Optional notes about this move..."
            />
          </Field>
        </VStack>
      </Modal>

      {/* Reject Modal */}
      <Modal
        open={rejectModalOpen}
        onClose={() => setRejectModalOpen(false)}
        title="Reject Application"
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={rejecting} onClick={reject}>
              Confirm Rejection
            </Button>
            <Button intent="ghost" onClick={() => setRejectModalOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Text tone="secondary">
            This will permanently mark the application as rejected and notify the applicant.
          </Text>
          <Field label="Rejection Reason" htmlFor="reject_reason" required>
            <Textarea
              id="reject_reason"
              value={rejectReason}
              onChange={e => setRejectReason(e.target.value)}
              placeholder="Please provide a reason..."
            />
          </Field>
        </VStack>
      </Modal>
    </>
  );
}

ApplicationsShow.layout = page => <App title="Application">{page}</App>;
