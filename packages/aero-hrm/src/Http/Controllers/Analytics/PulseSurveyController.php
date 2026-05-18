<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Analytics;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Analytics\StorePulseSurveyRequest;
use Aero\HRM\Models\PulseSurvey;
use Aero\HRM\Services\Analytics\PulseSurveyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PulseSurveyController extends Controller
{
    public function __construct(
        private readonly PulseSurveyService $service,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * List all pulse surveys.
     */
    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.pulse-surveys.survey-list.view');

        $surveys = PulseSurvey::latest()->paginate(20)->withQueryString();

        return Inertia::render('HRM/Analytics/PulseSurveys/Index', [
            'surveys' => $surveys,
        ]);
    }

    /**
     * Show survey creation form.
     */
    public function create(): Response
    {
        Gate::authorize('hrmac', 'hrm.pulse-surveys.survey-list.create');

        return Inertia::render('HRM/Analytics/PulseSurveys/Create');
    }

    /**
     * Store a new pulse survey.
     */
    public function store(StorePulseSurveyRequest $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.pulse-surveys.survey-list.create');

        $survey = DB::transaction(function () use ($request): PulseSurvey {
            $survey = PulseSurvey::create([
                'title' => $request->validated('title'),
                'questions' => $request->validated('questions'),
                'anonymous' => (bool) $request->validated('anonymous', true),
                'closes_at' => $request->validated('closes_at'),
                'status' => PulseSurvey::STATUS_DRAFT,
                'created_by' => $request->user()->id,
                'audience_filter' => $request->validated('audience_filter'),
            ]);

            $this->audit->log(
                event: 'pulse_survey.created',
                action: 'create',
                subject: $survey,
                description: "Pulse survey '{$survey->title}' created",
            );

            return $survey;
        });

        return redirect()
            ->route('hrm.analytics.pulse-surveys.index')
            ->with('success', 'Survey created successfully.');
    }

    /**
     * Activate (send) a pulse survey.
     */
    public function send(PulseSurvey $survey): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.pulse-surveys.survey-list.publish');

        DB::transaction(function () use ($survey): void {
            $survey->update([
                'status' => PulseSurvey::STATUS_ACTIVE,
                'opens_at' => now(),
            ]);

            $this->audit->log(
                event: 'pulse_survey.sent',
                action: 'publish',
                subject: $survey,
                description: "Pulse survey '{$survey->title}' published/sent",
            );
        });

        return back()->with('success', 'Survey sent successfully.');
    }

    /**
     * Show aggregated survey results.
     */
    public function results(PulseSurvey $survey): Response
    {
        Gate::authorize('hrmac', 'hrm.pulse-surveys.survey-list.analyze');

        $count = $survey->responses()->count();
        $suppressed = $count < 5;

        $this->audit->log(
            event: 'pulse_survey.results.viewed',
            action: 'analyze',
            subject: $survey,
            description: "Results viewed for pulse survey '{$survey->title}'",
        );

        return Inertia::render('HRM/Analytics/PulseSurveys/Results', [
            'survey' => $survey,
            'suppressed' => $suppressed,
            'responseCount' => $count,
            'aggregates' => $suppressed ? null : $this->service->aggregate($survey),
        ]);
    }
}
