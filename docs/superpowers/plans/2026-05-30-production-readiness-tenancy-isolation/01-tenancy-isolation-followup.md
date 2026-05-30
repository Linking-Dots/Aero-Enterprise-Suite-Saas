# Production-Readiness Audit — Axis A: Tenancy Isolation (Follow-up Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans or superpowers:subagent-driven-development.

**Source:** Axis A walk-through (12 audit questions, 3 batches), 2026-05-30
**Frame:** Prove there is zero cross-tenant data-leak path in SaaS mode. Builds on the architecture audit (D1–D34) and its follow-up (`../2026-05-28-foundation-10-10/17-architecture-audit-followup.md`).
**Goal:** Apply the 12 isolation decisions the operator made during this pass. Every task corresponds to an explicit "Recommended" answer.
**Estimated effort:** ~4–6 engineer-days
**Severity headline:** **A5 is a live cross-tenant FILE leak in SaaS** — a runtime `Config::set()` strips `FilesystemTenancyBootstrapper` out, so tenant uploads share one storage root. Fix first.

---

## Decisions captured (this axis)

| # | Area | Decision | Status |
|---|---|---|---|
| A1 | Cache driver isolation | Add boot-time fail-closed assertion (SaaS + non-tagging driver → throw); document Redis as hard SaaS requirement; keep `CachePrefixTenancyBootstrapper` as opt-in fallback | ⚠️ task |
| A2 | S3 / local isolation | Add behavior test (write as tenant A, assert tenant B cannot read) — **after A5 re-enables filesystem tenancy** | ⚠️ task |
| A3 | Raw `DB::table()` bypass | Add budget-ratchet wiring test for raw DB access to tenant-scoped tables in feature packages | ⚠️ task |
| A4 | GDPR forget atomicity | Reorder: delete row in txn → commit → drop DB outside → reconcile on failure; failure-path test | ⚠️ task |
| **A5** | **Runtime bootstrapper override** | **Delete the `boot()` `Config::set('tenancy.bootstrappers', …)` override; make `config/tenancy.php` authoritative (Filesystem + FailClosed restored)** | ⚠️ **task (P0)** |
| A6 | Config-vs-runtime drift | Add Feature wiring test asserting the **booted** `config('tenancy.*')`, not the file | ⚠️ task |
| A7 | Subdomain validation drift | Single source of truth: delete hardcoded list in `checkSubdomain`; unify max-length; agreement test | ⚠️ task |
| A8 | Suspended-tenant web access | Flush tenant resolution cache on suspend/archive; route-coverage test for `tenant.active` | ⚠️ task |
| A9 | Drop-database safety drift | Extract shared `TenantDatabaseDropGuard`; route all 3 drop paths through it | ⚠️ task |
| A10 | Tenant-context guard fails open | Fail **closed** in SaaS; narrow early-boot allowance; test | ⚠️ task |
| A11 | Purge atomicity | Shared reorder+reconcile sequencing helper (composes with A4) | ⚠️ task |
| A12 | BYOC isolation boundary | Document BYOC as DB-residency-only (files/cache stay on platform infra) | ⚠️ task |

**Verified clean (no task):**
- ✅ No hardcoded `DB::connection('mysql')` in production feature-package code (all hits are test `setPdo()` harness). Plan 03 T5 held.
- ✅ BYOC `byoc_db_{host,name,username,password}` carry `EncryptedField` casts; username/password also in `$hidden`. Provision-time overlay restored in `finally{}` (D3).
- ✅ `User` (extends `Authenticatable`) is guarded via the `EnforcesTenantContext` trait — it replicates the `TenantModel` global scope. No unguarded sensitive tenant model found.
- ✅ Reserved-subdomain config list (~80 entries) enforced via `Rule::notIn` at all 3 register-flow sites (the *redundant* hardcoded list in `checkSubdomain` is addressed by A7, not a leak).
- ✅ `EnsureTenantIsActive` logic is correct for archived/suspended/failed (gap is freshness + coverage — A8).

---

## File Structure (net-new + modified)

| File | Responsibility | Task |
|---|---|---|
| `packages/aero-platform/src/AeroPlatformServiceProvider.php` | DELETE the `boot()` bootstrapper `Config::set` override | A5 |
| `packages/aero-platform/tests/Feature/Wiring/TenancyRuntimeConfigTest.php` (new) | Assert booted `config('tenancy.bootstrappers')` + `filesystem.disks` | A5, A6 |
| `packages/aero-core/src/Support/CacheDriverGuard.php` (new) | Boot assertion: SaaS + non-tagging cache driver → throw | A1 |
| `packages/aero-hrm/tests/Feature/Tenancy/FilesystemIsolationTest.php` (new) | Write-as-A / read-as-B isolation behavior test | A2 |
| `aeos365/tests/Feature/Wiring/RawDbTenantAccessDisciplineTest.php` (new) | Ratchet: raw `DB::table()` on tenant tables in feature pkgs | A3 |
| `packages/aero-platform/src/Support/TenantDatabaseDropGuard.php` (new) | Shared regex + prefix + central-DB refusal | A9 |
| `packages/aero-platform/src/Support/TenantTeardownSequencer.php` (new) | Shared reorder+reconcile (delete rows → commit → drop → reconcile) | A4, A11 |
| `packages/aero-platform/src/Jobs/ReconcileOrphanedTenantDatabase.php` (new) | Cleanup job enqueued when post-commit drop fails | A4, A11 |
| `packages/aero-platform/src/Services/TenantForgetService.php` | Route through sequencer + drop guard | A4, A9 |
| `packages/aero-platform/src/Services/Tenant/TenantPurgeService.php` | Route through sequencer + drop guard | A9, A11 |
| `packages/aero-platform/src/Jobs/ProvisionTenant.php` | `rollbackDatabase()` → drop guard | A9 |
| `packages/aero-contracts/src/Models/TenantModel.php` | Fail-closed in SaaS | A10 |
| `packages/aero-core/src/Models/Concerns/EnforcesTenantContext.php` | Fail-closed in SaaS | A10 |
| `packages/aero-core/src/AeroCoreServiceProvider.php` | Checker closure fail-closed | A10 |
| `packages/aero-platform/src/Http/Requests/RegistrationDetailsRequest.php` | Unify subdomain max-length | A7 |
| `packages/aero-platform/src/Http/Requests/CheckRegistrationSubdomainRequest.php` | Unify subdomain max-length | A7 |
| `packages/aero-platform/src/Http/Controllers/TenantController.php` | Delete hardcoded reserved list in `checkSubdomain` | A7 |
| `packages/aero-platform/config/tenancy.php` | `subdomain.max_length` key | A7 |
| `packages/aero-platform/src/Listeners/FlushTenantResolutionCache.php` (new) | Flush Stancl tenant cache on suspend/archive | A8 |
| `deploy/security/byoc-boundary.md` (new) + admin BYOC step copy | Document DB-only BYOC residency | A12 |

---

## Task A5 — Delete the runtime bootstrapper override (P0, do first)

**Why P0:** `AeroPlatformServiceProvider::boot()` (~L328-336) runs `Config::set('tenancy.bootstrappers', [Database, CachePrefix, stock Queue])`, silently replacing `config/tenancy.php`'s `[Database, CacheTenancy, Filesystem, FailClosedQueue]`. Net effect in SaaS: **no `FilesystemTenancyBootstrapper`** (tenant `Storage::disk('local'|'public'|'s3')` writes to ONE shared root → cross-tenant file leak), and **stock queue bootstrapper** (Audit D5c negated). The "Undefined array key 'local'" comment justifying the override is stale — Phase 0 T5 added the `tenancy.filesystem` block (`config/tenancy.php:215-226`) that fixed the original error.

**Files:**
- Modify: `packages/aero-platform/src/AeroPlatformServiceProvider.php`

- [ ] **Step 1:** Confirm the original error is gone. With the `tenancy.filesystem` block present, temporarily comment out the `Config::set` and boot a tenant context; verify no `Undefined array key 'local'` from `FilesystemTenancyBootstrapper`.
- [ ] **Step 2:** Delete the entire `Config::set('tenancy.bootstrappers', [...])` block (and its stale comment). `config/tenancy.php` becomes authoritative.
- [ ] **Step 3 (decision note):** config keeps Stancl `CacheTenancyBootstrapper` (Redis tagging). A1 adds the driver guard so a non-Redis SaaS deploy fails closed at boot rather than leaking. `CachePrefixTenancyBootstrapper` stays in the tree as an opt-in fallback (wire via env if an operator genuinely cannot run Redis).
- [ ] **Step 4:** Keep `configureCentralDomains()` — that override is legitimate (request-derived). Only the bootstrapper override is removed.
- [ ] **Step 5:** Run the full platform suite + the new A6 runtime test. Verify a tenant file written under tenancy lands in a per-tenant path.

```bash
git commit -am "fix(tenancy): remove runtime bootstrapper override — restore filesystem + fail-closed queue isolation (Axis A A5)

AeroPlatformServiceProvider::boot() overrode config/tenancy.php with a
3-bootstrapper list that dropped FilesystemTenancyBootstrapper entirely
(tenant uploads shared one storage root = cross-tenant file leak) and
reverted the D5c fail-closed queue bootstrapper to stock. The override's
'Undefined array key local' justification was stale — Phase 0 T5 already
added the tenancy.filesystem config block that fixes it. config/tenancy.php
is now authoritative. TenancyConfigTest's file-based assertions are
superseded by TenancyRuntimeConfigTest (A6) which pins the BOOTED config."
```

---

## Task A6 — Runtime-config wiring test (drift guard)

**Files:** Create `packages/aero-platform/tests/Feature/Wiring/TenancyRuntimeConfigTest.php`

```php
/** Pins the BOOTED runtime config, not the file — closes the drift that hid A5. */
public function test_runtime_bootstrappers_include_filesystem_and_fail_closed(): void
{
    $bootstrappers = config('tenancy.bootstrappers'); // after full app boot
    $this->assertContains(\Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class, $bootstrappers);
    $this->assertContains(\Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper::class, $bootstrappers);
    $this->assertNotContains(\Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class, $bootstrappers,
        'Stock queue bootstrapper must not be the active one — FailClosed extends it.');
}

public function test_runtime_filesystem_disks_are_tenant_bootstrapped(): void
{
    $disks = config('tenancy.filesystem.disks');
    foreach (['local', 'public', 's3'] as $d) {
        $this->assertContains($d, $disks);
    }
}
```

- [ ] Keep `TenancyConfigTest` for the file-shape contract but add a class doc note pointing to this runtime test as the source of truth.
- [ ] Commit: `test(tenancy): pin booted runtime tenancy config to prevent SP override drift (A6)`

---

## Task A1 — Cache-driver fail-closed guard

**Files:** Create `packages/aero-core/src/Support/CacheDriverGuard.php`; call from a service-provider boot hook.

```php
final class CacheDriverGuard
{
    private const TAGGING_DRIVERS = ['redis', 'memcached'];

    public static function assertSaasUsesTaggingDriver(): void
    {
        if (! is_saas_mode()) return;
        $store = config('cache.default');
        $driver = config("cache.stores.{$store}.driver");
        if (! in_array($driver, self::TAGGING_DRIVERS, true)
            && ! config('tenancy.cache.allow_prefix_fallback', false)) {
            throw new \RuntimeException(
                "SaaS mode requires a tag-supporting cache driver (redis/memcached) for tenant ".
                "isolation via CacheTenancyBootstrapper. Driver '{$driver}' does not support tagging. ".
                "Set CACHE_STORE=redis, or opt into key-prefix fallback with tenancy.cache.allow_prefix_fallback=true."
            );
        }
    }
}
```

- [ ] Boot-time call (guard against console/early-boot false positives — only assert once the app is fully booted in HTTP/worker context).
- [ ] Test: SaaS + `array` driver throws; SaaS + `redis` passes; standalone is a no-op.
- [ ] Document Redis as a hard SaaS requirement in `deploy/README.md`.
- [ ] Commit: `feat(tenancy): fail closed when SaaS cache driver lacks tagging support (A1)`

---

## Task A2 — Filesystem isolation behavior test (after A5)

**Files:** Create `packages/aero-hrm/tests/Feature/Tenancy/FilesystemIsolationTest.php`

```php
/** Proves FilesystemTenancyBootstrapper actually namespaces per tenant (depends on A5). */
public function test_tenant_b_cannot_read_tenant_a_upload(): void
{
    tenancy()->initialize($tenantA);
    Storage::disk('local')->put('secret.txt', 'A-only');
    tenancy()->end();

    tenancy()->initialize($tenantB);
    $this->assertFalse(Storage::disk('local')->exists('secret.txt'),
        'Tenant B must not see Tenant A\'s file — filesystem tenancy is not isolating.');
    tenancy()->end();
}
```

- [ ] Add an S3 variant using a faked S3 disk asserting the per-tenant key prefix (`tenant-{id}/`).
- [ ] Commit: `test(tenancy): prove per-tenant filesystem isolation for local + s3 (A2)`

---

## Task A3 — Raw DB::table() ratchet for tenant tables

**Files:** Create `aeos365/tests/Feature/Wiring/RawDbTenantAccessDisciplineTest.php` (mirror `FacadeDisciplineTest` shape).

- [ ] **Step 1:** Capture current count of `DB::table('<tenant-table>')` / `DB::statement` against known tenant-scoped tables in `packages/aero-*` (exclude tests, migrations, `ProvisionTenant`'s intentional `tenancy()->run` block). Set `BUDGET` to that count.
- [ ] **Step 2:** Ratchet assertion with a message: "migrate one to Eloquent (carries the tenant-context guard) to lower the budget, or don't add new raw tenant-DB access."
- [ ] **Step 3:** Wire into `.github/workflows/wiring-guards.yml`.
- [ ] Commit: `test(tenancy): ratchet raw DB::table access to tenant tables (A3)`

---

## Task A9 — Shared TenantDatabaseDropGuard

Three drop paths currently diverge: `ProvisionTenant::rollbackDatabase` ✅, `TenantForgetService::dropTenantDatabase` ✅, `TenantPurgeService::dropTenantDatabase` ❌ (Stancl `tenants:delete --force`, no prefix/central guard — and it's the daily-scheduled path).

**Files:** Create `packages/aero-platform/src/Support/TenantDatabaseDropGuard.php`

```php
final class TenantDatabaseDropGuard
{
    /** @throws \RuntimeException when the name is unsafe to drop. */
    public static function assertSafe(string $databaseName): void
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $databaseName)) {
            throw new \RuntimeException("Unsafe tenant DB name '{$databaseName}'.");
        }
        $prefix = (string) config('tenancy.database.prefix', 'tenant');
        if ($prefix !== '' && ! str_starts_with($databaseName, $prefix)) {
            throw new \RuntimeException("DB '{$databaseName}' lacks tenant prefix '{$prefix}'.");
        }
        if (($central = config('database.connections.central.database')) && $databaseName === $central) {
            throw new \RuntimeException("Refusing to drop central DB '{$databaseName}'.");
        }
    }
}
```

- [ ] Route all three paths through `assertSafe()` before any `DROP`. For `TenantPurgeService`, resolve the name and assert **before** delegating to Stancl (or replace the Stancl call with a guarded `DROP`).
- [ ] Test: corrupted / empty / central-matching name is refused on every path.
- [ ] Commit: `refactor(tenancy): shared TenantDatabaseDropGuard across all 3 drop paths (A9)`

---

## Task A4 + A11 — Teardown atomicity (shared sequencer)

DDL (`DROP DATABASE`) implicitly commits in MySQL, so wrapping it in `DB::transaction()` is illusory. Both `TenantForgetService` (A4) and `TenantPurgeService` (A11) currently drop **first**, risking orphaned central rows on a later failure.

**Files:** Create `TenantTeardownSequencer` + `ReconcileOrphanedTenantDatabase` job.

```php
final class TenantTeardownSequencer
{
    public function teardown(Tenant $tenant, \Closure $deleteCentralRows, string $databaseName): void
    {
        TenantDatabaseDropGuard::assertSafe($databaseName); // A9, pre-flight

        // 1. Delete central rows transactionally and COMMIT first.
        DB::transaction($deleteCentralRows);

        // 2. Drop the DB outside any transaction. On failure, reconcile — never leave
        //    a committed row pointing at a live DB without a cleanup path.
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        } catch (\Throwable $e) {
            Log::error('Tenant DB drop failed post-commit; enqueuing reconciliation', [
                'database' => $databaseName, 'error' => $e->getMessage(),
            ]);
            ReconcileOrphanedTenantDatabase::dispatch($databaseName);
            throw $e;
        }
    }
}
```

- [ ] `TenantForgetService::forget()`: write audit → call sequencer (audit before, central-row delete inside the closure, drop after).
- [ ] `TenantPurgeService::purge()`: domains/subscriptions/tenant forceDelete inside the closure; drop after.
- [ ] `ReconcileOrphanedTenantDatabase`: idempotent — re-attempt guarded drop; alert if it still fails.
- [ ] Tests: simulate post-commit drop failure → assert reconciliation enqueued and no central orphan with a live DB; assert audit row persists for forget.
- [ ] Commit: `fix(tenancy): atomic teardown — commit row deletes before DDL drop + reconcile on failure (A4+A11)`

---

## Task A10 — Tenant-context guard fails closed in SaaS

`catch (\Throwable) { /* allow */ }` in `TenantModel`, `EnforcesTenantContext`, and the checker closure makes the guard fail **open** on any non-LogicException during `TenantScopeInterface` resolution.

**Files:** `TenantModel.php`, `EnforcesTenantContext.php`, `AeroCoreServiceProvider.php` (checker).

- [ ] Replace the broad swallow. Allow the query ONLY when the scope is genuinely not bound yet (early boot): `if (! app()->bound(TenantScopeInterface::class)) return;`. Otherwise let the resolution/LogicException propagate → fail closed in SaaS.
- [ ] Test: in SaaS, a non-LogicException raised during resolution blocks the query (assert it throws); early-boot (unbound) still allows; standalone remains a no-op.
- [ ] Commit: `fix(tenancy): tenant-context guard fails closed in SaaS instead of swallowing throwables (A10)`

---

## Task A7 — Subdomain validation single source of truth

**Files:** `RegistrationDetailsRequest` (max:40→shared), `CheckRegistrationSubdomainRequest`, `TenantController::checkSubdomain` (delete hardcoded list ~L456), `config/tenancy.php` (+`subdomain.max_length`).

- [ ] Add `config('tenancy.subdomain.max_length', 63)`; use it in all three validators so the availability probe and the actual register call agree.
- [ ] Delete the redundant hardcoded `$reserved = [...]` array in `checkSubdomain` — the `Rule::notIn(config('tenancy.reserved_subdomains'))` already covers it.
- [ ] Test: a 50-char subdomain behaves identically across probe + register; a reserved name is rejected at every site.
- [ ] Commit: `refactor(tenancy): single source of truth for subdomain length + reserved list (A7)`

---

## Task A8 — Suspended-tenant enforcement freshness + coverage

**Files:** Create `packages/aero-platform/src/Listeners/FlushTenantResolutionCache.php`; route-coverage test.

- [ ] On tenant suspend/archive (model event or status-transition observer), flush the Stancl tenant-resolution cache entry for that tenant so `EnsureTenantIsActive` 403s immediately rather than after up to `tenancy.cache.ttl` (3600s).
- [ ] Route-coverage test: enumerate tenant web/api route groups and assert `tenant.active` (alias) is present on each — fails if a new group skips the status gate.
- [ ] Commit: `fix(tenancy): flush resolution cache on suspend + assert tenant.active route coverage (A8)`

---

## Task A12 — Document BYOC isolation boundary

**Files:** Create `deploy/security/byoc-boundary.md`; update admin/registration BYOC step copy.

- [ ] State plainly: BYOC = **database** residency on the customer's server. Files (uploads, payslip PDFs) and cache live on the **platform's** shared disk/Redis (prefix-isolated, not customer-hosted).
- [ ] Ensure no registration/marketing BYOC copy implies full data residency.
- [ ] (Optional, larger scope — flagged not scheduled) per-tenant S3 bucket + cache namespace for true full-residency BYOC.
- [ ] Commit: `docs(security): document BYOC as DB-only residency boundary (A12)`

---

## Execution order

1. **A5** (P0 — live file leak) → unblocks A2, A6.
2. **A6** (lock the fix so drift can't recur).
3. **A9** (shared drop guard) → prerequisite for A4/A11.
4. **A4 + A11** (shared teardown sequencer; do together).
5. **A10** (guard fail-closed).
6. **A1** (cache driver guard) — independent.
7. **A3** (raw-DB ratchet) — independent.
8. **A7** (subdomain SoT) — independent.
9. **A8** (suspend freshness/coverage) — independent.
10. **A2** (filesystem isolation test — after A5).
11. **A12** (BYOC docs) — independent.

Subagent-driven recommended for A4+A11 and A9 (multi-file, shared helpers); inline fine for A1, A3, A7, A8, A12.

---

## Self-Review

- ✅ Every "Recommended" answer (A1–A12) maps to a task with files + test stub + commit draft.
- ✅ Verified-clean items (connection drift, BYOC casts, User guard via trait, reserved-list enforcement) recorded so they aren't re-audited.
- ✅ A5 framed as P0 with the stale-comment root cause and the false-green test (`TenancyConfigTest` reads the file the SP overrides).
- ✅ A4/A11 and A9 share helpers to avoid a fourth divergent drop/teardown implementation.
- ✅ Ordering puts the live leak first and locks it (A6) before lower-severity hardening.
- ⚠️ Host-repo check: confirm neither `aeos365` nor `aeos365-standalone` re-introduces a bootstrapper `Config::set` override (standalone does not load aero-platform, so it is unaffected; verify SaaS host `AppServiceProvider`).
