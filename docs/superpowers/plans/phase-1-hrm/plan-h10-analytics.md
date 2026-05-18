# Plan H-10: HR Analytics & AI Insights

**Module:** `packages/aero-hrm`
**Status:** Draft
**Owner:** HRM Platform Team
**Depends on:** H-01 (Employee core), H-02 (Attendance), H-03 (Leave), H-08 (Performance)
**Author date:** 2026-05-17

---

## 1. Purpose

Deliver a unified Analytics surface inside the HRM module that turns the operational data already produced by Employee, Attendance, Leave, Payroll and Performance into:

1. An executive **HR Analytics Dashboard** (headcount, turnover, avg tenure, gender ratio, department distribution).
2. **AI Insights** — a per-employee **attrition risk** model (rule-based scoring v1, swappable for an ML provider in v2).
3. A **DEI Dashboard** (diversity metrics, pay equity gaps).
4. **Pulse Surveys** (create / send / collect / aggregate anonymously).
5. **Workforce Planning** (headcount targets vs actuals, per department).

All routes are tenant-scoped, HRMAC-gated, and audited. Heavy aggregations are computed server-side and shipped either as flat Inertia props or via `Inertia::defer()` for above-the-fold render speed.

---

## 2. HRMAC Hierarchy (additions to `packages/aero-hrm/config/module.php`)

```php
'analytics' => [
    'label' => 'Analytics',
    'icon'  => 'ChartBarIcon',
    'order' => 90,
    'children' => [
        'dashboard'           => ['label' => 'HR Dashboard',       'actions' => ['view']],
        'attrition'           => ['label' => 'Attrition & Risk',   'actions' => ['view', 'export']],
        'dei'                 => ['label' => 'DEI',                'actions' => ['view', 'export']],
        'pulse-surveys'       => ['label' => 'Pulse Surveys',      'actions' => ['view', 'edit', 'send', 'results']],
        'workforce-planning'  => ['label' => 'Workforce Planning', 'actions' => ['view', 'edit']],
    ],
],
```

Resulting HRMAC paths:

- `hrm.hr-analytics.workforce-overview.view`
- `hrm.ai-analytics.attrition-predictions.view` / `.run`
- `hrm.workforce-planning.dei-analytics.view` / `.manage`
- `hrm.pulse-surveys.survey-list.view` / `.create` / `.publish` / `.analyze`
- `hrm.workforce-planning.workforce-plans.view` / `.update`

---

## 3. Data Model

### 3.1 Migrations

**`2026_05_17_000010_create_hrm_pulse_surveys_table.php`**

```php
Schema::create('hrm_pulse_surveys', function (Blueprint $t) {
    $t->id();
    $t->string('title');
    $t->text('description')->nullable();
    $t->json('questions');               // [{id, type:'scale|text|choice', text, options?}]
    $t->json('audience_filter')->nullable(); // {department_ids:[], location_ids:[], min_tenure_days:?}
    $t->enum('status', ['draft','active','closed'])->default('draft');
    $t->timestamp('opens_at')->nullable();
    $t->timestamp('closes_at')->nullable();
    $t->boolean('anonymous')->default(true);
    $t->foreignId('created_by')->constrained('users');
    $t->timestamps();
    $t->index(['status', 'closes_at']);
});
```

**`2026_05_17_000011_create_hrm_pulse_responses_table.php`**

```php
Schema::create('hrm_pulse_responses', function (Blueprint $t) {
    $t->id();
    $t->foreignId('survey_id')->constrained('hrm_pulse_surveys')->cascadeOnDelete();
    // Salted hash of (survey_id, employee_id, tenant_secret) — preserves anonymity but blocks duplicates.
    $t->string('respondent_hash', 64);
    $t->json('answers'); // [{question_id, value}]
    $t->json('demographics_snapshot')->nullable(); // {department_id, gender, tenure_bucket}
    $t->timestamp('submitted_at');
    $t->unique(['survey_id', 'respondent_hash']);
});
```

**`2026_05_17_000012_create_hrm_workforce_plans_table.php`**

```php
Schema::create('hrm_workforce_plans', function (Blueprint $t) {
    $t->id();
    $t->unsignedSmallInteger('fiscal_year');
    $t->foreignId('department_id')->constrained('hrm_departments');
    $t->unsignedInteger('target_headcount');
    $t->unsignedInteger('target_hires')->default(0);
    $t->unsignedInteger('target_attrition')->default(0);
    $t->text('notes')->nullable();
    $t->foreignId('updated_by')->nullable()->constrained('users');
    $t->timestamps();
    $t->unique(['fiscal_year', 'department_id']);
});
```

**`2026_05_17_000013_create_hrm_attrition_risk_scores_table.php`** (cache table populated by a daily job)

```php
Schema::create('hrm_attrition_risk_scores', function (Blueprint $t) {
    $t->id();
    $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $t->decimal('score', 5, 4); // 0.0000 - 1.0000
    $t->enum('band', ['low','medium','high','critical']);
    $t->json('factors'); // [{name, weight, value}]
    $t->timestamp('computed_at');
    $t->unique('employee_id');
});
```

### 3.2 Models

`PulseSurvey`, `PulseResponse`, `WorkforcePlan`, `AttritionRiskScore` — all extend `TenantModel` (single-mode per project standard).

---

## 4. Services

`packages/aero-hrm/src/Services/Analytics/`

| Service | Purpose |
|---------|---------|
| `HeadcountAnalyticsService` | Headcount snapshots, dept distribution, gender ratio, avg tenure. |
| `TurnoverAnalyticsService`  | Monthly turnover %, 12-month rolling, voluntary vs involuntary. |
| `AttritionRiskService`      | Rule-based scoring v1 (factors: tenure, last_review, leave_anomaly, comp_ratio, manager_changes). |
| `DEIService`                | Gender / age band / ethnicity distribution + pay-gap calc per role band. |
| `PulseSurveyService`        | Audience resolution, hash generation, aggregation, anti-deanonymization (min response threshold = 5). |
| `WorkforcePlanService`      | Plan vs actual diff per department. |

### 4.1 Sample SQL — Headcount KPIs

```php
// HeadcountAnalyticsService::kpis()
$active = DB::table('employees')
    ->where('employment_status', 'active')
    ->count();

$avgTenureDays = DB::table('employees')
    ->where('employment_status', 'active')
    ->selectRaw('AVG(julianday("now") - julianday(hire_date)) as d')
    ->value('d');

$genderRatio = DB::table('employees')
    ->where('employment_status','active')
    ->selectRaw('gender, COUNT(*) as c')
    ->groupBy('gender')->pluck('c','gender'); // ['male'=>120,'female'=>98,'other'=>4]

$byDept = DB::table('employees as e')
    ->join('hrm_departments as d', 'd.id', '=', 'e.department_id')
    ->where('e.employment_status','active')
    ->selectRaw('d.id, d.name, COUNT(e.id) as headcount')
    ->groupBy('d.id','d.name')
    ->orderByDesc('headcount')
    ->get();

return compact('active','avgTenureDays','genderRatio','byDept');
```

### 4.2 Sample SQL — 12-Month Turnover Trend

```php
// TurnoverAnalyticsService::monthlyTrend()
return DB::table('employees')
    ->selectRaw("strftime('%Y-%m', terminated_at) as month, COUNT(*) as leavers")
    ->whereNotNull('terminated_at')
    ->where('terminated_at', '>=', now()->subMonths(12))
    ->groupBy('month')
    ->orderBy('month')
    ->get();
```

### 4.3 Attrition Risk — Rule-Based v1

```php
final class AttritionRiskService
{
    public function score(Employee $e): array
    {
        $factors = [];

        // Tenure factor: <12mo and >5y both elevated
        $tenureYears = $e->hire_date?->diffInYears(now()) ?? 0;
        $factors[] = ['name'=>'tenure', 'weight'=>0.20,
            'value'=>$tenureYears < 1 ? 0.7 : ($tenureYears > 5 ? 0.5 : 0.2)];

        // Performance: low rating
        $lastReview = $e->performanceReviews()->latest('completed_at')->first();
        $rating = $lastReview?->overall_rating ?? 3;
        $factors[] = ['name'=>'performance', 'weight'=>0.25,
            'value'=>$rating <= 2 ? 0.9 : ($rating == 3 ? 0.4 : 0.1)];

        // Comp ratio (current_salary / band_midpoint)
        $compRatio = $e->salary && $e->jobBand?->midpoint
            ? $e->salary / $e->jobBand->midpoint : 1.0;
        $factors[] = ['name'=>'comp_ratio', 'weight'=>0.20,
            'value'=>$compRatio < 0.85 ? 0.8 : ($compRatio < 0.95 ? 0.4 : 0.1)];

        // Leave anomaly: > 1.5x dept average sick leave last 90 days
        $sick = $e->leaveRequests()->where('type','sick')
            ->where('start_date','>=', now()->subDays(90))->count();
        $factors[] = ['name'=>'leave_anomaly', 'weight'=>0.15,
            'value'=>$sick >= 5 ? 0.7 : ($sick >= 3 ? 0.4 : 0.1)];

        // Manager churn: >= 2 manager changes in 12 months
        $managerChanges = $e->managerHistory()
            ->where('changed_at','>=', now()->subYear())->count();
        $factors[] = ['name'=>'manager_churn', 'weight'=>0.20,
            'value'=>$managerChanges >= 2 ? 0.8 : ($managerChanges == 1 ? 0.3 : 0.1)];

        $score = collect($factors)->sum(fn($f) => $f['weight'] * $f['value']);
        $band  = match (true) {
            $score >= 0.70 => 'critical',
            $score >= 0.50 => 'high',
            $score >= 0.30 => 'medium',
            default        => 'low',
        };

        return compact('score','band','factors');
    }
}
```

A nightly `ComputeAttritionRiskJob` writes results into `hrm_attrition_risk_scores`. Controllers read the cache table — never recompute per-request.

---

## 5. Controllers (`packages/aero-hrm/src/Http/Controllers/Analytics/`)

All controllers inject `AuditServiceInterface` and use HRMAC middleware. Routes apply `auth`, `tenant`, and the relevant `hrmac:*` gate.

### 5.1 `AnalyticsDashboardController`

```php
final class AnalyticsDashboardController extends Controller
{
    public function __construct(
        private HeadcountAnalyticsService $headcount,
        private TurnoverAnalyticsService $turnover,
    ) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Analytics/Dashboard', [
            'kpis'         => $this->headcount->kpis(),     // shipped immediately
            'turnover'     => Inertia::defer(fn () => $this->turnover->monthlyTrend()),
            'distribution' => Inertia::defer(fn () => $this->headcount->kpis()['byDept']),
        ]);
    }
}
```

### 5.2 `AttritionController`

```php
public function index(Request $r): Response
{
    $filters = $r->only(['band','department_id','search']);

    $rows = AttritionRiskScore::query()
        ->with('employee:id,first_name,last_name,department_id,job_title')
        ->when($filters['band'] ?? null, fn ($q,$b) => $q->where('band',$b))
        ->when($filters['department_id'] ?? null,
            fn ($q,$d) => $q->whereHas('employee', fn ($e) => $e->where('department_id',$d)))
        ->orderByDesc('score')
        ->paginate(25)
        ->withQueryString();

    return Inertia::render('HRM/Analytics/Attrition', [
        'risks'   => $rows,
        'filters' => $filters,
        'bands'   => ['low','medium','high','critical'],
    ]);
}
```

### 5.3 `DEIController`

```php
public function index(): Response
{
    return Inertia::render('HRM/Analytics/DEI', [
        'gender'    => $this->dei->genderDistribution(),
        'ageBands'  => $this->dei->ageBands(),
        'payGap'    => $this->dei->payGapByRoleBand(), // [{band, male_avg, female_avg, gap_pct}]
        'leadership'=> $this->dei->leadershipRepresentation(),
    ]);
}
```

### 5.4 `PulseSurveyController`

```php
public function index(): Response { /* paginated list + filters */ }

public function create(): Response
{
    return Inertia::render('HRM/Analytics/PulseSurveys/Create', [
        'departments' => Department::select('id','name')->get(),
    ]);
}

public function store(StorePulseSurveyRequest $r): RedirectResponse
{
    $survey = PulseSurvey::create([...$r->validated(), 'created_by'=>$r->user()->id]);
    $this->audit->log('PULSE_SURVEY_CREATED', $survey);
    return redirect()->route('hrm.analytics.pulse-surveys.index');
}

public function send(PulseSurvey $survey): RedirectResponse
{
    abort_unless($survey->status === 'draft', 422, 'Already active');
    $survey->update(['status'=>'active','opens_at'=>now()]);
    dispatch(new SendPulseSurveyJob($survey));
    $this->audit->log('PULSE_SURVEY_SENT', $survey);
    return back()->with('success','Survey sent');
}

public function results(PulseSurvey $survey): Response
{
    $count = $survey->responses()->count();
    if ($count < 5) {
        return Inertia::render('HRM/Analytics/PulseSurveys/Results', [
            'survey' => $survey,
            'suppressed' => true,
            'responseCount' => $count,
        ]);
    }
    return Inertia::render('HRM/Analytics/PulseSurveys/Results', [
        'survey'        => $survey,
        'aggregates'    => $this->pulse->aggregate($survey),
        'responseCount' => $count,
        'suppressed'    => false,
    ]);
}
```

### 5.5 `WorkforcePlanningController`

```php
public function index(Request $r): Response
{
    $year = (int) $r->input('fiscal_year', now()->year);

    $rows = DB::table('hrm_departments as d')
        ->leftJoin('hrm_workforce_plans as p', function ($j) use ($year) {
            $j->on('p.department_id','=','d.id')->where('p.fiscal_year','=',$year);
        })
        ->selectRaw('d.id, d.name, p.target_headcount, p.target_hires, p.target_attrition,
            (SELECT COUNT(*) FROM hrm_employees e
              WHERE e.department_id = d.id AND e.employment_status = "active") as actual_headcount')
        ->orderBy('d.name')
        ->get();

    return Inertia::render('HRM/Analytics/WorkforcePlanning', [
        'fiscalYear' => $year,
        'rows'       => $rows,
    ]);
}

public function update(UpdateWorkforcePlanRequest $r): RedirectResponse
{
    foreach ($r->validated('plans') as $row) {
        WorkforcePlan::updateOrCreate(
            ['fiscal_year'=>$row['fiscal_year'],'department_id'=>$row['department_id']],
            [...$row, 'updated_by'=>$r->user()->id],
        );
    }
    $this->audit->log('WORKFORCE_PLAN_UPDATED', null, ['fiscal_year'=>$r->input('fiscal_year')]);
    return back()->with('success','Plan updated');
}
```

---

## 6. Routes (`packages/aero-hrm/routes/tenant.php`)

```php
Route::prefix('hrm/analytics')->name('hrm.analytics.')->middleware(['auth','tenant'])->group(function () {
    Route::get('dashboard', [AnalyticsDashboardController::class,'index'])
        ->middleware('hrmac:hrm.hr-analytics.workforce-overview.view')->name('dashboard');

    Route::get('attrition', [AttritionController::class,'index'])
        ->middleware('hrmac:hrm.ai-analytics.attrition-predictions.view')->name('attrition.index');

    Route::get('dei', [DEIController::class,'index'])
        ->middleware('hrmac:hrm.workforce-planning.dei-analytics.view')->name('dei.index');

    Route::prefix('pulse-surveys')->name('pulse-surveys.')->group(function () {
        Route::get('/',              [PulseSurveyController::class,'index'])->middleware('hrmac:hrm.pulse-surveys.survey-list.view')->name('index');
        Route::get('create',         [PulseSurveyController::class,'create'])->middleware('hrmac:hrm.pulse-surveys.survey-list.create')->name('create');
        Route::post('/',             [PulseSurveyController::class,'store'])->middleware('hrmac:hrm.pulse-surveys.survey-list.create')->name('store');
        Route::post('{survey}/send', [PulseSurveyController::class,'send'])->middleware('hrmac:hrm.pulse-surveys.survey-list.publish')->name('send');
        Route::get('{survey}/results',[PulseSurveyController::class,'results'])->middleware('hrmac:hrm.pulse-surveys.survey-list.analyze')->name('results');
    });

    Route::get('workforce-planning',  [WorkforcePlanningController::class,'index'])->middleware('hrmac:hrm.workforce-planning.workforce-plans.view')->name('workforce-planning.index');
    Route::put('workforce-planning',  [WorkforcePlanningController::class,'update'])->middleware('hrmac:hrm.workforce-planning.workforce-plans.update')->name('workforce-planning.update');
});
```

---

## 7. React Pages (`packages/aero-ui/resources/js/Pages/HRM/Analytics/`)

### 7.1 `Dashboard.jsx`

```jsx
import { Deferred } from '@inertiajs/react';
import { Card, CardBody, CardHeader } from '@heroui/react';
import { BarChart, LineChart, PieChart } from '@/Components/Charts';
import App from '../../App.jsx';

export default function Dashboard({ kpis, turnover, distribution }) {
  return (
    <div className="p-6 space-y-6">
      <div className="grid grid-cols-4 gap-4">
        <KpiCard label="Active Headcount" value={kpis.active} />
        <KpiCard label="Avg Tenure (yrs)" value={(kpis.avgTenureDays / 365).toFixed(1)} />
        <KpiCard label="Female %" value={pct(kpis.genderRatio.female, kpis.active)} />
        <KpiCard label="Departments" value={kpis.byDept.length} />
      </div>

      <Card>
        <CardHeader>12-Month Turnover</CardHeader>
        <CardBody>
          <Deferred data="turnover" fallback={<Skeleton h={240} />}>
            {(rows) => <LineChart xKey="month" yKey="leavers" data={rows} />}
          </Deferred>
        </CardBody>
      </Card>

      <Card>
        <CardHeader>Headcount by Department</CardHeader>
        <CardBody>
          <Deferred data="distribution" fallback={<Skeleton h={300} />}>
            {(rows) => <BarChart xKey="name" yKey="headcount" data={rows} />}
          </Deferred>
        </CardBody>
      </Card>
    </div>
  );
}
const pct = (n,d) => d ? `${Math.round((n/d)*100)}%` : '0%';
Dashboard.layout = page => <App title="HR Analytics">{page}</App>;
```

### 7.2 `Attrition.jsx`

```jsx
import { Link, router } from '@inertiajs/react';
import { Table, TableHeader, TableBody, TableColumn, TableRow, TableCell, Chip, Input, Select, SelectItem } from '@heroui/react';
import App from '../../App.jsx';

const bandColor = { low:'success', medium:'warning', high:'danger', critical:'danger' };

export default function Attrition({ risks, filters, bands }) {
  const update = (patch) => router.get(route('hrm.analytics.attrition.index'),
    { ...filters, ...patch }, { preserveState:true, replace:true });

  return (
    <div className="p-6 space-y-4">
      <div className="flex gap-3">
        <Input placeholder="Search employee" defaultValue={filters.search} onBlur={e=>update({search:e.target.value})}/>
        <Select selectedKeys={[filters.band ?? '']} onSelectionChange={(k)=>update({band:[...k][0]})}>
          {bands.map(b => <SelectItem key={b}>{b}</SelectItem>)}
        </Select>
      </div>

      <Table aria-label="Attrition risks">
        <TableHeader>
          <TableColumn>Employee</TableColumn>
          <TableColumn>Score</TableColumn>
          <TableColumn>Band</TableColumn>
          <TableColumn>Top Factors</TableColumn>
        </TableHeader>
        <TableBody items={risks.data}>
          {(r) => (
            <TableRow key={r.id}>
              <TableCell>{r.employee.first_name} {r.employee.last_name}</TableCell>
              <TableCell>{(r.score*100).toFixed(0)}%</TableCell>
              <TableCell><Chip color={bandColor[r.band]}>{r.band}</Chip></TableCell>
              <TableCell>{r.factors.slice(0,2).map(f=>f.name).join(', ')}</TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
Attrition.layout = page => <App title="Attrition Risk">{page}</App>;
```

### 7.3 `DEI.jsx`, `PulseSurveys/Index.jsx`, `PulseSurveys/Create.jsx`, `PulseSurveys/Results.jsx`, `WorkforcePlanning.jsx`

Each follows the same shape: Inertia flat props, HeroUI primitives, `route()` helper for navigation, charts via `@/Components/Charts`, `Page.layout = page => <App title="...">{page}</App>`.

`Results.jsx` enforces the **anonymity guardrail** in UI:

```jsx
{suppressed
  ? <Alert color="warning">Results are hidden until at least 5 responses are received ({responseCount}/5).</Alert>
  : <ResultsBreakdown aggregates={aggregates} />}
```

---

## 8. Audit Events

| Event | Where |
|-------|-------|
| `PULSE_SURVEY_CREATED` | `PulseSurveyController@store` |
| `PULSE_SURVEY_SENT`    | `PulseSurveyController@send` |
| `PULSE_SURVEY_CLOSED`  | `ClosePulseSurveyJob` |
| `WORKFORCE_PLAN_UPDATED` | `WorkforcePlanningController@update` |
| `ATTRITION_SNAPSHOT_COMPUTED` | `ComputeAttritionRiskJob` |
| `ANALYTICS_EXPORT` | DEI / Attrition CSV export endpoints |

---

## 9. Tests (`packages/aero-hrm/tests/Feature/Analytics/`)

PHPUnit 11, Orchestra Testbench, sqlite `:memory:`. Providers loaded: `AeroCoreServiceProvider`, `AeroHrmServiceProvider`.

```php
final class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_kpis(): void
    {
        $user = $this->actingAsUserWithPerms(['hrm.hr-analytics.workforce-overview.view']);
        Employee::factory()->count(10)->active()->create();

        $this->get(route('hrm.analytics.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('HRM/Analytics/Dashboard')
                ->where('kpis.active', 10)
                ->has('kpis.byDept'));
    }
}

final class PulseSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_pulse_survey(): void
    {
        $user = $this->actingAsUserWithPerms(['hrm.pulse-surveys.survey-list.create']);

        $this->post(route('hrm.analytics.pulse-surveys.store'), [
            'title' => 'May Pulse',
            'questions' => [['id'=>'q1','type'=>'scale','text'=>'How happy are you?']],
            'anonymous' => true,
            'closes_at' => now()->addDays(7)->toIso8601String(),
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_pulse_surveys', ['title'=>'May Pulse','status'=>'draft']);
    }

    public function test_results_are_suppressed_below_threshold(): void
    {
        $user = $this->actingAsUserWithPerms(['hrm.pulse-surveys.survey-list.analyze']);
        $s = PulseSurvey::factory()->active()->create();
        PulseResponse::factory()->count(3)->for($s,'survey')->create();

        $this->get(route('hrm.pulse-surveys.survey-list.analyze', $s))
            ->assertInertia(fn (Assert $p) => $p->where('suppressed', true));
    }

    public function test_results_aggregate_when_threshold_met(): void
    {
        $user = $this->actingAsUserWithPerms(['hrm.pulse-surveys.survey-list.analyze']);
        $s = PulseSurvey::factory()->active()->create([
            'questions' => [['id'=>'q1','type'=>'scale','text'=>'Score']],
        ]);
        PulseResponse::factory()->count(6)->for($s,'survey')->state(new Sequence(
            ['answers'=>[['question_id'=>'q1','value'=>5]]],
            ['answers'=>[['question_id'=>'q1','value'=>4]]],
            ['answers'=>[['question_id'=>'q1','value'=>3]]],
            ['answers'=>[['question_id'=>'q1','value'=>5]]],
            ['answers'=>[['question_id'=>'q1','value'=>4]]],
            ['answers'=>[['question_id'=>'q1','value'=>5]]],
        ))->create();

        $this->get(route('hrm.pulse-surveys.survey-list.analyze', $s))
            ->assertInertia(fn (Assert $p) => $p
                ->where('suppressed', false)
                ->where('aggregates.q1.average', 4.33)
                ->where('responseCount', 6));
    }
}

final class WorkforcePlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_returns_actual_vs_target(): void
    {
        $user = $this->actingAsUserWithPerms(['hrm.workforce-planning.workforce-plans.view']);
        $d = Department::factory()->create(['name'=>'Engineering']);
        Employee::factory()->count(7)->active()->for($d,'department')->create();
        WorkforcePlan::factory()->for($d,'department')->create([
            'fiscal_year'=>2026,'target_headcount'=>10,
        ]);

        $this->get(route('hrm.analytics.workforce-planning.index', ['fiscal_year'=>2026]))
            ->assertInertia(fn (Assert $p) => $p
                ->where('rows.0.actual_headcount', 7)
                ->where('rows.0.target_headcount', 10));
    }
}

final class AttritionRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_risk_when_low_perf_and_low_comp(): void
    {
        $e = Employee::factory()->create(['salary'=>50000,'hire_date'=>now()->subYears(6)]);
        $e->performanceReviews()->create(['overall_rating'=>2,'completed_at'=>now()]);

        $result = app(AttritionRiskService::class)->score($e->fresh());

        $this->assertGreaterThan(0.5, $result['score']);
        $this->assertContains($result['band'], ['high','critical']);
    }
}
```

---

## 10. Tasks (execution order)

1. **DB & Models** — add 4 migrations, factories, Eloquent models (`PulseSurvey`, `PulseResponse`, `WorkforcePlan`, `AttritionRiskScore`).
2. **Services** — implement `HeadcountAnalyticsService`, `TurnoverAnalyticsService`, `DEIService`, `AttritionRiskService`, `PulseSurveyService`, `WorkforcePlanService` under `src/Services/Analytics/`.
3. **HRMAC config** — extend `packages/aero-hrm/config/module.php` with the `analytics` subtree; bump `aero-hrmac` cache.
4. **Controllers & Routes** — 5 controllers, routes wired with `hrmac:*` middleware, FormRequests for store/update.
5. **Jobs** — `ComputeAttritionRiskJob` (daily, scheduled in `AeroHrmServiceProvider::register`), `SendPulseSurveyJob`, `ClosePulseSurveyJob`.
6. **React pages** — 7 pages under `packages/aero-ui/resources/js/Pages/HRM/Analytics/` using `App` layout, HeroUI + `Inertia.defer` rendering.
7. **Tests** — 5 PHPUnit feature tests as specified plus factories; Playwright smoke for Dashboard + Pulse create.

---

## 11. Out of Scope

- ML-backed attrition (deferred to H-10.1 — pluggable `AttritionRiskProvider` interface is created now, default impl is rule-based).
- Real-time streaming dashboards.
- Cross-tenant benchmarks.
