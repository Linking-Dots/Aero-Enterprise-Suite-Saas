# Foundation 10/10 Push — Master Index

**Author:** Lead Architect audit, 2026-05-28
**Scope:** Bring all 16 foundation + cross-cutting packages, plus host wiring, from current state to 10/10 production-ready. **HRM is deferred** — comes after foundation is solid.
**Total effort estimate:** ~64–95 engineer-days (varies by stub package decisions)

---

## Phase 0 — Host Wiring (do FIRST)

| # | Plan | Score Δ | Effort |
|---|---|---|---|
| 00 | [Wiring blockers](00-wiring-blockers.md) | 4 → 10 | 3-5d |

**Closes:** dev-shaped `.env`, `QUEUE_CONNECTION=sync`, disabled cache/filesystem tenancy bootstrappers, dual tenant middlewares, no Horizon/Sentry, missing `tenants.status` index, unbounded pagination, no CI guards.

---

## Tier 1 — The Spine (highest priority)

| # | Plan | Score Δ | Effort |
|---|---|---|---|
| 01 | [aero-contracts](01-aero-contracts.md) | 8 → 10 | 1-2d |
| 02 | [aero-core](02-aero-core.md) | 6.5 → 10 | 8-12d |
| 03 | [aero-platform](03-aero-platform.md) | 5.5 → 10 | 12-18d |
| 04 | [aero-hrmac](04-aero-hrmac.md) | 9 → 10 | 2-3d |

**Production-blocker findings:**
- `PlatformAnalyticsService::tenantAnalytics()` queries non-existent `tenants.plan_id` column — **broken in production right now** (plan 03 Task 1)
- `ProcessSubscriptionRenewalsJob` + `RetryFailedPaymentsJob` query wrong column (polymorphic mismatch) + Stripe retry is a `// TODO` stub — **tenants in `past_due` never auto-recover** (plan 03 Tasks 2, 3)
- `AdminDashboardService` cross-tenant cache leak — **every tenant sees the same SaaS dashboard stats** (plan 02 Task 1)
- `HelpSupportController` queries non-existent tables (`support_tickets`, `kb_articles`, `feedback`) — runtime crash (plan 02 Task 5)
- Reserved subdomain list missing `mail`, `smtp`, `static`, `ws` — tenant can hijack platform infra (plan 03 Task 6)
- BYOC credentials leak across queue workers — config not restored in `finally` (plan 03 Task 11)
- `rollbackDatabase` DROP regex too loose — could drop production DB on collision (plan 03 Task 12)
- HRMAC denial events not persisted to `HrmacAuditLog` table (table exists, unused) (plan 04 Task 1)
- `is_active` module flag is decorative — disabling a module via DB does not deny access (plan 04 Task 3)
- Duplicate `create_audit_logs_table` migrations (plan 02 Task 4)

---

## Tier 2 — User Touch Surface

| # | Plan | Score Δ | Effort |
|---|---|---|---|
| 05 | [aero-auth](05-aero-auth.md) | 6 → 10 | 5-7d |
| 06 | [aero-ui](06-aero-ui.md) | 6.5 → 10 | 6-9d |

**Critical findings:**
- `PasswordResetLinkController` has **no rate limit + account enumeration vulnerability** (plan 05 Task 1)
- Login rate limit is per-IP only — attacker probes many emails from one IP (plan 05 Task 2)
- Impersonation `redirect_url` not validated → open redirect (plan 05 Task 3)
- 3 fragmented audit channels (`AuditService` + `authentication_events` + Spatie `activity()` + `Log::channel('auth')`) (plan 05 Task 5)
- **346 inline `style={}`** violations in aero-ui (CLAUDE.md rule) (plan 06 Task 1-2)
- Zero tests across 422 JSX files (plan 06 Tasks 4-7)
- **Identity ownership resolved:** SAML/OIDC/Passkeys/etc. declared in aero-core but implemented in aero-auth → moved (plan 05 Task 6, closes aero-core Task 14)

---

## Tier 3 — Foundation Completeness

| # | Plan | Score Δ | Effort |
|---|---|---|---|
| 07 | [aero-i18n](07-aero-i18n.md) | 7 → 10 | 3-4d |
| 08 | [aero-notifications](08-aero-notifications.md) | 7 → 10 | 5-7d |
| 09 | [aero-installation](09-aero-installation.md) | 7 → 10 | 3-4d |

**Critical findings:**
- aero-notifications declares **zero submodules** despite implementing Email/SMS/Push/InApp/Suppression/Bounce/Deliverability — **resolves aero-core email_engine ownership** (plan 08 Task 1, closes aero-core Task 12)
- Notification idempotency missing — Horizon retries can double-send (plan 08 Task 2)
- aero-i18n migrations create `languages`/`translations` but config declares `tenant_translations`/`platform_translations` (plan 07 Task 1)
- No translation driver fallback — single network failure = 500 error (plan 07 Task 2)
- Installer has no per-step idempotency — re-running partial install can corrupt state (plan 09 Task 2)
- MigrationStep doesn't detect dirty schema → data-loss risk (plan 09 Task 3)
- No `installed.sentinel` — installer routes reachable after install (plan 09 Task 5)

---

## Cross-Cutting — Infrastructure Modules

| # | Plan | Current | Effort |
|---|---|---|---|
| 10 | [aero-custom-fields](10-aero-custom-fields.md) | 5 → 10 | 4-6d |
| 11 | [aero-forms](11-aero-forms.md) | 6 → 10 | 4-5d |
| 12 | [aero-workflow](12-aero-workflow.md) | 7 → 10 | 6-8d |
| 13 | [aero-automation](13-aero-automation.md) | **STUB** | 0.5d (remove) OR 8-12d (build) |
| 14 | [aero-booking](14-aero-booking.md) | **STUB** | 0.5d (remove) OR 8-12d (build) |
| 15 | [aero-time-tracking](15-aero-time-tracking.md) | **STUB** | 0.5d (remove) OR 6-9d (build) |
| 16 | [aero-mobile](16-aero-mobile.md) | **STUB** | 0.5d (remove) OR 6-9d (build) |

**Findings:**
- 4 packages are **scaffold stubs** (2 files each) — each plan has Branch A (remove) + Branch B (implement). Recommendation: remove unless on Q3 roadmap.
- custom-fields: declares 4 components, ships 1 controller — 75% of surface missing
- forms: declares `templates`, ships zero template backend; description claims PDF gen — no service
- workflow: declares `escalate` action but no SLA monitor; **boundary violation**: workflow modifies `leaves` table (HRM)

---

## Open Product Decisions

These decisions block specific tasks across multiple plans. Decide before implementation begins:

| # | Decision | Affects | Plan(s) | Recommendation |
|---|---|---|---|---|
| D1 | `email_engine` ownership: aero-core / aero-notifications / removed | aero-core Task 12, aero-notifications Task 1 | 02, 08 | **aero-notifications** (resolved in plan 08) |
| D2 | Identity components (SAML, OIDC, Passkeys, etc.) ownership: aero-core / aero-auth | aero-core Task 14, aero-auth Task 6 | 02, 05 | **aero-auth** (resolved in plan 05) |
| D3 | aero-automation: implement or remove | 13 | 13 | **Remove** unless Q3+ priority |
| D4 | aero-booking: implement or remove | 14 | 14 | **Remove** unless near-term feature |
| D5 | aero-time-tracking: implement or remove | 15 | 15 | **Remove** if HRM Attendance covers it |
| D6 | aero-mobile: implement or remove | 16 | 16 | **Remove** unless PWA is Q3+ priority |
| D7 | `aero-platform` controller drift (3 PlanControllers, 2 OnboardingControllers) | 03 Task 15 | 03 | Pick newer/better-tested per pair |
| D8 | `Subscription.tenant_id` removal — needs staging backfill verification first | 03 Task 8 | 03 | Backfill on staging clone, then prod |

---

## Recommended Execution Order

1. **Sprint 1 (week 1):** Phase 0 wiring (plan 00) + aero-hrmac (plan 04) + aero-contracts (plan 01)
   - Establishes safe runtime, closes HRMAC observability gaps, hardens the contract layer
2. **Sprint 2 (weeks 2-3):** aero-core critical tasks (plan 02 Tasks 1, 2, 3, 4, 5, 8) + aero-platform critical tasks (plan 03 Tasks 1, 2, 3, 5, 6, 11, 12)
   - **Closes every production blocker.** After this, system is safe to run in real production.
3. **Sprint 3 (week 4):** Tier 2 — aero-auth security hardening (plan 05) + aero-ui inline-style migration (plan 06)
4. **Sprint 4 (week 5):** Tier 3 — aero-i18n, aero-notifications (resolves email_engine), aero-installation
5. **Sprint 5+ (weeks 6+):** Cross-cutting real packages (10, 11, 12)
6. **Sprint 6:** Decisions on D3-D6 stubs. Remove or implement.
7. **THEN — HRM** (separate plan, not in this push)

---

## How to Execute Each Plan

Per plan README header:

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

Each plan has:
- TDD-shaped tasks (failing test → implementation → green → commit)
- Specific file paths
- Acceptance criteria per task
- Self-review checklist
- Score recheck rubric

---

## Tracking

Track per-plan completion via git tags:

```
git tag wiring-10-10-phase-0
git tag aero-contracts-10-10
git tag aero-core-10-10
git tag aero-platform-10-10
git tag aero-hrmac-10-10
git tag aero-auth-10-10
git tag aero-ui-10-10
git tag aero-i18n-10-10
git tag aero-notifications-10-10
git tag aero-installation-10-10
git tag aero-custom-fields-10-10
git tag aero-forms-10-10
git tag aero-workflow-10-10
# automation/booking/time-tracking/mobile: tag if implemented, otherwise document removal
```

When all 13 (or 17 if stubs implemented) tags exist, foundation is 10/10. Then start HRM.
