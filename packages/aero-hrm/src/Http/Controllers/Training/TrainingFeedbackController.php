<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreFeedbackRequest;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingFeedbackController extends Controller
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function create(TrainingEnrollment $enrollment): Response
    {
        // Employee must own the enrollment
        $user = request()->user();

        abort_unless(
            $enrollment->employee?->user_id === $user->id,
            403,
            'You are not authorized to submit feedback for this enrollment.'
        );

        abort_if(
            $enrollment->feedback()->exists(),
            422,
            'Feedback has already been submitted for this enrollment.'
        );

        $enrollment->load([
            'session:id,course_id,starts_at,ends_at',
            'session.course:id,title',
        ]);

        return Inertia::render('HRM/Training/Feedback/Create', [
            'enrollment' => $enrollment,
        ]);
    }

    public function store(StoreFeedbackRequest $request, TrainingEnrollment $enrollment): RedirectResponse
    {
        // Employee must own the enrollment
        $user = $request->user();

        abort_unless(
            $enrollment->employee?->user_id === $user->id,
            403,
            'You are not authorized to submit feedback for this enrollment.'
        );

        abort_if(
            $enrollment->feedback()->exists(),
            422,
            'Feedback has already been submitted for this enrollment.'
        );

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $enrollment): void {
            /** @var TrainingFeedback $feedback */
            $feedback = TrainingFeedback::create([
                'enrollment_id' => $enrollment->id,
                'content_rating' => $validated['content_rating'],
                'instructor_rating' => $validated['instructor_rating'],
                'overall_rating' => $validated['overall_rating'],
                'would_recommend' => $validated['would_recommend'],
                'what_worked' => $validated['what_worked'] ?? null,
                'what_didnt_work' => $validated['what_didnt_work'] ?? null,
                'suggestions' => $validated['suggestions'] ?? null,
                'submitted_at' => now(),
            ]);

            $this->audit->log(
                event: TrainingAuditEvents::TRAINING_FEEDBACK_SUBMITTED,
                action: 'submitted',
                subject: $feedback,
                description: "Feedback submitted for enrollment #{$enrollment->id}.",
            );
        });

        return redirect()
            ->route('hrm.training.courses.index')
            ->with('success', 'Feedback submitted. Thank you!');
    }
}
