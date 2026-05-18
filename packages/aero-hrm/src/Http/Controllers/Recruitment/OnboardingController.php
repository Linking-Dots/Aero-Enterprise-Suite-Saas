<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreOnboardingRunRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\OnboardingRun;
use Aero\HRM\Services\Recruitment\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $service,
    ) {}

    public function create(JobApplication $application): Response
    {
        $this->authorize('hrm.onboarding.onboarding-list.create');

        $application->load(['job:id,title', 'currentStage:id,name']);

        return Inertia::render('HRM/Recruitment/Onboarding/Create', [
            'application' => $application,
            'departments' => Department::select(['id', 'name'])->orderBy('name')->get(),
            'designations' => Designation::select(['id', 'name'])->orderBy('name')->get(),
            'templates' => $this->service->templates(),
        ]);
    }

    public function store(StoreOnboardingRunRequest $request, JobApplication $application): RedirectResponse
    {
        $run = $this->service->kickoff($application, $request->validated(), $request->user());

        return redirect()
            ->route('hrm.recruitment.jobs.show', $application->job_id)
            ->with('success', 'Onboarding started.');
    }

    public function complete(OnboardingRun $run): RedirectResponse
    {
        $this->authorize('hrm.onboarding.onboarding-list.create');

        $employee = $this->service->complete($run);

        return redirect()
            ->route('hrm.employees.show', $employee)
            ->with('success', 'Onboarding completed. Employee record created.');
    }
}
