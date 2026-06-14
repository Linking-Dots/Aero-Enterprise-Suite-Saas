# UAT Plan — Launch-Blocking Journey: Landing → Signup → Tenant Dashboard

**Scope:** SaaS mode only — end-to-end Playwright E2E validation of the
`Landing Home → Tenant Provisioning/Signup → Tenant Dashboard` sequence in
`packages/aero-platform` + `packages/aero-ui`.

**Audience:** QA engineer + developer pairing. Each test task is runnable
independently. Debug steps are first-class, not afterthoughts.

---

## Preconditions (verify once before running any spec)

1. Laragon running (Apache + MySQL 8).
2. Hosts file has wildcard `*.aeos365.test` → `127.0.0.1` (Acrylic DNS or
   pre-seeded pool). `aeos365.test` itself must resolve.
3. `c:\laragon\www\Aero-Enterprise-Suite-Saas\e2e\.env` is populated from
   `.env.example`. Critical vars:

   ```
   SAAS_PLATFORM_URL=http://aeos365.test
   SAAS_TENANT_SUBDOMAIN=uatco
   SAAS_TENANT_URL=http://uatco.aeos365.test
   SAAS_HOST_PATH=c:/laragon/www/Aero-Enterprise-Suite-Saas
   UAT_PASSWORD=Password123!
   ```
4. UAT DB `aeos365_uat` exists.
5. UAT `.env.uat` files exist at each host path (SaaS host only; standalone
   host not needed for this plan).
6. `cd e2e && npm install && npx playwright install chromium` done (P0
   scaffold already committed).

### Quick pre-flight (run before every fresh suite)

```powershell
cd e2e
npx playwright test specs/p0-smoke --grep "saas P0 smoke" --headed
```

Expected: green (authenticated tenant dashboard loads, no `/login` redirect).
If red → stop and debug preconditions before proceeding.

---

## Stage Architecture

```
Stage A  Landing Home Page          (GET / on aeos365.test)
    │
    ▼
Stage B  Authenticated Signup Entry (GET /signup on aeos365.test)
    │
    ▼
Stage C  Multi-Step Wizard          7 sub-steps (C1–C7)
    │
    ▼
Stage D  Provisioning Waiting Room  (poll → redirect or fail)
    │
    ▼
Stage E  Admin Setup               (POST /admin-setup.store on tenant subdomain)
    │
    ▼
Stage F  Tenant Onboarding         (GET /onboarding on tenant subdomain)
    │
    ▼
Stage G  Tenant Dashboard          (GET / on tenant subdomain)
```

**Launch-blocking definition:** a failure at any stage after Stage A blocks
the entire signup funnel. All G–F failures are less critical (admin can
re-engage); C–D failures are launch blockers.

---

## 1  Granular Debugging Steps Per Stage

These are the *first things to do* when a test fails in a given stage.
Document every failure mode, capture screenshots + HAR, and check server logs.

### 1.1  Stage A — Landing Home Page

| Check | How | Pass criteria |
|-------|-----|---------------|
| HTTP 200 | `curl -I http://aeos365.test` | `200 OK` |
| No 500 error in body | `page.locator('body').textContent()` | No text matching `/Server Error\|Whoops\|SQLSTATE/i` |
| Hero rendering | `page.waitForSelector('h1, [class*="hero"], main', 15s)` | visible |
| CTA "Start free trial" | `page.locator('text=Start free trial').first().isVisible()` | visible (or alternative signup link) |
| Network clean | `page.on('response', r => { if (r.status() >= 400) fail })` | zero 4xx/5xx |
| Inertia loaded | `page.evaluate(() => !!window.__inertia)` | truthy (React shell mounted) |
| Console errors | `page.on('console', msg => msg.type() === 'error' && fail)` | zero |

**Debug cheat-sheet (insert after each failing check):**

```ts
// 1. Screenshot the failed state
await page.screenshot({ path: 'debug-stageA.png', fullPage: true });

// 2. HAR for the last 10 requests
const client = await page.context().newPage();
await client.route('**/*', route => route.continue());
// … actually use playwright HAR via context options in a retry:

// 3. Body excerpt
console.log(await page.locator('body').textContent()).then(t => t.substring(0, 500));

// 4. Server-side: tail the Laravel log
// powershell: Get-Content c:\laragon\www\Aero-Enterprise-Suite-Saas\storage\logs\*.log -Tail 30
```

---

### 1.2  Stage B — Signup Page Entry

| Check | How | Pass |
|-------|-----|------|
| URL | `page.url()` | ends with `/signup` |
| No `/login` shadow | `not toHaveURL(/\/login/)` | true |
| StepAccount renders | `page.locator('text=Company|Individual').first()` | visible |
| No server error in body | as stage A | none |

**Edge case:** `/login` on platform domain is currently shadowed by the
tenant login route (known issue, tracked). If the user hits `/login` from an
external link, they see a tenant login form — not an error. Assert "no
server error" only, not exact page.

---

### 1.3  Stage C — Multi-Step Wizard

Sub-steps and their individual checks:

#### C1  Account Type (`/signup` → `/signup/details`)

| Check | How | Pass |
|-------|-----|------|
| Company card selectable | `locator('text=Company').click()` | navigates to `/signup/details` within 5s |
| POST `/signup/account-type` returns 302 | intercept `response` for `**/account-type` | status 302 |
| Session persisted (cookie) | `page.context().cookies()` | platform session cookie present |

#### C2  Details (`/signup/details` → next)

| Check | How | Pass |
|-------|-----|------|
| Subdomain uniqueness rejected | submit `existing-subdomain` → expect error text | error mentions subdomain |
| Reserved name rejected | submit `admin`, `www`, `api` → error | rejected |
| Length rules enforced | submit 1-char and 64-char → respective errors | validated |
| Duplicate email rejected | submit same email twice → uniform message | "already in use" (no enumeration) |
| Valid submit → 302 | fill valid data, submit | 302 to next step |

**Debug on C2 failure:** intercept the POST to `/signup/details`, inspect
`response.json()` for Laravel validation errors. Check server log for
`SQLSTATE` constraint violations (unique index race).

#### C3  Email Verification (`/signup/verify-email`)

| Check | How | Pass |
|-------|-----|------|
| "Resend code" button visible | `locator('text=Resend')` | visible (rate-limited) |
| Code input accepts 6 digits | `fill('#code', '123456')` | accepted |
| Wrong code → error | submit `000000` | "invalid code" message |
| MAIL log readable | `Get-Content storage\logs\*.log \| Select-String "verification"` | code present |
| Auto-skip in UAT | if MAIL_MAILER=log → test that `POST /verify-email/send` returns code in log, then submit | passes |

**Note:** In UAT (`MAIL_MAILER=log`) there is no real email transport.
The app reads the verification code from `mail.log` or an inline log.
The test must extract it from the log, or use a bypass endpoint if one
exists. If no bypass: read Laravel log after `sendEmailVerification` call,
parse the 6-digit code, feed it back.

#### C4  Phone Verification (`/signup/verify-phone`)

| Check | How | Pass |
|-------|-----|------|
| Skip works | confirm step is optional (field in StepDetails has `optional` hint) | can proceed without phone |
| If phone provided → SMS log readable | similar to email, check log | code present |

**Edge case:** Phone can be skipped if not provided in C2. If provided,
rate-limit (10/min) applies.

#### C5  Plan & Modules (`/signup/plan` → next)

| Check | How | Pass |
|-------|-----|------|
| Plans rendered | `locator('[class*="plan"], button').filter({ hasText: /Free\|Starter/ })` | count ≥ 1 |
| Module picker | `locator('text=Human Resources\|HRM')` | visible, toggleable |
| Module pricing displayed | `locator('text=/\\$|Included/')` | at least one price or "Included" |
| Submit → 302 | select plan + module, submit | 302 to `/signup/payment` or `/signup/byoc` |

#### C6  BYOC (`/signup/byoc`)

| Check | How | Pass |
|-------|-----|------|
| Page renders | `page.url()` includes `/byoc` | yes |
| "Use default" skip button | `locator('text=Use default\|Skip\|Continue')` | visible, clickable |
| Skip → 302 | click skip | 302 to `/signup/payment` |
| Test connection works | fill creds, click "Test" → success toast | connection OK |

**Critical:** BYOC is REQUIRED. The test must click through this step,
even if skipping (the skip button IS the BYOC submission).

#### C7  Payment/Trial (`/signup/payment` → trial activation)

| Check | How | Pass |
|-------|-----|------|
| Plan summary visible | plan name, modules, trial end date | all rendered |
| "Start Free Trial" button | `locator('text=Start Free Trial')` | visible, enabled |
| POST `/signup/trial` → 302 | click button | 302 to `/signup/provisioning/{id}` |
| Rate-limit | attempt >5 rapid clicks → 429 or disabled | enforced (5/hr throttle) |

---

### 1.4  Stage D — Provisioning Waiting Room

| Check | How | Pass |
|-------|-----|------|
| Polling endpoint 200 | `GET /signup/provisioning/{id}/status` | JSON with `is_ready` or `has_failed` |
| Step progression | `STEP_KEYS` order: creating_db → migrating → seeding → creating_admin | each step visible in order |
| Completion → redirect | `is_ready=true && !needs_admin_setup` → router to `/signup/success` | redirects |
| Completion → admin-setup | `is_ready=true && needs_admin_setup` → `window.location` to tenant domain | cross-domain redirect works |
| Failure → retry UI | `has_failed=true` → error Alert + Retry button | visible |
| Retry POST 200 | click retry → `POST /signup/provisioning/{id}/retry` | 200, re-starts polling |

**Timeout:** provisioning must complete within 90s on local Laragon.
If `has_failed` is true, dump `pollData.error` + server log.

**Debug on provisioning failure:**

```ts
// 1. Capture pollData
console.log('pollData:', JSON.stringify(pollData));

// 2. Check tenant status via tinker
// powershell: cd <host>; php artisan tinker --execute="print_r(\Aero\Platform\Models\Tenant::where('subdomain','uatco')->first()->toArray())"

// 3. Check tenant DB exists
// powershell: mysql -uroot -e "SHOW DATABASES LIKE '%uatco%'"

// 4. Tail provision log
// powershell: Get-Content storage\logs\*.log -Tail 50 | Select-String "provision|exception|error"
```

---

### 1.5  Stage E — Admin Setup (`/{subdomain}.aeos365.test/admin-setup`)

| Check | How | Pass |
|-------|-----|------|
| Redirect from login | if no admin exists, `/login` → 302 `/admin-setup` | RedirectIfNoAdmin middleware |
| Form fields | name, user_name, email, phone, password, confirm | all visible |
| Validation | submit empty form → field errors | each field error present |
| Weak password rejected | password "123" → error | rejected |
| Mismatched confirm rejected | different confirm → error | rejected |
| Valid submit → 302 | fill all fields, submit | 302 to `/` (dashboard) or `/onboarding` |
| Admin record created | check DB `users` table on tenant DB | 1 admin user |

**Critical:** admin_setup_completed flag is set in `tenant.data` JSON column.
Check: `tenant.data.admin_setup_completed === true` in central DB.

---

### 1.6  Stage F — Tenant Onboarding (`/onboarding`)

| Check | How | Pass |
|-------|-----|------|
| Middleware enforces | if `!admin_setup_completed` → redirect to `/admin-setup` | EnsureTenantIsSetup |
| Middleware enforces | if `!onboarding.completed` → redirect to `/onboarding` | same middleware |
| Wizard renders | steps visible (welcome → ... → complete) | at least welcome step |
| Skip path | "Skip for now" button → proceeds to dashboard | skips onboarding |
| Complete path | walk through steps → `onboarding.completed = true` | redirects to dashboard |

**Debug:** check `tenant.data.onboarding` JSON in central DB.

---

### 1.7  Stage G — Tenant Dashboard

| Check | How | Pass |
|-------|-----|------|
| URL | `page.url()` | `http://uatco.aeos365.test/` (or `/dashboard`) |
| Not redirected to `/login` | `not toHaveURL(/\/login/)` | true |
| Not redirected to `/admin-setup` | `not.toHaveURL(/\/admin-setup/)` | true |
| Not redirected to `/onboarding` | `not.toHaveURL(/\/onboarding/)` | true |
| Welcome banner | `text=Welcome` + admin name | visible |
| KPIs render | `locator('[class*="kpi"]')` or KPI labels | count ≥ 1 |
| Quick actions | "Manage Users", "Manage Roles" links | visible |
| System healthy badge | `text=System healthy` | visible |
| No server error | body text check | none |
| No JS console errors | `page.on('console', ...)` | zero errors |
| No failed network requests | `page.on('response', status >= 400)` | zero |

---

## 2  Edge-Case-Driven UAT Scenarios

### 2.1  Happy Path (Full Flow)

**ID:** `EP-001`
**Tags:** `@saas`
**Goal:** Complete the entire sequence in one continuous session.

1. Navigate to `http://aeos365.test` → verify landing page (Stage A).
2. Click "Start free trial" or navigate to `/signup` → verify signup page (Stage B).
3. Select **Company** account type → verify redirect to `/signup/details`.
4. Fill valid details with fresh unique subdomain `uatco-ep001` → submit → verify next step.
5. Complete **email verification** using code from Laravel log → submit.
6. **Skip phone** (leave field empty) → submit.
7. Select **free trial plan** + **HRM module** → submit → verify `/signup/payment`.
8. Click **"Start Free Trial"** → verify redirect to provisioning.
9. Wait for provisioning complete → verify redirect to admin-setup on tenant subdomain.
10. Fill admin setup form → submit → verify redirect to `/onboarding`.
11. Skip onboarding → verify redirect to tenant dashboard.
12. Assert dashboard loads with no errors, welcome banner present.

**Teardown:** clean up tenant `uatco-ep001` via tinker or tenant list.

---

### 2.2  Edge Case: Subdomain Collision & Reserved Names

**ID:** `EP-002`
**Tags:** `@saas`
**Goal:** Verify subdomain validation is airtight.

| # | Subdomain | Expected |
|---|-----------|----------|
| 1 | `admin` | Rejected — reserved |
| 2 | `www` | Rejected — reserved |
| 3 | `api` | Rejected — reserved |
| 4 | `a` | Rejected — too short |
| 5 | `ab` | Rejected — too short |
| 6 | `a`.repeat(64) | Rejected — too long |
| 7 | `uatco` (existing UAT tenant) | Rejected — already taken |
| 8 | `valid-uat-ep002-${ts}` | Accepted |

---

### 2.3  Edge Case: Cross-Domain Session Persistence

**ID:** `EP-003`
**Tags:** `@saas`
**Goal:** Verify session cookies survive the platform → tenant subdomain redirect.

1. Complete signup through trial activation (through `/signup/provisioning/{id}`).
2. After cross-domain redirect to `https://uatco-ep003.aeos365.test/admin-setup`,
   verify the form renders without bouncing to `/login`.
3. If the session IS lost: assert the error message is user-friendly ("session
   expired, please retry") — not a raw exception page.
4. Check cookie domain: `SESSION` cookie domain must be `.aeos365.test`
   (leading dot) so it presents on both `aeos365.test` and
   `*.aeos365.test`.

**Debug:** `page.context().cookies()` → filter for `name=laravel_session`
→ check `domain` field.

---

### 2.4  Edge Case: Provisioning Timeout & Retry

**ID:** `EP-004`
**Tags:** `@saas`
**Goal:** Verify the retry mechanism works when provisioning fails.

**Approach A (forced failure):**
1. Create a tenant via wizard, but before completion, corrupt its `data`
   column in central DB to remove the HRM module reference.
2. Provisioning should fail → retry → fix data → retry succeeds.

**Approach B (simulated):**
1. Mock the provisioning endpoint to return 500 on first call (intercept).
2. Verify the UI shows "Provisioning failed" with Retry button.
3. Un-mock → click Retry → verify success.

**Approach A is preferred** (tests real code path).

---

### 2.5  Edge Case: Email Verification Without SMTP (UAT log mode)

**ID:** `EP-005`
**Tags:** `@saas`
**Goal:** Verify email verification works when `MAIL_MAILER=log`.

1. Enter email → click "Send code".
2. Read Laravel log: `Get-Content storage\logs\*.log -Tail 5`.
3. Extract the 6-digit verification code from the log message.
4. Submit the code → verify pass.
5. Submit wrong code first → verify error, then correct code.

**Debug if log code is unreadable:** check `mail.log` directly; verify the
`verification` event is dispatched.

---

### 2.6  Edge Case: Double-Submit / Idempotency

**ID:** `EP-006`
**Tags:** `@saas`
**Goal:** Rapid double-clicks on key actions don't create duplicate state.

| Action | Test | Expected |
|--------|------|----------|
| Account type select | double-click Company card rapidly | 1 tenant created, not 2 |
| Trial activation | rapid-fire click "Start Free Trial" 3x | 1 provisioning job, rate-limit on 3rd+ |
| Provisioning retry | click Retry 3x rapidly | 1 retry request, button disabled during |

**Assert check:** after double-click, assert the DB has exactly 1 tenant with
the given subdomain.

---

### 2.7  Edge Case: Deep Link / Direct URL Access

**ID:** `EP-007`
**Tags:** `@saas`
**Goal:** Users who deep-link into mid-wizard aren't broken.

| URL | Expected |
|-----|----------|
| `/signup/details` (no session) | 302 → `/signup` (restart) |
| `/signup/plan` (no saved data) | 302 → `/signup` or error page |
| `/signup/payment` (no saved data) | 302 → `/signup` |
| `/signup/provisioning/999999` (bad ID) | 404 or friendly "not found" |
| `/{invalid-subdomain}.aeos365.test/` | 404 or tenant-not-found page |

---

### 2.8  Edge Case: Navigation & Back-Button

**ID:** `EP-008`
**Tags:** `@saas`
**Goal:** Browser back/forward doesn't corrupt wizard state.

1. Progress to payment step.
2. Click browser back → should return to plan step with selections intact.
3. Click forward → return to payment step with summary intact.
4. Navigate away from wizard to external URL → back via browser → wizard
   state preserved (Inertia savedData).

---

## 3  Structured Testing Framework

### 3.1  File Layout

```
e2e/
├── specs/
│   └── p1-lifecycle-signup/
│       ├── journey.saas.spec.ts        # EP-001 happy path (single test)
│       ├── edge-subdomains.saas.spec.ts # EP-002
│       ├── edge-session.saas.spec.ts   # EP-003
│       ├── edge-provisioning.saas.spec.ts # EP-004
│       ├── edge-email-log.saas.spec.ts # EP-005
│       ├── edge-idempotency.saas.spec.ts # EP-006
│       └── edge-deeplink.saas.spec.ts  # EP-007 + EP-008
├── pages/
│   ├── BasePage.ts                      # ✓ EXISTS
│   ├── auth/
│   │   ├── LoginPage.ts                 # ✓ EXISTS
│   │   ├── InstallWizardPage.ts         # (not used here)
│   │   └── RegistrationPage.ts          # ← CREATE (wizard driver)
│   └── tenant/
│       ├── AdminSetupPage.ts            # ← CREATE
│       ├── OnboardingPage.ts            # ← CREATE
│       └── TenantDashboardPage.ts       # ← CREATE
└── support/
    ├── env.ts                           # ✓ EXISTS
    ├── artisan.ts                       # ✓ EXISTS
    ├── provision-tenant.ts              # ✓ EXISTS
    ├── storage-state.ts                 # ✓ EXISTS
    ├── dns.ts                           # ✓ EXISTS
    ├── fixtures/
    │   └── roles.ts                     # ✓ EXISTS
    └── mail-log.ts                      # ← CREATE (read UAT mail log)
```

### 3.2  Page Object Model (POM) — New Files

#### `pages/auth/RegistrationPage.ts`

```ts
import { type Page, type Locator, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';
import { ENV } from '../../support/env.ts';

export class RegistrationPage extends BasePage {
  constructor(page: Page) { super(page); }

  async goto() {
    await this.page.goto(`${ENV.saasPlatformUrl}/signup`, { waitUntil: 'networkidle' });
    return this;
  }

  async selectAccountType(type: 'company' | 'individual') {
    const card = this.page.locator(`text=${type}`).first();
    await card.click();
    await this.page.waitForURL('**/signup/details', { timeout: 5000 }).catch(() => {});
    return this;
  }

  async fillDetails(data: {
    name: string; email: string; phone?: string; subdomain: string;
  }) {
    await this.page.fill('#name', data.name);
    await this.page.fill('#email', data.email);
    if (data.phone) await this.page.fill('#phone', data.phone);
    await this.page.fill('#subdomain', data.subdomain);
    await this.page.click('button[type=submit]');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async expectDetailsError(text: RegExp | string) {
    await expect(this.page.locator('[class*="error"], [role="alert"]').first())
      .toContainText(text, { timeout: 5000 });
  }

  // Email verification
  async submitEmailVerification(code: string) {
    await this.page.fill('#code', code);
    await this.page.click('button[type=submit]');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  // Plan selection
  async selectPlan(planName: string) {
    await this.page.locator(`button, [class*="plan"]`).filter({ hasText: new RegExp(planName, 'i') }).first().click();
    return this;
  }

  async selectModule(moduleName: string) {
    await this.page.locator(`button`).filter({ hasText: new RegExp(moduleName, 'i') }).first().click();
    return this;
  }

  async submitPlan() {
    await this.page.click('button:has-text("Continue to Payment")');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  // BYOC
  async skipByoc() {
    const skipBtn = this.page.locator('button').filter({ hasText: /Skip|Use Default|Continue/ }).first();
    if (await skipBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await skipBtn.click();
      await this.page.waitForLoadState('networkidle');
    }
    return this;
  }

  // Trial activation
  async startTrial() {
    await this.page.click('button:has-text("Start Free Trial")');
    await this.page.waitForURL('**/provisioning/**', { timeout: 15000 }).catch(() => {});
    return this;
  }

  // Provisioning
  async waitForProvisioning(timeout = 90000) {
    await this.page.waitForURL('**/admin-setup', { timeout });
    return this;
  }

  async expectProvisioningFailed() {
    await expect(this.page.locator('text=Provisioning failed')).toBeVisible({ timeout: 60000 });
  }
}
```

#### `pages/tenant/AdminSetupPage.ts`

```ts
import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class AdminSetupPage extends BasePage {
  constructor(page: Page) { super(page); }

  async fillForm(data: {
    name: string; userName: string; email: string;
    password: string; phone?: string;
  }) {
    await this.page.fill('#name', data.name);
    await this.page.fill('#user_name', data.userName);
    await this.page.fill('#email', data.email);
    if (data.phone) await this.page.fill('#phone', data.phone);
    await this.page.fill('#password', data.password);
    await this.page.fill('#password_confirmation', data.password);
    return this;
  }

  async submit() {
    await this.page.click('button:has-text("Complete Setup")');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async expectAdminSetupComplete() {
    await expect(this.page).not.toHaveURL(/\/admin-setup/);
  }
}
```

#### `pages/tenant/OnboardingPage.ts`

```ts
import { type Page } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class OnboardingPage extends BasePage {
  constructor(page: Page) { super(page); }

  async skip() {
    const skipBtn = this.page.locator('button').filter({ hasText: /Skip|Complete|Finish/ }).first();
    if (await skipBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await skipBtn.click();
      await this.page.waitForLoadState('networkidle');
    }
    return this;
  }

  async expectOnboardingDone() {
    await expect(this.page).not.toHaveURL(/\/onboarding/);
  }
}
```

#### `pages/tenant/TenantDashboardPage.ts`

```ts
import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class TenantDashboardPage extends BasePage {
  constructor(page: Page) { super(page); }

  async goto() {
    await this.page.goto('/', { waitUntil: 'networkidle' });
    return this;
  }

  async expectDashboardLoaded() {
    await expect(this.page).not.toHaveURL(/\/login\b/);
    await expect(this.page).not.toHaveURL(/\/admin-setup/);
    await expect(this.page).not.toHaveURL(/\/onboarding/);
    await expect(this.page.locator('body')).not.toContainText(/Server Error|SQLSTATE|Whoops/i);
  }

  async expectWelcomeBanner() {
    await expect(this.page.locator('text=Welcome').first()).toBeVisible();
  }

  async expectQuickActions() {
    await expect(this.page.locator('text=Manage Users').first()).toBeVisible();
  }
}
```

### 3.3  Support Helper — UAT Mail Log Reader

#### `support/mail-log.ts`

```ts
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
import { ENV } from './env.ts';

/**
 * Tail the Laravel log and extract the most recent N-digit verification code
 * that was "sent" to the given recipient.
 *
 * Works with MAIL_MAILER=log (UAT) only. Reads storage/logs/laravel.log.
 */
export function extractVerificationCode(
  logPath: string,
  recipient: string,
  digits = 6,
  tailLines = 50,
): string {
  const log = execFileSync(
    'powershell',
    [
      '-Command',
      `Get-Content "${logPath}" -Tail ${tailLines} | Select-String "verification|code|${recipient}"`,
    ],
    { encoding: 'utf8', encoding: 'utf8' },
  );
  const match = log.match(/\b(\d{6})\b/);
  if (!match) throw new Error(`No ${digits}-digit code found in log for ${recipient}. Log tail:\n${log}`);
  return match[1];
}

export function getLatestLaravelLog(hostPath: string): string {
  const logsDir = join(hostPath, 'storage', 'logs');
  const latest = execFileSync(
    'powershell',
    ['-Command', `Get-ChildItem "${logsDir}\\laravel*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName`],
    { encoding: 'utf8' },
  ).trim();
  return latest;
}
```

### 3.4  Spec Skeleton — `specs/p1-lifecycle-signup/journey.saas.spec.ts`

```ts
import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { AdminSetupPage } from '../../pages/tenant/AdminSetupPage.ts';
import { OnboardingPage } from '../../pages/tenant/OnboardingPage.ts';
import { TenantDashboardPage } from '../../pages/tenant/TenantDashboardPage.ts';
import { extractVerificationCode, getLatestLaravelLog } from '../../support/mail-log.ts';

const UNIQUE = Date.now();

test.describe('@saas EP-001: Happy path — signup to dashboard', () => {
  test('Landing → Signup → Wizard BYOC Trial → AdminSetup → Onboarding → Dashboard', async ({ page }) => {
    // ─── Stage A: Landing ─────────────────────────────────────────────
    const landing = new RegistrationPage(page); // reuses goto signup
    await page.goto(ENV.saasPlatformUrl, { waitUntil: 'networkidle' });
    await expect(page.locator('body')).not.toContainText(/Server Error|Whoops/i);
    await expect(page.locator('h1, [class*="hero"], main').first()).toBeVisible({ timeout: 15000 });

    // ─── Stage B: Signup entry ────────────────────────────────────────
    await landing.goto();
    await expect(page).toHaveURL(/\/signup/);
    await expect(page.locator('text=Company|Individual').first()).toBeVisible();

    // ─── Stage C1-C2: Account + Details ───────────────────────────────
    const subdomain = `uat-ep001-${UNIQUE}`;
    await landing.selectAccountType('company');
    await landing.fillDetails({
      name: 'UAT Demo Corp',
      email: `demo.${UNIQUE}@uatco.test`,
      phone: '+15551234567',
      subdomain,
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/);

    // ─── Stage C3: Email verification (UAT log mode) ─────────────────
    // Send code trigger (may auto-advance or require manual step)
    // ... adapt based on actual wizard behavior

    // ─── Stage C5: Plan ───────────────────────────────────────────────
    await landing.selectPlan('Free');
    await landing.selectModule('Human Resources');
    await landing.submitPlan();

    // ─── Stage C6: BYOC ───────────────────────────────────────────────
    await landing.skipByoc();

    // ─── Stage C7: Trial activation ───────────────────────────────────
    await landing.startTrial();

    // ─── Stage D: Provisioning ────────────────────────────────────────
    await landing.waitForProvisioning(90000);

    // ─── Stage E: Admin Setup ─────────────────────────────────────────
    const adminSetup = new AdminSetupPage(page);
    await adminSetup.fillForm({
      name: 'Demo Admin',
      userName: `demo_admin_${UNIQUE}`,
      email: `admin.${UNIQUE}@testcorp.test`,
      password: ENV.password,
    });
    await adminSetup.submit();
    await adminSetup.expectAdminSetupComplete();

    // ─── Stage F: Onboarding ──────────────────────────────────────────
    const onboarding = new OnboardingPage(page);
    await onboarding.skip();
    await onboarding.expectOnboardingDone();

    // ─── Stage G: Dashboard ───────────────────────────────────────────
    const dashboard = new TenantDashboardPage(page);
    await dashboard.expectDashboardLoaded();
    await dashboard.expectWelcomeBanner();
    await dashboard.expectQuickActions();
  });
});
```

### 3.5  Shared Fixtures & Utilities

The existing `fixtures/roles.ts` + `support/storage-state.ts` cover
authenticated role scenarios. For the signup journey, **no pre-existing
storageState is needed** — the test drives a fresh browser context through
the public flow. This is intentional: the journey tests the unauthenticated
path which is the primary launch-risk surface.

Add to `support/env.ts` — new UAT-only paths:

```ts
// e2e/.env additions:
// SAAS_HOST_LOG_PATH = c:/laragon/www/Aero-Enterprise-Suite-Saas/storage/logs
// (already derivable from SAAS_HOST_PATH, but explicit for clarity)
```

---

## 4  Execution Sequence (recommended run order)

```
1. P0 smoke                     (already green — gate check)
2. EP-001 Happy path            (the one that must pass for launch)
3. EP-002 Subdomain edge cases  (fast, no provisioning)
4. EP-003 Session cross-domain  (depends on EP-001 setup, or standalone subdomain check)
5. EP-005 Email log UAT         (fast, no provisioning)
6. EP-006 Idempotency           (depends on EP-001 subdomain logic)
7. EP-007 Deep links            (fast, no provisioning)
8. EP-004 Provisioning retry    (destructive mock, run last of signup)
```

Tags: `@saas` all of these (standalone not in scope for this plan).
`EP-004` may carry `@destructive` if it corrupts tenant data.

---

## 5  Known High-Risk Areas (priority order)

| Risk | Likely cause | Mitigation in plan |
|------|-------------|-------------------|
| Subdomain DNS wildcard | Acrylic/Apache vhost misconfig | DNS preflight in global-setup + Stage A check |
| Provisioning FK / collation | `aero-platform fresh-install` issues (tracked) | EP-004 retry + 90s timeout + server log capture |
| Cross-domain session loss | Cookie domain mismatch | EP-003 explicit cookie domain check |
| Email verification in UAT (log mode) | No SMTP → code invisible | EP-005 + `mail-log.ts` helper |
| BYOC step blocking | DB connection validation hanging | BYOC skip button must be wired; assert 5s timeout |
| Inertia request serialization | Big form state lost on cross-domain | EP-003 + EP-008 back-button test |
| Tenant DB not dropped on cleanup | Test suite leaves stale DBs | Teardown helper drops test tenant DB after each spec |

---

## 6  Teardown Pattern (per spec)

Each spec that creates a real tenant must clean up:

```ts
test.afterAll(async () => {
  const subdomain = test.info().annotations.find(a => a.type === 'subdomain')?.description;
  if (subdomain) {
    execFileSync('php', [
      'artisan', 'tinker', '--execute',
      `$t=\\Aero\\Platform\\Models\\Tenant::where('subdomain','${subdomain}')->first();`
        + ` if($t){ \\DB::statement("DROP DATABASE IF EXISTS `{$t->database()->getName()}`"); $t->forceDelete(); }`,
    ], { cwd: ENV.saasHostPath, encoding: 'utf8' });
  }
});
```

Store the subdomain as a test annotation at test creation time so teardown
can find it even if the test fails mid-flow.

---

## 7  Success Criteria (Launch Gate)

- [ ] `EP-001` passes **3 consecutive runs** without flake.
- [ ] `EP-002–007` all pass at least once clean.
- [ ] Zero `Server Error|SQLSTATE|Whoops` pages observed in any passing run.
- [ ] Provisioning completes ≤ 90s in local dev environment.
- [ ] All debug steps documented with expected log output patterns.
- [ ] TEARDOWN verified: created tenants fully removed from central + tenant DBs.
