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
