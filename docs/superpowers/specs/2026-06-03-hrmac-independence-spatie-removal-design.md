# HRMAC Independence + Spatie Removal + Nested Sub-modules — Design Spec

**Date:** 2026-06-03
**System:** AEOS365 (Laravel 12, dual-mode SaaS + Standalone). Authorization is HRMAC
(role + module/sub-module/component/action access). `spatie/laravel-permission` is a
leftover half-migration: still a composer dep + imported in 19 files + ships the
`permissions`/`role_has_permissions`/`model_has_permissions` tables, but the app does NOT
use Spatie permissions — it uses `role_module_access`. Surfaced by UAT (B-39: `/roles` 500
`PermissionRegistrar::$permissionClass null`).

**Goal:** Remove Spatie entirely; make HRMAC a self-contained custom authorization system
(roles + module access over module → sub-module → component → action) with **nested
sub-module** support. No role-data migration (in-place re-own).

---

## 1. Approach

**In-place re-own (chosen).** Keep `roles` + `model_has_roles` (the tables the app already
uses) with unchanged schema; HRMAC owns them. Strip the Spatie *permission* tables. Swap
all Spatie imports to HRMAC. Drop the composer dep. Add nested `parent_id` to `sub_modules`.
Existing role rows + assignments + grants survive untouched.

Rejected: rename to `hrmac_*` (large data migration, touches every role query);
big-bang access-layer rewrite (overkill — the service already works).

## 2. Schema / migrations (owned by aero-hrmac)

- **roles** (unchanged columns): `id, name, guard_name, scope ('platform'|'tenant'),
  is_protected, is_active, [tenant_id], [default_dashboard], timestamps`. Creation guarded
  with `Schema::hasTable` so existing DBs are untouched and fresh installs create it.
- **model_has_roles** (unchanged): `role_id, model_type, model_id` (+ `tenant_id` where
  present). Guarded creation.
- **Drop Spatie permission entity:** new migration `dropIfExists('permissions')`,
  `dropIfExists('model_has_permissions')`, `dropIfExists('role_has_permissions')`. Strip
  those three from the legacy `create_permission_tables` migration so fresh installs never
  create them. `roles` + `model_has_roles` creation is retained (guarded).
- **Nested sub-modules:** migration adds nullable self-referencing
  `parent_id → sub_modules(id)` (nullOnDelete) + index `(module_id, parent_id)`. Flat data
  (parent_id = null) keeps working unchanged.

Migration ordering: the `roles`/`model_has_roles` creation keeps its early date so nothing
that seeds roles runs first; only the permission-table creation is removed + a drop
migration added for already-provisioned DBs.

## 3. Models

- **`Aero\HRMAC\Models\Role`** — already extends `TenantModel` (no Spatie base). Confirm:
  `guard_name`, `scope`, `is_protected`, `is_active` fillable; `users()` (morph via
  model_has_roles); module-access relations. This is the single Role model
  (`config('hrmac.models.role')`).
- **`Aero\HRMAC\Models\SubModule`** — add `parent()` (belongsTo self) + `children()`
  (hasMany self) + `descendants()`/`ancestors()` helpers; `module()` unchanged.
- **`User` / `LandlordUser`** — remove the Spatie `HasRoles`/`HasPermissions` trait; keep
  existing custom `roles()`, `hasRole()`, `assignRole()`, `syncRoles()`, `getRoleNames()`
  (all already present and HRMAC-backed). `can()`/`authorize()` resolve via the HRMAC
  `Gate::before` hook (added in B-38).

## 4. Access resolution + sync

- **`RoleModuleAccessService`** — nested cascade: when checking sub-module access, a grant
  on any **ancestor** sub-module (via `parent_id` chain) grants the descendant. Component/
  action checks already cascade from their sub-module; that sub-module now also inherits
  from ancestors. Per-role access cache + versioning unchanged.
- **Sync (`ModuleDiscoveryService` + `aero:sync-module`)** — parse a nested `submodules`
  array inside a sub-module config entry, persisting `parent_id`. Backward compatible:
  configs without nesting behave exactly as today.
- **`config/module.php`** — nesting is opt-in; existing flat configs unchanged. (No mass
  re-authoring required; nesting available for packages that want it.)

## 5. The 19 Spatie-coupled files

Swap `Spatie\Permission\Models\Role` → `Aero\HRMAC\Models\Role`; remove
`Spatie\Permission\Models\Permission` usage (replace permission counts/management with
module-access via `RoleModuleAccessService`). Specifics:
- **RoleController (core + platform)** + **RoleService** — manage roles + their
  module-access grants (the HRMAC access tree), not Spatie permissions. `/roles` index uses
  `withCount('users')` + module-access count (not `permissions`).
- **CoreUserController / AssignUserRoles / Import/ExportJob / AdminDashboardService /
  MfaPolicyController** — role refs → HRMAC Role.
- **Kernel.php (core + hrm + platform)** — remove Spatie `role` / `permission` /
  `role_or_permission` middleware aliases; any route using them moves to `hrmac:`.
- **RoleHierarchyMiddleware (core + platform)** — resolve roles via HRMAC Role.
- **HRMAC Facade** — drop the stray Spatie reference.

## 6. Composer / config

- Remove `spatie/laravel-permission` from `aero-core`, `aero-hrm`, `aero-platform`
  `composer.json`; `composer update spatie/laravel-permission --no-interaction` (removal) in
  both hosts; confirm `composer.lock` clean.
- Delete `aero-core/config/permission.php`; replace any `config('permission.*')` with
  `config('hrmac.*')` (add the keys to `aero-hrmac` config if missing). Remove Spatie's
  `PermissionServiceProvider` auto-discovery (drops with the package).

## 7. Verification (exit criteria)

- `migrate:fresh` clean in **both** hosts; DB has `roles`, `model_has_roles`,
  `role_module_access`, `modules`, `sub_modules(+parent_id)`; **no** `permissions` /
  `model_has_permissions` / `role_has_permissions`.
- `grep -r "Spatie\\Permission" packages` → 0; `composer show` → no spatie/laravel-permission.
- HRMAC suite green incl. a new **nested-cascade** test (parent grant ⇒ child access) and a
  flat-still-works test.
- Live MCP re-sweep: `/roles`, `/users`, `/settings/system`, `/settings/mail` render as SA;
  HR allowed its grants, Employee denied; B-39 closed.
- P1.3 + P1.4 automated specs still green.

## 8. Non-goals
- Not re-authoring `config/module.php` to use nesting everywhere (support added; adoption later).
- Not changing the HRMAC cache strategy or the Gate hook (B-38) beyond nested cascade.
- Not migrating role table names (`roles`/`model_has_roles` stay).

## 9. Risks
- **Live data:** mitigated by in-place re-own (no row migration) + guarded creates.
- **Migration ordering across packages** (core/platform both shipped `create_permission_tables`):
  consolidate carefully; verify `migrate:fresh` both modes + a provisioned tenant.
- **Hidden Spatie usage** (Gate, service providers, blade `@role`): grep + runtime sweep catch it.
- **composer update** pulling other changes: pin to removing only the one package.
