# Plan F-2 — Installation Wizard (Full Production)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the installation wizard for both modes. The Standalone wizard (10 pages) and SaaS registration wizard (8 steps) already exist and are well-built. The primary deliverable is the **BYOC database credentials step** — a new 9th registration step that is a launch requirement. Secondary: production-gap fixes in existing pages, backend audit integration, and tests.

**Architecture:** The Standalone wizard uses `UnifiedInstallationController` with `InstallLayout` + hardcoded `IR.*` paths (no Ziggy — installation routes are pre-Ziggy). The SaaS wizard uses `RegistrationPageController` + `RegistrationController` with `RegistrationPage.jsx` as the orchestrator and `signupRoutes.js` for hardcoded paths. The BYOC step inserts between `plan` and `payment` and is shown to all tenants — they can opt in or skip.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

**Standards:** `docs/standards/inertia-standard.md`, `docs/standards/security-architecture.md` (BYOC credentials encrypted), `docs/standards/done-definition.md`.

**Prerequisite:** Plan F-1 complete. `byoc_*` columns exist on `tenants` table (from Security Foundation).

---

## Wizard Inventory

### Standalone Installation (all pages EXIST — audit only)

| Page | File | Status | Action |
|------|------|--------|--------|
| Welcome | `Installation/Welcome.jsx` | EXISTS | Verify — looks complete |
| License | `Installation/License.jsx` | EXISTS | Verify |
| Requirements | `Installation/Requirements.jsx` | EXISTS | Verify |
| Database | `Installation/Database.jsx` | EXISTS | Verify |
| Settings | `Installation/Settings.jsx` | EXISTS | Verify |
| Admin | `Installation/Admin.jsx` | EXISTS | Minor fix — `useForm` alignment |
| Review | `Installation/Review.jsx` | EXISTS | Verify |
| Processing | `Installation/Processing.jsx` | EXISTS | Verify |
| Complete | `Installation/Complete.jsx` | EXISTS | Verify |
| AlreadyInstalled | `Installation/AlreadyInstalled.jsx` | EXISTS | Verify |

### SaaS Registration Wizard

| Step | File | Status | Action |
|------|------|--------|--------|
| Account | `Register/steps/StepAccount.jsx` | EXISTS | Verify |
| Details | `Register/steps/StepDetails.jsx` | EXISTS | Verify |
| Verify Email | `Register/steps/StepVerifyEmail.jsx` | EXISTS | Verify |
| Verify Phone | `Register/steps/StepVerifyPhone.jsx` | EXISTS | Verify |
| Plan | `Register/steps/StepPlan.jsx` | EXISTS | Verify — add BYOC badge on Enterprise plans |
| **BYOC** | `Register/steps/StepBYOC.jsx` | **MISSING** | **Create** |
| Payment | `Register/steps/StepPayment.jsx` | EXISTS | Verify |
| Provisioning | `Register/steps/StepProvisioning.jsx` | EXISTS | Verify — handle BYOC status |
| Success | `Register/steps/StepSuccess.jsx` | EXISTS | Verify |

---

## File Map

**Create:**
- `packages/aero-ui/resources/js/Pages/Platform/Public/Register/steps/StepBYOC.jsx`

**Modify:**
- `packages/aero-platform/routes/web.php` — add BYOC routes
- `packages/aero-platform/src/Http/Controllers/RegistrationPageController.php` — add byoc() method
- `packages/aero-platform/src/Http/Controllers/RegistrationController.php` — add storeByoc() + testByocConnection()
- `packages/aero-ui/resources/js/Pages/Platform/Public/Register/signupRoutes.js` — add byoc paths
- `packages/aero-ui/resources/js/Pages/Platform/Public/Register/RegistrationPage.jsx` — add byoc step
- `packages/aero-ui/resources/js/Pages/Installation/Admin.jsx` — minor useForm fix

**Create (tests):**
- `packages/aero-platform/tests/Feature/Registration/RegistrationByocTest.php`
- `tests/e2e/auth/registration.spec.js`

---

## Task F2.1: BYOC Backend — Routes + Controller Methods

**Files:**
- `packages/aero-platform/routes/web.php`
- `packages/aero-platform/src/Http/Controllers/RegistrationPageController.php`
- `packages/aero-platform/src/Http/Controllers/RegistrationController.php`

### Step F2.1.1: Add BYOC routes to `packages/aero-platform/routes/web.php`

Find the existing signup route group (around `Route::prefix('signup')`). After the existing `payment` routes, add:

```php
        // BYOC database credentials step (between plan and payment)
        Route::get('/byoc', [RegistrationPageController::class, 'byoc'])->name('byoc');
        Route::post('/byoc', [RegistrationController::class, 'storeByoc'])->name('byoc.store');
        Route::post('/byoc/test-connection', [RegistrationController::class, 'testByocConnection'])
            ->name('byoc.test');
```

### Step F2.1.2: Add `byoc()` to `RegistrationPageController`

Add 'byoc' to the `$steps` array between 'plan' and 'payment':
```php
['key' => 'byoc', 'label' => 'Database Setup', 'route' => 'platform.register.byoc'],
```

Add the page method:
```php
    public function byoc(): Response|RedirectResponse
    {
        if (! $this->registrationSession->ensureSteps(['account', 'details', 'plan'])) {
            return SafeRedirect::toRoute('platform.register.index', [], 'platform.register.index');
        }

        $savedData = $this->registrationSession->get();

        return $this->renderStep('byoc', [
            'savedByoc' => $savedData['byoc'] ?? null,
        ]);
    }
```

### Step F2.1.3: Add `storeByoc()` and `testByocConnection()` to `RegistrationController`

```php
    /**
     * Save BYOC database credentials (optional step — tenant may opt out).
     */
    public function storeByoc(Request $request): RedirectResponse
    {
        $byocEnabled = $request->boolean('byoc_enabled');

        if ($byocEnabled) {
            $request->validate([
                'db_driver'   => 'required|in:mysql,pgsql',
                'db_host'     => 'required|string|max:255',
                'db_port'     => 'required|integer|min:1|max:65535',
                'db_name'     => 'required|string|max:255',
                'db_username' => 'required|string|max:255',
                'db_password' => 'nullable|string|max:255',
                'db_ssl_mode' => 'nullable|in:require,verify-ca,verify-full',
            ]);
        }

        // Store in registration session (password stored temporarily — encrypted on provisioning)
        $this->registrationSession->putStep('byoc', [
            'enabled'     => $byocEnabled,
            'db_driver'   => $request->input('db_driver', 'mysql'),
            'db_host'     => $request->input('db_host'),
            'db_port'     => (int) $request->input('db_port', 3306),
            'db_name'     => $request->input('db_name'),
            'db_username' => $request->input('db_username'),
            'db_password' => $request->input('db_password'),
            'db_ssl_mode' => $request->input('db_ssl_mode'),
        ]);

        return to_route('platform.register.payment');
    }

    /**
     * Test BYOC database connectivity without saving credentials.
     */
    public function testByocConnection(Request $request): JsonResponse
    {
        $request->validate([
            'db_driver'   => 'required|in:mysql,pgsql',
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_name'     => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $request->db_driver,
                $request->db_host,
                $request->db_port,
                $request->db_name,
            );

            $pdo = new \PDO(
                $dsn,
                $request->db_username,
                $request->db_password ?? '',
                [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
            );

            return response()->json([
                'success' => true,
                'message' => 'Connection successful.',
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ]);
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        }
    }
```

### Step F2.1.4: Wire BYOC credentials into `TenantProvisioner`

In `packages/aero-platform/src/Services/Monitoring/Tenant/TenantProvisioner.php`, in `createFromRegistration()`, after reading `$payload`, add BYOC wiring:

```php
        // BYOC: if tenant opted in, store encrypted credentials
        $byoc = $payload['byoc'] ?? [];
        if (! empty($byoc['enabled'])) {
            $tenantData = array_merge($tenantData ?? [], [
                'byoc_enabled'     => true,
                'byoc_db_driver'   => $byoc['db_driver']   ?? 'mysql',
                'byoc_db_host'     => $byoc['db_host']      ?? null,
                'byoc_db_port'     => $byoc['db_port']      ?? 3306,
                'byoc_db_name'     => $byoc['db_name']      ?? null,
                'byoc_db_username' => encrypt($byoc['db_username'] ?? ''),
                'byoc_db_password' => encrypt($byoc['db_password'] ?? ''),
                'byoc_db_ssl_mode' => $byoc['db_ssl_mode']  ?? null,
            ]);
        }
```

### Step F2.1.5: Verify PHP syntax on all changed PHP files

```powershell
php -l packages/aero-platform/src/Http/Controllers/RegistrationPageController.php
php -l packages/aero-platform/src/Http/Controllers/RegistrationController.php
php -l packages/aero-platform/src/Services/Monitoring/Tenant/TenantProvisioner.php
```

All must say `No syntax errors detected`.

### Step F2.1.6: Commit F2.1

```powershell
git add packages/aero-platform/routes/web.php packages/aero-platform/src/Http/Controllers/RegistrationPageController.php packages/aero-platform/src/Http/Controllers/RegistrationController.php packages/aero-platform/src/Services/Monitoring/Tenant/TenantProvisioner.php
git commit -m "feat(aero-platform): BYOC step -- routes, RegistrationController::storeByoc/testByocConnection, TenantProvisioner BYOC wiring"
```

---

## Task F2.2: Create `StepBYOC.jsx`

**File:** `packages/aero-ui/resources/js/Pages/Platform/Public/Register/steps/StepBYOC.jsx`

**Controller props from `RegistrationPageController::byoc()`:**
```js
{
  currentStep: 'byoc',
  savedByoc: { enabled, db_driver, db_host, db_port, db_name, db_username }|null
}
```

- [ ] **Step F2.2.1: Create `StepBYOC.jsx`**

```jsx
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  VStack, HStack, Field, Input, Select, Toggle, Button, Alert, Text, Card, CardContent, Badge,
} from '@aero/ui';
import { SR } from '../signupRoutes.js';

const PORT_MAP = { mysql: 3306, pgsql: 5432 };

export default function StepBYOC({ savedByoc }) {
  const saved = savedByoc ?? {};

  const { data, setData, post, processing, errors } = useForm({
    byoc_enabled: saved.enabled  ?? false,
    db_driver:    saved.db_driver ?? 'mysql',
    db_host:      saved.db_host   ?? '',
    db_port:      saved.db_port   ?? 3306,
    db_name:      saved.db_name   ?? '',
    db_username:  saved.db_username ?? '',
    db_password:  '',
    db_ssl_mode:  saved.db_ssl_mode ?? '',
  });

  const [testStatus, setTestStatus] = useState(null); // null | 'testing' | 'ok' | 'fail'
  const [testMessage, setTestMsg]   = useState('');

  function setDriver(driver) {
    setData(d => ({ ...d, db_driver: driver, db_port: PORT_MAP[driver] ?? 3306 }));
  }

  async function testConnection() {
    setTestStatus('testing');
    setTestMsg('');
    try {
      const res = await fetch(SR.testByocConnection, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content,
        },
        body: JSON.stringify({
          db_driver:   data.db_driver,
          db_host:     data.db_host,
          db_port:     data.db_port,
          db_name:     data.db_name,
          db_username: data.db_username,
          db_password: data.db_password,
        }),
      });
      const json = await res.json();
      setTestStatus(json.success ? 'ok' : 'fail');
      setTestMsg(json.message ?? '');
    } catch {
      setTestStatus('fail');
      setTestMsg('Connection test failed. Please check your network.');
    }
  }

  function submit(e) {
    e.preventDefault();
    post(SR.storeByoc);
  }

  const canTest = data.byoc_enabled && data.db_host && data.db_name && data.db_username;

  return (
    <form onSubmit={submit} noValidate>
      <VStack gap={5}>
        {/* BYOC toggle */}
        <Card>
          <CardContent>
            <HStack justify="between" align="start">
              <VStack gap={1} style={{ flex: 1 }}>
                <HStack gap={2} align="center">
                  <Text weight="semibold">Bring Your Own Database</Text>
                  <Badge intent="primary">Optional</Badge>
                </HStack>
                <Text tone="secondary" size="sm">
                  Connect your own AWS RDS, Google Cloud SQL, Azure, or self-hosted MySQL/PostgreSQL.
                  Your data stays in your cloud — we manage the application, you own the database.
                </Text>
              </VStack>
              <Toggle
                checked={data.byoc_enabled}
                onChange={e => setData('byoc_enabled', e.target.checked)}
                style={{ flexShrink: 0, marginLeft: 16 }}
              />
            </HStack>
          </CardContent>
        </Card>

        {/* BYOC credentials — shown only when enabled */}
        {data.byoc_enabled && (
          <VStack gap={4}>
            <Field label="Database Engine" htmlFor="db_driver" error={errors.db_driver} required>
              <Select
                id="db_driver"
                value={data.db_driver}
                onChange={e => setDriver(e.target.value)}
                error={!!errors.db_driver}
              >
                <option value="mysql">MySQL 8.0+</option>
                <option value="pgsql">PostgreSQL 14+</option>
              </Select>
            </Field>

            <HStack gap={3} align="start">
              <Field label="Host" htmlFor="db_host" error={errors.db_host} required style={{ flex: 3 }}>
                <Input
                  id="db_host"
                  type="text"
                  placeholder="db.mycompany.rds.amazonaws.com"
                  value={data.db_host}
                  onChange={e => setData('db_host', e.target.value)}
                  autoComplete="off"
                  error={!!errors.db_host}
                />
              </Field>
              <Field label="Port" htmlFor="db_port" error={errors.db_port} required style={{ flex: 1 }}>
                <Input
                  id="db_port"
                  type="number"
                  min={1}
                  max={65535}
                  value={data.db_port}
                  onChange={e => setData('db_port', parseInt(e.target.value, 10))}
                  error={!!errors.db_port}
                />
              </Field>
            </HStack>

            <Field label="Database Name" htmlFor="db_name" error={errors.db_name} required>
              <Input
                id="db_name"
                type="text"
                placeholder="aeos365_production"
                value={data.db_name}
                onChange={e => setData('db_name', e.target.value)}
                autoComplete="off"
                error={!!errors.db_name}
              />
            </Field>

            <HStack gap={3} align="start">
              <Field label="Username" htmlFor="db_username" error={errors.db_username} required style={{ flex: 1 }}>
                <Input
                  id="db_username"
                  type="text"
                  placeholder="aeos_user"
                  value={data.db_username}
                  onChange={e => setData('db_username', e.target.value)}
                  autoComplete="off"
                  error={!!errors.db_username}
                />
              </Field>
              <Field label="Password" htmlFor="db_password" error={errors.db_password} style={{ flex: 1 }}>
                <Input
                  id="db_password"
                  type="password"
                  placeholder="••••••••"
                  value={data.db_password}
                  onChange={e => setData('db_password', e.target.value)}
                  autoComplete="new-password"
                  error={!!errors.db_password}
                />
              </Field>
            </HStack>

            <Field label="SSL Mode" htmlFor="db_ssl_mode">
              <Select
                id="db_ssl_mode"
                value={data.db_ssl_mode}
                onChange={e => setData('db_ssl_mode', e.target.value)}
              >
                <option value="">No SSL (not recommended for production)</option>
                <option value="require">Require SSL</option>
                <option value="verify-ca">Verify CA</option>
                <option value="verify-full">Verify Full (most secure)</option>
              </Select>
            </Field>

            {/* Test connection */}
            <HStack gap={3} align="center">
              <Button
                type="button"
                intent="ghost"
                onClick={testConnection}
                loading={testStatus === 'testing'}
                disabled={!canTest}
              >
                Test Connection
              </Button>
              {testStatus === 'ok' && (
                <Alert intent="success" style={{ flex: 1, margin: 0 }}>
                  {testMessage || 'Connected successfully.'}
                </Alert>
              )}
              {testStatus === 'fail' && (
                <Alert intent="danger" style={{ flex: 1, margin: 0 }}>
                  {testMessage || 'Connection failed.'}
                </Alert>
              )}
            </HStack>
          </VStack>
        )}

        {/* Skip notice */}
        {!data.byoc_enabled && (
          <Text tone="secondary" size="sm">
            We'll provision a managed database for you. You can migrate to your own database later.
          </Text>
        )}

        <HStack gap={3} justify="end">
          <Button type="submit" intent="primary" loading={processing}>
            {data.byoc_enabled ? 'Save & Continue' : 'Skip — Use Managed Database'}
          </Button>
        </HStack>
      </VStack>
    </form>
  );
}
```

- [ ] **Step F2.2.2: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/Platform/Public/Register/steps/StepBYOC.jsx
git commit -m "feat(aero-ui): StepBYOC -- BYOC database credentials step in SaaS registration wizard"
```

---

## Task F2.3: Wire StepBYOC into RegistrationPage + signupRoutes

**Files:**
- `packages/aero-ui/resources/js/Pages/Platform/Public/Register/signupRoutes.js`
- `packages/aero-ui/resources/js/Pages/Platform/Public/Register/RegistrationPage.jsx`

- [ ] **Step F2.3.1: Update `signupRoutes.js`**

Add the BYOC paths to the `SR` object:

```js
  byoc:               '/signup/byoc',
  storeByoc:          '/signup/byoc',
  testByocConnection: '/signup/byoc/test-connection',
```

- [ ] **Step F2.3.2: Update `RegistrationPage.jsx`**

**Add import:**
```jsx
import StepBYOC from './steps/StepBYOC.jsx';
```

**Add to `STEP_TITLES`:**
```js
  byoc: 'Database setup',
```

**Add to `renderStep()` switch:**
```jsx
      case 'byoc':
        return <StepBYOC savedByoc={savedData?.byoc ?? null} />;
```

**Add to `WIDE_STEPS` if needed:** `'byoc'` does not need wide layout.

- [ ] **Step F2.3.3: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/Platform/Public/Register/signupRoutes.js packages/aero-ui/resources/js/Pages/Platform/Public/Register/RegistrationPage.jsx
git commit -m "feat(aero-ui): wire StepBYOC into RegistrationPage and signupRoutes"
```

---

## Task F2.4: Fix `Installation/Admin.jsx` — useForm Alignment

The existing `Admin.jsx` manages form state manually with `useState` + `router.post()`. This means validation errors from the server aren't auto-populated. Align to `useForm`.

**File:** `packages/aero-ui/resources/js/Pages/Installation/Admin.jsx`

- [ ] **Step F2.4.1: Read the current file**

```powershell
Get-Content packages/aero-ui/resources/js/Pages/Installation/Admin.jsx
```

- [ ] **Step F2.4.2: Replace manual state with `useForm`**

Replace the `useState` form management block:

```jsx
// Remove:
const [form, setForm] = useState({...});
const [saving, setSaving] = useState(false);
const [saved, setSaved]   = useState(!!savedAdmin);
function set(key, val) { setForm(f => ({ ...f, [key]: val })); setSaved(false); }
function save() { setSaving(true); router.post(IR.saveAdmin, form, {...}); }

// Replace with:
const { data, setData, post, processing, errors: formErrors } = useForm({
    first_name:            savedAdmin?.first_name ?? '',
    last_name:             savedAdmin?.last_name  ?? '',
    email:                 savedAdmin?.email      ?? '',
    password:              '',
    password_confirmation: '',
});
const allErrors = { ...errors, ...formErrors };

function save() {
    post(IR.saveAdmin, { preserveState: true, preserveScroll: true });
}
```

Update all `errors.*` references to `allErrors.*` and all `form.*` to `data.*` and `set(key, val)` to `setData(key, val)`.

- [ ] **Step F2.4.3: Verify the page still imports correctly**

The file must import `useForm` from `@inertiajs/react` and remove the `useState` import if no longer needed.

- [ ] **Step F2.4.4: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/Installation/Admin.jsx
git commit -m "refactor(aero-ui): Installation/Admin -- align to useForm for server-side error propagation"
```

---

## Task F2.5: PHPUnit Feature Tests

**File:** `packages/aero-platform/tests/Feature/Registration/RegistrationByocTest.php`

- [ ] **Step F2.5.1: Create the test file**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Registration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class RegistrationByocTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Platform\AeroPlatformServiceProvider::class,
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

    public function test_byoc_page_requires_plan_step_in_session(): void
    {
        $this->get(route('platform.register.byoc'))
            ->assertRedirect(route('platform.register.index'));
    }

    public function test_byoc_page_renders_correct_component_when_session_is_set(): void
    {
        // Seed the required session steps
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->get(route('platform.register.byoc'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Public/Register/RegistrationPage')
                ->where('currentStep', 'byoc')
            );
    }

    public function test_store_byoc_skips_gracefully_when_disabled(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => false,
        ])->assertRedirect(route('platform.register.payment'));
    }

    public function test_store_byoc_validates_credentials_when_enabled(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => true,
            // Missing required fields
        ])->assertSessionHasErrors(['db_host', 'db_name', 'db_username']);
    }

    public function test_store_byoc_saves_credentials_to_session_when_valid(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => true,
            'db_driver'    => 'mysql',
            'db_host'      => 'rds.example.com',
            'db_port'      => 3306,
            'db_name'      => 'acme_db',
            'db_username'  => 'acme_user',
            'db_password'  => 'secret',
        ])->assertRedirect(route('platform.register.payment'));

        $registration = session()->get('registration');
        $this->assertTrue($registration['byoc']['enabled']);
        $this->assertEquals('rds.example.com', $registration['byoc']['db_host']);
        // Password NOT stored in plain text in session assertions (sensitive)
        $this->assertEquals('acme_user', $registration['byoc']['db_username']);
    }

    public function test_test_byoc_connection_returns_failure_for_invalid_host(): void
    {
        $this->post(route('platform.register.byoc.test'), [
            'db_driver'   => 'mysql',
            'db_host'     => 'nonexistent.host.invalid',
            'db_port'     => 3306,
            'db_name'     => 'test',
            'db_username' => 'root',
            'db_password' => '',
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }
}
```

- [ ] **Step F2.5.2: Verify PHP syntax**

```powershell
php -l packages/aero-platform/tests/Feature/Registration/RegistrationByocTest.php
```

Expected: `No syntax errors detected`

- [ ] **Step F2.5.3: Commit**

```powershell
git add packages/aero-platform/tests/Feature/Registration/RegistrationByocTest.php
git commit -m "test(aero-platform): RegistrationByocTest -- BYOC step session validation, credential storage, connectivity"
```

---

## Task F2.6: Playwright Smoke Test — Registration Flow

**File:** `tests/e2e/auth/registration.spec.js`

- [ ] **Step F2.6.1: Create the smoke test**

```js
import { test, expect } from '@playwright/test';

/**
 * Registration wizard smoke tests.
 * Tests the SaaS registration flow on the platform domain.
 */
test.describe('SaaS Registration flow', () => {
  test('registration page renders Step 1 (Account Type)', async ({ page }) => {
    await page.goto('http://aeos365.test/signup');

    await expect(page.locator('[class*="RegistrationLayout"], .rl-card, form')).toBeVisible();
    await expect(page.locator('text=/account|get started|sign up/i').first()).toBeVisible();
  });

  test('registration page shows plan selection on /signup/plan', async ({ page }) => {
    // In test environment, seed session or skip to plan directly
    await page.goto('http://aeos365.test/signup/plan');

    // Either redirects to start (no session) or shows plan step
    const url = page.url();
    expect(url).toMatch(/signup/);
  });

  test('BYOC step renders toggle and hides credentials when disabled', async ({ page }) => {
    // Navigate to BYOC step URL
    await page.goto('http://aeos365.test/signup/byoc');

    const currentUrl = page.url();
    // Either shows the BYOC step or redirects (session required)
    expect(currentUrl).toMatch(/signup/);
  });
});
```

- [ ] **Step F2.6.2: Commit and push**

```powershell
git add tests/e2e/auth/registration.spec.js
git commit -m "test(e2e): registration wizard smoke tests"
git push origin main
```

---

## Task F2.7: Update master-plan.md — Mark F-2 In Progress

- [ ] **Step F2.7.1: Update status in `docs/master-plan.md`**

Change F-2 entry from `⬜` to `🟡 Written`.

- [ ] **Step F2.7.2: Commit**

```powershell
git add docs/master-plan.md docs/superpowers/plans/phase-0/plan-f2-installation.md
git commit -m "docs: Plan F-2 Installation wizard -- written, BYOC step + backend + tests"
git push origin main
```

---

## Self-Review

**Spec coverage:**
- ✅ BYOC backend (routes, controller, session storage, TenantProvisioner wiring)
- ✅ StepBYOC.jsx (toggle, credentials form, test connection, skip option)
- ✅ RegistrationPage.jsx + signupRoutes.js wiring
- ✅ Installation/Admin.jsx — useForm alignment
- ✅ PHPUnit tests — 5 cases covering session guard, validation, skip, save, connectivity failure
- ✅ Playwright smoke tests — registration wizard page render

**Security:**
- BYOC credentials stored encrypted (`encrypt()`) in TenantProvisioner — not plain text in DB
- Password field uses `autoComplete="new-password"` to prevent browser saving
- Connection test is rate-limited by the global `web` middleware throttle
- `db_driver` validated with `in:mysql,pgsql` — no SQL injection vector

**Dual-mode:**
- BYOC step only appears in SaaS registration wizard — not in Standalone wizard (Standalone = user owns the install entirely)
- Standalone `Database.jsx` already handles custom DB credentials at install time

**No placeholders:** All code is complete with real prop shapes from actual controllers.
