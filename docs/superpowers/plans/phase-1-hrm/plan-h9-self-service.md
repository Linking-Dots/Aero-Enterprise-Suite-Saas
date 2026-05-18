# Plan H-9 — HRM Employee Self-Service Portal

**Status:** Ready for implementation
**Owner:** HRM squad
**Depends on:** `aero-hrm` core, `aero-hrmac`, `aero-ui`, `AuditServiceInterface`, EncryptedField, Payslip PDF renderer (`Aero\HRM\Services\Payroll\PayslipRenderer`)
**Package:** `packages/aero-hrm/`
**Pages root:** `packages/aero-ui/resources/js/Pages/HRM/SelfService/`
**Route prefix:** `/hrm/self-service`
**Route name prefix:** `hrm.self-service.*`

---

## 1. Goal

A scoped portal where any authenticated employee can:

1. See a personal dashboard.
2. View / edit own profile (contact + emergency contact, view-only employment data).
3. Apply for leave, see balance + history, cancel pending requests.
4. View own payslips and download PDFs (access is logged).
5. View enrolled benefits.
6. View own training (enrollments + courses).
7. View own performance (goals + reviews).
8. View own career path + milestones.

The portal **never exposes other employees' data**. The controller layer always resolves `auth()->user()->employee` and scopes queries to that employee's ID — there is no admin override on these routes.

Every state-mutating action calls `AuditServiceInterface`. Payslip access is logged on every view (`PAYSLIP_VIEWED`).

---

## 2. Non-Goals

- HR admin views of "employee X's" payslips (separate routes already exist in HR admin module).
- Document upload from employee side beyond profile attachments (Phase 2).
- Mobile push notifications (Phase 2).
- Public, unauthenticated career-portal endpoints.

---

## 3. Architecture

### 3.1 Routing

- All routes live under `prefix('hrm/self-service')->name('hrm.self-service.')`.
- Middleware stack: `['web', 'auth', 'employee.required']` — `employee.required` ensures `auth()->user()->employee` is non-null. Routes failing this redirect to `hrm.self-service.no-profile`.
- Each route additionally has `hrmac:hrm.employee-self-service.<component>.<action>` middleware.

### 3.2 Controller pattern

Every self-service controller follows the same scaffold:

```php
abstract class SelfServiceController extends Controller
{
    protected function employee(): Employee
    {
        $employee = auth()->user()?->employee;
        abort_unless($employee, 403, 'No employee profile.');

        return $employee;
    }
}
```

Concrete controllers extend `SelfServiceController` and call `$this->employee()` once, then scope every query.

### 3.3 Layout

All pages reuse the standard authenticated layout:

```jsx
import App from '../../App.jsx';
Page.layout = page => <App title="Self-Service">{page}</App>;
```

A shared `<SelfServiceSidebar>` component (in `aero-ui/resources/js/Pages/HRM/SelfService/components/`) renders the section nav.

---

## 4. HRMAC Permission Map

Append under `modules.hrm.submodules.self-service` in `packages/aero-hrm/config/hrmac.php`:

```php
'self-service' => [
    'label' => 'Self Service',
    'components' => [
        'dashboard'   => ['label' => 'Dashboard',   'actions' => ['view']],
        'profile'     => ['label' => 'My Profile',  'actions' => ['view', 'edit']],
        'leaves'      => ['label' => 'My Leaves',   'actions' => ['view', 'edit']],
        'payslips'    => ['label' => 'My Payslips', 'actions' => ['view']],
        'benefits'    => ['label' => 'My Benefits', 'actions' => ['view']],
        'training'    => ['label' => 'My Training', 'actions' => ['view']],
        'performance' => ['label' => 'My Performance','actions' => ['view']],
        'career-path' => ['label' => 'Career Path', 'actions' => ['view']],
    ],
],
```

Every authenticated employee gets the `view` actions by default through a `self-service` role seeded by `aero-hrmac`.

---

## 5. Audit Events

`packages/aero-hrm/src/Audit/SelfServiceAuditEvents.php`:

```php
final class SelfServiceAuditEvents
{
    public const PAYSLIP_VIEWED   = 'self_service.payslip.viewed';
    public const PAYSLIP_DOWNLOADED = 'self_service.payslip.downloaded';
    public const LEAVE_APPLIED    = 'self_service.leave.applied';
    public const LEAVE_CANCELLED  = 'self_service.leave.cancelled';
    public const PROFILE_UPDATED  = 'self_service.profile.updated';
}
```

`PAYSLIP_VIEWED` is recorded via `AuditServiceInterface::logAccess($payslip, $context)` (access log channel — non-mutating action but sensitive).

---

## 6. Middleware

`packages/aero-hrm/src/Http/Middleware/EnsureEmployeeProfile.php`:

```php
<?php

namespace Aero\HRM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmployeeProfile
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->employee) {
            return redirect()->route('hrm.self-service.no-profile');
        }

        return $next($request);
    }
}
```

Aliased as `employee.required` in `AeroHrmServiceProvider::registerMiddleware()`.

---

## 7. Backend — Controllers

### 7.1 `DashboardController`

`packages/aero-hrm/src/Http/Controllers/SelfService/DashboardController.php`:

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\Leave;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Services\Leave\LeaveBalanceService;
use Inertia\Inertia;

class DashboardController extends SelfServiceController
{
    public function __construct(private readonly LeaveBalanceService $balances) {}

    public function index()
    {
        $employee = $this->employee();

        $latestPayslip = Payslip::where('employee_id', $employee->id)
            ->orderByDesc('period_end')
            ->first();

        $pendingLeaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $upcomingTrainings = TrainingEnrollment::where('employee_id', $employee->id)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', now()))
            ->with('session.course')
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('HRM/SelfService/Dashboard', [
            'employee'          => $employee->only(['id', 'first_name', 'last_name', 'employee_number', 'designation', 'department']),
            'leaveBalance'      => $this->balances->summarise($employee),
            'pendingLeavesCount'=> $pendingLeaves,
            'latestPayslip'     => $latestPayslip?->only(['id', 'period_start', 'period_end', 'net_pay']),
            'upcomingTrainings' => $upcomingTrainings,
        ]);
    }
}
```

### 7.2 `ProfileController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Http\Requests\SelfService\UpdateProfileRequest;
use Inertia\Inertia;

class ProfileController extends SelfServiceController
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function show()
    {
        $employee = $this->employee()->load([
            'department', 'designation', 'manager',
            'address', 'emergencyContacts', 'personalDocuments',
        ]);

        return Inertia::render('HRM/SelfService/Profile', [
            'employee' => $employee,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $employee = $this->employee();
        $data     = $request->validated();

        $employee->fill($data['contact'] ?? [])->save();

        if (isset($data['emergency_contact'])) {
            $employee->emergencyContacts()->updateOrCreate(
                ['employee_id' => $employee->id, 'is_primary' => true],
                $data['emergency_contact'],
            );
        }

        $this->audit->record(SelfServiceAuditEvents::PROFILE_UPDATED, $employee, [
            'fields' => array_keys($data['contact'] ?? []),
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
```

### 7.3 `LeaveController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Http\Requests\SelfService\StoreLeaveRequest;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\LeaveSetting;
use Aero\HRM\Services\Leave\LeaveBalanceService;
use Inertia\Inertia;

class LeaveController extends SelfServiceController
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly LeaveBalanceService $balances,
    ) {}

    public function index()
    {
        $employee = $this->employee();

        $leaves = Leave::where('employee_id', $employee->id)
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->paginate(15);

        return Inertia::render('HRM/SelfService/Leaves', [
            'leaves'     => $leaves,
            'balances'   => $this->balances->summarise($employee),
            'leaveTypes' => LeaveSetting::where('is_active', true)->get(['id', 'name', 'days_per_year']),
        ]);
    }

    public function store(StoreLeaveRequest $request)
    {
        $employee = $this->employee();

        $leave = Leave::create([
            ...$request->validated(),
            'employee_id' => $employee->id,
            'status'      => 'pending',
            'applied_at'  => now(),
        ]);

        $this->audit->record(SelfServiceAuditEvents::LEAVE_APPLIED, $leave, [
            'days' => $leave->days_count, 'type_id' => $leave->leave_type_id,
        ]);

        return back()->with('success', 'Leave application submitted.');
    }

    public function cancel(Leave $leave)
    {
        $employee = $this->employee();
        abort_unless($leave->employee_id === $employee->id, 403);
        abort_unless($leave->status === 'pending', 422, 'Only pending leaves can be cancelled.');

        $leave->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->audit->record(SelfServiceAuditEvents::LEAVE_CANCELLED, $leave, []);

        return back()->with('success', 'Leave cancelled.');
    }
}
```

### 7.4 `PayslipController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\Audit\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Services\Payroll\PayslipRenderer;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends SelfServiceController
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly PayslipRenderer $renderer,
    ) {}

    public function index()
    {
        $employee = $this->employee();

        $payslips = Payslip::where('employee_id', $employee->id)
            ->orderByDesc('period_end')
            ->paginate(12);

        return Inertia::render('HRM/SelfService/Payslips', [
            'payslips' => $payslips,
        ]);
    }

    public function show(Payslip $payslip)
    {
        $employee = $this->employee();
        abort_unless($payslip->employee_id === $employee->id, 403);

        $payslip->load(['allowances', 'deductions']);

        $this->audit->logAccess($payslip, [
            'event' => SelfServiceAuditEvents::PAYSLIP_VIEWED,
            'ip'    => request()->ip(),
            'ua'    => request()->userAgent(),
        ]);

        return Inertia::render('HRM/SelfService/Payslips/Show', [
            'payslip' => $payslip,
        ]);
    }

    public function download(Payslip $payslip): StreamedResponse
    {
        $employee = $this->employee();
        abort_unless($payslip->employee_id === $employee->id, 403);

        $this->audit->logAccess($payslip, [
            'event' => SelfServiceAuditEvents::PAYSLIP_DOWNLOADED,
            'ip'    => request()->ip(),
        ]);

        return $this->renderer->stream($payslip);
    }
}
```

### 7.5 `BenefitController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\Benefit;
use Inertia\Inertia;

class BenefitController extends SelfServiceController
{
    public function index()
    {
        $employee = $this->employee();

        $benefits = $employee->benefits()
            ->with('plan')
            ->orderBy('effective_from')
            ->get();

        return Inertia::render('HRM/SelfService/Benefits', [
            'benefits' => $benefits,
        ]);
    }
}
```

### 7.6 `TrainingController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\TrainingEnrollment;
use Inertia\Inertia;

class TrainingController extends SelfServiceController
{
    public function index()
    {
        $employee = $this->employee();

        $enrollments = TrainingEnrollment::where('employee_id', $employee->id)
            ->with(['session.course.category', 'feedback'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('HRM/SelfService/Training', [
            'enrollments' => $enrollments,
        ]);
    }
}
```

### 7.7 `PerformanceController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Models\PerformanceReview;
use Inertia\Inertia;

class PerformanceController extends SelfServiceController
{
    public function index()
    {
        $employee = $this->employee();

        $goals = $employee->goals()
            ->orderByDesc('period_end')
            ->get();

        $reviews = PerformanceReview::where('employee_id', $employee->id)
            ->with(['template'])
            ->orderByDesc('period_end')
            ->get();

        return Inertia::render('HRM/SelfService/Performance', [
            'goals'   => $goals,
            'reviews' => $reviews,
        ]);
    }
}
```

### 7.8 `CareerPathController`

```php
<?php

namespace Aero\HRM\Http\Controllers\SelfService;

use Inertia\Inertia;

class CareerPathController extends SelfServiceController
{
    public function index()
    {
        $employee = $this->employee()->load([
            'careerPath.milestones',
            'careerProgression',
        ]);

        return Inertia::render('HRM/SelfService/CareerPath', [
            'employee'   => $employee->only(['id', 'first_name', 'last_name', 'designation_id']),
            'path'       => $employee->careerPath,
            'progression'=> $employee->careerProgression,
        ]);
    }
}
```

---

## 8. Form Requests

`packages/aero-hrm/src/Http/Requests/SelfService/UpdateProfileRequest.php`:

```php
public function rules(): array
{
    return [
        'contact.personal_email'   => ['nullable', 'email', 'max:200'],
        'contact.personal_phone'   => ['nullable', 'string', 'max:30'],
        'contact.address_line1'    => ['nullable', 'string', 'max:200'],
        'contact.address_line2'    => ['nullable', 'string', 'max:200'],
        'contact.city'             => ['nullable', 'string', 'max:100'],
        'contact.country'          => ['nullable', 'string', 'size:2'],

        'emergency_contact.name'         => ['nullable', 'string', 'max:200'],
        'emergency_contact.relationship' => ['nullable', 'string', 'max:60'],
        'emergency_contact.phone'        => ['nullable', 'string', 'max:30'],
    ];
}
```

`StoreLeaveRequest`:

```php
public function rules(): array
{
    return [
        'leave_type_id' => ['required', 'exists:leave_settings,id'],
        'start_date'    => ['required', 'date', 'after_or_equal:today'],
        'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
        'reason'        => ['required', 'string', 'min:5', 'max:1000'],
        'days_count'    => ['required', 'numeric', 'min:0.5', 'max:90'],
    ];
}
```

---

## 9. Routes

`packages/aero-hrm/routes/web.php` (additive):

```php
Route::middleware(['web', 'auth', 'employee.required'])
    ->prefix('hrm/self-service')
    ->name('hrm.self-service.')
    ->group(function () {
        Route::middleware('hrmac:hrm.employee-self-service.my-dashboard.view')
            ->get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('hrmac:hrm.employee-self-service.my-dashboard.view')
            ->get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::middleware('hrmac:hrm.employee-self-service.my-dashboard.view')
            ->patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('hrmac:hrm.employee-self-service.my-leaves.view')
            ->get('leaves', [LeaveController::class, 'index'])->name('leaves');
        Route::middleware('hrmac:hrm.employee-self-service.my-leaves.apply')->group(function () {
            Route::post('leaves',                  [LeaveController::class, 'store'])->name('leaves.store');
            Route::post('leaves/{leave}/cancel',   [LeaveController::class, 'cancel'])->name('leaves.cancel');
        });

        Route::middleware('hrmac:hrm.employee-self-service.my-payslips.view')->group(function () {
            Route::get('payslips',                     [PayslipController::class, 'index'])->name('payslips');
            Route::get('payslips/{payslip}',           [PayslipController::class, 'show'])->name('payslips.show');
            Route::get('payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');
        });

        Route::middleware('hrmac:hrm.employee-self-service.my-benefits.view')
            ->get('benefits', [BenefitController::class, 'index'])->name('benefits');

        Route::middleware('hrmac:hrm.employee-self-service.my-trainings.view')
            ->get('training', [TrainingController::class, 'index'])->name('training');

        Route::middleware('hrmac:hrm.employee-self-service.my-performance.view')
            ->get('performance', [PerformanceController::class, 'index'])->name('performance');

        Route::middleware('hrmac:hrm.employee-self-service.my-career-path.view')
            ->get('career-path', [CareerPathController::class, 'index'])->name('career-path');

        Route::view('no-profile', 'aero-hrm::self-service.no-profile')->name('no-profile');
    });
```

---

## 10. Frontend Pages

### 10.1 `HRM/SelfService/Dashboard.jsx`

```jsx
import React from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Button, Chip } from '@aero/ui';
import App from '../../App.jsx';
import SelfServiceSidebar from './components/SelfServiceSidebar';

export default function Dashboard({ employee, leaveBalance, pendingLeavesCount, latestPayslip, upcomingTrainings }) {
    return (
        <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-4 p-6">
            <SelfServiceSidebar />
            <div className="space-y-4">
                <Card>
                    <CardHeader className="text-xl">Hello, {employee.first_name}</CardHeader>
                    <CardBody>
                        <div className="text-sm opacity-70">{employee.designation?.name} · {employee.department?.name} · #{employee.employee_number}</div>
                    </CardBody>
                </Card>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader>Leave Balance</CardHeader>
                        <CardBody>
                            {leaveBalance.map(b => (
                                <div key={b.type} className="flex justify-between">
                                    <span>{b.type}</span>
                                    <Chip>{b.remaining}/{b.total}</Chip>
                                </div>
                            ))}
                            <Button as={Link} href={route('hrm.self-service.leaves')} className="mt-3" color="primary" variant="flat">
                                Apply for leave ({pendingLeavesCount} pending)
                            </Button>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader>Latest Payslip</CardHeader>
                        <CardBody>
                            {latestPayslip ? (
                                <>
                                    <div className="text-sm">{latestPayslip.period_start} → {latestPayslip.period_end}</div>
                                    <div className="text-2xl font-semibold">{latestPayslip.net_pay}</div>
                                    <Button as={Link} href={route('hrm.self-service.payslips.show', latestPayslip.id)} className="mt-2" variant="flat">View</Button>
                                </>
                            ) : <div className="opacity-60">No payslips yet.</div>}
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader>Upcoming Training</CardHeader>
                        <CardBody>
                            {upcomingTrainings.length ? upcomingTrainings.map(e => (
                                <div key={e.id} className="flex justify-between text-sm py-1">
                                    <span>{e.session.course.title}</span>
                                    <span className="opacity-60">{e.session.starts_at}</span>
                                </div>
                            )) : <div className="opacity-60">No upcoming sessions.</div>}
                        </CardBody>
                    </Card>
                </div>
            </div>
        </div>
    );
}

Dashboard.layout = page => <App title="My Dashboard">{page}</App>;
```

### 10.2 `HRM/SelfService/Profile.jsx`

Two-column form: editable Contact section + Emergency Contact; read-only Employment block (employee_number, designation, department, manager, start_date). Submits PATCH to `hrm.self-service.profile.update`.

### 10.3 `HRM/SelfService/Leaves.jsx`

```jsx
import React from 'react';
import { useForm, router } from '@inertiajs/react';
import { Card, Button, Input, Select, SelectItem, Textarea, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Modal, ModalContent, ModalHeader, ModalBody, useDisclosure } from '@aero/ui';
import App from '../../App.jsx';
import SelfServiceSidebar from './components/SelfServiceSidebar';

export default function Leaves({ leaves, balances, leaveTypes }) {
    const { isOpen, onOpen, onClose } = useDisclosure();
    const form = useForm({ leave_type_id: '', start_date: '', end_date: '', reason: '', days_count: 1 });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('hrm.self-service.leaves.store'), { onSuccess: () => { form.reset(); onClose(); } });
    };

    return (
        <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-4 p-6">
            <SelfServiceSidebar />
            <div className="space-y-4">
                <Card className="p-4">
                    <div className="flex justify-between items-center">
                        <div className="flex gap-4">
                            {balances.map(b => (
                                <div key={b.type}>
                                    <div className="text-xs opacity-60">{b.type}</div>
                                    <div className="text-lg font-medium">{b.remaining}/{b.total}</div>
                                </div>
                            ))}
                        </div>
                        <Button color="primary" onPress={onOpen}>Apply for leave</Button>
                    </div>
                </Card>

                <Card>
                    <Table aria-label="My leaves">
                        <TableHeader>
                            <TableColumn>TYPE</TableColumn>
                            <TableColumn>FROM</TableColumn>
                            <TableColumn>TO</TableColumn>
                            <TableColumn>DAYS</TableColumn>
                            <TableColumn>STATUS</TableColumn>
                            <TableColumn>ACTION</TableColumn>
                        </TableHeader>
                        <TableBody items={leaves.data} emptyContent="No leave history">
                            {(l) => (
                                <TableRow key={l.id}>
                                    <TableCell>{l.leave_type?.name}</TableCell>
                                    <TableCell>{l.start_date}</TableCell>
                                    <TableCell>{l.end_date}</TableCell>
                                    <TableCell>{l.days_count}</TableCell>
                                    <TableCell><Chip size="sm">{l.status}</Chip></TableCell>
                                    <TableCell>
                                        {l.status === 'pending' && (
                                            <Button size="sm" color="danger" variant="flat"
                                                onPress={() => router.post(route('hrm.self-service.leaves.cancel', l.id))}>
                                                Cancel
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </Card>

                <Modal isOpen={isOpen} onClose={onClose}>
                    <ModalContent>
                        <ModalHeader>Apply for leave</ModalHeader>
                        <ModalBody>
                            <form onSubmit={submit} className="space-y-3">
                                <Select label="Type" onChange={(e) => form.setData('leave_type_id', e.target.value)}>
                                    {leaveTypes.map(t => <SelectItem key={t.id}>{t.name}</SelectItem>)}
                                </Select>
                                <Input type="date" label="From" value={form.data.start_date} onChange={(e) => form.setData('start_date', e.target.value)} />
                                <Input type="date" label="To" value={form.data.end_date} onChange={(e) => form.setData('end_date', e.target.value)} />
                                <Input type="number" min="0.5" step="0.5" label="Days" value={form.data.days_count} onChange={(e) => form.setData('days_count', e.target.value)} />
                                <Textarea label="Reason" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} />
                                <Button type="submit" color="primary" isLoading={form.processing} fullWidth>Submit</Button>
                            </form>
                        </ModalBody>
                    </ModalContent>
                </Modal>
            </div>
        </div>
    );
}

Leaves.layout = page => <App title="My Leaves">{page}</App>;
```

### 10.4 `HRM/SelfService/Payslips.jsx`

Card grid of payslips by period. Each card links to `Payslips/Show.jsx`.

### 10.5 `HRM/SelfService/Payslips/Show.jsx`

Breakdown of earnings + deductions; "Download PDF" button hits `payslips.download`.

### 10.6 `HRM/SelfService/Benefits.jsx`

List of `benefits` with plan name, effective dates, coverage notes.

### 10.7 `HRM/SelfService/Training.jsx`

Enrollment list. Each row exposes "Submit feedback" when `status === 'attended'` and no feedback exists yet; links to `hrm.training.feedback.create`.

### 10.8 `HRM/SelfService/Performance.jsx`

Two sections: **My Goals** (progress bars + period), **My Reviews** (cards with overall score + reviewer comment).

### 10.9 `HRM/SelfService/CareerPath.jsx`

Vertical timeline of milestones with status (`completed | in_progress | upcoming`) plus the employee's current designation pinned.

---

## 11. Inertia Data Contract

- Flat props (no nested `data.data`).
- Lists paginated server-side, `filters` echo when applicable.
- No prop returns another employee's data — verified by tests.

---

## 12. Tests (`tests/Feature/SelfService/SelfServiceTest.php`)

```php
<?php

namespace Aero\HRM\Tests\Feature\SelfService;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\LeaveSetting;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Tests\TestCase;
use Mockery;

class SelfServiceTest extends TestCase
{
    public function test_dashboard_resolves_to_current_employee(): void
    {
        $user = $this->actingAsEmployee();
        Payslip::factory()->create(['employee_id' => $user->employee->id, 'period_end' => now()]);

        $this->get(route('hrm.self-service.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/SelfService/Dashboard')
                                         ->where('employee.id', $user->employee->id));
    }

    public function test_employee_without_profile_is_redirected(): void
    {
        $user = \App\Models\User::factory()->create(['employee_id' => null]);
        $this->actingAs($user);

        $this->get(route('hrm.self-service.dashboard'))
            ->assertRedirect(route('hrm.self-service.no-profile'));
    }

    public function test_employee_can_apply_for_leave(): void
    {
        $user = $this->actingAsEmployee();
        $type = LeaveSetting::factory()->create();

        $this->post(route('hrm.self-service.leaves.store'), [
            'leave_type_id' => $type->id,
            'start_date'    => now()->addDays(2)->toDateString(),
            'end_date'      => now()->addDays(3)->toDateString(),
            'reason'        => 'Family event.',
            'days_count'    => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $user->employee->id,
            'status'      => 'pending',
        ]);
    }

    public function test_employee_can_cancel_pending_leave_only(): void
    {
        $user = $this->actingAsEmployee();
        $pending  = Leave::factory()->create(['employee_id' => $user->employee->id, 'status' => 'pending']);
        $approved = Leave::factory()->create(['employee_id' => $user->employee->id, 'status' => 'approved']);

        $this->post(route('hrm.self-service.leaves.cancel', $pending))->assertRedirect();
        $this->assertSame('cancelled', $pending->fresh()->status);

        $this->post(route('hrm.self-service.leaves.cancel', $approved))->assertStatus(422);
    }

    public function test_employee_cannot_view_other_employees_payslip(): void
    {
        $user  = $this->actingAsEmployee();
        $other = Employee::factory()->create();
        $other = Payslip::factory()->create(['employee_id' => $other->id]);

        $this->get(route('hrm.self-service.payslips.show', $other))->assertForbidden();
    }

    public function test_payslip_view_records_access_log(): void
    {
        $user    = $this->actingAsEmployee();
        $payslip = Payslip::factory()->create(['employee_id' => $user->employee->id]);

        $audit = Mockery::mock(\Aero\Contracts\Audit\AuditServiceInterface::class);
        $audit->shouldReceive('logAccess')
            ->once()
            ->withArgs(fn ($model, $ctx) => $model->id === $payslip->id
                && $ctx['event'] === 'self_service.payslip.viewed');
        $this->app->instance(\Aero\Contracts\Audit\AuditServiceInterface::class, $audit);

        $this->get(route('hrm.self-service.payslips.show', $payslip))->assertOk();
    }
}
```

---

## 13. Tasks (sequenced, minimum 7)

1. **HRMAC permission map + role seeder** — extend `hrmac.php` for `self-service.*`; seed default `self-service` role.
2. **Middleware** — implement `EnsureEmployeeProfile`, register alias `employee.required`, expose `no-profile` blade.
3. **Abstract controller** — `SelfServiceController` base with `employee()` helper.
4. **Concrete controllers** — eight controllers in `src/Http/Controllers/SelfService/`.
5. **Form Requests** — `UpdateProfileRequest`, `StoreLeaveRequest`.
6. **Routes** — `hrm.self-service.*` block under prefix and HRMAC middleware.
7. **Audit events** — `SelfServiceAuditEvents`; ensure `logAccess` is called on every payslip view/download.
8. **Frontend pages** — nine pages from section 10 plus `<SelfServiceSidebar>` shared nav.
9. **PHPUnit tests** — six methods in `SelfServiceTest`; ensure dashboard resolution and payslip access logging are covered.

---

## 14. Acceptance Criteria

- Routes return 403 / redirect when the user has no employee profile.
- Employees cannot read, mutate, or download another employee's data; verified by feature tests.
- Every state-mutating endpoint records an audit event with the corresponding `SelfServiceAuditEvents` constant.
- Every payslip `show` and `download` call invokes `AuditServiceInterface::logAccess`.
- All payslip routes scope `Payslip::where('employee_id', $employee->id)` before render.
- All PII fields surfaced to the frontend are decrypted exactly once at controller boundary and never echoed back in API errors.
- All 6 PHPUnit tests pass under sqlite `:memory:` with Orchestra Testbench.
