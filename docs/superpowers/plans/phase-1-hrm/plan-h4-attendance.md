# Plan H-4 — Attendance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-ready attendance subsystem covering employee clock in/out (web + QR token), admin daily and monthly views, overtime requests with approval workflow, weekly timesheets, and a shift swap marketplace — all gated by HRMAC, audited via `AuditServiceInterface`, and rendered through Inertia-driven React pages in `@aero/ui`.

**Architecture:** Five thin controllers in `packages/aero-hrm/src/Http/Controllers/Attendance/` delegate to dedicated service classes (`AttendanceClockService`, `OvertimeApprovalService`, `TimesheetAggregator`, `ShiftSwapService`). Every action enforces HRMAC at route middleware and re-asserts inside the controller via `Gate::authorize`. All mutating actions emit an `AuditServiceInterface::record()` call with module=`hrm`, submodule=`attendance`. Pages live in `packages/aero-ui/resources/js/Pages/HRM/Attendance/` and consume flat Inertia props (paginated lists use Laravel's `paginate()` result + `filters` prop).

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

**Prerequisite:** Plans A–S merged. Working directory: `c:\laragon\www\Aero-Enterprise-Suite-Saas`.

---

## File Map

| Action | Path |
|--------|------|
| Migration | `packages/aero-hrm/database/migrations/2026_05_17_000001_create_attendance_extensions.php` |
| Model | `packages/aero-hrm/src/Models/OvertimeRequest.php` |
| Model | `packages/aero-hrm/src/Models/Timesheet.php` |
| Model | `packages/aero-hrm/src/Models/TimesheetEntry.php` |
| Model | `packages/aero-hrm/src/Models/ShiftSwapRequest.php` |
| Service | `packages/aero-hrm/src/Services/Attendance/AttendanceClockService.php` |
| Service | `packages/aero-hrm/src/Services/Attendance/OvertimeApprovalService.php` |
| Service | `packages/aero-hrm/src/Services/Attendance/TimesheetAggregator.php` |
| Service | `packages/aero-hrm/src/Services/Attendance/ShiftSwapService.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Attendance/AttendanceController.php` (extend) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Attendance/OvertimeController.php` (replace) |
| Controller | `packages/aero-hrm/src/Http/Controllers/Attendance/TimesheetController.php` |
| Controller | `packages/aero-hrm/src/Http/Controllers/Attendance/ShiftMarketplaceController.php` (extend) |
| Routes | `packages/aero-hrm/routes/tenant.php` (extend) |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin/Daily.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin/Monthly.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/ClockIn.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Overtime/Index.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Overtime/Create.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Timesheets/Index.jsx` |
| Page | `packages/aero-ui/resources/js/Pages/HRM/Attendance/Shifts/Marketplace.jsx` |
| Tests | `packages/aero-hrm/tests/Feature/Attendance/AttendanceClockTest.php` |
| Tests | `packages/aero-hrm/tests/Feature/Attendance/OvertimeApprovalTest.php` |
| Tests | `packages/aero-hrm/tests/Feature/Attendance/TimesheetTest.php` |

---

## Task H4-1: Database — Overtime, Timesheet, Shift Swap tables

- [ ] Create migration `packages/aero-hrm/database/migrations/2026_05_17_000001_create_attendance_extensions.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 5, 2);
            $table->string('reason', 500);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('work_date');
        });

        Schema::create('hrm_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('week_start');
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'week_start']);
        });

        Schema::create('hrm_timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('hrm_timesheets')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('project', 150)->nullable();
            $table->string('task', 250);
            $table->decimal('hours', 4, 2);
            $table->timestamps();

            $table->index(['timesheet_id', 'entry_date']);
        });

        Schema::create('hrm_shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->date('shift_date');
            $table->string('shift_label', 50);
            $table->enum('status', ['open', 'matched', 'approved', 'rejected', 'cancelled'])->default('open');
            $table->string('note', 500)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_shift_swap_requests');
        Schema::dropIfExists('hrm_timesheet_entries');
        Schema::dropIfExists('hrm_timesheets');
        Schema::dropIfExists('hrm_overtime_requests');
    }
};
```

- [ ] Run `php artisan migrate --path=packages/aero-hrm/database/migrations/2026_05_17_000001_create_attendance_extensions.php` against a dev tenant.

---

## Task H4-2: Eloquent Models

- [ ] Create `packages/aero-hrm/src/Models/OvertimeRequest.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends TenantModel
{
    protected $table = 'hrm_overtime_requests';

    protected $fillable = [
        'employee_id', 'work_date', 'hours', 'reason',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'hours'       => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
```

- [ ] Create `packages/aero-hrm/src/Models/Timesheet.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends TenantModel
{
    protected $table = 'hrm_timesheets';

    protected $fillable = [
        'employee_id', 'week_start', 'status',
        'total_hours', 'submitted_at', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'week_start'   => 'date',
        'total_hours'  => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }
}
```

- [ ] Create `packages/aero-hrm/src/Models/TimesheetEntry.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends TenantModel
{
    protected $table = 'hrm_timesheet_entries';

    protected $fillable = ['timesheet_id', 'entry_date', 'project', 'task', 'hours'];

    protected $casts = [
        'entry_date' => 'date',
        'hours'      => 'decimal:2',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }
}
```

- [ ] Create `packages/aero-hrm/src/Models/ShiftSwapRequest.php`:

```php
<?php

namespace Aero\Hrm\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends TenantModel
{
    protected $table = 'hrm_shift_swap_requests';

    protected $fillable = [
        'requester_employee_id', 'target_employee_id', 'shift_date',
        'shift_label', 'status', 'note', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'shift_date'  => 'date',
        'approved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }
}
```

---

## Task H4-3: Services (business logic)

- [ ] Create `packages/aero-hrm/src/Services/Attendance/AttendanceClockService.php`:

```php
<?php

namespace Aero\Hrm\Services\Attendance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Attendance;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Exceptions\AttendanceException;
use Illuminate\Support\Carbon;

class AttendanceClockService
{
    public function __construct(private readonly AuditServiceInterface $audit)
    {
    }

    public function clockIn(Employee $employee, ?string $source = 'web'): Attendance
    {
        $today = Carbon::today();
        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('clock_in', $today)
            ->first();

        if ($existing) {
            throw new AttendanceException('Already clocked in today.');
        }

        $record = Attendance::create([
            'employee_id' => $employee->id,
            'clock_in'    => Carbon::now(),
            'source'      => $source,
            'status'      => $this->determineStatus($employee),
        ]);

        $this->audit->record('CLOCK_IN', 'hrm', 'attendance', [
            'employee_id'  => $employee->id,
            'attendance_id'=> $record->id,
            'source'       => $source,
        ]);

        return $record;
    }

    public function clockOut(Employee $employee): Attendance
    {
        $today = Carbon::today();
        $record = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('clock_in', $today)
            ->whereNull('clock_out')
            ->first();

        if (! $record) {
            throw new AttendanceException('No open clock-in found for today.');
        }

        $record->clock_out = Carbon::now();
        $record->total_minutes = $record->clock_in->diffInMinutes($record->clock_out);
        $record->save();

        $this->audit->record('CLOCK_OUT', 'hrm', 'attendance', [
            'employee_id'   => $employee->id,
            'attendance_id' => $record->id,
            'total_minutes' => $record->total_minutes,
        ]);

        return $record;
    }

    private function determineStatus(Employee $employee): string
    {
        $shiftStart = $employee->shift?->start_time ?? '09:00:00';
        $cutoff = Carbon::today()->setTimeFromTimeString($shiftStart)->addMinutes(15);
        return Carbon::now()->greaterThan($cutoff) ? 'late' : 'present';
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Attendance/OvertimeApprovalService.php`:

```php
<?php

namespace Aero\Hrm\Services\Attendance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\OvertimeRequest;
use Aero\Hrm\Exceptions\AttendanceException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OvertimeApprovalService
{
    public function __construct(private readonly AuditServiceInterface $audit)
    {
    }

    public function approve(OvertimeRequest $request): OvertimeRequest
    {
        if ($request->status !== 'pending') {
            throw new AttendanceException("Cannot approve a {$request->status} request.");
        }

        $request->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
        ]);

        $this->audit->record('OVERTIME_APPROVED', 'hrm', 'attendance', [
            'overtime_request_id' => $request->id,
            'employee_id'         => $request->employee_id,
            'hours'               => (float) $request->hours,
        ]);

        return $request;
    }

    public function reject(OvertimeRequest $request, string $reason): OvertimeRequest
    {
        if ($request->status !== 'pending') {
            throw new AttendanceException("Cannot reject a {$request->status} request.");
        }

        $request->update([
            'status'           => 'rejected',
            'approved_by'      => Auth::id(),
            'approved_at'      => Carbon::now(),
            'rejection_reason' => $reason,
        ]);

        $this->audit->record('OVERTIME_REJECTED', 'hrm', 'attendance', [
            'overtime_request_id' => $request->id,
            'employee_id'         => $request->employee_id,
            'reason'              => $reason,
        ]);

        return $request;
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Attendance/TimesheetAggregator.php`:

```php
<?php

namespace Aero\Hrm\Services\Attendance;

use Aero\Hrm\Models\Timesheet;

class TimesheetAggregator
{
    public function recompute(Timesheet $timesheet): void
    {
        $timesheet->total_hours = (float) $timesheet->entries()->sum('hours');
        $timesheet->save();
    }
}
```

- [ ] Create `packages/aero-hrm/src/Services/Attendance/ShiftSwapService.php`:

```php
<?php

namespace Aero\Hrm\Services\Attendance;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\ShiftSwapRequest;
use Aero\Hrm\Exceptions\AttendanceException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ShiftSwapService
{
    public function __construct(private readonly AuditServiceInterface $audit)
    {
    }

    public function approve(ShiftSwapRequest $swap): ShiftSwapRequest
    {
        if (! in_array($swap->status, ['open', 'matched'], true)) {
            throw new AttendanceException("Cannot approve a {$swap->status} swap.");
        }

        $swap->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
        ]);

        $this->audit->record('SHIFT_SWAP_APPROVED', 'hrm', 'attendance', [
            'shift_swap_id' => $swap->id,
            'requester'     => $swap->requester_employee_id,
            'target'        => $swap->target_employee_id,
        ]);

        return $swap;
    }
}
```

- [ ] Add exception class `packages/aero-hrm/src/Exceptions/AttendanceException.php` extending `\DomainException` if it does not already exist.

---

## Task H4-4: Controllers

- [ ] Replace/extend `packages/aero-hrm/src/Http/Controllers/Attendance/AttendanceController.php` with these public methods:

```php
public function daily(Request $request)
{
    Gate::authorize('hrmac', 'hrm.attendance.daily-attendance.view');

    $date = $request->date('date', now())->toDateString();

    $records = Attendance::query()
        ->with(['employee:id,first_name,last_name,department_id', 'employee.department:id,name'])
        ->whereDate('clock_in', $date)
        ->paginate(50)
        ->withQueryString();

    return Inertia::render('HRM/Attendance/Admin/Daily', [
        'date'    => $date,
        'records' => $records,
        'filters' => ['date' => $date, 'department_id' => $request->integer('department_id')],
    ]);
}

public function monthly(Request $request)
{
    Gate::authorize('hrmac', 'hrm.attendance.daily-attendance.view');

    $month = $request->date('month', now())->startOfMonth();
    $grid = app(\Aero\Hrm\Services\Attendance\MonthlyGridBuilder::class)->build($month);

    return Inertia::render('HRM/Attendance/Admin/Monthly', [
        'month'   => $month->toDateString(),
        'grid'    => $grid,
        'filters' => ['month' => $month->format('Y-m')],
    ]);
}

public function clockIn(Request $request, AttendanceClockService $clock)
{
    Gate::authorize('hrmac', 'hrm.attendance.my-attendance.view');

    $employee = $request->user()->employee;
    $record = $clock->clockIn($employee, $request->input('source', 'web'));

    return back()->with('success', "Clocked in at {$record->clock_in->format('H:i')}");
}

public function clockOut(Request $request, AttendanceClockService $clock)
{
    Gate::authorize('hrmac', 'hrm.attendance.my-attendance.view');

    $employee = $request->user()->employee;
    $record = $clock->clockOut($employee);

    return back()->with('success', "Clocked out at {$record->clock_out->format('H:i')}");
}
```

- [ ] Replace `packages/aero-hrm/src/Http/Controllers/Attendance/OvertimeController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Attendance;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\OvertimeRequest;
use Aero\Hrm\Services\Attendance\OvertimeApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');

        $requests = OvertimeRequest::query()
            ->with('employee:id,first_name,last_name')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Attendance/Overtime/Index', [
            'requests' => $requests,
            'filters'  => ['status' => $request->input('status')],
        ]);
    }

    public function create()
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');
        return Inertia::render('HRM/Attendance/Overtime/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.view');

        $data = $request->validate([
            'work_date' => 'required|date|before_or_equal:today',
            'hours'     => 'required|numeric|min:0.25|max:12',
            'reason'    => 'required|string|max:500',
        ]);

        $data['employee_id'] = $request->user()->employee->id;
        OvertimeRequest::create($data);

        return redirect()->route('hrm.attendance.overtime.index')
            ->with('success', 'Overtime request submitted.');
    }

    public function approve(OvertimeRequest $overtime, OvertimeApprovalService $svc)
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.approve');
        $svc->approve($overtime);
        return back()->with('success', 'Overtime approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtime, OvertimeApprovalService $svc)
    {
        Gate::authorize('hrmac', 'hrm.overtime.overtime-records.approve');
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $svc->reject($overtime, $data['reason']);
        return back()->with('success', 'Overtime rejected.');
    }
}
```

- [ ] Create `packages/aero-hrm/src/Http/Controllers/Attendance/TimesheetController.php`:

```php
<?php

namespace Aero\Hrm\Http\Controllers\Attendance;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Timesheet;
use Aero\Hrm\Services\Attendance\TimesheetAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('hrmac', 'hrm.attendance.attendance-logs.view');

        $weekStart = $request->date('week', Carbon::now()->startOfWeek())->toDateString();
        $employee = $request->user()->employee;

        $timesheet = Timesheet::query()
            ->with('entries')
            ->firstOrCreate(
                ['employee_id' => $employee->id, 'week_start' => $weekStart],
                ['status' => 'draft', 'total_hours' => 0]
            );

        return Inertia::render('HRM/Attendance/Timesheets/Index', [
            'timesheet' => $timesheet,
            'filters'   => ['week' => $weekStart],
        ]);
    }

    public function update(Request $request, Timesheet $timesheet, TimesheetAggregator $agg)
    {
        Gate::authorize('hrmac', 'hrm.attendance.daily-attendance.update');

        $data = $request->validate([
            'entries'              => 'required|array',
            'entries.*.entry_date' => 'required|date',
            'entries.*.task'       => 'required|string|max:250',
            'entries.*.project'    => 'nullable|string|max:150',
            'entries.*.hours'      => 'required|numeric|min:0|max:24',
        ]);

        $timesheet->entries()->delete();
        foreach ($data['entries'] as $entry) {
            $timesheet->entries()->create($entry);
        }
        $agg->recompute($timesheet);

        return back()->with('success', 'Timesheet saved.');
    }
}
```

- [ ] Extend `ShiftMarketplaceController` with `index` (paginated `ShiftSwapRequest::open()`), `store` (validate `shift_date`, `shift_label`, `note`), and `approve` (delegates to `ShiftSwapService::approve`). Each method calls `Gate::authorize('hrmac', 'hrm.attendance.shift-marketplace.view|create')`.

---

## Task H4-5: Routes + module.php HRMAC entries

- [ ] Append to `packages/aero-hrm/routes/tenant.php` (inside the `hrm` prefix group):

```php
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/daily', [AttendanceController::class, 'daily'])
        ->middleware('hrmac:hrm.attendance.daily-attendance.view')->name('daily');
    Route::get('/monthly', [AttendanceController::class, 'monthly'])
        ->middleware('hrmac:hrm.attendance.daily-attendance.view')->name('monthly');
    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])
        ->middleware('hrmac:hrm.attendance.my-attendance.view')->name('clock-in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])
        ->middleware('hrmac:hrm.attendance.my-attendance.view')->name('clock-out');

    Route::prefix('overtime')->name('overtime.')->group(function () {
        Route::get('/',           [OvertimeController::class, 'index'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('index');
        Route::get('/create',     [OvertimeController::class, 'create'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('create');
        Route::post('/',          [OvertimeController::class, 'store'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('store');
        Route::post('/{overtime}/approve', [OvertimeController::class, 'approve'])->middleware('hrmac:hrm.overtime.overtime-records.approve')->name('approve');
        Route::post('/{overtime}/reject',  [OvertimeController::class, 'reject'])->middleware('hrmac:hrm.overtime.overtime-records.approve')->name('reject');
    });

    Route::prefix('timesheets')->name('timesheets.')->group(function () {
        Route::get('/',           [TimesheetController::class, 'index'])->middleware('hrmac:hrm.attendance.attendance-logs.view')->name('index');
        Route::put('/{timesheet}', [TimesheetController::class, 'update'])->middleware('hrmac:hrm.attendance.daily-attendance.update')->name('update');
    });

    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/marketplace', [ShiftMarketplaceController::class, 'index'])->middleware('hrmac:hrm.attendance.shift-marketplace.view')->name('marketplace');
        Route::post('/marketplace', [ShiftMarketplaceController::class, 'store'])->middleware('hrmac:hrm.attendance.shift-marketplace.create')->name('marketplace.store');
        Route::post('/marketplace/{swap}/approve', [ShiftMarketplaceController::class, 'approve'])->middleware('hrmac:hrm.attendance.shift-marketplace.create')->name('marketplace.approve');
    });
});
```

- [ ] Add to `packages/aero-hrm/config/module.php` under `attendance` submodule:

```php
'attendance' => [
    'label' => 'Attendance',
    'components' => [
        'admin-view'    => ['actions' => ['view', 'edit']],
        'clock-in-out'  => ['actions' => ['view']],
        'overtime'      => ['actions' => ['view', 'edit', 'approve']],
        'timesheets'    => ['actions' => ['view', 'edit']],
        'shifts'        => ['actions' => ['view', 'edit']],
    ],
],
```

- [ ] Run `php artisan hrmac:sync` (or the project's equivalent) to materialise permissions.

---

## Task H4-6: Frontend pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/ClockIn.jsx`:

```jsx
import { router } from '@inertiajs/react';
import { IndexPageLayout, Card, Button, Badge, HStack, VStack, Text, Mono } from '@aero/ui';
import { useHRMAC } from '../../../hooks/useHRMAC.js';
import App from '../../../App.jsx';

export default function ClockIn({ today_record, employee, server_time }) {
  const canClock = useHRMAC('hrm.attendance.my-attendance.view');
  const clockIn  = () => router.post(route('hrm.attendance.clock-in'),  { source: 'web' });
  const clockOut = () => router.post(route('hrm.attendance.clock-out'), {});

  return (
    <App>
      <IndexPageLayout title="My Attendance">
        <Card>
          <VStack gap={4}>
            <Text size="xl">Hello, {employee.first_name}</Text>
            <Mono>{server_time}</Mono>
            {today_record ? (
              <HStack gap={3}>
                <Badge variant={today_record.status === 'late' ? 'warning' : 'success'}>
                  {today_record.status}
                </Badge>
                <Text>In: <Mono>{today_record.clock_in}</Mono></Text>
                {today_record.clock_out && <Text>Out: <Mono>{today_record.clock_out}</Mono></Text>}
              </HStack>
            ) : (
              <Text muted>You have not clocked in yet.</Text>
            )}
            <HStack gap={3}>
              <Button onClick={clockIn}  disabled={!canClock || (today_record && !today_record.clock_out === false)}>Clock In</Button>
              <Button onClick={clockOut} disabled={!canClock || !today_record || !!today_record.clock_out} variant="secondary">Clock Out</Button>
            </HStack>
          </VStack>
        </Card>
      </IndexPageLayout>
    </App>
  );
}

ClockIn.layout = page => <App title="Clock In/Out">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin/Daily.jsx`:

```jsx
import { router } from '@inertiajs/react';
import { IndexPageLayout, DataTable, Badge, Input, HStack } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function Daily({ date, records, filters }) {
  useHRMAC('hrm.attendance.daily-attendance.view');

  const setDate = (e) => router.get(route('hrm.attendance.daily'), { date: e.target.value }, { preserveState: true });

  const columns = [
    { header: 'Employee', accessor: row => `${row.employee.first_name} ${row.employee.last_name}` },
    { header: 'Department', accessor: row => row.employee?.department?.name ?? '-' },
    { header: 'Clock In',  accessor: 'clock_in' },
    { header: 'Clock Out', accessor: row => row.clock_out ?? '—' },
    { header: 'Status',    accessor: row => <Badge variant={row.status === 'late' ? 'warning' : 'success'}>{row.status}</Badge> },
  ];

  return (
    <App>
      <IndexPageLayout title="Daily Attendance">
        <HStack gap={3}><Input type="date" value={filters.date} onChange={setDate} /></HStack>
        <DataTable columns={columns} data={records.data} pagination={records} />
      </IndexPageLayout>
    </App>
  );
}

Daily.layout = page => <App title="Daily Attendance">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Admin/Monthly.jsx`:

```jsx
import { router } from '@inertiajs/react';
import { IndexPageLayout, Input, Badge } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function Monthly({ month, grid, filters }) {
  useHRMAC('hrm.attendance.daily-attendance.view');
  const setMonth = (e) => router.get(route('hrm.attendance.monthly'), { month: e.target.value });

  return (
    <App>
      <IndexPageLayout title="Monthly Attendance">
        <Input type="month" value={filters.month} onChange={setMonth} />
        <div className="overflow-auto">
          <table className="min-w-full text-xs">
            <thead>
              <tr>
                <th className="sticky left-0 bg-white p-2 text-left">Employee</th>
                {grid.days.map(d => <th key={d} className="p-1">{d.slice(-2)}</th>)}
              </tr>
            </thead>
            <tbody>
              {grid.rows.map(row => (
                <tr key={row.employee_id}>
                  <td className="sticky left-0 bg-white p-2">{row.name}</td>
                  {row.cells.map((c, i) => (
                    <td key={i} className="p-1 text-center">
                      <Badge size="xs" variant={c === 'P' ? 'success' : c === 'L' ? 'warning' : c === 'A' ? 'danger' : 'neutral'}>{c}</Badge>
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </IndexPageLayout>
    </App>
  );
}

Monthly.layout = page => <App title="Monthly Attendance">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Overtime/Index.jsx`:

```jsx
import { Link, router } from '@inertiajs/react';
import { IndexPageLayout, DataTable, Button, Badge, Select, HStack } from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import App from '../../../../App.jsx';

export default function OvertimeIndex({ requests, filters }) {
  const canApprove = useHRMAC('hrm.overtime.overtime-records.approve');
  const setStatus = (v) => router.get(route('hrm.attendance.overtime.index'), { status: v });

  const columns = [
    { header: 'Employee',  accessor: row => `${row.employee.first_name} ${row.employee.last_name}` },
    { header: 'Work date', accessor: 'work_date' },
    { header: 'Hours',     accessor: 'hours' },
    { header: 'Reason',    accessor: 'reason' },
    { header: 'Status',    accessor: row => <Badge variant={row.status === 'approved' ? 'success' : row.status === 'rejected' ? 'danger' : 'neutral'}>{row.status}</Badge> },
    canApprove && {
      header: '',
      accessor: row => row.status === 'pending' && (
        <HStack gap={2}>
          <Button size="xs" onClick={() => router.post(route('hrm.attendance.overtime.approve', row.id))}>Approve</Button>
          <Button size="xs" variant="danger" onClick={() => {
            const reason = window.prompt('Rejection reason');
            if (reason) router.post(route('hrm.attendance.overtime.reject', row.id), { reason });
          }}>Reject</Button>
        </HStack>
      ),
    },
  ].filter(Boolean);

  return (
    <App>
      <IndexPageLayout title="Overtime Requests"
        actions={<Link href={route('hrm.attendance.overtime.create')}><Button>Request OT</Button></Link>}>
        <Select value={filters.status ?? ''} onChange={(e) => setStatus(e.target.value)}>
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </Select>
        <DataTable columns={columns} data={requests.data} pagination={requests} />
      </IndexPageLayout>
    </App>
  );
}

OvertimeIndex.layout = page => <App title="Overtime">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Overtime/Create.jsx`:

```jsx
import { useForm } from '@inertiajs/react';
import { FormPageLayout, Input, Textarea, Button, VStack } from '@aero/ui';
import App from '../../../../App.jsx';

export default function OvertimeCreate() {
  const { data, setData, post, processing, errors } = useForm({ work_date: '', hours: '', reason: '' });
  const submit = (e) => { e.preventDefault(); post(route('hrm.attendance.overtime.store')); };

  return (
    <App>
      <FormPageLayout title="Request Overtime" onSubmit={submit}>
        <VStack gap={4}>
          <Input type="date"   label="Work date" value={data.work_date} onChange={e => setData('work_date', e.target.value)} error={errors.work_date} />
          <Input type="number" step="0.25" label="Hours" value={data.hours} onChange={e => setData('hours', e.target.value)} error={errors.hours} />
          <Textarea label="Reason" value={data.reason} onChange={e => setData('reason', e.target.value)} error={errors.reason} />
          <Button type="submit" loading={processing}>Submit</Button>
        </VStack>
      </FormPageLayout>
    </App>
  );
}

OvertimeCreate.layout = page => <App title="Request Overtime">{page}</App>;
```

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Timesheets/Index.jsx` — weekly grid; one row per weekday; columns: project, task, hours; submit button posts `entries[]` via `router.put(route('hrm.attendance.timesheets.update', timesheet.id), { entries })`.

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Attendance/Shifts/Marketplace.jsx` — list open swaps, Card for each with `Take this shift` button posting to `hrm.attendance.shifts.marketplace.approve`.

---

## Task H4-7: PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Attendance/AttendanceClockTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Attendance;

use Aero\Hrm\Models\Attendance;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Services\Attendance\AttendanceClockService;
use Aero\Hrm\Exceptions\AttendanceException;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_clock_in(): void
    {
        $employee = Employee::factory()->create();
        $record = app(AttendanceClockService::class)->clockIn($employee, 'web');

        $this->assertDatabaseHas('hrm_attendances', [
            'id'          => $record->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_double_clock_in_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $svc = app(AttendanceClockService::class);
        $svc->clockIn($employee);

        $this->expectException(AttendanceException::class);
        $svc->clockIn($employee);
    }

    public function test_clock_out_records_total_minutes(): void
    {
        $employee = Employee::factory()->create();
        $svc = app(AttendanceClockService::class);
        $svc->clockIn($employee);
        $this->travel(45)->minutes();
        $record = $svc->clockOut($employee);

        $this->assertGreaterThanOrEqual(44, $record->total_minutes);
    }
}
```

- [ ] Create `packages/aero-hrm/tests/Feature/Attendance/OvertimeApprovalTest.php`:

```php
<?php

namespace Aero\Hrm\Tests\Feature\Attendance;

use Aero\Hrm\Models\OvertimeRequest;
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Services\Attendance\OvertimeApprovalService;
use Aero\Hrm\Exceptions\AttendanceException;
use Aero\Hrm\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OvertimeApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_request_can_be_approved(): void
    {
        $request = OvertimeRequest::factory()->create(['status' => 'pending']);
        app(OvertimeApprovalService::class)->approve($request);

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_already_approved_request_cannot_be_approved_again(): void
    {
        $request = OvertimeRequest::factory()->create(['status' => 'approved']);
        $this->expectException(AttendanceException::class);
        app(OvertimeApprovalService::class)->approve($request);
    }

    public function test_request_can_be_rejected_with_reason(): void
    {
        $request = OvertimeRequest::factory()->create(['status' => 'pending']);
        app(OvertimeApprovalService::class)->reject($request, 'Not justified');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Not justified', $request->fresh()->rejection_reason);
    }
}
```

- [ ] Run tests:

```powershell
cd packages/aero-hrm
../../vendor/bin/phpunit --testsuite=Feature --filter="Attendance"
```

All 6+ methods must pass green.

---

## Task H4-8: Commit

- [ ] Stage and commit:

```powershell
git add packages/aero-hrm packages/aero-ui/resources/js/Pages/HRM/Attendance
git commit -m "feat(hrm): Plan H-4 Attendance — clock in/out, daily/monthly views, overtime, timesheets, shift marketplace

- AttendanceClockService with audit trail (CLOCK_IN/CLOCK_OUT)
- OvertimeApprovalService with approve/reject + AuditService
- Timesheet aggregator + weekly grid page
- Shift swap marketplace page + approval endpoint
- 6 PHPUnit feature tests
- HRMAC paths: hrm.attendance.{daily-attendance,my-attendance,attendance-logs,shift-marketplace}, hrm.overtime.overtime-records"
```

---

## Acceptance Criteria

- All routes carry `hrmac:` middleware
- Every state-changing service call emits exactly one `AuditServiceInterface::record()` event
- No business logic in controllers — they only validate, authorize, delegate, return Inertia
- Duplicate clock-in within the same day returns 422-equivalent (`AttendanceException`)
- Locked/approved overtime cannot be re-approved
- All 6 PHPUnit tests green; Playwright happy-path `tests/e2e/attendance.spec.ts` covers clock in → clock out → overtime request
