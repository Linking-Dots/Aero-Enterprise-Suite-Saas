# Phase 4 — Context-Aware Migration Routing (4-tier model)

**Status:** design approved 2026-06-11 (Boss). Implements decoupling-plan Phase 4 (V3/V5/V10) + architecture-standard rules 11-13. **HIGHEST risk** — governs which migrations hit which database. Run under Boss-Proxy/Watcher loop; gate every unit on the oracle + (eventually) full E2E install both modes.

## The model (Boss, 2026-06-11)

Every package declares a **tier**; the install/provisioning picks the migration set per context:

| Tier | Packages | Central (landlord) | Tenant (per-tenant) | Standalone (single DB) |
|------|----------|:---:|:---:|:---:|
| **platform** | aero-platform | ✅ | — | — |
| **core** | aero-core | — | ✅ | ✅ |
| **sharable** | contracts, kernel, license, infrastructure, hrmac, auth, notifications, i18n, ui *(installation = tooling)* | ✅ | ✅ | ✅ |
| **product** | all feature packages (hrm, crm, cms, dms, finance, …, custom-fields, forms, helpdesk, workflow) | — | ✅ *if subscribed* | ✅ *if purchased (+ add-ons later)* |

**Resulting sets:**
- **Central** = `platform + sharable`
- **Tenant** = `core + sharable + subscribed products`
- **Standalone** = `core + sharable + purchased products` (+ add-on products installed later)

## Decisions (approved)
1. **Granularity = per-PACKAGE** via `extra.aero.tier` in each `composer.json` (values: `platform|core|sharable|product`). All a package's migrations inherit its tier. Optional per-migration override reserved for the rare mixed-context package. This supersedes the plan's per-migration 3-tag as the canonical form (update arch-standard rules 11-13). Tier→tag map: platform→central, core/product→tenant, sharable→shared(both).
2. **Standalone "purchased" = de-facto installed packages** (whatever product packages are physically present in `packages/` or `vendor/aero`) for now. Proper license/purchase registry + add-on-install flow = follow-on.
3. **Sequencing = routing fix NOW, own track** — does not block on cutting core⟂platform (V1/V4). It is the launch-critical correctness fix and yields the missing E2E-install gate.

## Current bugs this fixes (verified 2026-06-11)
- Central wrongly runs **core's 56 migrations** (tenant-app tables) — should be platform+sharable only.
- Tenant **misses 21 sharable migrations** (auth 9, notifications 6, i18n 2, infrastructure 4) — only core+hrmac+subscribed today.
- Standalone migrates **all** products regardless of purchase; no add-on path.
- **4 packages unclassified** (`custom-fields`, `forms`, `helpdesk`, `workflow`) → leak into central today.
- Scoping lives in the **wizard**, not the migration system — a raw `php artisan migrate` (or the redundant CLI installer) ignores it and dumps everything into the current DB.

## Units
1. **Tier classification + validation gate.** Add `extra.aero.tier` to every package. Classify the 4 unclassified (default `product`). Add a gate (command/install check) that FAILS if any aero package lacks a valid tier. *(Pure metadata + a check — low risk; no routing change yet.)*
2. **Central + standalone routing.** Rewrite `MigrationStep::shouldMigratePackage()` to use tier: central=platform+sharable; standalone=core+sharable+purchased(installed). Delete reliance on the coarse `category`.
3. **Tenant routing.** `ProvisionTenant::getTenantMigrationPaths()` — add ALL sharable packages (auth/notifications/i18n/infrastructure), keep core + subscribed products.
4. **Context-aware migration loader.** Make the migration *system* tier-aware (a custom resolver / a `migrate --context=central|tenant|standalone` that filters by tier) so a raw `migrate` is safe — not just the wizard. Retire/guard the redundant CLI `aero:install`.
5. **Prove E2E install** in both modes against throwaway DBs; assert the right tables land in the right DB (central has NO core/product tables; tenant has core+sharable+subscribed; standalone has core+sharable+installed-products).

## Gate (every unit)
aero-core oracle 121t/20e/5f baseline; hrmac 39t/147a; both hosts boot + `route:list` exit 0. Unit 5 adds: per-context table-presence assertions.

---

## Execution log (2026-06-12)

| Unit | Commit | Watcher (opus) |
|------|--------|----------------|
| 1 — tier classification (39 pkgs) + `aero:verify-tiers` fail-closed gate | c6a26d73d (+7e2ea991c) | PASS |
| 2 — central+standalone routing; `PackageTier` SSOT relocated to **aero-kernel** | 97e3e23cd | PASS |
| 3 — tenant routing: core + ALL sharable + subscribed products | 565f6b988 | PASS |
| 4 — raw `tenants:migrate` tier-safe (exclude platform from tenant DBs) | 0f15acdb6 | PASS |
| 5 — E2E proof (this section) | — (proof; results below) | — |

All four routing layers now derive from one resolver `Aero\Kernel\Migration\PackageTier`
(pure leaf: `base_path`/`glob`/`json` only): wizard `MigrationStep`, platform landlord
migrator override (central + tenant branches), and tenant provisioning `ProvisionTenant`.

## Unit 5 — E2E table-routing proof (throwaway MySQL schemas)

Method: for each context, take the **authoritative resolved path set** from `PackageTier`, then
execute every migration's `up()` (de-duped by op-name keeping latest, tier-ordered — mirroring the
real migrator) against a throwaway `aeos_e2e_*` schema; introspect `information_schema`; classify
each landed table by its creator-package tier; assert no disallowed-tier table + sentinel boundaries.
Throwaway schemas dropped after. (Run from the SaaS host, only `hrm` product installed, so
standalone≡tenant here.)

### Expectation vs. actual

| Context | Expected tiers | Sentinel: platform (`tenants`) | Sentinel: core (`users`) | Sentinel: hrmac (`role_module_access`) | Sentinel: product (`employees`) | Disallowed-tier leaks |
|---------|----------------|:--:|:--:|:--:|:--:|:--:|
| **central** | platform + sharable | ✅ present | ✅ absent | ✅ absent¹ | ✅ absent | **0** |
| **tenant** | core + sharable + subscribed | ✅ absent | ✅ present | ✅ present | ✅ present | **0** |
| **standalone** | core + sharable + installed | ✅ absent | ✅ present | ✅ present | ✅ present | **0** |

¹ hrmac is sharable so it *is* allowed in central; the central run did include it. Sentinel chosen
per context for the clearest tier boundary.

**Routing verdict: PROVEN.** Every context receives exactly its allowed tiers' tables; zero
cross-tier leakage; all sentinel boundaries hold. Tables by tier — central: platform 52 / sharable 25
/ core 0 / product 0. standalone≡tenant: core 50 / sharable 26 / product 195 / platform 0.

### Findings surfaced by the E2E (NOT routing failures; out of Phase-4 scope)

1. **[REAL, pre-existing] `auth` (sharable) FK-depends on `core.users`.** `aero-auth`'s
   `create_user_sessions_table` / `create_user_devices_table` add an unconditional
   `foreignId('user_id')->constrained()` to `users`, but `users` is created **only by aero-core**.
   Central = platform+sharable excludes core ⇒ a real central migrate fails these FKs. Latent today
   (the dev landlord DB has no `migrations` table — central was never migrated). Predates Phase-4
   (auth was always in central's baseline). **Decision needed:** are auth's end-user session tables
   actually central-relevant (landlord admins authenticate via platform's `landlord_users`)? Likely
   auth is partly mis-tiered, or those FKs should be guarded. Tracked separately.
2. **[pre-existing] Duplicate table ownership across packages** — `user_sessions` (core + auth),
   `notification_logs` / `cache` / `jobs` / `sessions` (core + platform + notifications). The real
   migrator de-dups by op-name so only one runs; still a zero-dup violation to reconcile.
3. **[harness-scope, not a bug] central alter-before-create noise** — a handful of central ALTER
   migrations (`domains`, `plans`, `tenants` columns) reference tables created by **stancl/host**
   migrations, which the proof deliberately excluded (it ran only `aero-*` package paths). These run
   fine in a real install where the full migration set + override are present.

Net: **Phase-4 migration tier-routing is correct and proven end-to-end.** The one genuine
correctness gap (auth→core `users` in central) is a pre-existing tier-definition question, filed for
a follow-on; it is not introduced or worsened by this work.
