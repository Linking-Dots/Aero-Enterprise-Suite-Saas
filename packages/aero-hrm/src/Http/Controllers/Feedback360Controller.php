<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Feedback360Request;
use Aero\HRM\Models\Feedback360Response;
use Aero\HRM\Services\Performance\Feedback360Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Feedback360Controller extends Controller
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    /**
     * List all 360° feedback requests.
     * Gate: hrm.feedback-360.feedback-reviews.view
     */
    public function index(): Response
    {
        $this->authorize('hrm.feedback-360.feedback-reviews.view');

        $requests = Feedback360Request::with(['subject.user'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HRM/Performance/Feedback360/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Open a new 360° feedback request.
     * Gate: hrm.feedback-360.feedback-reviews.create
     */
    public function store(Request $request, Feedback360Service $svc): RedirectResponse
    {
        $this->authorize('hrm.feedback-360.feedback-reviews.create');

        $validated = $request->validate([
            'subject_employee_id' => ['required', 'integer', 'exists:employees,id'],
            'respondent_ids' => ['required', 'array', 'min:1'],
            'respondent_ids.*' => ['integer', 'exists:users,id'],
            'due_on' => ['required', 'date', 'after:today'],
        ]);

        $validated['requester_id'] = $request->user()->id;

        $svc->open($validated);

        return back()->with('success', '360° feedback request opened successfully.');
    }

    /**
     * Submit a response for a 360° feedback request.
     * Gate: hrm.feedback-360.feedback-reviews.submit
     */
    public function respond(Request $request, Feedback360Request $feedback360request): RedirectResponse
    {
        $this->authorize('hrm.feedback-360.feedback-reviews.submit');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'overall_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $response = Feedback360Response::create([
            'feedback_360_id' => $feedback360request->id,
            'reviewer_id' => $request->user()->id,
            'question_responses' => $validated['answers'],
            'overall_rating' => $validated['overall_rating'] ?? null,
            'comments' => $validated['comments'] ?? null,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $response,
            description: "360° feedback response submitted for request #{$feedback360request->id}",
        );

        return back()->with('success', 'Feedback submitted successfully.');
    }
}
