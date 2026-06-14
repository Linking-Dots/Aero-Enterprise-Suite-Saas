# UAT End-to-End Test Suite — Design Spec

**Date:** 2026-06-01
**System:** AEOS365 (Laravel 12 + React 18 / Inertia v2), dual-mode SaaS + Standalone, local Laragon.
**Goal:** Exhaustive, repeatable end-to-end validation of the **whole system** — auth, tenant lifecycle/installation, platform admin, billing, and the **HRM product** (every submodule, CRUD + key workflow) — in **both** deployment modes.

---

## 1. Tooling & architecture

| Layer | Tool | Role |
|---|---|---|
| Browser E2E | **Playwright** (`@playwright/test`) | Drives the real Inertia/React UI as a user. |
| Under-the-UI | **PHPUnit** (existing + shared `PackageTestCase`) | API/policy/model coverage; E2E does NOT re-test these. |
| Data | **Laravel `UatSeeder` + factories** | Deterministic baseline dataset. |

- **Location:** top-level `e2e/` Playwright project in the **monorepo** (builds on the existing `aero-platform/tests/e2e` precedent). Committed; wired into `.github/workflows/`.
- **Two Playwright `projects`:** `saas` (baseURL `http://{tenant}.aeos365.test`) and `standalone` (baseURL `http://aeos365-standalone.test`).
- **Page Object Model (POM):** shared HRM flows authored once (`e2e/pages/hrm/*`), reused across both mode-projects. Mode-specific entry (install vs register) lives in mode-specific specs.
- **Tags:** `@saas`, `@standalone`, `@billing` (skips without Stripe keys), `@destructive` (run last).

```
e2e/
  playwright.config.ts        # two projects (saas, standalone), globalSetup, storageState
  global-setup.ts             # migrate:fresh --seed + provision test tenant + auth states
  fixtures/                   # storageState per role, test data ids
  pages/                      # POM: auth/, hrm/, platform/, billing/
  specs/
    p1-lifecycle-auth/
    p2-hrm-core/
    p3-hrm-remainder/
    p4-platform-billing/
```

---

## 2. Test environment & data strategy

- **Dedicated UAT databases** (never dev): SaaS `aeos365_uat` (central) + provisioned tenant DB(s); Standalone `aeos365_standalone_uat`. Selected via `.env.uat` loaded by global-setup — a UAT run cannot clobber working data.
- **`globalSetup` (once per run):**
  - *Standalone:* `migrate:fresh --seed=UatSeeder`, write `storage/app/aeos.mode=standalone`, mark installed.
  - *SaaS:* `migrate:fresh` central + seed landlord/plans/products → create known test tenant → run `ProvisionTenant` synchronously → seed tenant DB.
  - Captures **`storageState` per role** (programmatic login) for reuse.
- **Prerequisites (one-time, documented in `e2e/README.md`):** Laragon wildcard `*.aeos365.test` + `aeos365-standalone.test` vhosts; PHP 8.3; Node + `npx playwright install chromium`; optional Stripe test keys.
- **`UatSeeder` (idempotent):** roles (Super Administrator, HR Manager, Employee, + landlord admin); **one user per role** (drives HRMAC allow/deny); departments, designations, leave types, holidays, salary structure + pay components, ~10 employees; a plan + active product subscription (HRM unlocked).
- **Isolation:** `migrate:fresh --seed` resets once per run; CRUD specs create their **own** records and are order-independent; **`@destructive`** specs (suspend / GDPR-forget / standalone re-install) run **last** or against a throwaway tenant.
- **Stripe:** test mode (test cards). Missing keys → `@billing` specs **skip** (graceful).
- **Speed:** role `storageState` reuse (no UI re-login per test); parallel workers within a mode-project; modes run sequentially (separate DBs).

---

## 3. Coverage matrix (enumerated cases)

> Notation: **[mode]** S=SaaS, A=Standalone, B=both. **[role]** the seeded user used. Each case = steps → expected result.

### P1 — Lifecycle & Auth

**1.1 Standalone installer [A]**
1. Fresh DB → `/install` wizard loads → DB-connection step succeeds.
2. Admin-user step creates the first admin.
3. License step (LICENSE_BYPASS or test key) → valid.
4. Module-discovery + migration + finalize → app marked installed.
5. Dirty-schema guard: re-run installer on a DB with tables but no migrations → refused (unless FORCE_CLEAN_INSTALL).
6. Post-install `/login` reachable; `/install*` 404s after completion.

**1.2 SaaS tenant lifecycle [S]**
1. Registration: valid subdomain accepted; **reserved name rejected**; subdomain length rules; duplicate email/subdomain rejected (uniform anti-enumeration message); BYOC step optional.
2. Provisioning: job runs → tenant DB created, modules synced, roles seeded, status=active.
3. Subdomain login works for the seeded admin.
4. Suspend tenant → web shows suspended page (403), **API returns 403 JSON**.
5. GDPR-forget → tenant row hard-deleted + DB dropped (verify via admin list).

**1.3 Auth & sessions [B]**
1. Login valid → dashboard; invalid → error (no enumeration).
2. Logout → session cleared.
3. Password reset: request (uniform response), reset link, rate-limit after N attempts.
4. MFA: setup (QR/secret), challenge on next login, backup-code recovery, trusted device.
5. Device binding: login requires device_id; suspended device blocked.
6. Impersonation [S, landlord]: start → banner shown → exit; tampered `redirect_url` blocked.
7. Session expiry → re-auth prompt.

**1.4 HRMAC allow/deny [B]**
1. HR Manager **can** reach + act on leave approval, payroll, employee mgmt.
2. Employee **cannot** reach admin HRM pages → 403/redirect; self-service only.
3. Denials recorded (audit-log viewer shows the denial).
4. Disabled module (`is_active=false`) denies access even to a granted role.

### P2 — HRM core (deep, [B])

**2.1 Employees** — create/edit/view/delete; profile; documents upload; **encrypted national_id/tax_id stored encrypted (verify masked in UI)**; profile image upload lands in **per-tenant storage**; employee self-service profile.
**2.2 Departments / Designations** — CRUD + appear in employee dropdowns.
**2.3 Attendance** — clock-in then clock-out (idempotent: double clock-in no dupe); daily attendance list; timesheet; overtime request→approve; shift listing.
**2.4 Leave** — leave type CRUD; apply → HR approve / reject; balance decremented on approval; accrual; calendar shows approved; bulk action.
**2.5 Payroll** — pay component + salary structure CRUD; **payroll run → payslip generated**; **finalized payslip is immutable** (edit/delete blocked); payslip view shows bank **last-4 only**.

### P3 — HRM remainder (CRUD + key workflow each, [B])

**3.1 Recruitment** — jobs CRUD/publish/close; applications: apply → move stage → reject; **interviews schedule/update (validates de-duped flat routes)**; offers; onboarding.
**3.2 Training** — courses, categories, sessions, **enrollments (validates de-duped flat enrollment routes)**, feedback, safety training.
**3.3 Performance** — reviews, review cycles, goals, competencies, calibration, **PIP create/list/show (validates the consolidated PIP routes)**, 360 feedback, skill matrix.
**3.4 Disciplinary** — cases (create→investigate→close), action-types CRUD, warnings, **grievances (create→investigate→resolve, validates de-duped routes)**.
**3.5 Safety** — incidents (report→resolve), inspections, safety training.
**3.6 Assets** — inventory CRUD (validates canonical Hrm* asset routes), categories, allocations (allocate→return).
**3.7 Expenses** — claims (submit→approve), categories.
**3.8 Benefits** — catalog, enrollment, open-enrollment period.
**3.9 Succession** — talent pools, candidates, career-paths, mobility.
**3.10 Misc** — events + registration, announcements, wellbeing dashboard (SQL-aggregated), workforce planning, compensation planning, exit interviews.
**3.11 Self-service portal** — my dashboard, my leaves (apply), my payslips (view), my profile (edit), my training.

### P4 — Platform admin + billing [S]

**4.1 Tenant management** — list, create, show, suspend, archive, GDPR-forget, bulk operations.
**4.2 Catalog** — plans, products, product subscriptions, modules, pricing.
**4.3 Billing `@billing`** — invoices (view/PDF), payment methods, renewal run, dunning, refund, credit note (Stripe test cards).
**4.4 Observability** — platform + product dashboards, **audit-log viewer**, **access-log viewer**, settings/infrastructure, feature flags, maintenance windows.
**4.5 Landlord** — landlord users & roles CRUD.

### P5 — CI integration + exit criteria
- Suite wired into `.github/workflows/` (Playwright job per mode-project; PHPUnit job).
- **Exit criteria:** P1–P4 specs green in both mode-projects on Laragon; CI green; a generated HTML report; documented manual sign-off for anything intentionally left manual (e.g., real-payment edge cases).

---

## 4. Phasing & effort

| Phase | Content | Notes |
|---|---|---|
| P0 | env config, `UatSeeder`, `global-setup`, POM scaffolding, auth `storageState` | Foundation — nothing runs without it |
| P1 | Lifecycle & auth + HRMAC | Entry paths + access model |
| P2 | HRM core deep | HR backbone |
| P3 | HRM remainder exhaustive | The bulk |
| P4 | Platform admin + billing | SaaS surfaces |
| P5 | CI + exit sign-off | |

Each phase is independently runnable and adds green specs. P0 must land first; P1–P4 can parallelize after.

## 5. Non-goals
- Not re-testing what PHPUnit already covers (unit/policy/model) via the browser.
- Not load/performance testing (separate effort).
- Not testing removed/non-HRM product modules (finance/crm/etc. are out of the HRM-only product).

## 6. Risks & mitigations
- **Flakiness** → POM + explicit waits + `storageState`; destructive specs isolated.
- **Laragon wildcard DNS** for SaaS subdomains → documented prereq + a setup check in global-setup.
- **Provisioning in setup** (queue jobs) → run synchronously in global-setup.
- **Stripe dependency** → test mode + graceful `@billing` skip.
- **Exhaustive scope is large** → phased; P0–P2 deliver the highest-value coverage first.
</content>
