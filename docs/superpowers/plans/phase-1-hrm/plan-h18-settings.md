# Plan H-18 — HRM Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver all HRM configuration surfaces — general settings (working hours, fiscal year, probation defaults), leave settings (working days, accrual engine), attendance rules (grace period, overtime thresholds, shift patterns), onboarding/offboarding task templates, and a public holidays calendar — so every other HRM module reads consistent config instead of hardcoded defaults.

**Architecture:** All settings use a `HrmSetting` Eloquent model (or package-specific settings rows in `system_settings` keyed by group) rather than `.env`. Settings are read via a `HrmSettingService` singleton bound in `AeroHrmServiceProvider`. Each settings page is a single Inertia form — GET to load, POST/PUT to save. No separate create/edit pages. HRMAC `hrm.settings.*` guards every route. AuditService logs every save.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## File Map

**Create (controllers):**
- `packages/aero-hrm/src/Http/Controllers/Settings/HrmGeneralSettingController.php`
- `packages/aero-hrm/src/Http/Controllers/Settings/HrmLeaveSettingController.php`
- `packages/aero-hrm/src/Http/Controllers/Settings/HrmAttendanceSettingController.php`
- `packages/aero-hrm/src/Http/Controllers/Settings/TaskTemplateController.php`
- `packages/aero-hrm/src/Http/Controllers/Settings/PublicHolidayController.php`

**Create (pages):**
- `packages/aero-ui/resources/js/Pages/HRM/Settings/General.jsx`
- `packages/aero-ui/resources/js/Pages/HRM/Settings/Leave.jsx`
- `packages/aero-ui/resources/js/Pages/HRM/Settings/Attendance.jsx`
- `packages/aero-ui/resources/js/Pages/HRM/Settings/TaskTemplates/Index.jsx`
- `packages/aero-ui/resources/js/Pages/HRM/Settings/TaskTemplates/Create.jsx`
- `packages/aero-ui/resources/js/Pages/HRM/Settings/Holidays/Index.jsx`

**Modify (routes):**
- `packages/aero-hrm/routes/tenant.php` — add settings route group

**Create (tests):**
- `packages/aero-hrm/tests/Feature/Settings/HrmSettingsTest.php`

---

## Task 1 — Routes: settings group

**Files:**
- Modify: `packages/aero-hrm/routes/tenant.php`

- [ ] Add settings route group inside the existing `hrm` prefix group:

```php
Route::prefix('settings')->name('settings.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/general',    [HrmGeneralSettingController::class,    'show'])->name('general')
        ->middleware('hrmac:hrm.settings.general-settings.view');
    Route::put('/general',    [HrmGeneralSettingController::class,    'update'])->name('general.update')
        ->middleware('hrmac:hrm.settings.general-settings.edit');

    Route::get('/leave',      [HrmLeaveSettingController::class,      'show'])->name('leave')
        ->middleware('hrmac:hrm.settings.leave-settings.view');
    Route::put('/leave',      [HrmLeaveSettingController::class,      'update'])->name('leave.update')
        ->middleware('hrmac:hrm.settings.leave-settings.edit');

    Route::get('/attendance', [HrmAttendanceSettingController::class, 'show'])->name('attendance')
        ->middleware('hrmac:hrm.settings.attendance-settings.view');
    Route::put('/attendance', [HrmAttendanceSettingController::class, 'update'])->name('attendance.update')
        ->middleware('hrmac:hrm.settings.attendance-settings.edit');

    Route::apiResource('task-templates', TaskTemplateController::class)
        ->middleware(['hrmac:hrm.settings.task-templates.view', 'hrmac:hrm.settings.task-templates.edit']);

    Route::get('/holidays',   [PublicHolidayController::class, 'index'])->name('holidays')
        ->middleware('hrmac:hrm.settings.holidays.view');
    Route::post('/holidays',  [PublicHolidayController::class, 'store'])->name('holidays.store')
        ->middleware('hrmac:hrm.settings.holidays.edit');
    Route::delete('/holidays/{holiday}', [PublicHolidayController::class, 'destroy'])->name('holidays.destroy')
        ->middleware('hrmac:hrm.settings.holidays.edit');
});
```

- [ ] Add `use` imports at top of `tenant.php`:
```php
use Aero\HRM\Http\Controllers\Settings\HrmGeneralSettingController;
use Aero\HRM\Http\Controllers\Settings\HrmLeaveSettingController;
use Aero\HRM\Http\Controllers\Settings\HrmAttendanceSettingController;
use Aero\HRM\Http\Controllers\Settings\TaskTemplateController;
use Aero\HRM\Http\Controllers\Settings\PublicHolidayController;
```

- [ ] Run: `php artisan route:list --path=hrm/settings`  
  Expected: 11 routes listed with correct names and middleware.

- [ ] Commit:
```bash
git add packages/aero-hrm/routes/tenant.php
git commit -m "feat(hrm): add settings route group with HRMAC guards"
```

---

## Task 2 — General Settings controller + page

**Files:**
- Create: `packages/aero-hrm/src/Http/Controllers/Settings/HrmGeneralSettingController.php`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Settings/General.jsx`

- [ ] Create the controller:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmGeneralSettingController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $settings = $this->getSettings();

        return Inertia::render('HRM/Settings/General', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_start_time'        => 'required|date_format:H:i',
            'work_end_time'          => 'required|date_format:H:i',
            'work_days_per_week'     => 'required|integer|min:1|max:7',
            'fiscal_year_start'      => 'required|date_format:m-d',
            'probation_months'       => 'required|integer|min:0|max:24',
            'notice_period_days'     => 'required|integer|min:0|max:365',
            'employee_id_prefix'     => 'nullable|string|max:10',
            'employee_id_digits'     => 'required|integer|min:3|max:8',
            'currency'               => 'required|string|size:3',
            'timezone'               => 'required|string|max:64',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => "hrm.general.{$key}"],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'group'    => 'hrm.general',
            'changed'  => array_keys($validated),
        ]);

        return back()->with('success', 'General settings saved.');
    }

    private function getSettings(): array
    {
        $rows = DB::table('system_settings')
            ->where('key', 'like', 'hrm.general.%')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($v, $k) => [str_replace('hrm.general.', '', $k) => $v])
            ->toArray();

        return array_merge([
            'work_start_time'    => '09:00',
            'work_end_time'      => '18:00',
            'work_days_per_week' => 5,
            'fiscal_year_start'  => '01-01',
            'probation_months'   => 3,
            'notice_period_days' => 30,
            'employee_id_prefix' => 'EMP',
            'employee_id_digits' => 4,
            'currency'           => 'USD',
            'timezone'           => 'UTC',
        ], $rows);
    }
}
```

- [ ] Create the React page:

```jsx
import { useForm } from '@inertiajs/react';
import App from '../../App.jsx';
import {
  IndexPageLayout, Card, CardContent, VStack, HStack,
  Field, Input, Select, Button, Text
} from '@aero/ui';

export default function HrmGeneralSettings({ settings }) {
  const { data, setData, put, processing, errors } = useForm(settings);

  function submit(e) {
    e.preventDefault();
    put(route('hrm.settings.general.update'));
  }

  return (
    <form onSubmit={submit}>
      <IndexPageLayout
        title="General Settings"
        breadcrumbs={[{ label: 'HRM' }, { label: 'Settings' }, { label: 'General' }]}
        actions={
          <Button type="submit" intent="primary" loading={processing}>Save Changes</Button>
        }
      >
        <Card>
          <CardContent>
            <VStack gap={6}>
              <Text weight="semibold" size="lg">Working Hours</Text>
              <HStack gap={4}>
                <Field label="Work Start Time" error={errors.work_start_time}>
                  <Input type="time" value={data.work_start_time}
                    onChange={e => setData('work_start_time', e.target.value)} />
                </Field>
                <Field label="Work End Time" error={errors.work_end_time}>
                  <Input type="time" value={data.work_end_time}
                    onChange={e => setData('work_end_time', e.target.value)} />
                </Field>
                <Field label="Work Days / Week" error={errors.work_days_per_week}>
                  <Select value={data.work_days_per_week}
                    onChange={e => setData('work_days_per_week', parseInt(e.target.value))}>
                    {[4,5,6,7].map(n => <option key={n} value={n}>{n} days</option>)}
                  </Select>
                </Field>
              </HStack>

              <Text weight="semibold" size="lg">Employee IDs</Text>
              <HStack gap={4}>
                <Field label="ID Prefix" error={errors.employee_id_prefix}>
                  <Input value={data.employee_id_prefix ?? ''}
                    onChange={e => setData('employee_id_prefix', e.target.value)}
                    placeholder="EMP" />
                </Field>
                <Field label="ID Digits" error={errors.employee_id_digits}>
                  <Select value={data.employee_id_digits}
                    onChange={e => setData('employee_id_digits', parseInt(e.target.value))}>
                    {[3,4,5,6].map(n => <option key={n} value={n}>{n} digits</option>)}
                  </Select>
                </Field>
              </HStack>

              <HStack gap={4}>
                <Field label="Fiscal Year Start (MM-DD)" error={errors.fiscal_year_start}>
                  <Input value={data.fiscal_year_start}
                    onChange={e => setData('fiscal_year_start', e.target.value)}
                    placeholder="01-01" />
                </Field>
                <Field label="Probation Period (months)" error={errors.probation_months}>
                  <Input type="number" min={0} max={24} value={data.probation_months}
                    onChange={e => setData('probation_months', parseInt(e.target.value))} />
                </Field>
                <Field label="Notice Period (days)" error={errors.notice_period_days}>
                  <Input type="number" min={0} value={data.notice_period_days}
                    onChange={e => setData('notice_period_days', parseInt(e.target.value))} />
                </Field>
              </HStack>
            </VStack>
          </CardContent>
        </Card>
      </IndexPageLayout>
    </form>
  );
}

HrmGeneralSettings.layout = page => <App title="General Settings">{page}</App>;
```

- [ ] Run: `php artisan test packages/aero-hrm/tests/Feature/Settings/HrmSettingsTest.php --filter=general`  
  Expected: passes.

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Settings/HrmGeneralSettingController.php
git add packages/aero-ui/resources/js/Pages/HRM/Settings/General.jsx
git commit -m "feat(hrm): HRM general settings controller + page"
```

---

## Task 3 — Leave Settings controller + page

**Files:**
- Create: `packages/aero-hrm/src/Http/Controllers/Settings/HrmLeaveSettingController.php`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Settings/Leave.jsx`

- [ ] Create the controller:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmLeaveSettingController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $settings = $this->getSettings();

        return Inertia::render('HRM/Settings/Leave', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'working_days'              => 'required|array|min:1',
            'working_days.*'            => 'in:mon,tue,wed,thu,fri,sat,sun',
            'accrual_enabled'           => 'boolean',
            'accrual_frequency'         => 'required_if:accrual_enabled,true|in:monthly,quarterly,annually',
            'carry_forward_enabled'     => 'boolean',
            'carry_forward_max_days'    => 'nullable|integer|min:0|max:365',
            'encashment_enabled'        => 'boolean',
            'encashment_max_days'       => 'nullable|integer|min:0|max:365',
            'leave_approval_levels'     => 'required|integer|min:1|max:3',
            'min_notice_days'           => 'required|integer|min:0|max:90',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => "hrm.leave.{$key}"],
                ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
            );
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'group'   => 'hrm.leave',
            'changed' => array_keys($validated),
        ]);

        return back()->with('success', 'Leave settings saved.');
    }

    private function getSettings(): array
    {
        $rows = DB::table('system_settings')
            ->where('key', 'like', 'hrm.leave.%')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($v, $k) => [str_replace('hrm.leave.', '', $k) => $v])
            ->toArray();

        $defaults = [
            'working_days'           => ['mon','tue','wed','thu','fri'],
            'accrual_enabled'        => true,
            'accrual_frequency'      => 'monthly',
            'carry_forward_enabled'  => true,
            'carry_forward_max_days' => 15,
            'encashment_enabled'     => false,
            'encashment_max_days'    => 0,
            'leave_approval_levels'  => 1,
            'min_notice_days'        => 1,
        ];

        return array_merge($defaults, $rows);
    }
}
```

- [ ] Create the React page:

```jsx
import { useForm } from '@inertiajs/react';
import App from '../../App.jsx';
import {
  IndexPageLayout, Card, CardContent, VStack, HStack,
  Field, Input, Select, Toggle, Button, Text, Badge
} from '@aero/ui';

const DAYS = [
  { value: 'mon', label: 'Mon' }, { value: 'tue', label: 'Tue' },
  { value: 'wed', label: 'Wed' }, { value: 'thu', label: 'Thu' },
  { value: 'fri', label: 'Fri' }, { value: 'sat', label: 'Sat' },
  { value: 'sun', label: 'Sun' },
];

export default function HrmLeaveSettings({ settings }) {
  const { data, setData, put, processing, errors } = useForm({
    ...settings,
    working_days: Array.isArray(settings.working_days)
      ? settings.working_days
      : JSON.parse(settings.working_days ?? '["mon","tue","wed","thu","fri"]'),
  });

  function toggleDay(day) {
    const days = data.working_days.includes(day)
      ? data.working_days.filter(d => d !== day)
      : [...data.working_days, day];
    setData('working_days', days);
  }

  function submit(e) {
    e.preventDefault();
    put(route('hrm.settings.leave.update'));
  }

  return (
    <form onSubmit={submit}>
      <IndexPageLayout
        title="Leave Settings"
        breadcrumbs={[{ label: 'HRM' }, { label: 'Settings' }, { label: 'Leave' }]}
        actions={<Button type="submit" intent="primary" loading={processing}>Save Changes</Button>}
      >
        <VStack gap={4}>
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Working Days</Text>
                <HStack gap={2}>
                  {DAYS.map(d => (
                    <button key={d.value} type="button"
                      onClick={() => toggleDay(d.value)}
                      style={{
                        padding: '6px 12px', borderRadius: '6px', cursor: 'pointer',
                        background: data.working_days.includes(d.value) ? 'var(--aeos-primary)' : 'var(--aeos-surface-2)',
                        color: data.working_days.includes(d.value) ? 'var(--aeos-on-primary)' : 'inherit',
                        border: '1px solid var(--aeos-border)',
                      }}
                    >{d.label}</button>
                  ))}
                </HStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <HStack justify="space-between">
                  <Text weight="semibold">Leave Accrual</Text>
                  <Toggle
                    checked={!!data.accrual_enabled}
                    onChange={v => setData('accrual_enabled', v)}
                    label="Enable accrual"
                  />
                </HStack>
                {data.accrual_enabled && (
                  <Field label="Accrual Frequency" error={errors.accrual_frequency}>
                    <Select value={data.accrual_frequency}
                      onChange={e => setData('accrual_frequency', e.target.value)}>
                      <option value="monthly">Monthly</option>
                      <option value="quarterly">Quarterly</option>
                      <option value="annually">Annually</option>
                    </Select>
                  </Field>
                )}
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <HStack gap={4}>
                  <Field label="Approval Levels" error={errors.leave_approval_levels}>
                    <Select value={data.leave_approval_levels}
                      onChange={e => setData('leave_approval_levels', parseInt(e.target.value))}>
                      <option value={1}>1 level</option>
                      <option value={2}>2 levels</option>
                      <option value={3}>3 levels</option>
                    </Select>
                  </Field>
                  <Field label="Min. Notice Days" error={errors.min_notice_days}>
                    <Input type="number" min={0} value={data.min_notice_days}
                      onChange={e => setData('min_notice_days', parseInt(e.target.value))} />
                  </Field>
                </HStack>
                <HStack gap={4}>
                  <Field label="Max Carry-Forward Days" error={errors.carry_forward_max_days}>
                    <Input type="number" min={0} value={data.carry_forward_max_days ?? 0}
                      onChange={e => setData('carry_forward_max_days', parseInt(e.target.value))} />
                  </Field>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        </VStack>
      </IndexPageLayout>
    </form>
  );
}

HrmLeaveSettings.layout = page => <App title="Leave Settings">{page}</App>;
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Settings/HrmLeaveSettingController.php
git add packages/aero-ui/resources/js/Pages/HRM/Settings/Leave.jsx
git commit -m "feat(hrm): leave settings controller + page"
```

---

## Task 4 — Attendance Settings + Task Templates + Holidays

**Files:**
- Create: `packages/aero-hrm/src/Http/Controllers/Settings/HrmAttendanceSettingController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Settings/TaskTemplateController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Settings/PublicHolidayController.php`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Settings/Attendance.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Settings/TaskTemplates/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/HRM/Settings/Holidays/Index.jsx`

- [ ] Create `HrmAttendanceSettingController`:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmAttendanceSettingController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $rows = DB::table('system_settings')
            ->where('key', 'like', 'hrm.attendance.%')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($v, $k) => [str_replace('hrm.attendance.', '', $k) => $v])
            ->toArray();

        $settings = array_merge([
            'late_grace_minutes'          => 15,
            'early_departure_grace'       => 10,
            'overtime_threshold_minutes'  => 30,
            'overtime_rate_multiplier'    => 1.5,
            'auto_clockout_hours'         => 10,
            'require_location'            => false,
            'require_selfie'              => false,
        ], $rows);

        return Inertia::render('HRM/Settings/Attendance', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'late_grace_minutes'         => 'required|integer|min:0|max:120',
            'early_departure_grace'      => 'required|integer|min:0|max:60',
            'overtime_threshold_minutes' => 'required|integer|min:0|max:120',
            'overtime_rate_multiplier'   => 'required|numeric|min:1|max:3',
            'auto_clockout_hours'        => 'required|integer|min:6|max:24',
            'require_location'           => 'boolean',
            'require_selfie'             => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => "hrm.attendance.{$key}"],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'group' => 'hrm.attendance',
        ]);

        return back()->with('success', 'Attendance settings saved.');
    }
}
```

- [ ] Create `TaskTemplateController`:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskTemplateController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $templates = TaskTemplate::orderBy('type')->orderBy('name')->paginate(25);

        return Inertia::render('HRM/Settings/TaskTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:onboarding,offboarding',
            'description' => 'nullable|string|max:1000',
            'tasks'       => 'required|array|min:1',
            'tasks.*.title'    => 'required|string|max:255',
            'tasks.*.due_days' => 'required|integer|min:0|max:90',
            'tasks.*.assignee_type' => 'required|in:employee,manager,hr',
        ]);

        $template = TaskTemplate::create([
            'name'        => $validated['name'],
            'type'        => $validated['type'],
            'description' => $validated['description'] ?? null,
            'tasks'       => $validated['tasks'],
        ]);

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'action'   => 'task_template_created',
            'template' => $template->id,
            'type'     => $template->type,
        ]);

        return back()->with('success', 'Task template created.');
    }

    public function update(Request $request, TaskTemplate $taskTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tasks'       => 'required|array|min:1',
            'tasks.*.title'    => 'required|string|max:255',
            'tasks.*.due_days' => 'required|integer|min:0|max:90',
            'tasks.*.assignee_type' => 'required|in:employee,manager,hr',
        ]);

        $taskTemplate->update($validated);

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'action'   => 'task_template_updated',
            'template' => $taskTemplate->id,
        ]);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(TaskTemplate $taskTemplate): RedirectResponse
    {
        $taskTemplate->delete();

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'action'   => 'task_template_deleted',
            'template' => $taskTemplate->id,
        ]);

        return back()->with('success', 'Template deleted.');
    }
}
```

- [ ] Create `PublicHolidayController`:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\PublicHoliday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicHolidayController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $year     = (int) $request->get('year', now()->year);
        $holidays = PublicHoliday::whereYear('date', $year)
            ->orderBy('date')
            ->get(['id', 'name', 'date', 'is_optional']);

        return Inertia::render('HRM/Settings/Holidays/Index', [
            'holidays'     => $holidays,
            'currentYear'  => $year,
            'availableYears' => range(now()->year - 1, now()->year + 2),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'date'        => 'required|date',
            'is_optional' => 'boolean',
        ]);

        $holiday = PublicHoliday::create($validated);

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'action'  => 'holiday_added',
            'holiday' => $holiday->id,
            'date'    => $holiday->date,
        ]);

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(PublicHoliday $publicHoliday): RedirectResponse
    {
        $publicHoliday->delete();

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, [
            'action'  => 'holiday_deleted',
            'holiday' => $publicHoliday->id,
        ]);

        return back()->with('success', 'Holiday removed.');
    }
}
```

- [ ] Create `HRM/Settings/Attendance.jsx`:

```jsx
import { useForm } from '@inertiajs/react';
import App from '../../App.jsx';
import { IndexPageLayout, Card, CardContent, VStack, HStack, Field, Input, Toggle, Button } from '@aero/ui';

export default function HrmAttendanceSettings({ settings }) {
  const { data, setData, put, processing, errors } = useForm(settings);

  return (
    <form onSubmit={e => { e.preventDefault(); put(route('hrm.settings.attendance.update')); }}>
      <IndexPageLayout
        title="Attendance Settings"
        breadcrumbs={[{ label: 'HRM' }, { label: 'Settings' }, { label: 'Attendance' }]}
        actions={<Button type="submit" intent="primary" loading={processing}>Save</Button>}
      >
        <Card>
          <CardContent>
            <VStack gap={4}>
              <HStack gap={4}>
                <Field label="Late Grace (min)" error={errors.late_grace_minutes}>
                  <Input type="number" min={0} value={data.late_grace_minutes}
                    onChange={e => setData('late_grace_minutes', +e.target.value)} />
                </Field>
                <Field label="Early Departure Grace (min)" error={errors.early_departure_grace}>
                  <Input type="number" min={0} value={data.early_departure_grace}
                    onChange={e => setData('early_departure_grace', +e.target.value)} />
                </Field>
              </HStack>
              <HStack gap={4}>
                <Field label="OT Threshold (min)" error={errors.overtime_threshold_minutes}>
                  <Input type="number" min={0} value={data.overtime_threshold_minutes}
                    onChange={e => setData('overtime_threshold_minutes', +e.target.value)} />
                </Field>
                <Field label="OT Rate Multiplier" error={errors.overtime_rate_multiplier}>
                  <Input type="number" step="0.1" min={1} max={3} value={data.overtime_rate_multiplier}
                    onChange={e => setData('overtime_rate_multiplier', parseFloat(e.target.value))} />
                </Field>
                <Field label="Auto Clock-out After (hrs)" error={errors.auto_clockout_hours}>
                  <Input type="number" min={6} max={24} value={data.auto_clockout_hours}
                    onChange={e => setData('auto_clockout_hours', +e.target.value)} />
                </Field>
              </HStack>
              <HStack gap={6}>
                <Toggle checked={!!data.require_location} onChange={v => setData('require_location', v)}
                  label="Require GPS Location" />
                <Toggle checked={!!data.require_selfie} onChange={v => setData('require_selfie', v)}
                  label="Require Selfie Photo" />
              </HStack>
            </VStack>
          </CardContent>
        </Card>
      </IndexPageLayout>
    </form>
  );
}

HrmAttendanceSettings.layout = page => <App title="Attendance Settings">{page}</App>;
```

- [ ] Create `HRM/Settings/TaskTemplates/Index.jsx`:

```jsx
import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { IndexPageLayout, Card, CardContent, VStack, HStack, Button, Text, Badge, Modal } from '@aero/ui';

export default function TaskTemplatesIndex({ templates }) {
  const [creating, setCreating] = useState(false);
  const { data, setData, post, processing, reset } = useForm({
    name: '', type: 'onboarding', description: '',
    tasks: [{ title: '', due_days: 1, assignee_type: 'hr' }],
  });

  function addTask() {
    setData('tasks', [...data.tasks, { title: '', due_days: 1, assignee_type: 'hr' }]);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.settings.task-templates.store'), {
      onSuccess: () => { setCreating(false); reset(); },
    });
  }

  return (
    <IndexPageLayout
      title="Task Templates"
      breadcrumbs={[{ label: 'HRM' }, { label: 'Settings' }, { label: 'Task Templates' }]}
      actions={<Button intent="primary" onClick={() => setCreating(true)}>New Template</Button>}
    >
      <VStack gap={3}>
        {templates.data.map(t => (
          <Card key={t.id}>
            <CardContent>
              <HStack justify="space-between" align="center">
                <VStack gap={1}>
                  <Text weight="semibold">{t.name}</Text>
                  <Text tone="secondary" size="sm">{t.description}</Text>
                </VStack>
                <HStack gap={2}>
                  <Badge intent={t.type === 'onboarding' ? 'success' : 'warning'}>{t.type}</Badge>
                  <Button size="sm" intent="ghost"
                    onClick={() => router.delete(route('hrm.settings.task-templates.destroy', t.id))}>
                    Delete
                  </Button>
                </HStack>
              </HStack>
            </CardContent>
          </Card>
        ))}
      </VStack>

      <Modal open={creating} onClose={() => setCreating(false)} title="New Task Template">
        <form onSubmit={submit}>
          <VStack gap={3} style={{ padding: '1rem' }}>
            <input placeholder="Template name" value={data.name}
              onChange={e => setData('name', e.target.value)}
              style={{ width: '100%', padding: '8px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }} />
            <select value={data.type} onChange={e => setData('type', e.target.value)}
              style={{ width: '100%', padding: '8px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }}>
              <option value="onboarding">Onboarding</option>
              <option value="offboarding">Offboarding</option>
            </select>
            {data.tasks.map((task, i) => (
              <HStack key={i} gap={2}>
                <input placeholder="Task title" value={task.title}
                  onChange={e => { const t = [...data.tasks]; t[i].title = e.target.value; setData('tasks', t); }}
                  style={{ flex: 1, padding: '6px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }} />
                <input type="number" min={0} value={task.due_days} placeholder="Days"
                  onChange={e => { const t = [...data.tasks]; t[i].due_days = +e.target.value; setData('tasks', t); }}
                  style={{ width: '70px', padding: '6px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }} />
              </HStack>
            ))}
            <Button type="button" intent="soft" size="sm" onClick={addTask}>+ Add Task</Button>
            <Button type="submit" intent="primary" loading={processing}>Create Template</Button>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

TaskTemplatesIndex.layout = page => <App title="Task Templates">{page}</App>;
```

- [ ] Create `HRM/Settings/Holidays/Index.jsx`:

```jsx
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { IndexPageLayout, Card, CardContent, VStack, HStack, Button, Text, Badge } from '@aero/ui';

export default function HolidaysIndex({ holidays, currentYear, availableYears }) {
  const [adding, setAdding] = useState(false);
  const { data, setData, post, processing, reset } = useForm({
    name: '', date: '', is_optional: false,
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.settings.holidays.store'), {
      onSuccess: () => { setAdding(false); reset(); },
    });
  }

  return (
    <IndexPageLayout
      title={`Public Holidays ${currentYear}`}
      breadcrumbs={[{ label: 'HRM' }, { label: 'Settings' }, { label: 'Holidays' }]}
      actions={
        <HStack gap={2}>
          <select value={currentYear}
            onChange={e => router.get(route('hrm.settings.holidays'), { year: e.target.value })}
            style={{ padding: '6px 12px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }}>
            {availableYears.map(y => <option key={y} value={y}>{y}</option>)}
          </select>
          <Button intent="primary" onClick={() => setAdding(true)}>Add Holiday</Button>
        </HStack>
      }
    >
      <VStack gap={2}>
        {holidays.map(h => (
          <Card key={h.id}>
            <CardContent>
              <HStack justify="space-between" align="center">
                <HStack gap={3} align="center">
                  <Text weight="semibold">{h.name}</Text>
                  <Text tone="secondary">{h.date}</Text>
                  {h.is_optional && <Badge intent="neutral">Optional</Badge>}
                </HStack>
                <Button size="sm" intent="ghost"
                  onClick={() => router.delete(route('hrm.settings.holidays.destroy', h.id))}>
                  Remove
                </Button>
              </HStack>
            </CardContent>
          </Card>
        ))}
      </VStack>

      {adding && (
        <Card style={{ marginTop: '1rem' }}>
          <CardContent>
            <form onSubmit={submit}>
              <HStack gap={3} align="end">
                <div>
                  <Text size="sm">Name</Text>
                  <input value={data.name} onChange={e => setData('name', e.target.value)}
                    placeholder="Holiday name"
                    style={{ display: 'block', padding: '6px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }} />
                </div>
                <div>
                  <Text size="sm">Date</Text>
                  <input type="date" value={data.date} onChange={e => setData('date', e.target.value)}
                    style={{ display: 'block', padding: '6px', border: '1px solid var(--aeos-border)', borderRadius: '6px' }} />
                </div>
                <Button type="submit" intent="primary" loading={processing}>Add</Button>
                <Button type="button" intent="ghost" onClick={() => setAdding(false)}>Cancel</Button>
              </HStack>
            </form>
          </CardContent>
        </Card>
      )}
    </IndexPageLayout>
  );
}

HolidaysIndex.layout = page => <App title="Public Holidays">{page}</App>;
```

- [ ] Commit:
```bash
git add packages/aero-hrm/src/Http/Controllers/Settings/
git add packages/aero-ui/resources/js/Pages/HRM/Settings/
git commit -m "feat(hrm): attendance settings, task templates, holidays controllers + pages"
```

---

## Task 5 — PHPUnit tests

**Files:**
- Create: `packages/aero-hrm/tests/Feature/Settings/HrmSettingsTest.php`

- [ ] Write the test:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Settings;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class HrmSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\HRM\AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function test_general_settings_requires_authentication(): void
    {
        $this->get(route('hrm.settings.general'))
            ->assertRedirect(route('login'));
    }

    public function test_general_settings_saves_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('hrm.settings.general.update'), [
                'work_start_time'        => '08:00',
                'work_end_time'          => '17:00',
                'work_days_per_week'     => 5,
                'fiscal_year_start'      => '01-01',
                'probation_months'       => 3,
                'notice_period_days'     => 30,
                'employee_id_prefix'     => 'EMP',
                'employee_id_digits'     => 4,
                'currency'               => 'USD',
                'timezone'               => 'Asia/Dhaka',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_settings', [
            'key'   => 'hrm.general.work_start_time',
            'value' => '08:00',
        ]);
    }

    public function test_leave_settings_validates_working_days(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('hrm.settings.leave.update'), [
                'working_days'           => ['invalid_day'],
                'accrual_enabled'        => true,
                'accrual_frequency'      => 'monthly',
                'carry_forward_enabled'  => true,
                'carry_forward_max_days' => 15,
                'encashment_enabled'     => false,
                'encashment_max_days'    => 0,
                'leave_approval_levels'  => 1,
                'min_notice_days'        => 1,
            ])
            ->assertSessionHasErrors(['working_days.0']);
    }

    public function test_holiday_can_be_created_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('hrm.settings.holidays.store'), [
                'name'        => 'New Year',
                'date'        => now()->startOfYear()->format('Y-m-d'),
                'is_optional' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('public_holidays', ['name' => 'New Year']);
    }

    public function test_task_template_store_validates_tasks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('hrm.settings.task-templates.store'), [
                'name'  => 'Onboarding',
                'type'  => 'onboarding',
                'tasks' => [],
            ])
            ->assertSessionHasErrors(['tasks']);
    }
}
```

- [ ] Run: `php artisan test packages/aero-hrm/tests/Feature/Settings/HrmSettingsTest.php -v`  
  Expected: 5 tests passing.

- [ ] Commit:
```bash
git add packages/aero-hrm/tests/Feature/Settings/HrmSettingsTest.php
git commit -m "test(hrm): HRM settings PHPUnit tests (general, leave, holidays, task templates)"
```

---

## Task 6 — Playwright smoke test

**Files:**
- Create: `tests/e2e/hrm/settings.spec.js`

- [ ] Write the smoke test:

```js
import { test, expect } from '@playwright/test';

test.describe('HRM Settings', () => {
  test('general settings page loads', async ({ page }) => {
    await page.goto('http://testco.aeos365.test/hrm/settings/general');
    await expect(page).not.toHaveURL(/error|500/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('holidays page renders year selector', async ({ page }) => {
    await page.goto('http://testco.aeos365.test/hrm/settings/holidays');
    await expect(page).not.toHaveURL(/error|500/);
  });

  test('task templates page renders', async ({ page }) => {
    await page.goto('http://testco.aeos365.test/hrm/settings/task-templates');
    await expect(page).not.toHaveURL(/error|500/);
  });
});
```

- [ ] Commit:
```bash
git add tests/e2e/hrm/settings.spec.js
git commit -m "test(hrm): Playwright smoke tests for HRM settings pages"
```
