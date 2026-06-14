# Dependency Decoupling — Phase 0 Audit (Pre-flight + Safety Net)

**Executed:** 2026-06-09 · **Mode:** read-only (no code moved) · **Branch:** `feature/core-admin-ca1-ca7`
**Inputs:** [implementation plan](dependency-decoupling-implementation.md) Phase 0 · [architecture standard](../standards/dependency-architecture.md)
**Consumes-into:** every later phase reads the move-map (§2) and the V4 inversion inventory (§5).

---

## TL;DR — the one thing that changes the plan

**There is no green baseline to snapshot.** The plan's Phase 0 assumes "run full suite both modes; snapshot green … as the regression oracle." Reality (this run):

| Suite | Result | Note |
|-------|--------|------|
| `aero-core` package suite | **RED** — 118 tests, 20 errors, 5 failures | route-name / Inertia-prop drift + setup errors |
| SaaS host (`aeos365`) | **RED** — 133 tests, **106 errors**, 4 failures | one systemic root cause (see below) |
| Standalone host (`aeos365-standalone`) | **CANNOT RUN** | `phpunit.xml` points at `tests/Unit` which does not exist |
| `aero-auth` / `aero-installation` / `aero-platform` | **NO RUNNABLE SUITE** | no `phpunit.xml`; only `aero-core` has one |

The 106 SaaS-host errors share a **single root cause** and it is the exact pathology this refactor exists to kill:

```
Illuminate\Database\QueryException: table "support_tickets" already exists
  at packages/aero-core/.../2026_05_29_000100_create_support_tickets_table.php:21
  via RefreshDatabase → migrate
```

A migration set is being **registered/run twice** in one context — the migration-registration coupling the architecture standard predicts ("a dependency edge is a migration-registration edge"). This is observable *today*, which makes it a usable oracle: **after Phases 1–5 it must disappear.**

### Gate decision required before committing bulk budget
The "snapshot green" premise is false. Pick one before Phase 1:
- **(A) Stabilize first** — fix the duplicate-registration + standalone phpunit config so both modes go green, *then* refactor against a true oracle. (Recommended — the refactor's whole justification is migration-context isolation; we cannot measure success without a runnable install/migration baseline.)
- **(B) Proceed against a known-red baseline** — freeze the exact failing-test list below as the delta oracle ("no *new* reds; the `support_tickets` error must clear"). Faster start, weaker safety net.

> Recorded baseline counts above ARE the frozen snapshot for option (B).

---

## 1. Wizard divergence ruling (Phase 4 / V5)

The installation wizard exists in **both** `aero-core` and `aero-installation`. **`aero-installation` is the canonical superset** — adopt it, delete core's copy.

**Installation-only steps (3 extra, no core equivalent):** `PlanSeedingStep` (342 ln), `PlatformConfigurationStep` (201 ln), `TenantProvisioningStep` (126 ln) — all SaaS-provisioning concerns.

**Shared-name steps — line counts (core vs installation), divergence flagged:**

| Step | core | installation | Divergence |
|------|-----:|-------------:|------------|
| BaseInstallationStep | 144 | 144 | identical size |
| CacheStep | 92 | 80 | minor |
| ConfigurationStep | 93 | **190** | **major** |
| DatabaseConnectionStep | 183 | 178 | minor |
| FinalizeStep | 154 | **318** | **major** |
| LicenseStep | 146 | 130 | minor |
| MigrationStep | 173 | **431** | **major — the migration runner** |
| ModuleDiscoveryStep | 89 | **473** | **major** |
| SettingsStep | 122 | 135 | minor |
| AdminUserStep | 214 | 205 | minor |
| SeedingStep | 119 | 134 | minor |

Orchestrators/controllers also diverge: `InstallationOrchestrator` 555 (core) vs **798** (installation); `UnifiedInstallationController` 2001 (core) vs **2078** (installation). Both pairs are currently hand-synced and **both are modified in the working tree** (git status) — active drift in flight.

**Ruling:** installation's copies are the actively-developed, larger, canonical versions. Phase 4 keeps installation, deletes core's `Installation/Steps/*`, `InstallationOrchestrator`, and `Http/Controllers/UnifiedInstallationController`. The 3 SaaS-only steps must become **registry/contract-gated** (they reference platform — see §5), not unconditionally loaded.

---

## 2. Per-symbol move-map (Phase 0 primary output)

Every `Aero\Core\*` symbol consumed by `auth`, `installation`, `platform`, grouped by its **target home** under the new graph. This table is what Phases 1–5 execute against.

### → `aero-auth` (V2 — identity moves out of core; `class_alias` bridge `Aero\Core\Models\User`)
| Symbol | Consumed by | Notes |
|--------|-------------|-------|
| `Models\User` | auth (~20), platform (~15) | **highest fan-out symbol in the repo.** Also referenced as the FQN string `'Aero\Core\Models\User'` in `model_has_roles.model_type` filters (platform `EnsureTenantIsSetup`, `RedirectIfNoAdmin`) — those string literals must be repointed/aliased or polymorphic role rows break. |
| `Models\UserDevice` | auth | |
| `Models\UserSession` | auth | |
| `Models\TenantInvitation` | auth | invitation identity |
| `Http\Requests\AcceptTeamInvitationRequest` | auth | |
| `Services\UserInvitationService` | auth | |
| `Notifications\InviteTeamMember` | auth | (or notifications) |
| `Services\Shared\Auth\DeviceAuthService` | auth | |

### → `aero-contracts` (V6, V9 — interfaces + base models)
| Symbol | Consumed by | Notes |
|--------|-------------|-------|
| `Models\CentralModel` | auth (1), platform (~13 Infra/Plan/Payment models) | V6 — canonical base in contracts; alias core/platform copies |
| `Encryption\EncryptedField` / `Casts\EncryptedField` | platform (~4) | **two paths for one concept** (`Encryption\` vs `Casts\`) — unify. Encryption interface → contracts, impl → infrastructure |
| `ValueObjects\RequestContext` | platform | |
| `Exceptions\LicenseException` | installation | → contracts or shared license-core (V7) |
| `Services\Notifications\PhoneVerificationService` | auth | interface → contracts (V9), impl stays mode-side |

### → `aero-kernel` (V1/V7/V8 — shared runtime; new package)
| Symbol | Consumed by | Notes |
|--------|-------------|-------|
| `Services\Audit\AuditEventType` | platform (~40 services), auth (~12) | **second-highest fan-out.** Pure enum/registry — clean kernel candidate |
| `Services\Audit\AuditService` / `Services\AuditService` | platform, auth | **two import paths for one service** (`Services\AuditService` vs `Services\Audit\AuditService`) — consolidate |
| `Support\TenantCache` | platform (~20), auth (~4) | heavy shared util |
| `Support\SafeRedirect` | auth (~8) | shared util |
| `Services\InstallationState` | platform (~3), installation (2) | install/runtime state |
| `Services\NavigationRegistry` | platform (2) | |
| `Services\ModuleRegistry` | platform | **V8 — move to kernel** |
| `Services\Module\ModuleDiscoveryService` | platform (~3), installation | **V8** |
| `Services\ModuleAccessService` / `RoleModuleAccessService` | platform | HRMAC-adjacent; confirm vs `aero-hrmac` before placing |
| `Models\Module` / `ModuleComponent` / `ModuleComponentAction` / `SubModule` | installation, platform | module-registry persistence — moves with V8 |
| `Services\LicenseValidationService` | installation | → shared license-core (V7, see §3) |
| `Traits\ParsesHostDomain` | platform (~5) | **also names platform `Domain` model internally — needs inversion (§5), not a clean move** |
| `Services\SystemSettingService` / `Models\SystemSetting` / `Models\CompanySetting` / `Http\Resources\SystemSettingResource` | auth, platform, installation (FQN string) | settings subsystem — confirm home (kernel vs core-domain) |
| `Http\Controllers\Controller` (base) | auth, platform | base controller → kernel/shared |
| `Http\Middleware\RedirectIfAuthenticated` / `ModuleAccessMiddleware` / `ModuleAccessMiddleware` | platform | shared middleware |
| `Http\Controllers\UserController` | platform (QUICK_START only — verify live use) | |

### Stays core-domain (consumer must stop naming it directly)
| Symbol | Consumed by | Notes |
|--------|-------------|-------|
| `Database\Seeders\RoleSeeder` / `RoleModuleAccessSeeder` | installation (FQN strings) | core domain seeders. Installation must resolve via **registry**, not hardcoded FQN (rule 12: no package names another's internals) |

### Anomaly to fix opportunistically
- `Aero\Core\Models\LandlordUser` is imported by `aero-platform/src/Policies/PlanPolicy.php`, **but the class is defined at `Aero\Platform\Models\LandlordUser`** (no such file in core). This is a stale/wrong import surviving only via autoload accident or an existing alias — confirm and correct during Phase 2/5.

---

## 3. License ruling (V7) — duplicated, currently IN SYNC

One algorithm, two homes, hand-synced by comment:

- **Validator (core):** `Services\License\LicenseValidator::verifyChecksum()` — `strtoupper(substr(md5($seg1.$seg2.$seg3 . $salt), 0, 2))`, salt `config('license.checksum_salt','aero-license-salt')`, format `XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX`.
- **Issuer (platform):** `Services\LicenseIssuer::generateKey()` — **byte-identical** checksum derivation; comment literally says *"matches LicenseValidator in aero-core"*.

**Ruling:** confirmed shared algorithm, no drift *yet* — but the only thing keeping them aligned is a comment. V7 extracts the checksum/format constants into the shared **license-core** (kernel); validator and issuer both consume it. Related surface to move/point: core `Services/License/{LicenseService,LicenseValidator,LicenseCache,DomainBinding}`, `Services/LicenseValidationService`, `Exceptions/LicenseException`; platform `Services/LicenseIssuer`, `Services/Enterprise/LicenseService`. Interface already exists: `aero-contracts/src/LicenseServiceInterface.php`.

---

## 4. Declared-graph violations (composer) — confirmed

Matches the standard's audit; no change:
- **V1** `platform → core` · **V2** `auth → core` · **V3** `installation → core`.

---

## 5. Hidden core → platform inventory (V4 — inversion targets)

Core has **zero** `use Aero\Platform\…` hard imports — all coupling is `class_exists()` + FQN strings (the leakiest kind). Full list to replace with a `TenancyProvider` contract + platform-registered extension points:

| Core file | Names (string) | Purpose |
|-----------|----------------|---------|
| `AeroCoreServiceProvider.php` (×3: L93, L681-683, L712) | `Platform\AeroPlatformServiceProvider`, `Platform\Http\Middleware\EnsureTenantIsActive` | SaaS-mode detection + middleware push |
| `Providers\ModuleRouteServiceProvider.php` L225 | `Platform\AeroPlatformServiceProvider` | mode detection |
| `Traits\AeroTenantable.php` L115 | `Platform\AeroPlatformServiceProvider` | mode detection |
| `Http\Middleware\EnsureTenantContext.php` L59 | `Platform\AeroPlatformServiceProvider` | mode detection |
| `Console\Commands\InstallCommand.php` L376 | `Platform\AeroPlatformServiceProvider` | install scope |
| `Traits\ParsesHostDomain.php` L150-156 | `Platform\Models\Domain` | domain resolution |
| `Http\Middleware\EnsureTenantIsSetup.php` L134-155 | `Platform\Http\Controllers\TenantOnboardingController`, `Platform\Models\PlatformSetting` | onboarding gate |
| `Services\Dashboard\AdminDashboardService.php` L551 | `Platform\Models\ErrorLog` | dashboard widget |
| `Services\PlatformErrorReporter.php` L257 | `Platform\Models\ErrorLog` | error reporting |
| `Installation\Steps\AdminUserStep.php` L180 | `Platform\Models\LandlordUser` | central admin (core copy — deleted in Phase 4) |
| `Installation\Steps\SeedingStep.php` L47-54 | `Platform\Database\Seeders\{PlatformDatabaseSeeder,ProductSeeder}` | seeding (core copy — deleted Phase 4) |
| `Http\Controllers\UnifiedInstallationController.php` L87,1193,1232,1308 | `Platform\AeroPlatformServiceProvider`, `Platform\Database\Seeders\PlatformDatabaseSeeder`, `Platform\Models\{LandlordUser,PlatformSetting}` | (core copy — deleted Phase 4) |

**Adjacent V9 string-coupling in core (interfaces → contracts):**
- `AdminDashboardService.php` L746 → `Notifications\Models\NotificationLog`
- `AeroCoreServiceProvider.php` L159-161 → `Notifications\Contracts\{MailContextResolver,SmsContextResolver}`
- `Http\Middleware\HandleInertiaRequests.php` L176 → `I18n\Services\TranslationService`

> Note: several of these guards live in the core install controller/steps that Phase 4 **deletes outright** — so a chunk of V4 dissolves for free once V5 lands. Sequence V5 (cut platform→core) to ride on Phase 4's deletions.

---

## 6. Sequencing implications surfaced by this audit

1. **Fix the oracle before bulk work** (gate decision above). The standalone phpunit config and the `support_tickets` double-registration are *pre-existing* breakage, not refactor fallout — they will mask regressions if left.
2. **`User` + `AuditEventType` + `TenantCache` are the three highest-fan-out symbols.** Phase 2 (User) and the kernel audit/cache move dominate edit volume; budget accordingly. `class_alias` bridges are mandatory for all three.
3. **Two import paths exist for two concepts** — consolidate while moving: `Services\AuditService` vs `Services\Audit\AuditService`; `Encryption\EncryptedField` vs `Casts\EncryptedField`.
4. **FQN string literals** (`'Aero\Core\Models\User'` in `model_has_roles`, seeder class strings) are invisible to any dep tool and to find/replace by namespace — enumerate them explicitly (done above) so Phase 2/4 repoints them.
5. **Phase 4 deletions absorb much of V4** — order V5 after V4 to avoid editing soon-deleted core install code.

**Risk of Phase 0 itself:** none (read-only, nothing written but this document).
