# Production-Readiness Audit — Axis B: SaaS ↔ Standalone Parity (Follow-up Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans or superpowers:subagent-driven-development.

**Source:** Axis B walk-through (8 audit questions, 2 batches), 2026-05-30
**Frame:** Every feature must work in BOTH deployment modes from the shared `packages/aero-*` monorepo. Standalone host (`aeos365-standalone`) requires exactly 8 packages: core, auth, installation, i18n, notifications, hrmac, ui, hrm — **not** aero-platform.
**Goal:** Apply the 8 parity decisions the operator made this pass. Every task = an explicit "Recommended" answer.
**Estimated effort:** ~3–5 engineer-days
**Severity headline:** **B1+B2 — standalone has effectively ZERO audit/access logging** (every write routes to a non-existent `central` connection and is silently swallowed), violating the CLAUDE.md audit rule. Fix first.

---

## Decisions captured (this axis)

| # | Area | Decision | Status |
|---|---|---|---|
| **B1** | **Audit routing keyed on unbound `currentTenant`** | **Route by real tenancy signal (`tenancy()->initialized`/`tenant()` + AeroMode), not an unbound container key** | ⚠️ **task (P0)** |
| **B2** | **No `central` connection in standalone** | **Add `central_connection()` resolver (SaaS→'central', standalone→default); route all central refs through it** | ⚠️ **task (P0)** |
| B3 | CentralModel subclasses ship in standalone | `CentralModel::getConnectionName()` resolves via `central_connection()`; audit reachability + migrate needed tables | ⚠️ task |
| B4 | No parity guard for SaaS-only deps | Standalone parity test (boot 8-pkg set, no `central` alias) + static guard; stop masking `central` in package-test `setUp()` | ⚠️ task |
| B5 | Two divergent `NotificationLog` models | One authoritative model for the idempotency contract; document/rename or consolidate the other | ⚠️ task |
| B6 | Standalone audit-target table ownership | Pin standalone audits to aero-core `audit_logs`/`access_logs`; migration-presence test | ⚠️ task |
| B7 | Tenant-migration source-set parity | Assert SaaS tenant-migration path == standalone installer path over the same package set | ⚠️ task |
| B8 | Frontend mode signal SaaS-only | Always emit `props.aero = {mode: aero_mode()}` in both modes | ⚠️ task |

**Verified clean (no task — recorded so they aren't re-audited):**
- ⚠️ **CORRECTION (2026-05-30, during execution):** the original "no `Aero\Platform\` references" claim was a FALSE-CLEAN — the audit grep was fooled by backslash escaping (searched single-backslash, missed double-backslash string literals). Reality: **5 files hard-import `use Aero\Platform\...;`** — aero-auth's AdminSetup/Impersonation/Login/UserController (Tenant, TenantImpersonationToken, platform Http Requests+Resources, IdentifyDomainContext) and aero-hrm's `AeroHrmServiceProvider` (AeroPlatformServiceProvider). These are genuine class-not-found risks in standalone. **B4's `StandaloneParityGuardTest` ratchet (budget 5) now locks the count + blocks new ones; decoupling these 5 is open Axis-B debt (see B9 below).** Guarded `class_exists('Aero\\Platform\\...')` soft-references remain the sanctioned pattern and are fine.
- ✅ `LicenseService` (aero-core) is parity-correct: `status()` short-circuits to `'saas'` in SaaS (never license-checks); standalone has domain binding, cache, online check, and a 72h **offline grace** fallback (the audit's "offline fallback deferred" note is stale — grace is implemented).
- ✅ `aero_mode()` (helpers.php) is parity-safe: the `central`-connection schema fallback is wrapped in try/catch and correctly defaults to `standalone` when `central` is absent. Primary source is the `aeos.mode` file.
- ✅ `SyncModuleHierarchy` tenant-catalog filter (Audit D15) is guarded by `function_exists('tenancy') && tenancy()->initialized` → skipped in standalone → all modules sync. Parity-safe.
- ✅ aero-core `HandleInertiaRequests` is defensive: null-guards user/permissions, `class_exists`/try-catch around HRMAC lookups, mirrors Platform's HRMAC props for standalone, only adds SaaS props under `is_saas_mode() && tenant()`.
- ✅ `RequireSaasMode` middleware lives only in aero-platform (not loaded in standalone) → cannot over-block standalone features.
- ✅ aero-notifications owns its own `NotificationLog` (carries the idempotency contract) — see B5 for the drift caveat.

---

## File Structure (net-new + modified)

| File | Responsibility | Task |
|---|---|---|
| `packages/aero-core/src/helpers.php` | Add `central_connection()` helper | B2 |
| `packages/aero-core/src/Services/Audit/AuditService.php` | Route by tenancy signal + `central_connection()` | B1, B2, B6 |
| `packages/aero-contracts/src/Models/CentralModel.php` | `getConnectionName()` resolves via resolver | B2, B3 |
| `packages/aero-core/tests/Feature/Parity/StandaloneBootParityTest.php` (new) | Boot 8-pkg set, no `central` alias, exercise core flows | B4 |
| `aeos365-standalone/tests/Feature/Parity/...` (host mirror, optional) | Same assertions in the real standalone host | B4 |
| `aeos365/tests/Feature/Wiring/NoSaasOnlyDependencyTest.php` (new) | Static guard: standalone pkgs must not hard-ref `central`/aero-platform | B4 |
| package test base classes (`PackageTestCase`, platform setUp) | Stop aliasing `central`→sqlite once resolver lands (or gate alias to SaaS tests) | B4 |
| `packages/aero-notifications/...` + `packages/aero-platform/src/Models/NotificationLog.php` | Designate authoritative model; rename/document the other | B5 |
| `packages/aero-core/database/migrations/*audit*` | Confirm `audit_logs`/`access_logs` ship + are standalone target | B6 |
| `packages/aero-installation/.../MigrationStep.php` + `packages/aero-platform/config/tenancy.php` | Source-set parity (shared migration set) | B7 |
| `packages/aero-core/tests/Feature/Parity/MigrationSourceParityTest.php` (new) | Assert identical package-migration set across both paths | B7 |
| `packages/aero-core/src/Http/Middleware/HandleInertiaRequests.php` | Always emit `props.aero.mode` | B8 |

---

## Task B2 — `central_connection()` resolver (foundation; do first)

The `central` connection is registered ONLY by `AeroPlatformServiceProvider:758` (SaaS-only). In standalone it never exists. Every `DB::connection('central')` / `CentralModel` use in a standalone-loaded package therefore throws.

**Files:** `packages/aero-core/src/helpers.php`

```php
if (! function_exists('central_connection')) {
    /**
     * The connection name for central/landlord data.
     * SaaS: 'central' (registered by aero-platform).
     * Standalone: the default connection — there is one DB, central == app DB.
     */
    function central_connection(): string
    {
        if (is_saas_mode() && config('database.connections.central')) {
            return 'central';
        }
        return config('database.default'); // standalone single DB
    }
}
```

- [ ] Add helper; test both modes (SaaS with central configured → 'central'; standalone → default).
- [ ] Commit: `feat(core): central_connection() resolver for SaaS/standalone parity (B2)`

---

## Task B1 — Audit routing by real tenancy signal (P0)

`AuditService::writeAuditLog/writeAccessLog` branch on `app()->bound('currentTenant')` — **nothing binds `currentTenant`** (only AuditService reads it; ImpersonationController sets it transiently). So `$isPlatform` is effectively always true → every write targets `DB::connection('central')->table('platform_audit_logs')`. In standalone that throws (no `central`) → swallowed by the method's try/catch → **all audit + access logging silently lost**.

**Files:** `packages/aero-core/src/Services/Audit/AuditService.php`

```php
private function isPlatformContext(): bool
{
    // SaaS: platform context == no tenant initialized. Standalone: never "platform".
    if (! is_saas_mode()) {
        return false;
    }
    return ! (function_exists('tenancy') && tenancy()->initialized);
}

private function writeAuditLog(array $data): void
{
    if ($this->isPlatformContext()) {
        DB::connection(central_connection())->table('platform_audit_logs')->insert($data);
    } else {
        DB::table('audit_logs')->insert($data); // tenant DB (SaaS) or single DB (standalone)
    }
}
// same shape for writeAccessLog → access_logs
```

- [ ] Keep the outer try/catch (never break a business op) BUT add a `Log::error` that is loud enough to alert if audit writes fail (a swallowed audit is a compliance hole).
- [ ] Tests: tenant-context audit lands in tenant DB (SaaS) and single DB (standalone); platform-context audit lands in central (SaaS).
- [ ] Commit: `fix(audit): route by real tenancy signal — restore standalone audit logging (B1)`

---

## Task B3 — CentralModel resolves connection in standalone

`SocialAuthAccount` (aero-auth) and `LandlordRoleModuleAccess` (aero-hrmac) extend `CentralModel` and BOTH ship in standalone, yet `CentralModel` hard-pins `$connection = 'central'`.

**Files:** `packages/aero-contracts/src/Models/CentralModel.php`

- [ ] Replace the static `$connection` pin with a resolved one:

```php
public function getConnectionName(): ?string
{
    return central_connection(); // 'central' in SaaS, default in standalone
}
// Update the creating/saving re-pin to setConnection(central_connection()).
```

- [ ] Reachability audit: enumerate every `CentralModel` subclass present in the standalone package set (`SocialAuthAccount`, `LandlordRoleModuleAccess`, …); confirm the tables they need are in the standalone migration set, or SaaS-gate the feature.
- [ ] Test: exercise `SocialAuthAccount` query in a standalone-shaped app (no `central`) → no throw.
- [ ] Update the CentralModel docstring (remove the now-false "do not extend in standalone").
- [ ] Commit: `fix(contracts): CentralModel resolves central_connection() for standalone parity (B3)`

---

## Task B4 — Standalone parity test + static guard

The B1–B3 breaks hid because package test suites alias `central`→sqlite in `setUp()` (masking the missing connection) and there's no boot-the-standalone-set parity test.

**Files:** new parity test + static guard; adjust test base classes.

- [ ] **Parity boot test:** configure an app with ONLY the 8 standalone providers, `AERO_MODE=standalone`, and **no** `central` connection. Exercise: login, an audit write, a `SocialAuthAccount` read, an HRMAC access check. Assert none throw on a missing `central`.
- [ ] **Static guard** (`NoSaasOnlyDependencyTest`): scan standalone-eligible packages for literal `connection('central')` / `'central'` connection strings and `Aero\Platform\` references; fail on any that aren't routed through `central_connection()` or SaaS-gated.
- [ ] Stop masking: once the resolver lands, remove the `database.connections.central => sqlite` alias from standalone-relevant `setUp()` (or gate it to SaaS-only test cases) so the masking can't return.
- [ ] Commit: `test(parity): standalone boot parity + SaaS-only dependency guard (B4)`

---

## Task B5 — One authoritative NotificationLog

`Aero\Notifications\Models\NotificationLog` (idempotency contract) vs `Aero\Platform\Models\NotificationLog` (CentralModel).

- [ ] Confirm `SendEmailJob`/`SendSmsJob` use the aero-notifications model in both modes (it owns `makeIdempotencyKey`/`alreadyDispatched`).
- [ ] If the platform model is a genuinely distinct platform-comms log, rename it (e.g. `PlatformNotificationLog`) to kill the ambiguity; otherwise consolidate.
- [ ] Test: dedup path is identical in SaaS + standalone.
- [ ] Commit: `refactor(notifications): single authoritative NotificationLog for idempotency (B5)`

---

## Task B6 — Pin standalone audit tables to aero-core

- [ ] Ensure B1's standalone branch writes to aero-core's `audit_logs`/`access_logs` (shipped in the standalone migration set) — never the aero-platform-only `platform_*` tables.
- [ ] Migration-presence test (standalone): after install, `audit_logs` + `access_logs` exist.
- [ ] Commit: `fix(audit): standalone audit target tables owned by aero-core (B6)`

---

## Task B7 — Tenant-migration source-set parity

SaaS migrates tenant tables via `tenancy.php` `migration_parameters` paths; standalone via the installer `MigrationStep`. Two execution paths, intended same source set.

- [ ] Parity test asserting the package-migration set applied by the SaaS tenant path equals the set applied by the standalone installer path (per shared package) — a migration added to one path can't silently miss the other.
- [ ] Document both execution paths in `deploy/README.md`.
- [ ] Commit: `test(parity): assert identical tenant-migration source set across modes (B7)`

---

## Task B8 — Always emit `props.aero.mode`

**Files:** `packages/aero-core/src/Http/Middleware/HandleInertiaRequests.php`

- [ ] Always share `'aero' => ['mode' => aero_mode()]` (both modes), not only under `is_saas_mode() && tenant()`.
- [ ] Grep aero-ui for `props.aero?.mode` / mode branches; ensure both `'saas'` and `'standalone'` are handled.
- [ ] Commit: `fix(ui): always emit aero.mode prop for explicit standalone frontend branch (B8)`

---

## Task B9 — Decouple aero-auth + aero-hrm from aero-platform (NEW — found during B4 execution)

5 files hard-import `Aero\Platform\` classes in standalone-eligible packages:
- `aero-auth/Http/Controllers/Auth/{AdminSetup,Impersonation,Login,User}Controller.php` — import `Tenant`, `TenantImpersonationToken`, platform Http `Requests`/`Resources`, `IdentifyDomainContext`.
- `aero-hrm/AeroHrmServiceProvider.php` — imports `AeroPlatformServiceProvider`.

**Approach:** move the shared Tenant/impersonation contracts + Http Requests/Resources to aero-contracts or aero-core (or gate the platform-only controllers behind `class_exists`/SaaS-only route registration); have aero-hrm reference a contract, not the concrete platform provider. Lower `StandaloneParityGuardTest::PLATFORM_IMPORT_BUDGET` per file decoupled until 0.

**Status:** ⚠️ open debt — ratchet (B4) prevents regression; decoupling is a multi-file refactor best done as its own focused unit (not landed in this pass).

---

## Execution order

1. **B2** (resolver — foundation for B1/B3/B6).
2. **B1** (P0 — restore standalone audit logging).
3. **B6** (pin the audit target table B1 writes to).
4. **B3** (CentralModel resolution) → unblocks standalone social auth etc.
5. **B4** (parity test + static guard; stop masking) — locks B1/B2/B3 against regression.
6. **B5** (NotificationLog) — independent.
7. **B7** (migration parity) — independent.
8. **B8** (frontend mode prop) — independent.

Subagent-driven fine for B5/B7/B8; B1+B2+B3+B6 are tightly coupled — do as one focused unit.

---

## Self-Review

- ✅ Every "Recommended" answer (B1–B8) maps to a task with files + test stub + commit draft.
- ✅ Verified-clean parity items recorded (no platform imports, license, mode detection, guarded catalog filter, defensive Inertia middleware, RequireSaasMode placement) so they aren't re-audited.
- ✅ Root cause is singular and clean: `central` is a SaaS-only construct (registered by aero-platform) referenced by standalone-loaded packages without a resolver — B2 fixes it once; B1/B3/B6 consume it.
- ✅ B4 explicitly addresses WHY the breaks were invisible (test-time `central`→sqlite masking) and stops it.
- ⚠️ Cross-axis link: B1's tenancy-signal routing and Axis A's `currentTenant`-vs-`tenant()` observations overlap — coordinate so the audit-routing fix and the tenancy-guard work don't collide.
