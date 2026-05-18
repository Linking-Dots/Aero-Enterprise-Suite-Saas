<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Training;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\MarkAttendanceRequest;
use Aero\HRM\Http\Requests\Training\StoreEnrollmentRequest;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Aero\HRM\Services\Training\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrainingEnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.training.enrollment.manage');

        $enrollments = TrainingEnrollment::with([
            'session:id,course_id,starts_at,ends_at,status',
            'session.course:id,title',
            'employee:id,user_id',
            'employee.user:id,name,email',
        ])
            ->when($request->session_id, fn ($q, int $id) => $q->where('session_id', $id))
            ->when($request->status, fn ($q, string $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Training/Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only(['session_id', 'status', 'per_page']),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->enrollmentService->enrollMany(
            sessionId: $validated['session_id'],
            employeeIds: $validated['employee_ids'],
            actor: $request->user(),
            source: $validated['source'] ?? 'admin',
        );

        return back()->with('success', 'Employees enrolled.');
    }

    public function markAttendance(MarkAttendanceRequest $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validated();

        // Cast attendance map keys to int (HTTP keys are strings)
        $attendanceMap = [];
        foreach ($validated['attendance'] as $enrollmentId => $status) {
            $attendanceMap[(int) $enrollmentId] = $status;
        }

        $this->enrollmentService->markAttendance($session, $attendanceMap, $request->user());

        return back()->with('success', 'Attendance marked.');
    }

    public function cancel(TrainingEnrollment $enrollment): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.training.enrollment.manage');

        $this->enrollmentService->cancel($enrollment, request()->user());

        return back()->with('success', 'Enrollment cancelled.');
    }
}
