# Plan H-11: Benefits Management

**Module:** `packages/aero-hrm`
**Status:** Draft
**Owner:** HRM Platform Team
**Depends on:** H-01 (Employee core), H-05 (Payroll for deduction hand-off)
**Author date:** 2026-05-17

---

## 1. Purpose

Implement an end-to-end Benefits Administration surface:

1. **Benefits Catalog** — admin maintains the menu of company benefits (health, dental, life, pension, etc.) with cost structure and eligibility rules.
2. **Enrollment Periods** — admin schedules open enrollment windows tied to selected benefits and an eligible employee audience.
3. **Open Enrollment** — eligible employees see their active period and enroll / waive / pick dependents within the window.
4. **Enrollments** — admin oversight of all employee enrollments.
5. Hand-off — employee deductions are emitted to payroll via an internal contract (no payroll code here; we only fire an event).

Every route is tenant-scoped and HRMAC-gated. Every state transition is audited.

---

## 2. HRMAC Hierarchy (additions to `packages/aero-hrm/config/module.php`)

```php
'benefits' => [
    'label' => 'Benefits',
    'icon'  => 'HeartIcon',
    'order' => 75,
    'children' => [
        'catalog'             => ['label' => 'Catalog',            'actions' => ['view','edit']],
        'enrollment-periods'  => ['label' => 'Enrollment Periods', 'actions' => ['view','edit','activate']],
        'open-enrollment'     => ['label' => 'Open Enrollment',    'actions' => ['view','edit']],
        'enrollments'         => ['label' => 'Enrollments',        'actions' => ['view']],
    ],
],
```

HRMAC paths:

- `hrm.benefits.benefit-catalog.view` / `.edit`
- `hrm.benefits.enrollment-periods.view` / `.edit` / `.activate`
- `hrm.benefits.open-enrollment.view` / `.edit`
- `hrm.benefits.enrollments.view`

---

## 3. Data Model

### 3.1 `hrm_benefits` (the catalog)

```php
Schema::create('hrm_benefits', function (Blueprint $t) {
    $t->id();
    $t->string('code')->unique();      // e.g. HEALTH_PPO, DENTAL_BASIC
    $t->string('name');
    $t->enum('category', ['health','dental','vision','life','disability','pension','wellness','other']);
    $t->text('description')->nullable();
    $t->string('provider')->nullable();
    $t->decimal('employee_cost', 12, 2)->default(0);   // per pay period
    $t->decimal('employer_cost', 12, 2)->default(0);
    $t->enum('frequency', ['monthly','biweekly','weekly','annual'])->default('monthly');
    $t->boolean('allows_dependents')->default(false);
    $t->decimal('dependent_cost', 12, 2)->nullable();
    $t->json('eligibility_rules')->nullable(); // {min_tenure_days, employment_types:['full_time']}
    $t->boolean('active')->default(true);
    $t->timestamps();
});
```

### 3.2 `hrm_benefit_enrollment_periods`

```php
Schema::create('hrm_benefit_enrollment_periods', function (Blueprint $t) {
    $t->id();
    $t->string('name');                            // "2026 Open Enrollment"
    $t->date('starts_at');
    $t->date('ends_at');
    $t->date('coverage_starts_at');
    $t->date('coverage_ends_at');
    $t->json('audience_filter')->nullable();       // {department_ids:[],location_ids:[],employment_types:[]}
    $t->enum('status', ['draft','active','closed'])->default('draft');
    $t->foreignId('created_by')->constrained('users');
    $t->timestamp('activated_at')->nullable();
    $t->timestamps();
    $t->index(['status','starts_at','ends_at']);
});

Schema::create('hrm_benefit_enrollment_period_benefits', function (Blueprint $t) {
    $t->id();
    $t->foreignId('period_id')->constrained('hrm_benefit_enrollment_periods')->cascadeOnDelete();
    $t->foreignId('benefit_id')->constrained('hrm_benefits')->cascadeOnDelete();
    $t->boolean('required')->default(false);
    $t->unique(['period_id','benefit_id']);
});
```

### 3.3 `hrm_benefit_enrollments` (per employee × benefit × period)

```php
Schema::create('hrm_benefit_enrollments', function (Blueprint $t) {
    $t->id();
    $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $t->foreignId('period_id')->constrained('hrm_benefit_enrollment_periods');
    $t->foreignId('benefit_id')->constrained('hrm_benefits');
    $t->enum('status', ['enrolled','waived'])->default('enrolled');
    $t->unsignedTinyInteger('dependents_count')->default(0);
    $t->decimal('employee_cost_snapshot', 12, 2);
    $t->decimal('employer_cost_snapshot', 12, 2);
    $t->text('waiver_reason')->nullable();
    $t->timestamp('elected_at');
    $t->timestamps();
    $t->unique(['employee_id','period_id','benefit_id']);
});
```

### 3.4 Models

`Benefit`, `BenefitEnrollmentPeriod`, `BenefitEnrollment` — all `TenantModel`. `BenefitEnrollmentPeriod` belongsToMany `Benefit` through pivot.

---

## 4. Services (`packages/aero-hrm/src/Services/Benefits/`)

| Service | Responsibility |
|---------|----------------|
| `BenefitCatalogService`  | Catalog CRUD + soft-deactivate guard. |
| `EnrollmentPeriodService`| Activate / close period. Pre-flight: at least one benefit attached. |
| `EligibilityService`     | Resolves whether an employee is eligible for a given benefit at a given date. |
| `OpenEnrollmentService`  | Computes employee's current period + eligible benefits + existing elections. Persists elections atomically. |

### 4.1 EligibilityService

```php
final class EligibilityService
{
    public function isEligible(Employee $e, Benefit $b, Carbon $asOf): bool
    {
        if (! $b->active) return false;
        $rules = $b->eligibility_rules ?? [];

        if (($min = $rules['min_tenure_days'] ?? null)
            && $e->hire_date && $e->hire_date->diffInDays($asOf) < $min) {
            return false;
        }
        if ($types = $rules['employment_types'] ?? null) {
            if (! in_array($e->employment_type, $types, true)) return false;
        }
        return true;
    }
}
```

### 4.2 OpenEnrollmentService

```php
final class OpenEnrollmentService
{
    public function __construct(
        private EligibilityService $eligibility,
        private AuditServiceInterface $audit,
    ) {}

    public function activePeriodFor(Employee $e, Carbon $today): ?BenefitEnrollmentPeriod
    {
        return BenefitEnrollmentPeriod::query()
            ->where('status','active')
            ->whereDate('starts_at','<=',$today)
            ->whereDate('ends_at','>=',$today)
            ->get()
            ->first(fn ($p) => $this->matchesAudience($e, $p->audience_filter));
    }

    public function eligibleBenefits(Employee $e, BenefitEnrollmentPeriod $p): Collection
    {
        return $p->benefits->filter(fn (Benefit $b) =>
            $this->eligibility->isEligible($e, $b, now()));
    }

    public function elect(Employee $e, BenefitEnrollmentPeriod $p, array $elections): void
    {
        DB::transaction(function () use ($e, $p, $elections) {
            foreach ($elections as $row) {
                $benefit = Benefit::findOrFail($row['benefit_id']);
                abort_unless($this->eligibility->isEligible($e, $benefit, now()), 422);

                $enrollment = BenefitEnrollment::updateOrCreate(
                    ['employee_id'=>$e->id,'period_id'=>$p->id,'benefit_id'=>$benefit->id],
                    [
                        'status' => $row['status'],
                        'dependents_count' => $row['dependents_count'] ?? 0,
                        'waiver_reason'    => $row['waiver_reason'] ?? null,
                        'employee_cost_snapshot' => $benefit->employee_cost
                            + ($benefit->dependent_cost ?? 0) * ($row['dependents_count'] ?? 0),
                        'employer_cost_snapshot' => $benefit->employer_cost,
                        'elected_at' => now(),
                    ],
                );

                $event = $row['status'] === 'enrolled' ? 'BENEFIT_ENROLLED' : 'BENEFIT_WAIVED';
                $this->audit->log($event, $enrollment);
            }
        });

        event(new BenefitElectionsCommitted($e->id, $p->id));
    }

    private function matchesAudience(Employee $e, ?array $filter): bool
    {
        if (! $filter) return true;
        if ($ids = $filter['department_ids'] ?? null) {
            if (! in_array($e->department_id, $ids, true)) return false;
        }
        if ($types = $filter['employment_types'] ?? null) {
            if (! in_array($e->employment_type, $types, true)) return false;
        }
        return true;
    }
}
```

---

## 5. Controllers (`packages/aero-hrm/src/Http/Controllers/Benefits/`)

### 5.1 `BenefitCatalogController`

```php
final class BenefitCatalogController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['search','category','active']);

        return Inertia::render('HRM/Benefits/Catalog/Index', [
            'benefits' => Benefit::query()
                ->when($filters['search']   ?? null, fn ($q,$s) => $q->where('name','like',"%$s%"))
                ->when($filters['category'] ?? null, fn ($q,$c) => $q->where('category',$c))
                ->when(isset($filters['active']), fn ($q) => $q->where('active',(bool)$filters['active']))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters'    => $filters,
            'categories' => ['health','dental','vision','life','disability','pension','wellness','other'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Benefits/Catalog/Create');
    }

    public function store(StoreBenefitRequest $r): RedirectResponse
    {
        $b = Benefit::create($r->validated());
        $this->audit->log('BENEFIT_CREATED', $b);
        return redirect()->route('hrm.benefits.catalog.index');
    }

    public function update(UpdateBenefitRequest $r, Benefit $benefit): RedirectResponse
    {
        $benefit->update($r->validated());
        $this->audit->log('BENEFIT_UPDATED', $benefit);
        return back()->with('success','Benefit updated');
    }

    public function destroy(Benefit $benefit): RedirectResponse
    {
        abort_if($benefit->enrollments()->exists(), 422, 'Has active enrollments — deactivate instead.');
        $benefit->delete();
        $this->audit->log('BENEFIT_DELETED', $benefit);
        return back();
    }
}
```

### 5.2 `EnrollmentPeriodController`

```php
public function index(): Response { /* paginated list with status filter */ }

public function create(): Response
{
    return Inertia::render('HRM/Benefits/EnrollmentPeriods/Create', [
        'benefits'    => Benefit::where('active',true)->orderBy('name')->get(['id','name','category']),
        'departments' => Department::select('id','name')->get(),
    ]);
}

public function store(StoreEnrollmentPeriodRequest $r): RedirectResponse
{
    $p = DB::transaction(function () use ($r) {
        $p = BenefitEnrollmentPeriod::create([
            ...$r->safe()->except('benefit_ids'),
            'created_by' => $r->user()->id,
        ]);
        $p->benefits()->sync($r->validated('benefit_ids'));
        return $p;
    });
    $this->audit->log('ENROLLMENT_PERIOD_CREATED', $p);
    return redirect()->route('hrm.benefits.enrollment-periods.show', $p);
}

public function show(BenefitEnrollmentPeriod $period): Response
{
    return Inertia::render('HRM/Benefits/EnrollmentPeriods/Show', [
        'period' => $period->load('benefits','creator:id,name'),
        'stats'  => [
            'enrolled'  => $period->enrollments()->where('status','enrolled')->count(),
            'waived'    => $period->enrollments()->where('status','waived')->count(),
            'employees' => $period->enrollments()->distinct('employee_id')->count('employee_id'),
        ],
    ]);
}

public function activate(BenefitEnrollmentPeriod $period): RedirectResponse
{
    abort_unless($period->status === 'draft', 422, 'Period already active or closed.');
    abort_if($period->benefits()->count() === 0, 422, 'Attach at least one benefit first.');

    $period->update(['status'=>'active','activated_at'=>now()]);
    $this->audit->log('ENROLLMENT_PERIOD_ACTIVATED', $period);
    dispatch(new NotifyEligibleEmployeesJob($period));

    return back()->with('success','Period activated');
}
```

### 5.3 `OpenEnrollmentController` (employee-facing)

```php
final class OpenEnrollmentController extends Controller
{
    public function __construct(private OpenEnrollmentService $svc) {}

    public function index(Request $r): Response
    {
        $employee = $r->user()->employee;
        $period   = $this->svc->activePeriodFor($employee, now());

        return Inertia::render('HRM/Benefits/OpenEnrollment/Index', [
            'period' => $period?->only(['id','name','starts_at','ends_at','coverage_starts_at','coverage_ends_at']),
            'eligibleBenefits' => $period
                ? $this->svc->eligibleBenefits($employee, $period)->values()
                : [],
            'myElections' => $period
                ? BenefitEnrollment::where('employee_id',$employee->id)->where('period_id',$period->id)->get()
                : [],
        ]);
    }

    public function enroll(EnrollBenefitsRequest $r): RedirectResponse
    {
        $employee = $r->user()->employee;
        $period   = BenefitEnrollmentPeriod::findOrFail($r->input('period_id'));

        abort_unless($period->status === 'active'
            && now()->between($period->starts_at, $period->ends_at),
            422, 'Enrollment window closed.');

        $this->svc->elect($employee, $period, $r->validated('elections'));

        return redirect()->route('hrm.benefits.open-enrollment.index')
            ->with('success','Your elections have been recorded.');
    }
}
```

### 5.4 `BenefitEnrollmentController` (admin)

```php
public function index(Request $r): Response
{
    $filters = $r->only(['period_id','benefit_id','status','search']);

    return Inertia::render('HRM/Benefits/Enrollments/Index', [
        'enrollments' => BenefitEnrollment::query()
            ->with(['employee:id,first_name,last_name,department_id','benefit:id,name','period:id,name'])
            ->when($filters['period_id']  ?? null, fn ($q,$v) => $q->where('period_id',$v))
            ->when($filters['benefit_id'] ?? null, fn ($q,$v) => $q->where('benefit_id',$v))
            ->when($filters['status']     ?? null, fn ($q,$v) => $q->where('status',$v))
            ->when($filters['search']     ?? null, fn ($q,$s) =>
                $q->whereHas('employee', fn ($e) =>
                    $e->whereRaw("first_name || ' ' || last_name like ?", ["%$s%"])))
            ->orderByDesc('elected_at')
            ->paginate(25)
            ->withQueryString(),
        'filters' => $filters,
        'periods' => BenefitEnrollmentPeriod::orderByDesc('starts_at')->get(['id','name']),
        'benefits'=> Benefit::orderBy('name')->get(['id','name']),
    ]);
}
```

---

## 6. Routes (`packages/aero-hrm/routes/tenant.php`)

```php
Route::prefix('hrm/benefits')->name('hrm.benefits.')->middleware(['auth','tenant'])->group(function () {

    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/',                 [BenefitCatalogController::class,'index'])->middleware('hrmac:hrm.benefits.benefit-catalog.view')->name('index');
        Route::get('create',            [BenefitCatalogController::class,'create'])->middleware('hrmac:hrm.benefits.benefit-catalog.edit')->name('create');
        Route::post('/',                [BenefitCatalogController::class,'store'])->middleware('hrmac:hrm.benefits.benefit-catalog.edit')->name('store');
        Route::put('{benefit}',         [BenefitCatalogController::class,'update'])->middleware('hrmac:hrm.benefits.benefit-catalog.edit')->name('update');
        Route::delete('{benefit}',      [BenefitCatalogController::class,'destroy'])->middleware('hrmac:hrm.benefits.benefit-catalog.edit')->name('destroy');
    });

    Route::prefix('enrollment-periods')->name('enrollment-periods.')->group(function () {
        Route::get('/',                 [EnrollmentPeriodController::class,'index'])->middleware('hrmac:hrm.benefits.enrollment-periods.view')->name('index');
        Route::get('create',            [EnrollmentPeriodController::class,'create'])->middleware('hrmac:hrm.benefits.enrollment-periods.edit')->name('create');
        Route::post('/',                [EnrollmentPeriodController::class,'store'])->middleware('hrmac:hrm.benefits.enrollment-periods.edit')->name('store');
        Route::get('{period}',          [EnrollmentPeriodController::class,'show'])->middleware('hrmac:hrm.benefits.enrollment-periods.view')->name('show');
        Route::post('{period}/activate',[EnrollmentPeriodController::class,'activate'])->middleware('hrmac:hrm.benefits.enrollment-periods.activate')->name('activate');
    });

    Route::prefix('open-enrollment')->name('open-enrollment.')->group(function () {
        Route::get('/',                 [OpenEnrollmentController::class,'index'])->middleware('hrmac:hrm.benefits.open-enrollment.view')->name('index');
        Route::post('enroll',           [OpenEnrollmentController::class,'enroll'])->middleware('hrmac:hrm.benefits.open-enrollment.edit')->name('enroll');
    });

    Route::get('enrollments',           [BenefitEnrollmentController::class,'index'])->middleware('hrmac:hrm.benefits.enrollments.view')->name('enrollments.index');
});
```

---

## 7. React Pages (`packages/aero-ui/resources/js/Pages/HRM/Benefits/`)

### 7.1 `Catalog/Index.jsx`

```jsx
import { Link, router } from '@inertiajs/react';
import { Button, Table, TableHeader, TableBody, TableColumn, TableRow, TableCell, Chip, Input, Select, SelectItem } from '@heroui/react';
import App from '../../../App.jsx';

export default function CatalogIndex({ benefits, filters, categories }) {
  const update = (patch) => router.get(route('hrm.benefits.catalog.index'),
    { ...filters, ...patch }, { preserveState:true, replace:true });

  return (
    <div className="p-6 space-y-4">
      <div className="flex justify-between">
        <h1 className="text-2xl font-semibold">Benefits Catalog</h1>
        <Button as={Link} href={route('hrm.benefits.catalog.create')} color="primary">New Benefit</Button>
      </div>

      <div className="flex gap-3">
        <Input placeholder="Search" defaultValue={filters.search} onBlur={e=>update({search:e.target.value})}/>
        <Select label="Category" selectedKeys={[filters.category ?? '']} onSelectionChange={k=>update({category:[...k][0]})}>
          {categories.map(c => <SelectItem key={c}>{c}</SelectItem>)}
        </Select>
      </div>

      <Table aria-label="Benefits catalog">
        <TableHeader>
          <TableColumn>Name</TableColumn>
          <TableColumn>Category</TableColumn>
          <TableColumn>Employee Cost</TableColumn>
          <TableColumn>Employer Cost</TableColumn>
          <TableColumn>Status</TableColumn>
        </TableHeader>
        <TableBody items={benefits.data}>
          {(b) => (
            <TableRow key={b.id}>
              <TableCell>{b.name}</TableCell>
              <TableCell><Chip>{b.category}</Chip></TableCell>
              <TableCell>${b.employee_cost}</TableCell>
              <TableCell>${b.employer_cost}</TableCell>
              <TableCell>{b.active ? <Chip color="success">Active</Chip> : <Chip>Inactive</Chip>}</TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
CatalogIndex.layout = page => <App title="Benefits Catalog">{page}</App>;
```

### 7.2 `OpenEnrollment/Enroll.jsx`

```jsx
import { useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Button, Checkbox, NumberInput, Textarea, RadioGroup, Radio } from '@heroui/react';
import App from '../../../App.jsx';

export default function Enroll({ period, eligibleBenefits, myElections }) {
  const { data, setData, post, processing, errors } = useForm({
    period_id: period.id,
    elections: eligibleBenefits.map(b => {
      const existing = myElections.find(e => e.benefit_id === b.id);
      return {
        benefit_id: b.id,
        status: existing?.status ?? 'enrolled',
        dependents_count: existing?.dependents_count ?? 0,
        waiver_reason: existing?.waiver_reason ?? '',
      };
    }),
  });

  const setElection = (idx, patch) => setData('elections',
    data.elections.map((e,i) => i===idx ? {...e, ...patch} : e));

  return (
    <div className="p-6 space-y-6">
      <h1 className="text-2xl font-semibold">{period.name}</h1>
      <p className="text-sm text-default-500">
        Window: {period.starts_at} → {period.ends_at} · Coverage: {period.coverage_starts_at} → {period.coverage_ends_at}
      </p>

      {eligibleBenefits.map((b, i) => (
        <Card key={b.id}>
          <CardHeader className="flex justify-between">
            <div>
              <strong>{b.name}</strong>
              <p className="text-xs text-default-500">{b.description}</p>
            </div>
            <span>${b.employee_cost}/{b.frequency}</span>
          </CardHeader>
          <CardBody>
            <RadioGroup
              value={data.elections[i].status}
              onValueChange={v => setElection(i, { status: v })}
              orientation="horizontal"
            >
              <Radio value="enrolled">Enroll</Radio>
              <Radio value="waived">Waive</Radio>
            </RadioGroup>

            {data.elections[i].status === 'enrolled' && b.allows_dependents && (
              <NumberInput label="Dependents" min={0} max={10}
                value={data.elections[i].dependents_count}
                onValueChange={v => setElection(i, { dependents_count: v })} />
            )}

            {data.elections[i].status === 'waived' && (
              <Textarea label="Waiver reason"
                value={data.elections[i].waiver_reason}
                onValueChange={v => setElection(i, { waiver_reason: v })} />
            )}
          </CardBody>
        </Card>
      ))}

      <Button color="primary" isLoading={processing}
        onPress={() => post(route('hrm.benefits.open-enrollment.enroll'))}>
        Submit Elections
      </Button>
    </div>
  );
}
Enroll.layout = page => <App title="Open Enrollment">{page}</App>;
```

The remaining pages (`Catalog/Create.jsx`, `EnrollmentPeriods/Index.jsx`, `EnrollmentPeriods/Create.jsx`, `OpenEnrollment/Index.jsx`, `Enrollments/Index.jsx`) follow the same flat-props pattern.

---

## 8. Audit Events

| Event | Where |
|-------|-------|
| `BENEFIT_CREATED` / `UPDATED` / `DELETED` | `BenefitCatalogController` |
| `ENROLLMENT_PERIOD_CREATED` | `EnrollmentPeriodController@store` |
| `ENROLLMENT_PERIOD_ACTIVATED` | `EnrollmentPeriodController@activate` |
| `BENEFIT_ENROLLED` | `OpenEnrollmentService::elect` (status=enrolled) |
| `BENEFIT_WAIVED` | `OpenEnrollmentService::elect` (status=waived) |

---

## 9. Tests (`packages/aero-hrm/tests/Feature/Benefits/`)

```php
final class BenefitCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_benefit(): void
    {
        $this->actingAsUserWithPerms(['hrm.benefits.benefit-catalog.edit']);

        $this->post(route('hrm.benefits.catalog.store'), [
            'code' => 'HEALTH_PPO',
            'name' => 'Health PPO',
            'category' => 'health',
            'employee_cost' => 120.00,
            'employer_cost' => 380.00,
            'frequency' => 'monthly',
            'allows_dependents' => true,
            'dependent_cost' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_benefits', ['code'=>'HEALTH_PPO']);
    }

    public function test_cannot_delete_benefit_with_enrollments(): void
    {
        $this->actingAsUserWithPerms(['hrm.benefits.benefit-catalog.edit']);
        $b = Benefit::factory()->create();
        BenefitEnrollment::factory()->for($b,'benefit')->create();

        $this->delete(route('hrm.benefits.catalog.destroy', $b))
            ->assertStatus(422);
    }
}

final class EnrollmentPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_cannot_activate_without_benefits(): void
    {
        $this->actingAsUserWithPerms(['hrm.benefits.enrollment-periods.activate']);
        $p = BenefitEnrollmentPeriod::factory()->draft()->create();

        $this->post(route('hrm.benefits.enrollment-periods.activate', $p))
            ->assertStatus(422);
    }

    public function test_period_activation_logs_audit(): void
    {
        $audit = $this->spy(AuditServiceInterface::class);
        $this->actingAsUserWithPerms(['hrm.benefits.enrollment-periods.activate']);
        $p = BenefitEnrollmentPeriod::factory()->draft()->hasBenefits(2)->create();

        $this->post(route('hrm.benefits.enrollment-periods.activate', $p))
            ->assertRedirect();

        $audit->shouldHaveReceived('log')->withArgs(fn ($e) => $e === 'ENROLLMENT_PERIOD_ACTIVATED');
    }
}

final class OpenEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_active_period(): void
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->givePermissionTo('hrm.benefits.open-enrollment.view');

        $period = BenefitEnrollmentPeriod::factory()->active()
            ->hasAttached(Benefit::factory()->count(2), [], 'benefits')
            ->create();

        $this->actingAs($user)
            ->get(route('hrm.benefits.open-enrollment.index'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('period.id', $period->id)
                ->has('eligibleBenefits', 2));
    }

    public function test_employee_can_enroll_in_benefits(): void
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->givePermissionTo('hrm.benefits.open-enrollment.edit');
        $period = BenefitEnrollmentPeriod::factory()->active()->create();
        $benefit = Benefit::factory()->create(['allows_dependents'=>true,'dependent_cost'=>50]);
        $period->benefits()->attach($benefit);

        $this->actingAs($user)
            ->post(route('hrm.benefits.open-enrollment.enroll'), [
                'period_id' => $period->id,
                'elections' => [
                    ['benefit_id'=>$benefit->id,'status'=>'enrolled','dependents_count'=>2],
                ],
            ])->assertRedirect();

        $this->assertDatabaseHas('hrm_benefit_enrollments', [
            'employee_id'=>$employee->id,
            'benefit_id'=>$benefit->id,
            'status'=>'enrolled',
            'dependents_count'=>2,
        ]);
    }

    public function test_employee_can_waive_with_reason(): void
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->givePermissionTo('hrm.benefits.open-enrollment.edit');
        $period = BenefitEnrollmentPeriod::factory()->active()->create();
        $benefit = Benefit::factory()->create();
        $period->benefits()->attach($benefit);

        $this->actingAs($user)->post(route('hrm.benefits.open-enrollment.enroll'), [
            'period_id'=>$period->id,
            'elections'=>[['benefit_id'=>$benefit->id,'status'=>'waived','waiver_reason'=>'Spouse coverage']],
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_benefit_enrollments', [
            'status'=>'waived','waiver_reason'=>'Spouse coverage',
        ]);
    }

    public function test_enrollment_blocked_outside_window(): void
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;
        $user->givePermissionTo('hrm.benefits.open-enrollment.edit');
        $period = BenefitEnrollmentPeriod::factory()->create([
            'status'=>'active','starts_at'=>now()->subDays(20),'ends_at'=>now()->subDay(),
        ]);

        $this->actingAs($user)->post(route('hrm.benefits.open-enrollment.enroll'), [
            'period_id'=>$period->id, 'elections'=>[],
        ])->assertStatus(422);
    }
}

final class EligibilityServiceTest extends TestCase
{
    public function test_min_tenure_rule(): void
    {
        $e = Employee::factory()->make(['hire_date'=>now()->subDays(10)]);
        $b = Benefit::factory()->make(['eligibility_rules'=>['min_tenure_days'=>90]]);
        $this->assertFalse(app(EligibilityService::class)->isEligible($e, $b, now()));
    }
}
```

---

## 10. Tasks (execution order)

1. **DB & Models** — 4 migrations (`hrm_benefits`, periods, pivot, enrollments), `Benefit`, `BenefitEnrollmentPeriod`, `BenefitEnrollment` models, factories.
2. **HRMAC config** — extend `packages/aero-hrm/config/module.php` with the `benefits` subtree.
3. **Services** — `BenefitCatalogService`, `EligibilityService`, `EnrollmentPeriodService`, `OpenEnrollmentService` under `src/Services/Benefits/`.
4. **Controllers + FormRequests + Routes** — 4 controllers, 4 FormRequests (`StoreBenefitRequest`, `UpdateBenefitRequest`, `StoreEnrollmentPeriodRequest`, `EnrollBenefitsRequest`), routes wired to `hrmac:*`.
5. **Events / Jobs** — `BenefitElectionsCommitted` event (payload: `employee_id`, `period_id`) for payroll integration, `NotifyEligibleEmployeesJob` for activation.
6. **React pages** — 7 pages under `Pages/HRM/Benefits/...` with `App` layout and HeroUI components.
7. **Tests** — 6 PHPUnit feature tests as specified, plus a service unit test for `EligibilityService`. Playwright smoke for enrollment flow.

---

## 11. Out of Scope

- Carrier integrations / EDI feeds (handled by H-11.1).
- Mid-year qualifying life events — schema is forward-compatible (period concept generalizes), implementation deferred.
- Dependent records (full demographic capture) — only count is stored in v1.
