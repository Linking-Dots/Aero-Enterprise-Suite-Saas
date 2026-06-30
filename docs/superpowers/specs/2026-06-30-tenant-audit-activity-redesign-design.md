# Tenant Audit/Activity Redesign — Design Spec

**Date:** 2026-06-30
**Priority:** #5 in the tenant-page-redesign iteration (Settings #3 done, Organization #4 done).
**Standard:** Resource-management canon (the Users page), NOT the settings-shell. These are list / table / viewer pages.
**Branch:** `main`, in-place (vendor/aero junctions — no worktree). Not pushed.

## Goal

Redesign the Audit/Activity cluster onto the hardened frontend engine + app shells, matching the
`Core/Users/Index.jsx` resource-management canon (`IndexPageLayout` + `PageHeader` + tabs via
Inertia `only:[...]` partial reloads + KPI strip from controller `stats` + filters bar + `DataTable` +
`Pagination` + portaled row-action `Menu` + skeleton + `EmptyState`), and apply every Theme Studio
dimension to every surface including the table container.

## Two confirmed product calls (Boss, 2026-06-30)

1. **Audit Logs → one tabbed page.** Fold Security + Queue Monitor into the main Audit page as tabs;
   nav collapses 3→1; tabs switch in place via partial reloads.
2. **Activity Feed → stays distinct, redesigned, with its own nav home.** Keep the collaboration
   activity feed separate from the compliance audit record (different data store + submodule); give the
   currently nav-orphaned `/activity` a real nav entry.

## Grounded current state

- **`AuditLogController::index()`** returns `title, stats, tab (business|model|access), logs => $query->items(),
  meta, filters`. `getStats()` yields business_events_today/total, model_changes_today,
  sensitive_accesses_today, active_users_today.
- **Data-shape bug (to fix):** `index()` passes `logs => items()` + a separate `meta`, but
  `Index.jsx` / `Queues.jsx` read the *full paginator* (`logs.data`, `logs.next_page_url`,
  `logs.last_page`). Pagination is therefore broken today. `Index.jsx` filter params (`from`/`to`) also
  don't match the controller (`date_from`/`date_to`). Reconcile by returning full paginators from the
  controller and reading `.data`/`.current_page`/`.last_page` + `<Pagination>` on the page (Users canon).
- **Security/Queues** are separate Inertia methods/routes (`security()`, `queues()`) + POST `retryJob()` /
  `flushQueue()` (return `back()` redirects). Same items()+meta split → same shape bug.
- **Activity Feed** (`ActivityController`) renders legacy `DashboardLayout` + `Card` + `TextField` + `Icon`.
  `index()` returns `title, activities (paginator), stats (total/today/week), filters`. `show()` is a
  legacy detail page. Routes gate the **3-segment** `core.activity_feed.view` / `.export`.
- **Activity Feed is nav-orphaned:** `activity_feed` is a *component* of the `comments_mentions`
  submodule, which is `'show_in_nav' => false`. `/activity` is unreachable via any nav link today.
- **Route-name vs HRMAC-code split:** route names use hyphens (`core.audit-logs.*`); HRMAC codes use
  underscores (`core.audit_logs.*`). Do not conflate.

## A. Unified Audit page — `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx`

One `IndexPageLayout`. **Five tabs**, switched in place via `router.reload({ only: [...] })` /
`router.get(route('core.audit-logs.index'), { tab, ...filters }, { preserveState, preserveScroll, only: [...] })`
— never navigate.

| Tab label | `tab=` | Data source | View gate (HRMAC code) |
|---|---|---|---|
| Activity | `business` | `audit_logs` | `core.audit_logs.activity_logs.view` (route base) |
| Model changes | `model` | `activity_log` | route base |
| Access | `access` | `access_logs` | route base |
| Security | `security` | `audit_logs` (`event_type like auth.%`/`security.%`) | `core.audit_logs.security_logs.view` |
| Queue | `queues` | `failed_jobs` | `core.audit_logs.queue_monitor.view` |

- **KPI strip:** `<Stat>` cards from existing `getStats()` — events today, total events, model changes
  today, sensitive accesses today. (Currently the page ignores `stats` entirely.)
- **Tab visibility:** render the Security / Queue tabs only when `useHRMAC()` returns true for their view
  codes. Activity/Model/Access share the route-base gate (always visible if the page loaded).
- **Filters bar:** search + event_type `Select` + date-from/date-to inputs (param names `search`,
  `event_type`, `date_from`, `date_to` to match the controller). Queue tab has no filters.
- **Columns per tab:** logs tabs → actor / event-badge / action / subject / IP / time; Queue → id /
  queue-badge / job-payload snippet / exception snippet / failed-at / Retry action.
- **Header / row actions:**
  - Export button — visible per active tab, gating `core.audit_logs.activity_logs.export` (Activity/Model/
    Access) or `core.audit_logs.security_logs.export` (Security). `type="button"`.
  - Queue Retry (per row) gates `core.audit_logs.queue_monitor.retry`; Flush-all (header) gates
    `core.audit_logs.queue_monitor.flush`. Both `type="button"`, single-submit guarded (`loading` state),
    POST to existing routes, `back()` redirect → toast.
- **Skeleton:** `router.on('start'/'finish')` → `DataTable loading`. **Empty:** `EmptyState` per tab
  (filter-aware "no match" + Reset where filters apply; true-empty messaging otherwise).
- **Backend (`AuditLogController`):**
  - Extend `index()` `match($tab)` to also handle `security` and `queues`, returning the right dataset.
  - Add in-controller HRMAC re-checks for the `security` and `queues` tabs (route middleware only gates
    `activity_logs.view`): abort 403 if the user lacks `security_logs.view` / `queue_monitor.view`.
  - **Return full paginators** (drop the `items()`+`meta` split) so the page reads canonical paginator
    shape. Update `getBusinessLogs`/`getModelActivityLogs`/`getAccessLogs` accordingly (or pass the
    paginator straight through).
  - Legacy `security` / `queues` GET routes → redirect to `index(?tab=security|queues)` (don't break;
    nav no longer links them). POST retry/flush routes unchanged.
- **Nav collapse 3→1:** `collapse_nav => true` on the `audit_logs` submodule in
  `packages/aero-core/config/module.php`, honored in **both** registration paths
  (`AbstractModuleProvider::registerNavigation` AND the hand-rolled
  `AeroCoreServiceProvider::registerCoreNavigation` — last-wins registry, the Settings/Org lesson).
  Collapsed leaf keeps `access 'core.audit_logs'` admin visibility. Clear config+app cache on host.

## B. Activity Feed — `packages/aero-ui/resources/js/Pages/Core/Activity/{Index,Show}.jsx`

- **Index** → `IndexPageLayout`:
  - KPI strip: total / today / this week (`getActivityStats()`).
  - Filters: search + module `Select` + action `Select` + date range (param names per controller:
    `module`, `action`, `start_date`, `end_date`; add `search` server-side if absent — verify before use).
  - `DataTable`: actor / description / action-badge / module-badge / time; `<Pagination>`.
  - Skeleton + `EmptyState`; Export button gating `core.activity_feed.export`, `type="button"`.
  - A row "View" opens the existing `core.activity.show` route.
- **Show** → redesigned clean read-only detail on the `App` layout (drop `DashboardLayout` / `Card` /
  className-on-`Icon`). Registered icons only.
- **Nav home + gate reconcile:** promote `activity_feed` to a first-class nav-visible core submodule in
  `config/module.php` so it gets a proper nav entry (`show_in_nav` default true). Reconcile the HRMAC
  gate — the route gates the 3-segment `core.activity_feed.view`, which does not map to the current
  4-segment component path `core.comments_mentions.activity_feed.*`. Exact submodule shape (direct
  view/export actions vs a component) + any permission re-seed are worked out in the implementation plan
  and **live-verified via the authenticated nav prop** (same rigor as the collapse work). Logged as an
  input to the systemic `{tenant}` route-param + HRMAC-gate-uniformity audit.
  - *Fallback (smaller blast radius, if the gate reconcile proves risky during execution):* surface a
    nav entry pointing at `/activity` without restructuring the HRMAC node, deferring the gate fix to the
    systemic audit. Default path is the full reconcile; fall back only if execution surfaces real risk.

## C. Compliance (every touched file)

- `@aero/ui` components only; no inline `style={}`; single centralized `<style>` block if any CSS is
  unavoidable.
- Inertia v2: `router.*`, tabs via `router.get`/`router.reload` with `only:[...]` — never v1 `Inertia.*`,
  never full navigate for tab switches.
- Registered icons only (the `icons.jsx` ~48-key registry; NOT `server/lock/send/eye/cog/globe/photo/
  shield/key/puzzle`). Heroicon component refs where the engine expects them.
- Theme consistency: card-style + accent must reach the **table container surface** and KPI cards, not
  just `.aeos-card-auto`.
- POST actions (`retry`/`flush`/`export`) `type="button"`, no double-submit.
- Respect the hyphen route-name (`core.audit-logs.*`) vs underscore HRMAC-code (`core.audit_logs.*`) split.

## Tasks (order)

1. **Audit backend reconcile** — `index()` 5-tab `match` (+ security/queues), in-controller gate
   re-checks, full-paginator returns; legacy security/queues GET → redirect.
2. **Audit Index 5-tab page** — rebuild `Index.jsx` on `IndexPageLayout` (tabs, KPI, filters, per-tab
   columns, Retry/Flush/Export actions, skeleton, EmptyState). Remove now-folded `Security.jsx` /
   `Queues.jsx` (or leave as dead pages behind redirects — decide in plan).
3. **Audit nav collapse** — `collapse_nav` on `audit_logs`, both paths; cache clear; verify nav prop.
4. **Activity Feed backend** — nav-home submodule promotion + gate reconcile in `config/module.php`
   (+ any permission re-seed); verify nav prop + gate live.
5. **Activity Index + Show redesign** — port both off `DashboardLayout` onto the resource standard.
6. **Live Playwright sweep + code review** — exercise real nav links + every tab; 0 console
   errors/warnings; tabs switch in place; KPI reflects stats; each action fires exactly one request;
   Theme Studio card-style + accent reach every surface incl. the table; confirm nav childCount/link-count
   via the authenticated `#app data-page` prop. Then `superpowers:requesting-code-review`, fix
   Critical/Important.

## Verification

Vite up (`cd c:\laragon\www\aeos365 && npm run dev`, `public/hot`). Login
`democorp.aeos365.test` (`admin@democorp.com` / `Aeos365!Admin`). Per Task 6 above. Package PHPUnit is
not wired into either host (known infra gap — `PackageTestCase` not autoloaded); verify endpoints live as
in Settings/Organization.

## Out of scope (logged, not done here)

- Systemic `{tenant}` route-param + HRMAC-gate-uniformity audit (the `activity_feed` 3-seg gate and the
  rail-vs-GET gate uniformity from Org feed into it).
- Deduplicating the audit_logs business events vs the activities-table feed (real product investigation;
  the two are kept as distinct destinations for now).
