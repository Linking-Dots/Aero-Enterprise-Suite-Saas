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
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
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
      className={`min-w-[220px] flex-1 bg-surface rounded-md border border-divider p-3 flex flex-col gap-2 transition-colors ${
        isOver ? 'border-primary' : ''
      }`}
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
      className={`bg-page rounded-sm border border-divider px-3 py-2.5 cursor-grab transition-shadow ${
        isDragging ? 'opacity-60 cursor-grabbing' : 'hover:shadow-md'
      }`}
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
      <VStack gap={5}>
        {/* Header */}
        <div className="border-b border-divider pb-5 mb-6">
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
          <div className="flex gap-6 mb-6">
            <div className="flex-1 bg-surface rounded-md border border-divider p-4 px-5">
              <Text tone="secondary">Total</Text>
              <Text size="lg">{metrics.total ?? 0}</Text>
            </div>
            <div className="flex-1 bg-surface rounded-md border border-divider p-4 px-5">
              <Text tone="secondary">Hired</Text>
              <Text size="lg">{metrics.hired ?? 0}</Text>
            </div>
            <div className="flex-1 bg-surface rounded-md border border-divider p-4 px-5">
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
          <div className="flex gap-4 overflow-x-auto pb-2">
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
