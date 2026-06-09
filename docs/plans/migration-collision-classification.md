# Migration Collision Classification (Phase 4 input / baseline-stabilization map)

**Produced:** 2026-06-09 · read-only · companion to [phase0-audit](dependency-decoupling-phase0-audit.md)
**Why:** the SaaS host test suite throws 106 errors, all one root cause — `central + tenant + shared` migration sets load into a single `:memory:` DB in tests/standalone and collide on every table defined in ≥2 places. This is the full collision inventory + the central|tenant|shared tag each table should carry.

## Root-cause headline

The collisions are **a half-finished decoupling already in the working tree**, not ancient debt:
- `aero-infrastructure` is **brand-new** (untracked in git) and already holds `installation_progress`, `installation_history`, `module_pricing`, `system_health_logs`.
- `aero-auth` and `aero-notifications` hold **byte-identical copies** (same filenames/timestamps) of migrations still present in `aero-core`/`aero-platform`.
- The new homes were created and populated; the **old copies were never deleted**. Two creates of one table → "already exists".

So most of the fix is **finishing moves already started** (delete the stale source copies), not new design.

## Scope note

Tables created ≥2× that live in **product packages** (`aero-hrm`, `aero-crm`, …) are **out of decoupling scope** — separate product cleanup: `cms_block_types`, `work_locations`, `training_sessions/enrollments/categories`, `kpi_values`, `grades`, `benefits/benefit_plans/benefit_enrollments`, and the 2nd `modules`/`forms` creators. Listed here only so they aren't mistaken for core/platform debt.

---

## Bucket 1 — In-flight V2 (identity → `aero-auth`): delete stale core/platform copies

Same filename in two packages ⇒ copied, not yet deleted. `aero-auth` is canonical.

| Table | Creators | Canonical | Action | Tag |
|-------|----------|-----------|--------|-----|
| `user_sessions` | core + auth *(same ts 2024_01_16_000003)* | auth | delete core copy | tenant |
| `user_impersonations` | core + auth *(same ts 2024_01_20_000001)* | auth | delete core copy | tenant |
| `user_devices` | core + auth *(same ts 2025_12_02_202539)* | auth | delete core copy | tenant |
| `social_auth_accounts` | platform + auth *(same ts 2026_01_20_000006)* | auth | delete platform copy | tenant |
| `authentication_events` | core *(inside `create_users_table`)* + auth *(2026_05_23_000004)* | auth | remove block from core's users migration | tenant |

> `authentication_events` is **embedded inside** core's `0001_01_01_000002_create_users_table.php` — extract/delete that block, don't drop the whole users migration.

## Bucket 2 — In-flight V9 (notifications → `aero-notifications`): delete core/platform copies

| Table | Creators | Canonical | Action | Tag |
|-------|----------|-----------|--------|-----|
| `notification_logs` | core + platform + notifications **(3×)** | notifications | delete core + platform copies | shared |
| `user_notification_preferences` | core + notifications | notifications | delete core copy | tenant |
| `notification_settings` | core + notifications | notifications | delete core copy | shared |

## Bucket 3 — In-flight V10 (shared → `aero-infrastructure`): delete core/platform copies

| Table | Creators | Canonical | Action | Tag |
|-------|----------|-----------|--------|-----|
| `installation_progress` | core + platform + infrastructure **(3×)** | infrastructure | delete core + platform copies | shared |
| `installation_history` | core + infrastructure | infrastructure | delete core copy | shared |
| `module_pricing` | platform + infrastructure | infrastructure | delete platform copy | central |
| `system_health_logs` | core + infrastructure | infrastructure | delete core copy | shared |

> Confirm `aero-infrastructure` is actually registered/booted before deleting sources, else these tables vanish from install. (It's untracked — verify its ServiceProvider `loadMigrationsFrom` is wired.)

## Bucket 4 — Framework/shared defaults duplicated core ⟂ platform: define once

Both central and tenant DBs legitimately need these — but the definition must be **single + `shared`-tagged**, not copy-pasted in both mode packages.

| Table | Creators | Action | Tag |
|-------|----------|--------|-----|
| `sessions` | core (`create_users`) + platform (`update_landlord_users`) | single shared definition | shared |
| `jobs` | core (`0001…000001`) + platform (`2025_12_01_142606`) | single shared definition | shared |
| `failed_jobs` | core (`0001…000001`) + platform (`2025_12_02_110811`) | single shared definition | shared |
| `cache` | core (`0001…000000`) + platform (`2024_07_27_052206`) | single shared definition | shared |
| `cache_locks` | core (`0001…000000`) + platform (`2024_07_27_052206`) | single shared definition | shared |
| `media` | core + platform *(same ts 2024_07_27_061640)* | single shared definition | shared |

## Bucket 5 — Cross-context name collision (NOT a production bug)

Two genuinely different tables that share a name; in real SaaS they live in different DBs. Only collide in single-DB (test/standalone). **Do not delete either** — tag + guard.

| Table | Creators | Resolution |
|-------|----------|------------|
| `support_tickets` | core (default→tenant, **unguarded**) + platform (`connection('central')`, guarded) | tag core=`tenant`, platform=`central`; for single-DB modes either prefix one or add `hasTable` guard to core's create. The proper fix is the context system deciding which set runs. |

> This is the canonical illustration of why the 3-tag system is required: name overlap across contexts is legal, single-DB execution is not.

## Bucket 6 — Within-platform drop/recreate (safe today; Q2 squash candidates)

These use `dropIfExists()` → `create()`, so **no runtime collision** — they don't contribute to the 106 errors. They're the "update migration that recreates" pattern, ideal squash targets *iff* pre-launch (no deployed DB to preserve history for).

| Table | Pair | Squash action |
|-------|------|---------------|
| `landlord_users` | `create_landlord_users` (UUID) + `update_…to_match_users_structure` (drop+recreate as bigint) | fold final structure into one create; delete the update |
| `landlord_password_reset_tokens` | same pair | same |
| `sessions` (platform copy) | recreated in the same `update_…` migration | drops out once Bucket 4 makes it single-shared |

---

## What actually unblocks the baseline

| Step | Effort | Effect |
|------|--------|--------|
| Buckets 1–3: delete stale source copies (finish in-flight moves) | low–med, mechanical | clears the **majority** of the 106 collisions |
| Bucket 4: collapse framework tables to one shared definition | low | clears the framework-table collisions |
| Bucket 5: tag/guard `support_tickets` (+ any other cross-context) | low | clears the remaining named collisions |
| Standalone `phpunit.xml`: stop referencing missing `tests/Unit` | trivial | standalone suite becomes runnable |
| Bucket 6 squash | optional | polish only — **not** required for green |

**Buckets 1–4 are the decoupling worklist (V2/V9/V10 + shared-table rule 13) — so stabilizing the baseline and advancing the refactor are the same work here.** That validates the "stabilize first" decision: it isn't a detour, it's Phase 1-adjacent cleanup with an immediate green payoff.

**Still required for a *robust* fix (vs. mechanical dedupe):** the context-aware migration loader (Phase 4) so the orchestrator runs the right tag-set per DB. The deletions above make the single-DB test/standalone path green; the loader makes multi-DB SaaS provably correct.

**Risk:** classification is read-only. The deletions it recommends are not — each needs "new home is booted & has the table" verified before the source copy is removed (call-outs above).

---

## Execution log (2026-06-09)

**Wiring verification (before any deletion):**
- `aero-auth`, `aero-notifications`: required + symlinked into **both** hosts → booted. ✅
- `aero-platform`: SaaS host only (correct — standalone has no central package; Bucket 4/5 collisions are SaaS-test-only).
- `aero-infrastructure`: **required by NEITHER host, in no vendor dir, in no package manifest → dormant stub. Its ServiceProvider never boots.** Bucket 3 source copies (core/platform) currently provide those tables. **Bucket 3 deletes are BLOCKED until infrastructure is wired in (composer require + dump-autoload), else the tables vanish from install.**

**Key correction to the model:** Laravel dedups migrations **by filename**. Same-name copies (`user_sessions`, `user_devices`, `social_auth_accounts`, `user_impersonations`) therefore **do not collide** (only one runs; for diverged-content same-name pairs like `user_impersonations` this is a *latent* path-order bug, not the test-breaker). Only **differently-named** duplicate creates collide. Also: this codebase's **established convention** for legit cross-package/cross-context duplicates is an idempotent `if (Schema::hasTable('x')) return;` guard (documented in core's `create_notification_logs` migration). Most duplicates already carry it; the 106 errors came from the **one** that forgot it.

**Fix applied:** added the established `hasTable` guard to `aero-core/.../2026_05_29_000100_create_support_tickets_table.php` (mirrors the documented convention; references this doc). Fixed `aeos365-standalone` `phpunit.xml` (added `tests/Unit/`).

**Post-fix baseline:**
| Suite | Before | After | Migration collisions |
|-------|--------|-------|----------------------|
| SaaS host (`aeos365`) | 133 all-blocked (106e/4f) | 28 pass / 92 err / 13 fail | **0** ✅ |
| Standalone host | could not start | runs: 1 test, 1 fail | **0** ✅ |
| `aero-core` package | 118 / 20e / 5f | 118 / 20e / 5f (unchanged) | **0** ✅ |

**Residual reds are NOT decoupling-related (freeze as known-red oracle):**
- **92 SaaS errors** — one systemic cause: `UrlGenerationException: Missing required parameter [tenant]` for `core.*.index` routes. SaaS routes are `{tenant}`-scoped; these feature tests generate URLs without a tenant. Pre-existing test-harness gap; not caused or fixed by decoupling. Candidate for a separate TestCase tenant-context fix.
- **13 SaaS + 5 core failures + 20 core errors** — assorted test drift (route names, Inertia prop shapes).
- **1 standalone failure** — single existing feature test.

**Net result:** the migration-context collision class (the decoupling thesis symptom) is eliminated; both modes' suites are runnable. Buckets 1–2 (delete same-name stale copies) remain as *latent-bug cleanup* for the proper V2/V9 phases (note Bucket 2 copies **diverged** — notifications versions are stubs vs core's richer schema — so those are a **reconcile-then-move**, not a blind delete). Bucket 4 framework dupes are guarded (didn't collide). Bucket 6 squash deferred.

### Bucket 3 / V10 — COMPLETED 2026-06-09 (aero-infrastructure wired in)

Boss confirmed infrastructure is the intended shared home (the 4 tables were genuine core↔platform cross-dependencies). Executed:

1. **Schema verification first** (avoided the notifications-stub trap): all 4 infra migrations are schema-canonical —
   - `installation_history` = core (identical), `module_pricing` = platform (identical), `system_health_logs` = core (identical).
   - `installation_progress`: infra's schema (`session_id/status/step/percentage/metadata/updated_at`) **matches the canonical aero-installation orchestrator's `updateOrInsert` columns** — core's (`meta/error_message`) and platform's (`payload/latest_error`) were the stale divergent schemas.
2. **Wired `aero/infrastructure` into both hosts**: added to each host `composer.json` require; fixed three packaging bugs found in the stub — missing top-level `"version": "1.0.0"` (infra **and** aero-platform), and wrong provider FQN in infra's `extra.laravel.providers` (`Aero\Infrastructure\InfrastructureServiceProvider` → `Aero\Infrastructure\Providers\InfrastructureServiceProvider`). `composer update` + autoload dump; provider now discovered in both hosts.
3. **Deleted 5 stale source copies**: core `create_installation_history` / `create_installation_progress` / `create_system_health_logs`; platform `create_installation_progress` / `create_module_pricing`.
4. **Verified both modes**: SaaS `0 already-exists / 0 no-such-table` across 133 tests; standalone runnable, same; a temp-sqlite `migrate:fresh` shows all 4 infra migrations execute (`DONE`) and create their tables. Tally unchanged (the 92 SaaS errors are the unrelated `[tenant]` route issue), confirming no regression.

`aero-infrastructure` is now a live foundation-layer package owning the shared tables — the core⟂platform sibling cross-dependency on these tables is removed. **Remaining for full V10:** ensure the install orchestrator's tag-discovery maps the `infrastructure:shared` group (step 0 in `installation-migration-order.php`) to the infra provider's migrations (Phase 4 orchestrator work).
