# Plan H-5 — Payroll Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade payroll engine: salary structures, pay components (earnings & deductions), payroll runs with a strict *draft → review → approved → locked* lifecycle, payslip viewing/printing (with encrypted bank PII), and tax bracket settings — fully gated by HRMAC and audited.

**Architecture:** Layered services in `packages/aero-hrm/src/Services/Payroll/`: `PayrollRunGenerator` (creates draft run + payslips), `PayrollCalculator` (applies pay components + tax brackets), `PayrollApprovalService` (transitions run → approved + locks). The immutability invariant is enforced at the model level (`booted()` guard rejecting `updating`/`deleting` once `locked_at` is non-null) **and** re-checked at the controller (`abort(403)` on any mutation of an approved run). Sensitive employee snapshot fields (`bank_account_number`, `bank_name`, `bank_routing_number`) use `EncryptedField` casts on the `Payslip` model.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

**Prerequisite:** Plans A–S + H-4 merged. Working directory: `c:\laragon\www\Aero-Enterprise-Suite-Saas`.

---

## File Map

| Action | Path |
|--------|------|
| Migration | `packages/aero-hrm/database/migrations/2026_05_17_000010_create_payroll_v2_tables.php` |
| Model | `packages/aero-hrm/src/Models/SalaryStructure.php` |
| Model | `packages/aero-hrm/src/Models/PayComponent.php` |
| Model | `packages/aero-hrm/src/Models/PayrollRun.php` |
| Model | `packages/aero-hrm/src/Models/Payslip.php` |
| Model | `packages/aero-hrm/src/Models/TaxBracket.php` |
| Service | `packages/aero-hrm/src/Services/Payroll/PayrollRunGenerator.php` |
| Service | `packages/aero-hrm/src/Services/Payroll/PayrollCalculator.php` |
| Service | `packages/aero-hrm/src/Services/Payroll/PayrollApprovalService.php` |
| Service | `packages/aero-hrm/src/Services/Payroll/PayslipPdfRenderer.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Payroll/SalaryStructureController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Payroll/PayComponentController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Payroll/PayrollRunController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Payroll/PayslipController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Payroll/TaxSettingController.php` |
| Routes | `packages/aero-hrm/routes/tenant.php` (extend) |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Structures/Index.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Structures/Create.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Components/Index.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Index.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Create.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Show.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Payslips/Show.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Payroll/Settings/Tax.jsx` |
| Tests | `packages/aero-hrm/tests/Feature/Payroll/PayrollRunGeneratorTest.php` |
| Tests | `packages/aero-hrm/tests/Feature/Payroll/PayrollImmutabilityTest.php` |
| Tests | `packages/aero-hrm/tests/Feature/Payroll/PayslipAccessTest.php` |

---

## Task H5-1: Schema — Payroll v2 tables with immutability column

- [ ] Create `packages/aero-hrm/database/migrations/2026_05_17_000010_create_payroll_v2_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_pay_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->enum('kind', ['earning', 'deduction']);
            $table->enum('calc_type', ['fixed', 'percent_of_basic', 'percent_of_gross', 'formula']);
            $table->decimal('value', 12, 4)->default(0);
            $table->boolean('taxable')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('basic', 12, 2);
            $table->json('component_ids')->nullable();   // ordered list of pay_component_id
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 5)->default('US');
            $table->decimal('income_from', 14, 2);
            $table->decimal('income_to',   14, 2)->nullable();
            $table->decimal('rate', 5, 4); // 0.20 = 20%
            $table->integer('effective_year');
            $table->timestamps();
            $table->index(['country_code', 'effective_year']);
        });

        Schema::create('hrm_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'review', 'approved'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable(); // <-- immutability flag
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_net',   14, 2)->default(0);
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('hrm_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('hrm_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->decimal('gross', 14, 2);
            $table->decimal('tax',   14, 2);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('net',   14, 2);
            $table->json('line_items'); // [{code,name,kind,amount}]
            $table->json('employee_snapshot'); // {first_name,last_name,designation,department}
            // Encrypted bank PII
            $table->text('bank_account_number')->nullable();
            $table->text('bank_name')->nullable();
            $table->text('bank_routing_number')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_payslips');
        Schema::dropIfExists('hrm_payroll_runs');
        Schema::dropIfExists('hrm_tax_brackets');
        Schema::dropIfExists('hrm_salary_structures');
        Schema::dropIfExists('hrm_pay_components');
    }
};
```

- [ ] `php artisan migrate`.

---

## Task H5-2: Models with immutability guard + EncryptedField

- [ ] Create `packages/aero-hrm/src/Models/PayComponent.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;

class PayComponent extends TenantModel
{
    protected $table = 'hrm_pay_components';
    protected $fillable = ['code', 'name', 'kind', 'calc_type', 'value', 'taxable', 'active'];
    protected $casts = [
        'value'   => 'decimal:4',
        'taxable' => 'bool',
        'active'  => 'bool',
    ];
}
```

- [ ] Create `packages/aero-hrm/src/Models/SalaryStructure.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;

class SalaryStructure extends TenantModel
{
    protected $table = 'hrm_salary_structures';
    protected $fillable = ['name', 'basic', 'component_ids', 'active'];
    protected $casts = [
        'basic'         => 'decimal:2',
        'component_ids' => 'array',
        'active'        => 'bool',
    ];

    public function components()
    {
        return PayComponent::whereIn('id', $this->component_ids ?? [])->orderByRaw(
            'FIELD(id,' . implode(',', $this->component_ids ?: [0]) . ')'
        );
    }
}
```

- [ ] Create `packages/aero-hrm/src/Models/TaxBracket.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;

class TaxBracket extends TenantModel
{
    protected $table = 'hrm_tax_brackets';
    protected $fillable = ['country_code', 'income_from', 'income_to', 'rate', 'effective_year'];
    protected $casts = [
        'income_from'    => 'decimal:2',
        'income_to'      => 'decimal:2',
        'rate'           => 'decimal:4',
        'effective_year' => 'int',
    ];
}
```

- [ ] Create `packages/aero-hrm/src/Models/PayrollRun.php` **with immutability guard**:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Aero\Hrm\Exceptions\PayrollLockedException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends TenantModel
{
    protected $table = 'hrm_payroll_runs';

    protected $fillable = [
        'label', 'period_start', 'period_end', 'status',
        'approved_at', 'approved_by', 'locked_at',
        'total_gross', 'total_net',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'approved_at'  => 'datetime',
        'locked_at'    => 'datetime',
        'total_gross'  => 'decimal:2',
        'total_net'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $run) {
            if ($run->getOriginal('locked_at') !== null) {
                throw new PayrollLockedException("Payroll run #{$run->id} is locked.");
            }
        });
        static::deleting(function (self $run) {
            if ($run->locked_at !== null) {
                throw new PayrollLockedException("Cannot delete locked payroll run #{$run->id}.");
            }
        });
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
```

- [ ] Create `packages/aero-hrm/src/Models/Payslip.php` **with EncryptedField casts + immutability guard**:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Casts\EncryptedField;
use Aero\Core\Models\TenantModel;
use Aero\Hrm\Exceptions\PayrollLockedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends TenantModel
{
    protected $table = 'hrm_payslips';

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'gross', 'tax', 'deductions_total', 'net',
        'line_items', 'employee_snapshot',
        'bank_account_number', 'bank_name', 'bank_routing_number',
    ];

    protected $casts = [
        'gross'              => 'decimal:2',
        'tax'                => 'decimal:2',
        'deductions_total'   => 'decimal:2',
        'net'                => 'decimal:2',
        'line_items'         => 'array',
        'employee_snapshot'  => 'array',
        'bank_account_number'=> EncryptedField::class,
        'bank_name'          => EncryptedField::class,
        'bank_routing_number'=> EncryptedField::class,
    ];

    protected static function booted(): void
    {
        $reject = function (self $slip) {
            if ($slip->run?->isLocked()) {
                throw new PayrollLockedException("Payslip #{$slip->id} belongs to a locked run.");
            }
        };
        static::updating($reject);
        static::deleting($reject);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
```

- [ ] Create exception `packages/aero-hrm/src/Exceptions/PayrollLockedException.php` extending `\DomainException`.

---

## Task H5-3: PayrollCalculator + PayrollRunGenerator + PayrollApprovalService

- [ ] Create `packages/aero-hrm/src/Services/Payroll/PayrollCalculator.php`:

```php
<?php

namespace Aero\Hrm\Services\Payroll;

use Aero\Hrm\Models\PayComponent;
use Aero\Hrm\Models\SalaryStructure;
use Aero\Hrm\Models\TaxBracket;

class PayrollCalculator
{
    public function compute(SalaryStructure $struct, int $year): array
    {
        $basic = (float) $struct->basic;
        $lines = [['code' => 'BASIC', 'name' => 'Basic', 'kind' => 'earning', 'amount' => $basic]];
        $earnings = $basic;
        $deductions = 0.0;

        foreach (PayComponent::whereIn('id', $struct->component_ids ?? [])->get() as $c) {
            $amount = match ($c->calc_type) {
                'fixed'             => (float) $c->value,
                'percent_of_basic'  => $basic * ((float) $c->value),
                'percent_of_gross'  => $earnings * ((float) $c->value),
                default             => 0.0,
            };
            $lines[] = ['code' => $c->code, 'name' => $c->name, 'kind' => $c->kind, 'amount' => round($amount, 2)];
            $c->kind === 'earning' ? $earnings += $amount : $deductions += $amount;
        }

        $tax = $this->tax($earnings, $year);
        $net = $earnings - $deductions - $tax;

        return [
            'gross'      => round($earnings, 2),
            'deductions' => round($deductions, 2),
            'tax'        => round($tax, 2),
            'net'        => round($net, 2),
            'lines'      => $lines,
        ];
    }

    private function tax(float $income, int $year): float
    {
        $brackets = TaxBracket::where('effective_year', $year)->orderBy('income_from')->get();
        $tax = 0.0;
        foreach ($brackets as $b) {
            $from = (float) $b->income_from;
            $to   = $b->income_to !== null ? (float) $b->income_to : INF;
            if ($income <= $from) break;
            $slab = min($income, $to) - $from;
            $tax += $slab * (float) $b->rate;
        }
        return $tax;
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Payroll/PayrollRunGenerator.php`:

```php
<?php

namespace Aero\Hrm\Services\Payroll;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\Payslip;
use Aero\Hrm\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class PayrollRunGenerator
{
    public function __construct(
        private readonly PayrollCalculator $calc,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function create(array $payload, array $employeeIds): PayrollRun
    {
        return DB::transaction(function () use ($payload, $employeeIds) {
            $run = PayrollRun::create([
                'label'        => $payload['label'],
                'period_start' => $payload['period_start'],
                'period_end'   => $payload['period_end'],
                'status'       => 'draft',
            ]);

            $year = (int) date('Y', strtotime($payload['period_end']));
            $totalGross = 0; $totalNet = 0;

            foreach (Employee::whereIn('id', $employeeIds)->with('salaryStructure', 'bankDetail')->get() as $emp) {
                if (! $emp->salaryStructure) continue;
                $r = $this->calc->compute($emp->salaryStructure, $year);

                Payslip::create([
                    'payroll_run_id'      => $run->id,
                    'employee_id'         => $emp->id,
                    'gross'               => $r['gross'],
                    'tax'                 => $r['tax'],
                    'deductions_total'    => $r['deductions'],
                    'net'                 => $r['net'],
                    'line_items'          => $r['lines'],
                    'employee_snapshot'   => [
                        'first_name'   => $emp->first_name,
                        'last_name'    => $emp->last_name,
                        'designation'  => $emp->designation?->name,
                        'department'   => $emp->department?->name,
                    ],
                    'bank_account_number' => $emp->bankDetail?->account_number,
                    'bank_name'           => $emp->bankDetail?->bank_name,
                    'bank_routing_number' => $emp->bankDetail?->routing_number,
                ]);

                $totalGross += $r['gross'];
                $totalNet   += $r['net'];
            }

            $run->update(['total_gross' => $totalGross, 'total_net' => $totalNet]);

            $this->audit->record('PAYROLL_RUN_CREATED', 'hrm', 'payroll', [
                'payroll_run_id'   => $run->id,
                'employee_count'   => count($employeeIds),
                'total_gross'      => $totalGross,
            ]);

            return $run->fresh('payslips');
        });
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Payroll/PayrollApprovalService.php`:

```php
<?php

namespace Aero\Hrm\Services\Payroll;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\PayrollRun;
use Aero\Hrm\Exceptions\PayrollLockedException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollApprovalService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function approve(PayrollRun $run): PayrollRun
    {
        if ($run->isLocked()) {
            throw new PayrollLockedException("Run already locked.");
        }
        if ($run->status === 'approved') {
            throw new PayrollLockedException("Run already approved.");
        }

        DB::transaction(function () use ($run) {
            $run->status      = 'approved';
            $run->approved_by = Auth::id();
            $run->approved_at = Carbon::now();
            // locked_at must be set LAST and via raw update to bypass guard during this single transition
            $run->save();
            // Lock the run by direct query — `updating` event already passed because original locked_at was null.
            DB::table('hrm_payroll_runs')->where('id', $run->id)->update(['locked_at' => Carbon::now()]);
        });

        $this->audit->record('PAYROLL_RUN_APPROVED', 'hrm', 'payroll', [
            'payroll_run_id' => $run->id,
            'total_net'      => (float) $run->total_net,
        ]);

        return $run->fresh();
    }
}
```

---

## Task H5-4: Controllers (with immutability re-check)

- [ ] Create `packages/aero-hrm/src/Http/Controllers/Payroll/PayrollRunController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Payroll;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\PayrollRun;
use Aero\Hrm\Services\Payroll\PayrollApprovalService;
use Aero\Hrm\Services\Payroll\PayrollRunGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.view');

        $runs = PayrollRun::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('period_end')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Payroll/Runs/Index', [
            'runs'    => $runs,
            'filters' => ['status' => $request->input('status')],
        ]);
    }

    public function create()
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.edit');
        return Inertia::render('HRM/Payroll/Runs/Create', [
            'employees' => Employee::select('id', 'first_name', 'last_name')->where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request, PayrollRunGenerator $gen)
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.edit');

        $data = $request->validate([
            'label'        => 'required|string|max:100',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:hrm_employees,id',
        ]);

        $run = $gen->create($data, $data['employee_ids']);

        return redirect()->route('hrm.payroll.runs.show', $run->id);
    }

    public function show(PayrollRun $run)
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.view');

        $run->load('payslips.employee:id,first_name,last_name');

        return Inertia::render('HRM/Payroll/Runs/Show', ['run' => $run]);
    }

    public function update(Request $request, PayrollRun $run)
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.edit');

        // Defence-in-depth: re-check at controller layer
        if ($run->isLocked()) {
            abort(403, 'Payroll run is locked and cannot be modified.');
        }

        $run->update($request->validate(['label' => 'sometimes|string|max:100']));
        return back()->with('success', 'Run updated.');
    }

    public function approve(PayrollRun $run, PayrollApprovalService $svc)
    {
        Gate::authorize('hrmac', 'hrm.payroll.runs.approve');
        $svc->approve($run);
        return back()->with('success', 'Payroll run approved and locked.');
    }
}
```

- [ ] Create `packages/aero-hrm/src/Http/Controllers/Payroll/PayslipController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Payroll;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Payslip;
use Aero\Hrm\Services\Payroll\PayslipPdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PayslipController extends Controller
{
    public function show(Request $request, Payslip $payslip, AuditServiceInterface $audit)
    {
        $isSelf = $request->user()->employee?->id === $payslip->employee_id;
        Gate::authorize('hrmac', $isSelf ? 'hrm.payroll.my-payslips.view' : 'hrm.payroll.payslips.view');

        $audit->record('PAYROLL_PAYSLIP_VIEWED', 'hrm', 'payroll', [
            'payslip_id'  => $payslip->id,
            'viewer_id'   => $request->user()->id,
            'self_view'   => $isSelf,
        ]);

        return Inertia::render('HRM/Payroll/Payslips/Show', ['payslip' => $payslip->load('run')]);
    }

    public function download(Request $request, Payslip $payslip, PayslipPdfRenderer $pdf, AuditServiceInterface $audit)
    {
        $isSelf = $request->user()->employee?->id === $payslip->employee_id;
        Gate::authorize('hrmac', $isSelf ? 'hrm.payroll.my-payslips.view' : 'hrm.payroll.payslips.view');

        $audit->record('PAYROLL_PAYSLIP_VIEWED', 'hrm', 'payroll', [
            'payslip_id' => $payslip->id, 'download' => true,
        ]);

        return $pdf->stream($payslip);
    }
}
```

- [ ] Create the remaining controllers (`SalaryStructureController`, `PayComponentController`, `TaxSettingController`) following the same pattern: HRMAC gate first, validate, persist, return Inertia / redirect.

- [ ] Create `packages/aero-hrm/src/Services/Payroll/PayslipPdfRenderer.php` that wraps `barryvdh/laravel-dompdf` to stream `view('aero-hrm::payslips.pdf', ['p' => $payslip])`.

---

## Task H5-5: Routes + module.php

- [ ] Append to `packages/aero-hrm/routes/tenant.php`:

```php
Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::resource('components', PayComponentController::class)->middleware('hrmac:hrm.payroll.structures.edit');
    Route::resource('structures', SalaryStructureController::class)->middleware('hrmac:hrm.payroll.structures.edit');

    Route::get('runs',           [PayrollRunController::class, 'index'])  ->middleware('hrmac:hrm.payroll.runs.view')  ->name('runs.index');
    Route::get('runs/create',    [PayrollRunController::class, 'create']) ->middleware('hrmac:hrm.payroll.runs.edit')  ->name('runs.create');
    Route::post('runs',          [PayrollRunController::class, 'store'])  ->middleware('hrmac:hrm.payroll.runs.edit')  ->name('runs.store');
    Route::get('runs/{run}',     [PayrollRunController::class, 'show'])   ->middleware('hrmac:hrm.payroll.runs.view')  ->name('runs.show');
    Route::put('runs/{run}',     [PayrollRunController::class, 'update']) ->middleware('hrmac:hrm.payroll.runs.edit')  ->name('runs.update');
    Route::post('runs/{run}/approve', [PayrollRunController::class, 'approve'])->middleware('hrmac:hrm.payroll.runs.approve')->name('runs.approve');

    Route::get('payslips/{payslip}',           [PayslipController::class, 'show'])    ->name('payslips.show');
    Route::get('payslips/{payslip}/download',  [PayslipController::class, 'download'])->name('payslips.download');

    Route::get('settings/tax',  [TaxSettingController::class, 'index']) ->middleware('hrmac:hrm.payroll.structures.edit')->name('settings.tax');
    Route::post('settings/tax', [TaxSettingController::class, 'store']) ->middleware('hrmac:hrm.payroll.structures.edit')->name('settings.tax.store');
});
```

- [ ] Update `packages/aero-hrm/config/module.php`:

```php
'payroll' => [
    'label' => 'Payroll',
    'components' => [
        'structures'    => ['actions' => ['view', 'edit']],
        'runs'          => ['actions' => ['view', 'edit', 'approve']],
        'payslips'      => ['actions' => ['view']],   // admin
        'my-payslips'   => ['actions' => ['view']],   // self
    ],
],
```

- [ ] `php artisan hrmac:sync`.

---

## Task H5-6: Frontend pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Index.jsx`:

```jsx
import { Link, router } from '@inertiajs/react';
import { IndexPageLayout, DataTable, Button, Badge, Select, Money } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function RunsIndex({ runs, filters }) {
  const canCreate = useHRMAC('hrm.payroll.runs.edit');
  const setStatus = (v) => router.get(route('hrm.payroll.runs.index'), { status: v });

  const columns = [
    { header: 'Label',  accessor: 'label' },
    { header: 'Period', accessor: r => `${r.period_start} → ${r.period_end}` },
    { header: 'Status', accessor: r => <Badge variant={r.locked_at ? 'success' : 'neutral'}>{r.status}{r.locked_at ? ' (locked)' : ''}</Badge> },
    { header: 'Gross',  accessor: r => <Money value={r.total_gross} /> },
    { header: 'Net',    accessor: r => <Money value={r.total_net} /> },
    { header: '',       accessor: r => <Link href={route('hrm.payroll.runs.show', r.id)}><Button size="xs">View</Button></Link> },
  ];

  return (
    <App>
      <IndexPageLayout title="Payroll Runs"
        actions={canCreate && <Link href={route('hrm.payroll.runs.create')}><Button>New Run</Button></Link>}>
        <Select value={filters.status ?? ''} onChange={(e) => setStatus(e.target.value)}>
          <option value="">All</option>
          <option value="draft">Draft</option>
          <option value="review">Review</option>
          <option value="approved">Approved</option>
        </Select>
        <DataTable columns={columns} data={runs.data} pagination={runs} />
      </IndexPageLayout>
    </App>
  );
}

RunsIndex.layout = page => <App title="Payroll Runs">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Show.jsx`:

```jsx
import { router } from '@inertiajs/react';
import { DetailPageLayout, DataTable, Button, Badge, Card, Money, HStack } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function RunShow({ run }) {
  const canApprove = useHRMAC('hrm.payroll.runs.approve');
  const locked = !!run.locked_at;

  const approve = () => {
    if (!window.confirm('Approve and LOCK this payroll run? This cannot be undone.')) return;
    router.post(route('hrm.payroll.runs.approve', run.id));
  };

  const columns = [
    { header: 'Employee', accessor: p => `${p.employee.first_name} ${p.employee.last_name}` },
    { header: 'Gross',    accessor: p => <Money value={p.gross} /> },
    { header: 'Tax',      accessor: p => <Money value={p.tax} /> },
    { header: 'Net',      accessor: p => <Money value={p.net} /> },
    { header: '',         accessor: p => <a href={route('hrm.payroll.payslips.show', p.id)} className="text-sky-600">View payslip</a> },
  ];

  return (
    <App>
      <DetailPageLayout title={run.label}
        meta={<HStack gap={3}>
          <Badge variant={locked ? 'success' : 'neutral'}>{run.status}{locked && ' • LOCKED'}</Badge>
          <span>{run.period_start} → {run.period_end}</span>
        </HStack>}
        actions={canApprove && !locked && <Button onClick={approve}>Approve &amp; Lock</Button>}
      >
        <Card>
          <HStack gap={6}>
            <div>Gross: <Money value={run.total_gross} /></div>
            <div>Net:   <Money value={run.total_net} /></div>
          </HStack>
        </Card>
        <DataTable columns={columns} data={run.payslips} />
      </DetailPageLayout>
    </App>
  );
}

RunShow.layout = page => <App title="Payroll Run">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Runs/Create.jsx` — form with `label`, `period_start`, `period_end`, multi-select `employee_ids`; submits to `hrm.payroll.runs.store`.

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Payslips/Show.jsx`:

```jsx
import { DetailPageLayout, Card, Money, VStack, HStack, Text, Button } from '@aero/ui';
import App from '../../../../App.jsx';

export default function PayslipShow({ payslip }) {
  return (
    <App>
      <DetailPageLayout title={`Payslip #${payslip.id}`}
        actions={<Button as="a" href={route('hrm.payroll.payslips.download', payslip.id)}>Download PDF</Button>}>
        <Card>
          <VStack gap={2}>
            <Text>{payslip.employee_snapshot.first_name} {payslip.employee_snapshot.last_name}</Text>
            <Text muted>{payslip.employee_snapshot.designation} • {payslip.employee_snapshot.department}</Text>
            <Text muted>Period {payslip.run.period_start} → {payslip.run.period_end}</Text>
          </VStack>
        </Card>
        <Card>
          <table className="w-full text-sm">
            <thead><tr><th className="text-left">Component</th><th className="text-right">Amount</th></tr></thead>
            <tbody>
              {payslip.line_items.map((l, i) => (
                <tr key={i}>
                  <td>{l.name}</td>
                  <td className="text-right" style={{ color: l.kind === 'deduction' ? '#b91c1c' : 'inherit' }}>
                    {l.kind === 'deduction' ? '-' : ''}<Money value={l.amount} />
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr><td>Tax</td><td className="text-right">-<Money value={payslip.tax} /></td></tr>
              <tr><td><b>Net</b></td><td className="text-right"><b><Money value={payslip.net} /></b></td></tr>
            </tfoot>
          </table>
        </Card>
        <Card>
          <HStack gap={6}>
            <div>Bank: {payslip.bank_name ?? '—'}</div>
            <div>A/C: {payslip.bank_account_number ? `••••${payslip.bank_account_number.slice(-4)}` : '—'}</div>
          </HStack>
        </Card>
      </DetailPageLayout>
    </App>
  );
}

PayslipShow.layout = page => <App title="Payslip">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Payroll/Structures/Index.jsx`, `Structures/Create.jsx`, `Components/Index.jsx`, `Settings/Tax.jsx` following the same `IndexPageLayout` / `FormPageLayout` pattern with HRMAC gates.

---

## Task H5-7: PHPUnit tests (immutability is the heart)

- [ ] Create `packages/aero-hrm/tests/Feature/Payroll/PayrollRunGeneratorTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Payroll;

use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\PayComponent;
use Aero\Hrm\Models\SalaryStructure;
use Aero\Hrm\Services\Payroll\PayrollRunGenerator;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollRunGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_creation_generates_payslips_with_totals(): void
    {
        $hra = PayComponent::factory()->create(['code' => 'HRA', 'kind' => 'earning', 'calc_type' => 'percent_of_basic', 'value' => 0.20]);
        $struct = SalaryStructure::factory()->create(['basic' => 1000, 'component_ids' => [$hra->id]]);
        $employees = Employee::factory()->count(3)->create(['salary_structure_id' => $struct->id]);

        $run = app(PayrollRunGenerator::class)->create([
            'label' => 'May 2026', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31',
        ], $employees->pluck('id')->all());

        $this->assertCount(3, $run->payslips);
        $this->assertGreaterThan(0, $run->total_gross);
    }

    public function test_payslip_has_encrypted_bank_account(): void
    {
        $employee = Employee::factory()->withBankDetails('1234567890', 'Acme Bank')->create();
        // ... build minimal struct + run
        $run = app(PayrollRunGenerator::class)->create([
            'label' => 'T', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31',
        ], [$employee->id]);

        $payslip = $run->payslips->first();
        $raw = \DB::table('hrm_payslips')->where('id', $payslip->id)->value('bank_account_number');

        $this->assertNotSame('1234567890', $raw, 'bank_account_number must be encrypted at rest');
        $this->assertSame('1234567890', $payslip->bank_account_number);
    }
}
```

- [ ] Create `packages/aero-hrm/tests/Feature/Payroll/PayrollImmutabilityTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Payroll;

use Aero\Hrm\Models\Payslip;
use Aero\Hrm\Models\PayrollRun;
use Aero\Hrm\Services\Payroll\PayrollApprovalService;
use Aero\Hrm\Exceptions\PayrollLockedException;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_run_locks_it(): void
    {
        $run = PayrollRun::factory()->create(['status' => 'draft']);
        app(PayrollApprovalService::class)->approve($run);

        $this->assertNotNull($run->fresh()->locked_at);
    }

    public function test_updating_locked_run_throws(): void
    {
        $run = PayrollRun::factory()->create(['status' => 'approved', 'locked_at' => now()]);

        $this->expectException(PayrollLockedException::class);
        $run->update(['label' => 'changed']);
    }

    public function test_controller_update_returns_403_on_locked_run(): void
    {
        $run = PayrollRun::factory()->create(['status' => 'approved', 'locked_at' => now()]);
        $admin = $this->actingAsHrmAdmin();

        $this->put(route('hrm.payroll.runs.update', $run->id), ['label' => 'x'])
            ->assertStatus(403);
    }

    public function test_deleting_payslip_of_locked_run_throws(): void
    {
        $run = PayrollRun::factory()->create(['status' => 'approved', 'locked_at' => now()]);
        $slip = Payslip::factory()->for($run, 'run')->create();

        $this->expectException(PayrollLockedException::class);
        $slip->delete();
    }
}
```

- [ ] Create `packages/aero-hrm/tests/Feature/Payroll/PayslipAccessTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Payroll;

use Aero\Hrm\Models\Payslip;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayslipAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_own_payslip(): void
    {
        $slip = Payslip::factory()->create();
        $this->actingAs($slip->employee->user);

        $this->get(route('hrm.payroll.payslips.show', $slip->id))->assertOk();
    }

    public function test_employee_cannot_view_someone_elses_payslip(): void
    {
        $slip = Payslip::factory()->create();
        $other = \App\Models\User::factory()->create();
        $this->actingAs($other);

        $this->get(route('hrm.payroll.payslips.show', $slip->id))->assertStatus(403);
    }
}
```

- [ ] Run: `../../vendor/bin/phpunit --filter=Payroll` — all 7+ methods green.

---

## Task H5-8: Commit

- [ ] Stage and commit:

```powershell
git add packages/aero-hrm packages/aero-ui/resources/js/Pages/HRM/Payroll
git commit -m "feat(hrm): Plan H-5 Payroll — runs, payslips, immutability law, encrypted bank PII

- Salary structures + pay components + tax brackets
- PayrollCalculator (basic + components + tax slabs) + PayrollRunGenerator
- PayrollApprovalService: approve → set locked_at (one-way)
- Model-level guard: cannot update/delete locked runs or their payslips
- Controller-level 403 re-check on update of locked run
- EncryptedField for bank_account_number / bank_name / bank_routing_number on Payslip
- AuditService: PAYROLL_RUN_APPROVED, PAYROLL_PAYSLIP_VIEWED (sensitive access log)
- 7+ PHPUnit feature tests including immutability"
```

---

## Acceptance Criteria

- Approved run has `locked_at` set; any `UPDATE`/`DELETE` on the run or its payslips throws `PayrollLockedException`
- `PUT /hrm/payroll/runs/{run}` on a locked run returns HTTP 403
- `payslips.bank_account_number` is ciphertext at rest (raw DB query proves it)
- Every payslip view fires `PAYROLL_PAYSLIP_VIEWED` audit event
- Self-service path `hrm.payroll.my-payslips.view` works for the owning employee only
- 7+ PHPUnit tests green
