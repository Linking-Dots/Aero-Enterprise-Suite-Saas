<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Recruitment;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\OnboardingRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function templates(): array
    {
        return [];
    }

    public function kickoff(JobApplication $app, array $data, mixed $actor): OnboardingRun
    {
        return DB::transaction(function () use ($app, $data, $actor): OnboardingRun {
            $run = OnboardingRun::create([
                'application_id' => $app->id,
                'status' => OnboardingRun::STATUS_IN_PROGRESS,
                'checklist' => $data['checklist'] ?? null,
                'created_by' => $actor->id,
            ]);

            $run->started_at = now();
            $run->save();

            $app->fill(['status' => 'hired']);
            $app->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::EMPLOYEE_ONBOARDED,
                action: 'onboarding_started',
                subject: $run,
                description: "Onboarding started for application #{$app->id}.",
                metadata: $data,
            );

            return $run;
        });
    }

    public function complete(OnboardingRun $run): Employee
    {
        return DB::transaction(function () use ($run): Employee {
            $run->load(['application']);
            $application = $run->application;

            $employeeCode = 'EMP-'.strtoupper(Str::random(6));

            $employee = Employee::create([
                'employee_code' => $employeeCode,
                'status' => 'active',
                'date_of_joining' => now()->toDateString(),
                'employment_type' => 'full_time',
            ]);

            $run->status = OnboardingRun::STATUS_COMPLETED;
            $run->completed_at = now();
            $run->employee_id = $employee->id;
            $run->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::EMPLOYEE_ONBOARDED,
                action: 'onboarding_completed',
                subject: $employee,
                description: "Employee {$employeeCode} created via onboarding run #{$run->id}.",
            );

            return $employee;
        });
    }
}
