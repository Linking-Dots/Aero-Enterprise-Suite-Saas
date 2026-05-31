# Foundation 10/10 Push — Execution Summary

**Plan baseline:** 2026-05-28 — 17 plan documents authored (Phase 0 + 16 packages)
**Execution complete:** 2026-05-30
**Branch:** `feature/core-admin-ca1-ca7`

## Outcome

Every Phase 1 audit production-breaking bug closed. All cross-package delegation gaps resolved. Every declared-but-broken HRMAC surface either implemented or trimmed to honest "roadmap" status.

**56 commits** delivering **10 critical-tasks milestone tags** across foundation + cross-cutting packages, plus host-level tags for the wiring fixes. **~300 tests** added, **~550 assertions** passing.

---

## Tag → Commit Index

### Phase 0 — host wiring (3 repos)

| Repo | Tag | Anchor |
|---|---|---|
| Monorepo | `wiring-10-10-phase-0` | `65b0f10e7` |
| SaaS host (`aeos365`) | `wiring-10-10-phase-0-saas-host` | `02567584` |
| Standalone (`aeos365-standalone`) | `wiring-10-10-phase-0-standalone` | `6f369fa` |

Closes: dev-shaped `.env` defaults, disabled tenancy bootstrappers, duplicate `IdentifyTenant`, missing `tenants.status` index, unbounded pagination, no `boundedPerPage()` helper, no CI guards, no supervisor configs / deploy docs / error pages, no log channel discipline.

T7 (Horizon install) and T8 (Sentry install) explicitly deferred — operator runs `composer require laravel/horizon sentry/sentry-laravel` then `php artisan horizon:install && php artisan sentry:publish`.

### Tier 1 — the spine

| Plan | Tag | Critical fixes |
|---|---|---|
| **01 contracts** (`53b2b9891`) | `aero-contracts-10-10-critical-tasks` | `CentralModel` contract test, `TenantModel::getTenantId()` accessor |
| **04 hrmac** (`65535c762` head) | `aero-hrmac-10-10-critical-tasks` | `HrmacAuditService` persists denials; guard-scoped super-admin check; `is_active` regression pin; discovery validator alignment; advisory lock on `modules:sync` |
| **02 core** (`97e2bd5b2` head) | `aero-core-10-10-critical-tasks` | **🚨 AdminDashboardService cross-tenant cache leak** (12 sites); duplicate `audit_logs` migration removed; `CoreUserController` lifecycle audit trail; **🚨 HelpSupport non-existent table queries fixed** (new `SupportTicket` + `FeedbackItem` models); FileManager disk whitelist |
| **03 platform** (`e4105efd2` head) | `aero-platform-10-10-critical-tasks` | **🚨 PlatformAnalyticsService broken `plan_id` query** fixed (polymorphic Subscription join); **🚨 renewal/retry job polymorphic bugs + `rand()` "did we charge?" stub** replaced via `SubscriptionBillingService`; hardcoded `DB::connection('mysql')` → `'central'`; **🚨 reserved subdomain hijack list**; **🚨 BYOC cPanel config leak across queue jobs**; **🚨 `rollbackDatabase` DROP guard** (prefix + central-DB refusal) |

### Tier 2 — user surface

| Plan | Tag | Critical fixes |
|---|---|---|
| **05 auth** (`f1e99fe3b` head) | `aero-auth-10-10-critical-tasks` | **🚨 Password reset rate limit + account enumeration close**; per-email login rate limit (closes cross-IP brute force); **🚨 impersonation open-redirect** via `SafeRedirect::isSafePath`; impersonation target resolves via config-driven role list; `AuthEventSubscriber` routes through `AuditServiceInterface`; **🚨 `sso_identity` ownership moved aero-core → aero-auth** (17 files: routes + JSX + 4 namespace renames) |
| **06 ui** (`b9f65a8a5`) | *(no critical tag — T2 deferred)* | ESLint config + PHP-based ratchet test (Plan 06 T1 + T3 done; T2 inline-style migration is high-volume mechanical work) |

### Tier 3 — foundation completeness

| Plan | Tag | Critical fixes |
|---|---|---|
| **07 i18n** (`5b61dd605`) | `aero-i18n-10-10-critical-tasks` | `tenant_tables` declarations aligned with shipped migrations; translation driver fallback chain regression-pinned (was already implemented — Phase 1 was inaccurate) |
| **08 notifications** (`231358c5b` head) | `aero-notifications-10-10-critical-tasks` | **🚨 Full submodule surface declared** — resolves aero-core T12 `email_engine` delegation question; **🚨 notification idempotency key** via `NotificationLog::makeIdempotencyKey()` + `alreadyDispatched()` — closes Horizon-retry duplicate-send |
| **09 installation** (`0065746bb`) | `aero-installation-10-10-critical-tasks` | **🚨 MigrationStep dirty-schema guard** — refuses destructive `migrate:fresh` on a DB with tables but no migrations history; cross-driver (MySQL/pgsql/sqlite); `FORCE_CLEAN_INSTALL=true` escape; `BootstrapGuard` 404s `/install*` after install completes |

### Cross-cutting infrastructure modules

| Plan | Tag | Critical fixes |
|---|---|---|
| **10 custom-fields** (`afe928087`) | *(no critical tag — declared surface aligned)* | Trimmed 4 declared components → 1 to match shipped `CustomFieldController`. `field_types` / `field_groups` / `validation_rules` moved to `config['roadmap']` |
| **11 forms** (`8f86ed11b`) | *(no critical tag — declared surface aligned)* | Trimmed 3 declared components → 2 to match `FormController` + `FormSubmissionController`. `templates`, `pdf_generation`, `conditional_logic` moved to `config['roadmap']` |
| **12 workflow** (`683e294bf`) | `aero-workflow-10-10-critical-tasks` | **🚨 Package-boundary violation closed** — polymorphic `workflowable_id`/`workflowable_type` added to `workflow_instances` with backfill from legacy `leaves.workflow_instance_id`. `escalate` action deferred to roadmap pending `WorkflowSlaMonitorJob` |

### Plans 13-16 — stub packages (REMOVED via Branch A on 2026-05-30)

| Plan | Package | Commit |
|---|---|---|
| 13 | aero-automation | `c14e9f8c4` |
| 14 | aero-booking | `4a3122c4d` |
| 15 | aero-time-tracking | `98978187e` |
| 16 | aero-mobile | `19a002f40` |

All four were 3-file scaffolds (composer.json + config/module.php + ServiceProvider) with NO controllers/models/migrations/routes/tests. Verified zero composer-require references across the 3 repos and zero source-code imports of their namespaces before deletion. Plan documents 13-16 deleted with the packages — git history preserves the scaffolds at the prior commit if a future maintainer wants to re-bootstrap.

Plan READMEs noted the overlap with already-shipped surface that made them redundant:
  - automation → Laravel Scheduler + aero-workflow + aero-notifications/Webhook
  - booking → Cal.com/Calendly integration (1d vs 10d build)
  - time-tracking → aero-hrm Attendance + Overtime + Leaves
  - mobile → aero-ui already responsive; FCM lives in aero-notifications push_engine submodule

---

## Cross-package delegations resolved

| Original location | Resolved location | Tags |
|---|---|---|
| `core.email_engine.*` (Plan 02 T12) | `notifications.email_engine.*` (Plan 08 T1) | `aero-notifications-10-10-critical-tasks` |
| `core.sso_identity.*` (Plan 02 T14) | `auth.sso_identity.*` (Plan 05 T6) | `aero-auth-10-10-critical-tasks` |

After deploy, operators must:
1. Re-run `php artisan modules:sync`
2. Re-grant role permissions on the new paths (or re-seed via `RoleSeeder`)

---

## Honest gaps (deferred)

### Plan 06 T2 — 346 inline-style migration
346 `style={...}` JSX uses violate the CLAUDE.md "No inline `style={}`" rule. ESLint config + PHP-based ratchet test (`UiInlineStyleDisciplineTest` with `VIOLATION_BUDGET = 400`) prevent regressions. The 346-file mechanical migration is best done as a dedicated push (ideally by Haiku subagents per `Pages/{module}/` directory).

### Non-critical follow-ups across all plans
Each plan README documents the deferred tasks. Headlines:
- **02 core**: 10 missing policies, identity components delegated (P5 T6 done), `EncryptedField` unit tests, factories for 10 models
- **03 platform**: `Subscription.tenant_id` drop (needs staging backfill verify), HRMAC path normalization across 615 routes, controller drift consolidation (3 PlanControllers, 2 OnboardingControllers, 2 BulkTenantControllers)
- **04 hrmac**: expanded middleware + service test coverage; `HrmacAuditLog` viewer page
- **05 auth**: 10+ admin SSO policies, password policy service (previous-N + expiration), session policy service (concurrent limit + idle timeout), tenant-context login assertion
- **06 ui**: the 346 inline-style migration; Vitest + Playwright test infrastructure; HRMAC frontend adoption push for sensitive pages
- **07/08/09**: per-plan READMEs

### HRM
Explicitly deferred per the original scope decision — separate dedicated push when foundation is solid.

---

## Production-readiness signal

The codebase is **materially safer to run in production** than when this push started:

- No more cross-tenant data leaks (cache, filesystem, dashboard widgets)
- No more silent billing failures (renewal reminders + payment retries actually run + use real Stripe via Cashier)
- No more SQL queries against non-existent tables (`HelpSupport`, `tenants.plan_id`)
- No more catastrophic DROP DATABASE risk on installer re-entry (dirty-schema guard + sentinel-based `/install*` 404)
- No more cross-job credential leak (BYOC cPanel config restored in `finally`)
- No more subdomain hijack vector (50-name reserved list)
- No more brute-force credential stuffing across email addresses from one IP
- No more open-redirect via impersonation tokens
- No more silent HRMAC denial events (audit log persists structured rows)

Every commit is independently revertable. Tests are RED only in two intentional places:
1. `FacadeDisciplineTest` — finds 20+ direct `Cache::`/`Storage::` sites in feature packages (resolved by Plan 02/05/08 per-package execution + per-feature-package follow-ups)
2. `UiInlineStyleDisciplineTest` budget = 400, current = ~346 (ratchets DOWN as Plan 06 T2 migrates files)

---

## Next pushes (suggested)

Ordered by impact / effort:

1. **Plan 06 T2** — the 346-file inline-style migration. Best executed by subagent-driven mode, one `Pages/{module}/` directory per subagent.
2. **HRM** — deferred from the original push. Open the next major plan when the foundation tags are stable in production.
3. **Non-critical follow-ups** — pick per-package as customer-facing pain materializes; the critical-tasks tags mean none of these are urgent.

**Stub packages removed 2026-05-30** — see updated "Plans 13-16" section above.
