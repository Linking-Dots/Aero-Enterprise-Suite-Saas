# Architecture Audit — Dual-mode (SaaS + Standalone) Production Readiness

**Date:** 2026-05-30
**Frame:** AEOS365 ships as BOTH multi-tenant SaaS (host: `aeos365`) AND single-tenant Standalone (host: `aeos365-standalone`) from a shared `packages/aero-*` monorepo. Audit every architectural decision for production readiness in both modes.

---

## 1. Multi-tenancy Architecture

1.1 **Tenancy library**: `stancl/tenancy` v3, configured for **database-per-tenant** with **subdomain identification**. Each tenant gets a MySQL database; the central app uses a separate `central` connection.

1.2 **Mode resolver**: `AeroMode` singleton in `aero-contracts` reads `AERO_MODE` env var (`saas` | `standalone`). `aero-core` sets the resolver during `ServiceProvider::register()`. Standalone is the default when no resolver is configured (tests, queue workers, early boot).

1.3 **TenantModel base class** (`aero-contracts`) installs a global scope that calls `AeroMode::assertTenantContext()` on every query. In SaaS mode this throws `LogicException` if a tenant model is queried without an active tenancy context. In standalone mode it's a no-op.

1.4 **CentralModel base class** pins `$connection = 'central'` AND re-pins on every `creating`/`saving` event. Defense-in-depth against accidental cross-DB writes.

1.5 **Tenancy bootstrappers** (active in `packages/aero-platform/config/tenancy.php`):
   - `DatabaseTenancyBootstrapper` (always on)
   - `CacheTenancyBootstrapper` (Phase 0 T4 — requires Redis)
   - `FilesystemTenancyBootstrapper` (Phase 0 T5 — requires `tenancy.filesystem.{suffix_base,disks,root_override}` block)
   - `QueueTenancyBootstrapper` (always on)

1.6 **BYOC (Bring Your Own Cloud)**: tenant table has `byoc_db_host`, `byoc_db_username`, `byoc_db_password`, `byoc_db_*` columns cast via `EncryptedField`. `ProvisionTenant` job overlays these into runtime config; restored to original in `finally{}` so subsequent queue jobs don't inherit (Plan 03 T11).

1.7 **Tenant identification**: only `Stancl\Tenancy\Middleware\InitializeTenancyByDomain` (canonical). The legacy custom `IdentifyTenant` middleware deleted in Phase 0 T3.

1.8 **Reserved subdomain list** (~50 entries) blocks tenants from hijacking platform infra DNS (`mail.`, `static.`, `cdn.`, `admin.`, `horizon.`, etc.). Enforced in `CheckRegistrationSubdomainRequest`, `RegistrationDetailsRequest`, and `TenantController::checkSubdomain`.

1.9 **Tenant retention & purge**: soft-delete + scheduled `PurgeExpiredTenants` command runs daily. `tenancy.retention.{days, auto_purge, notify_before_purge_days}` configurable.

1.10 **`tenants.status` index** added in Phase 0 T6 — closes the full-table-scan risk on `scopeActive/Suspended/Provisioning`.

1.11 **`rollbackDatabase` safety**: refuses to DROP databases whose name doesn't start with the configured tenant prefix OR matches the central DB. Phase 0 T12 closed the catastrophic-data-loss vector.

1.12 **Standalone mode** uses a single `mysql` connection (no `central`), `AERO_MODE=standalone` env var, single DB instance. No tenancy bootstrappers fire. `TenantModel` global scope short-circuits to a no-op.

---

## 2. Authentication & Authorization

2.1 **Two auth guards**:
   - `web` (tenant users, tenant DB)
   - `landlord` (platform admins, central DB)
   Same Sanctum tokens issuable to both via aero-core's ApiKey surface.

2.2 **HRMAC** (Hierarchical Role-Module Access Control): permission paths are `module.submodule.component.action` dot-notation. Enforced by `CheckRoleModuleAccess` middleware which casades from module → sub-module → component → action.

2.3 **Super-admin bypass**: `config('hrmac.super_admin_roles')` is now **guard-scoped** (Plan 04 T2): `['landlord' => [...], 'web' => [...], 'api' => []]`. A tenant user with a role literally named "Super Administrator" can no longer bypass landlord-guarded routes.

2.4 **`is_active` enforcement**: `RoleModuleAccessService::userCanAccess*()` filters every Module/SubModule/Action query by `is_active = true`. A module disabled via DB denies access at runtime even to users holding the grant (Plan 04 T3).

2.5 **HRMAC denial persistence**: `CheckRoleModuleAccess` middleware invokes `HrmacAuditService::logDenial()` which writes to the `hrmac_audit_log` table (Plan 04 T1). Compliance dashboards can query denials by user/module/date.

2.6 **Spatie Permission** as the underlying role/permission engine (HRMAC layers on top of it).

2.7 **Sanctum/PAT** for REST API auth. Tokens issued via aero-core ApiKey admin. Token's permissions equal the user's HRMAC grants (no separate API-permission model).

2.8 **Login rate limit** (Plan 05 T2): per-IP (5/60s) AND per-email (5/15min, sha1-hashed key, lowercased). Closes cross-IP brute-force against a single account.

2.9 **Password reset rate limit + anti-enumeration** (Plan 05 T1): per-email (5/hour) + per-IP (10/10min). Returns uniform response regardless of email existence: `"If an account with that email exists, a password reset link has been sent."`

2.10 **Device binding**: every login requires a UUIDv4 `device_id` from the frontend. `DeviceAuthService::canLoginFromDevice()` blocks the login if the device is suspended.

2.11 **Impersonation flow** (`aero-auth/ImpersonationController`):
   - Single-use token, 60-min TTL
   - `SafeRedirect::isSafePath()` blocks open-redirect via tampered `redirect_url` (Plan 05 T3)
   - Target user found via `config('hrmac.super_admin_roles.web')` (Plan 05 T4 — no hardcoded role-name string)
   - Audit via `AuditService` + Spatie activity log
   - Session flag `impersonated_by_platform` for UI banner

2.12 **Auth events centralized** on `AuditServiceInterface` (Plan 05 T5). `AuthEventSubscriber::logActivity()` no longer uses Spatie `activity()` directly.

2.13 **MFA / 2FA**: Spatie 2FA library, backup codes, trusted device sessions. Routes: `/2fa/setup`, `/2fa/challenge`. Recovery flow exists.

2.14 **SSO**: SAML, OIDC, OAuth Provider, SCIM, Magic Link, Passkeys all declared under `auth.sso_identity.*` in `aero-auth/config/module.php` (moved from aero-core in Plan 05 T6). Each has a config controller; provider config stored in tenant DB.

2.15 **Account lockout**: `authService->isAccountLocked($email)` checks failed attempt count; lockout duration in `config('auth.lockout.duration', 60)` minutes.

---

## 3. Data Isolation & Encryption

3.1 **Cache isolation**: `Stancl CacheTenancyBootstrapper` auto-prefixes keys per tenant. Requires Redis (file/database drivers don't support tagging). `TenantCache` helper (`aero-core/src/Support/TenantCache.php`) provides explicit `tenant:{id}:{key}` prefix for code that needs the raw API.

3.2 **AdminDashboardService cache** (Plan 02 T1): all 12 dashboard widgets use `TenantCache::remember()` — previously leaked across tenants on file/database cache drivers.

3.3 **Filesystem isolation**: `FilesystemTenancyBootstrapper` auto-suffixes `local` and `public` disk roots per tenant. `FileManagerController` (Plan 02 T11) whitelist allows only `local` and `public` — refuses 422 for `s3` or any other disk (those aren't tenancy-aware unless added to `tenancy.filesystem.disks`).

3.4 **PII at rest**: `EncryptedField` cast applied to:
   - `byoc_db_username`, `byoc_db_password`, `byoc_db_*` (on `Tenant`)
   - `bank_account_number`, `bank_name`, `bank_routing_number` (on `Payslip`)
   - `tax_id`, `national_id` (on Employee, OrgProfile)
   - `medical_notes` (on Employee)

3.5 **Audit trail**: `AuditServiceInterface::log()` for business events; `logAccess()` for PII reads. `CoreUserController` audits all lifecycle mutations (Plan 02 T3).

3.6 **Immutability observers**:
   - `PayrollRun`: locked after approval → `Payslip` updates/deletes throw `PayrollLockedException`
   - `Invoice`: voiding the only mutation post-finalize
   - `Subscription`: status transitions via state machine
   - `PerformanceReview`: locked after finalization

3.7 **HelpSupport tables** (Plan 02 T5): `support_tickets`, `feedback_items` now exist as Eloquent models with TenantModel base — previously queried via raw `DB::table()` against non-existent tables (production crash).

3.8 **Raw `DB::table()` discipline**: Phase 1 found 30+ sites; aero-core's API/Webhook/Audit controllers should use Eloquent models. CI guard (`FacadeDisciplineTest`) catches direct `Cache::`/`Session::`/`Storage::disk('local')` use in feature packages.

---

## 4. Module System

4.1 **Per-package `config/module.php`**: declares the module's code, name, scope (`tenant`|`platform`|`infrastructure`), dependencies, submodules, components, actions.

4.2 **`ModuleDiscoveryService`** (aero-hrmac): scans `vendor/aero/*/config/module.php`, `modules/*/config/module.php`, and `packages/aero-*/config/module.php` (Plan 04 T4 — added monorepo dev path).

4.3 **`SyncModuleHierarchy` command**: writes to `modules`, `sub_modules`, `module_components`, `module_component_actions` tables. Advisory lock prevents race conditions on concurrent runs (Plan 04 T5).

4.4 **`required_fields`** for discovery validation: `['code', 'name', 'scope']` (Plan 04 T4 — was `['module_key', 'label', 'scope']`).

4.5 **`AbstractModuleProvider`** in aero-contracts: each module package's ServiceProvider extends it. Auto-registers routes, migrations, views, etc.

4.6 **`is_active` flag on Module**: gates ACCESS at runtime via `RoleModuleAccessService` (Plan 04 T3). Routes still register at boot (URL stability); access check denies if module is `is_active=false`.

4.7 **Module-level vs submodule-level licensing**: HRM has per-submodule licensing via `tier_licensing` map (H.T3). Other packages still module-level `min_plan` only.

---

## 5. Frontend (aero-ui)

5.1 **Stack**: React 18, Inertia v2, HeroUI design system, Tailwind utility classes, Vite build. 422 JSX files; 0 tests.

5.2 **Inline-style discipline**: ESLint config blocks `style={...}` on components + DOM elements. PHP ratchet test (`UiInlineStyleDisciplineTest` in host) with `VIOLATION_BUDGET = 165` runs in CI. Current real violations: 155.

5.3 **`useHRMAC` React hook**: reads `auth.user.permissions_map` from Inertia shared props. Wildcard `*` for super-admins. Used in 219 of 422 JSX files (~52% adoption).

5.4 **Aeos utility CSS** (`packages/aero-ui/resources/css/components/utilities.css`): aeos-icon-{xs,sm,md,lg,xl}, aeos-flex-1, aeos-text-{center,left,right}, aeos-justify-{between,center,end}, aeos-content-{narrow,base,wide}, aeos-pill-surface, aeos-surface-chip, aeos-code-block, etc. 189 inline-style sites migrated.

5.5 **Inertia useForm + router**: 190 files use `useForm`, 305 use `router.*`. Zero Inertia v1 patterns remain.

5.6 **Branded error pages**: 403, 404, 500, 503, 419, 429 ship in both hosts (Phase 0 T14). Standalone-rendered (no Vite dependency) so they work during outages.

---

## 6. Background Processing

6.1 **Queue driver**: `QUEUE_CONNECTION=redis` in production `.env.example` for both hosts. Phase 0 T1+T2 replaced the dev-shaped `sync`.

6.2 **Horizon**: NOT installed — `composer require laravel/horizon` is operator action. Horizon supervisor config (`deploy/supervisor/aeos365-horizon.conf`) ships in the repo ready to deploy.

6.3 **Queue tenancy**: `Stancl QueueTenancyBootstrapper` re-binds the tenant DB connection at the start of every queued job. Jobs serialize the tenant ID; bootstrapper resolves and switches before `handle()` runs.

6.4 **Critical jobs**:
   - `ProvisionTenant`: `$tries=3`, `$backoff=[30,60,120]`, `$timeout=600`, `$maxExceptions=3`
   - `ProcessSubscriptionRenewalsJob`: uses polymorphic `Subscription::query()` (Plan 03 T2 — was broken)
   - `RetryFailedPaymentsJob`: real Stripe via `SubscriptionBillingService` (Plan 03 T3 — was a `rand()` stub)
   - `AggregateTenantStats`: `chunk(10)` + per-tenant `tenancy()->initialize/end`
   - `SendEmailJob`, `SendSmsJob`: idempotency via `NotificationLog::makeIdempotencyKey()` (Plan 08 T2)

6.5 **Scheduler**: `app/Console/Kernel.php` or `routes/console.php` schedules `tenant-stats:aggregate`, `subscription-renewals:process`, `payments:retry-failed`, `tenants:purge-expired`. Driven by supervisor program `aeos365-scheduler.conf`.

---

## 7. Billing & Subscriptions

7.1 **Library**: Laravel Cashier (Stripe). `Tenant` model uses `Billable` trait.

7.2 **Subscription polymorphism**: `subscriptions.billable_id` + `billable_type` (Cashier polymorphic). Legacy `tenant_id` column still present as bridge — Plan 03 T8 backfill + drop deferred until staging verification.

7.3 **Plan model**: `plans` table with `code`, `name`, `price_monthly`, `price_yearly`, `stripe_price_id_monthly`, `stripe_price_id_yearly`. Soft-deletes.

7.4 **`PlatformAnalyticsService::tenantAnalytics()`** (Plan 03 T1): plan-distribution query now joins polymorphic Subscription correctly — previously SELECTed non-existent `tenants.plan_id` column.

7.5 **`SubscriptionBillingService`** (Plan 03 T2+T3): `chargeRenewal()` + `retryPayment()`. Stripe path via Cashier; throws `BillingGatewayNotConfiguredException` when no gateway set up (instead of silent fake-success).

7.6 **HRM per-submodule licensing** (H.T3): 27 submodules mapped to `free`/`basic`/`professional`/`enterprise`. `HrmTierLicenseService` resolves required tier + checks against current tenant tier. Enforcement middleware deferred to follow-up.

7.7 **Standalone licensing**: `LICENSE_BYPASS=false` in `.env.example`. `LicenseStep` in installer activates against `LICENSE_SERVER_URL`. Offline activation fallback deferred (Plan 09 T4).

---

## 8. API Surface

8.1 **HRM REST API** (H.T2): `/api/hrm/employees`, `/api/hrm/leave-applications`, `/api/hrm/attendance/{today,clock-in,clock-out}`. Auth: `auth:sanctum`. Throttle: `60,1`. HRMAC enforced inside each method.

8.2 **`boundedPerPage` helper** on base Controller (Phase 0 T10): default 20, max 100. Closes `?per_page=999999` DOS vector.

8.3 **API response shape**: `{ data: [...], meta: {current_page, last_page, per_page, total}, links: {first, last, prev, next} }`.

8.4 **Idempotency**: `AttendanceApiController::clockIn` uses `firstOrCreate` — mobile retry doesn't create duplicate rows.

8.5 **No public webhook surface yet**: aero-core has `Webhook` model but no `/api/webhooks/{provider}` receiver endpoints.

---

## 9. Installation

9.1 **`UnifiedInstallationController`** + step-based `InstallationOrchestrator` (12 steps: AdminUser, Cache, Configuration, DatabaseConnection, Finalize, License, Migration, ModuleDiscovery, PlanSeeding, PlatformConfiguration, Seeding, Settings).

9.2 **`ModeDetector`**: reads `AERO_MODE` env to detect SaaS vs standalone. Drives which steps run + their parameters.

9.3 **`MigrationStep` dirty-schema guard** (Plan 09 T3): refuses `migrate:fresh` if the target DB has tables without a `migrations` history. `FORCE_CLEAN_INSTALL=true` env override available.

9.4 **`BootstrapGuard` middleware**: redirects to `/install` when not installed AND not in SaaS-with-platform mode. After install completes, 404s any `/install*` request (Plan 09 T5).

9.5 **Installation state**: file-based detection via `InstallationState::isInstalled()`. Per-step idempotency hooks declared in `BaseInstallationStep` but most steps don't override them yet (Plan 09 T2 deferred).

---

## 10. Observability

10.1 **Logging**: `LOG_CHANNEL=stack`, `LOG_STACK=daily,stderr` (+ optional `sentry`). `config/logging.php` published in both hosts (Phase 0 T9).

10.2 **Sentry**: declared in `.env.example` as `SENTRY_LARAVEL_DSN=`. Package install deferred to operator (`composer require sentry/sentry-laravel && php artisan sentry:publish`).

10.3 **Health checks**: `HealthCheckController` provides `index()` (LB-friendly) + `detailed()` (DB, cache, queue, redis, memory, disk, storage). Wired at `/api/platform/v1/health` (platform) and route in aero-core for tenant.

10.4 **AuditService**: persistence via `AuditLog` model (tenant DB for tenant events; central DB for platform events). `logAccess()` for PII reads. Used by 16 of 372 aero-core files (Phase 1 found ~4% coverage; CoreUserController now audits all mutations).

10.5 **Activity log**: Spatie ActivityLog for model-level activity (`LogsActivity` trait on Payslip, Tenant, etc.). Separate channel from `AuditService` — Phase 1 flagged the fragmentation; auth events now centralized on AuditService (Plan 05 T5).

10.6 **HrmacAuditLog**: separate table for HRMAC denial events (Plan 04 T1).

10.7 **Authentication events table**: `authentication_events` table tracks login/logout/failed/MFA events. Separate from generic audit.

---

## 11. Hosts & Deployment

11.1 **Two host apps**:
   - `c:/laragon/www/aeos365` — SaaS multi-tenant host. Main branch.
   - `c:/laragon/www/aeos365-standalone` — single-tenant deployment. Main branch.

11.2 **Both hosts are SEPARATE git repos** with their own commit history. Monorepo provides packages via composer path repo (`"repositories": [{"type": "path", "url": "../Aero-Enterprise-Suite-Saas/packages/*", "options": {"symlink": true}}]`).

11.3 **Production-shaped `.env.example`** in both hosts (Phase 0 T1+T2): Redis cache/sessions/queue, S3 filesystem (SaaS), Sentry DSN placeholder, AERO_MODE explicit.

11.4 **Supervisor configs** in `deploy/supervisor/`: Horizon (SaaS), scheduler loop, standalone worker fallback.

11.5 **Deploy README** at `deploy/README.md`: prerequisites, install steps, migration order (central → tenant), supervision setup, rollback procedure, zero-downtime outline.

11.6 **Branded error pages** in both hosts: 403, 404, 419, 429, 500, 503 with inline CSS (works during Vite outage).

11.7 **CI workflow** (`.github/workflows/wiring-guards.yml`): runs `Tests\Feature\Wiring\*` (env shape, single-tenant identification, facade discipline, inline-style ratchet, health checks).

---

## 12. HRM-specific

12.1 **HRM package boundary** (H.T1): `PackageBoundaryTest` blocks finance/CRM/commerce migrations in aero-hrm. Finance migrations moved to aero-finance/database/migrations/.

12.2 **HRM REST API** (H.T2): see §8.1.

12.3 **HRM per-submodule licensing** (H.T3): see §7.6.

12.4 **Payslip immutability** (H.T4): booted() updating/deleting listeners throw `PayrollLockedException`. EncryptedField on bank_*. LogsActivity trait. Structural pin via `PayslipImmutabilityContractTest`.

12.5 **Payroll → Finance bridge**: HRM payroll runs SHOULD emit finance journal entries to `finance_accounts` + `finance_journal_entries`. The migrations exist (moved in H.T1); the bridge service has NOT been written.

12.6 **HRM has 65+ tests** covering controllers, services, models. Critical paths (Payroll immutability, Leave application, Attendance) have feature tests.

12.7 **HRM standalone shippable**: aeos365-standalone composer requires exactly 8 packages (core, auth, installation, i18n, notifications, hrmac, ui, hrm). HRM has zero hard imports of feature packages (finance, workflow, forms, custom-fields).
