# Plan H-8 — HRM Training & Development Module

**Status:** Ready for implementation
**Owner:** HRM squad
**Depends on:** `aero-hrm` core, `aero-hrmac`, `aero-ui`, `AuditServiceInterface`, Laravel Media Library
**Package:** `packages/aero-hrm/`
**Pages root:** `packages/aero-ui/resources/js/Pages/HRM/Training/`
**Route prefix:** `/hrm/training`
**Route name prefix:** `hrm.training.*`

---

## 1. Goal

Deliver a complete Learning & Development workflow:

1. **Training categories** — taxonomy with CRUD.
2. **Training courses** — full CRUD with attached materials (URLs and uploaded files via Media Library).
3. **Training sessions** — schedule sessions of a course (dates, location, capacity, instructor).
4. **Enrollments** — admin enroll (one-or-many employees) + employee self-enroll; cancel; waitlist when capacity hit.
5. **Attendance marking** — per session, marker = instructor or HR.
6. **Post-training feedback** — employee submits structured feedback after attendance.

Routes gated by HRMAC. Each state change emits `AuditServiceInterface` events.

---

## 2. Non-Goals

- LMS-style SCORM/xAPI content (Phase 2).
- Certification expiry tracking (covered by existing `EmployeeCertification`).
- External LMS sync (Phase 2).

---

## 3. HRMAC Permission Map

Append under `modules.hrm.submodules.training` in `packages/aero-hrm/config/hrmac.php`:

```php
'training' => [
    'label' => 'Training & Development',
    'components' => [
        'categories'  => ['label' => 'Categories',  'actions' => ['view', 'edit']],
        'courses'     => ['label' => 'Courses',     'actions' => ['view', 'edit']],
        'sessions'    => ['label' => 'Sessions',    'actions' => ['view', 'edit']],
        'enrollments' => ['label' => 'Enrollments', 'actions' => ['view', 'edit']],
        'feedback'    => ['label' => 'Feedback',    'actions' => ['view', 'edit']],
    ],
],
```

---

## 4. Audit Events

`packages/aero-hrm/src/Audit/TrainingAuditEvents.php`:

```php
final class TrainingAuditEvents
{
    public const COURSE_CREATED              = 'training.course.created';
    public const COURSE_UPDATED              = 'training.course.updated';
    public const COURSE_DELETED              = 'training.course.deleted';
    public const SESSION_SCHEDULED           = 'training.session.scheduled';
    public const SESSION_UPDATED             = 'training.session.updated';
    public const TRAINING_ENROLLMENT_CREATED = 'training.enrollment.created';
    public const TRAINING_ENROLLMENT_CANCELLED = 'training.enrollment.cancelled';
    public const TRAINING_ATTENDANCE_MARKED  = 'training.attendance.marked';
    public const TRAINING_FEEDBACK_SUBMITTED = 'training.feedback.submitted';
}
```

---

## 5. Data Model

### 5.1 New migrations

`packages/aero-hrm/database/migrations/2026_05_17_000010_training_h8.php`:

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('training_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->enum('delivery_mode', ['in_person', 'virtual', 'self_paced'])->default('in_person');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->json('learning_objectives')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->enum('kind', ['link', 'file'])->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->unsignedInteger('capacity')->default(20);
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('status', ['enrolled', 'waitlisted', 'cancelled', 'attended', 'no_show'])->default('enrolled');
            $table->enum('source', ['self', 'admin'])->default('admin');
            $table->timestamp('attended_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enrolled_by')->constrained('users');
            $table->timestamps();

            $table->unique(['session_id', 'employee_id']);
        });

        Schema::create('training_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('training_enrollments')->cascadeOnDelete();
            $table->unsignedTinyInteger('content_rating');     // 1-5
            $table->unsignedTinyInteger('instructor_rating');  // 1-5
            $table->unsignedTinyInteger('overall_rating');     // 1-5
            $table->boolean('would_recommend')->default(false);
            $table->text('what_worked')->nullable();
            $table->text('what_didnt_work')->nullable();
            $table->text('suggestions')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_feedbacks');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_course_materials');
        Schema::dropIfExists('training_courses');
        Schema::dropIfExists('training_categories');
    }
};
```

### 5.2 Models (sketch)

- `TrainingCategory` — `name, slug, description, color, is_active`; `hasMany(courses)`.
- `TrainingCourse` — `belongsTo(category)`, `hasMany(materials, sessions)`; slug auto from title.
- `TrainingCourseMaterial` — `kind, label, url|file_path`.
- `TrainingSession` — `belongsTo(course, instructor)`, `hasMany(enrollments)`, `enrolledCount()`, `hasCapacity()`.
- `TrainingEnrollment` — `belongsTo(session, employee, enroller, marker)`, `hasOne(feedback)`.
- `TrainingFeedback` — `belongsTo(enrollment)`.

`TrainingEnrollment` enforces `unique(session_id, employee_id)` at DB and validates in Form Request.

---

## 6. Backend — Controllers

All extend `Aero\HRM\Http\Controllers\Controller`, inject `AuditServiceInterface`.

### 6.1 `TrainingCategoryController`

`packages/aero-hrm/src/Http/Controllers/Training/TrainingCategoryController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreCategoryRequest;
use Aero\HRM\Models\TrainingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TrainingCategoryController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(Request $request)
    {
        $categories = TrainingCategory::query()
            ->withCount('courses')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Training/Categories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['search']),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;

        $category = TrainingCategory::create($data);

        $this->audit->record('training.category.created', $category, $data);

        return back()->with('success', 'Category created.');
    }

    public function update(StoreCategoryRequest $request, TrainingCategory $category)
    {
        $data = $request->validated();
        $category->update($data);

        $this->audit->record('training.category.updated', $category, $data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(TrainingCategory $category)
    {
        abort_if($category->courses()->exists(), 422, 'Category has courses.');
        $category->delete();

        $this->audit->record('training.category.deleted', $category, []);

        return back()->with('success', 'Category deleted.');
    }
}
```

### 6.2 `TrainingCourseController`

```php
<?php

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreCourseRequest;
use Aero\HRM\Http\Requests\Training\UpdateCourseRequest;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Services\Training\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingCourseController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly CourseService $courses,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'mode', 'mandatory']);

        $courses = TrainingCourse::query()
            ->with('category')
            ->withCount(['sessions', 'sessions as upcoming_sessions_count' => fn ($q) => $q->where('starts_at', '>', now())])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['mode'] ?? null, fn ($q, $v) => $q->where('delivery_mode', $v))
            ->when(isset($filters['mandatory']), fn ($q) => $q->where('is_mandatory', (bool) $filters['mandatory']))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HRM/Training/Courses/Index', [
            'courses'    => $courses,
            'filters'    => $filters,
            'categories' => TrainingCategory::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('HRM/Training/Courses/Create', [
            'categories' => TrainingCategory::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = $this->courses->create($request->validated(), $request->user());

        $this->audit->record(TrainingAuditEvents::COURSE_CREATED, $course, [
            'title' => $course->title, 'category_id' => $course->category_id,
        ]);

        return redirect()->route('hrm.training.courses.show', $course)->with('success', 'Course created.');
    }

    public function show(TrainingCourse $course)
    {
        $course->load(['category', 'materials', 'sessions.instructor', 'sessions.enrollments']);

        return Inertia::render('HRM/Training/Courses/Show', [
            'course'   => $course,
            'sessions' => $course->sessions->sortBy('starts_at')->values(),
        ]);
    }

    public function update(UpdateCourseRequest $request, TrainingCourse $course)
    {
        $this->courses->update($course, $request->validated());

        $this->audit->record(TrainingAuditEvents::COURSE_UPDATED, $course, $request->validated());

        return back()->with('success', 'Course updated.');
    }

    public function destroy(TrainingCourse $course)
    {
        abort_if($course->sessions()->where('starts_at', '>', now())->exists(), 422, 'Course has upcoming sessions.');
        $course->delete();

        $this->audit->record(TrainingAuditEvents::COURSE_DELETED, $course, []);

        return redirect()->route('hrm.training.courses.index')->with('success', 'Course deleted.');
    }
}
```

### 6.3 `TrainingSessionController`

```php
<?php

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreSessionRequest;
use Aero\HRM\Http\Requests\Training\UpdateSessionRequest;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingSession;
use Inertia\Inertia;

class TrainingSessionController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function create(TrainingCourse $course)
    {
        return Inertia::render('HRM/Training/Sessions/Create', [
            'course' => $course,
        ]);
    }

    public function store(StoreSessionRequest $request, TrainingCourse $course)
    {
        $data = $request->validated();
        $data['course_id']  = $course->id;
        $data['created_by'] = $request->user()->id;

        $session = TrainingSession::create($data);

        $this->audit->record(TrainingAuditEvents::SESSION_SCHEDULED, $session, [
            'course_id' => $course->id, 'starts_at' => $session->starts_at->toIso8601String(),
        ]);

        return redirect()->route('hrm.training.courses.show', $course)->with('success', 'Session scheduled.');
    }

    public function update(UpdateSessionRequest $request, TrainingSession $session)
    {
        $session->update($request->validated());

        $this->audit->record(TrainingAuditEvents::SESSION_UPDATED, $session, $request->validated());

        return back()->with('success', 'Session updated.');
    }
}
```

### 6.4 `TrainingEnrollmentController`

```php
<?php

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\MarkAttendanceRequest;
use Aero\HRM\Http\Requests\Training\StoreEnrollmentRequest;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Aero\HRM\Services\Training\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingEnrollmentController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly EnrollmentService $enrollments,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['course_id', 'session_id', 'status']);

        $enrollments = TrainingEnrollment::query()
            ->with(['session.course', 'employee'])
            ->when($filters['session_id'] ?? null, fn ($q, $v) => $q->where('session_id', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->whereHas('session', fn ($s) => $s->where('course_id', $v)))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Training/Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters'     => $filters,
        ]);
    }

    public function store(StoreEnrollmentRequest $request)
    {
        $created = $this->enrollments->enrollMany(
            $request->integer('session_id'),
            $request->input('employee_ids', []),
            $request->user(),
            source: $request->user()->is_hr_admin ? 'admin' : 'self',
        );

        foreach ($created as $enrollment) {
            $this->audit->record(TrainingAuditEvents::TRAINING_ENROLLMENT_CREATED, $enrollment, [
                'session_id' => $enrollment->session_id,
                'employee_id' => $enrollment->employee_id,
            ]);
        }

        return back()->with('success', count($created).' enrollments created.');
    }

    public function markAttendance(MarkAttendanceRequest $request, TrainingSession $session)
    {
        $updated = $this->enrollments->markAttendance(
            $session,
            $request->input('attendance', []),
            $request->user(),
        );

        foreach ($updated as $enrollment) {
            $this->audit->record(TrainingAuditEvents::TRAINING_ATTENDANCE_MARKED, $enrollment, [
                'status' => $enrollment->status,
            ]);
        }

        return back()->with('success', 'Attendance recorded.');
    }

    public function cancel(TrainingEnrollment $enrollment)
    {
        $this->enrollments->cancel($enrollment, auth()->user());

        $this->audit->record(TrainingAuditEvents::TRAINING_ENROLLMENT_CANCELLED, $enrollment, []);

        return back()->with('success', 'Enrollment cancelled.');
    }
}
```

### 6.5 `TrainingFeedbackController`

```php
<?php

namespace Aero\HRM\Http\Controllers\Training;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Training\StoreFeedbackRequest;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingFeedback;
use Inertia\Inertia;

class TrainingFeedbackController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function create(TrainingEnrollment $enrollment)
    {
        abort_unless($enrollment->employee_id === auth()->user()->employee?->id, 403);
        abort_unless($enrollment->status === 'attended', 422, 'Feedback only after attendance.');

        return Inertia::render('HRM/Training/Feedback/Create', [
            'enrollment' => $enrollment->load('session.course'),
        ]);
    }

    public function store(StoreFeedbackRequest $request, TrainingEnrollment $enrollment)
    {
        abort_unless($enrollment->employee_id === $request->user()->employee?->id, 403);

        $feedback = TrainingFeedback::create([
            'enrollment_id'     => $enrollment->id,
            'content_rating'    => $request->integer('content_rating'),
            'instructor_rating' => $request->integer('instructor_rating'),
            'overall_rating'    => $request->integer('overall_rating'),
            'would_recommend'   => $request->boolean('would_recommend'),
            'what_worked'       => $request->string('what_worked')->toString(),
            'what_didnt_work'   => $request->string('what_didnt_work')->toString(),
            'suggestions'       => $request->string('suggestions')->toString(),
            'submitted_at'      => now(),
        ]);

        $this->audit->record(TrainingAuditEvents::TRAINING_FEEDBACK_SUBMITTED, $feedback, [
            'enrollment_id' => $enrollment->id,
            'overall'       => $feedback->overall_rating,
        ]);

        return redirect()->route('hrm.self-service.training')->with('success', 'Thanks for your feedback.');
    }
}
```

---

## 7. Services

- `CourseService` — create / update; sync `materials` (files via Media Library, links inline); ensure unique slug.
- `EnrollmentService`:
  - `enrollMany(sessionId, employeeIds, user, source)` — capacity-aware; surplus rows enter `waitlisted`; rejects duplicates.
  - `markAttendance(session, attendanceMap, marker)` — bulk update from `{enrollment_id: 'attended|no_show'}`.
  - `cancel(enrollment, user)` — if there's a waitlisted row, promote it to `enrolled`.

---

## 8. Form Requests

`packages/aero-hrm/src/Http/Requests/Training/`:

`StoreCourseRequest`:

```php
public function rules(): array
{
    return [
        'category_id'           => ['required', 'exists:training_categories,id'],
        'title'                 => ['required', 'string', 'max:200'],
        'summary'               => ['nullable', 'string', 'max:500'],
        'description'           => ['nullable', 'string'],
        'delivery_mode'         => ['required', 'in:in_person,virtual,self_paced'],
        'duration_minutes'      => ['required', 'integer', 'min:5', 'max:1440'],
        'learning_objectives'   => ['nullable', 'array'],
        'prerequisites'         => ['nullable', 'array'],
        'tags'                  => ['nullable', 'array'],
        'is_mandatory'          => ['boolean'],
        'is_active'             => ['boolean'],
        'materials'             => ['nullable', 'array'],
        'materials.*.kind'      => ['required_with:materials', 'in:link,file'],
        'materials.*.label'     => ['required_with:materials', 'string', 'max:200'],
        'materials.*.url'       => ['required_if:materials.*.kind,link', 'url', 'max:500'],
        'materials.*.file'      => ['required_if:materials.*.kind,file', 'file', 'max:25600'],
    ];
}
```

`StoreSessionRequest`:

```php
public function rules(): array
{
    return [
        'starts_at'     => ['required', 'date', 'after:now'],
        'ends_at'       => ['required', 'date', 'after:starts_at'],
        'location'      => ['nullable', 'string', 'max:200'],
        'meeting_link'  => ['nullable', 'url', 'max:500'],
        'capacity'      => ['required', 'integer', 'min:1', 'max:1000'],
        'instructor_id' => ['nullable', 'exists:users,id'],
        'notes'         => ['nullable', 'string', 'max:1000'],
    ];
}
```

`StoreEnrollmentRequest`:

```php
public function rules(): array
{
    return [
        'session_id'    => ['required', 'exists:training_sessions,id'],
        'employee_ids'  => ['required', 'array', 'min:1'],
        'employee_ids.*'=> ['integer', 'exists:employees,id', 'distinct'],
    ];
}
```

`MarkAttendanceRequest`:

```php
public function rules(): array
{
    return [
        'attendance'        => ['required', 'array', 'min:1'],
        'attendance.*'      => ['required', 'in:attended,no_show'],
    ];
}
```

`StoreFeedbackRequest`:

```php
public function rules(): array
{
    return [
        'content_rating'    => ['required', 'integer', 'between:1,5'],
        'instructor_rating' => ['required', 'integer', 'between:1,5'],
        'overall_rating'    => ['required', 'integer', 'between:1,5'],
        'would_recommend'   => ['boolean'],
        'what_worked'       => ['nullable', 'string', 'max:2000'],
        'what_didnt_work'   => ['nullable', 'string', 'max:2000'],
        'suggestions'       => ['nullable', 'string', 'max:2000'],
    ];
}
```

---

## 9. Routes

`packages/aero-hrm/routes/web.php`:

```php
Route::middleware(['web', 'auth'])
    ->prefix('hrm/training')
    ->name('hrm.training.')
    ->group(function () {
        // Categories
        Route::middleware('hrmac:hrm.training.categories.view')
            ->get('categories', [TrainingCategoryController::class, 'index'])->name('categories.index');
        Route::middleware('hrmac:hrm.training.categories.edit')->group(function () {
            Route::post('categories',                  [TrainingCategoryController::class, 'store'])->name('categories.store');
            Route::patch('categories/{category}',      [TrainingCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}',     [TrainingCategoryController::class, 'destroy'])->name('categories.destroy');
        });

        // Courses
        Route::middleware('hrmac:hrm.training.courses.view')->group(function () {
            Route::get('courses',                  [TrainingCourseController::class, 'index'])->name('courses.index');
            Route::get('courses/{course}',         [TrainingCourseController::class, 'show'])->name('courses.show');
        });
        Route::middleware('hrmac:hrm.training.courses.edit')->group(function () {
            Route::get('courses/create',           [TrainingCourseController::class, 'create'])->name('courses.create');
            Route::post('courses',                 [TrainingCourseController::class, 'store'])->name('courses.store');
            Route::patch('courses/{course}',       [TrainingCourseController::class, 'update'])->name('courses.update');
            Route::delete('courses/{course}',      [TrainingCourseController::class, 'destroy'])->name('courses.destroy');
        });

        // Sessions
        Route::middleware('hrmac:hrm.training.sessions.edit')->group(function () {
            Route::get('courses/{course}/sessions/create', [TrainingSessionController::class, 'create'])->name('sessions.create');
            Route::post('courses/{course}/sessions',       [TrainingSessionController::class, 'store'])->name('sessions.store');
            Route::patch('sessions/{session}',             [TrainingSessionController::class, 'update'])->name('sessions.update');
        });

        // Enrollments
        Route::middleware('hrmac:hrm.training.enrollments.view')
            ->get('enrollments', [TrainingEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::middleware('hrmac:hrm.training.enrollments.edit')->group(function () {
            Route::post('enrollments',                              [TrainingEnrollmentController::class, 'store'])->name('enrollments.store');
            Route::post('sessions/{session}/attendance',            [TrainingEnrollmentController::class, 'markAttendance'])->name('enrollments.attendance');
            Route::delete('enrollments/{enrollment}',               [TrainingEnrollmentController::class, 'cancel'])->name('enrollments.cancel');
        });

        // Feedback (employee scope, route still HRMAC-gated)
        Route::middleware('hrmac:hrm.training.feedback.edit')->group(function () {
            Route::get('feedback/{enrollment}/create', [TrainingFeedbackController::class, 'create'])->name('feedback.create');
            Route::post('feedback/{enrollment}',       [TrainingFeedbackController::class, 'store'])->name('feedback.store');
        });
    });
```

---

## 10. Frontend Pages

Layout pattern:

```jsx
import App from '../../App.jsx';
Page.layout = page => <App title="...">{page}</App>;
```

### 10.1 `HRM/Training/Categories/Index.jsx`

Table of categories with inline modal (HeroUI `Modal`) for create/edit. Submit via `useForm`. Delete confirms. Filter by `search`.

### 10.2 `HRM/Training/Courses/Index.jsx`

Filterable list:

```jsx
import React from 'react';
import { Link, router } from '@inertiajs/react';
import { Button, Card, Chip, Input, Select, SelectItem, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Pagination, Switch } from '@aero/ui';
import { useHRMAC } from '@aero/hrmac';
import App from '../../../App.jsx';

export default function CoursesIndex({ courses, filters, categories }) {
    const { canEdit } = useHRMAC('hrm.training.courses');
    const apply = (next) => router.get(route('hrm.training.courses.index'), { ...filters, ...next }, { preserveState: true, replace: true });

    return (
        <div className="space-y-4 p-6">
            <header className="flex justify-between">
                <h1 className="text-2xl font-semibold">Training Courses</h1>
                {canEdit && <Button as={Link} href={route('hrm.training.courses.create')} color="primary">New Course</Button>}
            </header>

            <Card className="p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                <Input label="Search" defaultValue={filters.search ?? ''}
                       onBlur={(e) => apply({ search: e.target.value || null })} />
                <Select label="Category" selectedKeys={filters.category_id ? [String(filters.category_id)] : []}
                        onChange={(e) => apply({ category_id: e.target.value || null })}>
                    {categories.map(c => <SelectItem key={c.id}>{c.name}</SelectItem>)}
                </Select>
                <Select label="Mode" selectedKeys={filters.mode ? [filters.mode] : []}
                        onChange={(e) => apply({ mode: e.target.value || null })}>
                    <SelectItem key="in_person">In Person</SelectItem>
                    <SelectItem key="virtual">Virtual</SelectItem>
                    <SelectItem key="self_paced">Self-paced</SelectItem>
                </Select>
                <Switch isSelected={!!filters.mandatory}
                        onValueChange={(v) => apply({ mandatory: v ? 1 : null })}>
                    Mandatory only
                </Switch>
            </Card>

            <Card>
                <Table aria-label="Courses">
                    <TableHeader>
                        <TableColumn>TITLE</TableColumn>
                        <TableColumn>CATEGORY</TableColumn>
                        <TableColumn>MODE</TableColumn>
                        <TableColumn>SESSIONS</TableColumn>
                        <TableColumn>UPCOMING</TableColumn>
                    </TableHeader>
                    <TableBody items={courses.data} emptyContent="No courses">
                        {(c) => (
                            <TableRow key={c.id}>
                                <TableCell>
                                    <Link href={route('hrm.training.courses.show', c.id)} className="text-primary hover:underline">
                                        {c.title}
                                    </Link>
                                    {c.is_mandatory && <Chip size="sm" color="warning" className="ml-2">Mandatory</Chip>}
                                </TableCell>
                                <TableCell>{c.category?.name}</TableCell>
                                <TableCell>{c.delivery_mode}</TableCell>
                                <TableCell>{c.sessions_count}</TableCell>
                                <TableCell>{c.upcoming_sessions_count}</TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <div className="p-3 flex justify-end">
                    <Pagination total={courses.last_page} page={courses.current_page} onChange={(p) => apply({ page: p })} />
                </div>
            </Card>
        </div>
    );
}

CoursesIndex.layout = page => <App title="Training Courses">{page}</App>;
```

### 10.3 `HRM/Training/Courses/Create.jsx`

Form with sections: Basics (title, category, summary, mode, duration), Content (description, objectives, prerequisites, tags), Materials (repeater of `{kind, label, url|file}`), Settings (`is_mandatory`, `is_active`). Submits multipart for file uploads.

### 10.4 `HRM/Training/Courses/Show.jsx`

Tabbed layout (HeroUI `Tabs`):

- **Overview** — description, objectives, prerequisites, materials list (clickable links / file downloads).
- **Sessions** — table of all sessions with `Schedule Session` button.
- **Enrollments** — flat list with status chips.
- **Analytics** — average ratings (from feedback) and attendance rate.

### 10.5 `HRM/Training/Sessions/Create.jsx`

Form for `starts_at, ends_at, location, meeting_link, capacity, instructor_id, notes`. Calendar widget (HeroUI `DatePicker`).

### 10.6 `HRM/Training/Enrollments/Index.jsx`

Admin view, filterable by course / session / status. Bulk attendance modal: pick session → load enrolled employees → mark each `attended | no_show` → submit.

### 10.7 `HRM/Training/Feedback/Create.jsx`

Employee-facing form with three 1–5 star ratings (HeroUI `Rating`), `would_recommend` switch, and three text areas. POSTs to `hrm.training.feedback.store`.

---

## 11. Inertia Data Contract

Every list page returns:

- `data` (paginated `data` array of records),
- `filters` (echo back the query string filters),
- supporting selects (`categories`, `statuses`, etc.).

Show pages return the single resource hydrated with required relations only — no `with('*')`.

---

## 12. Tests (`tests/Feature/Training/TrainingFeatureTest.php`)

```php
<?php

namespace Aero\HRM\Tests\Feature\Training;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Aero\HRM\Tests\TestCase;

class TrainingFeatureTest extends TestCase
{
    public function test_admin_can_create_course(): void
    {
        $this->actingAsHRAdmin();
        $cat = TrainingCategory::factory()->create();

        $this->post(route('hrm.training.courses.store'), [
            'category_id'      => $cat->id,
            'title'            => 'Secure Coding 101',
            'delivery_mode'    => 'virtual',
            'duration_minutes' => 90,
        ])->assertRedirect();

        $this->assertDatabaseHas('training_courses', ['title' => 'Secure Coding 101']);
    }

    public function test_admin_can_update_course(): void
    {
        $this->actingAsHRAdmin();
        $course = TrainingCourse::factory()->create();

        $this->patch(route('hrm.training.courses.update', $course), [
            'category_id'      => $course->category_id,
            'title'            => 'Renamed Course',
            'delivery_mode'    => 'in_person',
            'duration_minutes' => 60,
        ])->assertRedirect();

        $this->assertSame('Renamed Course', $course->fresh()->title);
    }

    public function test_course_with_upcoming_sessions_cannot_be_deleted(): void
    {
        $this->actingAsHRAdmin();
        $course = TrainingCourse::factory()->create();
        TrainingSession::factory()->for($course)->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $this->delete(route('hrm.training.courses.destroy', $course))->assertStatus(422);
    }

    public function test_admin_can_enroll_employees(): void
    {
        $this->actingAsHRAdmin();
        $session = TrainingSession::factory()->create(['capacity' => 5]);
        $employees = Employee::factory()->count(3)->create();

        $this->post(route('hrm.training.enrollments.store'), [
            'session_id'   => $session->id,
            'employee_ids' => $employees->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertSame(3, TrainingEnrollment::where('session_id', $session->id)->count());
    }

    public function test_overcapacity_enrollments_go_to_waitlist(): void
    {
        $this->actingAsHRAdmin();
        $session = TrainingSession::factory()->create(['capacity' => 2]);
        $employees = Employee::factory()->count(3)->create();

        $this->post(route('hrm.training.enrollments.store'), [
            'session_id'   => $session->id,
            'employee_ids' => $employees->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertSame(2, TrainingEnrollment::where('session_id', $session->id)->where('status', 'enrolled')->count());
        $this->assertSame(1, TrainingEnrollment::where('session_id', $session->id)->where('status', 'waitlisted')->count());
    }

    public function test_mark_attendance_updates_enrollments(): void
    {
        $this->actingAsHRAdmin();
        $session = TrainingSession::factory()->create();
        $e1 = TrainingEnrollment::factory()->for($session)->create(['status' => 'enrolled']);
        $e2 = TrainingEnrollment::factory()->for($session)->create(['status' => 'enrolled']);

        $this->post(route('hrm.training.enrollments.attendance', $session), [
            'attendance' => [
                $e1->id => 'attended',
                $e2->id => 'no_show',
            ],
        ])->assertRedirect();

        $this->assertSame('attended', $e1->fresh()->status);
        $this->assertSame('no_show',  $e2->fresh()->status);
        $this->assertNotNull($e1->fresh()->attended_at);
    }
}
```

---

## 13. Tasks (sequenced, minimum 6)

1. **Migrations & factories** — create six tables + factories for `TrainingCategory`, `TrainingCourse`, `TrainingSession`, `TrainingEnrollment`, `TrainingFeedback`.
2. **Models, casts, relations** — register media collection `materials` on `TrainingCourse`.
3. **HRMAC config + audit events** — extend `hrmac.php`, add `TrainingAuditEvents`.
4. **Controllers + Form Requests + Services** — five controllers, two services (`CourseService`, `EnrollmentService`).
5. **Routes** — `hrm.training.*` block with per-action HRMAC middleware.
6. **Frontend pages** — seven pages from section 10.
7. **PHPUnit tests** — six methods in `TrainingFeatureTest`; factories for relations; run under `:memory:`.

---

## 14. Acceptance Criteria

- HRMAC denies access without the matching permission.
- Capacity enforcement: enrollment beyond capacity → `waitlisted`; cancelling an enrolled row promotes the first waitlisted row.
- `(session_id, employee_id)` is unique at DB and surfaces a validation error in `StoreEnrollmentRequest`.
- Attendance marking emits `TRAINING_ATTENDANCE_MARKED` for every changed row.
- Feedback creation is rejected unless the caller is the employee on the enrollment AND `status === 'attended'`.
- All six PHPUnit tests pass under sqlite `:memory:`.
