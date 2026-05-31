# Production-Readiness Audit — Axis C: Scalability (Follow-up Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans or superpowers:subagent-driven-development.

**Source:** Axis C walk-through (8 audit questions, 2 batches), 2026-05-30
**Frame:** Find the next bottleneck before customers do — per-request cost, queue throughput, query/index efficiency, payload size.
**Goal:** Apply the 8 scalability decisions the operator made this pass. Every task = an explicit "Recommended" answer.
**Estimated effort:** ~4–6 engineer-days
**Theme:** Two cost centres dominate — (1) per-request overhead paid on every authenticated page (HRMAC resolution + subscription join + large shared-prop payload), and (2) load-everything-then-process-in-PHP patterns (dashboards, serial tenant-stats).

---

## Decisions captured (this axis)

| # | Area | Decision | Status |
|---|---|---|---|
| C1 | PHP-side aggregation on unbounded `->get()` | Push aggregation to SQL; audit analytics/dashboard controllers | ⚠️ task |
| C2 | Per-request uncached subscription join | Cache subscribed module codes per tenant; event-invalidate; memoize accessor | ⚠️ task |
| C3 | Heavy jobs share `default` queue | Dedicated queues + Horizon balance + documented throughput model | ⚠️ task |
| C4 | Index coverage unverified on hot columns | Audit query filter/sort pairs vs indexes; add composites; EXPLAIN smoke test | ⚠️ task |
| C5 | Inertia shared-prop payload | Trim whole-table lookups from per-request props; byte budget | ⚠️ task |
| C6 | Per-request HRMAC DB cost | Share one cached per-user access tree between middleware + Inertia | ⚠️ task |
| C7 | Serial tenant-stats aggregation | Fan out per-tenant/chunk onto a dedicated queue (batch) | ⚠️ task |
| C8 | Ad-hoc cache TTLs + stale-on-change | Document TTL strategy; event-invalidate per-user caches | ⚠️ task |

**Note:** C2, C6, C8 all touch the per-request HRMAC/subscription caching layer — implement as one coherent caching unit. C3 + C7 share queue topology.

---

## Task C1 — Push dashboard aggregation to SQL

**Confirmed:** `WellbeingController` loads the ENTIRE `EmployeeRiskScore` table twice per render — once for a 6-month `groupBy` trend, once for high/med/low stat counts — then computes in PHP.

**Files:** `packages/aero-hrm/src/Http/Controllers/WellbeingController.php` + analytics/dashboard controllers (`Analytics/*`, `*DashboardController`, `WorkforcePlanningController`, `CompensationPlanningController`, `PulseSurveyController`).

- [ ] Replace load-all-then-aggregate with SQL: `selectRaw('DATE_FORMAT(burnout_calculated_at,"%Y-%m") m, AVG(burnout_risk_score) avg, SUM(burnout_risk_score>=60) high, COUNT(*) total')->groupBy('m')`; stat counts via a single `selectRaw` of conditional sums.
- [ ] Audit the analytics/dashboard controllers for the same pattern; convert the worst offenders. Reserve `->get()` for bounded lookups.
- [ ] Add a review note / lint flag for unbounded `->get()` on high-volume models (attendances, payslips, *_risk_scores, *_responses).
- [ ] Test: dashboard returns identical numbers with the SQL path; assert query count is O(1) not O(rows).
- [ ] Commit: `perf(hrm): SQL aggregation for wellbeing + analytics dashboards (C1)`

---

## Task C2 — Cache subscribed module codes (per-request join removal)

`HandleInertiaRequests::getSubscribedModuleCodes()` runs a `productSubscriptions` ⨝ `products` query on every page load; `Tenant::subscribed_product_modules` (flagged Task 2 P2) does an uncached join per access.

**Files:** `HandleInertiaRequests.php`, `Tenant.php`.

- [ ] Cache subscribed module codes per tenant (`tenant_subscribed_modules:{id}`, short TTL), invalidated on the existing `ProductSubscriptionChanged` event (add the cache forget to `ResyncTenantModuleCatalog` or a dedicated listener).
- [ ] Memoize `subscribed_product_modules` per request (static/instance memo).
- [ ] Test: second navigation in the same request cycle issues zero subscription queries; cache busts on subscription change.
- [ ] Commit: `perf(platform): cache subscribed module codes + event-invalidate (C2)`

---

## Task C6 — One cached per-user access tree (shared by middleware + Inertia)

`CheckRoleModuleAccess` resolves `userCanAccessModule/SubModule` per gated request; the Inertia middleware separately builds + caches the access tree (600s). Two independent resolutions of the same data.

**Files:** `CheckRoleModuleAccess.php`, `RoleModuleAccessService`, `HandleInertiaRequests.php`.

- [ ] Resolve the per-user access tree once (cache key `user_access_tree:{id}`), and have BOTH the middleware check and the Inertia props read from it.
- [ ] Verify no N+1 in the cascading module→submodule→component→action resolution (eager-load the tree in one pass).
- [ ] Invalidate on role/grant change (shared with C8).
- [ ] Test: a gated request after warm cache issues zero `role_module_access` queries; middleware + frontend agree.
- [ ] Commit: `perf(hrmac): single cached per-user access tree for middleware + inertia (C6)`

---

## Task C8 — Cache TTL strategy + event invalidation

**Files:** a short `docs/standards/cache-strategy.md` + invalidation listeners.

- [ ] Document the TTL strategy (what's cached, TTL, invalidation trigger): access tree 600s, lookups 3600s, license 86400s, tenant resolution 3600s, subscribed modules (C2).
- [ ] Event-invalidate per-user access caches (`user_access_tree`, `user_permissions_map`, `standalone_user_module_access`) on role assignment/grant change — don't wait out 600s (perf-consistency + access-freshness/security).
- [ ] Cross-link: Axis A A8 (flush tenant resolution cache on suspend), Axis B cache-invalidation. Use one invalidation surface.
- [ ] Test: granting/revoking a role busts the user's cached tree immediately.
- [ ] Commit: `perf(hrmac): documented TTL strategy + event-driven cache invalidation (C8)`

---

## Task C5 — Trim Inertia shared-prop payload

Every authenticated page serializes `auth.user.{module_access, accessible_modules, modules_lookup, sub_modules_lookup, permissions_map}`. `modules_lookup` + `sub_modules_lookup` are whole-table maps.

**Files:** `HandleInertiaRequests.php` (both aero-core + aero-platform), affected aero-ui consumers.

- [ ] Keep `permissions_map` (useHRMAC depends on it). Move `modules_lookup`/`sub_modules_lookup` off the per-request payload — serve once via a cached endpoint or an Inertia persistent/lazy prop loaded on demand.
- [ ] Measure the shared-prop byte size before/after; set a payload budget (assert in a test).
- [ ] Grep aero-ui for consumers of `modules_lookup`/`sub_modules_lookup`; repoint them to the new source.
- [ ] Commit: `perf(ui): trim whole-table lookups from per-request inertia payload (C5)`

---

## Task C3 — Dedicated queues + throughput model

Named queues exist for `security`/`notifications`/`emails`/`error-reporting`, but `ProvisionTenant` (timeout 600s), `AggregateTenantStats`, renewal/retry billing run on `default`.

**Files:** the heavy jobs (`onQueue`), `deploy/supervisor/aeos365-horizon.conf`, `config/horizon.php` (when installed), `deploy/README.md`.

- [ ] Assign heavy/slow jobs to dedicated queues: `provisioning` (ProvisionTenant), `maintenance` (AggregateTenantStats, purge), `billing` (renewals/retries). Keep fast user-facing jobs off them.
- [ ] Horizon supervisor config: separate worker pools per queue (so a provisioning backlog can't starve notifications). Document a throughput model: `jobs/sec ≈ workers ÷ avg_job_duration`; size pools from measured avg durations.
- [ ] Commit: `perf(queue): dedicate queues for heavy jobs + Horizon balance + throughput doc (C3)`

---

## Task C7 — Fan out tenant-stats aggregation

`AggregateTenantStats` processes all active tenants serially (chunk + foreach + `tenancy()->initialize/end` per tenant).

**Files:** `packages/aero-platform/src/Jobs/AggregateTenantStats.php` + a new per-tenant job.

- [ ] Convert to a dispatcher: chunk tenants and dispatch a per-tenant (or per-chunk) `AggregateOneTenantStats` job onto the `maintenance` queue (C3), wrapped in a `Bus::batch()` for completion/failure tracking.
- [ ] One tenant's slowness/failure no longer blocks the rest; aggregation parallelizes across workers.
- [ ] Test: dispatching enqueues N child jobs; a failing tenant doesn't fail the batch siblings.
- [ ] Commit: `perf(platform): fan out tenant-stats aggregation onto maintenance queue (C7)`

---

## Task C4 — Index coverage audit on hot tables

195 index declarations exist across 35 HRM migrations, but no systematic filter/sort-vs-index check.

**Files:** new migration(s) for missing composites; an index checklist or EXPLAIN smoke test.

- [ ] Map common filter+sort pairs on high-volume tables: `attendances(employee_id, date)`, `leave_applications(employee_id, status)`, `payslips(employee_id, id)`, `audit_logs(created_at)` / `(subject_type, subject_id)`, `*_risk_scores(burnout_calculated_at)`.
- [ ] Add composite indexes where missing (verify against existing declarations first — don't duplicate).
- [ ] Add an EXPLAIN-based smoke test (or documented checklist) for the hottest list endpoints asserting no full-table scan on the primary filter.
- [ ] Commit: `perf(hrm): composite indexes for hot filter/sort columns (C4)`

---

## Execution order

1. **C2 + C6 + C8** (one caching unit — biggest per-request win; shared invalidation surface).
2. **C5** (payload trim — independent front-end win).
3. **C1** (dashboard SQL aggregation — independent).
4. **C4** (indexes — independent; do before/with C1 so aggregates hit indexes).
5. **C3** (queue topology) → **C7** (fan-out depends on C3's `maintenance` queue).

Subagent-driven fine for C1 (per-controller), C4 (per-table). C2/C6/C8 are coupled — one unit.

---

## Self-Review

- ✅ Every "Recommended" answer (C1–C8) maps to a task with files + test stub + commit draft.
- ✅ Confirmed evidence cited (WellbeingController double full-table load; named-queue gaps; serial AggregateTenantStats; uncached getSubscribedModuleCodes).
- ✅ Coupled work grouped (C2/C6/C8 caching; C3/C7 queues) to avoid rework.
- ✅ C8's event-invalidation is both a perf and a security-freshness fix and is explicitly cross-linked to Axis A A8 and Axis B cache work so the three don't build separate invalidation paths.
- ⚠️ C5/C6 touch `HandleInertiaRequests` in BOTH aero-core and aero-platform — keep the two middlewares consistent.
