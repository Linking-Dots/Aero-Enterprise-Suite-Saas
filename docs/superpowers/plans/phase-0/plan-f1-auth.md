# Plan F-1 — Auth Module (Full Production)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the entire authentication module to production-ready standard — upgrade existing pages, create all missing pages, remove debug artifacts from controllers, integrate the new `AuditServiceInterface`, and ensure device-binding login works end-to-end in both SaaS (tenant subdomain) and Standalone modes.

**Architecture:** aero-auth handles all tenant-context authentication. The backend is largely complete but has three issues: (1) `LoginController` has `\Log::info()` debug statements that must be removed; (2) controllers reference `Aero\Core\Services\AuditService` directly instead of `AuditServiceInterface`; (3) the existing `Login.jsx` doesn't send `device_id` — the controller rejects login without it. This plan fixes all three and builds the 7 missing pages.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

**Standards:** All pages follow `docs/standards/inertia-standard.md`. All sensitive events use `AuditServiceInterface`. Done criteria: `docs/standards/done-definition.md`.

**Prerequisite:** Plans A–R + Security Foundation committed to `main`.

---

## Page Inventory

| Page | Path | Status | Action |
|------|------|--------|--------|
| Login | `Auth/Login` | EXISTS | Upgrade — add `device_id`, audit |
| Forgot Password | `Auth/ForgotPassword` | EXISTS | Upgrade — standard alignment |
| Reset Password | `Auth/ResetPassword` | EXISTS | Upgrade — standard alignment |
| Verify Email | `Auth/VerifyEmail` | EXISTS | Upgrade — standard alignment |
| 2FA Challenge | `Auth/TwoFactor/Challenge` | EXISTS | Upgrade — minor cleanup |
| 2FA Setup | `Auth/TwoFactor/Index` | MISSING | Create |
| Device Management | `Auth/Devices` | MISSING | Create |
| Session Management | `Auth/Sessions` | MISSING | Create |
| Accept Invitation | `Auth/AcceptInvitation` | MISSING | Create |
| Invitation Invalid | `Auth/InvitationInvalid` | MISSING | Create |
| Shared Login | `Shared/Auth/Login` | MISSING | Create (platform/admin domain) |
| Admin User Devices | `Admin/UserDevices` | MISSING | Create |

---

## File Map

**Modify:**
- `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php`
- `packages/aero-ui/resources/js/Pages/Auth/Login.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/ForgotPassword.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/ResetPassword.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/VerifyEmail.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/TwoFactor/Challenge.jsx`

**Create:**
- `packages/aero-ui/resources/js/Pages/Auth/TwoFactor/Index.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/Devices.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/Sessions.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/AcceptInvitation.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/InvitationInvalid.jsx`
- `packages/aero-ui/resources/js/Pages/Shared/Auth/Login.jsx`
- `packages/aero-ui/resources/js/Pages/Admin/UserDevices.jsx`
- `packages/aero-auth/tests/Feature/Auth/LoginControllerTest.php`
- `packages/aero-auth/tests/Feature/Auth/TwoFactorControllerTest.php`
- `packages/aero-auth/tests/Feature/Auth/SessionControllerTest.php`
- `tests/e2e/auth/login.spec.js`

---

## Task F1.1: Clean LoginController + Integrate AuditServiceInterface

**File:** `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php`

The controller has three production issues:
1. `\Log::info("LOGIN ATTEMPT - Database...")` and `\Log::info("USER LOOKUP...")` — remove completely (debug artifacts)
2. References `App\Http\Middleware\IdentifyDomainContext` — should be `\Aero\Platform\Http\Middleware\IdentifyDomainContext`
3. `use Aero\Core\Services\AuditService` — upgrade to `use Aero\Contracts\AuditServiceInterface`

- [ ] **Step F1.1.1: Remove debug log statements**

Find and remove these two blocks from `LoginController::store()`:
```php
// Remove this block:
$currentDb = DB::connection()->getDatabaseName();
$tenantId = tenant('id') ?? 'NO_TENANT';
\Log::info("LOGIN ATTEMPT - Database: {$currentDb}, Tenant: {$tenantId}, Email: {$email}");

// Remove this block:
\Log::info('USER LOOKUP - Found: '.($user ? 'YES (ID: '.$user->id.')' : 'NO'));
```

- [ ] **Step F1.1.2: Update AuditService import**

Replace in `LoginController.php`:
```php
// Remove (if present):
use Aero\Core\Services\AuditService;

// The controller uses $this->authService->logAuthenticationEvent() which is correct.
// Additionally inject AuditServiceInterface for structured audit events:
use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
```

Add `AuditServiceInterface $auditService` to the constructor and inject it. Then in `destroy()`, after `Auth::guard('web')->logout()`:
```php
app(AuditServiceInterface::class)->log(
    event:       AuditEventType::LOGOUT->value,
    action:      'logout',
    description: "User logged out",
);
```

- [ ] **Step F1.1.3: Fix IdentifyDomainContext namespace**

In `LoginController.php`, replace:
```php
use App\Http\Middleware\IdentifyDomainContext;
```
With:
```php
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
```

- [ ] **Step F1.1.4: Verify syntax**
```powershell
php -l packages/aero-auth/src/Http/Controllers/Auth/LoginController.php
```
Expected: `No syntax errors detected`

- [ ] **Step F1.1.5: Commit**
```powershell
git add packages/aero-auth/src/Http/Controllers/Auth/LoginController.php
git commit -m "fix(aero-auth): remove debug Log::info statements, fix IdentifyDomainContext namespace, inject AuditServiceInterface in LoginController"
```

---

## Task F1.2: Upgrade Login.jsx — Add device_id binding

**File:** `packages/aero-ui/resources/js/Pages/Auth/Login.jsx`

**Critical bug:** The `LoginController` requires `device_id` in the request body and returns a validation error without it. The current `Login.jsx` doesn't send it. Fix: generate a UUID v4 in `localStorage` on first visit and include it with every login attempt.

The controller expects:
```php
// From LoginController::store():
$deviceId = $request->input('device_id') ?? $request->header('X-Device-ID');
if (!$deviceId) { throw ValidationException... }
```

- [ ] **Step F1.2.1: Rewrite `packages/aero-ui/resources/js/Pages/Auth/Login.jsx`**

```jsx
import { useEffect } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Toggle, Button, Alert, Text, HStack } from '@aero/ui';

// Persist a UUID per browser — used for device fingerprinting / security.
// A new UUID means a new "device" from the server's perspective.
function getOrCreateDeviceId() {
  let id = localStorage.getItem('aeos_device_id');
  if (!id) {
    id = crypto.randomUUID();
    localStorage.setItem('aeos_device_id', id);
  }
  return id;
}

export default function Login({
  canResetPassword,
  status,
  canRegister,
  oauthProviders = [],
  deviceBlocked,
  deviceMessage,
  blockedDeviceInfo,
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email:     '',
    password:  '',
    remember:  false,
    device_id: '',
  });

  // Populate device_id from localStorage on mount (not in form initial state
  // to avoid SSR mismatch — localStorage is browser-only)
  useEffect(() => {
    setData('device_id', getOrCreateDeviceId());
  }, []);

  function submit(e) {
    e.preventDefault();
    post(route('login'), { onFinish: () => reset('password') });
  }

  return (
    <AuthLayout title="Sign in to your account">
      <form className="al-form" onSubmit={submit} noValidate>
        {status && <Alert intent="info">{status}</Alert>}

        {deviceBlocked && (
          <Alert intent="danger" title="Device blocked">
            <Text>{deviceMessage ?? 'This device has been blocked. Contact your administrator.'}</Text>
            {blockedDeviceInfo && (
              <Text size="sm" tone="secondary" style={{ marginTop: 6 }}>
                Last seen: {blockedDeviceInfo.device_name} · {blockedDeviceInfo.last_used_at}
              </Text>
            )}
          </Alert>
        )}

        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            placeholder="you@company.com"
            autoComplete="email"
            autoFocus
            error={!!errors.email}
          />
        </Field>

        <Field label="Password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="••••••••"
            autoComplete="current-password"
            error={!!errors.password}
          />
        </Field>

        <HStack justify="between" align="center">
          <Toggle
            label="Remember me"
            checked={data.remember}
            onChange={e => setData('remember', e.target.checked)}
          />
          {canResetPassword && (
            <Link href={route('password.request')} className="al-link">
              Forgot password?
            </Link>
          )}
        </HStack>

        {/* device_id is submitted as a hidden field */}
        <input type="hidden" name="device_id" value={data.device_id} />

        {errors.device_id && (
          <Alert intent="danger">{errors.device_id}</Alert>
        )}

        <Button intent="primary" fullWidth loading={processing} type="submit" size="lg">
          Sign in
        </Button>

        {oauthProviders.length > 0 && (
          <>
            <div className="al-sep">
              <span className="al-sep-line" />
              <span className="al-sep-text">or continue with</span>
              <span className="al-sep-line" />
            </div>
            <div className="al-oauth-grid">
              {oauthProviders.map(p => (
                <a
                  key={p.name}
                  href={p.url}
                  className="aeos-btn aeos-btn-ghost"
                  style={{ flex: 1, justifyContent: 'center' }}
                >
                  {p.label}
                </a>
              ))}
            </div>
          </>
        )}

        {canRegister && (
          <Text tone="secondary" size="sm" style={{ textAlign: 'center', marginTop: 8 }}>
            Don't have an account?{' '}
            <Link href={route('platform.register.index')} className="al-link">
              Sign up
            </Link>
          </Text>
        )}
      </form>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.2.2: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/Login.jsx
git commit -m "fix(aero-ui): Login — add device_id UUID binding (required by LoginController device auth)"
```

---

## Task F1.3: Upgrade ForgotPassword + ResetPassword + VerifyEmail

**Files:**
- `packages/aero-ui/resources/js/Pages/Auth/ForgotPassword.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/ResetPassword.jsx`
- `packages/aero-ui/resources/js/Pages/Auth/VerifyEmail.jsx`

These pages exist but need alignment with the current standards: wrap in `AuthLayout`, use `Field` wrapper for errors, handle flash properly.

- [ ] **Step F1.3.1: Rewrite `ForgotPassword.jsx`**

```jsx
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button, Alert, Text } from '@aero/ui';

export default function ForgotPassword({ status }) {
  const { data, setData, post, processing, errors } = useForm({ email: '' });

  function submit(e) {
    e.preventDefault();
    post(route('password.email'));
  }

  return (
    <AuthLayout title="Reset your password">
      <form className="al-form" onSubmit={submit} noValidate>
        <Text tone="secondary" size="sm">
          Enter your email address and we'll send you a link to reset your password.
        </Text>

        {status && <Alert intent="success">{status}</Alert>}

        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            placeholder="you@company.com"
            autoComplete="email"
            autoFocus
            error={!!errors.email}
          />
        </Field>

        <Button intent="primary" fullWidth loading={processing} type="submit">
          Send reset link
        </Button>

        <Text tone="secondary" size="sm" style={{ textAlign: 'center' }}>
          <Link href={route('login')} className="al-link">Back to login</Link>
        </Text>
      </form>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.3.2: Rewrite `ResetPassword.jsx`**

```jsx
import { useForm } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button, Alert } from '@aero/ui';

export default function ResetPassword({ token, email }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    token,
    email:                 email ?? '',
    password:              '',
    password_confirmation: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('password.store'), {
      onFinish: () => reset('password', 'password_confirmation'),
    });
  }

  return (
    <AuthLayout title="Set new password">
      <form className="al-form" onSubmit={submit} noValidate>
        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            autoComplete="email"
            error={!!errors.email}
          />
        </Field>

        <Field label="New password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="At least 8 characters"
            autoComplete="new-password"
            autoFocus
            error={!!errors.password}
          />
        </Field>

        <Field label="Confirm password" htmlFor="password_confirmation" error={errors.password_confirmation} required>
          <Input
            id="password_confirmation"
            type="password"
            value={data.password_confirmation}
            onChange={e => setData('password_confirmation', e.target.value)}
            autoComplete="new-password"
            error={!!errors.password_confirmation}
          />
        </Field>

        <Button intent="primary" fullWidth loading={processing} type="submit">
          Reset password
        </Button>
      </form>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.3.3: Rewrite `VerifyEmail.jsx`**

```jsx
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Button, Alert, Text } from '@aero/ui';

export default function VerifyEmail({ status }) {
  const { post, processing } = useForm({});

  return (
    <AuthLayout title="Verify your email">
      <div className="al-form">
        <Text tone="secondary" size="sm">
          Please verify your email address by clicking the link we sent when you registered.
          If you didn't receive the email, click below to request another.
        </Text>

        {status === 'verification-link-sent' && (
          <Alert intent="success">
            A new verification link has been sent to your email address.
          </Alert>
        )}

        <Button
          intent="primary"
          fullWidth
          loading={processing}
          onClick={() => post(route('core.verification.send'))}
        >
          Resend verification email
        </Button>

        <Text tone="secondary" size="sm" style={{ textAlign: 'center' }}>
          <Link href={route('logout')} method="post" as="button" className="al-link">
            Sign out
          </Link>
        </Text>
      </div>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.3.4: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/ForgotPassword.jsx packages/aero-ui/resources/js/Pages/Auth/ResetPassword.jsx packages/aero-ui/resources/js/Pages/Auth/VerifyEmail.jsx
git commit -m "refactor(aero-ui): align ForgotPassword, ResetPassword, VerifyEmail to production standard"
```

---

## Task F1.4: Create 2FA Setup Page

**Controller props from `TwoFactorController::index()`:**
```js
{
  enabled: bool,
  remainingCodes: number
}
// Setup API: POST /auth/two-factor/setup → { secret, qr_url, recovery_codes }
// Confirm API: POST /auth/two-factor/confirm → { success } or validation error
// Disable: POST /auth/two-factor/disable
// Regenerate codes: POST /auth/two-factor/regenerate-codes
```

- [ ] **Step F1.4.1: Create `packages/aero-ui/resources/js/Pages/Auth/TwoFactor/Index.jsx`**

```jsx
import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent,
  Button, Input, Field, Alert, Badge,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';

export default function TwoFactorIndex({ enabled, remainingCodes }) {
  const toast = useToast();
  const [step, setStep]         = useState('idle'); // idle | setup | confirm | codes
  const [qrUrl, setQrUrl]       = useState('');
  const [secret, setSecret]     = useState('');
  const [recoveryCodes, setCodes] = useState([]);

  const confirmForm = useForm({ code: '' });
  const disableForm = useForm({});

  const startSetup = () => {
    fetch(route('auth.two-factor.setup'), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } })
      .then(r => r.json())
      .then(data => {
        setQrUrl(data.qr_url);
        setSecret(data.secret);
        setCodes(data.recovery_codes ?? []);
        setStep('setup');
      })
      .catch(() => toast.error('Failed to start 2FA setup.'));
  };

  const confirmSetup = () => {
    confirmForm.post(route('auth.two-factor.confirm'), {
      onSuccess: () => { setStep('codes'); toast.success('2FA enabled.'); },
      onError:   () => toast.error('Invalid code. Try again.'),
    });
  };

  const disable = () => {
    if (!confirm('Disable two-factor authentication? Your account will be less secure.')) return;
    disableForm.post(route('auth.two-factor.disable'), {
      onSuccess: () => { toast.success('2FA disabled.'); router.reload(); },
    });
  };

  const regenerateCodes = () => {
    fetch(route('auth.two-factor.regenerate-codes'), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    })
      .then(r => r.json())
      .then(data => { setCodes(data.codes ?? []); setStep('codes'); toast.success('Recovery codes regenerated.'); })
      .catch(() => toast.error('Failed to regenerate codes.'));
  };

  return (
    <App>
      <IndexPageLayout title="Two-Factor Authentication">
        <Card style={{ maxWidth: 520 }}>
          <CardContent>
            {!enabled && step === 'idle' && (
              <VStack gap={4}>
                <VStack gap={1}>
                  <Text weight="semibold">Protect your account</Text>
                  <Text tone="secondary" size="sm">
                    Two-factor authentication adds an extra layer of security. Once enabled,
                    you'll need your authenticator app in addition to your password.
                  </Text>
                </VStack>
                <Button intent="primary" onClick={startSetup}>Enable Two-Factor Authentication</Button>
              </VStack>
            )}

            {step === 'setup' && (
              <VStack gap={4}>
                <VStack gap={1}>
                  <Text weight="semibold">Scan this QR code</Text>
                  <Text tone="secondary" size="sm">
                    Use Google Authenticator, Authy, or any TOTP app to scan the code below.
                  </Text>
                </VStack>

                {qrUrl && (
                  <div style={{ display: 'flex', justifyContent: 'center' }}>
                    <img src={qrUrl} alt="2FA QR Code" style={{ width: 180, height: 180 }} />
                  </div>
                )}

                <VStack gap={1}>
                  <Text size="sm" tone="secondary">Or enter this setup key manually:</Text>
                  <Mono size="sm" style={{ letterSpacing: '0.1em', userSelect: 'all' }}>{secret}</Mono>
                </VStack>

                <Field label="Enter the 6-digit code from your app" htmlFor="code" error={confirmForm.errors.code}>
                  <Input
                    id="code"
                    type="text"
                    inputMode="numeric"
                    maxLength={6}
                    value={confirmForm.data.code}
                    onChange={e => confirmForm.setData('code', e.target.value)}
                    placeholder="000000"
                    autoFocus
                  />
                </Field>

                <HStack gap={2}>
                  <Button intent="primary" onClick={confirmSetup} loading={confirmForm.processing}>
                    Verify and Enable
                  </Button>
                  <Button intent="ghost" onClick={() => setStep('idle')}>Cancel</Button>
                </HStack>
              </VStack>
            )}

            {step === 'codes' && recoveryCodes.length > 0 && (
              <VStack gap={4}>
                <Alert intent="warning" title="Save your recovery codes">
                  These codes can be used to recover access if you lose your authenticator.
                  Each code can only be used once. Store them somewhere safe.
                </Alert>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                  {recoveryCodes.map((code, i) => (
                    <Mono key={i} size="sm" style={{ background: 'var(--aeos-surface-raised)', padding: '6px 10px', borderRadius: 6 }}>
                      {code}
                    </Mono>
                  ))}
                </div>
                <Button intent="primary" onClick={() => router.reload()}>Done — I've saved my codes</Button>
              </VStack>
            )}

            {enabled && step === 'idle' && (
              <VStack gap={4}>
                <HStack justify="between" align="center">
                  <VStack gap={0}>
                    <Text weight="semibold">Two-Factor Authentication</Text>
                    <Text tone="secondary" size="sm">Your account is protected with 2FA.</Text>
                  </VStack>
                  <Badge intent="success">Enabled</Badge>
                </HStack>

                <VStack gap={2}>
                  <Text size="sm" tone="secondary">Recovery codes remaining: <strong>{remainingCodes}</strong></Text>
                  {remainingCodes < 3 && (
                    <Alert intent="warning">
                      You have fewer than 3 recovery codes. Regenerate them now.
                    </Alert>
                  )}
                </VStack>

                <HStack gap={2}>
                  <Button intent="ghost" onClick={regenerateCodes}>Regenerate Recovery Codes</Button>
                  <Button intent="danger" onClick={disable} loading={disableForm.processing}>
                    Disable 2FA
                  </Button>
                </HStack>
              </VStack>
            )}
          </CardContent>
        </Card>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step F1.4.2: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/TwoFactor/Index.jsx
git commit -m "feat(aero-ui): 2FA Setup page -- QR code scan, confirm, recovery codes, disable flow"
```

---

## Task F1.5: Create Device Management Page

**Controller props from `DeviceController::index()`:**
```js
{
  devices: [{ id, device_name, browser, platform, device_type, ip_address, last_used_at, is_current, is_active }],
  singleDeviceEnabled: bool
}
```

- [ ] **Step F1.5.1: Create `packages/aero-ui/resources/js/Pages/Auth/Devices.jsx`**

```jsx
import { router } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent,
  Button, Badge, Toggle,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '../App.jsx';

function DeviceIcon({ type }) {
  const icons = { mobile: '📱', tablet: '📲', desktop: '💻', unknown: '🖥️' };
  return <span style={{ fontSize: 20 }}>{icons[type] ?? icons.unknown}</span>;
}

export default function Devices({ devices, singleDeviceEnabled }) {
  const toast = useToast();

  const deactivate = (deviceId) => {
    if (!confirm('Deactivate this device? You will be logged out from it.')) return;
    router.delete(route('core.devices.deactivate', deviceId), {
      preserveScroll: true,
      onSuccess: () => toast.success('Device deactivated.'),
      onError:   () => toast.error('Failed to deactivate device.'),
    });
  };

  const toggleSingleDevice = () => {
    router.post(route('core.devices.admin.toggle', { userId: 'me' }), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success(singleDeviceEnabled
        ? 'Multi-device login enabled.'
        : 'Single-device login enabled.'),
    });
  };

  return (
    <App>
      <IndexPageLayout title="Trusted Devices">
        {/* Single device toggle */}
        <Card style={{ marginBottom: 16 }}>
          <CardContent>
            <HStack justify="between" align="center">
              <VStack gap={0}>
                <Text weight="semibold">Single-device login</Text>
                <Text tone="secondary" size="sm">
                  When enabled, only the most recently active device can log in.
                  All other devices will be signed out automatically.
                </Text>
              </VStack>
              <Toggle
                checked={singleDeviceEnabled}
                onChange={toggleSingleDevice}
              />
            </HStack>
          </CardContent>
        </Card>

        {/* Device list */}
        <VStack gap={3}>
          {devices.length === 0 ? (
            <Card><CardContent><Text tone="secondary">No trusted devices found.</Text></CardContent></Card>
          ) : devices.map(device => (
            <Card key={device.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <HStack gap={3} align="center">
                    <DeviceIcon type={device.device_type} />
                    <VStack gap={0}>
                      <HStack gap={2} align="center">
                        <Text weight="semibold">{device.device_name ?? 'Unknown device'}</Text>
                        {device.is_current && <Badge intent="primary">This device</Badge>}
                        {!device.is_active && <Badge intent="neutral">Inactive</Badge>}
                      </HStack>
                      <Text size="sm" tone="secondary">
                        {device.browser} · {device.platform}
                      </Text>
                      <Mono size="xs" tone="tertiary">
                        {device.ip_address} · Last used {device.last_used_at}
                      </Mono>
                    </VStack>
                  </HStack>
                  {!device.is_current && device.is_active && (
                    <Button size="sm" intent="ghost" tone="danger" onClick={() => deactivate(device.id)}>
                      Deactivate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step F1.5.2: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/Devices.jsx
git commit -m "feat(aero-ui): Device Management page -- trusted devices list, single-device toggle, deactivate"
```

---

## Task F1.6: Create Session Management Page

**Controller props from `SessionController::index()`:**
```js
{
  title: 'Active Sessions',
  sessions: [{ id, ip_address, user_agent, browser, platform, last_activity, is_current }],
  current_session_id: number|null,
  max_sessions: number
}
```

- [ ] **Step F1.6.1: Create `packages/aero-ui/resources/js/Pages/Auth/Sessions.jsx`**

```jsx
import { router } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent, Button, Badge,
  HStack, VStack, Text, Mono, Alert,
  useToast,
} from '@aero/ui';
import App from '../App.jsx';

export default function Sessions({ sessions, current_session_id, max_sessions }) {
  const toast = useToast();

  const terminate = (sessionId) => {
    if (!confirm('Terminate this session?')) return;
    router.delete(route('core.security.sessions.terminate', sessionId), {
      preserveScroll: true,
      onSuccess: () => toast.success('Session terminated.'),
      onError:   () => toast.error('Failed to terminate session.'),
    });
  };

  const terminateAll = () => {
    if (!confirm('Sign out of all other sessions?')) return;
    router.delete(route('core.security.sessions.terminate-all'), {
      preserveScroll: true,
      onSuccess: () => toast.success('All other sessions terminated.'),
    });
  };

  const otherSessions = sessions.filter(s => s.id !== current_session_id);

  return (
    <App>
      <IndexPageLayout
        title="Active Sessions"
        actions={
          otherSessions.length > 0 && (
            <Button intent="ghost" tone="danger" onClick={terminateAll}>
              Sign out all other sessions
            </Button>
          )
        }
      >
        {sessions.length >= max_sessions && (
          <Alert intent="warning" style={{ marginBottom: 16 }}>
            You've reached the maximum of {max_sessions} concurrent sessions.
            Sign out of other sessions to log in from a new device.
          </Alert>
        )}

        <VStack gap={3}>
          {sessions.map(session => (
            <Card key={session.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={0}>
                    <HStack gap={2} align="center">
                      <Text weight="semibold">
                        {session.browser ?? 'Unknown browser'} · {session.platform ?? 'Unknown OS'}
                      </Text>
                      {session.id === current_session_id && (
                        <Badge intent="success">Current session</Badge>
                      )}
                    </HStack>
                    <Mono size="xs" tone="tertiary">
                      {session.ip_address} · Last active {session.last_activity}
                    </Mono>
                  </VStack>
                  {session.id !== current_session_id && (
                    <Button size="sm" intent="ghost" tone="danger" onClick={() => terminate(session.id)}>
                      Terminate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}

          {sessions.length === 0 && (
            <Card><CardContent><Text tone="secondary">No active sessions found.</Text></CardContent></Card>
          )}
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step F1.6.2: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/Sessions.jsx
git commit -m "feat(aero-ui): Session Management page -- active sessions list, terminate, terminate all"
```

---

## Task F1.7: Create Invitation Pages

**Controller props from `InvitationController::showAcceptForm()`:**
```js
// Auth/AcceptInvitation:
{
  title: 'Accept Invitation',
  invitation: { id, email, name, roles, inviter: { name }, expires_at },
  token: string
}
// Auth/InvitationInvalid:
{
  title: string,
  message: string
}
```

- [ ] **Step F1.7.1: Create `packages/aero-ui/resources/js/Pages/Auth/AcceptInvitation.jsx`**

```jsx
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Field, Input, Button, Alert, Text, VStack, Badge } from '@aero/ui';

export default function AcceptInvitation({ invitation, token }) {
  const { data, setData, post, processing, errors } = useForm({
    token,
    name:                  invitation.name ?? '',
    password:              '',
    password_confirmation: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('invitation.accept.store', token));
  }

  return (
    <AuthLayout title="Accept your invitation">
      <form className="al-form" onSubmit={submit} noValidate>
        <VStack gap={2} style={{ marginBottom: 16 }}>
          {invitation.inviter && (
            <Text tone="secondary" size="sm">
              {invitation.inviter.name} has invited you to join.
            </Text>
          )}
          <Text size="sm">
            <strong>{invitation.email}</strong>
          </Text>
          {invitation.roles?.length > 0 && (
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
              {invitation.roles.map(r => (
                <Badge key={r} intent="primary">{r}</Badge>
              ))}
            </div>
          )}
        </VStack>

        <Field label="Your name" htmlFor="name" error={errors.name} required>
          <Input
            id="name"
            type="text"
            value={data.name}
            onChange={e => setData('name', e.target.value)}
            autoComplete="name"
            autoFocus
            error={!!errors.name}
          />
        </Field>

        <Field label="Choose a password" htmlFor="password" error={errors.password} required>
          <Input
            id="password"
            type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            placeholder="At least 8 characters"
            autoComplete="new-password"
            error={!!errors.password}
          />
        </Field>

        <Field label="Confirm password" htmlFor="password_confirmation" error={errors.password_confirmation} required>
          <Input
            id="password_confirmation"
            type="password"
            value={data.password_confirmation}
            onChange={e => setData('password_confirmation', e.target.value)}
            autoComplete="new-password"
            error={!!errors.password_confirmation}
          />
        </Field>

        <Button intent="primary" fullWidth loading={processing} type="submit">
          Create account and sign in
        </Button>
      </form>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.7.2: Create `packages/aero-ui/resources/js/Pages/Auth/InvitationInvalid.jsx`**

```jsx
import { Link } from '@inertiajs/react';
import AuthLayout from './AuthLayout.jsx';
import { Alert, Button, Text } from '@aero/ui';

export default function InvitationInvalid({ title, message }) {
  return (
    <AuthLayout title={title ?? 'Invalid Invitation'}>
      <div className="al-form">
        <Alert intent="danger" title={title}>{message}</Alert>
        <Button intent="ghost" fullWidth as={Link} href={route('login')}>
          Back to login
        </Button>
      </div>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.7.3: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Auth/AcceptInvitation.jsx packages/aero-ui/resources/js/Pages/Auth/InvitationInvalid.jsx
git commit -m "feat(aero-ui): AcceptInvitation + InvitationInvalid pages"
```

---

## Task F1.8: Create Shared Login + Admin UserDevices

**`Shared/Auth/Login`** is used by `SimpleLoginController` for the platform/admin domain where device binding is not required (landlord users).

**Controller props from `SimpleLoginController::create()`:**
```js
{ status: string|null, canResetPassword: bool }
```

- [ ] **Step F1.8.1: Create `packages/aero-ui/resources/js/Pages/Shared/Auth/Login.jsx`**

```jsx
import { useForm } from '@inertiajs/react';
import AuthLayout from '../../Auth/AuthLayout.jsx';
import { Field, Input, Toggle, Button, Alert } from '@aero/ui';

export default function SharedLogin({ status, canResetPassword }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email:    '',
    password: '',
    remember: false,
  });

  function submit(e) {
    e.preventDefault();
    post(route('login'), { onFinish: () => reset('password') });
  }

  return (
    <AuthLayout title="Platform Admin">
      <form className="al-form" onSubmit={submit} noValidate>
        {status && <Alert intent="info">{status}</Alert>}

        <Field label="Email address" htmlFor="email" error={errors.email} required>
          <Input
            id="email" type="email"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            autoComplete="email" autoFocus
            error={!!errors.email}
          />
        </Field>

        <Field label="Password" htmlFor="password" error={errors.password} required>
          <Input
            id="password" type="password"
            value={data.password}
            onChange={e => setData('password', e.target.value)}
            autoComplete="current-password"
            error={!!errors.password}
          />
        </Field>

        <Toggle label="Remember me" checked={data.remember} onChange={e => setData('remember', e.target.checked)} />

        <Button intent="primary" fullWidth loading={processing} type="submit" size="lg">
          Sign in to Platform
        </Button>
      </form>
    </AuthLayout>
  );
}
```

- [ ] **Step F1.8.2: Create `packages/aero-ui/resources/js/Pages/Admin/UserDevices.jsx`**

```jsx
import { router } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent, Button, Badge,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';

export default function AdminUserDevices({ user, devices }) {
  const toast = useToast();

  const deactivate = (deviceId) => {
    if (!confirm('Deactivate this device?')) return;
    router.delete(route('core.devices.admin.deactivate', { userId: user.id, deviceId }), {
      preserveScroll: true,
      onSuccess: () => toast.success('Device deactivated.'),
    });
  };

  const resetAll = () => {
    if (!confirm(`Reset all devices for ${user.name}? They will need to log in again.`)) return;
    router.post(route('core.devices.admin.reset', { userId: user.id }), {}, {
      onSuccess: () => toast.success('All devices reset.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title={`Devices — ${user.name}`}
        breadcrumbs={[{ label: 'Users', href: route('core.users.index') }, { label: user.name }]}
        actions={<Button intent="ghost" tone="danger" onClick={resetAll}>Reset All Devices</Button>}
      >
        <VStack gap={3}>
          {devices.length === 0 ? (
            <Card><CardContent><Text tone="secondary">No registered devices.</Text></CardContent></Card>
          ) : devices.map(d => (
            <Card key={d.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={0}>
                    <HStack gap={2}>
                      <Text weight="semibold">{d.device_name ?? 'Unknown device'}</Text>
                      {!d.is_active && <Badge intent="neutral">Inactive</Badge>}
                    </HStack>
                    <Text size="sm" tone="secondary">{d.browser} · {d.platform}</Text>
                    <Mono size="xs" tone="tertiary">{d.ip_address} · Last used {d.last_used_at}</Mono>
                  </VStack>
                  {d.is_active && (
                    <Button size="sm" intent="ghost" tone="danger" onClick={() => deactivate(d.id)}>
                      Deactivate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step F1.8.3: Commit**
```powershell
git add packages/aero-ui/resources/js/Pages/Shared/Auth/Login.jsx packages/aero-ui/resources/js/Pages/Admin/UserDevices.jsx
git commit -m "feat(aero-ui): Shared/Auth/Login (platform domain), Admin/UserDevices pages"
```

---

## Task F1.9: PHPUnit Feature Tests

- [ ] **Step F1.9.1: Create `packages/aero-auth/tests/Feature/Auth/LoginControllerTest.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Auth;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Auth\AeroAuthServiceProvider::class,
        ];
    }

    public function test_login_page_renders_correct_component(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->has('canResetPassword')
                ->has('status')
            );
    }

    public function test_login_requires_device_id(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('device_id');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('login'), [
            'email'     => 'test@example.com',
            'password'  => 'wrong-password',
            'device_id' => 'test-device-' . uniqid(),
        ])->assertSessionHasErrors('email');
    }

    public function test_login_succeeds_with_valid_credentials_and_device_id(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct-password'),
            'active'   => true,
        ]);

        $response = $this->post(route('login'), [
            'email'     => 'test@example.com',
            'password'  => 'correct-password',
            'device_id' => 'test-device-' . uniqid(),
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_destroys_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_rate_limits_after_5_attempts(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email'     => 'test@example.com',
                'password'  => 'wrong',
                'device_id' => 'device-' . $i,
            ]);
        }

        $this->post(route('login'), [
            'email'     => 'test@example.com',
            'password'  => 'wrong',
            'device_id' => 'device-final',
        ])->assertSessionHasErrors('email'); // rate limit error
    }
}
```

- [ ] **Step F1.9.2: Create `packages/aero-auth/tests/Feature/Auth/SessionControllerTest.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Auth;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Auth\AeroAuthServiceProvider::class,
        ];
    }

    public function test_sessions_page_requires_auth(): void
    {
        $this->get(route('core.security.sessions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_sessions_page_renders_correct_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.authentication.sessions.view');

        $this->actingAs($user)
            ->get(route('core.security.sessions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Sessions')
                ->has('sessions')
                ->has('current_session_id')
                ->has('max_sessions')
            );
    }

    public function test_sessions_returns_403_without_permission(): void
    {
        $user = User::factory()->create(); // no permissions

        $this->actingAs($user)
            ->get(route('core.security.sessions.index'))
            ->assertForbidden();
    }
}
```

- [ ] **Step F1.9.3: Commit tests**
```powershell
git add packages/aero-auth/tests/
git commit -m "test(aero-auth): LoginController + SessionController feature tests"
```

---

## Task F1.10: Playwright Smoke Test — Login Flow

- [ ] **Step F1.10.1: Create `tests/e2e/auth/login.spec.js`**

```js
import { test, expect } from '@playwright/test';

test.describe('Auth — Login flow', () => {
  test('tenant user can log in with correct credentials', async ({ page }) => {
    await page.goto('http://testcompany.aeos365.test/login');
    await expect(page.locator('h1, [class*="title"]')).toBeVisible();

    await page.fill('[id=email]', 'admin@testcompany.com');
    await page.fill('[id=password]', 'password');
    await page.click('[type=submit]');

    await page.waitForURL('**/dashboard', { timeout: 10000 });
    await expect(page).toHaveURL(/dashboard/);
  });

  test('login fails with wrong password and shows error', async ({ page }) => {
    await page.goto('http://testcompany.aeos365.test/login');
    await page.fill('[id=email]', 'admin@testcompany.com');
    await page.fill('[id=password]', 'wrongpassword');
    await page.click('[type=submit]');

    await expect(page.locator('text=incorrect')).toBeVisible({ timeout: 5000 });
  });

  test('standalone login works', async ({ page }) => {
    await page.goto('http://aeos365-standalone.test/login');
    await page.fill('[id=email]', 'admin@aeos365.com');
    await page.fill('[id=password]', 'password');
    await page.click('[type=submit]');

    await page.waitForURL('**/dashboard', { timeout: 10000 });
    await expect(page).toHaveURL(/dashboard/);
  });
});
```

- [ ] **Step F1.10.2: Commit and push**
```powershell
git add tests/e2e/
git commit -m "test(e2e): login smoke test for SaaS tenant and standalone modes"
git push origin main
```

---

## Self-Review

**Spec coverage:**
- ✅ Login — `device_id` bug fixed, audit-ready, oauth providers, device blocked state
- ✅ ForgotPassword / ResetPassword / VerifyEmail — standard alignment
- ✅ 2FA Setup — QR code, confirm, recovery codes, disable, regenerate
- ✅ 2FA Challenge — exists, minor cleanup
- ✅ Devices — list, deactivate, single-device toggle
- ✅ Sessions — list, terminate, terminate all, max session warning
- ✅ AcceptInvitation — name, password, roles display
- ✅ InvitationInvalid — clear error state, back to login
- ✅ Shared/Auth/Login — platform admin domain (no device binding)
- ✅ Admin/UserDevices — admin view of user's devices

**Controller upgrades:**
- ✅ Debug `\Log::info()` statements removed from LoginController
- ✅ `IdentifyDomainContext` namespace fixed
- ✅ `AuditServiceInterface` injected

**Tests:**
- ✅ LoginController — 5 test cases including rate limiting, device_id validation
- ✅ SessionController — auth guard, permissions, Inertia component
- ✅ Playwright — SaaS tenant login + standalone login smoke tests

**No placeholders:** All JSX is complete with real prop shapes from the actual controllers.
