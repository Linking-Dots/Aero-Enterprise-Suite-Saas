<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\MoveStageRequest;
use Aero\HRM\Http\Requests\Recruitment\RejectApplicationRequest;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Services\Recruitment\ApplicationPipelineService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationPipelineService $service,
    ) {}

    public function show(JobApplication $application): Response
    {
        $this->authorize('hrm.recruitment.applicants.view');

        $application->load([
            'job:id,title,status',
            'currentStage:id,name',
            'assignedTo:id,name',
        ]);

        return Inertia::render('HRM/Recruitment/Applications/Show', [
            'application' => $application,
            'timeline' => $this->service->timeline($application),
        ]);
    }

    public function moveStage(MoveStageRequest $request, JobApplication $application): RedirectResponse
    {
        $this->service->moveStage(
            $application,
            (int) $request->validated('stage_id'),
            (string) ($request->validated('notes') ?? ''),
            $request->user(),
        );

        return back()->with('success', 'Application stage updated.');
    }

    public function reject(RejectApplicationRequest $request, JobApplication $application): RedirectResponse
    {
        $this->service->reject(
            $application,
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('success', 'Application rejected.');
    }
}
