# Next-session kickoff — Priority #5: AUDIT / ACTIVITY (tenant-page redesign)

Continue the tenant-page redesign iteration (priority #5: AUDIT / ACTIVITY). Settings (#3) and
Organization (#4) are DONE, reviewed, and pushed to main. Now redesign the Audit/Activity cluster.

NOTE — DIFFERENT STANDARD: unlike Settings/Organization (settings-shell, edit forms), these are
LIST / TABLE / VIEWER pages. They belong to the **resource-management standard** (the Users page
canon), NOT the settings-shell. Do NOT force a settings-shell here.

READ FIRST: memory [[tenant-page-redesign-iteration]] + [[theme-consistency-all-pages]] +
[[module-grouping-rule]]; the SDD ledger .superpowers/sdd/progress.md; and the canonical
resource-mgmt reference impl: packages/aero-ui/resources/js/Pages/Core/Users/Index.jsx
(IndexPageLayout + PageHeader + tabs via `only:[...]` partial reloads, KPI/Stat strip from live
controller `stats`, filters bar search/status/saved-views, DataTable + Pagination, rich rows,
status Badge, portaled row-action overflow Menu, bulk-action bar, loading skeleton via
router.on('start'/'finish'), EmptyState CTAs). Also re-read the just-shipped Organization work
(commits b405d82c0..1762f0f57) for the nav-collapse pattern + the live-verify rigor.

THE CLUSTER (grounded — verify before relying):

A) **Audit & Activity Logs** submodule — config/module.php ~371 (`code 'audit_logs'`, priority 6,
   route /audit-logs, icon ClipboardDocumentListIcon). 3 declared components:
   - activity_logs  (page /audit-logs/activity)  actions: view / export / filter
   - security_logs  (page /audit-logs/security)  actions: view / export / investigate
   - queue_monitor  (page /audit-logs/queues)    actions: view / retry / flush
   Controller: packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php
   Pages: packages/aero-ui/resources/js/Pages/Core/AuditLogs/{Index,Security,Queues}.jsx

B) **Activity Feed** — config/module.php ~1149 (`code 'activity_feed'` component of a SEPARATE
   parent submodule; route /activity; gated core.activity_feed.view). actions: view / export.
   Controller: packages/aero-core/src/Http/Controllers/Admin/ActivityController.php
   Pages: packages/aero-ui/resources/js/Pages/Core/Activity/{Index,Show}.jsx

ROUTES (grounded; web.php ~388-421 and ~982-995). ⚠️ NAME vs HRMAC mismatch — route NAMES use
HYPHEN `core.audit-logs.*`, HRMAC codes use UNDERSCORE `core.audit_logs.*`. Don't conflate.
  core.audit-logs.index           GET  /audit-logs            → Inertia Core/AuditLogs/Index   (gate core.audit_logs.activity_logs.view)
  core.audit-logs.activity        GET  /audit-logs/activity   → JSON (activityLogs)            (business-logs partial feed)
  core.audit-logs.stats           GET  /audit-logs/stats      → JSON
  core.audit-logs.export          GET  /audit-logs/export                                      (gate ...activity_logs.export)
  core.audit-logs.activity.export POST /audit-logs/activity/export                             (gate ...activity_logs.export)
  core.audit-logs.security        GET  /audit-logs/security   → Inertia Core/AuditLogs/Security(gate ...security_logs.view)
  core.audit-logs.security.export POST /audit-logs/security/export                             (gate ...security_logs.export)
  core.audit-logs.queues          GET  /audit-logs/queues     → Inertia Core/AuditLogs/Queues  (gate ...queue_monitor.view)
  core.audit-logs.queues.retry    POST /audit-logs/queues/retry/{id}                           (gate ...queue_monitor.retry)
  core.audit-logs.queues.flush    POST /audit-logs/queues/flush                                (gate ...queue_monitor.flush)
  core.activity.index             GET  /activity              → Inertia Core/Activity/Index    (gate core.activity_feed.view)
  core.activity.show              GET  /activity/{id}         → Inertia Core/Activity/Show      (gate core.activity_feed.view)
  core.activity.stats             GET  /activity/stats        → JSON
  core.activity.export            GET  /activity/export                                        (gate core.activity_feed.export)

CURRENT STATE (grounded):
- AuditLogs/Index.jsx ALREADY imports IndexPageLayout + DataTable, but it destructures only
  `{ logs, filters }` and IGNORES the controller's `stats` + `tab` (business/model/access) + `meta`
  → KPI strip, the 3 tabs, and pagination are NOT wired. The controller (index()) already returns
  stats/tab/logs/meta/filters. Redesign = wire KPI strip from `stats`, the business/model/access
  TABS (in-place via `only:[...]`, never navigate), real Pagination from `meta`, polished filters.
- Activity/Index.jsx is on the LEGACY DashboardLayout + Card + TextField + Icon (NOT the standard) →
  full port to IndexPageLayout. Activity/Show.jsx is a detail page → align to the standard's detail
  pattern (or a Drawer — brainstorm).
- AuditLogs/Security.jsx + Queues.jsx are table/viewer pages; Queues has retry/flush row actions
  (POST) — keep them as portaled row-action Menu items with the correct HRMAC gates.

OPEN DESIGN QUESTIONS — resolve in brainstorm (do NOT pre-decide):
1. NAV CONSOLIDATION: audit_logs has 3 child nav links (activity/security/queues). Do we collapse
   3→1 by making Security + Queue Monitor TABS of the main Audit Logs page (resource-mgmt tabs do
   in-place partial reloads), keeping ONE "Audit Logs" nav link (collapse_nav pattern from
   Settings/Org)? Or keep them as separate pages? Recommend the tabbed single-page consolidation
   for parity with the iteration, but confirm — Security/Queues have distinct HRMAC view gates, so
   tab visibility must gate per-tab (a user with only queue_monitor.view sees just that tab).
2. ACTIVITY FEED OVERLAP: the separate /activity "Activity Feed" conceptually overlaps the audit
   "Activity Logs" (business-log) tab. Decide: fold Activity Feed in as another tab of the unified
   Audit page, or keep it a distinct page (it lives under a different parent submodule + has its own
   /activity route + activity_feed HRMAC). Watch for data duplication. This is a real product call.
3. PARTIAL-RELOAD MECHANISM: tabs in the Users canon use Inertia `only:[...]` partial reloads on the
   SAME Inertia page. The audit JSON endpoints (core.audit-logs.activity / .stats, core.activity.stats)
   are a different mechanism. Decide whether to drive tabs via Inertia partials (preferred, matches
   canon) and leave the JSON endpoints alone, or consume the JSON. Don't break existing endpoints.

GOTCHAS (carried): @aero/ui only, NO inline style={}, single centralized <style>; Inertia v2
(router.* / useForm, partial reloads via `router.reload({ only:[...] })` — never navigate for tabs);
registered icon names only (registry packages/aero-ui/resources/js/icons/icons.jsx ~48 keys —
unknown = console.warn; prefer heroicon component refs); theme consistency (KPI/stat cards + table
container + any drawers respond to body[data-card-style] + accent — the table container itself must
be a card surface, see [[theme-consistency-all-pages]]); export buttons that window.open a GET are
fine but gate on the export HRMAC; POST actions (queue retry/flush) must be type=button row actions
with NO double-submit; respect the route-name(hyphen) vs HRMAC-code(underscore) split.

VERIFY LIVE (Playwright MCP): ensure vite running (cd c:\laragon\www\aeos365 && npm run dev,
public/hot present). Login democorp.aeos365.test (admin@democorp.com / Aeos365!Admin). Exercise the
real nav-menu links (per [[nav-menu-navigation]]) + every tab: 0 console errors/warnings each; tabs
switch in place (partial reload, URL/Inertia only — no full nav); KPI strip reflects controller
stats; row actions (retry/flush, export) fire exactly ONE request each (no double-submit); toggle
Theme Studio (card-style + accent) — every surface incl. the table container responds; if nav
collapsed, confirm via the authenticated #app data-page navigation prop that the audit cluster shows
the expected childCount and link count.

THEN: superpowers:requesting-code-review over the branch diff, fix Critical/Important, update memory
[[tenant-page-redesign-iteration]] (Audit/Activity DONE; queue → Subscription/Billing → remaining
list pages → systemic {tenant} route-param + HRMAC-gate-uniformity audit), update .superpowers/sdd/
progress.md, and offer finishing-a-development-branch options.

Work on main in place (live host consumes @aero/ui via the vendor/aero junctions — no worktree).
Address me as Boss; show tokens burned per reply. Use the SDD/superpowers workflow (brainstorm →
plan → execute) as with Settings + Organization. Do NOT push unless I say so.
