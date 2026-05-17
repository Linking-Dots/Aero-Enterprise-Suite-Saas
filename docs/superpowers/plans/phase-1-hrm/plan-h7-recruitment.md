# Plan H-7 — HRM Recruitment Module

**Status:** Ready for implementation
**Owner:** HRM squad
**Depends on:** `aero-hrm` core, `aero-hrmac`, `aero-ui`, AuditServiceInterface, EncryptedField cast
**Package:** `packages/aero-hrm/`
**Pages root:** `packages/aero-ui/resources/js/Pages/HRM/Recruitment/`
**Route prefix:** `/hrm/recruitment`
**Route name prefix:** `hrm.recruitment.*`

---

## 1. Goal

Deliver an end-to-end Recruitment workflow:

1. **Job postings** — full CRUD with `draft → open → closed` lifecycle, publish/close actions.
2. **Applications kanban** — pipeline stages `Applied → Screening → Interview → Offer → Hired/Rejected`, drag-and-drop stage moves, reject with reason.
3. **Interview scheduling** — schedule, reschedule, cancel; multi-interviewer panel; auto-notify.
4. **Offer letters** — create, render PDF, send, track acceptance.
5. **Onboarding wizard** — triggered when an application reaches the `Hired` stage; produces an `Employee` record plus a checklist of OnboardingSteps.

Every route is gated by **HRMAC** middleware. Every business action calls **`AuditServiceInterface`**. Any PII (applicant phone, address, expected salary) is persisted through the **EncryptedField** cast.

---

## 2. Non-Goals

- Public-facing careers site (lives in `aero-marketing`, separate plan).
- Resume parsing / AI-assisted screening (Phase 2).
- Background checks integration (Phase 2).
- Mass email campaigns (Phase 2).

---

## 3. HRMAC Permission Map

Register in `packages/aero-hrm/config/hrmac.php` under `modules.hrm.submodules.recruitment`:

```php
'recruitment' => [
    'label' => 'Recruitment',
    'components' => [
        'jobs' => [
            'label' => 'Job Postings',
            'actions' => ['view', 'edit', 'publish'],
        ],
        'applications' => [
            'label' => 'Applications',
            'actions' => ['view', 'edit'],
        ],
        'interviews' => [
            'label' => 'Interviews',
            'actions' => ['view', 'edit'],
        ],
        'offers' => [
            'label' => 'Offers',
            'actions' => ['view', 'edit'],
        ],
        'onboarding' => [
            'label' => 'Onboarding',
            'actions' => ['view', 'edit'],
        ],
    ],
],
```

Middleware on every route, e.g.:

```php
Route::middleware('hrmac:hrm.recruitment.jobs.view')
    ->get('/recruitment/jobs', [JobController::class, 'index'])
    ->name('hrm.recruitment.jobs.index');
```

---

## 4. Audit Events

Defined in `packages/aero-hrm/src/Audit/RecruitmentAuditEvents.php`:

```php
final class RecruitmentAuditEvents
{
    public const JOB_CREATED              = 'recruitment.job.created';
    public const JOB_UPDATED              = 'recruitment.job.updated';
    public const JOB_PUBLISHED            = 'recruitment.job.published';
    public const JOB_CLOSED               = 'recruitment.job.closed';
    public const APPLICATION_RECEIVED     = 'recruitment.application.received';
    public const APPLICATION_STAGE_CHANGED = 'recruitment.application.stage_changed';
    public const APPLICATION_REJECTED     = 'recruitment.application.rejected';
    public const INTERVIEW_SCHEDULED      = 'recruitment.interview.scheduled';
    public const INTERVIEW_RESCHEDULED    = 'recruitment.interview.rescheduled';
    public const OFFER_SENT               = 'recruitment.offer.sent';
    public const OFFER_ACCEPTED           = 'recruitment.offer.accepted';
    public const EMPLOYEE_ONBOARDED       = 'recruitment.onboarding.completed';
}
```

---

## 5. Data Model (existing models reused, deltas only)

Reused: `Job`, `JobApplication`, `JobHiringStage`, `JobApplicationStageHistory`, `JobInterview`, `JobInterviewFeedback`, `JobOffer`, `OnboardingStep`, `Employee`.

### 5.1 Migration deltas

`packages/aero-hrm/database/migrations/2026_05_17_000001_recruitment_h7_alterations.php`:

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('job_offers', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
            $table->string('signed_document_path')->nullable()->after('offer_letter_path');
        });

        Schema::create('onboarding_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->json('checklist')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_runs');
        Schema::table('job_offers', fn (Blueprint $t) => $t->dropColumn(['accepted_at', 'declined_at', 'signed_document_path']));
        Schema::table('job_applications', fn (Blueprint $t) => $t->dropColumn(['rejection_reason', 'rejected_at', 'rejected_by']));
    }
};
```

### 5.2 Encrypted fields

Add to `JobApplication`:

```php
protected $casts = [
    'phone'            => EncryptedField::class,
    'address'          => EncryptedField::class,
    'expected_salary'  => EncryptedField::class,
    'skills'           => 'array',
    'custom_fields'    => 'array',
    'application_date' => 'datetime',
    'last_status_change' => 'datetime',
];
```

---

## 6. Backend — Controllers (split from monolithic `RecruitmentController`)

All controllers extend `Aero\HRM\Http\Controllers\Controller` and inject `AuditServiceInterface` via constructor.

### 6.1 `JobController`

`packages/aero-hrm/src/Http/Controllers/Recruitment/JobController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreJobRequest;
use Aero\HRM\Http\Requests\Recruitment\UpdateJobRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Job;
use Aero\HRM\Services\Recruitment\JobLifecycleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly JobLifecycleService $lifecycle,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'department_id', 'type']);

        $jobs = Job::query()
            ->with(['department', 'hiringManager'])
            ->withCount(['applications'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('posting_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HRM/Recruitment/Jobs/Index', [
            'jobs'        => $jobs,
            'filters'     => $filters,
            'departments' => Department::select('id', 'name')->get(),
            'statuses'    => Job::STATUSES,
            'types'       => Job::TYPES,
        ]);
    }

    public function create()
    {
        return Inertia::render('HRM/Recruitment/Jobs/Create', [
            'departments' => Department::select('id', 'name')->get(),
            'statuses'    => Job::STATUSES,
            'types'       => Job::TYPES,
        ]);
    }

    public function store(StoreJobRequest $request)
    {
        $job = $this->lifecycle->create($request->validated(), $request->user());

        $this->audit->record(RecruitmentAuditEvents::JOB_CREATED, $job, [
            'title' => $job->title, 'status' => $job->status,
        ]);

        return redirect()
            ->route('hrm.recruitment.jobs.show', $job)
            ->with('success', 'Job created.');
    }

    public function show(Job $job)
    {
        $job->load(['department', 'hiringManager', 'hiringStages']);

        $applicationsByStage = $this->lifecycle->kanbanBuckets($job);

        return Inertia::render('HRM/Recruitment/Jobs/Show', [
            'job'                 => $job,
            'hiringStages'        => $job->hiringStages,
            'applicationsByStage' => $applicationsByStage,
            'metrics'             => $this->lifecycle->metrics($job),
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job)
    {
        $this->lifecycle->update($job, $request->validated());

        $this->audit->record(RecruitmentAuditEvents::JOB_UPDATED, $job, $request->validated());

        return back()->with('success', 'Job updated.');
    }

    public function publish(Job $job)
    {
        $this->lifecycle->publish($job);

        $this->audit->record(RecruitmentAuditEvents::JOB_PUBLISHED, $job, [
            'posting_date' => $job->posting_date?->toIso8601String(),
        ]);

        return back()->with('success', 'Job published.');
    }

    public function close(Job $job)
    {
        $this->lifecycle->close($job);

        $this->audit->record(RecruitmentAuditEvents::JOB_CLOSED, $job, [
            'closing_date' => $job->closing_date?->toIso8601String(),
        ]);

        return back()->with('success', 'Job closed.');
    }
}
```

### 6.2 `ApplicationController`

`packages/aero-hrm/src/Http/Controllers/Recruitment/ApplicationController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\MoveStageRequest;
use Aero\HRM\Http\Requests\Recruitment\RejectApplicationRequest;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Services\Recruitment\ApplicationPipelineService;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly ApplicationPipelineService $pipeline,
    ) {}

    public function show(JobApplication $application)
    {
        $application->load([
            'job.hiringStages',
            'applicant',
            'currentStage',
            'stageHistory.fromStage',
            'stageHistory.toStage',
            'stageHistory.mover',
            'interviews.interviewers',
            'offers',
        ]);

        return Inertia::render('HRM/Recruitment/Applications/Show', [
            'application' => $application,
            'resume'      => $application->getFirstMedia('resumes'),
            'timeline'    => $this->pipeline->timeline($application),
        ]);
    }

    public function moveStage(MoveStageRequest $request, JobApplication $application)
    {
        $fromStageId = $application->current_stage_id;

        $this->pipeline->moveStage(
            $application,
            $request->integer('stage_id'),
            $request->string('notes')->toString(),
            $request->user(),
        );

        $this->audit->record(
            RecruitmentAuditEvents::APPLICATION_STAGE_CHANGED,
            $application,
            ['from' => $fromStageId, 'to' => $application->current_stage_id],
        );

        return back()->with('success', 'Stage updated.');
    }

    public function reject(RejectApplicationRequest $request, JobApplication $application)
    {
        $this->pipeline->reject(
            $application,
            $request->string('reason')->toString(),
            $request->user(),
        );

        $this->audit->record(RecruitmentAuditEvents::APPLICATION_REJECTED, $application, [
            'reason' => $request->string('reason')->toString(),
        ]);

        return back()->with('success', 'Application rejected.');
    }
}
```

### 6.3 `InterviewController`

`packages/aero-hrm/src/Http/Controllers/Recruitment/InterviewController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreInterviewRequest;
use Aero\HRM\Http\Requests\Recruitment\UpdateInterviewRequest;
use Aero\HRM\Models\JobInterview;
use Aero\HRM\Services\Recruitment\InterviewScheduler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly InterviewScheduler $scheduler,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['from', 'to', 'status', 'job_id']);

        $interviews = JobInterview::query()
            ->with(['application.job', 'application.applicant', 'interviewers'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('scheduled_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('scheduled_at', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Recruitment/Interviews/Index', [
            'interviews' => $interviews,
            'filters'    => $filters,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('HRM/Recruitment/Interviews/Create', [
            'application_id' => $request->integer('application_id'),
            'interviewers'   => $this->scheduler->availableInterviewers(),
            'types'          => InterviewScheduler::TYPES,
        ]);
    }

    public function store(StoreInterviewRequest $request)
    {
        $interview = $this->scheduler->schedule($request->validated(), $request->user());

        $this->audit->record(RecruitmentAuditEvents::INTERVIEW_SCHEDULED, $interview, [
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'interviewers' => $interview->interviewers->pluck('id')->all(),
        ]);

        return redirect()
            ->route('hrm.recruitment.interviews.index')
            ->with('success', 'Interview scheduled.');
    }

    public function update(UpdateInterviewRequest $request, JobInterview $interview)
    {
        $this->scheduler->update($interview, $request->validated());

        $this->audit->record(RecruitmentAuditEvents::INTERVIEW_RESCHEDULED, $interview, $request->validated());

        return back()->with('success', 'Interview updated.');
    }
}
```

### 6.4 `OfferController`

`packages/aero-hrm/src/Http/Controllers/Recruitment/OfferController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreOfferRequest;
use Aero\HRM\Models\JobOffer;
use Aero\HRM\Services\Recruitment\OfferService;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly OfferService $offers,
    ) {}

    public function create()
    {
        return Inertia::render('HRM/Recruitment/Offers/Create', [
            'templates' => $this->offers->templates(),
        ]);
    }

    public function store(StoreOfferRequest $request)
    {
        $offer = $this->offers->extend($request->validated(), $request->user());

        $this->audit->record(RecruitmentAuditEvents::OFFER_SENT, $offer, [
            'application_id' => $offer->application_id,
            'salary'         => $offer->offered_salary,
        ]);

        return redirect()
            ->route('hrm.recruitment.offers.show', $offer)
            ->with('success', 'Offer sent.');
    }

    public function show(JobOffer $offer)
    {
        $offer->load(['application.job', 'application.applicant']);

        return Inertia::render('HRM/Recruitment/Offers/Show', [
            'offer' => $offer,
        ]);
    }
}
```

### 6.5 `OnboardingController`

`packages/aero-hrm/src/Http/Controllers/Recruitment/OnboardingController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreOnboardingRunRequest;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\OnboardingRun;
use Aero\HRM\Services\Recruitment\OnboardingService;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly OnboardingService $onboarding,
    ) {}

    public function create(JobApplication $application)
    {
        return Inertia::render('HRM/Recruitment/Onboarding/Create', [
            'application' => $application->load('job.department', 'applicant'),
            'templates'   => $this->onboarding->templates(),
        ]);
    }

    public function store(StoreOnboardingRunRequest $request, JobApplication $application)
    {
        $run = $this->onboarding->kickoff($application, $request->validated(), $request->user());

        return redirect()
            ->route('hrm.recruitment.applications.show', $application)
            ->with('success', 'Onboarding kicked off.');
    }

    public function complete(OnboardingRun $run)
    {
        $employee = $this->onboarding->complete($run);

        $this->audit->record(RecruitmentAuditEvents::EMPLOYEE_ONBOARDED, $employee, [
            'application_id' => $run->application_id,
            'run_id'         => $run->id,
        ]);

        return redirect()
            ->route('hrm.employees.show', $employee)
            ->with('success', 'Employee onboarded.');
    }
}
```

---

## 7. Services

Thin controllers; logic in services.

- `JobLifecycleService` — create, update, publish, close; seeds default stages; computes kanban buckets and metrics.
- `ApplicationPipelineService` — `moveStage`, `reject`, `timeline`; writes `JobApplicationStageHistory`.
- `InterviewScheduler` — schedule/reschedule, interviewer conflict checks, notification dispatch.
- `OfferService` — render letter from template, persist `JobOffer`, mark `application.status = 'offered'`, advance stage.
- `OnboardingService` — `kickoff` creates `OnboardingRun` from a template; `complete` creates `Employee` from `JobApplication`, copies encrypted PII, fires `EmployeeOnboarded` event.

All write methods accept the acting `User` and update `*_by` columns through a single `Audit` helper.

---

## 8. Form Requests

`packages/aero-hrm/src/Http/Requests/Recruitment/`:

- `StoreJobRequest`, `UpdateJobRequest` — same rules as legacy controller, plus `description` markdown sanitiser.
- `MoveStageRequest`:

```php
public function rules(): array
{
    return [
        'stage_id' => ['required', 'exists:job_hiring_stages,id'],
        'notes'    => ['nullable', 'string', 'max:1000'],
    ];
}
```

- `RejectApplicationRequest`:

```php
public function rules(): array
{
    return [
        'reason' => ['required', 'string', 'min:5', 'max:2000'],
    ];
}
```

- `StoreInterviewRequest`, `UpdateInterviewRequest`, `StoreOfferRequest`, `StoreOnboardingRunRequest` — full validation tables.

---

## 9. Routes

`packages/aero-hrm/routes/web.php` (additive):

```php
Route::middleware(['web', 'auth'])
    ->prefix('hrm/recruitment')
    ->name('hrm.recruitment.')
    ->group(function () {
        Route::middleware('hrmac:hrm.recruitment.jobs.view')->group(function () {
            Route::get('jobs',              [JobController::class, 'index'])->name('jobs.index');
            Route::get('jobs/{job}',        [JobController::class, 'show'])->name('jobs.show');
        });

        Route::middleware('hrmac:hrm.recruitment.jobs.edit')->group(function () {
            Route::get('jobs/create',       [JobController::class, 'create'])->name('jobs.create');
            Route::post('jobs',             [JobController::class, 'store'])->name('jobs.store');
            Route::patch('jobs/{job}',      [JobController::class, 'update'])->name('jobs.update');
        });

        Route::middleware('hrmac:hrm.recruitment.jobs.publish')->group(function () {
            Route::post('jobs/{job}/publish', [JobController::class, 'publish'])->name('jobs.publish');
            Route::post('jobs/{job}/close',   [JobController::class, 'close'])->name('jobs.close');
        });

        Route::middleware('hrmac:hrm.recruitment.applications.view')->group(function () {
            Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        });

        Route::middleware('hrmac:hrm.recruitment.applications.edit')->group(function () {
            Route::post('applications/{application}/stage',  [ApplicationController::class, 'moveStage'])->name('applications.stage');
            Route::post('applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
        });

        Route::middleware('hrmac:hrm.recruitment.interviews.view')->group(function () {
            Route::get('interviews', [InterviewController::class, 'index'])->name('interviews.index');
        });

        Route::middleware('hrmac:hrm.recruitment.interviews.edit')->group(function () {
            Route::get('interviews/create',         [InterviewController::class, 'create'])->name('interviews.create');
            Route::post('interviews',               [InterviewController::class, 'store'])->name('interviews.store');
            Route::patch('interviews/{interview}',  [InterviewController::class, 'update'])->name('interviews.update');
        });

        Route::middleware('hrmac:hrm.recruitment.offers.view')->group(function () {
            Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
        });

        Route::middleware('hrmac:hrm.recruitment.offers.edit')->group(function () {
            Route::get('offers/create', [OfferController::class, 'create'])->name('offers.create');
            Route::post('offers',       [OfferController::class, 'store'])->name('offers.store');
        });

        Route::middleware('hrmac:hrm.recruitment.onboarding.edit')->group(function () {
            Route::get('onboarding/{application}', [OnboardingController::class, 'create'])->name('onboarding.create');
            Route::post('onboarding/{application}', [OnboardingController::class, 'store'])->name('onboarding.store');
            Route::post('onboarding/{run}/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
        });
    });
```

---

## 10. Frontend Pages

All pages use the standard layout:

```jsx
import App from '../../App.jsx';
MyPage.layout = page => <App title="...">{page}</App>;
```

### 10.1 `HRM/Recruitment/Jobs/Index.jsx`

```jsx
import React from 'react';
import { Link, router } from '@inertiajs/react';
import { Button, Card, Chip, Input, Select, SelectItem, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Pagination } from '@aero/ui';
import { useHRMAC } from '@aero/hrmac';
import App from '../../../App.jsx';

export default function JobsIndex({ jobs, filters, departments, statuses, types }) {
    const { canEdit } = useHRMAC('hrm.recruitment.jobs');
    const [search, setSearch] = React.useState(filters.search ?? '');

    const apply = (next) => router.get(route('hrm.recruitment.jobs.index'), { ...filters, ...next }, { preserveState: true, replace: true });

    return (
        <div className="space-y-4 p-6">
            <header className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Job Postings</h1>
                {canEdit && (
                    <Button as={Link} href={route('hrm.recruitment.jobs.create')} color="primary">New Job</Button>
                )}
            </header>

            <Card className="p-4">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <Input
                        label="Search"
                        value={search}
                        onValueChange={setSearch}
                        onBlur={() => apply({ search })}
                    />
                    <Select label="Status" selectedKeys={filters.status ? [filters.status] : []}
                            onChange={(e) => apply({ status: e.target.value || null })}>
                        {statuses.map(s => <SelectItem key={s}>{s}</SelectItem>)}
                    </Select>
                    <Select label="Department" selectedKeys={filters.department_id ? [String(filters.department_id)] : []}
                            onChange={(e) => apply({ department_id: e.target.value || null })}>
                        {departments.map(d => <SelectItem key={d.id}>{d.name}</SelectItem>)}
                    </Select>
                    <Select label="Type" selectedKeys={filters.type ? [filters.type] : []}
                            onChange={(e) => apply({ type: e.target.value || null })}>
                        {types.map(t => <SelectItem key={t}>{t}</SelectItem>)}
                    </Select>
                </div>
            </Card>

            <Card>
                <Table aria-label="Jobs">
                    <TableHeader>
                        <TableColumn>TITLE</TableColumn>
                        <TableColumn>DEPARTMENT</TableColumn>
                        <TableColumn>STATUS</TableColumn>
                        <TableColumn>APPLICATIONS</TableColumn>
                        <TableColumn>POSTED</TableColumn>
                    </TableHeader>
                    <TableBody items={jobs.data} emptyContent="No jobs">
                        {(job) => (
                            <TableRow key={job.id}>
                                <TableCell>
                                    <Link href={route('hrm.recruitment.jobs.show', job.id)} className="text-primary hover:underline">
                                        {job.title}
                                    </Link>
                                </TableCell>
                                <TableCell>{job.department?.name ?? '—'}</TableCell>
                                <TableCell><Chip size="sm" variant="flat">{job.status}</Chip></TableCell>
                                <TableCell>{job.applications_count}</TableCell>
                                <TableCell>{job.posting_date ?? '—'}</TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <div className="flex justify-end p-3">
                    <Pagination
                        total={jobs.last_page}
                        page={jobs.current_page}
                        onChange={(p) => apply({ page: p })}
                    />
                </div>
            </Card>
        </div>
    );
}

JobsIndex.layout = page => <App title="Job Postings">{page}</App>;
```

### 10.2 `HRM/Recruitment/Jobs/Create.jsx`

Form with `title, department_id, type, location, description, salary_min, salary_max, salary_currency, positions, status (draft|open), posting_date, closing_date`. Uses `useForm` from Inertia. On success redirects to show.

### 10.3 `HRM/Recruitment/Jobs/Show.jsx`

Header card with job meta + actions (`Publish`, `Close`, `Edit`). Body holds **Kanban** of stages.

```jsx
import React from 'react';
import { router } from '@inertiajs/react';
import { Card, Button, Chip } from '@aero/ui';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import { useHRMAC } from '@aero/hrmac';
import App from '../../../App.jsx';

export default function JobShow({ job, hiringStages, applicationsByStage, metrics }) {
    const { canEdit, canPublish } = useHRMAC('hrm.recruitment.jobs');

    const onDragEnd = (result) => {
        if (!result.destination) return;
        const applicationId = result.draggableId;
        const stageId = result.destination.droppableId;
        router.post(route('hrm.recruitment.applications.stage', applicationId), {
            stage_id: stageId,
            notes: 'Moved via kanban',
        }, { preserveScroll: true });
    };

    return (
        <div className="p-6 space-y-4">
            <header className="flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-semibold">{job.title}</h1>
                    <div className="flex gap-2 mt-1">
                        <Chip variant="flat">{job.status}</Chip>
                        <Chip variant="flat">{job.department?.name}</Chip>
                        <Chip variant="flat">{job.type}</Chip>
                    </div>
                </div>
                <div className="flex gap-2">
                    {canPublish && job.status === 'draft' && (
                        <Button color="primary" onPress={() => router.post(route('hrm.recruitment.jobs.publish', job.id))}>Publish</Button>
                    )}
                    {canPublish && job.status === 'open' && (
                        <Button color="warning" onPress={() => router.post(route('hrm.recruitment.jobs.close', job.id))}>Close</Button>
                    )}
                </div>
            </header>

            <DragDropContext onDragEnd={onDragEnd}>
                <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
                    {hiringStages.map((stage) => (
                        <Droppable droppableId={String(stage.id)} key={stage.id}>
                            {(provided) => (
                                <Card ref={provided.innerRef} {...provided.droppableProps} className="p-3 min-h-[320px]">
                                    <div className="flex justify-between mb-2">
                                        <span className="font-medium">{stage.name}</span>
                                        <Chip size="sm">{applicationsByStage[stage.id]?.count ?? 0}</Chip>
                                    </div>
                                    {(applicationsByStage[stage.id]?.applications ?? []).map((app, i) => (
                                        <Draggable draggableId={String(app.id)} index={i} key={app.id} isDragDisabled={!canEdit}>
                                            {(p) => (
                                                <Card ref={p.innerRef} {...p.draggableProps} {...p.dragHandleProps}
                                                      className="p-2 mb-2 cursor-pointer"
                                                      onClick={() => router.visit(route('hrm.recruitment.applications.show', app.id))}>
                                                    <div className="text-sm font-medium">{app.applicant_name}</div>
                                                    <div className="text-xs opacity-70">{app.email}</div>
                                                </Card>
                                            )}
                                        </Draggable>
                                    ))}
                                    {provided.placeholder}
                                </Card>
                            )}
                        </Droppable>
                    ))}
                </div>
            </DragDropContext>
        </div>
    );
}

JobShow.layout = page => <App title="Job Detail">{page}</App>;
```

### 10.4 `HRM/Recruitment/Applications/Show.jsx`

Three-column layout: applicant info (left), resume viewer + evaluations (centre), timeline + actions (right). Actions: `Move stage`, `Schedule interview`, `Send offer`, `Reject`. Reject opens a modal with `reason` field.

### 10.5 `HRM/Recruitment/Interviews/Index.jsx`

Filterable list of scheduled interviews: date range, status, job. Columns: scheduled at, candidate, job, type, interviewers, status.

### 10.6 `HRM/Recruitment/Interviews/Create.jsx`

Form: select application, title, scheduled_at, duration, type, location/meeting link, multi-select interviewers. Pre-fills `application_id` from query string.

### 10.7 `HRM/Recruitment/Offers/Create.jsx`

Form: application picker, template select, offered salary, joining date, valid until, benefits, notes. Live preview of letter on right.

### 10.8 `HRM/Recruitment/Onboarding/Create.jsx`

Multi-step wizard (HeroUI `Tabs`):

1. **Confirm hire** — review applicant + job.
2. **Employee shell** — employee number, employment type, department, designation, manager, start date.
3. **Checklist** — pre-loaded from template; HR can add/remove items.
4. **Review & Launch** — POST to `hrm.recruitment.onboarding.store`.

---

## 11. Inertia Data Contract

Every controller returns **flat props**, paginated collections via `paginate()`, and a `filters` prop mirroring the query string. No nested `data.data.data`.

---

## 12. Tests (PHPUnit, `tests/Feature/Recruitment/`)

`RecruitmentFeatureTest.php` — 7 methods minimum, all using `RefreshDatabase`.

```php
<?php

namespace Aero\HRM\Tests\Feature\Recruitment;

use Aero\HRM\Models\Job;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobHiringStage;
use Aero\HRM\Models\JobOffer;
use Aero\HRM\Tests\TestCase;

class RecruitmentFeatureTest extends TestCase
{
    public function test_hr_admin_can_list_jobs(): void
    {
        $this->actingAsHRAdmin();
        Job::factory()->count(3)->create();

        $this->get(route('hrm.recruitment.jobs.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Recruitment/Jobs/Index')
                                         ->has('jobs.data', 3));
    }

    public function test_hr_admin_can_create_job(): void
    {
        $this->actingAsHRAdmin();

        $this->post(route('hrm.recruitment.jobs.store'), [
            'title' => 'Senior Engineer',
            'department_id' => 1,
            'type' => 'full_time',
            'description' => 'Build things.',
            'salary_currency' => 'USD',
            'positions' => 1,
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('jobs', ['title' => 'Senior Engineer']);
    }

    public function test_publish_transitions_job_to_open(): void
    {
        $this->actingAsHRAdmin();
        $job = Job::factory()->create(['status' => 'draft']);

        $this->post(route('hrm.recruitment.jobs.publish', $job))->assertRedirect();

        $this->assertSame('open', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->posting_date);
    }

    public function test_close_transitions_job_to_closed(): void
    {
        $this->actingAsHRAdmin();
        $job = Job::factory()->create(['status' => 'open']);

        $this->post(route('hrm.recruitment.jobs.close', $job))->assertRedirect();
        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_kanban_move_creates_stage_history(): void
    {
        $this->actingAsHRAdmin();
        $job = Job::factory()->hasHiringStages(2)->create();
        [$from, $to] = $job->hiringStages;
        $app = JobApplication::factory()->create(['job_id' => $job->id, 'current_stage_id' => $from->id]);

        $this->post(route('hrm.recruitment.applications.stage', $app), [
            'stage_id' => $to->id,
            'notes'    => 'screening passed',
        ])->assertRedirect();

        $this->assertSame($to->id, $app->fresh()->current_stage_id);
        $this->assertDatabaseHas('job_application_stage_histories', [
            'application_id' => $app->id,
            'from_stage_id'  => $from->id,
            'to_stage_id'    => $to->id,
        ]);
    }

    public function test_reject_application_records_reason(): void
    {
        $this->actingAsHRAdmin();
        $app = JobApplication::factory()->create();

        $this->post(route('hrm.recruitment.applications.reject', $app), [
            'reason' => 'Not a fit for current openings.',
        ])->assertRedirect();

        $fresh = $app->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertSame('Not a fit for current openings.', $fresh->rejection_reason);
    }

    public function test_offer_creation_advances_application(): void
    {
        $this->actingAsHRAdmin();
        $job = Job::factory()->hasHiringStages(['Offer'])->create();
        $app = JobApplication::factory()->create(['job_id' => $job->id]);

        $this->post(route('hrm.recruitment.offers.store'), [
            'application_id'   => $app->id,
            'offered_salary'   => 80000,
            'joining_date'     => now()->addDays(15)->toDateString(),
            'offer_valid_until'=> now()->addDays(7)->toDateString(),
            'salary_currency'  => 'USD',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_offers', ['application_id' => $app->id, 'offered_salary' => 80000]);
        $this->assertSame('offered', $app->fresh()->status);
    }
}
```

A separate `OnboardingServiceTest` covers the kickoff → employee creation path.

---

## 13. Tasks (sequenced)

1. **Migrations & model casts** — add `rejection_reason`, `accepted_at`, `onboarding_runs`; mount `EncryptedField` on `JobApplication` PII.
2. **HRMAC registration** — update `aero-hrm/config/hrmac.php` and clear HRMAC cache in deploy.
3. **Audit constants & events** — `RecruitmentAuditEvents`, integration with `AuditServiceInterface`.
4. **Split controllers + services** — extract `JobController`, `ApplicationController`, `InterviewController`, `OfferController`, `OnboardingController` and matching services; legacy `RecruitmentController` deprecated but kept until pages flip.
5. **Form Requests** — replace inline validation with `StoreJobRequest` etc.
6. **Routes** — register `hrm.recruitment.*` with HRMAC middleware; remove duplicate `hr.recruitment.*` aliases.
7. **Frontend pages** — implement the eight pages listed under section 10.
8. **PHPUnit tests** — implement `RecruitmentFeatureTest` (7 methods) + `OnboardingServiceTest`. Update CI matrix.
9. **Docs & changelog** — note the route rename in `docs/` and `CHANGELOG.md`.

---

## 14. Acceptance Criteria

- All routes return 403 without the matching HRMAC permission.
- Every state-mutating action produces an `AuditServiceInterface` record with the constants from `RecruitmentAuditEvents`.
- Applicant PII columns are encrypted at rest (verified by raw SQL dump in test).
- Kanban drag-and-drop persists stage moves and creates a `JobApplicationStageHistory` row.
- Onboarding completion creates an `Employee` row and fires `EMPLOYEE_ONBOARDED`.
- 7 PHPUnit methods pass green under sqlite `:memory:` with Orchestra Testbench.
