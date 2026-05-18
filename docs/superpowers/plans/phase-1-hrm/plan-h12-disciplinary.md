# Plan H-12: Disciplinary & Employee Relations

**Module:** `packages/aero-hrm`
**Status:** Draft
**Owner:** HRM Platform Team
**Depends on:** H-01 (Employee core), H-04 (Documents for attachments)
**Author date:** 2026-05-17

---

## 1. Purpose

Build the Employee Relations surface of HRM:

1. **Action Types** — admin-configurable taxonomy of disciplinary actions (Verbal Warning, Written Warning, PIP, Suspension, Termination).
2. **Disciplinary Cases** — formal cases with timeline, documents, employee response, closure.
3. **Warnings** — lightweight issuance/acknowledgement workflow with escalation rules.
4. **Exit Interviews** — schedule, conduct, capture structured responses on offboarding.
5. **Grievances** — employee-filed complaints with investigation trail and resolution.

All operations are tenant-scoped, HRMAC-gated, fully audited, and produce immutable timeline events.

---

## 2. HRMAC Hierarchy (additions to `packages/aero-hrm/config/module.php`)

```php
'disciplinary' => [
    'label' => 'Disciplinary & Relations',
    'icon'  => 'ScaleIcon',
    'order' => 80,
    'children' => [
        'action-types'     => ['label'=>'Action Types',     'actions'=>['view','edit']],
        'cases'            => ['label'=>'Cases',            'actions'=>['view','edit','close']],
        'warnings'         => ['label'=>'Warnings',         'actions'=>['view','edit']],
        'exit-interviews'  => ['label'=>'Exit Interviews',  'actions'=>['view','edit']],
        'grievances'       => ['label'=>'Grievances',       'actions'=>['view','edit','investigate']],
    ],
],
```

HRMAC paths:

- `hrm.disciplinary.action-types.view` / `.edit`
- `hrm.disciplinary.disciplinary-cases.view` / `.update` / `.close`
- `hrm.disciplinary.warnings.view` / `.edit`
- `hrm.exit-interviews.exit-interview-list.view` / `.update`
- `hrm.grievances.grievance-list.view` / `.update` / `.investigate`

---

## 3. Data Model

### 3.1 Action Types

```php
Schema::create('hrm_disciplinary_action_types', function (Blueprint $t) {
    $t->id();
    $t->string('name')->unique();
    $t->enum('severity', ['low','medium','high','critical'])->default('medium');
    $t->text('description')->nullable();
    $t->unsignedTinyInteger('escalates_after_count')->nullable(); // e.g. 3 warnings → escalation
    $t->string('escalates_to_type')->nullable();
    $t->boolean('active')->default(true);
    $t->timestamps();
});
```

### 3.2 Disciplinary Cases

```php
Schema::create('hrm_disciplinary_cases', function (Blueprint $t) {
    $t->id();
    $t->string('reference')->unique();   // CASE-2026-000123
    $t->foreignId('employee_id')->constrained('employees');
    $t->foreignId('action_type_id')->constrained('hrm_disciplinary_action_types');
    $t->foreignId('opened_by')->constrained('users');
    $t->date('incident_date');
    $t->string('subject');
    $t->text('description');
    $t->enum('status', ['open','awaiting_response','under_review','closed'])->default('open');
    $t->enum('outcome', ['none','verbal','written','pip','suspension','termination'])->default('none');
    $t->text('closure_notes')->nullable();
    $t->timestamp('closed_at')->nullable();
    $t->foreignId('closed_by')->nullable()->constrained('users');
    $t->timestamps();
    $t->index(['status','employee_id']);
});

Schema::create('hrm_disciplinary_case_events', function (Blueprint $t) {
    $t->id();
    $t->foreignId('case_id')->constrained('hrm_disciplinary_cases')->cascadeOnDelete();
    $t->string('type');             // opened|note|response|document|status_change|closed
    $t->json('payload')->nullable();
    $t->foreignId('actor_id')->nullable()->constrained('users');
    $t->timestamp('occurred_at');
});

Schema::create('hrm_disciplinary_case_documents', function (Blueprint $t) {
    $t->id();
    $t->foreignId('case_id')->constrained('hrm_disciplinary_cases')->cascadeOnDelete();
    $t->string('disk');
    $t->string('path');
    $t->string('original_name');
    $t->unsignedInteger('size_bytes');
    $t->foreignId('uploaded_by')->constrained('users');
    $t->timestamps();
});
```

### 3.3 Warnings

```php
Schema::create('hrm_warnings', function (Blueprint $t) {
    $t->id();
    $t->foreignId('employee_id')->constrained('employees');
    $t->foreignId('issued_by')->constrained('users');
    $t->foreignId('action_type_id')->nullable()->constrained('hrm_disciplinary_action_types');
    $t->string('subject');
    $t->text('body');
    $t->enum('status', ['issued','acknowledged','escalated'])->default('issued');
    $t->timestamp('issued_at');
    $t->timestamp('acknowledged_at')->nullable();
    $t->text('employee_response')->nullable();
    $t->foreignId('escalated_to_case_id')->nullable()->constrained('hrm_disciplinary_cases');
    $t->timestamps();
});
```

### 3.4 Exit Interviews

```php
Schema::create('hrm_exit_interviews', function (Blueprint $t) {
    $t->id();
    $t->foreignId('employee_id')->constrained('employees');
    $t->date('scheduled_for');
    $t->foreignId('interviewer_id')->nullable()->constrained('users');
    $t->enum('status', ['scheduled','completed','no_show'])->default('scheduled');
    $t->json('responses')->nullable();   // [{question_id, value}]
    $t->text('summary')->nullable();
    $t->unsignedTinyInteger('eligible_for_rehire')->nullable();
    $t->timestamp('completed_at')->nullable();
    $t->timestamps();
});
```

### 3.5 Grievances

```php
Schema::create('hrm_grievances', function (Blueprint $t) {
    $t->id();
    $t->string('reference')->unique();   // GRV-2026-000045
    $t->foreignId('filed_by')->constrained('employees'); // employee
    $t->foreignId('against_employee_id')->nullable()->constrained('employees');
    $t->enum('category', ['harassment','discrimination','workplace_safety','policy_violation','interpersonal','other']);
    $t->string('subject');
    $t->text('description');
    $t->enum('confidentiality', ['standard','confidential','anonymous'])->default('standard');
    $t->enum('status', ['filed','under_investigation','resolved','dismissed'])->default('filed');
    $t->foreignId('investigator_id')->nullable()->constrained('users');
    $t->text('resolution_notes')->nullable();
    $t->timestamp('resolved_at')->nullable();
    $t->timestamps();
});

Schema::create('hrm_grievance_events', function (Blueprint $t) {
    $t->id();
    $t->foreignId('grievance_id')->constrained('hrm_grievances')->cascadeOnDelete();
    $t->string('type'); // filed|assigned|note|interview|status_change|resolved|dismissed
    $t->json('payload')->nullable();
    $t->foreignId('actor_id')->nullable()->constrained('users');
    $t->timestamp('occurred_at');
});
```

### 3.6 Models

`DisciplinaryActionType`, `DisciplinaryCase`, `DisciplinaryCaseEvent`, `DisciplinaryCaseDocument`, `Warning`, `ExitInterview`, `Grievance`, `GrievanceEvent` — all `TenantModel`.

`DisciplinaryCase` has `events()`, `documents()`. `Grievance` has `events()`.

---

## 4. Services (`packages/aero-hrm/src/Services/Disciplinary/`)

| Service | Responsibility |
|---------|----------------|
| `DisciplinaryCaseService`  | Open/close/transition cases; emit timeline events; enforce state machine. |
| `WarningService`           | Issue/acknowledge/escalate; check escalation thresholds vs action type. |
| `ExitInterviewService`     | Schedule + record responses. |
| `GrievanceService`         | File / assign investigator / resolve / dismiss; emit timeline events. |
| `ReferenceGenerator`       | Returns `CASE-YYYY-NNNNNN` / `GRV-YYYY-NNNNNN`. |

### 4.1 Case state machine

```
open → awaiting_response → under_review → closed
open → under_review → closed         (skip response when none required)
```

```php
final class DisciplinaryCaseService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function open(array $data): DisciplinaryCase
    {
        return DB::transaction(function () use ($data) {
            $case = DisciplinaryCase::create([
                ...$data,
                'reference' => app(ReferenceGenerator::class)->case(),
                'status'    => 'open',
            ]);
            $this->addEvent($case, 'opened', ['subject'=>$case->subject]);
            $this->audit->log('DISCIPLINARY_CASE_OPENED', $case);
            return $case;
        });
    }

    public function respond(DisciplinaryCase $case, string $response, int $userId): void
    {
        abort_unless(in_array($case->status, ['open','awaiting_response']), 422, 'Cannot respond in current state.');
        $case->update(['status'=>'under_review']);
        $this->addEvent($case, 'response', ['text'=>$response], $userId);
        $this->audit->log('DISCIPLINARY_CASE_RESPONDED', $case);
    }

    public function close(DisciplinaryCase $case, string $outcome, ?string $notes, int $userId): void
    {
        abort_if($case->status === 'closed', 422, 'Already closed.');
        $case->update([
            'status'        => 'closed',
            'outcome'       => $outcome,
            'closure_notes' => $notes,
            'closed_at'     => now(),
            'closed_by'     => $userId,
        ]);
        $this->addEvent($case, 'closed', ['outcome'=>$outcome,'notes'=>$notes], $userId);
        $this->audit->log('DISCIPLINARY_CASE_CLOSED', $case);
    }

    public function addEvent(DisciplinaryCase $c, string $type, array $payload = [], ?int $userId = null): void
    {
        $c->events()->create([
            'type'=>$type,'payload'=>$payload,
            'actor_id'=>$userId ?? auth()->id(),'occurred_at'=>now(),
        ]);
    }
}
```

### 4.2 Warning escalation

```php
final class WarningService
{
    public function __construct(
        private DisciplinaryCaseService $cases,
        private AuditServiceInterface $audit,
    ) {}

    public function issue(array $data): Warning
    {
        $w = Warning::create([...$data, 'status'=>'issued', 'issued_at'=>now()]);
        $this->audit->log('WARNING_ISSUED', $w);
        $this->maybeEscalate($w);
        return $w;
    }

    public function acknowledge(Warning $w, ?string $response): void
    {
        abort_unless($w->status === 'issued', 422);
        $w->update([
            'status'=>'acknowledged',
            'acknowledged_at'=>now(),
            'employee_response'=>$response,
        ]);
        $this->audit->log('WARNING_ACKNOWLEDGED', $w);
    }

    private function maybeEscalate(Warning $w): void
    {
        $type = $w->actionType;
        if (! $type || ! $type->escalates_after_count) return;

        $count = Warning::where('employee_id',$w->employee_id)
            ->where('action_type_id',$type->id)
            ->where('issued_at','>=', now()->subYear())
            ->count();

        if ($count < $type->escalates_after_count) return;

        $case = $this->cases->open([
            'employee_id'    => $w->employee_id,
            'action_type_id' => DisciplinaryActionType::firstWhere('name', $type->escalates_to_type)?->id ?? $type->id,
            'opened_by'      => auth()->id(),
            'incident_date'  => now()->toDateString(),
            'subject'        => "Auto-escalation: {$count}x {$type->name}",
            'description'    => "Triggered by warning #{$w->id}.",
        ]);
        $w->update(['status'=>'escalated','escalated_to_case_id'=>$case->id]);
        $this->audit->log('WARNING_ESCALATED', $w, ['case_id'=>$case->id]);
    }
}
```

### 4.3 Grievance lifecycle

```php
final class GrievanceService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function file(array $data): Grievance
    {
        $g = Grievance::create([
            ...$data,
            'reference' => app(ReferenceGenerator::class)->grievance(),
            'status'    => 'filed',
        ]);
        $this->addEvent($g, 'filed', ['category'=>$g->category]);
        $this->audit->log('GRIEVANCE_FILED', $g);
        return $g;
    }

    public function assignInvestigator(Grievance $g, int $userId, int $byUserId): void
    {
        $g->update(['investigator_id'=>$userId,'status'=>'under_investigation']);
        $this->addEvent($g, 'assigned', ['investigator_id'=>$userId], $byUserId);
        $this->audit->log('GRIEVANCE_ASSIGNED', $g);
    }

    public function resolve(Grievance $g, string $notes, int $userId): void
    {
        abort_unless($g->status === 'under_investigation', 422);
        $g->update(['status'=>'resolved','resolution_notes'=>$notes,'resolved_at'=>now()]);
        $this->addEvent($g, 'resolved', ['notes'=>$notes], $userId);
        $this->audit->log('GRIEVANCE_RESOLVED', $g);
    }

    public function dismiss(Grievance $g, string $reason, int $userId): void
    {
        $g->update(['status'=>'dismissed','resolution_notes'=>$reason,'resolved_at'=>now()]);
        $this->addEvent($g, 'dismissed', ['reason'=>$reason], $userId);
        $this->audit->log('GRIEVANCE_DISMISSED', $g);
    }

    private function addEvent(Grievance $g, string $type, array $payload, ?int $userId = null): void
    {
        $g->events()->create([
            'type'=>$type,'payload'=>$payload,
            'actor_id'=>$userId ?? auth()->id(),'occurred_at'=>now(),
        ]);
    }
}
```

---

## 5. Controllers (`packages/aero-hrm/src/Http/Controllers/Disciplinary/`)

### 5.1 `ActionTypeController`

```php
public function index(): Response
{
    return Inertia::render('HRM/Disciplinary/ActionTypes/Index', [
        'types' => DisciplinaryActionType::orderBy('name')->paginate(20),
    ]);
}
public function store(StoreActionTypeRequest $r): RedirectResponse { /* ... */ }
public function update(UpdateActionTypeRequest $r, DisciplinaryActionType $type): RedirectResponse { /* ... */ }
public function destroy(DisciplinaryActionType $type): RedirectResponse
{
    abort_if($type->cases()->exists() || $type->warnings()->exists(), 422, 'Type in use.');
    $type->delete();
    return back();
}
```

### 5.2 `DisciplinaryCaseController`

```php
final class DisciplinaryCaseController extends Controller
{
    public function __construct(private DisciplinaryCaseService $svc) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['status','action_type_id','employee_id','search']);

        return Inertia::render('HRM/Disciplinary/Cases/Index', [
            'cases' => DisciplinaryCase::query()
                ->with(['employee:id,first_name,last_name','actionType:id,name,severity'])
                ->when($filters['status']         ?? null, fn ($q,$v) => $q->where('status',$v))
                ->when($filters['action_type_id'] ?? null, fn ($q,$v) => $q->where('action_type_id',$v))
                ->when($filters['employee_id']    ?? null, fn ($q,$v) => $q->where('employee_id',$v))
                ->when($filters['search']         ?? null, fn ($q,$s) =>
                    $q->where(fn ($x) => $x->where('reference','like',"%$s%")->orWhere('subject','like',"%$s%")))
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString(),
            'filters'     => $filters,
            'actionTypes' => DisciplinaryActionType::where('active',true)->get(['id','name']),
            'statuses'    => ['open','awaiting_response','under_review','closed'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Disciplinary/Cases/Create', [
            'actionTypes' => DisciplinaryActionType::where('active',true)->get(['id','name','severity']),
            'employees'   => Employee::select('id','first_name','last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(StoreCaseRequest $r): RedirectResponse
    {
        $case = $this->svc->open([...$r->validated(), 'opened_by'=>$r->user()->id]);
        return redirect()->route('hrm.disciplinary.cases.show', $case);
    }

    public function show(DisciplinaryCase $case): Response
    {
        return Inertia::render('HRM/Disciplinary/Cases/Show', [
            'case' => $case->load(['employee','actionType','openedBy:id,name','closedBy:id,name']),
            'timeline' => $case->events()->with('actor:id,name')->orderBy('occurred_at')->get(),
            'documents' => $case->documents()->with('uploader:id,name')->get(),
        ]);
    }

    public function respond(RespondCaseRequest $r, DisciplinaryCase $case): RedirectResponse
    {
        $this->svc->respond($case, $r->validated('response'), $r->user()->id);
        return back()->with('success','Response recorded');
    }

    public function close(CloseCaseRequest $r, DisciplinaryCase $case): RedirectResponse
    {
        $this->svc->close($case, $r->validated('outcome'), $r->validated('closure_notes'), $r->user()->id);
        return back()->with('success','Case closed');
    }
}
```

### 5.3 `WarningController`

```php
public function index(): Response { /* paginate w/ filters */ }

public function create(): Response
{
    return Inertia::render('HRM/Disciplinary/Warnings/Create', [
        'actionTypes' => DisciplinaryActionType::where('active',true)->get(['id','name']),
        'employees'   => Employee::select('id','first_name','last_name')->get(),
    ]);
}

public function store(StoreWarningRequest $r): RedirectResponse
{
    $this->svc->issue([...$r->validated(), 'issued_by'=>$r->user()->id]);
    return redirect()->route('hrm.disciplinary.warnings.index');
}

public function acknowledge(AcknowledgeWarningRequest $r, Warning $warning): RedirectResponse
{
    abort_unless($warning->employee->user_id === $r->user()->id, 403);
    $this->svc->acknowledge($warning, $r->input('response'));
    return back();
}
```

### 5.4 `ExitInterviewController`

```php
public function index(): Response { /* list with status filter */ }

public function show(ExitInterview $interview): Response
{
    return Inertia::render('HRM/Disciplinary/ExitInterviews/Show', [
        'interview' => $interview->load('employee','interviewer:id,name'),
        'questionnaire' => config('aero-hrm.exit_interview_questions'),
    ]);
}

public function store(StoreExitInterviewRequest $r, ExitInterview $interview): RedirectResponse
{
    $interview->update([
        'responses'           => $r->validated('responses'),
        'summary'             => $r->input('summary'),
        'eligible_for_rehire' => $r->input('eligible_for_rehire'),
        'status'              => 'completed',
        'completed_at'        => now(),
    ]);
    $this->audit->log('EXIT_INTERVIEW_COMPLETED', $interview);
    return back()->with('success','Interview recorded');
}
```

### 5.5 `GrievanceController`

```php
public function index(): Response { /* admin sees all; employee sees own */ }

public function create(): Response
{
    return Inertia::render('HRM/Disciplinary/Grievances/Create', [
        'categories' => ['harassment','discrimination','workplace_safety','policy_violation','interpersonal','other'],
    ]);
}

public function store(StoreGrievanceRequest $r): RedirectResponse
{
    $g = $this->svc->file([
        ...$r->validated(),
        'filed_by' => $r->user()->employee->id,
    ]);
    return redirect()->route('hrm.disciplinary.grievances.show', $g);
}

public function show(Grievance $grievance): Response
{
    $this->authorizeView($grievance);
    return Inertia::render('HRM/Disciplinary/Grievances/Show', [
        'grievance' => $grievance->load('filedBy','againstEmployee','investigator:id,name'),
        'timeline'  => $grievance->events()->with('actor:id,name')->orderBy('occurred_at')->get(),
    ]);
}

public function investigate(InvestigateGrievanceRequest $r, Grievance $grievance): RedirectResponse
{
    $this->svc->assignInvestigator($grievance, $r->validated('investigator_id'), $r->user()->id);
    return back();
}

public function resolve(ResolveGrievanceRequest $r, Grievance $grievance): RedirectResponse
{
    $r->validated('dismiss')
        ? $this->svc->dismiss($grievance, $r->validated('notes'), $r->user()->id)
        : $this->svc->resolve($grievance, $r->validated('notes'), $r->user()->id);
    return back();
}
```

---

## 6. Routes (`packages/aero-hrm/routes/tenant.php`)

```php
Route::prefix('hrm/disciplinary')->name('hrm.disciplinary.')->middleware(['auth','tenant'])->group(function () {

    Route::prefix('action-types')->name('action-types.')->group(function () {
        Route::get('/',         [ActionTypeController::class,'index'])->middleware('hrmac:hrm.disciplinary.action-types.view')->name('index');
        Route::post('/',        [ActionTypeController::class,'store'])->middleware('hrmac:hrm.disciplinary.action-types.edit')->name('store');
        Route::put('{type}',    [ActionTypeController::class,'update'])->middleware('hrmac:hrm.disciplinary.action-types.edit')->name('update');
        Route::delete('{type}', [ActionTypeController::class,'destroy'])->middleware('hrmac:hrm.disciplinary.action-types.edit')->name('destroy');
    });

    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/',                  [DisciplinaryCaseController::class,'index'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.view')->name('index');
        Route::get('create',             [DisciplinaryCaseController::class,'create'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.update')->name('create');
        Route::post('/',                 [DisciplinaryCaseController::class,'store'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.update')->name('store');
        Route::get('{case}',             [DisciplinaryCaseController::class,'show'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.view')->name('show');
        Route::post('{case}/respond',    [DisciplinaryCaseController::class,'respond'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.update')->name('respond');
        Route::post('{case}/close',      [DisciplinaryCaseController::class,'close'])->middleware('hrmac:hrm.disciplinary.disciplinary-cases.close')->name('close');
    });

    Route::prefix('warnings')->name('warnings.')->group(function () {
        Route::get('/',                  [WarningController::class,'index'])->middleware('hrmac:hrm.disciplinary.warnings.view')->name('index');
        Route::get('create',             [WarningController::class,'create'])->middleware('hrmac:hrm.disciplinary.warnings.edit')->name('create');
        Route::post('/',                 [WarningController::class,'store'])->middleware('hrmac:hrm.disciplinary.warnings.edit')->name('store');
        Route::post('{warning}/ack',     [WarningController::class,'acknowledge'])->middleware('hrmac:hrm.disciplinary.warnings.view')->name('acknowledge');
    });

    Route::prefix('exit-interviews')->name('exit-interviews.')->group(function () {
        Route::get('/',                  [ExitInterviewController::class,'index'])->middleware('hrmac:hrm.exit-interviews.exit-interview-list.view')->name('index');
        Route::get('{interview}',        [ExitInterviewController::class,'show'])->middleware('hrmac:hrm.exit-interviews.exit-interview-list.view')->name('show');
        Route::post('{interview}',       [ExitInterviewController::class,'store'])->middleware('hrmac:hrm.exit-interviews.exit-interview-list.update')->name('store');
    });

    Route::prefix('grievances')->name('grievances.')->group(function () {
        Route::get('/',                  [GrievanceController::class,'index'])->middleware('hrmac:hrm.grievances.grievance-list.view')->name('index');
        Route::get('create',             [GrievanceController::class,'create'])->middleware('hrmac:hrm.grievances.grievance-list.update')->name('create');
        Route::post('/',                 [GrievanceController::class,'store'])->middleware('hrmac:hrm.grievances.grievance-list.update')->name('store');
        Route::get('{grievance}',        [GrievanceController::class,'show'])->middleware('hrmac:hrm.grievances.grievance-list.view')->name('show');
        Route::post('{grievance}/investigate', [GrievanceController::class,'investigate'])->middleware('hrmac:hrm.grievances.grievance-list.investigate')->name('investigate');
        Route::post('{grievance}/resolve',     [GrievanceController::class,'resolve'])->middleware('hrmac:hrm.grievances.grievance-list.investigate')->name('resolve');
    });
});
```

---

## 7. React Pages (`packages/aero-ui/resources/js/Pages/HRM/Disciplinary/`)

### 7.1 `Cases/Index.jsx`

```jsx
import { Link, router } from '@inertiajs/react';
import { Button, Table, TableHeader, TableBody, TableColumn, TableRow, TableCell, Chip, Input, Select, SelectItem } from '@heroui/react';
import App from '../../../App.jsx';

const statusColor = { open:'warning', awaiting_response:'primary', under_review:'secondary', closed:'success' };

export default function CasesIndex({ cases, filters, actionTypes, statuses }) {
  const update = (patch) => router.get(route('hrm.disciplinary.cases.index'),
    {...filters, ...patch}, { preserveState:true, replace:true });

  return (
    <div className="p-6 space-y-4">
      <div className="flex justify-between">
        <h1 className="text-2xl font-semibold">Disciplinary Cases</h1>
        <Button as={Link} href={route('hrm.disciplinary.cases.create')} color="primary">New Case</Button>
      </div>

      <div className="flex gap-3">
        <Input placeholder="Search reference/subject" defaultValue={filters.search} onBlur={e=>update({search:e.target.value})}/>
        <Select label="Status" selectedKeys={[filters.status ?? '']} onSelectionChange={k=>update({status:[...k][0]})}>
          {statuses.map(s => <SelectItem key={s}>{s}</SelectItem>)}
        </Select>
        <Select label="Action Type" selectedKeys={[String(filters.action_type_id ?? '')]} onSelectionChange={k=>update({action_type_id:[...k][0]})}>
          {actionTypes.map(t => <SelectItem key={t.id}>{t.name}</SelectItem>)}
        </Select>
      </div>

      <Table aria-label="Cases">
        <TableHeader>
          <TableColumn>Reference</TableColumn>
          <TableColumn>Employee</TableColumn>
          <TableColumn>Type</TableColumn>
          <TableColumn>Subject</TableColumn>
          <TableColumn>Status</TableColumn>
        </TableHeader>
        <TableBody items={cases.data}>
          {(c) => (
            <TableRow key={c.id}>
              <TableCell>
                <Link href={route('hrm.disciplinary.cases.show', c.id)} className="text-primary">{c.reference}</Link>
              </TableCell>
              <TableCell>{c.employee.first_name} {c.employee.last_name}</TableCell>
              <TableCell>{c.action_type.name}</TableCell>
              <TableCell className="max-w-xs truncate">{c.subject}</TableCell>
              <TableCell><Chip color={statusColor[c.status]}>{c.status}</Chip></TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
CasesIndex.layout = page => <App title="Disciplinary Cases">{page}</App>;
```

### 7.2 `Cases/Show.jsx`

```jsx
import { useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Chip, Textarea, Select, SelectItem, Button, Divider } from '@heroui/react';
import App from '../../../App.jsx';

export default function CaseShow({ case: c, timeline, documents }) {
  const respond = useForm({ response: '' });
  const close   = useForm({ outcome: 'verbal', closure_notes: '' });

  return (
    <div className="p-6 grid grid-cols-3 gap-6">
      <div className="col-span-2 space-y-4">
        <Card>
          <CardHeader className="flex justify-between">
            <div>
              <h2 className="text-xl">{c.reference} · {c.subject}</h2>
              <p className="text-sm text-default-500">
                {c.employee.first_name} {c.employee.last_name} · {c.action_type.name}
              </p>
            </div>
            <Chip>{c.status}</Chip>
          </CardHeader>
          <CardBody>
            <p className="whitespace-pre-wrap">{c.description}</p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Timeline</CardHeader>
          <CardBody className="space-y-3">
            {timeline.map(ev => (
              <div key={ev.id} className="flex gap-3">
                <Chip size="sm">{ev.type}</Chip>
                <div>
                  <p className="text-xs text-default-500">
                    {ev.actor?.name ?? 'System'} · {ev.occurred_at}
                  </p>
                  {ev.payload?.text && <p className="mt-1">{ev.payload.text}</p>}
                  {ev.payload?.outcome && <p className="mt-1">Outcome: <strong>{ev.payload.outcome}</strong></p>}
                </div>
              </div>
            ))}
          </CardBody>
        </Card>
      </div>

      <div className="space-y-4">
        {c.status !== 'closed' && (
          <Card>
            <CardHeader>Record Response</CardHeader>
            <CardBody>
              <Textarea value={respond.data.response} onValueChange={v => respond.setData('response', v)} />
              <Button className="mt-2" onPress={() => respond.post(route('hrm.disciplinary.cases.respond', c.id))}>Submit</Button>
            </CardBody>
          </Card>
        )}
        {c.status !== 'closed' && (
          <Card>
            <CardHeader>Close Case</CardHeader>
            <CardBody className="space-y-2">
              <Select selectedKeys={[close.data.outcome]} onSelectionChange={k => close.setData('outcome', [...k][0])}>
                {['none','verbal','written','pip','suspension','termination'].map(o => <SelectItem key={o}>{o}</SelectItem>)}
              </Select>
              <Textarea value={close.data.closure_notes} onValueChange={v => close.setData('closure_notes', v)} />
              <Button color="danger" onPress={() => close.post(route('hrm.disciplinary.disciplinary-cases.close', c.id))}>Close</Button>
            </CardBody>
          </Card>
        )}
        <Card>
          <CardHeader>Documents</CardHeader>
          <CardBody>
            {documents.length === 0 && <p className="text-sm text-default-500">No documents.</p>}
            {documents.map(d => <p key={d.id}>{d.original_name}</p>)}
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
CaseShow.layout = page => <App title="Case Detail">{page}</App>;
```

The remaining 9 pages (`ActionTypes/Index.jsx`, `Cases/Create.jsx`, `Warnings/Index.jsx`, `Warnings/Create.jsx`, `ExitInterviews/Index.jsx`, `ExitInterviews/Show.jsx`, `Grievances/Index.jsx`, `Grievances/Create.jsx`, `Grievances/Show.jsx`) follow the same flat-props + `App` layout pattern. `Grievances/Show.jsx` mirrors the case timeline structure.

---

## 8. Audit Events

| Event | Where |
|-------|-------|
| `DISCIPLINARY_CASE_OPENED`     | `DisciplinaryCaseService::open` |
| `DISCIPLINARY_CASE_RESPONDED`  | `DisciplinaryCaseService::respond` |
| `DISCIPLINARY_CASE_CLOSED`     | `DisciplinaryCaseService::close` |
| `WARNING_ISSUED`               | `WarningService::issue` |
| `WARNING_ACKNOWLEDGED`         | `WarningService::acknowledge` |
| `WARNING_ESCALATED`            | `WarningService::maybeEscalate` |
| `EXIT_INTERVIEW_COMPLETED`     | `ExitInterviewController@store` |
| `GRIEVANCE_FILED`              | `GrievanceService::file` |
| `GRIEVANCE_ASSIGNED`           | `GrievanceService::assignInvestigator` |
| `GRIEVANCE_RESOLVED`           | `GrievanceService::resolve` |
| `GRIEVANCE_DISMISSED`          | `GrievanceService::dismiss` |

---

## 9. Tests (`packages/aero-hrm/tests/Feature/Disciplinary/`)

```php
final class DisciplinaryCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_a_case(): void
    {
        $this->actingAsUserWithPerms(['hrm.disciplinary.disciplinary-cases.update']);
        $type = DisciplinaryActionType::factory()->create();
        $emp  = Employee::factory()->create();

        $this->post(route('hrm.disciplinary.cases.store'), [
            'employee_id'   => $emp->id,
            'action_type_id'=> $type->id,
            'incident_date' => now()->subDay()->toDateString(),
            'subject'       => 'Tardiness',
            'description'   => 'Late 4 days in a row.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_disciplinary_cases', ['subject'=>'Tardiness','status'=>'open']);
        $this->assertDatabaseHas('hrm_disciplinary_case_events', ['type'=>'opened']);
    }

    public function test_case_can_be_closed_with_outcome(): void
    {
        $this->actingAsUserWithPerms(['hrm.disciplinary.disciplinary-cases.close']);
        $c = DisciplinaryCase::factory()->open()->create();

        $this->post(route('hrm.disciplinary.disciplinary-cases.close', $c), [
            'outcome' => 'written',
            'closure_notes' => 'Final written warning issued.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_disciplinary_cases', [
            'id'=>$c->id,'status'=>'closed','outcome'=>'written',
        ]);
    }

    public function test_closing_a_closed_case_fails(): void
    {
        $this->actingAsUserWithPerms(['hrm.disciplinary.disciplinary-cases.close']);
        $c = DisciplinaryCase::factory()->create(['status'=>'closed','closed_at'=>now()]);

        $this->post(route('hrm.disciplinary.disciplinary-cases.close', $c), [
            'outcome'=>'verbal','closure_notes'=>'x',
        ])->assertStatus(422);
    }
}

final class WarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_acknowledge_own_warning(): void
    {
        $emp = Employee::factory()->create();
        $emp->user->givePermissionTo('hrm.disciplinary.warnings.view');
        $w = Warning::factory()->for($emp,'employee')->create(['status'=>'issued']);

        $this->actingAs($emp->user)
            ->post(route('hrm.disciplinary.warnings.acknowledge', $w), ['response'=>'Understood'])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_warnings', [
            'id'=>$w->id,'status'=>'acknowledged','employee_response'=>'Understood',
        ]);
    }

    public function test_warning_escalates_to_case_at_threshold(): void
    {
        $this->actingAsUserWithPerms(['hrm.disciplinary.warnings.edit']);
        $type = DisciplinaryActionType::factory()->create([
            'name'=>'Verbal Warning','escalates_after_count'=>3,'escalates_to_type'=>'Written Warning',
        ]);
        $written = DisciplinaryActionType::factory()->create(['name'=>'Written Warning']);
        $emp = Employee::factory()->create();

        // Two prior warnings already exist
        Warning::factory()->count(2)->for($emp,'employee')->create([
            'action_type_id'=>$type->id,'status'=>'issued','issued_at'=>now()->subMonth(),
        ]);

        $this->post(route('hrm.disciplinary.warnings.store'), [
            'employee_id'=>$emp->id,'action_type_id'=>$type->id,
            'subject'=>'3rd verbal','body'=>'See attached.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_warnings', ['status'=>'escalated']);
        $this->assertDatabaseHas('hrm_disciplinary_cases', ['action_type_id'=>$written->id]);
    }
}

final class GrievanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_file_grievance(): void
    {
        $emp = Employee::factory()->create();
        $emp->user->givePermissionTo('hrm.grievances.grievance-list.update');

        $this->actingAs($emp->user)->post(route('hrm.disciplinary.grievances.store'), [
            'category'    => 'harassment',
            'subject'     => 'Inappropriate comments',
            'description' => 'Multiple incidents last week.',
            'confidentiality' => 'confidential',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_grievances', [
            'filed_by'=>$emp->id,'status'=>'filed','category'=>'harassment',
        ]);
    }

    public function test_grievance_can_be_assigned_and_resolved(): void
    {
        $admin = $this->actingAsUserWithPerms(['hrm.grievances.grievance-list.investigate']);
        $investigator = User::factory()->create();
        $g = Grievance::factory()->create(['status'=>'filed']);

        $this->post(route('hrm.grievances.grievance-list.investigate', $g), [
            'investigator_id'=>$investigator->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id'=>$g->id,'status'=>'under_investigation']);

        $this->post(route('hrm.disciplinary.grievances.resolve', $g), [
            'dismiss'=>false,'notes'=>'Mediation completed.',
        ])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id'=>$g->id,'status'=>'resolved']);
    }

    public function test_grievance_dismissal_path(): void
    {
        $this->actingAsUserWithPerms(['hrm.grievances.grievance-list.investigate']);
        $g = Grievance::factory()->create(['status'=>'under_investigation']);

        $this->post(route('hrm.disciplinary.grievances.resolve', $g), [
            'dismiss'=>true,'notes'=>'No evidence found.',
        ])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id'=>$g->id,'status'=>'dismissed']);
    }
}
```

(That is 7 PHPUnit methods spread across three feature test files.)

---

## 10. Tasks (execution order)

1. **DB & Models** — 8 migrations (action types, cases, case events, case documents, warnings, exit interviews, grievances, grievance events), all 8 models with factories.
2. **HRMAC config** — extend `packages/aero-hrm/config/module.php` with the `disciplinary` subtree.
3. **Services** — `ReferenceGenerator`, `DisciplinaryCaseService`, `WarningService`, `ExitInterviewService`, `GrievanceService` under `src/Services/Disciplinary/`.
4. **FormRequests** — `StoreActionTypeRequest`, `UpdateActionTypeRequest`, `StoreCaseRequest`, `RespondCaseRequest`, `CloseCaseRequest`, `StoreWarningRequest`, `AcknowledgeWarningRequest`, `StoreExitInterviewRequest`, `StoreGrievanceRequest`, `InvestigateGrievanceRequest`, `ResolveGrievanceRequest`.
5. **Controllers + Routes** — 5 controllers wired to `hrmac:*` middleware in `routes/tenant.php`.
6. **React pages** — 11 pages under `Pages/HRM/Disciplinary/...` (using `App` layout), with the case `Show.jsx` and grievance `Show.jsx` rendering timeline + actions side-by-side.
7. **Tests** — 7 PHPUnit feature tests as specified plus factory states (`open`, `draft`, `active`). Playwright smoke for: open case → respond → close, and file grievance → assign → resolve.

---

## 11. Out of Scope

- Encrypted-at-rest grievance bodies (planned H-12.1 with KMS integration).
- Anonymous grievance submission portal for non-employees.
- Auto-redaction of names in exported case PDFs.
- Multi-step PIP workflow with milestone tracking (deferred to H-12.2).
