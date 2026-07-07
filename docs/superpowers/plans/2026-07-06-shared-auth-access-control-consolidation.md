# Shared Auth + Access-Control Consolidation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate the duplicated user/role/module-access admin surfaces so **user + auth live in `aero-auth`** and **access control (roles, permissions, module-access) lives in `aero-hrmac`**, with ONE context-neutral HRMAC namespace each — removing every `core.*` and `platform.*`/`landlord-*` declaration of these concerns from `aero-core` and `aero-platform`.

**Architecture:** HRMAC permission codes are `{module.code}.{submodule}.{component}.{action}`, discovered from each package's `config/module.php` by `ModuleDiscoveryService` (scans `vendor/aero/*`) and materialized by `php artisan aero:sync-module`. Today `user_management`/`roles_permissions`/`module_access` are submodules of the **`core`** module (tenant) AND re-declared as `platform-users`/`landlord-*` under the **`platform`** module. `RoleController`/`ModuleController` already live in `aero-hrmac`; `RoleController` renders one of two views via a `hrmac_role_view` route default. We move the user surface into the **`auth`** module (already `scope: infrastructure`, shared) and the roles/module-access surface into a new **`hrmac`** module, parameterize the already-built `Core/Users` + `Core/Roles` React pages by `routePrefix` + `hrmacNamespace`, and render them from both the tenant (`aero-core`) and platform (`aero-platform`) contexts. Data realm stays guard/DB-split (tenant `users` vs central `landlord_users`) — only the admin UI + HRMAC codes unify.

**Tech Stack:** Laravel 12, Inertia v2, React 18, `@aero/ui`, PHPUnit. HRMAC (`aero-hrmac`), Auth (`aero-auth`).

## Global Constraints

- Package-first: ALL code in `packages/aero-*`; host apps (`aeos365`, `aeos365-standalone`) are dumb wrappers.
- Dependency direction MUST stay downward: `aero-core` and `aero-platform` may depend on `aero-auth`/`aero-hrmac`; never the reverse. (`aero-auth`/`aero-hrmac` stay context-free — no `core`/`platform` symbols.)
- One neutral HRMAC namespace per concern; NO `core.`/`platform.`/`landlord-` prefixes for user/role/module-access after this migration.
- Frontend: components from `@aero/ui`, no inline `style={}`, `useForm()`/`router.*` from Inertia v2, theme tokens only.
- Every write in `DB::transaction()`; decimals cast to float at the controller boundary.
- Dual-mode: every change works in SaaS (tenant + platform) AND standalone.
- Doc currency: resolve version-sensitive API via Context7/tech-versions.md (Laravel 12, React 18, Inertia v2).
- Verify each phase LIVE through the UI as a real user (Playwright) with 0 console errors before proceeding.

---

## Verified Current State (executor context — do not skip)

| Piece | Location | Notes |
|---|---|---|
| HRMAC discovery | `packages/aero-hrmac/src/Services/ModuleDiscoveryService.php` | scans `vendor/aero/*/config/module.php`; namespace `{code}.{submodule}.{component}.{action}` |
| Sync command | `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php` | `--scope=tenant|platform|all`; **module synced only if `module.scope === run scope` OR run scope `all`** (L146-151) |
| Scope filter (platform) | `packages/aero-platform/src/Services/TenantSubscriptionModuleFilter.php` | gates ONLY tenant syncs by subscription; platform/all pass all |
| Core module | `packages/aero-core/config/module.php` | `code: 'core'`, `scope: 'tenant'`; submodules `user_management` (L199), `roles_permissions` (L342), `module_access` (L365) |
| Platform module | `packages/aero-platform/config/module.php` | `code: 'platform'`, `scope: 'platform'`; submodule `platform-users` (L418) → `landlord-user-list` (L427), `landlord-roles` (L438) |
| Auth module | `packages/aero-auth/config/module.php` | `code: 'auth'`, `scope: 'infrastructure'`; owns SSO/MFA/sessions — **no user-admin surface yet** |
| HRMAC module | `packages/aero-hrmac/config/` | only `hrmac.php` (engine config) — **no `config/module.php`** yet |
| Roles controller | `packages/aero-hrmac/src/Http/Controllers/RoleController.php` | SHARED; view via `hrmac_role_view` default (`Core/Roles/Index` default; `Platform/Admin/Roles/Index` on platform) |
| Module controller | `packages/aero-hrmac/src/Http/Controllers/ModuleController.php` | SHARED |
| **Stale** platform RoleController | `packages/aero-platform/src/Http/Controllers/Admin/RoleController.php` | dead — platform routes import the `aero-hrmac` one; DELETE |
| Users controller (tenant) | `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php` | large surface (index/paginate/stats/store/update/toggleStatus/updateUserRole/destroy/restore/forceDelete/bulk*/export/lock/unlock/forcePasswordReset/resendVerification/invite) |
| Users controller (platform) | `packages/aero-platform/src/Http/Controllers/Admin/LandlordUserController.php` | central-DB `landlord` guard; index/show/store/update/toggleStatus/destroy |
| Tenant user routes | `packages/aero-core/routes/web.php` (`core.api.users.*`, ~L308-352) | `hrmac:core.user_management.users.*` |
| Platform user routes | `packages/aero-platform/routes/admin.php` (L1375-1390) | `hrmac:platform-users.landlord-user-list.*` |
| Platform roles routes | `packages/aero-platform/routes/admin.php` (L1396-1408) | `hrmac:platform-users.landlord-roles.*`; `->defaults('hrmac_role_view', 'Platform/Admin/Roles/Index')` |
| Shared-ready pages (done) | `packages/aero-ui/.../Pages/Core/Users/*`, `Pages/Core/Roles/*` | hardcoded to `core.*` routes + `core.user_management.*`/`core.roles_permissions.*` HRMAC |
| Old platform pages | `packages/aero-ui/.../Pages/Platform/Admin/Users/*`, `Platform/Admin/Roles/Index` | RETIRE |

**Target namespace (proposed; confirm in Phase 0):**
- Users → `auth.user_management.users.{view,create,edit,delete,activate,impersonate,bulk_delete,…}` + `auth.user_management.user_invitations.{resend,cancel,store}`
- Roles → `hrmac.roles_permissions.roles.{view,create,edit,delete,assign}`
- Module access → `hrmac.roles_permissions.module_access.configure`

---

## Phase 0 — Design Lock (no code; produces the decisions every later phase depends on)

### Task 0: Lock the shared-scope + namespace + gating decisions

**Files:** none (writes decisions into this plan's "Target namespace" + a new `## Decisions` block appended to `.superpowers/sdd/progress.md`).

- [ ] **Step 1: Resolve shared-scope propagation.** Determine how a `scope: infrastructure` module (`auth`, new `hrmac`) reaches BOTH the central/platform permission set AND every tenant permission set, given `SyncModuleHierarchy` L146-151 exact-match. Confirm which is true today by running, in `c:/laragon/www/aeos365`:
  - `php artisan aero:sync-module --scope=platform --dry-run` (does `auth` appear?)
  - `php artisan aero:sync-module --scope=tenant --dry-run` (does `auth` appear?)
  Then choose ONE mechanism and record it:
  - **(A)** Teach `SyncModuleHierarchy` to treat `scope: infrastructure` (and `shared`) as matching every run scope (change L147 predicate to `$moduleScope !== $scope && $moduleScope !== 'infrastructure'`).
  - **(B)** Have each context's `ModuleSyncFilterInterface` inject infrastructure modules into its scope.
  - Recommendation: **(A)** — smallest, context-free, lives in HRMAC where the rule belongs.
- [ ] **Step 2: Confirm exact permission-string semantics.** Verify whether the middleware `hrmac:platform-users.landlord-user-list.view` matches the discovered code `platform.platform-users.landlord-user-list.view` by suffix or full path (read `packages/aero-hrmac/src/Http/Middleware/CheckRoleModuleAccess.php` handle()). This determines whether target codes are `auth.user_management.users.view` (4-seg) and whether existing grants need remap. Record the exact target strings.
- [ ] **Step 3: Lock scope-gated affordances** (Boss-approved, all three): (a) cross-tenant impersonation is platform-only; (b) role set is scoped per context (tenant roles vs platform roles); (c) user-invitation flow is tenant-only. These become props on the shared pages.
- [ ] **Step 4: Commit the decision record.**

```bash
git add .superpowers/sdd/progress.md docs/superpowers/plans/2026-07-06-shared-auth-access-control-consolidation.md
git commit -m "docs(auth): lock shared-auth/access-control consolidation design decisions"
```

**Gate:** Boss approves the three decisions before Phase 1.

---

## Phase 1 — Access-control → `aero-hrmac` (roles + module-access)

Backend controllers already live in HRMAC; this phase moves the **HRMAC declarations + views** and deletes the stale platform copy. Lowest risk — do first.

### Task 1: Create the shared `hrmac` module declaration

**Files:**
- Create: `packages/aero-hrmac/config/module.php`
- Reference (copy structure from): `packages/aero-core/config/module.php:342-410` (`roles_permissions` + `module_access` blocks)

**Interfaces:**
- Produces: module `code: 'hrmac'`, `scope: 'infrastructure'`, submodule `roles_permissions` with components `roles` (actions view/create/edit/delete/assign) and `module_access` (action configure). Discovered by `ModuleDiscoveryService`.

- [ ] **Step 1: Write a failing test** that the hrmac module is discoverable with the roles/module_access codes.

```php
// packages/aero-hrmac/tests/Feature/HrmacModuleDefinitionTest.php
public function test_hrmac_module_declares_roles_and_module_access(): void
{
    $codes = app(\Aero\HRMAC\Services\ModuleDiscoveryService::class)
        ->getAllPermissionCodes()->pluck('name');
    $this->assertTrue($codes->contains('hrmac.roles_permissions.roles.view'));
    $this->assertTrue($codes->contains('hrmac.roles_permissions.module_access.configure'));
}
```

- [ ] **Step 2: Run it — expect FAIL** (`vendor/bin/phpunit --filter test_hrmac_module_declares_roles_and_module_access`). Expected: hrmac module.php missing.
- [ ] **Step 3: Create `packages/aero-hrmac/config/module.php`** with `code=hrmac`, `name='Access Control'`, `scope='infrastructure'`, `priority=0`, and the `roles_permissions` submodule (`roles`, `module_access`) copied verbatim from the core block's action lists.
- [ ] **Step 4: Run test — expect PASS.**
- [ ] **Step 5: Commit** (`feat(hrmac): declare shared access-control module (roles + module-access)`).

### Task 2: Point both role views at a single shared page + repoint namespace

**Files:**
- Modify: `packages/aero-platform/routes/admin.php:1396-1408` (roles group) — change HRMAC middleware `platform-users.landlord-roles.*` → `hrmac.roles_permissions.roles.*`; change `->defaults('hrmac_role_view', 'Platform/Admin/Roles/Index')` → the shared page name from Phase 3 (interim: keep until Phase 3 renames — see note).
- Modify: `packages/aero-hrmac/src/Http/Controllers/RoleController.php:56,72` — default view + branch to the shared page name.
- Modify: `packages/aero-core/routes/web.php` (roles routes) — HRMAC `core.roles_permissions.*` → `hrmac.roles_permissions.*`.

**Interfaces:**
- Consumes: shared page name decided in Phase 3 (`Shared/AccessControl/Roles/Index` — see Task 7). Phases 2/3 run before this task's view rename lands; sequence Task 2's route/HRMAC repoint together with Task 7.

- [ ] **Step 1: Update RoleController tests** (`packages/aero-hrmac/tests/.../RoleControllerTest`, `packages/aero-core/tests/Feature/Admin/RoleControllerTest.php:35`) to assert the shared component name + `hrmac.roles_permissions.roles.view` gate.
- [ ] **Step 2: Run — expect FAIL.**
- [ ] **Step 3: Repoint** the route middleware + `hrmac_role_view` defaults + controller default/branch.
- [ ] **Step 4: Run — expect PASS.**
- [ ] **Step 5: Commit** (`refactor(hrmac): repoint role admin to shared hrmac namespace + view`).

### Task 3: Remove `roles_permissions` + `module_access` from core, `landlord-roles` from platform

**Files:**
- Modify: `packages/aero-core/config/module.php` — delete `roles_permissions` (L342) + `module_access` (L365) submodule blocks.
- Modify: `packages/aero-platform/config/module.php` — delete `landlord-roles` component (L438) from `platform-users`.
- Delete: `packages/aero-platform/src/Http/Controllers/Admin/RoleController.php` (stale duplicate).

- [ ] **Step 1: Grep for leftover references** — `grep -rn "roles_permissions\|landlord-roles\|Admin\\\\RoleController" packages/aero-core packages/aero-platform --include=*.php` — expect only the lines being removed.
- [ ] **Step 2: Delete the config blocks + stale controller.**
- [ ] **Step 3: Re-sync + verify** — in `c:/laragon/www/aeos365`: `php artisan aero:sync-module --scope=all --prune`; assert `hrmac.roles_permissions.*` exists and `core.roles_permissions.*`/`platform.*.landlord-roles.*` are pruned (`php artisan tinker` count on `permissions`/module table).
- [ ] **Step 4: Commit** (`refactor: remove role/module-access declarations from core+platform (now in hrmac)`).

**Gate:** Roles + Module-Access screens work in BOTH tenant and platform via `hrmac.roles_permissions.*`; 0 console errors (Playwright).

---

## Phase 2 — User management → `aero-auth`

Highest-lift phase (large controller surface + two guards). Move the admin surface into `auth` and drive both contexts from it.

### Task 4: Add `user_management` submodule to the `auth` module

**Files:**
- Modify: `packages/aero-auth/config/module.php` — add `user_management` submodule (components `users` + `user_invitations`) with the union of actions from `aero-core` `user_management` (L199-340) and `aero-platform` `landlord-user-list` (L427-437).

- [ ] **Step 1: Failing test** — `auth.user_management.users.view` discoverable (mirror Task 1's test in `packages/aero-auth/tests`).
- [ ] **Step 2: Run — FAIL.**
- [ ] **Step 3: Add the submodule block.**
- [ ] **Step 4: Run — PASS.**
- [ ] **Step 5: Commit** (`feat(auth): declare shared user-management module`).

### Task 5: Introduce a shared user-admin controller in `aero-auth`, guard-parameterized

**Files:**
- Create: `packages/aero-auth/src/Http/Controllers/Admin/UserAdminController.php` — the shared surface; resolves the acting guard (`tenant`|`landlord`) and the target model/table from a context binding, so it serves both tenant `users` and central `landlord_users`.
- Modify: `packages/aero-core/routes/web.php` (user routes) — repoint to `UserAdminController` + `hrmac:auth.user_management.users.*`, render `Shared/UserManagement/Users/*` (Phase 3).
- Modify: `packages/aero-platform/routes/admin.php:1375-1390` — repoint to `UserAdminController` + `hrmac:auth.user_management.users.*`.
- Delete (after parity verified): `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php`, `packages/aero-platform/src/Http/Controllers/Admin/LandlordUserController.php`.

**Interfaces:**
- Produces: `UserAdminController` with the method set currently on `CoreUserController` (index/paginate/stats/store/update/toggleStatus/updateUserRole/destroy/restore/forceDelete/bulk*/export/lock/unlock/forcePasswordReset/resendVerification/invite) — invitation + lifecycle methods gated to tenant context; impersonate gated to platform.
- Consumes: a context binding (guard + model + route-prefix) supplied by each host/service provider.

- [ ] **Step 1: Port `CoreUserControllerTest` + `LandlordUserControllerTest`** into `packages/aero-auth/tests/Feature/Admin/UserAdminControllerTest.php`, asserting BOTH guards (tenant list from `users`, platform list from `landlord_users`) and the `auth.user_management.users.*` gates. (Full method-by-method port — expand each existing test case.)
- [ ] **Step 2: Run — expect FAIL** (controller absent).
- [ ] **Step 3: Implement `UserAdminController`** by moving `CoreUserController` logic and parameterizing the model/guard resolution; fold in the platform-only `impersonate` and drop tenant-only invite in platform context.
- [ ] **Step 4: Run the ported tests — expect PASS.**
- [ ] **Step 5: Repoint both route files; run both host apps' user routes** (`php artisan route:list | grep users`).
- [ ] **Step 6: Delete `CoreUserController` + `LandlordUserController`; run full auth + core + platform suites — expect green.**
- [ ] **Step 7: Commit** (`refactor(auth): shared guard-parameterized user-admin controller`).

### Task 6: Remove `user_management` from core, `platform-users`/`landlord-user-list` from platform

**Files:**
- Modify: `packages/aero-core/config/module.php` — delete `user_management` (L199-340).
- Modify: `packages/aero-platform/config/module.php` — delete `platform-users` submodule (L418-437; the `landlord-roles` piece already gone in Task 3).

- [ ] **Step 1: Grep leftovers** (`user_management\|platform-users\|landlord-user`), expect only removal lines + already-repointed refs.
- [ ] **Step 2: Delete blocks.**
- [ ] **Step 3: Re-sync `--scope=all --prune`; assert `auth.user_management.*` present, `core.user_management.*`/`platform-users.*` pruned.**
- [ ] **Step 4: Commit** (`refactor: remove user-management declarations from core+platform (now in auth)`).

**Gate:** Users screen works in BOTH tenant and platform via `auth.user_management.*`; 0 console errors.

---

## Phase 3 — Frontend page unification

### Task 7: Parameterize the done Core pages into shared pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Shared/UserManagement/Users/{Index,Create,Edit,Show,UsersRail}.jsx` (+ `Invitations/Index.jsx`)
- Create: `packages/aero-ui/resources/js/Pages/Shared/AccessControl/Roles/{Index,AccessDrawer,RolesRail}.jsx`
- Move-from (reuse logic): `Pages/Core/Users/*`, `Pages/Core/Roles/*`
- Modify: tenant controllers/route defaults to render `Shared/UserManagement/*` + `Shared/AccessControl/*`; platform likewise.
- Delete: `Pages/Platform/Admin/Users/*`, `Pages/Platform/Admin/Roles/Index.jsx`, and the old `Pages/Core/Users/*`/`Pages/Core/Roles/*` once callers repoint.

**Interfaces:**
- Consumes props: `routePrefix` (e.g. `core.users` | `platform.admin.users`), `hrmacNamespace` (`auth.user_management` | `hrmac.roles_permissions`), `scope` (`tenant` | `platform`), `can` flags. Replace every hardcoded `route('core.users.*')` and `useHRMAC('core.user_management.*')` with prefix-driven equivalents.

- [ ] **Step 1:** Build `Shared/UserManagement/Users/Index.jsx` from `Core/Users/Index.jsx`, swapping hardcoded `core.*` route names + `core.user_management.*` HRMAC for the `routePrefix`/`hrmacNamespace` props; gate invitations on `scope==='tenant'`, impersonation on `scope==='platform'`.
- [ ] **Step 2:** Repeat for Roles (`AccessDrawer` respects [[module-grouping-rule]]: core flat, single product flat, 2+ products grouped).
- [ ] **Step 3:** Repoint tenant + platform controllers/route-defaults to the shared pages.
- [ ] **Step 4: Live UA — BOTH shells (sidebar+command), 390/768/1440**, tenant AND platform: user list, role editor, module-access — 0 console errors (Playwright).
- [ ] **Step 5:** Delete `Platform/Admin/Users/*`, `Platform/Admin/Roles/Index`, old `Core/Users/*`, `Core/Roles/*`.
- [ ] **Step 6: Commit** (`refactor(ui): shared user + access-control pages (scope-parameterized)`).

---

## Phase 4 — Repoint stragglers + full verification

### Task 8: Sweep every remaining reference + seeded permissions

**Files:** repo-wide.

- [ ] **Step 1:** `grep -rn "core.user_management\|core.roles_permissions\|core.module_access\|platform-users\|landlord-user-list\|landlord-roles" packages` — expect ZERO (outside git history). Fix any: policies (`UserPolicy.php`), middlewares (`ModuleAccessMiddleware.php`), seeders, `useHRMAC()` strings.
- [ ] **Step 2:** Repoint role/permission SEEDERS granting old codes to the new `auth.*`/`hrmac.*` codes; re-seed super-admin `*` unaffected.
- [ ] **Step 3:** `php artisan aero:sync-module --scope=all --prune` on `aeos365` (central) + a tenant DB + `aeos365-standalone`.
- [ ] **Step 4: Full suites** — `vendor/bin/phpunit` in aero-auth, aero-hrmac, aero-core, aero-platform — green.
- [ ] **Step 5: Live UAT all three contexts** (tenant, platform, standalone): log in, open Users + Roles + Module-Access, exercise create/edit/assign/impersonate(platform)/invite(tenant) — 0 console errors, both shells, 3 breakpoints.
- [ ] **Step 6: Commit** (`refactor: repoint all consumers to shared auth/hrmac namespaces`).

---

## Risks & Rollback

- **Blast radius:** live auth right before the FYP demo (2026-07-17). Mitigate: phase-gated, each phase independently green + committed; work on a branch, not `main`.
- **Permission lockout:** if seeds aren't repointed, admins lose access to the moved surfaces. Mitigate: Task 8 Step 2 before any re-seed; keep super-admin `*` bypass; verify login after each re-sync.
- **Tenant DB drift:** each tenant DB must re-sync. Mitigate: run `aero:sync-module --scope=tenant` across tenants (or on next provision); verify one seeded tenant (`democorp`).
- **Rollback:** each phase is a discrete commit set on the branch — `git revert` the phase range; re-run `aero:sync-module --prune` to restore prior codes. Nothing merges to `main` until Phase 4 gate passes.

## Verification Gates (all must hold before merge)

- Zero `core.user_management`/`core.roles_permissions`/`core.module_access`/`platform-users`/`landlord-*` references remain.
- `auth.user_management.*` + `hrmac.roles_permissions.*` synced into BOTH central and tenant permission sets.
- Users/Roles/Module-Access render from the SAME shared pages in tenant + platform, both shells, 390/768/1440, 0 console errors.
- Full PHPUnit green across aero-auth/aero-hrmac/aero-core/aero-platform.
- `aero-auth`/`aero-hrmac` contain NO `core`/`platform` symbols (dependency direction intact).
