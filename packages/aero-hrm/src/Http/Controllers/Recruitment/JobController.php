<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreJobRequest;
use Aero\HRM\Http\Requests\Recruitment\UpdateJobRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Job;
use Aero\HRM\Services\Recruitment\JobLifecycleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function __construct(
        private readonly JobLifecycleService $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('hrm.recruitment.job-openings.view');

        $jobs = Job::with(['department:id,name', 'hiringManager:id,name'])
            ->withCount('applications')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Recruitment/Jobs/Index', [
            'jobs' => $jobs,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('hrm.recruitment.job-openings.create');

        return Inertia::render('HRM/Recruitment/Jobs/Create', [
            'departments' => Department::select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        $job = $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('hrm.recruitment.jobs.show', $job)
            ->with('success', 'Job posting created.');
    }

    public function show(Job $job): Response
    {
        $this->authorize('hrm.recruitment.job-openings.view');

        $job->load(['department:id,name', 'hiringManager:id,name', 'hiringStages']);

        return Inertia::render('HRM/Recruitment/Jobs/Show', [
            'job' => $job,
            'hiringStages' => $job->hiringStages,
            'applicationsByStage' => $this->service->kanbanBuckets($job),
            'metrics' => $this->service->metrics($job),
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $this->service->update($job, $request->validated());

        return back()->with('success', 'Job posting updated.');
    }

    public function publish(Job $job): RedirectResponse
    {
        $this->authorize('hrm.recruitment.job-openings.update');

        $this->service->publish($job);

        return back()->with('success', 'Job posting published.');
    }

    public function close(Job $job): RedirectResponse
    {
        $this->authorize('hrm.recruitment.job-openings.update');

        $this->service->close($job);

        return back()->with('success', 'Job posting closed.');
    }
}
