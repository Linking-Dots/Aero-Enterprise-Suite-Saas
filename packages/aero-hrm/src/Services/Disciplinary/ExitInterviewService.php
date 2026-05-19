<?php

namespace Aero\HRM\Services\Disciplinary;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmExitInterview;
use Illuminate\Support\Facades\DB;

final class ExitInterviewService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function schedule(array $data): HrmExitInterview
    {
        return DB::transaction(function () use ($data) {
            $interview = HrmExitInterview::create($data);
            $this->audit->log(event: 'EXIT_INTERVIEW_SCHEDULED', action: 'schedule', subject: $interview, description: "Scheduled exit interview for employee {$interview->employee_id}");

            return $interview;
        });
    }

    public function record(HrmExitInterview $interview, array $data): void
    {
        DB::transaction(function () use ($interview, $data) {
            $interview->update([
                ...$data,
                'status' => HrmExitInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            $this->audit->log(event: 'EXIT_INTERVIEW_COMPLETED', action: 'complete', subject: $interview, description: "Exit interview completed for employee {$interview->employee_id}");
        });
    }
}
