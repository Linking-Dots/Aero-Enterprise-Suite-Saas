<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreSessionRequest;
use Aero\HRM\Http\Requests\Training\UpdateSessionRequest;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrainingSessionController extends Controller
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function create(TrainingCourse $course): Response
    {
        Gate::authorize('hrmac', 'hrm.training.training-sessions.create');

        return Inertia::render('HRM/Training/Sessions/Create', [
            'course' => $course->only(['id', 'title']),
        ]);
    }

    public function store(StoreSessionRequest $request, TrainingCourse $course): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $course, $request): void {
            /** @var TrainingSession $session */
            $session = TrainingSession::create([
                'course_id' => $course->id,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'location' => $validated['location'] ?? null,
                'meeting_link' => $validated['meeting_link'] ?? null,
                'capacity' => $validated['capacity'],
                'instructor_id' => $validated['instructor_id'] ?? null,
                'status' => TrainingSession::STATUS_SCHEDULED,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->audit->log(
                event: TrainingAuditEvents::SESSION_SCHEDULED,
                action: 'scheduled',
                subject: $session,
                description: "Training session scheduled for course '{$course->title}'.",
            );
        });

        return redirect()
            ->route('hrm.training.courses.show', $course)
            ->with('success', 'Session scheduled.');
    }

    public function update(UpdateSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        DB::transaction(function () use ($request, $session): void {
            $session->update($request->validated());

            $this->audit->log(
                event: TrainingAuditEvents::SESSION_UPDATED,
                action: 'updated',
                subject: $session,
                description: "Training session #{$session->id} updated.",
            );
        });

        return back()->with('success', 'Session updated.');
    }
}
