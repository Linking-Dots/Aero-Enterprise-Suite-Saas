<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreInterviewRequest;
use Aero\HRM\Http\Requests\Recruitment\UpdateInterviewRequest;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobInterview;
use Aero\HRM\Services\Recruitment\InterviewScheduler;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function __construct(
        private readonly InterviewScheduler $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('hrm.recruitment.interview-scheduling.view');

        $interviews = JobInterview::with([
            'application:id,first_name,last_name,job_id',
            'application.job:id,title',
            'scheduledBy:id,name',
            'interviewers:id,name',
        ])
            ->latest('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Recruitment/Interviews/Index', [
            'interviews' => $interviews,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('hrm.recruitment.interview-scheduling.create');

        return Inertia::render('HRM/Recruitment/Interviews/Create', [
            'applications' => JobApplication::select(['id', 'first_name', 'last_name', 'job_id'])
                ->whereNotIn('status', ['rejected', 'hired', 'withdrawn'])
                ->with('job:id,title')
                ->get(),
            'interviewers' => $this->service->availableInterviewers(),
            'types' => InterviewScheduler::TYPES,
        ]);
    }

    public function store(StoreInterviewRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['interview_type'] = $data['type'];
        unset($data['type']);

        $this->service->schedule($data, $request->user());

        return redirect()
            ->route('hrm.recruitment.interviews.index')
            ->with('success', 'Interview scheduled.');
    }

    public function update(UpdateInterviewRequest $request, JobInterview $interview): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['type'])) {
            $data['interview_type'] = $data['type'];
            unset($data['type']);
        }

        $this->service->update($interview, $data);

        return back()->with('success', 'Interview updated.');
    }
}
