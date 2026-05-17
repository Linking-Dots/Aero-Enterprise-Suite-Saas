# Plan H-3 — Leave Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an end-to-end leave management surface — leave types, employee applications, admin approve/reject, per-employee balance, team calendar, and global settings — fully wired to HRMAC and audited.

**Architecture:** Five Inertia controllers (`LeaveTypeController`, `LeaveApplicationController`, `LeaveBalanceController`, `LeaveCalendarController`, `LeaveSettingController`) delegate balance arithmetic and state transitions to a `LeaveApplicationService`. Approvals/rejections/cancellations emit `LEAVE_APPROVED` / `LEAVE_REJECTED` / `LEAVE_CANCELLED` audit events. The team calendar endpoint returns flat FullCalendar-style events for a given date range. Settings are a key/value JSON document stored on the `leave_settings` table (single tenant-scoped row).

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations + Models

**Files:**
- Create: `packages/aero-hrm/database/migrations/2026_05_17_020001_create_leave_types_table.php`
- Create: `packages/aero-hrm/database/migrations/2026_05_17_020002_create_leave_applications_table.php`
- Create: `packages/aero-hrm/database/migrations/2026_05_17_020003_create_leave_balances_table.php`
- Create: `packages/aero-hrm/database/migrations/2026_05_17_020004_create_leave_settings_table.php`
- Create: `packages/aero-hrm/src/Models/LeaveType.php`
- Create: `packages/aero-hrm/src/Models/LeaveApplication.php`
- Create: `packages/aero-hrm/src/Models/LeaveBalance.php`
- Create: `packages/aero-hrm/src/Models/LeaveSetting.php`

- [ ] `create_leave_types_table` migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('code', 16)->unique();
            $t->string('color', 16)->default('#3b82f6');
            $t->decimal('days_per_year', 5, 2)->default(0);
            $t->boolean('is_paid')->default(true);
            $t->boolean('requires_approval')->default(true);
            $t->boolean('carry_forward')->default(false);
            $t->boolean('encashable')->default(false);
            $t->decimal('max_carry_forward', 5, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('leave_types'); }
};
```

- [ ] `create_leave_applications_table` migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('total_days', 5, 2);
            $t->string('status', 16)->default('pending'); // pending, approved, rejected, cancelled
            $t->text('reason')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'start_date']);
            $t->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('leave_applications'); }
};
```

- [ ] `create_leave_balances_table` migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->decimal('entitled', 6, 2)->default(0);
            $t->decimal('used', 6, 2)->default(0);
            $t->decimal('carried_forward', 6, 2)->default(0);
            $t->decimal('encashed', 6, 2)->default(0);
            $t->timestamps();
            $t->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }
    public function down(): void { Schema::dropIfExists('leave_balances'); }
};
```

- [ ] `create_leave_settings_table` migration:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_settings', function (Blueprint $t) {
            $t->id();
            $t->string('accrual_cycle', 16)->default('yearly'); // yearly, monthly, quarterly
            $t->boolean('allow_negative_balance')->default(false);
            $t->boolean('auto_approve_under_days')->default(false);
            $t->decimal('auto_approve_threshold', 4, 2)->default(0);
            $t->boolean('encashment_enabled')->default(false);
            $t->json('extra')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('leave_settings'); }
};
```

- [ ] `LeaveType.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends TenantModel
{
    use SoftDeletes;
    protected $fillable = ['name', 'code', 'color', 'days_per_year', 'is_paid', 'requires_approval', 'carry_forward', 'encashable', 'max_carry_forward', 'is_active'];
    protected $casts = [
        'is_paid' => 'boolean', 'requires_approval' => 'boolean',
        'carry_forward' => 'boolean', 'encashable' => 'boolean',
        'is_active' => 'boolean', 'days_per_year' => 'decimal:2',
        'max_carry_forward' => 'decimal:2',
    ];
}
```

- [ ] `LeaveApplication.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends TenantModel
{
    use SoftDeletes;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days',
        'status', 'reason', 'rejection_reason',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'total_days'  => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function rejector(): BelongsTo  { return $this->belongsTo(\App\Models\User::class, 'rejected_by'); }
}
```

- [ ] `LeaveBalance.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends TenantModel
{
    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'entitled', 'used', 'carried_forward', 'encashed'];
    protected $casts    = ['entitled' => 'decimal:2', 'used' => 'decimal:2', 'carried_forward' => 'decimal:2', 'encashed' => 'decimal:2'];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }

    public function getRemainingAttribute(): float
    {
        return (float) $this->entitled + (float) $this->carried_forward - (float) $this->used - (float) $this->encashed;
    }
}
```

- [ ] `LeaveSetting.php`:
```php
<?php
namespace Aero\HRM\Models;
use Aero\Contracts\Models\TenantModel;

class LeaveSetting extends TenantModel
{
    protected $fillable = ['accrual_cycle', 'allow_negative_balance', 'auto_approve_under_days', 'auto_approve_threshold', 'encashment_enabled', 'extra'];
    protected $casts = [
        'allow_negative_balance'  => 'boolean',
        'auto_approve_under_days' => 'boolean',
        'auto_approve_threshold'  => 'decimal:2',
        'encashment_enabled'      => 'boolean',
        'extra'                   => 'array',
    ];
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/database/migrations/2026_05_17_0200*.php \
       packages/aero-hrm/src/Models/LeaveType.php \
       packages/aero-hrm/src/Models/LeaveApplication.php \
       packages/aero-hrm/src/Models/LeaveBalance.php \
       packages/aero-hrm/src/Models/LeaveSetting.php
git commit -m "feat(aero-hrm): leave management schema + models"
```

---

## Task 2 — LeaveApplicationService + audit event registration

**Files:**
- Create: `packages/aero-hrm/src/Services/LeaveApplicationService.php`
- Modify: `packages/aero-core/src/Services/Audit/AuditEventType.php` (add `LEAVE_CANCELLED`)

- [ ] Add new audit event:
```php
case LEAVE_CANCELLED = 'hrm.leave.cancelled';
```

- [ ] Write the service:
```php
<?php

namespace Aero\HRM\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveApplicationService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function calculateDays(Carbon $start, Carbon $end): float
    {
        return (float) ($start->diffInDays($end) + 1);
    }

    public function create(array $data): LeaveApplication
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['start_date']);
            $end   = Carbon::parse($data['end_date']);

            if ($end->lt($start)) {
                throw new RuntimeException('End date cannot be before start date.');
            }

            $days = $this->calculateDays($start, $end);
            $type = LeaveType::findOrFail($data['leave_type_id']);

            $app = LeaveApplication::create([
                'employee_id'   => $data['employee_id'],
                'leave_type_id' => $type->id,
                'start_date'    => $start,
                'end_date'      => $end,
                'total_days'    => $days,
                'status'        => $type->requires_approval
                    ? LeaveApplication::STATUS_PENDING
                    : LeaveApplication::STATUS_APPROVED,
                'reason'        => $data['reason'] ?? null,
                'approved_by'   => $type->requires_approval ? null : Auth::id(),
                'approved_at'   => $type->requires_approval ? null : now(),
            ]);

            if ($app->status === LeaveApplication::STATUS_APPROVED) {
                $this->applyBalanceImpact($app, +1);
            }

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $app,
                description: "Leave application #{$app->id} submitted",
                after: $app->only(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days', 'status']),
            );

            return $app;
        });
    }

    public function approve(LeaveApplication $app): LeaveApplication
    {
        return DB::transaction(function () use ($app) {
            if ($app->status !== LeaveApplication::STATUS_PENDING) {
                throw new RuntimeException('Only pending applications can be approved.');
            }

            $app->update([
                'status'      => LeaveApplication::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->applyBalanceImpact($app, +1);

            $this->audit->log(
                event: AuditEventType::LEAVE_APPROVED->value,
                action: 'approved',
                subject: $app,
                description: "Leave #{$app->id} approved",
            );

            return $app;
        });
    }

    public function reject(LeaveApplication $app, string $reason): LeaveApplication
    {
        return DB::transaction(function () use ($app, $reason) {
            if ($app->status !== LeaveApplication::STATUS_PENDING) {
                throw new RuntimeException('Only pending applications can be rejected.');
            }

            $app->update([
                'status'           => LeaveApplication::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'rejected_by'      => Auth::id(),
                'rejected_at'      => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::LEAVE_REJECTED->value,
                action: 'rejected',
                subject: $app,
                description: "Leave #{$app->id} rejected",
                metadata: ['reason' => $reason],
            );

            return $app;
        });
    }

    public function cancel(LeaveApplication $app): LeaveApplication
    {
        return DB::transaction(function () use ($app) {
            $wasApproved = $app->status === LeaveApplication::STATUS_APPROVED;
            $app->update(['status' => LeaveApplication::STATUS_CANCELLED]);

            if ($wasApproved) {
                $this->applyBalanceImpact($app, -1);
            }

            $this->audit->log(
                event: AuditEventType::LEAVE_CANCELLED->value,
                action: 'cancelled',
                subject: $app,
                description: "Leave #{$app->id} cancelled",
            );

            return $app;
        });
    }

    private function applyBalanceImpact(LeaveApplication $app, int $direction): void
    {
        $year = $app->start_date->year;

        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $app->employee_id, 'leave_type_id' => $app->leave_type_id, 'year' => $year],
            ['entitled' => $app->leaveType->days_per_year ?? 0],
        );

        $balance->used = max(0, (float) $balance->used + ($direction * (float) $app->total_days));
        $balance->save();
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Services/LeaveApplicationService.php \
       packages/aero-core/src/Services/Audit/AuditEventType.php
git commit -m "feat(aero-hrm): LeaveApplicationService + LEAVE_CANCELLED audit event"
```

---

## Task 3 — Form Requests

**Files:**
- Create: `packages/aero-hrm/src/Http/Requests/Leave/LeaveTypeRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/Leave/StoreLeaveApplicationRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/Leave/RejectLeaveRequest.php`
- Create: `packages/aero-hrm/src/Http/Requests/Leave/LeaveSettingRequest.php`

- [ ] `LeaveTypeRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leave.types.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('type')?->id;
        return [
            'name'              => ['required', 'string', 'max:80', Rule::unique('leave_types', 'name')->ignore($id)],
            'code'              => ['required', 'string', 'max:16', Rule::unique('leave_types', 'code')->ignore($id)],
            'color'             => ['nullable', 'string', 'max:16'],
            'days_per_year'     => ['required', 'numeric', 'min:0', 'max:366'],
            'is_paid'           => ['boolean'],
            'requires_approval' => ['boolean'],
            'carry_forward'     => ['boolean'],
            'encashable'        => ['boolean'],
            'max_carry_forward' => ['nullable', 'numeric', 'min:0'],
            'is_active'         => ['boolean'],
        ];
    }
}
```

- [ ] `StoreLeaveApplicationRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated employee may apply for self;
        // applying on behalf requires the admin scope.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

- [ ] `RejectLeaveRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leave.applications.approve') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }
}
```

- [ ] `LeaveSettingRequest`:
```php
<?php
namespace Aero\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hrm.leave.settings.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'accrual_cycle'           => ['required', Rule::in(['yearly', 'monthly', 'quarterly'])],
            'allow_negative_balance'  => ['boolean'],
            'auto_approve_under_days' => ['boolean'],
            'auto_approve_threshold'  => ['nullable', 'numeric', 'min:0', 'max:30'],
            'encashment_enabled'      => ['boolean'],
        ];
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Requests/Leave/
git commit -m "feat(aero-hrm): form requests for leave types/applications/settings"
```

---

## Task 4 — Controllers (5)

**Files:**
- Create: `packages/aero-hrm/src/Http/Controllers/Leave/LeaveTypeController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Leave/LeaveApplicationController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Leave/LeaveBalanceController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Leave/LeaveCalendarController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Leave/LeaveSettingController.php`

- [ ] `LeaveTypeController`:
```php
<?php
namespace Aero\HRM\Http\Controllers\Leave;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\LeaveTypeRequest;
use Aero\HRM\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $this->authorize('hrm.leave.types.view');

        return Inertia::render('HRM/Leave/Types/Index', [
            'types' => LeaveType::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        $type = LeaveType::create($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_CREATED->value,
            action: 'created',
            subject: $type,
            description: "Leave type {$type->name} created",
        );

        return back()->with('success', 'Leave type created.');
    }

    public function update(LeaveTypeRequest $request, LeaveType $type): RedirectResponse
    {
        $before = $type->only(['name', 'code', 'days_per_year', 'is_active']);
        $type->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $type,
            description: "Leave type {$type->name} updated",
            before: $before,
            after: $type->only(['name', 'code', 'days_per_year', 'is_active']),
        );

        return back()->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $type): RedirectResponse
    {
        $this->authorize('hrm.leave.types.edit');
        $type->delete();

        $this->audit->log(
            event: AuditEventType::RECORD_DELETED->value,
            action: 'deleted',
            subject: $type,
            description: "Leave type {$type->name} deleted",
        );

        return back()->with('success', 'Leave type deleted.');
    }
}
```

- [ ] `LeaveApplicationController`:
```php
<?php
namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\RejectLeaveRequest;
use Aero\HRM\Http\Requests\Leave\StoreLeaveApplicationRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveType;
use Aero\HRM\Services\LeaveApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApplicationController extends Controller
{
    public function __construct(private readonly LeaveApplicationService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('hrm.leave.applications.view');

        $filters = $request->only(['search', 'status', 'leave_type_id', 'from', 'to']);

        $applications = LeaveApplication::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['leave_type_id'] ?? null, fn ($q, $id) => $q->where('leave_type_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($filters['to']   ?? null, fn ($q, $d) => $q->whereDate('end_date', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Leave/Applications/Index', [
            'applications' => $applications,
            'leaveTypes'   => LeaveType::select('id', 'name')->get(),
            'statuses'     => ['pending', 'approved', 'rejected', 'cancelled'],
            'filters'      => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Leave/Applications/Create', [
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'employees'  => Employee::query()->with('user:id,name')->select('id', 'user_id', 'employee_code')->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => "{$e->employee_code} — ".($e->user?->name ?? '—')]),
        ]);
    }

    public function store(StoreLeaveApplicationRequest $request): RedirectResponse
    {
        $app = $this->service->create($request->validated());

        return redirect()->route('hrm.leave.applications.show', $app)
            ->with('success', 'Leave application submitted.');
    }

    public function show(LeaveApplication $application): Response
    {
        $this->authorize('hrm.leave.applications.view');

        $application->load(['employee.user', 'leaveType', 'approver', 'rejector']);

        return Inertia::render('HRM/Leave/Applications/Show', [
            'application' => $application,
            'permissions' => [
                'canApprove' => request()->user()->can('hrm.leave.applications.approve'),
            ],
        ]);
    }

    public function approve(LeaveApplication $application): RedirectResponse
    {
        abort_unless(request()->user()->can('hrm.leave.applications.approve'), 403);
        $this->service->approve($application);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(RejectLeaveRequest $request, LeaveApplication $application): RedirectResponse
    {
        $this->service->reject($application, $request->string('reason')->toString());

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(LeaveApplication $application): RedirectResponse
    {
        abort_unless(
            $application->employee->user_id === request()->user()->id
                || request()->user()->can('hrm.leave.applications.edit'),
            403,
        );
        $this->service->cancel($application);

        return back()->with('success', 'Leave cancelled.');
    }
}
```

- [ ] `LeaveBalanceController`:
```php
<?php
namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hrm.leave.balance.view');

        $year = (int) $request->integer('year', now()->year);

        $balances = LeaveBalance::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->where('year', $year)
            ->when($request->integer('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->orderBy('employee_id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (LeaveBalance $b) => [
                'id'             => $b->id,
                'employee'       => $b->employee?->user?->name,
                'leave_type'     => $b->leaveType?->name,
                'entitled'       => $b->entitled,
                'used'           => $b->used,
                'carried_forward' => $b->carried_forward,
                'remaining'      => $b->remaining,
            ]);

        return Inertia::render('HRM/Leave/Balance/Index', [
            'balances'  => $balances,
            'employees' => Employee::with('user:id,name')->get()->map(fn ($e) => ['id' => $e->id, 'label' => $e->user?->name]),
            'year'      => $year,
        ]);
    }
}
```

- [ ] `LeaveCalendarController`:
```php
<?php
namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hrm.leave.calendar.view');

        $from = $request->date('from', now()->startOfMonth());
        $to   = $request->date('to',   now()->endOfMonth());

        $events = LeaveApplication::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->whereBetween('start_date', [$from, $to])
            ->get()
            ->map(fn (LeaveApplication $a) => [
                'id'    => $a->id,
                'title' => ($a->employee?->user?->name ?? '—').' — '.$a->leaveType?->name,
                'start' => $a->start_date->toDateString(),
                'end'   => $a->end_date->copy()->addDay()->toDateString(), // FullCalendar exclusive
                'color' => $a->leaveType?->color,
            ]);

        return Inertia::render('HRM/Leave/Calendar/Index', [
            'events' => $events,
            'range'  => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
```

- [ ] `LeaveSettingController`:
```php
<?php
namespace Aero\HRM\Http\Controllers\Leave;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\LeaveSettingRequest;
use Aero\HRM\Models\LeaveSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveSettingController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $this->authorize('hrm.leave.settings.view');

        return Inertia::render('HRM/Leave/Settings/Index', [
            'settings' => LeaveSetting::firstOrCreate([]),
        ]);
    }

    public function update(LeaveSettingRequest $request): RedirectResponse
    {
        $settings = LeaveSetting::firstOrCreate([]);
        $before = $settings->toArray();
        $settings->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $settings,
            description: 'Leave settings updated',
            before: $before,
            after: $settings->fresh()->toArray(),
        );

        return back()->with('success', 'Settings saved.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Leave/
git commit -m "feat(aero-hrm): leave controllers (types/applications/balance/calendar/settings)"
```

---

## Task 5 — Routes + HRMAC config

**Files:**
- Modify: `packages/aero-hrm/routes/tenant.php`
- Modify: `packages/aero-hrm/config/module.php`

- [ ] Append routes:
```php
use Aero\HRM\Http\Controllers\Leave\LeaveTypeController;
use Aero\HRM\Http\Controllers\Leave\LeaveApplicationController;
use Aero\HRM\Http\Controllers\Leave\LeaveBalanceController;
use Aero\HRM\Http\Controllers\Leave\LeaveCalendarController;
use Aero\HRM\Http\Controllers\Leave\LeaveSettingController;

Route::middleware(['auth', 'tenant'])->prefix('hrm/leave')->name('hrm.leave.')->group(function () {

    Route::prefix('types')->name('types.')->group(function () {
        Route::get('/',    [LeaveTypeController::class, 'index'])->middleware('hrmac:hrm.leave.types.view')->name('index');
        Route::post('/',   [LeaveTypeController::class, 'store'])->middleware('hrmac:hrm.leave.types.edit')->name('store');
        Route::put('/{type}',    [LeaveTypeController::class, 'update'])->middleware('hrmac:hrm.leave.types.edit')->name('update');
        Route::delete('/{type}', [LeaveTypeController::class, 'destroy'])->middleware('hrmac:hrm.leave.types.edit')->name('destroy');
    });

    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/',         [LeaveApplicationController::class, 'index'])->middleware('hrmac:hrm.leave.applications.view')->name('index');
        Route::get('/create',   [LeaveApplicationController::class, 'create'])->name('create');
        Route::post('/',        [LeaveApplicationController::class, 'store'])->name('store');
        Route::get('/{application}',          [LeaveApplicationController::class, 'show'])->middleware('hrmac:hrm.leave.applications.view')->name('show');
        Route::post('/{application}/approve', [LeaveApplicationController::class, 'approve'])->middleware('hrmac:hrm.leave.applications.approve')->name('approve');
        Route::post('/{application}/reject',  [LeaveApplicationController::class, 'reject'])->middleware('hrmac:hrm.leave.applications.approve')->name('reject');
        Route::post('/{application}/cancel',  [LeaveApplicationController::class, 'cancel'])->name('cancel');
    });

    Route::get('balance',  [LeaveBalanceController::class, 'index'])->middleware('hrmac:hrm.leave.balance.view')->name('balance.index');
    Route::get('calendar', [LeaveCalendarController::class, 'index'])->middleware('hrmac:hrm.leave.calendar.view')->name('calendar.index');

    Route::get('settings', [LeaveSettingController::class, 'index'])->middleware('hrmac:hrm.leave.settings.view')->name('settings.index');
    Route::put('settings', [LeaveSettingController::class, 'update'])->middleware('hrmac:hrm.leave.settings.edit')->name('settings.update');
});
```

- [ ] Append HRMAC entries to `config/module.php`:
```php
'leave' => [
    'label' => 'Leave',
    'components' => [
        'types'          => ['actions' => ['view', 'edit']],
        'applications'   => ['actions' => ['view', 'edit', 'approve']],
        'balance'        => ['actions' => ['view']],
        'calendar'       => ['actions' => ['view']],
        'settings'       => ['actions' => ['view', 'edit']],
    ],
],
```

- [ ] Clear HRMAC cache:
```bash
php artisan hrmac:cache:clear
```

- [ ] Commit:
```bash
git add packages/aero-hrm/routes/tenant.php packages/aero-hrm/config/module.php
git commit -m "feat(aero-hrm): leave routes + HRMAC config"
```

---

## Task 6 — React pages: Types + Applications Index/Create/Show

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Types/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Applications/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Applications/Create.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Applications/Show.jsx`

- [ ] `Types/Index.jsx`:
```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Button, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Switch, useDisclosure, Chip } from '@aero/ui';

export default function LeaveTypesIndex({ types }) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '', code: '', color: '#3b82f6', days_per_year: 0,
    is_paid: true, requires_approval: true, carry_forward: false, encashable: false, max_carry_forward: '', is_active: true,
  });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = (t) => { setEditing(t); setData({ ...t }); onOpen(); };
  const submit = (e) => {
    e.preventDefault();
    if (editing) put(route('hrm.leave.types.update', editing.id), { onSuccess: onClose });
    else post(route('hrm.leave.types.store'), { onSuccess: onClose });
  };

  return (
    <>
      <Head title="Leave Types" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Leave Types</h1>
          <Button color="primary" onPress={openCreate}>New Leave Type</Button>
        </CardHeader>
        <CardBody>
          <Table aria-label="Leave types">
            <TableHeader>
              <TableColumn>Name</TableColumn>
              <TableColumn>Code</TableColumn>
              <TableColumn>Days/Year</TableColumn>
              <TableColumn>Paid</TableColumn>
              <TableColumn>Approval</TableColumn>
              <TableColumn>Status</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No leave types.">
              {types.data.map(t => (
                <TableRow key={t.id}>
                  <TableCell><span className="inline-block w-3 h-3 rounded-full mr-2" style={{ background: t.color }} /> {t.name}</TableCell>
                  <TableCell>{t.code}</TableCell>
                  <TableCell>{t.days_per_year}</TableCell>
                  <TableCell>{t.is_paid ? 'Yes' : 'No'}</TableCell>
                  <TableCell>{t.requires_approval ? 'Required' : 'Auto'}</TableCell>
                  <TableCell><Chip size="sm">{t.is_active ? 'Active' : 'Inactive'}</Chip></TableCell>
                  <TableCell>
                    <Button size="sm" variant="light" onPress={() => openEdit(t)}>Edit</Button>
                    <Button size="sm" variant="light" color="danger" onPress={() => router.delete(route('hrm.leave.types.destroy', t.id))}>Delete</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardBody>
      </Card>

      <Modal isOpen={isOpen} onClose={onClose} size="lg">
        <ModalContent>
          <form onSubmit={submit}>
            <ModalHeader>{editing ? 'Edit Leave Type' : 'New Leave Type'}</ModalHeader>
            <ModalBody className="grid grid-cols-2 gap-4">
              <Input label="Name" value={data.name} onValueChange={(v) => setData('name', v)} errorMessage={errors.name} />
              <Input label="Code" value={data.code} onValueChange={(v) => setData('code', v)} errorMessage={errors.code} />
              <Input label="Color" type="color" value={data.color} onValueChange={(v) => setData('color', v)} />
              <Input label="Days/Year" type="number" step="0.5" value={data.days_per_year} onValueChange={(v) => setData('days_per_year', v)} errorMessage={errors.days_per_year} />
              <Input label="Max Carry Forward" type="number" step="0.5" value={data.max_carry_forward ?? ''} onValueChange={(v) => setData('max_carry_forward', v)} />
              <div className="col-span-2 grid grid-cols-2 gap-2">
                <Switch isSelected={data.is_paid} onValueChange={(v) => setData('is_paid', v)}>Paid leave</Switch>
                <Switch isSelected={data.requires_approval} onValueChange={(v) => setData('requires_approval', v)}>Requires approval</Switch>
                <Switch isSelected={data.carry_forward} onValueChange={(v) => setData('carry_forward', v)}>Carry forward</Switch>
                <Switch isSelected={data.encashable} onValueChange={(v) => setData('encashable', v)}>Encashable</Switch>
                <Switch isSelected={data.is_active} onValueChange={(v) => setData('is_active', v)}>Active</Switch>
              </div>
            </ModalBody>
            <ModalFooter>
              <Button variant="light" onPress={onClose}>Cancel</Button>
              <Button type="submit" color="primary" isLoading={processing}>Save</Button>
            </ModalFooter>
          </form>
        </ModalContent>
      </Modal>
    </>
  );
}
LeaveTypesIndex.layout = page => <App title="Leave Types">{page}</App>;
```

- [ ] `Applications/Index.jsx`:
```jsx
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Button, Chip, Select, SelectItem, Input, Pagination } from '@aero/ui';

const statusColor = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'default' };

export default function ApplicationsIndex({ applications, leaveTypes, statuses, filters }) {
  const [status, setStatus] = useState(filters.status ?? '');
  const [leaveTypeId, setLeaveTypeId] = useState(filters.leave_type_id ?? '');
  const [from, setFrom] = useState(filters.from ?? '');
  const [to, setTo] = useState(filters.to ?? '');

  const apply = () => router.get(route('hrm.leave.applications.index'), {
    status: status || undefined,
    leave_type_id: leaveTypeId || undefined,
    from: from || undefined,
    to: to || undefined,
  }, { preserveState: true, replace: true });

  return (
    <>
      <Head title="Leave Applications" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Leave Applications</h1>
          <Button as={Link} href={route('hrm.leave.applications.create')} color="primary">Apply for Leave</Button>
        </CardHeader>
        <CardBody className="gap-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <Select label="Status" selectedKeys={status ? [status] : []} onSelectionChange={(k) => { setStatus([...k][0] ?? ''); apply(); }}>
              <SelectItem key="">All</SelectItem>
              {statuses.map(s => <SelectItem key={s}>{s}</SelectItem>)}
            </Select>
            <Select label="Leave Type" selectedKeys={leaveTypeId ? [String(leaveTypeId)] : []} onSelectionChange={(k) => { setLeaveTypeId([...k][0] ?? ''); apply(); }}>
              <SelectItem key="">All</SelectItem>
              {leaveTypes.map(t => <SelectItem key={t.id}>{t.name}</SelectItem>)}
            </Select>
            <Input type="date" label="From" value={from} onValueChange={setFrom} onBlur={apply} />
            <Input type="date" label="To"   value={to}   onValueChange={setTo}   onBlur={apply} />
          </div>

          <Table aria-label="Leave applications">
            <TableHeader>
              <TableColumn>Employee</TableColumn>
              <TableColumn>Type</TableColumn>
              <TableColumn>From</TableColumn>
              <TableColumn>To</TableColumn>
              <TableColumn>Days</TableColumn>
              <TableColumn>Status</TableColumn>
              <TableColumn> </TableColumn>
            </TableHeader>
            <TableBody emptyContent="No applications.">
              {applications.data.map(a => (
                <TableRow key={a.id}>
                  <TableCell>{a.employee?.user?.name ?? '—'}</TableCell>
                  <TableCell>{a.leave_type?.name}</TableCell>
                  <TableCell>{a.start_date}</TableCell>
                  <TableCell>{a.end_date}</TableCell>
                  <TableCell>{a.total_days}</TableCell>
                  <TableCell><Chip size="sm" color={statusColor[a.status]}>{a.status}</Chip></TableCell>
                  <TableCell><Button size="sm" as={Link} href={route('hrm.leave.applications.show', a.id)} variant="light">View</Button></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end">
            <Pagination total={applications.last_page} page={applications.current_page}
              onChange={(p) => router.get(route('hrm.leave.applications.index'), { ...filters, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>
    </>
  );
}
ApplicationsIndex.layout = page => <App title="Leave Applications">{page}</App>;
```

- [ ] `Applications/Create.jsx`:
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, Input, Select, SelectItem, Textarea, Button } from '@aero/ui';

export default function CreateLeaveApplication({ leaveTypes, employees }) {
  const { data, setData, post, processing, errors } = useForm({
    employee_id: employees[0]?.id ?? null,
    leave_type_id: leaveTypes[0]?.id ?? null,
    start_date: '', end_date: '', reason: '',
  });

  const submit = (e) => { e.preventDefault(); post(route('hrm.leave.applications.store')); };

  return (
    <>
      <Head title="Apply for Leave" />
      <form onSubmit={submit}>
        <Card><CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Select label="Employee" selectedKeys={data.employee_id ? [String(data.employee_id)] : []}
                  onSelectionChange={(k) => setData('employee_id', [...k][0] ?? null)} errorMessage={errors.employee_id}>
            {employees.map(e => <SelectItem key={e.id}>{e.label}</SelectItem>)}
          </Select>
          <Select label="Leave Type" selectedKeys={data.leave_type_id ? [String(data.leave_type_id)] : []}
                  onSelectionChange={(k) => setData('leave_type_id', [...k][0] ?? null)} errorMessage={errors.leave_type_id}>
            {leaveTypes.map(t => <SelectItem key={t.id}>{t.name}</SelectItem>)}
          </Select>
          <Input type="date" label="Start Date" value={data.start_date} onValueChange={(v) => setData('start_date', v)} errorMessage={errors.start_date} />
          <Input type="date" label="End Date"   value={data.end_date}   onValueChange={(v) => setData('end_date', v)} errorMessage={errors.end_date} />
          <Textarea label="Reason" value={data.reason} onValueChange={(v) => setData('reason', v)} errorMessage={errors.reason} className="md:col-span-2" />
          <div className="md:col-span-2 flex justify-end">
            <Button type="submit" color="primary" isLoading={processing}>Submit Application</Button>
          </div>
        </CardBody></Card>
      </form>
    </>
  );
}
CreateLeaveApplication.layout = page => <App title="Apply for Leave">{page}</App>;
```

- [ ] `Applications/Show.jsx`:
```jsx
import { Head, useForm, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Button, Chip, Divider, Textarea, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure } from '@aero/ui';

const statusColor = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'default' };

export default function ShowLeaveApplication({ application, permissions }) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const { data, setData, post, processing, errors } = useForm({ reason: '' });

  const approve = () => router.post(route('hrm.leave.applications.approve', application.id));
  const reject  = (e) => { e.preventDefault(); post(route('hrm.leave.applications.reject', application.id), { onSuccess: onClose }); };
  const cancel  = () => router.post(route('hrm.leave.applications.cancel', application.id));

  return (
    <>
      <Head title={`Leave #${application.id}`} />
      <Card>
        <CardHeader className="flex justify-between">
          <div>
            <h1 className="text-2xl font-semibold">Leave Application #{application.id}</h1>
            <p className="text-default-500">{application.employee?.user?.name} · {application.leave_type?.name}</p>
          </div>
          <Chip color={statusColor[application.status]}>{application.status}</Chip>
        </CardHeader>
        <CardBody className="gap-4">
          <div className="grid grid-cols-3 gap-4">
            <div><p className="text-xs text-default-500">From</p><p className="font-medium">{application.start_date}</p></div>
            <div><p className="text-xs text-default-500">To</p><p className="font-medium">{application.end_date}</p></div>
            <div><p className="text-xs text-default-500">Total Days</p><p className="font-medium">{application.total_days}</p></div>
          </div>
          {application.reason && <div><p className="text-xs text-default-500">Reason</p><p>{application.reason}</p></div>}
          {application.rejection_reason && <div><p className="text-xs text-default-500">Rejection Reason</p><p>{application.rejection_reason}</p></div>}
          <Divider />

          <div className="flex gap-2 justify-end">
            {application.status === 'pending' && permissions.canApprove && (
              <>
                <Button color="success" onPress={approve}>Approve</Button>
                <Button color="danger" variant="flat" onPress={onOpen}>Reject</Button>
              </>
            )}
            {application.status === 'pending' && (
              <Button variant="light" onPress={cancel}>Cancel</Button>
            )}
          </div>
        </CardBody>
      </Card>

      <Modal isOpen={isOpen} onClose={onClose}>
        <ModalContent>
          <form onSubmit={reject}>
            <ModalHeader>Reject Leave</ModalHeader>
            <ModalBody>
              <Textarea label="Rejection Reason" value={data.reason} onValueChange={(v) => setData('reason', v)} errorMessage={errors.reason} />
            </ModalBody>
            <ModalFooter>
              <Button variant="light" onPress={onClose}>Cancel</Button>
              <Button type="submit" color="danger" isLoading={processing}>Confirm Reject</Button>
            </ModalFooter>
          </form>
        </ModalContent>
      </Modal>
    </>
  );
}
ShowLeaveApplication.layout = page => <App title="Leave Application">{page}</App>;
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/Leave/Types/ \
       packages/aero-ui/resources/js/Pages/HRM/Leave/Applications/
git commit -m "feat(aero-ui): leave types CRUD + applications index/create/show"
```

---

## Task 7 — React pages: Balance + Calendar + Settings

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Balance/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Calendar/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Leave/Settings/Index.jsx`

- [ ] `Balance/Index.jsx`:
```jsx
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Select, SelectItem, Input, Pagination } from '@aero/ui';

export default function LeaveBalanceIndex({ balances, employees, year }) {
  const [y, setY] = useState(year);
  const apply = (next) => router.get(route('hrm.leave.balance.index'), { year: next }, { preserveState: true });

  return (
    <>
      <Head title="Leave Balance" />
      <Card>
        <CardHeader className="flex justify-between">
          <h1 className="text-2xl font-semibold">Leave Balance</h1>
          <Input type="number" label="Year" value={String(y)} onValueChange={(v) => { setY(v); apply(v); }} className="max-w-[120px]" />
        </CardHeader>
        <CardBody>
          <Table aria-label="Leave balances">
            <TableHeader>
              <TableColumn>Employee</TableColumn>
              <TableColumn>Leave Type</TableColumn>
              <TableColumn>Entitled</TableColumn>
              <TableColumn>Carried Forward</TableColumn>
              <TableColumn>Used</TableColumn>
              <TableColumn>Remaining</TableColumn>
            </TableHeader>
            <TableBody emptyContent="No balances for this year.">
              {balances.data.map(b => (
                <TableRow key={b.id}>
                  <TableCell>{b.employee ?? '—'}</TableCell>
                  <TableCell>{b.leave_type}</TableCell>
                  <TableCell>{b.entitled}</TableCell>
                  <TableCell>{b.carried_forward}</TableCell>
                  <TableCell>{b.used}</TableCell>
                  <TableCell className="font-semibold">{b.remaining}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <div className="flex justify-end mt-4">
            <Pagination total={balances.last_page} page={balances.current_page}
              onChange={(p) => router.get(route('hrm.leave.balance.index'), { year: y, page: p }, { preserveState: true })} />
          </div>
        </CardBody>
      </Card>
    </>
  );
}
LeaveBalanceIndex.layout = page => <App title="Leave Balance">{page}</App>;
```

- [ ] `Calendar/Index.jsx`:
```jsx
import { Head, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Input } from '@aero/ui';

export default function LeaveCalendar({ events, range }) {
  // Lightweight month-grid view (no external deps). Replace with FullCalendar in a follow-up.
  const days = [];
  const start = new Date(range.from);
  const end   = new Date(range.to);
  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    days.push(new Date(d));
  }
  const eventsByDay = events.reduce((acc, e) => {
    const s = new Date(e.start);
    const en = new Date(e.end);
    for (let d = new Date(s); d < en; d.setDate(d.getDate() + 1)) {
      const key = d.toISOString().slice(0, 10);
      (acc[key] ||= []).push(e);
    }
    return acc;
  }, {});

  const change = (key, value) => router.get(route('hrm.leave.calendar.index'), { ...range, [key]: value }, { preserveState: true });

  return (
    <>
      <Head title="Leave Calendar" />
      <Card>
        <CardHeader className="flex justify-between gap-4">
          <h1 className="text-2xl font-semibold">Team Leave Calendar</h1>
          <div className="flex gap-2">
            <Input type="date" label="From" value={range.from} onValueChange={(v) => change('from', v)} />
            <Input type="date" label="To"   value={range.to}   onValueChange={(v) => change('to', v)} />
          </div>
        </CardHeader>
        <CardBody>
          <div className="grid grid-cols-7 gap-1">
            {days.map(d => {
              const key = d.toISOString().slice(0, 10);
              const dayEvents = eventsByDay[key] ?? [];
              return (
                <div key={key} className="border rounded p-2 min-h-[80px]">
                  <div className="text-xs text-default-500">{key.slice(-2)}</div>
                  {dayEvents.map(e => (
                    <div key={e.id+key} className="text-xs rounded px-1 mt-1" style={{ background: e.color, color: '#fff' }}>{e.title}</div>
                  ))}
                </div>
              );
            })}
          </div>
        </CardBody>
      </Card>
    </>
  );
}
LeaveCalendar.layout = page => <App title="Leave Calendar">{page}</App>;
```

- [ ] `Settings/Index.jsx`:
```jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, CardBody, CardHeader, Select, SelectItem, Input, Switch, Button } from '@aero/ui';

export default function LeaveSettingsIndex({ settings }) {
  const { data, setData, put, processing, errors } = useForm({
    accrual_cycle: settings.accrual_cycle ?? 'yearly',
    allow_negative_balance: !!settings.allow_negative_balance,
    auto_approve_under_days: !!settings.auto_approve_under_days,
    auto_approve_threshold: settings.auto_approve_threshold ?? 0,
    encashment_enabled: !!settings.encashment_enabled,
  });

  const submit = (e) => { e.preventDefault(); put(route('hrm.leave.settings.update')); };

  return (
    <>
      <Head title="Leave Settings" />
      <form onSubmit={submit}>
        <Card>
          <CardHeader><h1 className="text-2xl font-semibold">Leave Settings</h1></CardHeader>
          <CardBody className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Select label="Accrual Cycle" selectedKeys={[data.accrual_cycle]}
                    onSelectionChange={(k) => setData('accrual_cycle', [...k][0])} errorMessage={errors.accrual_cycle}>
              <SelectItem key="yearly">Yearly</SelectItem>
              <SelectItem key="monthly">Monthly</SelectItem>
              <SelectItem key="quarterly">Quarterly</SelectItem>
            </Select>
            <Input type="number" step="0.5" label="Auto-Approve Threshold (days)"
                   value={String(data.auto_approve_threshold)} onValueChange={(v) => setData('auto_approve_threshold', v)}
                   errorMessage={errors.auto_approve_threshold} />
            <Switch isSelected={data.allow_negative_balance} onValueChange={(v) => setData('allow_negative_balance', v)}>Allow negative balance</Switch>
            <Switch isSelected={data.auto_approve_under_days} onValueChange={(v) => setData('auto_approve_under_days', v)}>Auto-approve under threshold</Switch>
            <Switch isSelected={data.encashment_enabled} onValueChange={(v) => setData('encashment_enabled', v)}>Enable leave encashment</Switch>

            <div className="md:col-span-2 flex justify-end">
              <Button type="submit" color="primary" isLoading={processing}>Save Settings</Button>
            </div>
          </CardBody>
        </Card>
      </form>
    </>
  );
}
LeaveSettingsIndex.layout = page => <App title="Leave Settings">{page}</App>;
```

- [ ] Build and verify:
```bash
npm run build --workspace=packages/aero-ui
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/HRM/Leave/Balance/ \
       packages/aero-ui/resources/js/Pages/HRM/Leave/Calendar/ \
       packages/aero-ui/resources/js/Pages/HRM/Leave/Settings/
git commit -m "feat(aero-ui): leave balance/calendar/settings pages"
```

---

## Task 8 — Feature tests for LeaveApplicationController

**Files:**
- Create: `packages/aero-hrm/tests/Feature/Leave/LeaveApplicationControllerTest.php`

- [ ] Write tests (8 methods):
```php
<?php

namespace Aero\HRM\Tests\Feature\Leave;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class LeaveApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

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

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        foreach ([
            'hrm.leave.applications.view',
            'hrm.leave.applications.edit',
            'hrm.leave.applications.approve',
        ] as $a) {
            $user->givePermissionTo($a);
        }
        $this->actingAs($user);
        return $user;
    }

    private function makeContext(): array
    {
        $type     = LeaveType::factory()->create(['days_per_year' => 20, 'requires_approval' => true]);
        $employee = Employee::factory()->create();
        return [$type, $employee];
    }

    public function test_index_lists_applications_with_filters(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        LeaveApplication::factory()->count(3)->create(['leave_type_id' => $type->id, 'employee_id' => $employee->id, 'status' => 'pending']);
        LeaveApplication::factory()->count(2)->create(['leave_type_id' => $type->id, 'employee_id' => $employee->id, 'status' => 'approved']);

        $this->get(route('hrm.leave.applications.index', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Leave/Applications/Index')
                ->has('applications.data', 3));
    }

    public function test_store_creates_pending_application(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();

        $this->post(route('hrm.leave.applications.store'), [
            'employee_id'   => $employee->id,
            'leave_type_id' => $type->id,
            'start_date'    => '2026-06-01',
            'end_date'      => '2026-06-03',
            'reason'        => 'Family event',
        ])->assertRedirect();

        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employee->id,
            'status'      => 'pending',
            'total_days'  => 3.0,
        ]);
    }

    public function test_store_validates_end_after_start(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();

        $this->post(route('hrm.leave.applications.store'), [
            'employee_id'   => $employee->id,
            'leave_type_id' => $type->id,
            'start_date'    => '2026-06-05',
            'end_date'      => '2026-06-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_show_returns_application(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        $app = LeaveApplication::factory()->create(['leave_type_id' => $type->id, 'employee_id' => $employee->id]);

        $this->get(route('hrm.leave.applications.show', $app))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('HRM/Leave/Applications/Show')
                ->where('application.id', $app->id));
    }

    public function test_approve_changes_status_and_updates_balance(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        $app = LeaveApplication::factory()->create([
            'leave_type_id' => $type->id, 'employee_id' => $employee->id,
            'start_date'    => '2026-06-01', 'end_date' => '2026-06-03',
            'total_days'    => 3, 'status' => 'pending',
        ]);

        $this->post(route('hrm.leave.applications.approve', $app))->assertRedirect();

        $this->assertSame('approved', $app->fresh()->status);
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2026, 'used' => 3,
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        $app = LeaveApplication::factory()->create(['leave_type_id' => $type->id, 'employee_id' => $employee->id, 'status' => 'pending']);

        $this->post(route('hrm.leave.applications.reject', $app), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_reject_marks_application_rejected(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        $app = LeaveApplication::factory()->create(['leave_type_id' => $type->id, 'employee_id' => $employee->id, 'status' => 'pending']);

        $this->post(route('hrm.leave.applications.reject', $app), ['reason' => 'Insufficient cover'])
            ->assertRedirect();

        $this->assertSame('rejected', $app->fresh()->status);
        $this->assertSame('Insufficient cover', $app->fresh()->rejection_reason);
    }

    public function test_cancel_releases_balance_when_previously_approved(): void
    {
        $this->actingAsAdmin();
        [$type, $employee] = $this->makeContext();
        $app = LeaveApplication::factory()->create([
            'leave_type_id' => $type->id, 'employee_id' => $employee->id,
            'start_date'    => '2026-06-01', 'end_date' => '2026-06-03',
            'total_days'    => 3, 'status' => 'pending',
        ]);

        $this->post(route('hrm.leave.applications.approve', $app));
        $this->post(route('hrm.leave.applications.cancel', $app))->assertRedirect();

        $this->assertSame('cancelled', $app->fresh()->status);
        $this->assertSame('0.00', (string) LeaveBalance::where('employee_id', $employee->id)->first()->used);
    }
}
```

- [ ] Run tests:
```bash
cd packages/aero-hrm && ../../vendor/bin/phpunit --filter=LeaveApplicationControllerTest --testdox
```

- [ ] Commit:
```bash
git add packages/aero-hrm/tests/Feature/Leave/LeaveApplicationControllerTest.php
git commit -m "test(aero-hrm): LeaveApplicationController feature tests (8 methods)"
```

---

## Acceptance checklist

- [ ] All four leave migrations applied.
- [ ] Service-driven state transitions (create/approve/reject/cancel) emit audit events.
- [ ] Balance row is created/incremented on approval, decremented on cancellation of approved leave.
- [ ] Every route guarded with `hrmac:hrm.leave.*` middleware.
- [ ] React pages live under `Pages/HRM/Leave/` and use `App` layout.
- [ ] Team calendar renders approved leaves within range with type colors.
- [ ] LeaveApplicationControllerTest has 8 passing methods.
