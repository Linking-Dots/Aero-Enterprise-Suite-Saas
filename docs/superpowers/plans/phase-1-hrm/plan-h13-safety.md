# Plan H-13 — Workplace Safety Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an end-to-end Workplace Safety module covering incident reporting and investigation, scheduled inspections with checklist findings, safety training assignments, and a KPI dashboard (LTIFR, incident trend, open findings).

**Architecture:** All domain logic lives in `packages/aero-hrm/` under `src/Http/Controllers/Safety/`, `src/Models/Safety/`, and `src/Services/Safety/`. Inertia pages live in `packages/aero-ui/resources/js/Pages/HRM/Safety/`. Every route is HRMAC-guarded, every state mutation is recorded via `AuditServiceInterface`, and queries return paginated, flat Inertia props. Dashboard KPIs are computed in a dedicated read-model service so list controllers stay thin.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Database, models, and migrations

- [ ] Create migrations under `packages/aero-hrm/database/migrations/`:
  - `2026_05_17_000001_create_safety_incidents_table.php`
  - `2026_05_17_000002_create_safety_incident_investigations_table.php`
  - `2026_05_17_000003_create_safety_inspections_table.php`
  - `2026_05_17_000004_create_safety_inspection_findings_table.php`
  - `2026_05_17_000005_create_safety_trainings_table.php`
  - `2026_05_17_000006_create_safety_training_assignments_table.php`

```php
// 2026_05_17_000001_create_safety_incidents_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->dateTime('occurred_at');
            $table->string('type', 64); // injury, near_miss, property_damage
            $table->string('severity', 32); // low, medium, high, critical
            $table->foreignId('department_id')->nullable()->constrained('hrm_departments')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('description');
            $table->json('injured_person_ids')->nullable();
            $table->string('status', 24)->default('reported'); // reported, investigating, closed
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'severity']);
        });
    }
    public function down(): void { Schema::dropIfExists('safety_incidents'); }
};
```

- [ ] Create models in `packages/aero-hrm/src/Models/Safety/`:

```php
// packages/aero-hrm/src/Models/Safety/SafetyIncident.php
namespace Aero\Hrm\Models\Safety;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyIncident extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'reference','occurred_at','type','severity','department_id','location',
        'description','injured_person_ids','status','reported_by','closed_by','closed_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'closed_at'   => 'datetime',
        'injured_person_ids' => 'array',
    ];

    public function investigations(): HasMany
    {
        return $this->hasMany(SafetyIncidentInvestigation::class, 'incident_id');
    }
}
```

## Task 2 — Routes, HRMAC config, and audit constants

- [ ] Add routes in `packages/aero-hrm/routes/tenant.php`:

```php
Route::middleware(['auth','tenant'])->prefix('hrm/safety')->name('hrm.safety.')->group(function () {
    Route::get('dashboard', [SafetyDashboardController::class, 'index'])
        ->middleware('hrmac:hrm.safety.safety-incidents.view')->name('dashboard');

    Route::middleware('hrmac:hrm.safety.safety-incidents.view')->group(function () {
        Route::get('incidents',                 [SafetyIncidentController::class, 'index'])->name('incidents.index');
        Route::get('incidents/{incident}',      [SafetyIncidentController::class, 'show'])->name('incidents.show');
    });
    Route::middleware('hrmac:hrm.safety.safety-incidents.update')->group(function () {
        Route::get('incidents/create',          [SafetyIncidentController::class, 'create'])->name('incidents.create');
        Route::post('incidents',                [SafetyIncidentController::class, 'store'])->name('incidents.store');
    });
    Route::middleware('hrmac:hrm.safety.safety-incidents.resolve')->group(function () {
        Route::post('incidents/{incident}/investigate', [SafetyIncidentController::class, 'investigate'])->name('incidents.investigate');
        Route::post('incidents/{incident}/close',       [SafetyIncidentController::class, 'close'])->name('incidents.close');
    });

    Route::middleware('hrmac:hrm.safety.safety-inspections.view')->group(function () {
        Route::get('inspections',               [SafetyInspectionController::class, 'index'])->name('inspections.index');
        Route::get('inspections/{inspection}',  [SafetyInspectionController::class, 'show'])->name('inspections.show');
    });
    Route::middleware('hrmac:hrm.safety.safety-inspections.update')->group(function () {
        Route::post('inspections',                              [SafetyInspectionController::class, 'store'])->name('inspections.store');
        Route::post('inspections/{inspection}/findings',        [SafetyInspectionController::class, 'submitFindings'])->name('inspections.findings');
    });

    Route::middleware('hrmac:hrm.safety.safety-training.view')->get('training', [SafetyTrainingController::class, 'index'])->name('training.index');
    Route::middleware('hrmac:hrm.safety.safety-training.update')->group(function () {
        Route::post('training',                       [SafetyTrainingController::class, 'store'])->name('training.store');
        Route::post('training/{assignment}/complete', [SafetyTrainingController::class, 'complete'])->name('training.complete');
    });
});
```

- [ ] Add HRMAC entries in `packages/aero-hrm/config/module.php`:

```php
'safety' => [
    'label' => 'Workplace Safety',
    'components' => [
        'incidents'   => ['actions' => ['view','edit','investigate']],
        'inspections' => ['actions' => ['view','edit']],
        'training'    => ['actions' => ['view','edit']],
        'dashboard'   => ['actions' => ['view']],
    ],
],
```

- [ ] Add audit constants to `packages/aero-hrm/src/Support/AuditEvents.php`:

```php
public const SAFETY_INCIDENT_REPORTED = 'SAFETY_INCIDENT_REPORTED';
public const SAFETY_INCIDENT_CLOSED   = 'SAFETY_INCIDENT_CLOSED';
public const INSPECTION_CONDUCTED     = 'INSPECTION_CONDUCTED';
```

## Task 3 — Controllers and services

- [ ] Implement `SafetyIncidentController`:

```php
// packages/aero-hrm/src/Http/Controllers/Safety/SafetyIncidentController.php
namespace Aero\Hrm\Http\Controllers\Safety;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Http\Requests\Safety\StoreIncidentRequest;
use Aero\Hrm\Models\Safety\SafetyIncident;
use Aero\Hrm\Support\AuditEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SafetyIncidentController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request)
    {
        $filters = $request->only(['severity','department_id','status','q']);
        $incidents = SafetyIncident::query()
            ->when($filters['severity']      ?? null, fn($q,$v) => $q->where('severity',$v))
            ->when($filters['department_id'] ?? null, fn($q,$v) => $q->where('department_id',$v))
            ->when($filters['status']        ?? null, fn($q,$v) => $q->where('status',$v))
            ->when($filters['q']             ?? null, fn($q,$v) => $q->where('description','like',"%{$v}%"))
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Safety/Incidents/Index', [
            'incidents' => $incidents,
            'filters'   => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('HRM/Safety/Incidents/Create');
    }

    public function store(StoreIncidentRequest $request)
    {
        $incident = SafetyIncident::create($request->validated() + [
            'reference'   => 'INC-'.strtoupper(Str::random(8)),
            'status'      => 'reported',
            'reported_by' => $request->user()->id,
        ]);

        $this->audit->record(AuditEvents::SAFETY_INCIDENT_REPORTED, $incident);

        return redirect()->route('hrm.safety.incidents.show', $incident)
            ->with('success', 'Incident reported.');
    }

    public function show(SafetyIncident $incident)
    {
        $incident->load('investigations');
        return Inertia::render('HRM/Safety/Incidents/Show', ['incident' => $incident]);
    }

    public function investigate(Request $request, SafetyIncident $incident)
    {
        $data = $request->validate([
            'root_cause' => ['required','string','max:2000'],
            'corrective_action' => ['required','string','max:2000'],
        ]);

        $incident->investigations()->create($data + ['investigator_id' => $request->user()->id]);
        $incident->update(['status' => 'investigating']);

        return back()->with('success', 'Investigation logged.');
    }

    public function close(Request $request, SafetyIncident $incident)
    {
        $incident->update([
            'status'    => 'closed',
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);
        $this->audit->record(AuditEvents::SAFETY_INCIDENT_CLOSED, $incident);
        return back()->with('success', 'Incident closed.');
    }
}
```

- [ ] Implement `SafetyInspectionController`, `SafetyTrainingController`, and `SafetyDashboardController` (use `SafetyKpiService` to compute LTIFR, trend, open findings).

```php
// packages/aero-hrm/src/Services/Safety/SafetyKpiService.php
namespace Aero\Hrm\Services\Safety;

use Aero\Hrm\Models\Safety\SafetyIncident;
use Aero\Hrm\Models\Safety\SafetyInspectionFinding;

class SafetyKpiService
{
    public function dashboard(): array
    {
        $hoursWorked  = 200_000; // pull from attendance summary
        $lostTime     = SafetyIncident::where('type','injury')->whereYear('occurred_at', now()->year)->count();
        $ltifr        = $hoursWorked > 0 ? round(($lostTime * 1_000_000) / $hoursWorked, 2) : 0;

        return [
            'ltifr'         => $ltifr,
            'open_findings' => SafetyInspectionFinding::where('status','open')->count(),
            'incident_trend'=> SafetyIncident::selectRaw('DATE_FORMAT(occurred_at,"%Y-%m") as month, COUNT(*) as total')
                                ->groupBy('month')->orderBy('month')->limit(12)->get(),
        ];
    }
}
```

## Task 4 — React pages

- [ ] Create pages in `packages/aero-ui/resources/js/Pages/HRM/Safety/`:

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Safety/Incidents/Index.jsx
import App from '../../../App.jsx';
import { router, Link } from '@inertiajs/react';
import { Table, Button, Chip, Input, Select } from '@aero/ui';

export default function IncidentsIndex({ incidents, filters }) {
    const update = (patch) => router.get(route('hrm.safety.incidents.index'),
        { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">Safety Incidents</h1>
                <Link href={route('hrm.safety.incidents.create')}>
                    <Button color="primary">Report Incident</Button>
                </Link>
            </div>
            <div className="grid grid-cols-3 gap-3">
                <Select label="Severity" value={filters.severity ?? ''}
                    onChange={(e) => update({ severity: e.target.value })}>
                    {['low','medium','high','critical'].map(s => <option key={s} value={s}>{s}</option>)}
                </Select>
                <Select label="Status" value={filters.status ?? ''}
                    onChange={(e) => update({ status: e.target.value })}>
                    {['reported','investigating','closed'].map(s => <option key={s} value={s}>{s}</option>)}
                </Select>
                <Input label="Search" value={filters.q ?? ''} onChange={(e)=>update({q: e.target.value})} />
            </div>
            <Table
                columns={['Ref','Occurred','Type','Severity','Status']}
                rows={incidents.data.map(i => [
                    <Link href={route('hrm.safety.incidents.show', i.id)}>{i.reference}</Link>,
                    i.occurred_at, i.type,
                    <Chip color={i.severity === 'critical' ? 'danger' : 'default'}>{i.severity}</Chip>,
                    i.status,
                ])}
                pagination={incidents}
            />
        </div>
    );
}
IncidentsIndex.layout = page => <App title="Safety Incidents">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Safety/Dashboard.jsx
import App from '../../App.jsx';
import { Card, Stat, LineChart } from '@aero/ui';

export default function SafetyDashboard({ ltifr, open_findings, incident_trend }) {
    return (
        <div className="p-6 grid grid-cols-3 gap-4">
            <Card><Stat label="LTIFR" value={ltifr} /></Card>
            <Card><Stat label="Open Findings" value={open_findings} /></Card>
            <Card className="col-span-3">
                <LineChart data={incident_trend} xKey="month" yKey="total" />
            </Card>
        </div>
    );
}
SafetyDashboard.layout = page => <App title="Safety Dashboard">{page}</App>;
```

- [ ] Create `Incidents/Create.jsx`, `Incidents/Show.jsx`, `Inspections/Index.jsx`, `Inspections/Create.jsx`, `Inspections/Show.jsx`, `Training/Index.jsx` following the same layout pattern.

## Task 5 — PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Safety/SafetyIncidentTest.php`:

```php
namespace Aero\Hrm\Tests\Feature\Safety;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Hrm\AeroHrmServiceProvider;
use Aero\Hrm\Models\Safety\SafetyIncident;
use Aero\Hrm\Tests\Concerns\ActsAsTenantUser;
use Orchestra\Testbench\TestCase;

class SafetyIncidentTest extends TestCase
{
    use ActsAsTenantUser;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    public function test_admin_can_list_incidents(): void
    {
        $this->actingAsTenantUser(['hrm.safety.safety-incidents.view']);
        SafetyIncident::factory()->count(3)->create();
        $this->get(route('hrm.safety.incidents.index'))
            ->assertOk()->assertInertia(fn ($p) => $p->component('HRM/Safety/Incidents/Index'));
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $this->actingAsTenantUser([]);
        $this->get(route('hrm.safety.incidents.index'))->assertForbidden();
    }

    public function test_can_report_incident_and_audit_fires(): void
    {
        $this->actingAsTenantUser(['hrm.safety.safety-incidents.update']);
        $payload = [
            'occurred_at' => now()->toDateTimeString(),
            'type' => 'injury', 'severity' => 'high',
            'description' => 'Slip and fall',
        ];
        $this->post(route('hrm.safety.incidents.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('safety_incidents', ['type' => 'injury', 'severity' => 'high']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'SAFETY_INCIDENT_REPORTED']);
    }

    public function test_can_close_incident(): void
    {
        $this->actingAsTenantUser(['hrm.safety.safety-incidents.resolve']);
        $incident = SafetyIncident::factory()->create(['status' => 'investigating']);
        $this->post(route('hrm.safety.incidents.close', $incident))->assertRedirect();
        $this->assertSame('closed', $incident->fresh()->status);
    }

    public function test_index_filters_by_severity(): void
    {
        $this->actingAsTenantUser(['hrm.safety.safety-incidents.view']);
        SafetyIncident::factory()->create(['severity' => 'critical']);
        SafetyIncident::factory()->create(['severity' => 'low']);
        $this->get(route('hrm.safety.incidents.index', ['severity' => 'critical']))
            ->assertInertia(fn ($p) => $p->where('incidents.data.0.severity', 'critical'));
    }
}
```

- [ ] Run `vendor/bin/phpunit --filter=Safety` and ensure all 5 tests pass.
