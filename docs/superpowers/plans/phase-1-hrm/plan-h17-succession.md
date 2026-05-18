# Plan H-17 — Succession Planning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver succession planning: career paths with milestones, employee assignment + progress tracking, talent pool (high-potential employees), succession candidates for key roles, and an internal talent mobility board (internal job postings/transfers).

**Architecture:** Code lives in `packages/aero-hrm/src/{Models,Http,Services}/Succession/`. Progress against a milestone uses a `MilestoneProgressService` that maps competency/training completion to milestone status. All authenticated routes HRMAC-guarded; talent pool and candidate changes audited.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations and models

- [ ] Create migrations:
  - `2026_05_17_040001_create_career_paths_table.php`
  - `2026_05_17_040002_create_career_milestones_table.php`
  - `2026_05_17_040003_create_career_path_employees_table.php`
  - `2026_05_17_040004_create_talent_pool_members_table.php`
  - `2026_05_17_040005_create_succession_candidates_table.php`
  - `2026_05_17_040006_create_talent_mobility_postings_table.php`

```php
// 2026_05_17_040001_create_career_paths_table.php
Schema::create('career_paths', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug', 120)->unique();
    $table->text('description')->nullable();
    $table->foreignId('target_role_id')->nullable()->constrained('hrm_designations')->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// 2026_05_17_040002_create_career_milestones_table.php
Schema::create('career_milestones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('career_path_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->integer('order_index')->default(0);
    $table->json('requirements')->nullable(); // [{type:'training',id:1},{type:'competency',level:3}]
    $table->timestamps();
});

// 2026_05_17_040005_create_succession_candidates_table.php
Schema::create('succession_candidates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->constrained('hrm_designations')->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $table->string('readiness', 32); // ready_now, 1_2_years, 3_5_years
    $table->text('notes')->nullable();
    $table->foreignId('nominated_by')->constrained('users');
    $table->timestamps();
    $table->unique(['role_id','employee_id']);
});
```

- [ ] Create models:

```php
// packages/aero-hrm/src/Models/Succession/CareerPath.php
namespace Aero\Hrm\Models\Succession;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class CareerPath extends TenantModel
{
    protected $fillable = ['name','slug','description','target_role_id','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function milestones(): HasMany { return $this->hasMany(CareerMilestone::class)->orderBy('order_index'); }
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(\Aero\Hrm\Models\Employee::class, 'career_path_employees')
            ->withPivot('current_milestone_id','assigned_at')->withTimestamps();
    }
    public function getRouteKeyName(): string { return 'slug'; }
}
```

## Task 2 — HRMAC, routes, audit constants

- [ ] Add HRMAC entries to `packages/aero-hrm/config/module.php`:

```php
'succession' => [
    'label' => 'Succession Planning',
    'components' => [
        'career-paths' => ['actions' => ['view','edit']],
        'talent-pool'  => ['actions' => ['view','edit']],
        'candidates'   => ['actions' => ['view','edit']],
        'mobility'     => ['actions' => ['view','edit']],
    ],
],
```

- [ ] Add routes in `packages/aero-hrm/routes/tenant.php`:

```php
Route::middleware(['auth','tenant'])->prefix('hrm/succession')->name('hrm.succession.')->group(function () {
    Route::middleware('hrmac:hrm.career-pathing.career-paths.view')->group(function () {
        Route::get('career-paths',         [CareerPathController::class,'index'])->name('career-paths.index');
        Route::get('career-paths/{path}',  [CareerPathController::class,'show'])->name('career-paths.show');
    });
    Route::middleware('hrmac:hrm.career-pathing.career-paths.update')->group(function () {
        Route::post('career-paths',          [CareerPathController::class,'store'])->name('career-paths.store');
        Route::put('career-paths/{path}',    [CareerPathController::class,'update'])->name('career-paths.update');
    });

    Route::middleware('hrmac:hrm.succession-planning.succession-candidates.view')->get('talent-pool', [TalentPoolController::class,'index'])->name('talent-pool.index');
    Route::middleware('hrmac:hrm.succession-planning.succession-candidates.manage')->group(function () {
        Route::post('talent-pool',                  [TalentPoolController::class,'add'])->name('talent-pool.add');
        Route::delete('talent-pool/{member}',       [TalentPoolController::class,'remove'])->name('talent-pool.remove');
    });

    Route::middleware('hrmac:hrm.succession-planning.succession-candidates.view')->get('candidates', [SuccessionCandidateController::class,'index'])->name('candidates.index');
    Route::middleware('hrmac:hrm.succession-planning.succession-candidates.manage')->post('candidates', [SuccessionCandidateController::class,'store'])->name('candidates.store');

    Route::middleware('hrmac:hrm.workforce-planning.talent-marketplace.view')->get('mobility', [TalentMobilityController::class,'index'])->name('mobility.index');
    Route::middleware('hrmac:hrm.workforce-planning.talent-marketplace.manage')->post('mobility', [TalentMobilityController::class,'store'])->name('mobility.store');
});
```

- [ ] Add audit constants:

```php
public const SUCCESSION_CANDIDATE_ADDED = 'SUCCESSION_CANDIDATE_ADDED';
public const TALENT_POOL_UPDATED        = 'TALENT_POOL_UPDATED';
```

## Task 3 — Services & controllers

- [ ] Implement `MilestoneProgressService`:

```php
// packages/aero-hrm/src/Services/Succession/MilestoneProgressService.php
namespace Aero\Hrm\Services\Succession;

use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\Succession\CareerPath;

class MilestoneProgressService
{
    public function progressFor(CareerPath $path, Employee $employee): array
    {
        return $path->milestones->map(function ($m) use ($employee) {
            $met = collect($m->requirements ?? [])->every(
                fn ($req) => $this->requirementMet($employee, $req)
            );
            return [
                'milestone_id' => $m->id,
                'name'         => $m->name,
                'completed'    => $met,
            ];
        })->all();
    }

    private function requirementMet(Employee $employee, array $req): bool
    {
        return match ($req['type'] ?? null) {
            'training'   => $employee->trainings()->where('training_id', $req['id'])->where('status','completed')->exists(),
            'competency' => $employee->competencies()->where('competency_id', $req['id'])
                                ->where('level','>=', $req['level'] ?? 1)->exists(),
            default => false,
        };
    }
}
```

- [ ] Implement `CareerPathController`:

```php
// packages/aero-hrm/src/Http/Controllers/Succession/CareerPathController.php
namespace Aero\Hrm\Http\Controllers\Succession;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Succession\CareerPath;
use Aero\Hrm\Services\Succession\MilestoneProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CareerPathController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q']);
        $paths = CareerPath::query()
            ->when($filters['q'] ?? null, fn($q,$v) => $q->where('name','like',"%{$v}%"))
            ->withCount('milestones')
            ->latest('id')->paginate(20)->withQueryString();
        return Inertia::render('HRM/Succession/CareerPaths/Index',
            ['paths' => $paths, 'filters' => $filters]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:160'],
            'description' => ['nullable','string'],
            'target_role_id' => ['nullable','exists:hrm_designations,id'],
            'milestones' => ['array'],
            'milestones.*.name' => ['required','string'],
            'milestones.*.requirements' => ['nullable','array'],
        ]);
        $path = CareerPath::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'description' => $data['description'] ?? null,
            'target_role_id' => $data['target_role_id'] ?? null,
        ]);
        foreach ($data['milestones'] ?? [] as $i => $m) {
            $path->milestones()->create($m + ['order_index' => $i]);
        }
        return redirect()->route('hrm.succession.career-paths.show', $path);
    }

    public function show(CareerPath $path, MilestoneProgressService $progress)
    {
        $path->load('milestones','employees');
        $employeeProgress = $path->employees->map(fn ($e) => [
            'employee'  => $e->only(['id','full_name']),
            'progress'  => $progress->progressFor($path, $e),
        ]);
        return Inertia::render('HRM/Succession/CareerPaths/Show', [
            'path' => $path,
            'employee_progress' => $employeeProgress,
        ]);
    }
}
```

- [ ] Implement `SuccessionCandidateController`:

```php
// packages/aero-hrm/src/Http/Controllers/Succession/SuccessionCandidateController.php
namespace Aero\Hrm\Http\Controllers\Succession;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Succession\SuccessionCandidate;
use Aero\Hrm\Support\AuditEvents;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SuccessionCandidateController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request)
    {
        $filters = $request->only(['role_id','readiness']);
        $candidates = SuccessionCandidate::query()->with('role','employee')
            ->when($filters['role_id']   ?? null, fn($q,$v)=>$q->where('role_id',$v))
            ->when($filters['readiness'] ?? null, fn($q,$v)=>$q->where('readiness',$v))
            ->latest()->paginate(20)->withQueryString();
        return Inertia::render('HRM/Succession/Candidates/Index',
            ['candidates' => $candidates, 'filters' => $filters]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role_id'     => ['required','exists:hrm_designations,id'],
            'employee_id' => ['required','exists:hrm_employees,id'],
            'readiness'   => ['required','in:ready_now,1_2_years,3_5_years'],
            'notes'       => ['nullable','string'],
        ]);
        $candidate = SuccessionCandidate::create($data + ['nominated_by' => $request->user()->id]);
        $this->audit->record(AuditEvents::SUCCESSION_CANDIDATE_ADDED, $candidate);
        return back()->with('success','Candidate nominated.');
    }
}
```

## Task 4 — React pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Succession/CareerPaths/Index.jsx`:

```jsx
import App from '../../../App.jsx';
import { Link } from '@inertiajs/react';
import { Table, Button } from '@aero/ui';

export default function CareerPathsIndex({ paths }) {
    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">Career Paths</h1>
                <Link href={route('hrm.succession.career-paths.create')}>
                    <Button color="primary">New Path</Button>
                </Link>
            </div>
            <Table
                columns={['Name','Milestones','Active']}
                rows={paths.data.map(p => [
                    <Link href={route('hrm.succession.career-paths.show', p.slug)}>{p.name}</Link>,
                    p.milestones_count, p.is_active ? 'Yes' : 'No',
                ])}
                pagination={paths}
            />
        </div>
    );
}
CareerPathsIndex.layout = page => <App title="Career Paths">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Succession/CareerPaths/Show.jsx
import App from '../../../App.jsx';
import { Card, Progress, Chip } from '@aero/ui';

export default function CareerPathShow({ path, employee_progress }) {
    return (
        <div className="p-6 space-y-4">
            <h1 className="text-xl font-semibold">{path.name}</h1>
            <p className="text-default-500">{path.description}</p>

            <Card className="p-4">
                <h2 className="font-semibold mb-2">Milestones</h2>
                <ol className="list-decimal pl-6 space-y-1">
                    {path.milestones.map(m => <li key={m.id}>{m.name}</li>)}
                </ol>
            </Card>

            <Card className="p-4">
                <h2 className="font-semibold mb-2">Assigned Employees</h2>
                {employee_progress.map(({ employee, progress }) => {
                    const done = progress.filter(p => p.completed).length;
                    const pct = Math.round((done / progress.length) * 100);
                    return (
                        <div key={employee.id} className="py-2 border-b last:border-0">
                            <div className="flex justify-between">
                                <span>{employee.full_name}</span>
                                <Chip>{done}/{progress.length} milestones</Chip>
                            </div>
                            <Progress value={pct} className="mt-1" />
                        </div>
                    );
                })}
            </Card>
        </div>
    );
}
CareerPathShow.layout = page => <App title="Career Path">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Succession/Candidates/Index.jsx
import App from '../../../App.jsx';
import { useForm } from '@inertiajs/react';
import { Card, Table, Select, Button, Input } from '@aero/ui';

export default function CandidatesIndex({ candidates, filters, roles, employees }) {
    const form = useForm({ role_id: '', employee_id: '', readiness: 'ready_now', notes: '' });
    const submit = (e) => { e.preventDefault();
        form.post(route('hrm.succession.candidates.store'), { onSuccess: () => form.reset() });
    };
    return (
        <div className="p-6 space-y-4">
            <h1 className="text-xl font-semibold">Succession Candidates</h1>
            <Card className="p-4">
                <form onSubmit={submit} className="grid grid-cols-5 gap-3">
                    <Select label="Role" value={form.data.role_id} onChange={e=>form.setData('role_id',e.target.value)}>
                        {roles.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                    </Select>
                    <Select label="Employee" value={form.data.employee_id} onChange={e=>form.setData('employee_id',e.target.value)}>
                        {employees.map(emp => <option key={emp.id} value={emp.id}>{emp.full_name}</option>)}
                    </Select>
                    <Select label="Readiness" value={form.data.readiness} onChange={e=>form.setData('readiness',e.target.value)}>
                        {['ready_now','1_2_years','3_5_years'].map(r => <option key={r}>{r}</option>)}
                    </Select>
                    <Input label="Notes" value={form.data.notes} onChange={e=>form.setData('notes',e.target.value)}/>
                    <Button type="submit" color="primary" isLoading={form.processing}>Nominate</Button>
                </form>
            </Card>
            <Table
                columns={['Role','Employee','Readiness']}
                rows={candidates.data.map(c => [c.role?.name, c.employee?.full_name, c.readiness])}
                pagination={candidates}
            />
        </div>
    );
}
CandidatesIndex.layout = page => <App title="Succession Candidates">{page}</App>;
```

- [ ] Create remaining pages: `CareerPaths/Create.jsx`, `TalentPool/Index.jsx`, `Mobility/Index.jsx`.

## Task 5 — PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Succession/SuccessionTest.php`:

```php
namespace Aero\Hrm\Tests\Feature\Succession;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Hrm\AeroHrmServiceProvider;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\Succession\{CareerPath, SuccessionCandidate};
use Aero\Hrm\Tests\Concerns\ActsAsTenantUser;
use Orchestra\Testbench\TestCase;

class SuccessionTest extends TestCase
{
    use ActsAsTenantUser;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default','testing');
        $app['config']->set('database.connections.testing', [
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ]);
    }

    public function test_can_create_career_path_with_milestones(): void
    {
        $this->actingAsTenantUser(['hrm.career-pathing.career-paths.update']);
        $this->post(route('hrm.succession.career-paths.store'), [
            'name' => 'Senior Engineer',
            'milestones' => [
                ['name' => 'Mentor 1 junior'],
                ['name' => 'Lead a project'],
            ],
        ])->assertRedirect();
        $path = CareerPath::firstWhere('name', 'Senior Engineer');
        $this->assertCount(2, $path->milestones);
    }

    public function test_can_nominate_succession_candidate_and_audit_fires(): void
    {
        $this->actingAsTenantUser(['hrm.succession-planning.succession-candidates.manage']);
        $role = \Aero\Hrm\Models\Designation::factory()->create();
        $emp  = Employee::factory()->create();
        $this->post(route('hrm.succession.candidates.store'), [
            'role_id' => $role->id, 'employee_id' => $emp->id, 'readiness' => 'ready_now',
        ])->assertRedirect();
        $this->assertDatabaseHas('succession_candidates', ['role_id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'SUCCESSION_CANDIDATE_ADDED']);
    }

    public function test_cannot_nominate_same_employee_twice_for_role(): void
    {
        $this->actingAsTenantUser(['hrm.succession-planning.succession-candidates.manage']);
        $role = \Aero\Hrm\Models\Designation::factory()->create();
        $emp  = Employee::factory()->create();
        SuccessionCandidate::factory()->create([
            'role_id' => $role->id, 'employee_id' => $emp->id, 'readiness' => 'ready_now',
        ]);
        $this->post(route('hrm.succession.candidates.store'), [
            'role_id' => $role->id, 'employee_id' => $emp->id, 'readiness' => 'ready_now',
        ])->assertSessionHasErrors();
    }

    public function test_talent_pool_changes_are_audited(): void
    {
        $this->actingAsTenantUser(['hrm.succession-planning.succession-candidates.manage']);
        $emp = Employee::factory()->create();
        $this->post(route('hrm.succession.talent-pool.add'), ['employee_id' => $emp->id])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['event' => 'TALENT_POOL_UPDATED']);
    }
}
```

- [ ] Run `vendor/bin/phpunit --filter=Succession` and confirm 4 tests pass.
