# HRMAC as a Context-Free Shared Package — Design

**Date:** 2026-06-04
**Status:** Approved (verbal) — executing
**Author:** Lead Architect (with Emam Hosen)

## Principle (operator directive)

> HRMAC is an **independent package** shared by **SaaS platform**, **SaaS tenants**, and **standalone**.
> **No context handling inside HRMAC.** The *consuming* packages/hosts decide the execution
> context (which DB connection, which guard) and the data. HRMAC only provides the
> access-control *engine*: roles, module/sub-module/component/action access, the cascade
> resolver, the middleware, and the sync command.

This makes HRMAC behave like the already-purified `aero-auth` package.

## What "context handling" means here (the things to REMOVE from HRMAC)

| # | Location | Current context-coupling | Target |
|---|----------|--------------------------|--------|
| 1 | `RoleModuleAccessService::modelForCurrentContext()` (17 call sites) | Uses `Aero\Core\ValueObjects\RequestContext::isPlatform()` to switch between `LandlordRoleModuleAccess` (pinned to `central`) and `RoleModuleAccess`. | One **configured** model (`hrmac.models.role_module_access`, default `RoleModuleAccess`) on the **default connection**. No RequestContext. |
| 2 | `LandlordRoleModuleAccess` model | Central-pinned duplicate; its table (`landlord_role_module_access`) doesn't even exist. | **Deleted.** |
| 3 | `SyncModuleHierarchy::detectScope()` + `tenancy()->initialized` probes | Auto-detects tenant vs platform via stancl/tenancy. | Scope comes from the **`--scope` option** passed by the consumer. No auto-detect. |
| 4 | `CheckRoleModuleAccess::resolveActiveGuard()` + guard-scoped super-admin | Probes `landlord/web/api` guards to pick a super-admin list. | **Flat** `super_admin_roles` list (union). HRMAC checks the authenticated user against it; it never decides "which guard". |

## Why this is safe (no regression to the verified tenant path)

- The tenant/standalone path already resolves to `RoleModuleAccess` on the current
  (tenant/default) connection. Removing the switch makes that the *only* path → unchanged.
- The landlord branch (`LandlordRoleModuleAccess`) points at a **missing table** and
  `landlord_roles` has **0 rows** → it is vestigial/broken today. Removing it cannot
  regress anything that currently works.

## How each consumer supplies context + data (the "sharing packages decide")

- **Connection:** HRMAC models use the *default* connection. The host sets it:
  - SaaS tenant request → stancl/tenancy has swapped default → tenant DB.
  - SaaS platform request → default is `central` → central DB.
  - Standalone → single default DB.
  So HRMAC never names a connection; the consumer's runtime context does.
- **Data (which roles/grants):** whatever lives in the current connection's HRMAC tables.
- **Sync scope:** consumer calls `aero:sync-module --scope=tenant|platform|all`.
- **Super-admin roles & user model:** `config/hrmac.php` (`super_admin_roles`, `models.*`),
  which a consumer may override/extend.

## Execution phases

- **A. Service purification** — replace `modelForCurrentContext()` with `accessModel()` (config-driven), delete `LandlordRoleModuleAccess` + `RequestContext` import. Verify tenant `/roles` + access checks unaffected. Commit.
- **B. Middleware purification** — flatten super-admin check, drop `resolveActiveGuard()` guard probing. Commit.
- **C. Sync purification** — drop `detectScope()`/tenancy probing; require explicit `--scope`. Commit.
- **D. Config** — add `models.role_module_access`; flatten `super_admin_roles`. Commit.
- **E. Platform consumer wiring (aero-platform)** — retire `LandlordRole` + `LandlordRoleService` JSON-permission system; platform uses HRMAC `Role`/`RoleModuleAccess` on the central connection (its own data decision). Separate follow-on; ensure central has the full HRMAC schema (components/actions) + seeded platform roles. Commit.

## Out of scope (separate effort)

Central-DB schema completeness (missing `module_components`/`module_component_actions`
in central) and platform role seeding are handled in Phase E as the *platform's* data
decision, not HRMAC's.
