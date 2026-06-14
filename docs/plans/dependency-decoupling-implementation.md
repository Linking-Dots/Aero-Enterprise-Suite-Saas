# Dependency Decoupling — Implementation Plan

**Goal:** Execute the [dependency-architecture](../standards/dependency-architecture.md) worklist (V1–V10) without ever breaking the build, in either mode (SaaS + standalone).

**Constraints assumed:** ~5h working sessions · token ceilings per session · sub-agent delegation with model selection (opus = architecture/risk, sonnet = scan/mechanical edits, haiku = bulk verification).

**Strategy:** Bottom-up (`contracts → auth → kernel → installation → cut platform→core → invert core→platform`). Each phase ends green (tests pass, both modes install). `class_alias()` bridges keep old namespaces resolving until dependents are repointed. Lead Architect plans + verifies each chunk; Backend Engineer executes in an isolated worktree; QC gates. This keeps the orchestrator context lean.

---

## Phase 0 — Pre-flight audit + safety net  *(no code moves)*

| Item | Detail |
|------|--------|
| Symbol-level audit | Exact list of every `Aero\Core\` symbol used by `auth`, `installation`, `platform` (use + class_exists + FQN strings) |
| Wizard diff | Diff core's 11 Steps vs installation's 14 Steps — which is canonical, what diverged |
| License diff | Confirm core validator vs platform issuer share one algorithm (or already drift) |
| Test baseline | Run full suite both modes; snapshot green; record install E2E (saas + standalone) as the regression oracle |

**Delegate:** Audit Prompt Generator (sonnet) for scans → Lead Architect (opus) for the canonical-copy / divergence rulings.
**Output:** a per-symbol move-map that all later phases consume. **Risk:** none (read-only).

---

## Phase 1 — Contracts foundation  *(V6, V9, + TenancyProvider interface)*  ✅ DONE 2026-06-09 (9381588b8)

- Canonicalize `CentralModel`/`TenantModel` in `aero-contracts`; add `class_alias` from `core`/`platform` copies.
- Move Mail/SMS/`TranslationDriverInterface` **interfaces** into `contracts`.
- Add `TenancyProvider` contract (interface only — no implementation yet).

**Delegate:** Backend Engineer (opus — base-model semantics are subtle). QC (sonnet).
**Gate:** suite green; both copies still resolve via alias. **Risk:** low.

**Outcome:** Mostly pre-done in-flight. `CentralModel`/`TenantModel` already canonical in `aero-contracts/src/Models/` with core+platform BC shims (subclasses, not `class_alias` — equivalent BC). V9 interfaces (`MailContextResolverInterface`, `SmsContextResolverInterface`, `TranslationDriverInterface`, …) already in `contracts`, implemented by core's `Core*ContextResolver`. **Net new:** (1) platform `CentralModel` shim was extending `\Aero\Core\Models\CentralModel` — a platform→core edge — repointed to `\Aero\Contracts\Models\CentralModel` (last base-model sibling edge gone). (2) Added `Aero\Contracts\TenancyProvider` (inversion seam; interface only; implemented in Phase 5). Verified 0 regression. Existing `DomainContextContract` + `TenantScopeInterface` are query-only (no overlap).

---

## Phase 2 — auth becomes pure  *(V2 — cut `auth → core`)*

- Move `User` + identity models from `core` into `aero-auth`.
- Replace auth's core references with `contracts` interfaces.
- Remove `aero/core` from auth's composer; auth → `contracts` only.
- `class_alias('Aero\Auth\Models\User', 'Aero\Core\Models\User')` bridge so the broad fan-out of User references keeps resolving.

**Delegate:** Backend Engineer (opus). QC (opus — User fan-out touches auth/roles/HRMAC; needs careful regression).
**Gate:** login + role checks pass both modes. **Risk:** HIGH — `User` is referenced widely (every `model_has_roles`, controllers, HRMAC).

---

## Phase 3 — kernel extraction  *(V1 partial, V7, V8)*

- Create `aero-kernel` (pure: requires contracts/infrastructure only).
- Move `ModuleRegistry`, the migrator-context logic, and the shared **license signing core** into kernel.
- Point core's validator + platform's issuer at the shared license core.

**Delegate:** Lead Architect (opus) designs the package boundary + service-provider split; Backend Engineer (opus) moves code; QC (sonnet).
**Gate:** module discovery + license validate/issue pass. **Risk:** medium.

---

## Phase 4 — installation neutralized + deduped  *(V3, V5, V10)*

- Single canonical wizard in `aero-installation`; **delete core's copy** (merge the 14 vs 11 Steps).
- Implement the 3-tag (`central|tenant|shared`) context system + registry-driven migration selection.
- Single `shared`-tagged `installation_progress`; remove duplicates.
- installation → `contracts` + `kernel` only (drop core).

**Delegate:** Lead Architect (opus) for the context-tag design; Backend Engineer (opus) executes; QC (opus — this is the migration runner).
**Gate:** **full E2E install in BOTH modes**, plus tenant-provisioning runs the correct set per DB. **Risk:** HIGHEST — directly governs which migrations hit which database.

---

## Phase 5 — cut sibling edges  *(V1 final, V4)*

- Platform implements `TenancyProvider`; registers itself into core's extension points at boot.
- Remove `aero/core` from platform's composer.
- Delete every `class_exists('Aero\Platform\…')` guard + hardcoded FQN string from core.

**Delegate:** Lead Architect (opus) + Backend Engineer (opus). QC (opus).
**Gate:** core boots with platform **absent** (standalone) and **present** (saas) purely via the contract. **Risk:** HIGH.

---

## Phase 6 — Enforcement + regression lock

- Add `deptrac.yaml` encoding the layers; wire into CI so any new violation fails the build.
- Remove the temporary `class_alias` bridges; repoint stragglers.
- Full regression both modes.

**Delegate:** QC (opus) authors deptrac + runs regression; Backend Engineer (sonnet) clears alias stragglers.
**Gate:** deptrac green, suite green, both installs clean. **Risk:** low (net) — surfaces anything missed.

---

## Delegation & model map

| Phase | Lead | Executor | Verifier | Dominant model |
|-------|------|----------|----------|----------------|
| 0 | Lead Architect | Audit Prompt Gen | — | sonnet |
| 1 | — | Backend Eng | QC | opus / sonnet |
| 2 | — | Backend Eng | QC | **opus** |
| 3 | Lead Architect | Backend Eng | QC | opus |
| 4 | Lead Architect | Backend Eng | QC | **opus** |
| 5 | Lead Architect | Backend Eng | QC | **opus** |
| 6 | — | Backend Eng | QC | opus / sonnet |

**Principle:** opus on the four risk phases (2, 4, 5, and license/registry semantics in 3); sonnet for scans and mechanical edits; haiku only for bulk file verification. Orchestrator stays lean by delegating each chunk to a worktree-isolated sub-agent and reviewing diffs, not doing edits in the main thread.

---

## Deferred findings (2026-06-12 — surfaced during the placement scan, NOT regressions)

### TICKET D-1 — platform module-access tangle (pre-existing, needs dedicated fix)
- `packages/aero-platform/src/Http/Middleware/CheckModuleAccess.php` is REGISTERED (router aliases
  `module` / `check.module` in AeroPlatformServiceProvider + Http/Kernel) but imports the
  **non-existent** `Aero\Platform\Services\Shared\Module\ModuleAccessService` → the middleware is
  un-constructable → **any platform route using it 500s**. Latent because those routes evidently
  aren't exercised in dev.
- `packages/aero-platform/src/Policies/Concerns/ChecksModuleAccess.php` is an **unused duplicate**
  of core's healthy `Aero\Core\Policies\Concerns\ChecksModuleAccess` (which correctly uses
  `Aero\Core\Services\ModuleAccessService`); platform's copy has the same broken import.
- hrm policies (`CompetencyPolicy`, `BenefitPolicy`, `SkillPolicy`, `OnboardingPolicy`,
  `OffboardingPolicy`) write `use ChecksModuleAccess` with **no visible import** of the trait →
  suspect (would fatal if the policy class is loaded) — needs verification.
- Semantics mismatch: the only real platform service `Aero\Platform\Services\Module\ModuleAccessService`
  type-hints `LandlordUser`; the broken consumers type-hint core `User`.
- **Recommended fix (own unit):** delete platform's unused dup trait; repoint the live middleware to
  the real platform service and reconcile the `User`/`LandlordUser` typing (or split tenant vs
  landlord paths); audit the hrm policies' trait resolution. Do NOT guess — verify each.

### TICKET D-2 — Taggable trait (auth→core), blocked on Tag-model placement
- `Aero\Core\Traits\Taggable` is used only by `aero-auth\Models\User`, but it depends on
  `Aero\Core\Models\AuditLog` + `Aero\Core\Models\Tag` (core models), so it cannot move to kernel
  without dragging core models. Resolving auth's last core coupling requires deciding whether `Tag`
  (and tagging) is a sharable concern (→ move Tag + Taggable to a sharable) or stays core (→ auth
  shouldn't use Taggable). Deferred pending that decision. (Searchable, the clean half, already
  moved to kernel — commit eded4b746.)
