<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Recruitment;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobApplicationStageHistory;
use Illuminate\Support\Facades\DB;

class ApplicationPipelineService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function moveStage(JobApplication $app, int $stageId, string $notes, mixed $actor): void
    {
        DB::transaction(function () use ($app, $stageId, $notes, $actor): void {
            $fromStageId = $app->current_stage_id;

            $app->current_stage_id = $stageId;
            $app->save();

            JobApplicationStageHistory::create([
                'application_id' => $app->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $stageId,
                'moved_by' => $actor->id,
                'moved_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->log(
                event: RecruitmentAuditEvents::APPLICATION_STAGE_CHANGED,
                action: 'stage_changed',
                subject: $app,
                description: "Application #{$app->id} moved to stage {$stageId}.",
                before: ['current_stage_id' => $fromStageId],
                after: ['current_stage_id' => $stageId],
            );
        });
    }

    public function reject(JobApplication $app, string $reason, mixed $actor): void
    {
        DB::transaction(function () use ($app, $reason, $actor): void {
            $app->fill([
                'rejection_reason' => $reason,
                'status' => 'rejected',
            ]);
            $app->rejected_by = $actor->id;
            $app->rejected_at = now();
            $app->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::APPLICATION_REJECTED,
                action: 'rejected',
                subject: $app,
                description: "Application #{$app->id} rejected. Reason: {$reason}",
            );
        });
    }

    public function timeline(JobApplication $app): array
    {
        $history = $app->stageHistory()
            ->with(['fromStage:id,name', 'toStage:id,name', 'movedBy:id,name'])
            ->orderBy('moved_at')
            ->get();

        return $history->map(fn ($entry) => [
            'id' => $entry->id,
            'from_stage' => $entry->fromStage?->name,
            'to_stage' => $entry->toStage?->name,
            'moved_by' => $entry->movedBy?->name,
            'notes' => $entry->notes,
            'moved_at' => $entry->moved_at?->toISOString(),
        ])->values()->toArray();
    }
}
