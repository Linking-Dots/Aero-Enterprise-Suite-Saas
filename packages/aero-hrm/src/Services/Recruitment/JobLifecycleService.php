<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Recruitment;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Models\Job;
use Illuminate\Support\Facades\DB;

class JobLifecycleService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function create(array $data, mixed $actor): Job
    {
        return DB::transaction(function () use ($data, $actor): Job {
            $job = Job::create(array_merge($data, ['created_by' => $actor->id]));

            $this->audit->log(
                event: RecruitmentAuditEvents::JOB_CREATED,
                action: 'created',
                subject: $job,
                description: "Job posting \"{$job->title}\" created.",
            );

            return $job;
        });
    }

    public function update(Job $job, array $data): void
    {
        DB::transaction(function () use ($job, $data): void {
            $before = $job->toArray();
            $job->update($data);

            $this->audit->log(
                event: RecruitmentAuditEvents::JOB_UPDATED,
                action: 'updated',
                subject: $job,
                description: "Job posting \"{$job->title}\" updated.",
                before: $before,
                after: $job->fresh()?->toArray(),
            );
        });
    }

    public function publish(Job $job): void
    {
        DB::transaction(function () use ($job): void {
            $job->status = 'open';
            $job->posting_date = now();
            $job->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::JOB_PUBLISHED,
                action: 'published',
                subject: $job,
                description: "Job posting \"{$job->title}\" published.",
            );
        });
    }

    public function close(Job $job): void
    {
        DB::transaction(function () use ($job): void {
            $job->status = 'closed';
            $job->closing_date = now();
            $job->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::JOB_CLOSED,
                action: 'closed',
                subject: $job,
                description: "Job posting \"{$job->title}\" closed.",
            );
        });
    }

    public function kanbanBuckets(Job $job): array
    {
        $stages = $job->hiringStages()
            ->with(['applications' => fn ($q) => $q->select([
                'id', 'job_id', 'current_stage_id', 'first_name', 'last_name',
                'email', 'status', 'overall_score', 'created_at',
            ])])
            ->orderBy('sequence')
            ->get();

        return $stages->map(fn ($stage) => [
            'id' => $stage->id,
            'name' => $stage->name,
            'applications' => $stage->applications->values()->toArray(),
        ])->values()->toArray();
    }

    public function metrics(Job $job): array
    {
        $applications = $job->applications();

        $total = (clone $applications)->count();
        $hired = (clone $applications)->where('status', 'hired')->count();
        $rejected = (clone $applications)->where('status', 'rejected')->count();

        $byStage = (clone $applications)
            ->selectRaw('current_stage_id, count(*) as total')
            ->groupBy('current_stage_id')
            ->pluck('total', 'current_stage_id')
            ->toArray();

        return compact('total', 'hired', 'rejected', 'byStage');
    }
}
