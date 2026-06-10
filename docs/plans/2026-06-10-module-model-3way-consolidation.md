# Module-Model 3-Way Consolidation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (or subagent-driven-development) to implement task-by-task. Steps use checkbox (`- [ ]`) syntax. Re-run the oracle after every task; stop and report if it goes red.

**Goal:** Collapse the three duplicate module-hierarchy model sets (aero-core/tenant, aero-platform/central, aero-hrmac/context-free) into the single canonical aero-hrmac set, with `class_alias` bridges so the ~53 existing consumer references keep resolving untouched.

**Architecture:** `aero-hrmac` is the canonical home — its `HrmacModel` base is *context-free* (uses the default connection, which stancl/tenancy swaps to the tenant DB, the central DB for platform, the single DB standalone), so one model set serves all three contexts and dissolves the tenant-vs-central split that core (`TenantModel`) and platform (`CentralModel`) currently duplicate. We port the union of the three APIs into hrmac's models, alias the legacy FQNs, then delete the duplicates.

**Tech Stack:** Laravel 12 · Eloquent · Orchestra Testbench (aero-core `phpunit.xml` is the runnable oracle) · path-repo monorepo with junctioned packages in both hosts.

**Oracle / verification (run after every task):**
- `cd /c/laragon/www/Aero-Enterprise-Suite-Saas && /c/laragon/www/aeos365/vendor/bin/phpunit -c packages/aero-core/phpunit.xml` — must stay at baseline **121t / 20e / 5f / 273a** (no NEW reds).
- Both hosts boot (`php -r` bootstrap snippet) and resolve the aliased FQNs in SaaS **and** standalone.
- After any file move/delete: dump host autoloaders **one at a time, foreground** (`composer dump-autoload -o`), verify 0 stale classmap entries before testing (backgrounded concurrent dumps corrupt the classmap — known gotcha).

---

## Decisions (CONFIRMED 2026-06-10)

**Governing invariant (Boss):** `aero-core` ⟂ `aero-platform` — ZERO dependencies either direction. Both may depend on SHARED packages (contracts, kernel, license, infrastructure, auth, hrmac). **Shared packages must be pure-shareable — they must NEVER reference core or platform.** Every task below is checked against this; the deptrac layers in the dependency-architecture standard encode it.

1. ✅ **Canonical package = `aero-hrmac`** (shared, pure). Owns the table migrations already (`modules`, `sub_modules`, `module_components`, `module_component_actions`); its base is context-free.
2. ✅ **Canonical base = `HrmacModel`.** Retire the `TenantModel` (core) and `CentralModel` (platform) variants.
3. ✅ **Canonical class names = `Module`, `SubModule`, `ModuleComponent`, `ModuleComponentAction`** (CONFIRMED). Rename hrmac's `Component`→`ModuleComponent`, `Action`→`ModuleComponentAction`. Matches tables + the majority of ~53 consumer refs.
4. ✅ **Retire the light `Component` / `Action` stub sets** in core AND platform (distinct from the rich models). Task 1 confirms dead first.
5. ✅ **Drop the dead permission API — do NOT port it (CONFIRMED).** `userCanAccess`, `permissionRequirements`, `getRequiredPermissions`, `getAllRequiredPermissions`, `getModuleLevelPermissions` all reference a `ModulePermission` class **that does not exist anywhere in the codebase** — they are dead/broken. Porting them would drag a broken ref into pure hrmac. Canonical access = hrmac's **`roleAccess`** (role_module_access). Task 1 confirms the `userCanAccess`→`checkAccess` chain is genuinely dead (no live external caller) before removal, so no behavior change. The ONLY genuinely-working methods hrmac lacks and that get ported: `Module::scopeInCategory`, `ModuleComponent::scopeOfType`, `ModuleComponent::types()` (static; used by platform `ModuleController::types()`) — all verified pure (no core/platform refs).
6. ✅ **`plans()` → platform-registered dynamic relationship (CONFIRMED).** `aero-platform/Module::plans()` relates to `Aero\Platform\Models\Plan`; a pure hrmac `Module` must NOT reference it. Platform registers `plans()` onto the canonical Module at boot via the relationship-registry pattern (same mechanism core User uses) from `AeroPlatformServiceProvider`. NOT ported into hrmac.
7. ✅ **Bridges:** `class_alias` `Aero\Core\Models\{Module,SubModule,ModuleComponent,ModuleComponentAction}` AND `Aero\Platform\Models\{...}` → `Aero\HRMAC\Models\{...}`, registered in `HRMACServiceProvider::register()`. Removed in the final enforcement phase.
8. ✅ **Table collision (`modules`):** hrmac owns `2024_01_01_000001_create_modules_table` (tenant + central via context-free); aero-platform owns `2025_11_29_000000_create_modules_table` (central). Task 7 diffs schemas; delete platform's only after Boss confirms (deployed central DBs); fold any column delta into an hrmac migration guarded by `Schema::hasColumn`.

**Purity note:** the union-API method bodies were verified to contain ZERO `Aero\Core` / `Aero\Platform` / `Aero\Auth` references — so what lands in hrmac keeps it pure-shareable. The only cross-package coupling found (`plans()` → platform Plan) is handled by Decision 6 (inversion via registry), preserving core⟂platform and hrmac purity.

---

## Current-state inventory (audit 2026-06-10)

**Sets (16 model files for one hierarchy):**

| Package | Files | Base | Notes |
|---|---|---|---|
| aero-core | Module, SubModule, ModuleComponent, ModuleComponentAction, **Component, Action** | `TenantModel` | rich set + light Component/Action stubs |
| aero-platform | Module, SubModule, ModuleComponent, ModuleComponentAction, **Component, Action** | `CentralModel` | near-identical to core; Module adds `plans()` |
| aero-hrmac | Module, SubModule, **Component, Action** | `HrmacModel` | canonical; has `roleAccess`, `parent/children/ancestorIds`; lacks permission API |

**API to land on hrmac models** (per Decision 5 — the dead permission API is DROPPED, only genuinely-working methods hrmac lacks are ported):

- `Module`: port `scopeInCategory` — from `packages/aero-core/src/Models/Module.php`. DROP `userCanAccess`/`permissionRequirements`/`getAllRequiredPermissions`/`getModuleLevelPermissions` (dead — ModulePermission). `plans()` → Decision 6 (platform registry). hrmac already has `subModules`, `components`, `roleAccess`, `scopeActive`, `scopeOrdered`, `scopeTenant`, `scopePlatform`.
- `SubModule`: nothing to port (its non-dead methods — `module`, `components`, `getFullCodeAttribute`, scopes — already exist on hrmac `SubModule`, which additionally has `parent`/`children`/`ancestorIds`/`roleAccess`). DROP the dead perm methods.
- `ModuleComponent` (renamed from hrmac `Component`): port `scopeOfType` + static `types()` — from `packages/aero-core/src/Models/ModuleComponent.php`. DROP the dead perm methods. hrmac already has `module`, `subModule`, `actions`, `roleAccess`, `getFullCodeAttribute`, `scopeActive`.
- `ModuleComponentAction` (renamed from hrmac `Action`): nothing to port (`component`, `roleAccess`, `getFullCodeAttribute` already present). DROP dead `permissionRequirements`.

So the real port is small: **3 methods** (`Module::scopeInCategory`, `ModuleComponent::scopeOfType`, `ModuleComponent::types()`) — all verified pure.

**Consumers:** `Aero\Core\Models\<set>` = 28 refs / 10 files; `Aero\Platform\Models\<set>` = 25 refs / 16 files. All covered by the alias bridge (Decision 7) — **zero edits** for consumers using the public API that survives. Consumers calling `plans()` need the Decision-6 registry; any caller of the dropped permission methods (confirmed dead by Task 1) gets removed.

---

## Task 1: Confirm the light `Component`/`Action` sets are dead + inventory `plans()` callers

**Files:** none modified (investigation + record findings in this plan's "Findings" appendix).

- [ ] **Step 1:** Find every reference to the light sets:
  Run: `grep -rnE "Aero..(Core|Platform)..Models..(Component|Action)\b" packages --include=*.php | grep -vE "ModuleComponent|ModuleComponentAction|/Models/(Component|Action|ModuleComponent|ModuleComponentAction)\.php"`
  Expected: classify each hit. If a hit imports `...\Models\Component` / `...\Models\Action` (not `ModuleComponent`), it uses the light set.
- [ ] **Step 2:** Find every `->plans(` / `::plans(` caller and every `Aero\Platform\Models\Module` user that calls `plans()`:
  Run: `grep -rnE "->plans\(|module->plans|\$module->plans" packages --include=*.php`
  Record callers — these need the Decision-6 registry.
- [ ] **Step 3:** Record findings in the appendix below. If the light sets have live consumers, STOP and revise Decision 4 with the Boss before proceeding.

---

## Task 2: Rename hrmac `Component`→`ModuleComponent`, `Action`→`ModuleComponentAction`

**Files:**
- Rename: `packages/aero-hrmac/src/Models/Component.php` → `ModuleComponent.php`
- Rename: `packages/aero-hrmac/src/Models/Action.php` → `ModuleComponentAction.php`
- Modify: every intra-hrmac reference (`Component::class`, `Action::class`, `belongsTo(Component::class)`, etc.) in hrmac Models + `RoleModuleAccess.php` + `RoleModuleAccessService.php` + `RoleService.php` + hrmac `ModuleController.php`.

- [ ] **Step 1:** `git mv packages/aero-hrmac/src/Models/Component.php packages/aero-hrmac/src/Models/ModuleComponent.php` and same for Action→ModuleComponentAction.
- [ ] **Step 2:** In both renamed files, change `class Component` → `class ModuleComponent` (resp. `Action`→`ModuleComponentAction`). Keep `protected $table = 'module_components'` / `'module_component_actions'` (already correct).
- [ ] **Step 3:** Repoint intra-hrmac refs. Run to find them: `grep -rnE "\b(Component|Action)::class|belongsTo\((Component|Action)::|hasMany\((Component|Action)::|\b(Component|Action) " packages/aero-hrmac/src --include=*.php` — change `Component`→`ModuleComponent`, `Action`→`ModuleComponentAction` (mind word boundaries; do NOT touch `ModuleComponent`/`ModuleComponentAction` already-correct).
- [ ] **Step 4:** Add temporary self-aliases so hrmac-internal code mid-migration still resolves both names (optional; remove in Task 8): in `HRMACServiceProvider::register()` add `class_alias(\Aero\HRMAC\Models\ModuleComponent::class, 'Aero\\HRMAC\\Models\\Component')` etc. **Only if** Task-1 found hrmac-external consumers of `Aero\HRMAC\Models\Component`.
- [ ] **Step 5:** Dump aeos365 autoload (foreground), verify 0 stale `Component.php`/`Action.php` classmap entries.
- [ ] **Step 6:** Oracle + both-host boot. Expected: baseline 121t/20e/5f.
- [ ] **Step 7:** Commit: `git commit -m "consolidation: rename hrmac Component/Action -> ModuleComponent/ModuleComponentAction"`

---

## Task 3: Port the union permission/access API into hrmac's canonical models

**Files:**
- Modify: `packages/aero-hrmac/src/Models/{Module,SubModule,ModuleComponent,ModuleComponentAction}.php`
- Test: `packages/aero-hrmac/tests/...` (hrmac has no phpunit.xml yet — see Task 3a)

### Task 3a: Stand up an hrmac test harness (oracle for the ported API)

- [ ] **Step 1:** Create `packages/aero-hrmac/phpunit.xml` (copy `packages/aero-core/phpunit.xml`, swap testsuite dir to `tests`).
- [ ] **Step 2:** Create `packages/aero-hrmac/tests/PackageTestCase.php` (Orchestra Testbench, :memory: sqlite, boots `HRMACServiceProvider`, `RefreshDatabase`). Mirror aero-core's PackageTestCase env setup (app.key, central connection = same db).
- [ ] **Step 3:** Add `"orchestra/testbench"` + `"phpunit/phpunit"` to hrmac `composer.json` `require-dev`; `composer update` hrmac in a host or add testbench to the host that runs it.
- [ ] **Step 4:** Smoke test: `tests/Feature/ModuleHierarchyTest.php` that migrates, inserts a module + sub_module + component + action, asserts the relationships traverse. Run: `/c/laragon/www/aeos365/vendor/bin/phpunit -c packages/aero-hrmac/phpunit.xml`. Expected: PASS.
- [ ] **Step 5:** Commit.

### Task 3b: Port the 3 working methods hrmac lacks (TDD); DROP the dead permission API

Per Decision 5: do NOT port `userCanAccess`/`permissionRequirements`/`getRequiredPermissions`/`getAllRequiredPermissions`/`getModuleLevelPermissions` (they reference the non-existent `ModulePermission` — dead). Port only the genuinely-working methods.

- [ ] **Step 1 (failing test):** In `ModuleHierarchyTest`, add tests for `Module::scopeInCategory('foo')` (returns only modules with that category), `ModuleComponent::scopeOfType('bar')`, and `ModuleComponent::types()` (returns the static type map). Expected semantics: copy from `aero-core/src/Models/Module.php` (`scopeInCategory`) and `aero-core/src/Models/ModuleComponent.php` (`scopeOfType`, `types`).
- [ ] **Step 2:** Run — expected FAIL ("undefined method").
- [ ] **Step 3:** Copy ONLY `scopeInCategory` into hrmac `Module.php`; `scopeOfType` + static `types()` into hrmac `ModuleComponent.php`. Verify each body has zero `Aero\Core`/`Aero\Platform` refs (purity).
- [ ] **Step 4:** Run — expected PASS. Oracle (core suite) still baseline.
- [ ] **Step 5:** Commit.

---

## Task 4: Bridge — alias legacy core + platform FQNs to hrmac

**Files:**
- Modify: `packages/aero-hrmac/src/HRMACServiceProvider.php`

- [ ] **Step 1:** In `HRMACServiceProvider::register()`, add a guarded alias loop:
```php
$aliases = [
    \Aero\HRMAC\Models\Module::class                => ['Aero\\Core\\Models\\Module', 'Aero\\Platform\\Models\\Module'],
    \Aero\HRMAC\Models\SubModule::class             => ['Aero\\Core\\Models\\SubModule', 'Aero\\Platform\\Models\\SubModule'],
    \Aero\HRMAC\Models\ModuleComponent::class       => ['Aero\\Core\\Models\\ModuleComponent', 'Aero\\Platform\\Models\\ModuleComponent'],
    \Aero\HRMAC\Models\ModuleComponentAction::class => ['Aero\\Core\\Models\\ModuleComponentAction', 'Aero\\Platform\\Models\\ModuleComponentAction'],
];
foreach ($aliases as $canonical => $legacyNames) {
    foreach ($legacyNames as $legacy) {
        if (! class_exists($legacy, false)) {
            class_alias($canonical, $legacy);
        }
    }
}
```
- [ ] **Step 2:** This will COLLIDE with the still-present core/platform model FILES (a real class can't also be an alias). So aliasing only takes effect AFTER Task 5 deletes those files. Until then, guard with `class_exists(..., false)` (already above) — the alias is a no-op while the real class exists. Verify: oracle still baseline (alias dormant).
- [ ] **Step 3:** Commit.

---

## Task 5: Delete the duplicate core + platform model sets

**Files:**
- Delete: `packages/aero-core/src/Models/{Module,SubModule,ModuleComponent,ModuleComponentAction,Component,Action}.php`
- Delete: `packages/aero-platform/src/Models/{Module,SubModule,ModuleComponent,ModuleComponentAction,Component,Action}.php`

- [ ] **Step 1:** Handle `plans()` FIRST (Decision 6): register the platform `Module::plans()` relationship from `AeroPlatformServiceProvider` via the relationship registry, BEFORE deleting `aero-platform/src/Models/Module.php`. Add a test that `app(Module)->plans` still resolves in SaaS.
- [ ] **Step 2:** `git rm` the 6 core + 6 platform module-set files.
- [ ] **Step 3:** Dump BOTH hosts (one at a time, foreground, `-o`). Verify 0 stale classmap entries for the deleted paths and that `Aero\Core\Models\Module` now resolves via alias.
- [ ] **Step 4:** Oracle + both-host boot snippet asserting `(new Aero\Core\Models\Module) instanceof Aero\HRMAC\Models\Module` and the same for `Aero\Platform\Models\Module`, in SaaS + standalone. Expected baseline + aliases OK.
- [ ] **Step 5:** Repoint any consumer that broke because it called a method NOT in the union (the oracle/host boot will surface these). Likely candidates: platform `plans()` callers (handled Step 1), `ModuleComponent::types()` (ported Task 3b). Fix each, re-verify.
- [ ] **Step 6:** Commit.

---

## Task 6: Reconcile the core/platform `ModuleAccessService` duplicates

**Context:** `aero-core/src/Services/ModuleAccessService.php`, `aero-core/src/Services/Module/ModuleAccessService.php`, `aero-platform/src/Services/ModuleAccessService.php`, `aero-platform/src/Services/Module/ModuleAccessService.php` all reference the module models. With models consolidated they resolve via alias, but these services are themselves duplicated.

- [ ] **Step 1:** Diff the four service files. If byte-identical (modulo namespace), this is a separate service-consolidation — record as a follow-on, out of scope for THIS plan (models only). If they differ materially, note for a future chunk.
- [ ] **Step 2:** No code change in this task — just confirm the alias keeps them green. Oracle baseline.

---

## Task 7: Reconcile the `modules` table migration collision

**Files:**
- Inspect: `packages/aero-hrmac/database/migrations/2024_01_01_000001_create_modules_table.php`
- Inspect: `packages/aero-platform/database/migrations/2025_11_29_000000_create_modules_table.php`

- [ ] **Step 1:** Diff the two schemas. Determine if platform's central `modules` has columns hrmac's lacks.
- [ ] **Step 2:** If platform's is a strict subset/equal: delete the platform migration (hrmac's runs in central too via context-free). If it has extra columns: fold them into a new hrmac migration guarded by `Schema::hasColumn`, then delete platform's create. Confirm with Boss before deleting (deployed central DBs).
- [ ] **Step 3:** Verify `migrate:fresh` in both modes shows the tables created exactly once (0 "already exists").
- [ ] **Step 4:** Commit.

---

## Task 8: Cleanup

- [ ] **Step 1:** Remove any temporary self-aliases added in Task 2 Step 4.
- [ ] **Step 2:** Update `docs/standards/dependency-architecture.md` + the decoupling memory to record module models canonical in aero-hrmac.
- [ ] **Step 3:** Full regression: oracle baseline + both-host boot + a tenant + central UAT smoke of a module-access path.
- [ ] **Step 4:** Final commit.

---

## Risks

- **HIGH — silent API gaps:** a consumer calling a method that existed on core/platform but not on the union hrmac model fails only at runtime. Mitigation: the oracle (core suite) + both-host boot exercise the main paths; Task 5 Step 5 sweeps stragglers. Consider a temporary `__call` shim on hrmac models that logs unknown-method hits during a soak.
- **HIGH — `plans()` up-edge:** porting it would create hrmac→platform. Must use the registry (Decision 6). If the registry pattern isn't wired for module models, that's a prerequisite sub-task.
- **MED — table collision in central:** deleting platform's `create_modules` on a deployed central DB. Gate on Boss confirmation (Task 7 Step 2).
- **MED — three sets drift during the work:** core/platform sets are near-identical now; do Tasks 2-5 in one focused pass so they don't diverge under a background committer.
- **LOW — hrmac test harness:** new (Task 3a); keep it minimal.

## Findings appendix (filled during Task 1)

- Light `Component`/`Action` consumers: _TBD Task 1_
- `plans()` callers: _TBD Task 1_
