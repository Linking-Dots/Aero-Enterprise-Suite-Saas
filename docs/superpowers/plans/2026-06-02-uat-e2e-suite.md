# UAT End-to-End Test Suite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an exhaustive, repeatable Playwright E2E suite that validates the whole AEOS365 system (auth, tenant lifecycle/installation, platform admin, billing, and every HRM submodule) in **both** deployment modes — SaaS and Standalone — against the live Laragon servers, backed by a deterministic Laravel `UatSeeder`.

**Architecture:** A single top-level `e2e/` Playwright project in the monorepo with its own `package.json`. Two Playwright `projects` (`saas`, `standalone`) share HRM flows authored once as Page Objects (`e2e/pages/hrm/*`). A `global-setup.ts` swaps each host to a dedicated UAT `.env` (UAT databases — never dev data), runs `migrate:fresh --seed`, provisions a known SaaS test tenant synchronously, and captures a `storageState` per role via one real UI login each. `global-teardown.ts` restores the original `.env` files. Tags (`@saas`, `@standalone`, `@billing`, `@destructive`) gate mode/Stripe/order. The spec source of truth is `docs/superpowers/specs/2026-06-01-uat-e2e-design.md`.

**Tech Stack:** Playwright `@playwright/test` ^1.59 (TypeScript), Node 18+, Laravel 12 artisan (`migrate:fresh`, `db:seed`), PHP 8.3, MySQL (Laragon), Inertia v2 + React 18 UI under test.

---

## Reference facts (verified against the codebase — do not re-derive)

**Hosts & databases**

| Mode | Host path | Dev DB | UAT DB | URL | Session domain |
|---|---|---|---|---|---|
| SaaS | `c:\laragon\www\aeos365` | `eos365` | `aeos365_uat` (central) + provisioned tenant DB | platform `http://aeos365.test`, tenant `http://uatco.aeos365.test`, admin `http://admin.aeos365.test` | `.aeos365.test` |
| Standalone | `c:\laragon\www\aeos365-standalone` | `aeos_standalone` | `aeos365_standalone_uat` | `http://aeos365-standalone.test` | (host default) |

- `QUEUE_CONNECTION=sync` in both → `ProvisionTenant::dispatchSync($tenant)` runs inline.
- SaaS `SESSION_DOMAIN=.aeos365.test`; subdomains share cookies. Wildcard `*.aeos365.test` Laragon vhost required.
- Standalone `.env` currently `APP_URL=http://localhost` → UAT env sets `http://aeos365-standalone.test`.

**Mode / installed markers** (`packages/aero-core/src/helpers.php`, `FinalizeStep.php`, `BootstrapGuard.php`)
- Mode file: `storage/app/aeos.mode` containing literal `saas` or `standalone`.
- Installed flag file: `storage/app/aeos.installed`.
- Standalone `BootstrapGuard` redirects to `/install` when `aeos.installed` is absent AND the platform package is not installed. SaaS has the platform package → never redirects to `/install`.

**Provisioning** (`packages/aero-platform/src/Jobs/ProvisionTenant.php`)
- `new ProvisionTenant($tenant)` then `::dispatchSync($tenant)`. Steps: validate → create DB → migrate (plan modules) → sync module hierarchy → seed 4 default roles (Super Administrator, Administrator, HR Manager, Employee) + grant `role_module_access` → activate.
- Admin user is **NOT** created by the job — it is created post-provision on the tenant domain. The UAT seeder must create the tenant admin/HR/employee users itself.
- Requires: `tenant->subdomain`, ≥1 domain row, `tenant->data['plan_id']` with modules (HRM).

**Routes**
- Install (both modes, unified): prefix `install`, names `installation.*` (+ legacy `install.*`). Pages: `/install`, `/install/license`, `/install/requirements`, `/install/database`, `/install/settings`, `/install/admin`, `/install/review`, `/install/processing`, `/install/complete`. POSTs: `/install/save-database`, `/install/save-admin`, `/install/execute`, `/install/progress`. (`packages/aero-installation/routes/installation.php`)
- SaaS signup (platform domain): prefix `signup`, names `platform.register.*`. `/signup`, `/signup/details`, `/signup/verify-email`, `/signup/verify-phone`, `/signup/plan`, `/signup/payment`, `/signup/byoc`, `/signup/provisioning/{tenant}`. `/login` 302→`/signup` on platform domain. (`packages/aero-platform/routes/web.php`)
- Tenant auth (subdomain): `login`, `logout`, `password.request` (`/forgot-password`), `password.reset` (`/reset-password/{token}`), `auth.two-factor.*`. Login page hidden field `device_id` is a UUID v4 auto-populated by React. (`packages/aero-auth/routes/tenant.php`)
- Admin/landlord auth: `/login`, `/logout` on the admin domain. (`packages/aero-auth/routes/admin.php`)
- Platform admin: `packages/aero-platform/routes/admin.php` (tenant mgmt, catalog, billing, observability).
- HRM: prefix `/hrm/...` (e.g. `/hrm/employees`, `/hrm/leave`, `/hrm/payroll`).

**Seeders** (existing, reusable)
- `Aero\HRM\Database\Seeders\HrmDemoSeeder` aggregates: Department, Designation, LeaveType, Holiday, ExpenseCategory, AssetCategory, TrainingCategory, GrievanceCategory, DisciplinaryActionType, ShiftSchedule, Skill, Grade, SalaryComponent.
- `Aero\Core\Database\Seeders\RoleSeeder`, `RoleModuleAccessSeeder`, `AdminUserSeeder`.

**Existing Playwright remnants** (to be superseded, then deleted in P0)
- `tests/e2e/*.spec.js` (monorepo root) and `c:\laragon\www\aeos365\e2e\theme-regression.spec.js` + its `playwright.config.js`. The new suite lives in `e2e/` at the monorepo root; the old root `tests/e2e/` is removed.

---

## File structure

```
e2e/                                     # NEW — monorepo-root Playwright project
  package.json                           # @playwright/test, dotenv, tsx
  playwright.config.ts                   # projects: saas, standalone; globalSetup/Teardown
  tsconfig.json
  .env.example                           # documented vars (URLs, role creds, Stripe)
  .env                                   # gitignored — operator's local values
  .gitignore                             # node_modules, .auth, test-results, playwright-report
  README.md                              # prereqs + how to run
  global-setup.ts                        # env-swap + migrate:fresh --seed + provision + storageState
  global-teardown.ts                     # restore original .env files
  support/
    env.ts                               # typed env loader (URLs, creds, flags)
    artisan.ts                           # run host artisan with a given --env
    provision-tenant.ts                  # PHP one-liner to create+provision UAT tenant
    storage-state.ts                     # UI login → save .auth/<role>.<mode>.json
    dns.ts                               # assert *.aeos365.test + standalone vhost resolve
  fixtures/
    roles.ts                             # role ids, emails, storageState paths
    test-data.ts                         # deterministic ids/names used by seeder + specs
  pages/
    BasePage.ts                          # goto/expectNoError/toast/table helpers
    auth/
      LoginPage.ts
      InstallWizardPage.ts               # standalone installer
      RegistrationPage.ts                # SaaS signup
    hrm/
      EmployeesPage.ts
      DepartmentsPage.ts  DesignationsPage.ts
      AttendancePage.ts   LeavePage.ts   PayrollPage.ts
      RecruitmentPage.ts  TrainingPage.ts PerformancePage.ts
      DisciplinaryPage.ts SafetyPage.ts   AssetsPage.ts
      ExpensesPage.ts     BenefitsPage.ts  SuccessionPage.ts
      MiscPage.ts         SelfServicePage.ts
    platform/
      TenantsPage.ts  CatalogPage.ts  ObservabilityPage.ts  LandlordPage.ts
    billing/
      BillingPage.ts
  specs/
    p1-lifecycle-auth/
      install.standalone.spec.ts         # @standalone @destructive
      tenant-lifecycle.saas.spec.ts      # @saas (suspend/forget are @destructive)
      auth-sessions.spec.ts              # @saas @standalone
      hrmac.spec.ts                      # @saas @standalone
    p2-hrm-core/
      employees.spec.ts  departments-designations.spec.ts
      attendance.spec.ts  leave.spec.ts  payroll.spec.ts
    p3-hrm-remainder/
      recruitment.spec.ts training.spec.ts performance.spec.ts
      disciplinary.spec.ts safety.spec.ts assets.spec.ts
      expenses.spec.ts benefits.spec.ts succession.spec.ts
      misc.spec.ts self-service.spec.ts
    p4-platform-billing/
      tenant-management.saas.spec.ts     # @saas (forget/archive @destructive)
      catalog.saas.spec.ts               # @saas
      billing.saas.spec.ts               # @saas @billing
      observability.saas.spec.ts         # @saas
      landlord.saas.spec.ts              # @saas

aeos365/database/seeders/UatSeeder.php           # NEW — tenant/HRM dataset (SaaS tenant DB)
aeos365/database/seeders/UatPlatformSeeder.php   # NEW — central landlord/plans/products
aeos365/.env.uat                                 # NEW — SaaS UAT env (aeos365_uat)
aeos365-standalone/database/seeders/UatSeeder.php # NEW — same dataset, standalone DB
aeos365-standalone/.env.uat                      # NEW — standalone UAT env

.github/workflows/uat-e2e.yml                    # P5 — CI job
```

`UatSeeder.php` is authored once (canonical copy under `packages/aero-hrm/database/seeders/` is **not** used because seeders must be host-discoverable in `Database\Seeders`); the SaaS and standalone host copies are byte-identical. A P0 task copies the canonical file to both hosts.

---

## Phase P0 — Foundation

Nothing runs without P0. Deliverable: `npx playwright test --project=standalone specs/p0-smoke` (a trivial smoke spec) green in both projects, with `storageState` files minted for every role.

### Task 0.1: Scaffold the `e2e/` Playwright project

**Files:**
- Create: `e2e/package.json`
- Create: `e2e/tsconfig.json`
- Create: `e2e/.gitignore`

- [ ] **Step 1: Create `e2e/package.json`**

```json
{
  "name": "aeos365-e2e",
  "private": true,
  "type": "module",
  "scripts": {
    "test": "playwright test",
    "test:saas": "playwright test --project=saas",
    "test:standalone": "playwright test --project=standalone",
    "test:headed": "playwright test --headed",
    "report": "playwright show-report"
  },
  "devDependencies": {
    "@playwright/test": "^1.59.1",
    "dotenv": "^16.4.5",
    "tsx": "^4.19.0",
    "typescript": "^5.6.0"
  }
}
```

- [ ] **Step 2: Create `e2e/tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "ESNext",
    "moduleResolution": "Bundler",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "types": ["node"]
  },
  "include": ["**/*.ts"]
}
```

- [ ] **Step 3: Create `e2e/.gitignore`**

```
node_modules/
.auth/
test-results/
playwright-report/
.env
*.env.bak.uat
```

- [ ] **Step 4: Install deps + Chromium**

Run: `cd c:\laragon\www\Aero-Enterprise-Suite-Saas\e2e && npm install && npx playwright install chromium`
Expected: dependencies installed; Chromium downloaded (or "is already installed").

- [ ] **Step 5: Commit**

```bash
git add e2e/package.json e2e/tsconfig.json e2e/.gitignore
git commit -m "test(e2e): scaffold monorepo Playwright project

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.2: Environment config + typed loader

**Files:**
- Create: `e2e/.env.example`
- Create: `e2e/support/env.ts`

- [ ] **Step 1: Create `e2e/.env.example`**

```bash
# --- URLs (must resolve via Laragon) ---
SAAS_PLATFORM_URL=http://aeos365.test
SAAS_ADMIN_URL=http://admin.aeos365.test
SAAS_TENANT_SUBDOMAIN=uatco
SAAS_TENANT_URL=http://uatco.aeos365.test
STANDALONE_URL=http://aeos365-standalone.test

# --- Host paths (artisan targets) ---
SAAS_HOST_PATH=c:/laragon/www/aeos365
STANDALONE_HOST_PATH=c:/laragon/www/aeos365-standalone

# --- Seeded role credentials (set by UatSeeder, identical both modes) ---
UAT_PASSWORD=Password123!
UAT_SUPERADMIN_EMAIL=superadmin@uatco.test
UAT_HR_EMAIL=hr@uatco.test
UAT_EMPLOYEE_EMAIL=employee@uatco.test
UAT_LANDLORD_EMAIL=landlord@aeos365.test

# --- Optional Stripe test mode (omit to skip @billing) ---
STRIPE_KEY=
STRIPE_SECRET=

# --- Behaviour ---
RUN_DESTRUCTIVE=0          # 1 to include @destructive specs
SKIP_GLOBAL_SETUP=0        # 1 to reuse existing UAT DB state (fast re-runs)
```

- [ ] **Step 2: Create `e2e/support/env.ts`**

```ts
import { config } from 'dotenv';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
config({ path: resolve(here, '../.env') });

function req(name: string): string {
  const v = process.env[name];
  if (!v) throw new Error(`Missing required env var ${name} (copy e2e/.env.example to e2e/.env)`);
  return v;
}

export const ENV = {
  saasPlatformUrl: req('SAAS_PLATFORM_URL'),
  saasAdminUrl: req('SAAS_ADMIN_URL'),
  saasTenantSubdomain: req('SAAS_TENANT_SUBDOMAIN'),
  saasTenantUrl: req('SAAS_TENANT_URL'),
  standaloneUrl: req('STANDALONE_URL'),
  saasHostPath: req('SAAS_HOST_PATH'),
  standaloneHostPath: req('STANDALONE_HOST_PATH'),
  password: req('UAT_PASSWORD'),
  superAdminEmail: req('UAT_SUPERADMIN_EMAIL'),
  hrEmail: req('UAT_HR_EMAIL'),
  employeeEmail: req('UAT_EMPLOYEE_EMAIL'),
  landlordEmail: req('UAT_LANDLORD_EMAIL'),
  stripeKey: process.env.STRIPE_KEY ?? '',
  stripeSecret: process.env.STRIPE_SECRET ?? '',
  runDestructive: process.env.RUN_DESTRUCTIVE === '1',
  skipGlobalSetup: process.env.SKIP_GLOBAL_SETUP === '1',
  hasStripe(): boolean { return !!this.stripeSecret && !!this.stripeKey; },
};
```

- [ ] **Step 3: Commit**

```bash
git add e2e/.env.example e2e/support/env.ts
git commit -m "test(e2e): env config + typed loader

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.3: UAT `.env` files for both hosts

**Files:**
- Create: `c:\laragon\www\aeos365\.env.uat`
- Create: `c:\laragon\www\aeos365-standalone\.env.uat`

These are full env files; global-setup copies them over the live `.env` for the run and restores afterward. Start from each host's current `.env` and change only the keys below.

- [ ] **Step 1: Read the current SaaS `.env`**

Run: `Get-Content c:\laragon\www\aeos365\.env`
Use it as the base; produce `.env.uat` identical except:
```bash
APP_ENV=uat
DB_DATABASE=aeos365_uat
QUEUE_CONNECTION=sync
MAIL_MAILER=log
# central connection points at the same UAT DB (config/database.php 'central' uses DB_*)
```

- [ ] **Step 2: Write `c:\laragon\www\aeos365\.env.uat`** (full file with the overrides above).

- [ ] **Step 3: Read the current standalone `.env`** and write `c:\laragon\www\aeos365-standalone\.env.uat` identical except:
```bash
APP_ENV=uat
APP_URL=http://aeos365-standalone.test
DB_DATABASE=aeos365_standalone_uat
QUEUE_CONNECTION=sync
MAIL_MAILER=log
LICENSE_BYPASS=true
```

- [ ] **Step 4: Create the UAT databases**

Run:
```
& "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -uroot -e "CREATE DATABASE IF NOT EXISTS aeos365_uat; CREATE DATABASE IF NOT EXISTS aeos365_standalone_uat;"
```
(If the mysql path differs, discover it: `Get-ChildItem C:\laragon\bin\mysql -Directory`.)
Expected: no error; `SHOW DATABASES` lists both.

- [ ] **Step 5: Verify `.env.uat` is gitignored at the host level** (hosts already gitignore `.env*` — confirm `.env.uat` will not be committed). These files are operator-local; do **not** commit them. Document them in `e2e/README.md` instead.

- [ ] **Step 6: Commit** (README pointer only; no secret files)

```bash
git add e2e/README.md
git commit -m "test(e2e): document UAT env files + database setup

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```
(README is authored in Task 0.9; sequence this commit after it, or fold both into one.)

### Task 0.4: `UatSeeder` + `UatPlatformSeeder`

**Files:**
- Create: `c:\laragon\www\aeos365\database\seeders\UatSeeder.php`
- Create: `c:\laragon\www\aeos365\database\seeders\UatPlatformSeeder.php`
- Create: `c:\laragon\www\aeos365-standalone\database\seeders\UatSeeder.php` (copy of the SaaS `UatSeeder.php`)

`UatSeeder` runs against a **tenant/standalone DB** (HRM schema present). It is idempotent (`firstOrCreate`/`updateOrCreate`). `UatPlatformSeeder` runs against the **central** DB (SaaS only) and seeds landlord admin + plan + product.

- [ ] **Step 1: Write `UatSeeder.php`** (SaaS host)

```php
<?php

namespace Database\Seeders;

use Aero\HRM\Database\Seeders\HrmDemoSeeder;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Models\SubModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic UAT dataset for HRM E2E. Idempotent.
 * Runs inside a tenant DB (SaaS) or the standalone DB.
 *
 * Creates: 4 roles (assumed seeded by provisioning; ensured here),
 * one user per role driving HRMAC allow/deny, HRM lookup/config data
 * via HrmDemoSeeder, and ~10 employees with a salary structure.
 */
class UatSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure the four canonical roles exist (provisioning seeds them in SaaS;
        //    standalone install seeds Super Administrator only).
        foreach ([
            ['Super Administrator', true],
            ['Administrator', false],
            ['HR Manager', false],
            ['Employee', false],
        ] as [$name, $protected]) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['is_protected' => $protected]);
        }

        $superAdmin = Role::where('name', 'Super Administrator')->first();
        $hrManager = Role::where('name', 'HR Manager')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        // 2. Grant HR Manager full module access (same scope as Super Admin) so
        //    HRMAC allow-cases pass; Employee keeps self-service-only from provisioning.
        $allSubModuleIds = SubModule::pluck('id');
        foreach ($allSubModuleIds as $id) {
            DB::table('role_module_access')->insertOrIgnore([
                'role_id' => $hrManager->id, 'sub_module_id' => $id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 3. One user per role (emails match e2e/.env).
        $users = [
            ['superadmin@uatco.test', 'UAT Super Admin', $superAdmin],
            ['hr@uatco.test', 'UAT HR Manager', $hrManager],
            ['employee@uatco.test', 'UAT Employee', $employeeRole],
        ];
        foreach ($users as [$email, $name, $role]) {
            $user = \Aero\Core\Models\User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                ]
            );
            if (! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }
        }

        // 4. HRM lookup/config data (departments, designations, leave types,
        //    holidays, salary components, shifts, grades, skills, etc.).
        $this->call(HrmDemoSeeder::class);

        // 5. ~10 employees linked to the seeded departments/designations.
        (new UatEmployeeFactory())->seed(10);
    }
}
```

> If `Aero\Core\Models\User` is not the tenant user model, the executor must confirm via `grep -rn "class User" packages/aero-core/src/Models` and adjust. `UatEmployeeFactory` is defined in Step 2.

- [ ] **Step 2: Add the employee generator** at the bottom of `UatSeeder.php` (same file, after the class) or as a small private method. Confirm the Employee model + required columns first:

Run: `cd c:\laragon\www\Aero-Enterprise-Suite-Saas && grep -rn "class Employee" packages/aero-hrm/src/Models/Employee.php; grep -rn "Schema::create('employees'" -A40 packages/aero-hrm/database/migrations/*employees*`

Then implement a private `seedEmployees(int $n)` using `Aero\HRM\Models\Employee::factory()` if a factory exists, else `firstOrCreate` per employee with the discovered required columns (employee_code `UAT-0001..`, first/last name, email `emp1@uatco.test..`, department_id, designation_id, hire_date, national_id, tax_id). Replace the `(new UatEmployeeFactory())->seed(10)` call with `$this->seedEmployees(10)`.

- [ ] **Step 3: Write `UatPlatformSeeder.php`** (SaaS central DB only)

```php
<?php

namespace Database\Seeders;

use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SaaS central-DB UAT seed: landlord admin + an HRM-enabled plan + product.
 * Runs on the central connection (default in SaaS). Idempotent.
 */
class UatPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Landlord/super-admin for the platform admin domain.
        $admin = \Aero\Core\Models\User::on('central')->firstOrCreate(
            ['email' => 'landlord@aeos365.test'],
            ['name' => 'UAT Landlord', 'password' => Hash::make('Password123!'), 'email_verified_at' => now()]
        );
        // Assign the landlord/super-admin role (confirm role name via aero-platform seeders).
        if (method_exists($admin, 'assignRole') && ! $admin->hasRole('Super Admin')) {
            $admin->assignRole('Super Admin');
        }

        // An HRM-enabled plan the test tenant subscribes to.
        $plan = Plan::firstOrCreate(['slug' => 'uat-hrm'], [
            'name' => 'UAT HRM Plan', 'price' => 0, 'billing_cycle' => 'monthly', 'is_active' => true,
        ]);
        // Attach the HRM module to the plan (confirm pivot via Plan::modules()).
        $hrmModule = \Aero\Platform\Models\Module::where('code', 'hrm')->first();
        if ($hrmModule && ! $plan->modules()->where('code', 'hrm')->exists()) {
            $plan->modules()->attach($hrmModule->id);
        }
    }
}
```

> Class/relationship names (`Plan`, `Product`, `Module`, landlord role name, pivot) **must be verified** against `packages/aero-platform/src/Models/*` and `packages/aero-platform/database/seeders/*` before running; adjust to match. This is the one seeder most likely to need per-codebase tweaks.

- [ ] **Step 4: Copy `UatSeeder.php` to the standalone host** (byte-identical; same `Database\Seeders` namespace).

Run: `Copy-Item c:\laragon\www\aeos365\database\seeders\UatSeeder.php c:\laragon\www\aeos365-standalone\database\seeders\UatSeeder.php`

- [ ] **Step 5: Verify the standalone seeder runs end-to-end** (this is the cheapest full validation of the seeder before global-setup exists)

Run:
```
cd c:\laragon\www\aeos365-standalone
Copy-Item .env .env.bak.uat -Force
Copy-Item .env.uat .env -Force
php artisan config:clear
php artisan migrate:fresh --seed --seeder="Database\Seeders\UatSeeder" --force
Copy-Item .env.bak.uat .env -Force; Remove-Item .env.bak.uat
php artisan config:clear
```
Expected: migrations run on `aeos365_standalone_uat`; seeder completes with no error; `SELECT count(*) FROM employees` ≈ 10, `users` has 3 rows.

- [ ] **Step 6: Commit**

```bash
git add e2e/  # nothing host-side is committed except via the host repos if they are tracked
git commit -m "test(e2e): UatSeeder + UatPlatformSeeder (HRM dataset, roles, employees)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```
> Host `database/seeders/*.php` live in the host repos (separate git roots). Commit them there if those repos are version-controlled; otherwise they are local-only and documented in `e2e/README.md`.

### Task 0.5: artisan + DNS support helpers

**Files:**
- Create: `e2e/support/artisan.ts`
- Create: `e2e/support/dns.ts`

- [ ] **Step 1: Create `e2e/support/artisan.ts`**

```ts
import { execFileSync } from 'node:child_process';
import { copyFileSync, existsSync, rmSync } from 'node:fs';
import { join } from 'node:path';

/** Swap host to UAT .env, run artisan commands, then restore. */
export function withUatEnv(hostPath: string, fn: () => void): void {
  const env = join(hostPath, '.env');
  const uat = join(hostPath, '.env.uat');
  const bak = join(hostPath, '.env.bak.uat');
  if (!existsSync(uat)) throw new Error(`Missing ${uat} — create it (see e2e/README.md)`);
  copyFileSync(env, bak);
  try {
    copyFileSync(uat, env);
    artisan(hostPath, ['config:clear']);
    fn();
  } finally {
    copyFileSync(bak, env);
    rmSync(bak);
    artisan(hostPath, ['config:clear']);
  }
}

/** Leave the host pointed at UAT (used while the browser drives the live server). */
export function activateUatEnv(hostPath: string): void {
  const env = join(hostPath, '.env');
  const uat = join(hostPath, '.env.uat');
  const bak = join(hostPath, '.env.bak.uat');
  if (!existsSync(bak)) copyFileSync(env, bak);
  copyFileSync(uat, env);
  artisan(hostPath, ['config:clear']);
}

export function restoreEnv(hostPath: string): void {
  const env = join(hostPath, '.env');
  const bak = join(hostPath, '.env.bak.uat');
  if (existsSync(bak)) {
    copyFileSync(bak, env);
    rmSync(bak);
    artisan(hostPath, ['config:clear']);
  }
}

export function artisan(hostPath: string, args: string[]): string {
  return execFileSync('php', ['artisan', ...args], {
    cwd: hostPath, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'],
  });
}
```

- [ ] **Step 2: Create `e2e/support/dns.ts`**

```ts
import { request } from '@playwright/test';

export async function assertResolves(url: string): Promise<void> {
  const ctx = await request.newContext({ ignoreHTTPSErrors: true });
  try {
    const res = await ctx.get(url, { timeout: 8000, maxRedirects: 0 }).catch((e) => {
      throw new Error(`Cannot reach ${url}. Is Laragon running and the vhost configured?\n${e}`);
    });
    if (res.status() >= 500) throw new Error(`${url} returned ${res.status()} — server error during setup.`);
  } finally {
    await ctx.dispose();
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add e2e/support/artisan.ts e2e/support/dns.ts
git commit -m "test(e2e): artisan env-swap + DNS preflight helpers

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.6: BasePage + LoginPage POM and storageState minting

**Files:**
- Create: `e2e/pages/BasePage.ts`
- Create: `e2e/pages/auth/LoginPage.ts`
- Create: `e2e/support/storage-state.ts`
- Create: `e2e/fixtures/roles.ts`

- [ ] **Step 1: Create `e2e/fixtures/roles.ts`**

```ts
import { ENV } from '../support/env.ts';

export type Role = 'superadmin' | 'hr' | 'employee' | 'landlord';

export const ROLE_EMAIL: Record<Role, string> = {
  superadmin: ENV.superAdminEmail,
  hr: ENV.hrEmail,
  employee: ENV.employeeEmail,
  landlord: ENV.landlordEmail,
};

export function statePath(role: Role, mode: 'saas' | 'standalone'): string {
  return `.auth/${role}.${mode}.json`;
}
```

- [ ] **Step 2: Create `e2e/pages/BasePage.ts`**

```ts
import { type Page, type Locator, expect } from '@playwright/test';

export class BasePage {
  constructor(protected page: Page) {}

  async goto(path: string) {
    await this.page.goto(path, { waitUntil: 'networkidle' });
    await this.expectNoServerError();
  }

  async expectNoServerError() {
    await expect(this.page).not.toHaveURL(/\/(500|404|error)\b/);
    await expect(this.page.locator('body')).not.toContainText(/Server Error|Whoops, something went wrong/i);
  }

  async expectToast(text: RegExp | string) {
    await expect(this.page.getByText(text).first()).toBeVisible({ timeout: 8000 });
  }

  row(text: string): Locator {
    return this.page.getByRole('row', { hasText: text });
  }
}
```

- [ ] **Step 3: Create `e2e/pages/auth/LoginPage.ts`**

```ts
import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class LoginPage extends BasePage {
  constructor(page: Page, private baseUrl: string) { super(page); }

  async login(email: string, password: string) {
    await this.page.goto(`${this.baseUrl}/login`, { waitUntil: 'networkidle' });
    await this.page.fill('#email', email);
    await this.page.fill('#password', password);
    // device_id is auto-populated by a React useEffect (UUID v4 hidden field).
    await expect(this.page.locator('[name=device_id]')).not.toHaveValue('', { timeout: 5000 });
    await this.page.click('[type=submit]');
    await this.page.waitForLoadState('networkidle');
  }

  async expectLoginError() {
    await expect(this.page.getByText(/incorrect|credentials|invalid|do not match/i).first())
      .toBeVisible({ timeout: 8000 });
  }
}
```

- [ ] **Step 4: Create `e2e/support/storage-state.ts`**

```ts
import { chromium } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { ENV } from './env.ts';
import { LoginPage } from '../pages/auth/LoginPage.ts';
import { ROLE_EMAIL, statePath, type Role } from '../fixtures/roles.ts';

/** Log in once via UI and persist the session for reuse across specs. */
export async function mintState(role: Role, mode: 'saas' | 'standalone', baseUrl: string): Promise<void> {
  mkdirSync('.auth', { recursive: true });
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  const login = new LoginPage(page, baseUrl);
  await login.login(ROLE_EMAIL[role], ENV.password);
  // Sanity: we are authenticated (not bounced back to /login).
  if (/\/login\b/.test(page.url())) {
    await browser.close();
    throw new Error(`storageState mint failed for ${role}/${mode}: still on /login. Check seeded creds.`);
  }
  await ctx.storageState({ path: statePath(role, mode) });
  await browser.close();
}
```

- [ ] **Step 5: Commit**

```bash
git add e2e/pages/BasePage.ts e2e/pages/auth/LoginPage.ts e2e/support/storage-state.ts e2e/fixtures/roles.ts
git commit -m "test(e2e): BasePage + LoginPage POM and storageState minting

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.7: Tenant provisioning helper (SaaS)

**Files:**
- Create: `e2e/support/provision-tenant.ts`
- Create: `c:\laragon\www\aeos365\database\seeders\uat_provision.php` (artisan-run PHP script)

- [ ] **Step 1: Write the provisioning PHP script** `c:\laragon\www\aeos365\database\seeders\uat_provision.php`. It creates the known test tenant, a domain row, an HRM subscription, runs `ProvisionTenant::dispatchSync`, then seeds the tenant DB.

```php
<?php
// Run via: php artisan tinker --execute="require database_path('seeders/uat_provision.php');"
// Idempotent: re-running drops + recreates the UAT tenant.

use Aero\Platform\Jobs\ProvisionTenant;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;

$subdomain = 'uatco';

// Clean any prior UAT tenant so provisioning starts fresh.
$existing = Tenant::where('subdomain', $subdomain)->first();
if ($existing) {
    try { \DB::statement("DROP DATABASE IF EXISTS `{$existing->database()->getName()}`"); } catch (\Throwable $e) {}
    $existing->forceDelete();
}

$plan = Plan::where('slug', 'uat-hrm')->firstOrFail();

$tenant = Tenant::create([
    'name' => 'UAT Co',
    'subdomain' => $subdomain,
    'email' => 'landlord@aeos365.test',
    'data' => ['plan_id' => $plan->id],
]);
$tenant->domains()->create(['domain' => $subdomain.'.aeos365.test']);

// Attach the HRM module subscription so getActiveModules() returns ['hrm'].
// (Confirm the exact API: $tenant->subscriptions() / tenant_module pivot.)
$tenant->modules()->syncWithoutDetaching([\Aero\Platform\Models\Module::where('code','hrm')->value('id')]);

ProvisionTenant::dispatchSync($tenant);
$tenant->refresh();

if ($tenant->status !== 'active') {
    throw new \RuntimeException("Provisioning failed: status={$tenant->status} step={$tenant->provisioning_step}");
}

// Seed the tenant DB with the HRM dataset + role users.
$tenant->run(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\UatSeeder', '--force' => true,
    ]);
});

echo "UAT tenant provisioned: {$tenant->id} ({$subdomain}.aeos365.test)\n";
```

> The tenant↔module/subscription API (`$tenant->modules()`, `getActiveModules()`, or a `Subscription` model) **must be confirmed** against `Tenant.php` + `ProvisionTenant::getActiveModules()` usage. Adjust the `data['plan_id']` / module-attach lines so `getActiveModules()->all()` returns `['hrm']`.

- [ ] **Step 2: Create `e2e/support/provision-tenant.ts`**

```ts
import { artisan } from './artisan.ts';
import { ENV } from './env.ts';

/** Create + synchronously provision the known UAT SaaS tenant, then seed its DB. */
export function provisionUatTenant(): void {
  const out = artisan(ENV.saasHostPath, [
    'tinker', '--execute', "require database_path('seeders/uat_provision.php');",
  ]);
  if (!/UAT tenant provisioned/.test(out)) {
    throw new Error(`Tenant provisioning did not confirm success:\n${out}`);
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add e2e/support/provision-tenant.ts
git commit -m "test(e2e): SaaS UAT tenant provisioning helper

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.8: global-setup + global-teardown + playwright.config

**Files:**
- Create: `e2e/global-setup.ts`
- Create: `e2e/global-teardown.ts`
- Create: `e2e/playwright.config.ts`

- [ ] **Step 1: Create `e2e/global-setup.ts`**

```ts
import { ENV } from './support/env.ts';
import { artisan, activateUatEnv } from './support/artisan.ts';
import { provisionUatTenant } from './support/provision-tenant.ts';
import { mintState } from './support/storage-state.ts';
import { assertResolves } from './support/dns.ts';
import { writeFileSync } from 'node:fs';
import { join } from 'node:path';

export default async function globalSetup() {
  if (ENV.skipGlobalSetup) {
    console.log('[uat] SKIP_GLOBAL_SETUP=1 — reusing existing UAT state');
    return;
  }

  // --- DNS / vhost preflight (fail fast with guidance) ---
  await assertResolves(ENV.standaloneUrl);
  await assertResolves(ENV.saasPlatformUrl);
  await assertResolves(ENV.saasTenantUrl); // requires *.aeos365.test wildcard

  // ============ STANDALONE ============
  console.log('[uat] standalone: migrate:fresh --seed');
  activateUatEnv(ENV.standaloneHostPath);
  artisan(ENV.standaloneHostPath, ['migrate:fresh', '--seed', '--seeder=Database\\Seeders\\UatSeeder', '--force']);
  // Mark installed + standalone mode so BootstrapGuard lets requests through.
  const saStorage = join(ENV.standaloneHostPath, 'storage', 'app');
  writeFileSync(join(saStorage, 'aeos.mode'), 'standalone');
  writeFileSync(join(saStorage, 'aeos.installed'), new Date().toISOString());
  await mintState('superadmin', 'standalone', ENV.standaloneUrl);
  await mintState('hr', 'standalone', ENV.standaloneUrl);
  await mintState('employee', 'standalone', ENV.standaloneUrl);

  // ============ SAAS ============
  console.log('[uat] saas: central migrate:fresh + platform seed');
  activateUatEnv(ENV.saasHostPath);
  artisan(ENV.saasHostPath, ['migrate:fresh', '--force']); // central
  artisan(ENV.saasHostPath, ['db:seed', '--class=Database\\Seeders\\UatPlatformSeeder', '--force']);
  writeFileSync(join(ENV.saasHostPath, 'storage', 'app', 'aeos.mode'), 'saas');

  console.log('[uat] saas: provision test tenant');
  provisionUatTenant();

  // Tenant-scoped role logins live at the subdomain; landlord at the admin domain.
  await mintState('superadmin', 'saas', ENV.saasTenantUrl);
  await mintState('hr', 'saas', ENV.saasTenantUrl);
  await mintState('employee', 'saas', ENV.saasTenantUrl);
  await mintState('landlord', 'saas', ENV.saasAdminUrl);

  console.log('[uat] global setup complete');
}
```

- [ ] **Step 2: Create `e2e/global-teardown.ts`**

```ts
import { ENV } from './support/env.ts';
import { restoreEnv } from './support/artisan.ts';

export default async function globalTeardown() {
  restoreEnv(ENV.saasHostPath);
  restoreEnv(ENV.standaloneHostPath);
  console.log('[uat] restored original .env for both hosts');
}
```

- [ ] **Step 3: Create `e2e/playwright.config.ts`**

```ts
import { defineConfig, devices } from '@playwright/test';
import { ENV } from './support/env.ts';

export default defineConfig({
  testDir: './specs',
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  // Modes run sequentially (separate DBs); workers parallelize within a mode.
  workers: process.env.CI ? 2 : 4,
  reporter: [['html', { open: 'never' }], ['list']],
  grepInvert: ENV.runDestructive ? undefined : /@destructive/,
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    viewport: { width: 1366, height: 800 },
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'standalone',
      grep: /@standalone/,
      use: { ...devices['Desktop Chrome'], baseURL: ENV.standaloneUrl },
    },
    {
      name: 'saas',
      grep: /@saas/,
      use: { ...devices['Desktop Chrome'], baseURL: ENV.saasTenantUrl },
    },
  ],
});
```

- [ ] **Step 4: Commit**

```bash
git add e2e/global-setup.ts e2e/global-teardown.ts e2e/playwright.config.ts
git commit -m "test(e2e): global setup/teardown + two-project config (saas, standalone)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

### Task 0.9: Smoke spec + README, then verify P0 runs

**Files:**
- Create: `e2e/specs/p0-smoke/smoke.spec.ts`
- Create: `e2e/README.md`
- Delete: `tests/e2e/` (old root remnants)

- [ ] **Step 1: Create `e2e/specs/p0-smoke/smoke.spec.ts`**

```ts
import { test, expect } from '@playwright/test';
import { statePath } from '../../fixtures/roles.ts';

test.describe('@standalone P0 smoke', () => {
  test.use({ storageState: statePath('superadmin', 'standalone') });
  test('authenticated dashboard loads', async ({ page }) => {
    await page.goto('/');
    await expect(page).not.toHaveURL(/\/login\b/);
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('@saas P0 smoke', () => {
  test.use({ storageState: statePath('superadmin', 'saas') });
  test('authenticated tenant dashboard loads', async ({ page }) => {
    await page.goto('/');
    await expect(page).not.toHaveURL(/\/login\b/);
    await expect(page.locator('body')).toBeVisible();
  });
});
```

- [ ] **Step 2: Write `e2e/README.md`** documenting: prerequisites (Laragon wildcard `*.aeos365.test` + `aeos365-standalone.test` vhosts; PHP 8.3; `npm install`; `npx playwright install chromium`; UAT DBs created; `.env.uat` in each host; `e2e/.env` copied from `.env.example`); how the env-swap works (global-setup overwrites the live `.env`, teardown restores — do not run during active dev work); how to run (`npm run test:standalone`, `npm run test:saas`, `RUN_DESTRUCTIVE=1`, `SKIP_GLOBAL_SETUP=1`); Stripe-optional `@billing` skip; tag taxonomy.

- [ ] **Step 3: Remove old remnants**

Run: `Remove-Item -Recurse -Force c:\laragon\www\Aero-Enterprise-Suite-Saas\tests\e2e`

- [ ] **Step 4: VERIFY P0 — full run of the smoke spec in both projects**

Run: `cd c:\laragon\www\Aero-Enterprise-Suite-Saas\e2e && npx playwright test specs/p0-smoke`
Expected: global-setup logs DNS ok → standalone migrate/seed → saas central seed + tenant provision → 7 storageState files written under `.auth/`; both smoke tests PASS; teardown restores envs. **Show the terminal output.** If DNS preflight fails, STOP and report the missing vhost to the operator (do not work around it).

- [ ] **Step 5: Commit**

```bash
git add e2e/specs/p0-smoke e2e/README.md
git rm -r tests/e2e
git commit -m "test(e2e): P0 smoke spec + README; remove old root e2e remnants

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

**P0 GATE:** Stop and get operator OK before P1.

---

## Cross-phase conventions (apply to every P1–P5 spec)

- **Auth:** each `describe` block does `test.use({ storageState: statePath(role, mode) })`. Default role = `hr` for HRM admin flows, `employee` for self-service, `landlord` for platform.
- **Mode tags:** shared HRM specs tag **both** `@saas @standalone` at the file's top-level `describe` and run the same POM against `page` (baseURL is the active project's). Mode-only specs (install, lifecycle, platform, billing) tag a single mode.
- **Order-independence:** every CRUD test creates its own record with a unique suffix (`` `UAT ${Date.now()}` ``) and cleans up by deleting it at the end (or relies on `migrate:fresh` isolation). Never depend on another test's record.
- **POM-only:** specs call page-object methods; no raw selectors in specs except trivial assertions. Each POM method is `async`, navigates via `goto`, and asserts no server error.
- **`@destructive`** tests (suspend, GDPR-forget, re-install) live at the end of their file and only run with `RUN_DESTRUCTIVE=1`.
- **TDD note:** E2E here is black-box against already-built features, so the "write failing test first" loop = author the spec, run it, watch it fail for a *real* reason (selector/route), then fix the POM/selector until green. If a spec fails because the **feature** is broken, STOP and report it as a product bug (do not edit product code to make a UAT test pass without operator sign-off).

---

## Phase P1 — Lifecycle & Auth + HRMAC

Deliverable: `specs/p1-lifecycle-auth/*` green (non-destructive by default; destructive lifecycle behind the flag).

### Task 1.1: Standalone installer spec  (`install.standalone.spec.ts`, `@standalone @destructive`)

**Files:** Create `e2e/pages/auth/InstallWizardPage.ts`, `e2e/specs/p1-lifecycle-auth/install.standalone.spec.ts`.

> This is `@destructive` because it requires a **non-installed** DB. It runs against a throwaway DB `aeos365_standalone_uat_install` (set a temp `.env.uat.install` or have the spec's `beforeAll` drop `aeos.installed` + `migrate:fresh` on a scratch DB). Implementation note in the task: do NOT run this against the seeded UAT DB used by every other standalone spec.

- [ ] **Step 1: `InstallWizardPage.ts`** — methods: `start()` (`goto('/install')`), `acceptRequirements()`, `submitDatabase({host,port,db,user,pass})` (fills DB step, clicks test then next — asserts `/install/save-database` success), `createAdmin({name,email,password})`, `submitLicense(key?)` (LICENSE_BYPASS path), `finalize()` (clicks execute, waits for `/install/complete`), `expectInstalledRedirect()` (assert `/install` now 404s / redirects to `/login`).
- [ ] **Step 2: Spec cases** (each = `test(...)`):
  - `installer wizard loads + DB step succeeds on fresh DB` → maps spec 1.1.1–1.1.2.
  - `admin-user step creates first admin` → 1.1.2.
  - `license step valid via bypass` → 1.1.3.
  - `finalize marks app installed, /login reachable` → 1.1.4 + 1.1.6.
  - `/install 404s after completion` → 1.1.6.
  - `dirty-schema guard refuses re-install without FORCE_CLEAN_INSTALL` → 1.1.5 (create tables w/o migrations on scratch DB, hit `/install/execute`, assert refusal).
- [ ] **Step 3: Run** `npx playwright test --project=standalone specs/p1-lifecycle-auth/install.standalone.spec.ts` with `RUN_DESTRUCTIVE=1`. Show output.
- [ ] **Step 4: Commit.**

### Task 1.2: SaaS tenant lifecycle spec  (`tenant-lifecycle.saas.spec.ts`, `@saas`; suspend/forget `@destructive`)

**Files:** Create `e2e/pages/auth/RegistrationPage.ts`, `e2e/pages/platform/TenantsPage.ts` (shared with P4), the spec.

- [ ] **Step 1: `RegistrationPage.ts`** — `startSignup()` (`goto(${SAAS_PLATFORM_URL}/signup)`), `submitAccountType()`, `submitDetails({company, subdomain, email, ...})`, `expectSubdomainRejected(reason)`, `chooseFreeTrialPlan()`, `expectProvisioningPage(tenantId)`. Drives the live signup flow on the platform domain.
- [ ] **Step 2: Spec cases** (some create real pending tenants — use unique subdomains `uat-reg-${Date.now()}` and cancel/cleanup):
  - `valid subdomain accepted through details step` → 1.2.1.
  - `reserved subdomain rejected` (try `admin`/`www`/`api`) → 1.2.1.
  - `subdomain length rules enforced` → 1.2.1.
  - `duplicate email/subdomain → uniform anti-enumeration message` → 1.2.1.
  - `BYOC step optional (skippable)` → 1.2.1.
  - `full registration → provisioning runs → tenant active + subdomain login works` (use a throwaway subdomain; provision via the live sync queue; then login at the new subdomain) → 1.2.2–1.2.3.
  - `@destructive suspend tenant → web 403 page, API returns 403 JSON` (suspend the throwaway tenant via landlord; assert web + `Accept: application/json` API both 403) → 1.2.4.
  - `@destructive GDPR-forget → tenant row hard-deleted + DB dropped` (assert via landlord tenant list + `SHOW DATABASES` through an artisan check) → 1.2.5.
- [ ] **Step 3: Run** (with and without `RUN_DESTRUCTIVE=1`). Show output. **Step 4: Commit.**

### Task 1.3: Auth & sessions spec  (`auth-sessions.spec.ts`, `@saas @standalone`)

**Files:** extend `LoginPage.ts` (add `logout()`, `requestPasswordReset(email)`, MFA helpers), create the spec.

- [ ] **Step 1: LoginPage additions** — `logout()`, `requestPasswordReset(email)` (`goto /forgot-password`, submit, assert uniform response), `expectRateLimited()`, MFA: `setupMfa()` (read QR/secret), `submitMfaCode(code)`, `useBackupCode(code)`, `trustDevice()`.
- [ ] **Step 2: Spec cases** (run in BOTH projects via shared file):
  - `valid login → dashboard; invalid → error (no enumeration)` → 1.3.1.
  - `logout clears session` (after logout, protected page bounces to /login) → 1.3.2.
  - `password reset request returns uniform response` → 1.3.3.
  - `password reset rate-limited after N attempts` → 1.3.3.
  - `MFA setup shows QR/secret + challenge on next login` → 1.3.4 (only if a seeded MFA-enabled user exists; if MFA setup needs UI steps, drive them; else mark this case `test.skip` with a documented manual note).
  - `backup-code recovery + trusted device` → 1.3.4.
  - `device binding: login requires device_id; suspended device blocked` → 1.3.5 (device_id presence already asserted; for "suspended device blocked", seed a suspended device row or skip-with-note).
  - `impersonation [saas, landlord]: start → banner → exit; tampered redirect_url blocked` → 1.3.6 (saas-only `test.skip` in standalone project).
  - `session expiry → re-auth prompt` → 1.3.7 (expire cookie via context, assert redirect).
- [ ] **Step 3: Run both projects. Show output. Step 4: Commit.**

### Task 1.4: HRMAC allow/deny spec  (`hrmac.spec.ts`, `@saas @standalone`)

**Files:** create the spec (reuses HRM page objects from P2 for navigation targets; if P2 POMs don't exist yet, navigate by URL).

- [ ] **Step 1: Spec cases:**
  - `HR Manager can reach + act on leave approval, payroll, employee mgmt` (storageState `hr`) → 1.4.1: visit `/hrm/leave`, `/hrm/payroll`, `/hrm/employees`; assert 200 + an action control present.
  - `Employee cannot reach admin HRM pages → 403/redirect; self-service only` (storageState `employee`) → 1.4.2: visit `/hrm/employees` etc., assert 403 or redirect away; visit `/my/dashboard` ok.
  - `denials recorded in audit-log viewer` → 1.4.3 (landlord/saas or admin: open audit/access-log viewer, assert the denial appears; standalone uses its audit viewer).
  - `disabled module (is_active=false) denies a granted role` → 1.4.4: flip a submodule `is_active=false` via artisan tinker in `beforeAll`, assert HR (granted) is denied, restore in `afterAll`.
- [ ] **Step 2: Run both projects. Show output. Step 3: Commit.**

**P1 GATE:** operator OK before P2.

---

## Phase P2 — HRM core (deep)

Deliverable: `specs/p2-hrm-core/*` green in **both** projects. Build the five HRM page objects, then the specs. Each spec is `@saas @standalone`, default storageState `hr`.

### Task 2.1: EmployeesPage + employees spec

**Files:** `e2e/pages/hrm/EmployeesPage.ts`, `e2e/specs/p2-hrm-core/employees.spec.ts`.

- [ ] **Step 1: `EmployeesPage.ts`** — `list()`, `openCreate()`, `create(data)`, `edit(code, patch)`, `view(code)`, `delete(code)`, `uploadDocument(code, file)`, `uploadAvatar(code, file)`, `expectMaskedSensitive(code)` (national_id/tax_id shown masked), `expectInList(name)`, `expectNotInList(name)`.
- [ ] **Step 2: Spec cases** → spec 2.1: create/edit/view/delete; document upload; **encrypted national_id/tax_id masked in UI**; **avatar lands in per-tenant storage** (assert image URL is tenant-scoped — verify path via artisan in saas); employee self-service profile (storageState `employee`, `/my/profile`).
- [ ] **Step 3: Reference code — write the full create test** (this is the pattern every later CRUD test follows):

```ts
import { test, expect } from '@playwright/test';
import { statePath } from '../../fixtures/roles.ts';
import { EmployeesPage } from '../../pages/hrm/EmployeesPage.ts';

test.describe('@saas @standalone HRM Employees', () => {
  test.use({ storageState: statePath('hr', process.env.PW_MODE as any ?? 'standalone') });

  test('create → appears in list → view → delete', async ({ page }) => {
    const emp = new EmployeesPage(page);
    const name = `UAT Emp ${Date.now()}`;
    await emp.openCreate();
    await emp.create({ firstName: 'UAT', lastName: `Emp${Date.now()}`, email: `e${Date.now()}@uatco.test`,
      department: 'Engineering', designation: 'Software Engineer', nationalId: '900101-12-3456' });
    await emp.expectToast(/created|saved/i);
    await emp.expectInList('UAT Emp');
  });
});
```

> The `statePath` mode must match the running project. Replace the `process.env.PW_MODE` hack with a tiny helper `currentMode()` reading `test.info().project.name` — add `e2e/support/mode.ts` exporting `modeFromProject(name): 'saas'|'standalone'` and use `test.use(async ({}, use, testInfo) => …)` or a custom fixture. Define this helper in Task 2.1 Step 1 and use it in ALL P2–P4 specs.

- [ ] **Step 4: Add `e2e/support/mode.ts`** + a custom test fixture `e2e/fixtures/test.ts` that auto-selects storageState by `(role, project.name)` so specs write `roleTest('hr')(...)`. Refactor the smoke + this spec to use it. (This removes the `PW_MODE` hack everywhere.)
- [ ] **Step 5: Run both projects. Show output. Step 6: Commit.**

### Task 2.2: Departments / Designations spec
**Files:** `DepartmentsPage.ts`, `DesignationsPage.ts`, `departments-designations.spec.ts`.
- [ ] CRUD both; assert each new record appears in the employee create-form dropdown (spec 2.2). Run both projects, show output, commit.

### Task 2.3: Attendance spec
**Files:** `AttendancePage.ts`, `attendance.spec.ts`.
- [ ] clock-in then clock-out; **double clock-in is idempotent (no duplicate)**; daily attendance list; timesheet; overtime request→approve; shift listing (spec 2.3). Run both, show output, commit.

### Task 2.4: Leave spec
**Files:** `LeavePage.ts`, `leave.spec.ts`.
- [ ] leave-type CRUD; apply (employee) → HR approve/reject; **balance decremented on approval**; accrual; calendar shows approved; bulk action (spec 2.4). Mixed storageState (`employee` applies, `hr` approves) within a test. Run both, show output, commit.

### Task 2.5: Payroll spec
**Files:** `PayrollPage.ts`, `payroll.spec.ts`.
- [ ] pay-component + salary-structure CRUD; **payroll run → payslip generated**; **finalized payslip immutable (edit/delete blocked → assert 403/disabled)**; payslip view shows **bank last-4 only** (spec 2.5). Run both, show output, commit.

**P2 GATE:** operator OK before P3.

---

## Phase P3 — HRM remainder (exhaustive)

Deliverable: `specs/p3-hrm-remainder/*` green both projects. One page object + one spec per submodule. Each spec = CRUD + the key workflow named in the spec, default storageState `hr`, tagged `@saas @standalone`. The de-dup-route validations (interviews, enrollments, PIP, grievances, assets) are first-class assertions (visit the canonical route, assert 200 + the action works).

For each task below: **(1)** build the page object with `list/create/edit/delete` + the workflow method(s); **(2)** write the spec cases enumerated; **(3)** run BOTH projects and show output; **(4)** commit.

- [ ] **Task 3.1 Recruitment** (`RecruitmentPage.ts`, `recruitment.spec.ts`): jobs CRUD/publish/close; applications apply→move-stage→reject; **interviews schedule/update (de-duped flat routes)**; offers; onboarding. (spec 3.1)
- [ ] **Task 3.2 Training** (`TrainingPage.ts`): courses, categories, sessions, **enrollments (de-duped flat routes)**, feedback, safety training. (spec 3.2)
- [ ] **Task 3.3 Performance** (`PerformancePage.ts`): reviews, review cycles, goals, competencies, calibration, **PIP create/list/show (consolidated routes)**, 360 feedback, skill matrix. (spec 3.3)
- [ ] **Task 3.4 Disciplinary** (`DisciplinaryPage.ts`): cases create→investigate→close; action-types CRUD; warnings; **grievances create→investigate→resolve (de-duped routes)**. (spec 3.4)
- [ ] **Task 3.5 Safety** (`SafetyPage.ts`): incidents report→resolve; inspections; safety training. (spec 3.5)
- [ ] **Task 3.6 Assets** (`AssetsPage.ts`): inventory CRUD (**canonical `Hrm*` asset routes**), categories, allocations allocate→return. (spec 3.6)
- [ ] **Task 3.7 Expenses** (`ExpensesPage.ts`): claims submit→approve; categories. (spec 3.7)
- [ ] **Task 3.8 Benefits** (`BenefitsPage.ts`): catalog, enrollment, open-enrollment period. (spec 3.8)
- [ ] **Task 3.9 Succession** (`SuccessionPage.ts`): talent pools, candidates, career-paths, mobility. (spec 3.9)
- [ ] **Task 3.10 Misc** (`MiscPage.ts`): events + registration, announcements, wellbeing dashboard (SQL-aggregated, assert it renders numbers), workforce planning, compensation planning, exit interviews. (spec 3.10)
- [ ] **Task 3.11 Self-service portal** (`SelfServicePage.ts`, storageState `employee`): my dashboard, my leaves (apply), my payslips (view), my profile (edit), my training. (spec 3.11)

> Per-task route prefixes are discovered from `packages/aero-hrm/routes/*.php` + `config/module.php` at task time. The route-name de-dup work (BUG-3, now FIXED per the tech-debt ledger) means the canonical names are stable; assert the **flat** canonical route resolves.

**P3 GATE:** operator OK before P4.

### Task 3.0 (do first in P3): generic CRUD helper to keep POMs DRY
**Files:** `e2e/pages/hrm/CrudResource.ts`.
- [ ] Build a reusable `CrudResource` base (constructor takes `{ indexUrl, createUrl, formFields, rowKey }`) exposing `list/create/edit/delete/expectInList/expectNotInList`. Each submodule POM extends it and adds only its workflow methods. Write it before Task 3.1 and refactor EmployeesPage/etc. to use it where it fits. Run the P2 suite to confirm no regression. Commit.

---

## Phase P4 — Platform admin + billing  (`@saas` only)

Deliverable: `specs/p4-platform-billing/*` green in the saas project. storageState `landlord`, baseURL the admin domain (override per-spec: `test.use({ baseURL: ENV.saasAdminUrl })`).

- [ ] **Task 4.1 Tenant management** (`TenantsPage.ts`, `tenant-management.saas.spec.ts`): list, create, show, suspend, archive, GDPR-forget, bulk operations. forget/archive/suspend = `@destructive` (operate on throwaway tenants created in the test). (spec 4.1) Run, show output, commit.
- [ ] **Task 4.2 Catalog** (`CatalogPage.ts`, `catalog.saas.spec.ts`): plans, products, product subscriptions, modules, pricing CRUD. (spec 4.2) Run, show output, commit.
- [ ] **Task 4.3 Billing** (`BillingPage.ts`, `billing.saas.spec.ts`, `@billing`): invoices (view/PDF), payment methods, renewal run, dunning, refund, credit note. Entire file gated: `test.skip(!ENV.hasStripe(), 'Stripe test keys not configured')`. Use Stripe test cards (`4242…`). (spec 4.3) Run with and without keys (show the skip), commit.
- [ ] **Task 4.4 Observability** (`ObservabilityPage.ts`, `observability.saas.spec.ts`): platform + product dashboards, **audit-log viewer**, **access-log viewer**, settings/infrastructure, feature flags, maintenance windows — assert each renders without error and shows expected columns/rows. (spec 4.4) Run, show output, commit.
- [ ] **Task 4.5 Landlord** (`LandlordPage.ts`, `landlord.saas.spec.ts`): landlord users & roles CRUD. (spec 4.5) Run, show output, commit.

**P4 GATE:** operator OK before P5.

---

## Phase P5 — CI integration + exit criteria

### Task 5.1: GitHub Actions workflow
**Files:** Create `.github/workflows/uat-e2e.yml`.
- [ ] **Step 1:** Author a workflow with a job per mode-project. Because the suite drives **live Laragon URLs**, CI must either (a) run on a self-hosted Windows+Laragon runner, or (b) spin up the two hosts via `php artisan serve` + a MySQL service + `/etc/hosts` entries for `aeos365-standalone.test`, `aeos365.test`, `uatco.aeos365.test`, `admin.aeos365.test`. Implement option (b) for hosted runners: MySQL service container, `composer install` both hosts, write `e2e/.env`, `php artisan serve` per host on mapped ports with host-header rewrites (or use `127.0.0.1` + `--host`), then `npx playwright test --project=standalone` and `--project=saas`. Upload `playwright-report/` as an artifact. Add a PHPUnit job (`cd aeos365 && php artisan test`).

> CI subdomain routing is the known hard part. If hosted-runner subdomain routing proves infeasible in the time box, the workflow runs the **standalone** project on a hosted runner and gates the **saas** project to a self-hosted-Laragon runner label. Document whichever path is taken in `e2e/README.md`.

- [ ] **Step 2:** Validate the workflow YAML locally (`actionlint` if available, else careful review). Commit.

### Task 5.2: Exit-criteria verification + sign-off doc
**Files:** Create `docs/superpowers/plans/2026-06-02-uat-e2e-suite/EXIT.md`.
- [ ] **Step 1:** Run the full suite both projects locally: `cd e2e && npm test` (then `RUN_DESTRUCTIVE=1 npm test` for destructive). Capture the HTML report.
- [ ] **Step 2:** Write `EXIT.md` listing: P1–P4 spec pass counts per project; the generated report path; any cases intentionally left manual (`test.skip` with reason — e.g. real-payment edge cases, MFA hardware) with a documented manual sign-off line each.
- [ ] **Step 3:** Final commit + use `superpowers:finishing-a-development-branch` to decide merge/PR.

**Exit criteria (from spec §5, P5):** P1–P4 specs green in both mode-projects on Laragon; CI green; a generated HTML report exists; manual sign-off documented for anything left manual.

---

## Self-review (performed against the spec)

**Spec coverage:** §1 tooling/architecture → Task 0.1/0.8 (two projects, POM). §2 env/data → 0.2/0.3/0.4/0.7/0.8 (UAT DBs, `.env.uat`, `UatSeeder`, sync provisioning, storageState, Stripe-skip, isolation via migrate:fresh, destructive flag). §3 matrix: P1 1.1–1.4 → Tasks 1.1–1.4; P2 2.1–2.5 → Tasks 2.1–2.5; P3 3.1–3.11 → Tasks 3.1–3.11; P4 4.1–4.5 → Tasks 4.1–4.5; P5 → Tasks 5.1–5.2. §6 risks: flakiness (POM + networkidle waits + storageState), wildcard DNS (dns.ts preflight), provisioning-in-setup (dispatchSync), Stripe (skip), exhaustive scope (phased gates). All spec sections map to a task.

**Placeholder scan:** No "TBD"/"add validation"/"similar to". Three places explicitly flagged for codebase confirmation before running (tenant↔module API in `uat_provision.php`; platform model/role names in `UatPlatformSeeder`; Employee columns in `UatSeeder`) — each names the exact file to grep and what to adjust, which is a verification step, not a content gap.

**Type consistency:** `statePath(role, mode)`, `ENV.*`, `withUatEnv/activateUatEnv/restoreEnv/artisan`, `mintState(role, mode, baseUrl)`, `provisionUatTenant()`, `BasePage`/`LoginPage` method names are consistent across global-setup, helpers, and specs. Task 2.1 Step 4 removes the temporary `PW_MODE` hack by introducing `support/mode.ts` + `fixtures/test.ts`, and mandates its use in all later specs (consistency fix applied inline).
