<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Succession;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmCareerPath;
use Aero\HRM\Models\HrmSafetyTrainingAssignment;

final class MilestoneProgressService
{
    public function progressFor(HrmCareerPath $path, Employee $employee): array
    {
        return $path->milestones->map(function ($m) use ($employee) {
            $met = collect($m->requirements ?? [])->every(
                fn ($req) => $this->requirementMet($employee, $req)
            );

            return [
                'milestone_id' => $m->id,
                'name' => $m->name,
                'completed' => $met,
            ];
        })->all();
    }

    private function requirementMet(Employee $employee, array $req): bool
    {
        return match ($req['type'] ?? null) {
            'training' => HrmSafetyTrainingAssignment::where('employee_id', $employee->id)
                ->where('training_id', $req['id'] ?? null)
                ->where('status', 'completed')
                ->exists(),
            default => false, // competency check deferred to future module
        };
    }
}
