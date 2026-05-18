import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
  useDroppable,
  useDraggable,
} from '@dnd-kit/core';
import App from '../../../../App.jsx';
import useHRMAC from '../../../../hooks/useHRMAC.js';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, Card,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'open':   return 'success';
    case 'draft':  return 'warning';
    case 'closed': return 'neutral';
    default:       return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

/* ── Droppable stage column ─────────────────────────────────── */
function StageColumn({ stage, applications }) {
  const { setNodeRef, isOver } = useDroppable({ id: stage.id });

  return (
    <div
      ref={setNodeRef}
      className={`recruitment-kanban-col${isOver ? ' recruitment-kanban-col--over' : ''}`}
    >
      <HStack gap={2} align="center">
        <Eyebrow>{stage.name}</Eyebrow>
        <Badge intent="neutral">{applications.length}</Badge>
      </HStack>
      <VStack gap={2}>
        {applications.map(app => (
          <DraggableCard key={app.id} application={app} />
        ))}
        {applications.length === 0 && (
          <Text tone="secondary">No applications</Text>
        )}
      </VStack>
    </div>
  );
}

/* ── Draggable application card ─────────────────────────────── */
function DraggableCard({ application }) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
    id: application.id,
  });

  const dragStyle = transform
    ? { transform: `translate3d(${transform.x}px, ${transform.y}px, 0)` }
    : undefined;

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      className={`recruitment-app-card${isDragging ? ' recruitment-app-card--dragging' : ''}`}
      style={dragStyle}
      onClick={() => router.get(route('hrm.recruitment.applications.show', application.id))}
    >
      <VStack gap={1}>
        <Text>{application.applicant_name}</Text>
        <Text tone="secondary">{application.email}</Text>
      </VStack>
    </div>
  );
}

/* ── Main page ──────────────────────────────────────────────── */
export default function JobsShow({ job, hiringStages, applicationsByStage, metrics }) {
  const canPublish = useHRMAC('hrm.recruitment.jobs.publish');
  const canClose   = useHRMAC('hrm.recruitment.jobs.close');

  const sensors = useSensors(useSensor(PointerSensor));

  const [publishing, setPublishing] = useState(false);
  const [closing,    setClosing]    = useState(false);

  function handleDragEnd(event) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    router.post(
      route('hrm.recruitment.applications.stage', active.id),
      { stage_id: over.id, notes: 'Moved via kanban' },
      { preserveScroll: true },
    );
  }

  function publish() {
    setPublishing(true);
    router.post(
      route('hrm.recruitment.jobs.publish', job.id),
      {},
      { onFinish: () => setPublishing(false) },
    );
  }

  function close() {
    setClosing(true);
    router.post(
      route('hrm.recruitment.jobs.close', job.id),
      {},
      { onFinish: () => setClosing(false) },
    );
  }

  const stagesWithApps = (hiringStages ?? []).map(stage => ({
    stage,
    applications: applicationsByStage?.[stage.id]?.applications ?? [],
  }));

  return (
    <>
      <style>{`
        .recruitment-job-header {
          padding-bottom: 1.25rem;
          border-bottom: 1px solid var(--aeos-divider);
          margin-bottom: 1.5rem;
        }
        .recruitment-metrics {
          display: flex;
          gap: 1.5rem;
          margin-bottom: 1.5rem;
        }
        .recruitment-metric-card {
          flex: 1;
          padding: 1rem 1.25rem;
          background: var(--aeos-bg-surface);
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
        }
        .recruitment-kanban {
          display: flex;
          gap: 1rem;
          overflow-x: auto;
          padding-bottom: 0.5rem;
        }
        .recruitment-kanban-col {
          min-width: 220px;
          flex: 1;
          background: var(--aeos-bg-surface);
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
          padding: 0.75rem;
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
          transition: border-color 0.15s;
        }
        .recruitment-kanban-col--over {
          border-color: var(--aeos-primary);
        }
        .recruitment-app-card {
          background: var(--aeos-bg-page);
          border-radius: var(--aeos-r-sm);
          border: 1px solid var(--aeos-divider);
          padding: 0.625rem 0.75rem;
          cursor: grab;
          transition: box-shadow 0.15s;
        }
        .recruitment-app-card:hover {
          box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .recruitment-app-card--dragging {
          opacity: 0.6;
          cursor: grabbing;
        }
      `}</style>

      <VStack gap={5}>
        {/* Header */}
        <div className="recruitment-job-header">
          <HStack gap={3} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>Job Posting</Eyebrow>
                <HStack gap={2} align="center">
                  <Text size="lg">{job.title}</Text>
                  <Badge intent={statusIntent(job.status)}>{statusLabel(job.status)}</Badge>
                </HStack>
                <HStack gap={3}>
                  <Text tone="secondary">{job.department?.name ?? '—'}</Text>
                  <Text tone="secondary">{job.type ?? '—'}</Text>
                  {job.posting_date && (
                    <Text tone="secondary">Posted: {job.posting_date}</Text>
                  )}
                  {job.closing_date && (
                    <Text tone="secondary">Closes: {job.closing_date}</Text>
                  )}
                </HStack>
              </VStack>
            </Box>
            <HStack gap={2}>
              {canPublish && job.status === 'draft' && (
                <Button intent="primary" loading={publishing} onClick={publish}>
                  Publish
                </Button>
              )}
              {canClose && job.status === 'open' && (
                <Button intent="soft" loading={closing} onClick={close}>
                  Close Job
                </Button>
              )}
              <Button
                intent="ghost"
                leftIcon="arrowLeft"
                onClick={() => router.get(route('hrm.recruitment.jobs.index'))}
              >
                Back
              </Button>
            </HStack>
          </HStack>
        </div>

        {/* Metrics */}
        {metrics && (
          <div className="recruitment-metrics">
            <div className="recruitment-metric-card">
              <Text tone="secondary">Total</Text>
              <Text size="lg">{metrics.total ?? 0}</Text>
            </div>
            <div className="recruitment-metric-card">
              <Text tone="secondary">Hired</Text>
              <Text size="lg">{metrics.hired ?? 0}</Text>
            </div>
            <div className="recruitment-metric-card">
              <Text tone="secondary">Rejected</Text>
              <Text size="lg">{metrics.rejected ?? 0}</Text>
            </div>
          </div>
        )}

        {/* Kanban board */}
        <Eyebrow>Pipeline</Eyebrow>
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <div className="recruitment-kanban">
            {stagesWithApps.map(({ stage, applications }) => (
              <StageColumn
                key={stage.id}
                stage={stage}
                applications={applications}
              />
            ))}
            {stagesWithApps.length === 0 && (
              <Text tone="secondary">No hiring stages configured for this job.</Text>
            )}
          </div>
        </DndContext>
      </VStack>
    </>
  );
}

JobsShow.layout = page => <App title="Job Posting">{page}</App>;
