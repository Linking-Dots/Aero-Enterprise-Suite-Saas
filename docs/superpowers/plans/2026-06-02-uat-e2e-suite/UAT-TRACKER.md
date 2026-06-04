# AEOS365 UAT Tracker — Scenarios, Bugs & Findings

> **Living document.** Updated as the UAT E2E suite (plan: `../2026-06-02-uat-e2e-suite.md`,
> spec: `../../specs/2026-06-01-uat-e2e-design.md`) is built and run. Every scenario, every
> bug found + fixed, and every cross-package duplication/finding lands here.
>
> **Legend:** ✅ pass · ❌ fail (bug open) · 🔧 fixed (re-verify) · ⛔ blocked · ⬜ pending · ➖ n/a
> **Modes:** S = SaaS · A = Standalone · B = both

Last updated: 2026-06-02

---

## A. Scenario checklist

### P0 — Foundation
| ID | Scenario | Mode | Status | Notes |
|----|----------|------|--------|-------|
| P0.1 | `e2e/` project scaffold + deps + Chromium | B | ✅ | committed |
| P0.2 | Env loader + `.env.uat` per host + UAT DBs created | B | ✅ | `aeos365_uat`, `aeos365_standalone_uat` |
| P0.3 | `UatSeeder` (self-contained) seeds clean | A | ✅ | 13 users, 10 employees, role grants 72/72/72/3 |
| P0.4 | `UatPlatformSeeder` (central) seeds clean | S | ✅ | plans/products/modules + landlord roles+user; registry synced |
| P0.5 | global-setup migrate+seed+storageState | A | ✅ | all 3 roles mint OK |
| P0.6 | global-setup provision SaaS tenant | S | ✅ | uatco provisioned (DB, migrate, modules, roles, seed) |
| P0.7 | Smoke: authenticated dashboard loads | A | ✅ | `1 passed` (single-env, production config, https) |
| P0.7s | Smoke: authenticated tenant dashboard loads | S | ✅ | `2 passed` both modes |

### P1 — Lifecycle & Auth + HRMAC
| ID | Scenario | Mode | Status | Notes |
|----|----------|------|--------|-------|
| P1.1 | Standalone installer wizard | A | 🟡 partial | post-install guard verified (/install → "Already Installed", DB step unreachable). Fresh wizard (DB→admin→license→finalize) + dirty-guard = @destructive, needs dedicated scratch-DB harness (not run against working UAT) |
| P1.2 | SaaS tenant lifecycle | S | 🟡 partial | registration entry renders + platform /login reachable; provision happy-path covered by P0 (uat_provision). Remaining: full register→new-tenant login (needs wildcard *.aeos365.test DNS), suspend 403 + GDPR-forget (needs P4 landlord UI / covered by PHPUnit D3) |
| P1.3 | Auth & sessions | B | ✅ | 12 passed both modes (login valid/invalid/anti-enum, logout, password-reset, session-expiry). MFA/device/impersonation → P4/feature-gated |
| P1.4 | HRMAC allow/deny | B | ✅ | green both modes (HR full, Employee denied admin pages, self-service allowed) |

### P2 — HRM core
| ID | Scenario | Mode | Status | Notes |
|----|----------|------|--------|-------|
| P2.1 | Employees CRUD + docs + encrypted PII masked + per-tenant avatar + self-service | B | ⬜ | |
| P2.2 | Departments / Designations CRUD + dropdowns | B | ⬜ | |
| P2.3 | Attendance clock-in/out (idempotent), timesheet, overtime, shifts | B | ⬜ | |
| P2.4 | Leave: types CRUD, apply→approve/reject, balance, accrual, calendar, bulk | B | ⬜ | |
| P2.5 | Payroll: components/structure CRUD, run→payslip, immutable, bank last-4 | B | ⬜ | |

### P3 — HRM remainder
| ID | Scenario | Mode | Status | Notes |
|----|----------|------|--------|-------|
| P3.1 | Recruitment (jobs, applications, interviews, offers, onboarding) | B | ⬜ | |
| P3.2 | Training (courses, sessions, enrollments, feedback) | B | ⬜ | |
| P3.3 | Performance (reviews, cycles, goals, PIP, 360, skill matrix) | B | ⬜ | |
| P3.4 | Disciplinary (cases, action-types, warnings, grievances) | B | ⬜ | |
| P3.5 | Safety (incidents, inspections, training) | B | ⬜ | |
| P3.6 | Assets (inventory, categories, allocations) | B | ⬜ | |
| P3.7 | Expenses (claims submit→approve, categories) | B | ⬜ | |
| P3.8 | Benefits (catalog, enrollment, open-enrollment) | B | ⬜ | |
| P3.9 | Succession (talent pools, candidates, career-paths, mobility) | B | ⬜ | |
| P3.10 | Misc (events, announcements, wellbeing, workforce/comp planning, exit interviews) | B | ⬜ | announcements widget fixed (B-14) |
| P3.11 | Self-service portal (dashboard, leaves, payslips, profile, training) | B | ⬜ | |

### P4 — Platform admin + billing (SaaS)
| ID | Scenario | Mode | Status | Notes |
|----|----------|------|--------|-------|
| P4.1 | Tenant management (list/create/show/suspend/archive/forget/bulk) | S | ⬜ | |
| P4.2 | Catalog (plans/products/subscriptions/modules/pricing) | S | ⬜ | |
| P4.3 | Billing `@billing` (invoices/PDF, payment methods, renewal, dunning, refund, credit) | S | ⬜ | Stripe-gated |
| P4.4 | Observability (dashboards, audit-log, access-log, settings, flags, maintenance) | S | ⬜ | |
| P4.5 | Landlord (users & roles CRUD) | S | ⬜ | |

---

## B. Bugs found & fixed

| ID | Area / file | Bug | Root cause | Fix | Status |
|----|-------------|-----|-----------|-----|--------|
| B-1 | aero-core `2026_01_11_000001_create_notification_logs_table` | `migrate:fresh` collision | duplicate `Schema::create` of canonical aero-notifications table | `Schema::hasTable()` guard | ✅ committed |
| B-2 | aero-core `..._000002_create_user_notification_preferences_table` | same | duplicate of aero-notifications table | `hasTable()` guard | ✅ committed |
| B-3 | aero-core `..._000003_create_notification_settings_table` | same | duplicate of aero-notifications table | `hasTable()` guard | ✅ committed |
| B-4 | aero-notifications `notification_templates` | FK to `tenants` fails (standalone & cross-DB SaaS) | `constrained()` inferred FK to absent/cross-DB `tenants` | plain column; conditional FK when `tenants` exists | ✅ committed |
| B-5 | aero-hrm `training_h8_tables` | drop `training_enrollments` blocked by FK | legacy `training_feedback` still referenced it | `disableForeignKeyConstraints()` + drop legacy feedback | ✅ committed |
| B-6 | aero-hrm `create_hrm_benefits_tables` | unique index name > 64 chars | auto-generated name too long | explicit short index name | ✅ committed |
| B-7 | `UatSeeder` (users) | `user_name` NOT NULL no default | seeder omitted `user_name` | set `user_name` from email local-part | ✅ (host) |
| B-8 | aero-auth `AeroAuthServiceProvider` | standalone `/login` 500 `admin.domain` / `landlord` guard | admin.php (SaaS landlord routes) loaded in standalone | do NOT load admin.php in standalone | ✅ verified |
| B-9 | standalone host `app/Http/Controllers/Controller.php` | `Class App\Http\Controllers\Controller not found` | base controller missing in standalone skeleton | add base `Controller` (parity with SaaS) | ✅ (host) |
| B-10 | aero-ui `Pages/Auth/Login.jsx` | `crypto.randomUUID is not a function` → form never mounts | `crypto.randomUUID` undefined on non-HTTPS/non-localhost (`http://*.test`) | UUID v4 fallback (getRandomValues/Math.random) | ✅ verified |
| B-11 | aero-hrmac `CheckRoleModuleAccess::resolveActiveGuard()` | post-login 500 `Auth guard [landlord] not defined` | probed `landlord` guard unconditionally; absent in standalone | skip guards not in `config('auth.guards')` | ✅ verified |
| B-14 | aero-core `Models/Announcement` | dashboard 500 `Unknown column 'status'` then `deleted_at` | model expects `status` + SoftDeletes; live table (aero-hrm) has neither | scope→`published_at`, removed SoftDeletes | ✅ verified |
| B-15 | aero-ui `HRM/Settings/General.jsx` | vite build fail: unbalanced JSX (`</div>`) | extra closing tag | removed stray `</div>` | ✅ build-green |
| B-16 | aero-ui `Pages/**` (50 files) | vite build fail: `useHRMAC` default import | `useHRMAC` is a named export | bulk → `import { useHRMAC } from '@/hooks/useHRMAC'` | ✅ build-green |
| B-17 | aero-ui `Pages/**` (≈342 files) | vite build fail: wrong-depth `App.jsx` relative imports | inconsistent `../` depth resolves to wrong path | normalize all → `@/Pages/App.jsx` | ✅ build-green |
| B-18 | standalone host `.env` | POST /login 419 + https asset CORS block | production forces https assets but `APP_URL=http` (scheme mismatch breaks CSRF/cookies + asset origin) | `APP_URL=https`, suite runs over https | ✅ verified |
| B-19 | aero-hrmac `RoleModuleAccessService::isSuperAdmin` | 500 `Nested arrays may not be passed to whereIn` (non-super-admin) | guard-scoped `super_admin_roles` config passed nested to `hasRole`→`whereIn` | flatten config via `array_walk_recursive` | ✅ verified |
| B-20 | aero-core `create_role_module_access` migration | HR 500 `Unknown column 'status'` | `RoleModuleAccessService` filters `status='active'` but column ships only in aero-hrmac's per-tenant migration (never runs standalone) | add `status`/`suspended_at` to core's base table; guard aero-hrmac add_status with `hasColumn` | ✅ verified |
| B-21 | aero-core `User` / AuditService | `AuditService::log failed: undefined method User::getAuditLabel` | User extends Authenticatable (no `getAuditLabel`) | added `getAuditLabel()` to User | ✅ verified (no more audit errors) |
| B-22 | aero-core nav (DashboardRegistry/NavigationRegistry) | `Navigation error: Nested arrays...whereIn` (caught → empty menu) | nested `super_admin_roles` config → `hasRole`; mitigated defensively in `User::hasRole` (flatten). A residual nav-path whereIn still logs (vendor-deep/lazy, non-fatal) | ✅ verified (0 nav errors in fresh log across all roles × pages; the residual was accumulated pre-fix log lines) |

### SaaS bring-up (P0.4/P0.6) — central never-run path

| ID | Area / file | Bug | Fix | Status |
|----|-------------|-----|-----|--------|
| B-23 | aero-platform `tenant_quota_overrides` migration | central `migrate:fresh` FK fail: `set_by` foreignUuid vs bigint `landlord_users.id` (`update_landlord_users...` migrated id UUID→bigint) | `foreignId('set_by')` | ✅ verified |
| B-24 | aero-platform `create_advanced_billing_tables` | central FK to `users` (absent; central has `landlord_users`) | 7× `constrained('users')`→`constrained('landlord_users')` | ✅ verified |
| B-25 | aero-contracts `AeroMode` + `PlatformHrmacSeeder` | landlord HRMAC roles seeded on central via tenant-scoped Role → A10 guard throws | added `AeroMode::withoutTenantContextGuard()`; wrapped PlatformHrmacSeeder + UatPlatformSeeder landlord step | ✅ verified |
| B-26 | aero-platform `add_scope_and_protection_to_rbac_tables` | central `roles` lacks `is_active` (HRMAC Role writes it) | add `is_active` | ✅ verified |
| B-27 | aero-platform `add_scope_to_modules_table` | `modules.scope` enum('platform','tenant') truncates config scope `infrastructure` → `aero:sync-module` aborts | `enum`→`string` | ✅ verified |
| B-28 | aero-platform `create_modules_table` | central `module_components` lacks `priority` (sync writes it) | add `priority` | ✅ verified |
| B-29 | aero-platform `add_component_actions_to_module_hierarchy` | central `module_component_actions` lacks `is_active` | add `is_active` | ✅ verified |
| B-30 | central module registry empty | `tenant_module` maps codes→central `modules` (TenantCreatedListener) but registry never synced | `UatPlatformSeeder` runs guard-disabled `aero:sync-module --scope=all` after PlatformHrmacSeeder | ✅ verified (modules=6, hrm/core present) |
| B-31 | aero-core/aero-hrm `announcements` migrations | tenant provisioning collision + schema divergence (core status-shape ran before hrm published_at-shape) | core defers to aero-hrm canonical (mirror shape); both `hasTable`-guarded | ✅ verified (provision OK) |
| B-32 | aero-platform `config/tenancy.php` | tenant subdomain assets 404: `asset_helper_tenancy=true` rewrites `@vite`/`asset()` to `/tenancy/assets` | set `asset_helper_tenancy => false` (shared central build) | ✅ verified (login renders) |
| B-33 | aero-core `User::hasAnyRole` | tenant dashboard 500 `Nested arrays...whereIn` (Platform `buildTenantProps`→`isSuperAdmin`→`hasAnyRole(nested config)`) | flatten config in `hasAnyRole` (like `hasRole`) | ✅ verified (302→/dashboard→200) |
| B-30b | `uat_provision.php` | `tenants.type` NOT NULL no default | set `type='company'` + `selected_modules` in data | ✅ (host script) |
| B-35 | aero-auth decoupling (package purity) | aero-auth carried SaaS knowledge (admin.php landlord routes, `admin.domain` shim, dead `LoginController`/`SimpleLoginController` importing `IdentifyDomainContext`, controller branching on `landlord_users`/domain_context) | (1) `AuthenticatedSessionController` now resolves guard+routes via `AuthContext` only; (2) landlord/admin auth routes moved to `aero-platform/routes/admin-auth.php`; (3) deleted 2 dead login controllers + the standalone pass-through shim | ✅ verifying |
| B-37 | aero-core `User` | `/profile` 500 `Call to undefined method getRoleNames()` | User uses custom roles (not Spatie); method missing | added `getRoleNames()` (role-name Collection) | ✅ verified (live) |
| B-38 | aero-hrmac Gate wiring (MAJOR) | every HRM controller `authorize('hrm.x.y.z')` 403'd non-super-admins (HR forbidden from /hrm/employees) | no `Gate::before` delegated dot-path abilities to RoleModuleAccess → undefined ability → default-deny; service granted but Gate didn't | `HRMACServiceProvider::registerHrmacGate()` Gate::before → userCanAccessAction/SubModule | ✅ verified (live: HR reaches employees) |
| B-36 | aero-platform `web.php` /login | platform-domain `/login` renders the tenant login instead of redirecting to `/signup` (web.php intent) | unconstrained tenant `login` route (aero-auth) shadows the domain-constrained platform redirect | **OPEN** — minor (P4 platform routing); not a security hole | ❌ open (P4) |
| B-39 | aero-core `RoleController` (`/roles`) | `/roles` 500 `PermissionRegistrar::$permissionClass null` | RoleController used Spatie `Role`/`Permission` (app uses HRMAC) | swapped to HRMAC Role + module-access counts | ✅ verified (live: /roles renders) |
| B-40 | aero-core role routes (`api/roles` + `roles`) + aero-platform | role CRUD routes wired to `RoleController` methods that didn't exist; only `index` existed → view worked, every create/edit/delete 500'd | controllers never implemented those methods (legacy drift) | P-D rebuilt clean: `Aero\HRMAC\Http\Controllers\RoleController` (index/store/update/destroy/assignUser via audited `RoleService`), single `core.roles.*` group, deleted 2 dead core groups + old controllers; `ModuleController` moved to hrmac; platform `platform.admin.roles.*` adopts the same controller (route-default view) | ✅ verified (live tenant /roles CRUD; platform render verified via controller) |
| B-41 | aero-core/auth/hrmac/platform (8 files) | `/users` 500 `Class Admin\Role not found` | Phase-A perl swap stripped backslashes → `use AeroHRMACModelsRole;` | rewrote to `use Aero\HRMAC\Models\Role;` in all 8 | ✅ verified (live: /users renders) |
| B-34 | aero-platform `LandlordAuthContext::dashboardRoute()` | landlord login OK but post-login redirect targeted `admin.dashboard` (UNDEFINED) → fell back to tenant-scoped `core.dashboard` → 500 `Missing parameter: tenant` | dashboardRoute returned a route name that doesn't exist; real route is `platform.admin.dashboard` | dashboardRoute() → `platform.admin.dashboard` | ✅ fixed (route exists; login completes) |
| B-42 | aero-hrmac `HrmacModel` guard (E1 regression) + aero-platform | platform admin GUEST pages (`/login`) 500 `Role queried outside valid HRMAC context` | context-free HRMAC models now guard-fail-closed; admin guest routes never set `RequestContext=platform` (only `auth:landlord` routes did) → shared Inertia nav queries HRMAC `Module`/`Role` and threw | host-aware default `RequestContext` bind in `AeroPlatformServiceProvider` (admin host → platform) so the whole admin domain incl. guest pages resolves platform context; route middleware still overrides | ✅ fixed (login page renders; auth succeeds) |
| B-43 | aero-platform admin session (landlord guard) | landlord login authenticates server-side (auth log: credentials validated, redirect → platform.admin.dashboard) but fresh navigations to `/dashboard` and `/roles` bounce back to `/login` — session not persisting across requests on admin domain. Previously MASKED by B-34 (login never completed). Not HRMAC-related (role controller verified rendering correct central data in platform context via controller-level test). | platform admin session/cookie or DB-session-on-central nuance (landlord guard); needs dedicated investigation | **OPEN** — platform session infra (P3/P4); blocks browser smoke of platform admin only | ❌ open |

---

## C. Cross-package feature / schema duplication

| ID | Table / feature | Packages defining it | Canonical (live) | Resolution |
|----|-----------------|----------------------|------------------|------------|
| C-1 | `notification_logs` | aero-core, aero-notifications, aero-platform | aero-notifications (only `NotificationLog` model uses it) | core/platform guard with `hasTable`; core's is dead schema |
| C-2 | `user_notification_preferences` | aero-core, aero-notifications | aero-notifications | core guarded |
| C-3 | `notification_settings` | aero-core, aero-notifications | aero-notifications | core guarded |
| C-4 | `announcements` | aero-core (`status`-based), aero-hrm (`published_at`-based) | aero-hrm (created earlier, wins; core create is guarded/skips) | core `Announcement` model realigned to `published_at` (B-14). **Open:** two `Announcement` models + two Dashboard/Announcement controllers — consolidation candidate |
| C-5 | `/login` route | aero-auth tenant.php (web guard) + admin.php (landlord guard) | tenant.php in standalone; admin.php only SaaS | admin.php SaaS-gated (B-8) |

---

## D. Open findings / deferred (tracked in tech-debt ledger)

- **TD-15** — HRM `HrmDemoSeeder` chain (13 seeders) schema drift; UAT decoupled (self-contained `UatSeeder`). `HrmLeaveTypeSeeder` confirmed broken (writes `LeaveSetting`/`leave_global_settings` with leave-type fields); other 11 untriaged.
- **SaaS platform bring-up (P0.4/P0.6)** — central `migrate:fresh` fails on `tenant_quota_overrides`/`feature_usage_events` `set_by` FK **collation** mismatch (uuid `landlord_users.id` `utf8mb4_unicode_ci` vs MySQL 8.4 default); `PlatformHrmacSeeder` queries HRMAC `Role` (TenantModel) on central → tenant-context guard. Then module→`tenant_module`→`ProvisionTenant` HRM-activation chain unverified.
- **B9 decouple (memory)** — aero-auth / aero-hrm hard SaaS imports; this UAT effort confirms broad standalone-boot coupling (admin.php, landlord guard, base Controller, App\Models\User refs).
- **Standalone host skeleton** — was missing base `Controller`; frontend had never been built (multiple latent JSX/import bugs). Standalone fresh-install path had never run end-to-end before this effort.
