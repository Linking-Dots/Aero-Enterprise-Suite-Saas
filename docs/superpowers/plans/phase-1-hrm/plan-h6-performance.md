# Plan H-6 — Performance Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a full performance subsystem: review cycles with templates, self+manager performance reviews, employee goals (SMART), 360 feedback, calibration sessions (9-box grid), skill matrix, and PIPs (Performance Improvement Plans). All gated by HRMAC and audited.

**Architecture:** Per-domain services (`ReviewCycleService`, `ReviewSubmissionService`, `GoalLifecycleService`, `Feedback360Service`, `PIPService`) keep controllers thin. Templates and rating scales live in `review_templates` (JSON-backed sections + rating scale). Reviews go through a state machine: `draft → self_submitted → manager_submitted → finalized`; once finalized, the review record is read-only at controller + model level. Pages live in `packages/aero-ui/resources/js/Pages/HRM/Performance/` and follow the Inertia flat-props convention.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

**Prerequisite:** Plans A–S + H-4, H-5 merged. Working directory: `c:\laragon\www\Aero-Enterprise-Suite-Saas`.

---

## File Map

| Action | Path |
|--------|------|
| Migration | `packages/aero-hrm/database/migrations/2026_05_17_000020_create_performance_v2_tables.php` |
| Model | `packages/aero-hrm/src/Models/ReviewCycle.php` |
| Model | `packages/aero-hrm/src/Models/ReviewTemplate.php` |
| Model | `packages/aero-hrm/src/Models/PerformanceReview.php` |
| Model | `packages/aero-hrm/src/Models/Goal.php` |
| Model | `packages/aero-hrm/src/Models/Feedback360Request.php` |
| Model | `packages/aero-hrm/src/Models/Feedback360Response.php` (already exists — extend) |
| Model | `packages/aero-hrm/src/Models/CalibrationSession.php` |
| Model | `packages/aero-hrm/src/Models/CalibrationParticipant.php` |
| Model | `packages/aero-hrm/src/Models/PerformanceImprovementPlan.php` (already exists — extend) |
| Service | `packages/aero-hrm/src/Services/Performance/ReviewCycleService.php` |
| Service | `packages/aero-hrm/src/Services/Performance/ReviewSubmissionService.php` |
| Service | `packages/aero-hrm/src/Services/Performance/GoalLifecycleService.php` |
| Service | `packages/aero-hrm/src/Services/Performance/Feedback360Service.php` |
| Service | `packages/aero-hrm/src/Services/Performance/PIPService.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/ReviewCycleController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/PerformanceReviewController.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/GoalController.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Feedback360Controller.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/PerformanceCalibrationController.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/SkillMatrixController.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Performance/PerformanceImprovementPlanController.php` (replace) |
| Routes | `packages/aero-hrm/routes/tenant.php` (extend) |
| Pages | 11 React pages under `packages/aero-ui/resources/js/Pages/HRM/Performance/` |
| Tests | 3 PHPUnit feature test files (6+ methods total) |

---

## Task H6-1: Schema — Performance v2 tables

- [ ] Create `packages/aero-hrm/database/migrations/2026_05_17_000020_create_performance_v2_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_review_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->json('sections');     // [{title, questions: [{q, type:'rating|text', scale_min, scale_max}]}]
            $table->json('rating_scale'); // {min:1,max:5,labels:{1:'Poor',5:'Outstanding'}}
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('template_id')->constrained('hrm_review_templates')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->json('employee_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('hrm_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('hrm_review_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['draft', 'self_submitted', 'manager_submitted', 'finalized'])->default('draft');
            $table->json('self_answers')->nullable();
            $table->json('manager_answers')->nullable();
            $table->decimal('final_rating', 4, 2)->nullable();
            $table->string('final_comment', 2000)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cycle_id', 'employee_id']);
        });

        Schema::create('hrm_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('specific', 500);
            $table->string('measurable', 500);
            $table->string('achievable', 500)->nullable();
            $table->string('relevant', 500)->nullable();
            $table->date('time_bound');
            $table->tinyInteger('progress')->default(0); // 0..100
            $table->enum('status', ['open', 'in_progress', 'achieved', 'missed', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('hrm_feedback_360_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->json('respondent_ids'); // employee ids invited
            $table->date('due_on');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('hrm_calibration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('hrm_review_cycles')->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('grid')->nullable(); // { "employee_id": {x:..., y:...} } 9-box positions
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_calibration_sessions');
        Schema::dropIfExists('hrm_feedback_360_requests');
        Schema::dropIfExists('hrm_goals');
        Schema::dropIfExists('hrm_performance_reviews');
        Schema::dropIfExists('hrm_review_cycles');
        Schema::dropIfExists('hrm_review_templates');
    }
};
```

- [ ] `php artisan migrate`.

---

## Task H6-2: Models with state-machine + finalization guards

- [ ] Create `packages/aero-hrm/src/Models/ReviewTemplate.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;

class ReviewTemplate extends TenantModel
{
    protected $table = 'hrm_review_templates';
    protected $fillable = ['name', 'sections', 'rating_scale', 'active'];
    protected $casts = [
        'sections'     => 'array',
        'rating_scale' => 'array',
        'active'       => 'bool',
    ];
}
```

- [ ] Create `packages/aero-hrm/src/Models/ReviewCycle.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCycle extends TenantModel
{
    protected $table = 'hrm_review_cycles';
    protected $fillable = ['name', 'template_id', 'starts_on', 'ends_on', 'status', 'employee_ids'];
    protected $casts = [
        'starts_on'    => 'date',
        'ends_on'      => 'date',
        'employee_ids' => 'array',
    ];

    public function template(): BelongsTo { return $this->belongsTo(ReviewTemplate::class, 'template_id'); }
    public function reviews(): HasMany    { return $this->hasMany(PerformanceReview::class, 'cycle_id'); }
}
```

- [ ] Create `packages/aero-hrm/src/Models/PerformanceReview.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Aero\Hrm\Exceptions\PerformanceFinalizedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends TenantModel
{
    protected $table = 'hrm_performance_reviews';

    protected $fillable = [
        'cycle_id', 'employee_id', 'manager_id', 'status',
        'self_answers', 'manager_answers',
        'final_rating', 'final_comment', 'finalized_at', 'finalized_by',
    ];

    protected $casts = [
        'self_answers'    => 'array',
        'manager_answers' => 'array',
        'final_rating'    => 'decimal:2',
        'finalized_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $r) {
            if ($r->getOriginal('status') === 'finalized') {
                throw new PerformanceFinalizedException("Review #{$r->id} is finalized.");
            }
        });
    }

    public function cycle(): BelongsTo    { return $this->belongsTo(ReviewCycle::class, 'cycle_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function manager(): BelongsTo  { return $this->belongsTo(Employee::class, 'manager_id'); }
}
```

- [ ] Create `packages/aero-hrm/src/Models/Goal.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends TenantModel
{
    protected $table = 'hrm_goals';
    protected $fillable = [
        'employee_id', 'title', 'specific', 'measurable', 'achievable',
        'relevant', 'time_bound', 'progress', 'status', 'closed_at',
    ];
    protected $casts = ['time_bound' => 'date', 'closed_at' => 'datetime', 'progress' => 'int'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
```

- [ ] Create `packages/aero-hrm/src/Models/Feedback360Request.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback360Request extends TenantModel
{
    protected $table = 'hrm_feedback_360_requests';
    protected $fillable = ['subject_employee_id', 'requester_id', 'respondent_ids', 'due_on', 'status'];
    protected $casts = ['respondent_ids' => 'array', 'due_on' => 'date'];

    public function subject(): BelongsTo { return $this->belongsTo(Employee::class, 'subject_employee_id'); }
}
```

- [ ] Create `packages/aero-hrm/src/Models/CalibrationSession.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;

class CalibrationSession extends TenantModel
{
    protected $table = 'hrm_calibration_sessions';
    protected $fillable = ['cycle_id', 'name', 'grid', 'status'];
    protected $casts = ['grid' => 'array'];
}
```

- [ ] Create exception `packages/aero-hrm/src/Exceptions/PerformanceFinalizedException.php` extending `\DomainException`.

---

## Task H6-3: Services

- [ ] Create `packages/aero-hrm/src/Services/Performance/ReviewCycleService.php`:

```php
<?php

namespace Aero\Hrm\Services\Performance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\PerformanceReview;
use Aero\Hrm\Models\ReviewCycle;
use Illuminate\Support\Facades\DB;

class ReviewCycleService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function activate(ReviewCycle $cycle): ReviewCycle
    {
        DB::transaction(function () use ($cycle) {
            $cycle->update(['status' => 'active']);
            foreach ((array) $cycle->employee_ids as $empId) {
                PerformanceReview::firstOrCreate(
                    ['cycle_id' => $cycle->id, 'employee_id' => $empId],
                    ['status' => 'draft'],
                );
            }
        });

        $this->audit->record('REVIEW_CYCLE_ACTIVATED', 'hrm', 'performance', [
            'cycle_id' => $cycle->id,
            'count'    => count((array) $cycle->employee_ids),
        ]);

        return $cycle->fresh();
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Performance/ReviewSubmissionService.php`:

```php
<?php

namespace Aero\Hrm\Services\Performance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\PerformanceReview;
use Aero\Hrm\Exceptions\PerformanceFinalizedException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReviewSubmissionService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function submitSelf(PerformanceReview $r, array $answers): PerformanceReview
    {
        $this->guardNotFinalized($r);
        $r->update(['self_answers' => $answers, 'status' => 'self_submitted']);
        return $r;
    }

    public function submitManager(PerformanceReview $r, array $answers): PerformanceReview
    {
        $this->guardNotFinalized($r);
        $r->update(['manager_answers' => $answers, 'status' => 'manager_submitted']);
        return $r;
    }

    public function finalize(PerformanceReview $r, float $rating, string $comment): PerformanceReview
    {
        $this->guardNotFinalized($r);
        $r->update([
            'final_rating'  => $rating,
            'final_comment' => $comment,
            'finalized_at'  => Carbon::now(),
            'finalized_by'  => Auth::id(),
            'status'        => 'finalized',
        ]);

        $this->audit->record('PERFORMANCE_REVIEW_FINALIZED', 'hrm', 'performance', [
            'review_id'   => $r->id,
            'employee_id' => $r->employee_id,
            'rating'      => $rating,
        ]);

        return $r;
    }

    private function guardNotFinalized(PerformanceReview $r): void
    {
        if ($r->status === 'finalized') {
            throw new PerformanceFinalizedException("Review #{$r->id} is finalized.");
        }
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Performance/GoalLifecycleService.php`:

```php
<?php

namespace Aero\Hrm\Services\Performance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Goal;
use Illuminate\Support\Carbon;

class GoalLifecycleService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function close(Goal $goal, string $finalStatus): Goal
    {
        if (! in_array($finalStatus, ['achieved', 'missed', 'closed'], true)) {
            throw new \InvalidArgumentException("Invalid close status: {$finalStatus}");
        }

        $goal->update([
            'status'    => $finalStatus,
            'closed_at' => Carbon::now(),
            'progress'  => $finalStatus === 'achieved' ? 100 : $goal->progress,
        ]);

        $this->audit->record('GOAL_CLOSED', 'hrm', 'performance', [
            'goal_id'    => $goal->id,
            'employee_id'=> $goal->employee_id,
            'outcome'    => $finalStatus,
        ]);

        return $goal;
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Performance/Feedback360Service.php`:

```php
<?php

namespace Aero\Hrm\Services\Performance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Feedback360Request;
use Aero\Hrm\Notifications\Feedback360Invitation;
use Illuminate\Support\Facades\Notification;

class Feedback360Service
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function open(array $payload): Feedback360Request
    {
        $req = Feedback360Request::create($payload + ['status' => 'open']);

        // Notify respondents
        $users = \Aero\Hrm\Models\Employee::whereIn('id', $req->respondent_ids)->with('user')->get()->pluck('user')->filter();
        Notification::send($users, new Feedback360Invitation($req));

        $this->audit->record('FEEDBACK_360_OPENED', 'hrm', 'performance', [
            'request_id' => $req->id,
            'subject'    => $req->subject_employee_id,
            'respondents'=> count($req->respondent_ids),
        ]);

        return $req;
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Performance/PIPService.php`:

```php
<?php

namespace Aero\Hrm\Services\Performance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\PerformanceImprovementPlan;

class PIPService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function create(array $payload): PerformanceImprovementPlan
    {
        $pip = PerformanceImprovementPlan::create($payload);

        $this->audit->record('PIP_CREATED', 'hrm', 'performance', [
            'pip_id'     => $pip->id,
            'employee_id'=> $pip->employee_id,
        ]);

        return $pip;
    }
}
```

---

## Task H6-4: Controllers

- [ ] Create `packages/aero-hrm/src/Http/Controllers/Performance/ReviewCycleController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Performance;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\ReviewCycle;
use Aero\Hrm\Models\ReviewTemplate;
use Aero\Hrm\Services\Performance\ReviewCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReviewCycleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.view');

        $cycles = ReviewCycle::with('template:id,name')
            ->latest('starts_on')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Performance/Cycles/Index', [
            'cycles'  => $cycles,
            'filters' => [],
        ]);
    }

    public function create()
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.update');

        return Inertia::render('HRM/Performance/Cycles/Create', [
            'templates' => ReviewTemplate::where('active', true)->select('id', 'name')->get(),
            'employees' => Employee::where('status', 'active')->select('id', 'first_name', 'last_name')->get(),
        ]);
    }

    public function store(Request $request, ReviewCycleService $svc)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.update');

        $data = $request->validate([
            'name'         => 'required|string|max:120',
            'template_id'  => 'required|exists:hrm_review_templates,id',
            'starts_on'    => 'required|date',
            'ends_on'      => 'required|date|after_or_equal:starts_on',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'activate_now' => 'boolean',
        ]);

        $cycle = ReviewCycle::create($data + ['status' => 'draft']);
        if (! empty($data['activate_now'])) {
            $svc->activate($cycle);
        }

        return redirect()->route('hrm.performance.cycles.index')
            ->with('success', 'Review cycle created.');
    }
}
```

- [ ] Replace `packages/aero-hrm/src/Http/Controllers/Performance/PerformanceReviewController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Performance;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\PerformanceReview;
use Aero\Hrm\Services\Performance\ReviewSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PerformanceReviewController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.view');

        $reviews = PerformanceReview::with(['employee:id,first_name,last_name', 'cycle:id,name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Performance/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => ['status' => $request->input('status')],
        ]);
    }

    public function show(PerformanceReview $review)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.view');

        return Inertia::render('HRM/Performance/Reviews/Show', [
            'review'   => $review->load('employee', 'cycle.template'),
            'template' => $review->cycle->template,
        ]);
    }

    public function submitSelf(Request $request, PerformanceReview $review, ReviewSubmissionService $svc)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.update');
        $data = $request->validate(['answers' => 'required|array']);
        $svc->submitSelf($review, $data['answers']);
        return back()->with('success', 'Self-assessment submitted.');
    }

    public function submitManager(Request $request, PerformanceReview $review, ReviewSubmissionService $svc)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.update');
        $data = $request->validate(['answers' => 'required|array']);
        $svc->submitManager($review, $data['answers']);
        return back()->with('success', 'Manager assessment submitted.');
    }

    public function finalize(Request $request, PerformanceReview $review, ReviewSubmissionService $svc)
    {
        Gate::authorize('hrmac', 'hrm.performance.appraisal-cycles.update');
        $data = $request->validate([
            'final_rating'  => 'required|numeric|min:1|max:5',
            'final_comment' => 'required|string|max:2000',
        ]);
        $svc->finalize($review, (float) $data['final_rating'], $data['final_comment']);
        return back()->with('success', 'Review finalized.');
    }
}
```

- [ ] Replace `packages/aero-hrm/src/Http/Controllers/Performance/GoalController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Performance;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Goal;
use Aero\Hrm\Services\Performance\GoalLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.performance.goals.view');

        $employee = $request->user()->employee;
        $isAdmin  = $request->boolean('admin') && Gate::check('hrmac', 'hrm.performance.goals.edit');

        $goals = Goal::query()
            ->when(! $isAdmin, fn ($q) => $q->where('employee_id', $employee?->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Performance/Goals/Index', [
            'goals'    => $goals,
            'isAdmin'  => $isAdmin,
            'filters'  => ['admin' => $isAdmin],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.performance.goals.edit');

        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'specific'   => 'required|string|max:500',
            'measurable' => 'required|string|max:500',
            'achievable' => 'nullable|string|max:500',
            'relevant'   => 'nullable|string|max:500',
            'time_bound' => 'required|date|after:today',
        ]);
        $data['employee_id'] = $request->user()->employee->id;
        Goal::create($data);

        return redirect()->route('hrm.performance.goals.index')->with('success', 'Goal created.');
    }

    public function update(Request $request, Goal $goal)
    {
        Gate::authorize('hrmac', 'hrm.performance.goals.edit');
        $data = $request->validate([
            'progress' => 'sometimes|integer|min:0|max:100',
            'status'   => 'sometimes|in:open,in_progress',
        ]);
        $goal->update($data);
        return back();
    }

    public function close(Request $request, Goal $goal, GoalLifecycleService $svc)
    {
        Gate::authorize('hrmac', 'hrm.performance.goals.edit');
        $data = $request->validate(['outcome' => 'required|in:achieved,missed,closed']);
        $svc->close($goal, $data['outcome']);
        return back()->with('success', 'Goal closed.');
    }
}
```

- [ ] Replace remaining controllers (`Feedback360Controller`, `PerformanceCalibrationController`, `SkillMatrixController`, `PerformanceImprovementPlanController`) with HRMAC gate + service delegation following the same pattern.

---

## Task H6-5: Routes + module.php

- [ ] Append to `packages/aero-hrm/routes/tenant.php`:

```php
Route::prefix('performance')->name('performance.')->group(function () {
    Route::resource('cycles', ReviewCycleController::class)
        ->only(['index', 'create', 'store'])
        ->middleware('hrmac:hrm.performance.appraisal-cycles.view');

    Route::get('reviews',                 [PerformanceReviewController::class, 'index'])->middleware('hrmac:hrm.performance.appraisal-cycles.view')  ->name('reviews.index');
    Route::get('reviews/{review}',        [PerformanceReviewController::class, 'show'])->middleware('hrmac:hrm.performance.appraisal-cycles.view')   ->name('reviews.show');
    Route::post('reviews/{review}/self',  [PerformanceReviewController::class, 'submitSelf'])->middleware('hrmac:hrm.performance.appraisal-cycles.update')->name('reviews.self');
    Route::post('reviews/{review}/manager',[PerformanceReviewController::class, 'submitManager'])->middleware('hrmac:hrm.performance.appraisal-cycles.update')->name('reviews.manager');
    Route::post('reviews/{review}/finalize', [PerformanceReviewController::class, 'finalize'])->middleware('hrmac:hrm.performance.appraisal-cycles.update')->name('reviews.finalize');

    Route::resource('goals', GoalController::class)->middleware('hrmac:hrm.performance.goals.view');
    Route::post('goals/{goal}/close', [GoalController::class, 'close'])->middleware('hrmac:hrm.performance.goals.edit')->name('goals.close');

    Route::get('feedback-360',  [Feedback360Controller::class, 'index'])->middleware('hrmac:hrm.feedback-360.feedback-reviews.view')->name('feedback-360.index');
    Route::post('feedback-360', [Feedback360Controller::class, 'store'])->middleware('hrmac:hrm.feedback-360.feedback-reviews.update')->name('feedback-360.store');
    Route::post('feedback-360/{request}/respond', [Feedback360Controller::class, 'respond'])->middleware('hrmac:hrm.feedback-360.feedback-reviews.view')->name('feedback-360.respond');

    Route::get('calibration',  [PerformanceCalibrationController::class, 'index'])->middleware('hrmac:hrm.performance.calibration.view')->name('calibration.index');
    Route::put('calibration/{session}', [PerformanceCalibrationController::class, 'update'])->middleware('hrmac:hrm.performance.calibration.manage')->name('calibration.update');

    Route::get('skills/matrix', [SkillMatrixController::class, 'matrix'])->middleware('hrmac:hrm.performance.skill-matrix.view')->name('skills.matrix');

    Route::get('pip',         [PerformanceImprovementPlanController::class, 'index'])->middleware('hrmac:hrm.performance.improvement_plans.view')->name('pip.index');
    Route::get('pip/create',  [PerformanceImprovementPlanController::class, 'create'])->middleware('hrmac:hrm.performance.improvement_plans.update')->name('pip.create');
    Route::post('pip',        [PerformanceImprovementPlanController::class, 'store'])->middleware('hrmac:hrm.performance.improvement_plans.update')->name('pip.store');
});
```

- [ ] Update `packages/aero-hrm/config/module.php`:

```php
'performance' => [
    'label' => 'Performance',
    'components' => [
        'cycles'        => ['actions' => ['view', 'edit']],
        'reviews'       => ['actions' => ['view', 'edit', 'finalize']],
        'goals'         => ['actions' => ['view', 'edit']],
        'feedback-360'  => ['actions' => ['view', 'edit']],
        'calibration'   => ['actions' => ['view', 'edit']],
        'skills'        => ['actions' => ['view', 'edit']],
        'pip'           => ['actions' => ['view', 'edit']],
    ],
],
```

- [ ] `php artisan hrmac:sync`.

---

## Task H6-6: Frontend pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Performance/Reviews/Show.jsx`:

```jsx
import { useForm, router } from '@inertiajs/react';
import { DetailPageLayout, Card, Button, Badge, RatingScale, Textarea, VStack, HStack, Input } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function ReviewShow({ review, template }) {
  const canFinalize = useHRMAC('hrm.performance.appraisal-cycles.update');
  const isFinalized = review.status === 'finalized';

  const selfForm    = useForm({ answers: review.self_answers    ?? {} });
  const managerForm = useForm({ answers: review.manager_answers ?? {} });
  const finalForm   = useForm({ final_rating: review.final_rating ?? 3, final_comment: review.final_comment ?? '' });

  const finalize = (e) => {
    e.preventDefault();
    if (!window.confirm('Finalize this review? It will become read-only.')) return;
    finalForm.post(route('hrm.performance.reviews.finalize', review.id));
  };

  return (
    <App>
      <DetailPageLayout
        title={`Review: ${review.employee.first_name} ${review.employee.last_name}`}
        meta={<Badge variant={isFinalized ? 'success' : 'neutral'}>{review.status}</Badge>}
      >
        <Card title="Self assessment">
          {template.sections.map((sec, i) => (
            <VStack key={i} gap={3}>
              <h3>{sec.title}</h3>
              {sec.questions.map((q, j) => (
                <div key={j}>
                  <label>{q.q}</label>
                  {q.type === 'rating'
                    ? <RatingScale value={selfForm.data.answers[`${i}.${j}`] ?? 0} onChange={(v) => selfForm.setData('answers', { ...selfForm.data.answers, [`${i}.${j}`]: v })} disabled={isFinalized} />
                    : <Textarea value={selfForm.data.answers[`${i}.${j}`] ?? ''} onChange={(e) => selfForm.setData('answers', { ...selfForm.data.answers, [`${i}.${j}`]: e.target.value })} disabled={isFinalized} />}
                </div>
              ))}
            </VStack>
          ))}
          {!isFinalized && <Button onClick={() => selfForm.post(route('hrm.performance.reviews.self', review.id))}>Submit self assessment</Button>}
        </Card>

        <Card title="Manager assessment">
          {/* same pattern reading/writing managerForm */}
          {!isFinalized && <Button onClick={() => managerForm.post(route('hrm.performance.reviews.manager', review.id))}>Submit manager assessment</Button>}
        </Card>

        {canFinalize && !isFinalized && (
          <Card title="Finalize">
            <form onSubmit={finalize}>
              <HStack gap={3}>
                <Input type="number" min={1} max={5} step={0.1} value={finalForm.data.final_rating} onChange={(e) => finalForm.setData('final_rating', e.target.value)} />
                <Textarea value={finalForm.data.final_comment} onChange={(e) => finalForm.setData('final_comment', e.target.value)} />
                <Button type="submit" loading={finalForm.processing}>Finalize</Button>
              </HStack>
            </form>
          </Card>
        )}
      </DetailPageLayout>
    </App>
  );
}

ReviewShow.layout = page => <App title="Performance Review">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Performance/Goals/Index.jsx`:

```jsx
import { Link, router } from '@inertiajs/react';
import { IndexPageLayout, DataTable, Button, Badge, Progress, HStack } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function GoalsIndex({ goals, isAdmin }) {
  const canEdit = useHRMAC('hrm.performance.goals.edit');

  const close = (id, outcome) => router.post(route('hrm.performance.goals.close', id), { outcome });

  const columns = [
    { header: 'Title',   accessor: 'title' },
    { header: 'Target',  accessor: 'time_bound' },
    { header: 'Progress',accessor: g => <Progress value={g.progress} /> },
    { header: 'Status',  accessor: g => <Badge>{g.status}</Badge> },
    canEdit && { header: '', accessor: g => g.status !== 'closed' && (
      <HStack gap={1}>
        <Button size="xs" onClick={() => close(g.id, 'achieved')}>Achieved</Button>
        <Button size="xs" variant="danger" onClick={() => close(g.id, 'missed')}>Missed</Button>
      </HStack>
    )},
  ].filter(Boolean);

  return (
    <App>
      <IndexPageLayout
        title={isAdmin ? 'Team Goals' : 'My Goals'}
        actions={canEdit && <Link href={route('hrm.performance.goals.create')}><Button>New Goal</Button></Link>}
      >
        <DataTable columns={columns} data={goals.data} pagination={goals} />
      </IndexPageLayout>
    </App>
  );
}

GoalsIndex.layout = page => <App title="Goals">{page}</App>;
```

- [ ] Create the remaining pages following the project's existing patterns:
  - `Cycles/Index.jsx`, `Cycles/Create.jsx`
  - `Reviews/Index.jsx`
  - `Goals/Create.jsx` (SMART form with all five fields)
  - `Feedback360/Index.jsx` (request list + respond modal)
  - `Calibration/Index.jsx` (3×3 grid with employee chips draggable into cells)
  - `Skills/Matrix.jsx` (employee × skill heat map)
  - `PIP/Index.jsx`, `PIP/Create.jsx`

---

## Task H6-7: PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Performance/ReviewCycleTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Performance;

use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\PerformanceReview;
use Aero\Hrm\Models\ReviewCycle;
use Aero\Hrm\Models\ReviewTemplate;
use Aero\Hrm\Services\Performance\ReviewCycleService;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_activating_cycle_creates_reviews_for_each_employee(): void
    {
        $tpl = ReviewTemplate::factory()->create();
        $employees = Employee::factory()->count(3)->create();
        $cycle = ReviewCycle::create([
            'name' => 'Q2 2026', 'template_id' => $tpl->id,
            'starts_on' => '2026-04-01', 'ends_on' => '2026-06-30',
            'status' => 'draft', 'employee_ids' => $employees->pluck('id')->all(),
        ]);

        app(ReviewCycleService::class)->activate($cycle);

        $this->assertSame('active', $cycle->fresh()->status);
        $this->assertSame(3, PerformanceReview::where('cycle_id', $cycle->id)->count());
    }
}
```

- [ ] Create `packages/aero-hrm/tests/Feature/Performance/ReviewSubmissionTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Performance;

use Aero\Hrm\Models\PerformanceReview;
use Aero\Hrm\Services\Performance\ReviewSubmissionService;
use Aero\Hrm\Exceptions\PerformanceFinalizedException;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_submission_transitions_status(): void
    {
        $r = PerformanceReview::factory()->create(['status' => 'draft']);
        app(ReviewSubmissionService::class)->submitSelf($r, ['0.0' => 4]);

        $this->assertSame('self_submitted', $r->fresh()->status);
    }

    public function test_finalize_locks_review(): void
    {
        $r = PerformanceReview::factory()->create(['status' => 'manager_submitted']);
        app(ReviewSubmissionService::class)->finalize($r, 4.2, 'Strong year.');

        $this->assertSame('finalized', $r->fresh()->status);
        $this->assertNotNull($r->fresh()->finalized_at);
    }

    public function test_cannot_modify_finalized_review(): void
    {
        $r = PerformanceReview::factory()->create(['status' => 'finalized']);

        $this->expectException(PerformanceFinalizedException::class);
        $r->update(['final_comment' => 'changed']);
    }
}
```

- [ ] Create `packages/aero-hrm/tests/Feature/Performance/GoalAndFeedbackTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Performance;

use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\Feedback360Request;
use Aero\Hrm\Models\Goal;
use Aero\Hrm\Services\Performance\Feedback360Service;
use Aero\Hrm\Services\Performance\GoalLifecycleService;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class GoalAndFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_can_be_closed_as_achieved(): void
    {
        $goal = Goal::factory()->create(['status' => 'in_progress', 'progress' => 80]);
        app(GoalLifecycleService::class)->close($goal, 'achieved');

        $this->assertSame('achieved', $goal->fresh()->status);
        $this->assertSame(100, $goal->fresh()->progress);
        $this->assertNotNull($goal->fresh()->closed_at);
    }

    public function test_360_feedback_request_notifies_respondents(): void
    {
        Notification::fake();
        $subject = Employee::factory()->create();
        $respondents = Employee::factory()->count(3)->withUser()->create();

        app(Feedback360Service::class)->open([
            'subject_employee_id' => $subject->id,
            'requester_id'        => $subject->user->id,
            'respondent_ids'      => $respondents->pluck('id')->all(),
            'due_on'              => now()->addDays(14)->toDateString(),
        ]);

        $this->assertSame(1, Feedback360Request::count());
        Notification::assertCount(3);
    }
}
```

- [ ] Run: `../../vendor/bin/phpunit --filter=Performance` — all 6+ tests green.

---

## Task H6-8: Commit

- [ ] Stage and commit:

```powershell
git add packages/aero-hrm packages/aero-ui/resources/js/Pages/HRM/Performance
git commit -m "feat(hrm): Plan H-6 Performance — cycles, reviews, goals, 360 feedback, calibration, PIP

- ReviewCycleService activation: creates draft PerformanceReview per employee
- ReviewSubmissionService: draft → self_submitted → manager_submitted → finalized state machine
- Finalized reviews are immutable (model boot guard + finalize HRMAC)
- GoalLifecycleService.close emits GOAL_CLOSED audit event
- Feedback360Service notifies respondents via Notification
- PIPService.create emits PIP_CREATED audit event
- HRMAC paths: hrm.performance.{appraisal-cycles,goals,calibration,skill-matrix,improvement_plans}, hrm.feedback-360.feedback-reviews
- 6+ PHPUnit feature tests"
```

---

## Acceptance Criteria

- Every route carries `hrmac:` middleware
- Review state machine prevents jumping states; finalized review cannot be updated (DB-level guard + 403 at controller)
- `final_rating` validated between 1.0 and 5.0
- 360 feedback `Feedback360Invitation` notification fires for every respondent
- Goal close requires `outcome in {achieved, missed, closed}` and stamps `closed_at`
- All 6+ PHPUnit tests green; Playwright happy path `tests/e2e/performance.spec.ts` covers cycle activation → submit self → submit manager → finalize
