# Dependency Architecture — Strict Unidirectional Layering

**Status:** Canonical · **Decided:** 2026-06-09 · **Applies to:** every `packages/aero-*`

The monorepo enforces a **strict unidirectional dependency graph**. Dependencies flow **down only**. No package may depend on a peer or on anything above it. The two mode packages — `aero-core` (standalone/tenant) and `aero-platform` (SaaS/central) — are **strict siblings with zero dependency in either direction**.

This is not cosmetic. In Laravel, **a dependency edge is a migration-registration edge**: requiring a package boots its `ServiceProvider`, which fires `loadMigrationsFrom()`. If the landlord (central) and tenant migration sets can boot into each other's context, the installation wizard runs the wrong migrations against the wrong database. The dependency graph is the mechanism that keeps install contexts isolated.

---

## The Layers

```
HOSTS         aeos365 · aeos365-standalone            (dumb wrappers, zero business logic)
                    │
PRODUCTS      hrm crm ims finance scm pos project …   (own their domain tables)
                    │  depend down on ▼
MODE          aero-core  ⟂  aero-platform             (SIBLINGS — never depend on each other)
                    │                    │
SHARED SEAM   aero-kernel · aero-installation · aero-auth
                    │  depend down on ▼
FOUNDATION    aero-contracts · aero-infrastructure
PURE LEAVES   aero-ui · aero-hrmac · aero-i18n · aero-notifications
```

| Layer | Packages | May depend on |
|-------|----------|---------------|
| Hosts | `aeos365`, `aeos365-standalone` | anything (wrappers only) |
| Products | `aero-hrm`, `aero-crm`, `aero-ims`, `aero-finance`, … | `core`, `contracts`; pure leaves directly |
| Mode | `aero-core`, `aero-platform` | seam, foundation, leaves — **never each other** |
| Shared seam | `aero-kernel`, `aero-installation`, `aero-auth` | `contracts`, foundation, leaves only |
| Foundation | `aero-contracts`, `aero-infrastructure` | nothing (pure) |
| Pure leaves | `aero-ui`, `aero-hrmac`, `aero-i18n`, `aero-notifications` | nothing upward |

---

## The Rules

### Vertical (product → core → foundation)
1. Flow is one-way: `Product → Core → Foundation`. Never skip up, never point down→up.
2. Products own their domain persistence (models + migrations). Products **never** import `aero-infrastructure` — shared tables are core-mediated.
3. Product ↔ product communication: **domain events** (notifications) + **`contracts` interfaces** (synchronous reads). **Never** `use Aero\OtherProduct\…`.
4. Products type against `contracts`; `core` binds implementations in the container.
5. Frontend: no client-side DTO/mapper layer. Controllers shape Inertia props server-side via `Inertia::render()`.
6. Products depend **directly** on pure leaves (`ui`, `hrmac`, `i18n`); **core-mediated** for shared tables.

### Sibling isolation (core ⟂ platform)
7. `aero-core` and `aero-platform` have **zero** dependency in either direction — composer or code.
8. Shared logic between them lives in `aero-kernel` (runtime) or `aero-contracts` (interfaces). Both siblings depend **down** on it.
9. **Hidden coupling is still coupling.** `class_exists('Aero\Platform\…')` guards and hardcoded FQN strings are forbidden. Core must never name platform.
10. Mode-dependent behavior uses **inversion**: a `TenancyProvider` contract in `contracts` that platform implements and registers into core's extension points at boot. Core consumes the contract; core never names platform.

### Migration context
11. Every migration is tagged `central` | `tenant` | `shared`.
    - `central` → landlord DB only (SaaS).
    - `tenant` → per-tenant DB only.
    - `shared` → both contexts.
    - **standalone** = all three sets run into the single DB.
12. The installation orchestrator (`aero-installation`) selects the migration set per mode using the context tags + the package registry. No package filters another package's migrations.
13. A shared table (e.g. `installation_progress`) is defined **once** in the shared layer, tagged `shared`, and created deliberately per context. No duplicate definitions across packages.

### Foundation purity
14. `contracts`, `infrastructure`, and pure leaves have **no upward imports, ever**.
15. `CentralModel` / `TenantModel` have a **single** canonical definition in `aero-contracts`. Copies elsewhere are forbidden.

---

## Current Violations (audit 2026-06-09)

### Declared graph (composer `require`)

| Package | Requires | Status |
|---------|----------|--------|
| contracts, infrastructure, ui, i18n, notifications | — | ✅ pure |
| hrmac | contracts | ✅ seam-only |
| core | contracts, infrastructure | ✅ correct |
| **auth** | **core**, contracts | ❌ **V2** |
| **installation** | **core**, contracts | ❌ **V3** |
| **platform** | **core**, auth, contracts, infrastructure, notifications | ❌ **V1** |
| all products | core, contracts | ✅ correct |

### Hidden coupling (`class_exists()` string guards — invisible to composer)

| From | To (by string) |
|------|-----------------|
| core | `Platform\AeroPlatformServiceProvider` (×4), `Platform\Models\PlatformSetting`, `Platform\Models\Domain`, `Platform\Http\…\TenantOnboardingController` |
| core | `Notifications\Models\NotificationLog` |
| core | `Core\Models\User` (string self-ref) |

> Verified: core has **zero** `use Aero\Platform\…` / `Installation` / `Notifications` hard imports. All core→platform coupling is `class_exists()` string indirection — the leakiest kind, invisible to any dependency tool.

### Structural duplications
- **Installation wizard duplicated**: `UnifiedInstallationController` + `InstallationOrchestrator` exist in **both** `aero-core` and `aero-installation` (11 Steps in core, 14 in installation), hand-synced.
- **`CentralModel`/`TenantModel` triplicated**: `contracts` + `core` + `platform`.
- **License algorithm duplicated**: core validates (`Services/License/*`), platform issues (`LicenseIssuer`), kept in sync by hand-copied comments.

---

## Decoupling Worklist (ranked)

| # | Violation | Fix | Target home |
|---|-----------|-----|-------------|
| V1 | `platform → core` (composer) | Extract shared runtime to `aero-kernel`; platform stops requiring core | `aero-kernel` |
| V2 | `auth → core` (composer) | Move `User` + identity into `auth`; replace core refs with contracts | `aero-auth` (deps: contracts only) |
| V3 | `installation → core` (composer) | Wizard becomes context-neutral; resolves via registry + `TenancyProvider` | `aero-installation` (deps: contracts, kernel) |
| V4 | `core → platform` (class_exists) | Inversion via `TenancyProvider` contract | `aero-contracts` + platform impl |
| V5 | Wizard duplicated (core + installation) | Single canonical wizard in `aero-installation`; delete core copy | `aero-installation` |
| V6 | `CentralModel`/`TenantModel` triplicated | Canonical in contracts; delete copies (temp aliases) | `aero-contracts` |
| V7 | License algorithm duplicated | Shared signing core; validator + issuer consume it | shared license-core |
| V8 | `ModuleRegistry` core-owned, platform-consumed | Move to kernel | `aero-kernel` |
| V9 | Mail/SMS/Translation interfaces in core | Move interfaces to contracts | `aero-contracts` |
| V10 | `installation_progress` duplicated | Single `shared`-tagged definition | shared layer |

---

## Execution Order (bottom-up, never breaks the build)

`contracts` → `auth` → `kernel` → `installation` → cut `platform → core` → invert `core → platform`

Each step compiles before the next. During migration, temporary `class_alias()` bridges old → new namespaces so dependents keep resolving until they're repointed.

---

## Enforcement (deferred)

Backend boundaries are PHP/Composer — the enforcement tool is **Deptrac** (or PHPat), **not** dependency-cruiser/eslint (those are JS tools; the only JS package is `aero-ui`). A `deptrac.yaml` encoding these layers is the final step, added once the graph is clean so it fails the build on any new violation.
