<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreCourseRequest;
use Aero\HRM\Http\Requests\Training\UpdateCourseRequest;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingSession;
use Aero\HRM\Services\Training\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrainingCourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService,
        protected AuditServiceInterface $audit,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.view');

        $courses = TrainingCourse::with(['category:id,name'])
            ->withCount('sessions')
            ->when($request->search, fn ($q, string $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->category_id, fn ($q, int $id) => $q->where('category_id', $id))
            ->when($request->delivery_mode, fn ($q, string $m) => $q->where('delivery_mode', $m))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $categories = TrainingCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('HRM/Training/Courses/Index', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'delivery_mode', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.create');

        $categories = TrainingCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('HRM/Training/Courses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courseService->create($request->validated(), $request->user());

        return redirect()
            ->route('hrm.training.courses.show', $course)
            ->with('success', 'Course created.');
    }

    public function show(TrainingCourse $course): Response
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.view');

        $course->load([
            'category:id,name',
            'materials' => fn ($q) => $q->orderBy('display_order'),
            'sessions' => fn ($q) => $q->withCount('enrollments')->latest('starts_at'),
        ]);

        return Inertia::render('HRM/Training/Courses/Show', [
            'course' => $course,
        ]);
    }

    public function update(UpdateCourseRequest $request, TrainingCourse $course): RedirectResponse
    {
        $this->courseService->update($course, $request->validated());

        return back()->with('success', 'Course updated.');
    }

    public function destroy(TrainingCourse $course): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.training.training-programs.delete');

        $hasUpcoming = $course->sessions()
            ->where('starts_at', '>', now())
            ->whereNotIn('status', [TrainingSession::STATUS_CANCELLED])
            ->exists();

        if ($hasUpcoming) {
            abort(422, 'Cannot delete a course that has upcoming sessions.');
        }

        DB::transaction(function () use ($course): void {
            $course->delete();

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_DELETED,
                action: 'deleted',
                subject: $course,
                description: "Training course '{$course->title}' deleted.",
            );
        });

        return redirect()
            ->route('hrm.training.courses.index')
            ->with('success', 'Course deleted.');
    }
}
