<?php

namespace Aero\HRM\Services\Safety;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmSafetyTrainingAssignment;
use Illuminate\Support\Facades\DB;

final class SafetyTrainingService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function assign(array $data): HrmSafetyTrainingAssignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = HrmSafetyTrainingAssignment::updateOrCreate(
                ['training_id' => $data['training_id'], 'employee_id' => $data['employee_id']],
                [...$data, 'status' => HrmSafetyTrainingAssignment::STATUS_ASSIGNED],
            );
            $this->audit->log(event: 'SAFETY_TRAINING_ASSIGNED', action: 'assign', subject: $assignment, description: "Safety training assigned to employee {$data['employee_id']}");

            return $assignment;
        });
    }

    public function complete(HrmSafetyTrainingAssignment $assignment): void
    {
        abort_if($assignment->status === HrmSafetyTrainingAssignment::STATUS_COMPLETED, 422, 'Already completed.');
        DB::transaction(function () use ($assignment) {
            $assignment->update([
                'status' => HrmSafetyTrainingAssignment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            $this->audit->log(event: 'SAFETY_TRAINING_COMPLETED', action: 'complete', subject: $assignment, description: "Safety training completed by employee {$assignment->employee_id}");
        });
    }
}
