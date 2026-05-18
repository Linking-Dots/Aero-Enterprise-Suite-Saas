<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Recruitment;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\JobInterview;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InterviewScheduler
{
    public const TYPES = ['phone', 'video', 'in_person', 'panel'];

    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function availableInterviewers(): Collection
    {
        return Employee::with('user:id,name')->get();
    }

    public function schedule(array $data, mixed $actor): JobInterview
    {
        return DB::transaction(function () use ($data, $actor): JobInterview {
            $interviewerIds = $data['interviewer_ids'] ?? [];
            unset($data['interviewer_ids']);

            $interview = JobInterview::create(array_merge($data, [
                'scheduled_by' => $actor->id,
                'interview_type' => $data['type'] ?? ($data['interview_type'] ?? null),
                'status' => 'scheduled',
            ]));

            if (! empty($interviewerIds)) {
                $interview->interviewers()->sync($interviewerIds);
            }

            $this->audit->log(
                event: RecruitmentAuditEvents::INTERVIEW_SCHEDULED,
                action: 'scheduled',
                subject: $interview,
                description: "Interview scheduled for application #{$interview->application_id} on {$interview->scheduled_at}.",
            );

            return $interview;
        });
    }

    public function update(JobInterview $interview, array $data): void
    {
        DB::transaction(function () use ($interview, $data): void {
            $before = $interview->toArray();
            $interview->update($data);

            $this->audit->log(
                event: RecruitmentAuditEvents::INTERVIEW_RESCHEDULED,
                action: 'rescheduled',
                subject: $interview,
                description: "Interview #{$interview->id} updated.",
                before: $before,
                after: $interview->fresh()?->toArray(),
            );
        });
    }
}
