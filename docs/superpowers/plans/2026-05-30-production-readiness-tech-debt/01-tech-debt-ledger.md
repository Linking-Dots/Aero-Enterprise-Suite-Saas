# Production-Readiness Audit — Axis D: Tech Debt Ledger

> **For agentic workers:** this is a TRACKING document + paydown plan. Each item has a category, source, trigger, and priority. Execute high-priority items per the cadence the operator sets.

**Source:** Axis D walk-through (4 audit questions), 2026-05-30
**Goal:** ONE consolidated, prioritized ledger of every deferred/residual item so "deferred until demand" never becomes "lost."
**Decisions:** D-ledger-1 schedule ratchet paydown · D-ledger-2 convert structural→behavior tests · D-ledger-3 complete GDPR HTTP tests · D-ledger-4 consolidate into this ledger (all "Recommended").

---

## Priority legend

- **P0** — production-correctness / legal; do next.
- **P1** — should land this quarter; real risk or cost.
- **P2** — mechanical / hygiene; subagent-friendly; schedule a sprint.
- **P3** — accept-and-track; do on trigger (customer demand, scale threshold).

---

## Section 0 — Cross-axis P0/P1 (from Axes A/B/C — pointers, not duplicated)

| ID | Item | Plan | Priority |
|---|---|---|---|
| A5 | Runtime bootstrapper override → cross-tenant **file leak** + fail-closed queue reverted | `../2026-05-30-production-readiness-tenancy-isolation/01-…` | **P0** |
| B1+B2 | Standalone has **zero audit logging** (routes to nonexistent `central`) | `../2026-05-30-production-readiness-saas-standalone-parity/01-…` | **P0** |
| A9/A4/A11 | Inconsistent DROP-DB safety + illusory teardown transactions | tenancy-isolation plan | P1 |
| A10 | Tenant-context guard fails OPEN in SaaS | tenancy-isolation plan | P1 |
| B3 | CentralModel subclasses crash in standalone (social auth) | parity plan | P1 |
| C1/C6/C7 | PHP-aggregation dashboards · per-request HRMAC cost · serial stats | scalability plan | P1 |

These are tracked in their own axis plans; listed here so the ledger is the single front door.

---

## Section 1 — Ratchet residuals (D-ledger-1: scheduled paydown to zero)

Ratchets block regression but don't pay down. Decision: keep as guards AND drive to zero (lower BUDGET per commit).

| Ratchet | File | Current budget | Target | Effort |
|---|---|---|---|---|
| Facade `Cache::` | `aeos365/tests/Feature/Wiring/FacadeDisciplineTest.php` | 4 | 0 | migrate to `TenantCache` (P2) |
| Facade `Storage::disk('local')` | same | 1 | 0 | migrate to tenant disk (P2) |
| Facade `Session::` | same | 0 | 0 | ✅ already clean |
| Inline `style={}` | `aeos365/tests/Feature/Wiring/UiInlineStyleDisciplineTest.php` | 165 | 0 | ~155 actual → utility classes (P2, subagent per `Pages/{module}/`) |

- [ ] **Task D1a:** Migrate the 4 `Cache::` offenders → `TenantCache`; lower `BUDGET_CACHE` to 0 in the same commit.
- [ ] **Task D1b:** Migrate the 1 `Storage::disk('local')` offender → tenant disk; lower `BUDGET_STORAGE_LOCAL` to 0. (Composes with Axis A A5 — filesystem tenancy.)
- [ ] **Task D1c:** Inline-style paydown sprint — Haiku subagents, one `packages/aero-ui/resources/js/Pages/{module}/` per agent, lowering `VIOLATION_BUDGET` per merge until 0.
- [ ] Commit per ratchet: `chore(debt): pay down <ratchet> to 0 and lock budget`.

---

## Section 2 — Structural tests → behavior tests (D-ledger-2)

10 files assert on file contents / method existence rather than runtime behavior. Convert the behavior-able ones; keep structural only where behavior testing is impractical.

| File | Convert to | Priority |
|---|---|---|
| `aero-hrm/tests/Unit/Api/ApiV1ControllerStructureTest.php` | HTTP feature tests for `/api/hrm/{employees,leave-applications,attendance,payslips,departments,designations}` — assert response shape, sanctum auth, HRMAC, `boundedPerPage` | **P1** |
| `aero-hrm/tests/Unit/Api/RestApiSurfaceTest.php` | fold into the above HTTP suite | P1 |
| `aero-auth/tests/Unit/Wiring/IdentityOwnershipTest.php` | keep (architectural pin — ownership of `sso_identity` namespace) | P3 keep |
| `aero-auth/tests/Unit/Http/ImpersonationOpenRedirectTest.php` | already behavior-ish; verify it exercises the route | P2 verify |
| `aero-core/tests/Unit/Http/FileManagerDiskWhitelistTest.php` | behavior test hitting the controller with `s3`/bad disk → 422 | P2 |
| `aero-core/tests/Unit/Http/HelpSupportControllerEloquentTest.php` | behavior test (model-backed) | P2 |
| `aero-core/tests/Unit/Http/CoreUserAuditTrailTest.php` | assert an audit row actually persists (ties to Axis B B1) | P1 |
| others (`LoginRateLimitTest`, `PasswordResetLinkControllerTest`, `ImpersonationRoleLookupTest`) | verify behavior coverage; keep | P3 |

- [ ] **Task D2 (P1 headline):** Build the HRM v1 API HTTP integration suite (Audit Task 9 left ONLY structural pins). One test class per resource; cover auth, HRMAC denial, own-scope vs admin-scope (payslips), pagination bounds, response envelope `{data,meta,links}`.
- [ ] Commit: `test(hrm): real HTTP integration suite for v1 API (replaces structural pins)`.

---

## Section 3 — Known-red / incomplete tests (D-ledger-3)

| Item | State | Priority |
|---|---|---|
| `aero-platform/tests/Feature/Admin/TenantForgetTest.php` | 4 of 7 HTTP cases fail on guard-config setup (Audit Task 7) | **P0** (legal endpoint) |
| `markTestSkipped` usages | only 1 (`aero-auth/.../IdentityOwnershipTest.php`) — verify it's intentional | P3 |

- [ ] **Task D3:** Fix the `TenantForgetTest` guard-config harness (landlord guard + HRMAC alias resolution under test) and complete all 7 cases: unauthenticated → 401/redirect; authed-without-HRMAC → 403; missing/short `reason` → 422; missing `confirm` → 422; happy path → 200 + audit row + DB dropped (mock the drop). GDPR authz/validation must be green.
- [ ] Commit: `test(platform): complete TenantForget HTTP authz + validation suite (D3)`.

---

## Section 4 — Deferred features ledger (D-ledger-4)

Consolidated from EXECUTION_SUMMARY "Honest gaps", per-package READMEs, and the audit follow-up. Each carries a trigger.

| ID | Item | Source | Trigger | Priority |
|---|---|---|---|---|
| TD-1 | **PayrollFinance bridge** — HRM payroll → finance journal entries | Audit D33 / §12.5 | customer demand | **P3 (stays deferred)** |
| TD-2 | 10 missing core model policies | 02-aero-core README | before exposing those models via API | P2 |
| TD-3 | Model factories for ~10 models | 02-aero-core README | when writing their feature tests | P2 |
| TD-4 | `EncryptedField` unit tests | 02-aero-core README | P2 (security-adjacent) | P2 |
| TD-5 | Password policy service (previous-N + expiration) | 05-aero-auth README | enterprise customer requirement | P3 |
| TD-6 | Session policy service (concurrent limit + idle timeout) | 05-aero-auth README | enterprise requirement | P3 |
| TD-7 | `Subscription.tenant_id` column drop | Audit §7.2 / 03 README | after staging backfill verification | P2 |
| TD-8 | HRMAC path normalization across ~615 routes | 03-aero-platform README | P2 (consistency) | P2 |
| TD-9 | Controller drift consolidation (3 PlanControllers, 2 Onboarding, 2 BulkTenant) | 03 README | P2 | P2 |
| TD-10 | `HrmacAuditLog` viewer page | 04-aero-hrmac README | compliance UI demand | P3 |
| TD-11 | Step-idempotency for remaining installer steps | Audit §9.5 / 09 README | P3 | P3 |
| TD-12 | Offline license activation hardening | 09 README (NOTE: grace IS implemented — verify + close) | P3 | P3 |
| TD-13 | Vitest + Playwright frontend test infra | 06-aero-ui README | P2 (enables UI regression safety) | P2 |
| TD-14 | HRMAC frontend adoption push (sensitive pages) | 06 README (52% adoption) | P2 | P2 |

- [ ] **Task D4a:** Triage the 73+ `TODO/FIXME/HACK` (40 files) into: (a) ledger items (add row), (b) actionable-now (fix in place), (c) stale-noise (delete). Convert survivors to `@todo TD-<id>` referencing this ledger.
- [ ] **Task D4b:** Keep this file as the living ledger; update on each deferral. Add a CI note linking new "deferred" decisions here.
- [ ] Commit: `docs(debt): consolidated tech-debt ledger + TODO triage (D4)`.

---

## Execution order (suggested)

1. **P0:** Cross-axis A5, B1/B2 (their plans) + **D3** (GDPR HTTP tests) + **D2 HRM API suite**.
2. **P1:** CoreUserAuditTrail behavior test (with B1), structural→behavior conversions.
3. **P2 sprint (subagent-friendly):** ratchet paydown (D1a/b/c), factories/policies, frontend test infra, Vitest/Playwright.
4. **P3:** on-trigger only — PayrollFinanceBridge stays deferred (D33), password/session policy services, offline-license verify.

---

## Self-Review

- ✅ All 4 "Recommended" decisions captured as tasks (D1 ratchets, D2 structural→behavior, D3 GDPR tests, D4 ledger).
- ✅ Cross-axis P0/P1 surfaced at the top so this is the single front door; not duplicated (pointers to their plans).
- ✅ Every deferred item carries a trigger + priority; PayrollFinanceBridge explicitly stays P3-deferred per D33.
- ✅ Quantified: facade budgets (4/1/0), inline-style (165/~155), 1 skipped test, 10 structural tests, 73+ TODOs.
- ✅ Living-document intent stated so future deferrals land here, not in scattered READMEs.
