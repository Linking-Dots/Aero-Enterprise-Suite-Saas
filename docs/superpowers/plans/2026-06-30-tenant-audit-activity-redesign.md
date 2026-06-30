# Tenant Audit/Activity Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the Audit/Activity cluster onto the resource-management canon — a unified 5-tab Audit page (nav 3→1) plus a redesigned, nav-homed Activity Feed.

**Architecture:** `Core/AuditLogs/Index.jsx` becomes one `IndexPageLayout` with five tabs (Activity/Model/Access/Security/Queue) switched in place via Inertia `only:[...]` partial reloads on `core.audit-logs.index?tab=`; the controller's `index()` `match($tab)` is extended to serve all five datasets as full paginators with in-controller per-tab HRMAC re-checks. Security/Queue standalone pages are folded in (GET routes redirect). The Activity Feed (`/activity`) is ported off `DashboardLayout` onto the same canon and promoted to a first-class nav-visible core submodule with reconciled 4-segment HRMAC codes.

**Tech Stack:** Laravel 12 + React 18 + Inertia v2; `@aero/ui` component library; HRMAC access control; Spec: `docs/superpowers/specs/2026-06-30-tenant-audit-activity-redesign-design.md`.

## Global Constraints

- **Package-first:** all code in `packages/aero-*`. Host apps untouched.
- **`@aero/ui` only**; no inline `style={}`; single centralized `<style>` block if any CSS is unavoidable.
- **Inertia v2:** `router.*`; tabs via `router.get(...)` / `router.reload({ only:[...] })` — never v1 `Inertia.*`, never a full navigate for a tab switch.
- **Registered icons only.** The `@aero/ui` Icon registry (`packages/aero-ui/resources/js/icons/icons.jsx`) contains exactly these names: `home, phone, users, user, settings, layout, menu, x, search, plus, minus, check, checkCircle, alertCircle, alertTriangle, info, sparkles, bell, mail, chevronLeft, chevronRight, chevronUp, chevronDown, arrowUp, arrowDown, arrowLeft, arrowRight, arrowPath, trending, chartBar, document, folder, inbox, download, upload, filter, pencil, trash, star, pin, command, external, link, calendar, clock, sort, sun, moon` plus aliases `sparkle, bars3, cog, location, refresh, dashboard, fileText, chat, more, chart, graph`. Any other `leftIcon`/`icon`/`name` string warns and renders a fallback square — do NOT use `server/lock/send/eye/shield/key/puzzle/photo/globe/plus-circle/arrow-right-on-rectangle/trash` (heroicon-style kebab names are NOT in this registry). For `<Stat icon=>` and `<Button leftIcon=>` use only the names above.
- **HRMAC route-name vs code split:** route NAMES use hyphens (`core.audit-logs.*`); HRMAC CODES use underscores (`core.audit_logs.*`). Never conflate.
- **POST actions** (`retry`/`flush`/`export`) are `type="button"` and single-submit-guarded (a `loading`/in-flight state disables re-click).
- **Theme consistency:** card-style + accent must reach the table container surface and KPI cards (see [[theme-consistency-all-pages]]), not just `.aeos-card-auto`. `IndexPageLayout` + `DataTable` already carry these — do not strip their wrapper classes.
- **Testing reality:** package PHPUnit is NOT wired into either host (`PackageTestCase` not autoloaded — known infra gap). Each task is verified by (a) a vite compile-transform returning HTTP 200 for the changed module, and (b) targeted live checks (Playwright + the authenticated `#app data-page` nav prop), exactly as the Settings/Organization clusters were. There are no runnable package unit tests; "verify" steps below are the test cycle.
- **Branch:** work in place on `main` (vendor/aero junctions — no worktree). Do NOT push.

---

## File Structure

- `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php` — extend `index()` to 5 tabs + full paginators + per-tab gate; add legacy redirects. (Task 1)
- `packages/aero-core/routes/web.php` — point legacy `security`/`queues` GET routes at redirects; update activity-feed gate codes. (Tasks 1, 4)
- `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx` — rebuilt unified 5-tab page. (Task 2)
- `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Security.jsx`, `Queues.jsx` — deleted (folded into Index). (Task 2)
- `packages/aero-core/config/module.php` — `collapse_nav` on `audit_logs`; promote `activity_feed` to a core submodule. (Tasks 3, 4)
- `packages/aero-ui/resources/js/Pages/Core/Activity/Index.jsx`, `Show.jsx` — ported onto the resource canon. (Task 5)

---

### Task 1: Audit backend — 5-tab `index()`, full paginators, per-tab gate, legacy redirects

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php`
- Modify: `packages/aero-core/routes/web.php:402-412` (legacy GET security/queues)

**Interfaces:**
- Produces (Inertia props for `Core/AuditLogs/Index`): `{ title: string, stats: object, tab: string, logs: LengthAwarePaginator, filters: object }` where `logs` is a FULL paginator (`logs.data`, `logs.current_page`, `logs.last_page`, `logs.total`, `logs.from`, `logs.to`). `tab` ∈ `business|model|access|security|queues`.
- Consumes: `Aero\Core\Services\ModuleAccessService::canAccessComponent(User, 'core', 'audit_logs', $componentCode): array` returning `['allowed' => bool, ...]`.

- [ ] **Step 1: Make the private log helpers return paginators, not `[items, meta]`.**

In `AuditLogController.php`, change `getBusinessLogs`, `getModelActivityLogs`, `getAccessLogs` to return the paginator object directly. For `getBusinessLogs`:

```php
private function getBusinessLogs(int $perPage, string $search, ?string $actorId, ?string $eventType, ?string $dateFrom, ?string $dateTo)
{
    if (! $this->tableExists('audit_logs')) {
        return $this->emptyPaginator($perPage);
    }

    return DB::table('audit_logs')
        ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('actor_name', 'like', "%{$search}%")
                ->orWhere('subject_label', 'like', "%{$search}%");
        }))
        ->when($actorId, fn ($q) => $q->where('actor_id', $actorId))
        ->when($eventType, fn ($q) => $q->where('event_type', $eventType))
        ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
        ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
        ->orderByDesc('created_at')
        ->paginate($perPage)
        ->withQueryString();
}
```

Apply the same shape to `getModelActivityLogs` (return `...->paginate($perPage)->withQueryString();`, table `activity_log`, guard returns `$this->emptyPaginator($perPage)`) and `getAccessLogs` (table `access_logs`, same).

- [ ] **Step 2: Add the security + queue paginators and an empty-paginator helper.**

Add these private methods:

```php
private function getSecurityLogs(int $perPage, string $search, ?string $eventType, ?string $dateFrom, ?string $dateTo)
{
    if (! $this->tableExists('audit_logs')) {
        return $this->emptyPaginator($perPage);
    }

    return DB::table('audit_logs')
        ->where(function ($q) {
            $q->where('event_type', 'like', 'auth.%')
                ->orWhere('event_type', 'like', 'security.%');
        })
        ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('actor_name', 'like', "%{$search}%")
                ->orWhere('actor_email', 'like', "%{$search}%");
        }))
        ->when($eventType, fn ($q) => $q->where('event_type', $eventType))
        ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
        ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
        ->orderByDesc('created_at')
        ->paginate($perPage)
        ->withQueryString();
}

private function getQueueJobs(int $perPage)
{
    if (! $this->tableExists('failed_jobs')) {
        return $this->emptyPaginator($perPage);
    }

    return DB::table('failed_jobs')
        ->orderByDesc('failed_at')
        ->paginate($perPage)
        ->withQueryString();
}

private function emptyPaginator(int $perPage): \Illuminate\Pagination\LengthAwarePaginator
{
    return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
        'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
    ]);
}
```

- [ ] **Step 3: Rewrite `index()` to serve all five tabs with a per-tab gate.**

Replace the body of `index()`:

```php
public function index(Request $request): Response
{
    $tab = $request->get('tab', 'business');
    $perPage = (int) $request->get('per_page', 20);
    $search = (string) $request->get('search', '');
    $actorId = $request->get('actor_id');
    $eventType = $request->get('event_type');
    $dateFrom = $request->get('date_from');
    $dateTo = $request->get('date_to');

    // The route gates only activity_logs.view (page load). The Security/Queue
    // tabs are stricter sub-views — re-check their own HRMAC component here so a
    // crafted ?tab= cannot read data the user lacks access to (defense in depth;
    // the frontend already hides the tabs).
    $access = app(\Aero\Core\Services\ModuleAccessService::class);
    if ($tab === 'security') {
        abort_unless($access->canAccessComponent($request->user(), 'core', 'audit_logs', 'security_logs')['allowed'] ?? false, 403);
    }
    if ($tab === 'queues') {
        abort_unless($access->canAccessComponent($request->user(), 'core', 'audit_logs', 'queue_monitor')['allowed'] ?? false, 403);
    }

    $logs = match ($tab) {
        'model' => $this->getModelActivityLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
        'access' => $this->getAccessLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
        'security' => $this->getSecurityLogs($perPage, $search, $eventType, $dateFrom, $dateTo),
        'queues' => $this->getQueueJobs($perPage),
        default => $this->getBusinessLogs($perPage, $search, $actorId, $eventType, $dateFrom, $dateTo),
    };

    return Inertia::render('Core/AuditLogs/Index', [
        'title' => 'Audit Logs',
        'stats' => $this->getStats(),
        'tab' => $tab,
        'logs' => $logs,
        'filters' => $request->only(['search', 'actor_id', 'event_type', 'date_from', 'date_to']),
    ]);
}
```

- [ ] **Step 4: Redirect the now-folded standalone GET pages.**

Replace `security()` and `queues()` method bodies so the old routes land on the unified page (don't break bookmarks; nav no longer links them). Keep `retryJob()` / `flushQueue()` / `export()` / the legacy `*Logs`/`exportSecurityLogs` methods unchanged.

```php
public function security(Request $request): RedirectResponse
{
    return redirect()->route('core.audit-logs.index', ['tab' => 'security']);
}

public function queues(Request $request): RedirectResponse
{
    return redirect()->route('core.audit-logs.index', ['tab' => 'queues']);
}
```

(Their return type changes from `Response` to `RedirectResponse` — update the method signatures; `RedirectResponse` is already imported.)

- [ ] **Step 5: Let the legacy security/queues GET routes still resolve to the redirect.**

In `routes/web.php`, the `security` (line ~403) and `queues` (line ~409) GET routes already point at `AuditLogController::security` / `queues`. They now redirect — no route edit strictly required. Leave the `withoutMiddleware`/`middleware` chains as-is (the redirect target re-gates). Confirm the two routes still reference `[AuditLogController::class, 'security']` and `'queues']`. No change needed unless they were inlined elsewhere.

- [ ] **Step 6: Verify the controller compiles and the page renders all tabs.**

Run on the host:

```bash
cd c:/laragon/www/aeos365 && php -l ../Aero-Enterprise-Suite-Saas/packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php
```

Expected: `No syntax errors detected`. Then with the dev server up, hit `http://democorp.aeos365.test/audit-logs?tab=security` and `?tab=queues` while logged in as admin — expect HTTP 200 (not 500), and a JSON/Inertia payload whose `logs` has `data`/`current_page`/`last_page` keys. (Full UI verification happens in Task 2.)

- [ ] **Step 7: Commit.**

```bash
git add packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php packages/aero-core/routes/web.php
git commit -m "Phase3/audit: AuditLogController index() serves 5 tabs as full paginators + per-tab HRMAC gate; legacy security/queues GET redirect"
```

---

### Task 2: Unified 5-tab Audit Index page

**Files:**
- Modify (full rewrite): `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx`
- Delete: `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Security.jsx`
- Delete: `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Queues.jsx`

**Interfaces:**
- Consumes (from Task 1): props `{ title, stats, tab, logs (full paginator), filters }`. `stats` keys: `business_events_today`, `business_events_total`, `model_changes_today`, `sensitive_accesses_today`. HRMAC codes: view `core.audit_logs.{activity_logs|security_logs|queue_monitor}.view`; export `core.audit_logs.activity_logs.export` / `core.audit_logs.security_logs.export`; queue `core.audit_logs.queue_monitor.retry` / `.flush`.

- [ ] **Step 1: Replace `Index.jsx` with the unified page.**

```jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Input,
  Select,
  Text,
  Mono,
  Stat,
  Tabs,
  EmptyState,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EVENT_INTENT = {
  login: 'success', logout: 'neutral', login_failed: 'danger',
  password_changed: 'warning', created: 'success', updated: 'neutral',
  deleted: 'danger', exported: 'warning', impersonated: 'amber',
  login_success: 'success', mfa_failed: 'danger', mfa_success: 'success',
  suspicious: 'danger', permission_denied: 'warning', token_revoked: 'warning',
};

const EVENT_TYPE_OPTIONS = [
  { value: '', label: 'All Event Types' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
  { value: 'login_failed', label: 'Login Failed' },
  { value: 'password_changed', label: 'Password Changed' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'exported', label: 'Exported' },
  { value: 'impersonated', label: 'Impersonated' },
];

const SECURITY_EVENT_OPTIONS = [
  { value: '', label: 'All Security Events' },
  { value: 'login_failed', label: 'Login Failed' },
  { value: 'login_success', label: 'Login Success' },
  { value: 'logout', label: 'Logout' },
  { value: 'password_changed', label: 'Password Changed' },
  { value: 'mfa_failed', label: 'MFA Failed' },
  { value: 'mfa_success', label: 'MFA Success' },
  { value: 'suspicious', label: 'Suspicious Activity' },
  { value: 'impersonated', label: 'Impersonation' },
  { value: 'permission_denied', label: 'Permission Denied' },
  { value: 'token_revoked', label: 'Token Revoked' },
];

const truncate = (s, n) => (s ? (s.length > n ? s.slice(0, n) + '…' : s) : '—');

export default function AuditLogsIndex({ stats, tab: initialTab, logs, filters }) {
  const toast = useToast();

  const canViewSecurity = useHRMAC('core.audit_logs.security_logs.view');
  const canViewQueue    = useHRMAC('core.audit_logs.queue_monitor.view');
  const canExportLogs   = useHRMAC('core.audit_logs.activity_logs.export');
  const canExportSec    = useHRMAC('core.audit_logs.security_logs.view') && useHRMAC('core.audit_logs.security_logs.export');
  const canRetry        = useHRMAC('core.audit_logs.queue_monitor.retry');
  const canFlush        = useHRMAC('core.audit_logs.queue_monitor.flush');

  const [tab, setTab]         = useState(initialTab || 'business');
  const [search, setSearch]   = useState(filters?.search ?? '');
  const [eventType, setEvent] = useState(filters?.event_type ?? '');
  const [dateFrom, setFrom]   = useState(filters?.date_from ?? '');
  const [dateTo, setTo]       = useState(filters?.date_to ?? '');

  const [tableLoading, setTableLoading] = useState(false);
  const [retrying, setRetrying] = useState(null);
  const [flushing, setFlushing] = useState(false);

  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const isQueue = tab === 'queues';
  const isSecurity = tab === 'security';

  const reload = (next = {}) => {
    const params = { tab, ...next };
    if (!isQueue) {
      if (search)    params.search     = search;
      if (eventType) params.event_type = eventType;
      if (dateFrom)  params.date_from  = dateFrom;
      if (dateTo)    params.date_to    = dateTo;
    }
    router.get(route('core.audit-logs.index'), params, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const switchTab = next => {
    setTab(next);
    router.get(route('core.audit-logs.index'), { tab: next }, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const applyFilters = () => reload({ page: 1 });
  const resetFilters = () => {
    setSearch(''); setEvent(''); setFrom(''); setTo('');
    router.get(route('core.audit-logs.index'), { tab }, {
      preserveState: true, preserveScroll: true, only: ['logs', 'filters', 'tab', 'stats'],
    });
  };

  const exportLogs = () => {
    const params = {};
    if (eventType) params.event_type = eventType;
    if (dateFrom)  params.date_from  = dateFrom;
    if (dateTo)    params.date_to    = dateTo;
    window.open(route('core.audit-logs.export', params), '_blank');
  };

  const retryJob = id => {
    setRetrying(id);
    router.post(route('core.audit-logs.queues.retry', id), {}, {
      preserveState: true, preserveScroll: true,
      onSuccess: () => toast.success('Job queued for retry.'),
      onError:   () => toast.error('Failed to retry job.'),
      onFinish:  () => setRetrying(null),
    });
  };

  const flushAll = () => {
    if (!confirm('Permanently delete all failed jobs? This cannot be undone.')) return;
    setFlushing(true);
    router.post(route('core.audit-logs.queues.flush'), {}, {
      preserveState: true, preserveScroll: true,
      onSuccess: () => toast.success('All failed jobs flushed.'),
      onError:   () => toast.error('Failed to flush jobs.'),
      onFinish:  () => setFlushing(false),
    });
  };

  const logColumns = [
    { key: 'actor_name', label: 'Actor', width: '18%',
      render: row => <Text size="sm">{row.actor_name || row.actor_email || 'System'}</Text> },
    { key: 'event_type', label: 'Event', width: '15%',
      render: row => <Badge intent={EVENT_INTENT[row.event_type] ?? 'neutral'}>{row.event_type || '—'}</Badge> },
    { key: 'action', label: 'Action', width: '19%',
      render: row => <Text size="sm">{row.action || '—'}</Text> },
    { key: 'subject_label', label: 'Subject', width: '18%',
      render: row => <Text size="sm" tone="secondary">{truncate(row.subject_label, 40)}</Text> },
    { key: 'actor_ip', label: 'IP', width: '14%',
      render: row => <Mono size="sm">{row.actor_ip || '—'}</Mono> },
    { key: 'created_at', label: 'Time', width: '16%',
      render: row => <Mono size="sm">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</Mono> },
  ];

  const queueColumns = [
    { key: 'id', label: 'ID', width: '8%', render: row => <Mono size="sm">{row.id}</Mono> },
    { key: 'queue', label: 'Queue', width: '13%', render: row => <Badge intent="neutral">{row.queue || 'default'}</Badge> },
    { key: 'payload', label: 'Job', width: '24%',
      render: row => {
        const raw = typeof row.payload === 'string' ? row.payload : JSON.stringify(row.payload ?? {});
        return <Mono size="sm">{truncate(raw, 80)}</Mono>;
      } },
    { key: 'exception', label: 'Exception', width: '27%',
      render: row => <Text size="sm" tone="secondary">{truncate(row.exception, 100)}</Text> },
    { key: 'failed_at', label: 'Failed At', width: '14%',
      render: row => <Mono size="sm">{row.failed_at ? new Date(row.failed_at).toLocaleString() : '—'}</Mono> },
    canRetry && { key: 'actions', label: '', width: '12%', align: 'right',
      render: row => (
        <Button intent="soft" size="sm" type="button" loading={retrying === row.id}
          leftIcon="arrowPath" onClick={() => retryJob(row.id)}>Retry</Button>
      ) },
  ].filter(Boolean);

  const tabs = [
    { value: 'business', label: 'Activity' },
    { value: 'model',    label: 'Model changes' },
    { value: 'access',   label: 'Access' },
    canViewSecurity && { value: 'security', label: 'Security' },
    canViewQueue    && { value: 'queues',   label: 'Queue' },
  ].filter(Boolean);

  const rows = logs?.data ?? [];
  const hasFilter = !!(search || eventType || dateFrom || dateTo);
  const showExport = (tab === 'business' && canExportLogs) || (isSecurity && canExportSec);

  return (
    <IndexPageLayout
      title="Audit & Activity Logs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Audit Logs' },
      ]}
      description="System activity, model changes, sensitive-data access, security events, and the job queue."
      tabs={<Tabs value={tab} tabs={tabs} onChange={switchTab} />}
      actions={
        <HStack gap={2}>
          {showExport && (
            <Button intent="ghost" type="button" leftIcon="download" onClick={exportLogs}>Export</Button>
          )}
          {isQueue && canFlush && (
            <Button intent="danger" type="button" leftIcon="trash" loading={flushing} onClick={flushAll}>
              Flush all
            </Button>
          )}
        </HStack>
      }
      kpis={[
        <Stat key="today" title="Events today"     value={stats?.business_events_today ?? 0} icon="clock" />,
        <Stat key="total" title="Total events"     value={stats?.business_events_total ?? 0} icon="document" />,
        <Stat key="model" title="Model changes"    value={stats?.model_changes_today ?? 0} icon="pencil" />,
        <Stat key="acc"   title="Sensitive access" value={stats?.sensitive_accesses_today ?? 0} icon="user" iconTone="amber" />,
      ]}
      filters={
        isQueue ? null : (
          <HStack gap={3} align="end" wrap>
            <Input placeholder="Search actor, subject…" value={search} leftIcon="search"
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()} />
            <Select value={eventType} onChange={e => setEvent(e.target.value)}
              options={isSecurity ? SECURITY_EVENT_OPTIONS : EVENT_TYPE_OPTIONS} />
            <Input type="date" value={dateFrom} onChange={e => setFrom(e.target.value)} />
            <Input type="date" value={dateTo}   onChange={e => setTo(e.target.value)} />
            <Button intent="primary" type="button" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost"   type="button" onClick={resetFilters}>Reset</Button>
          </HStack>
        )
      }
      table={
        !tableLoading && rows.length === 0 ? (
          isQueue ? (
            <EmptyState icon="check" title="Queue is clean" description="No failed jobs to show." />
          ) : hasFilter ? (
            <EmptyState icon="filter" title="No matching events"
              description="Try adjusting your search, event type, or date range."
              action={<Button intent="ghost" type="button" onClick={resetFilters}>Reset filters</Button>} />
          ) : (
            <EmptyState icon="document" title="No events yet"
              description="Activity will appear here as users interact with the system." />
          )
        ) : (
          <DataTable columns={isQueue ? queueColumns : logColumns} rows={rows} loading={tableLoading} />
        )
      }
      pagination={
        logs?.last_page > 1 && (
          <Pagination page={logs.current_page} total={logs.last_page}
            onChange={page => reload({ page })} />
        )
      }
    />
  );
}

AuditLogsIndex.layout = page => <App title="Audit & Activity Logs">{page}</App>;
```

- [ ] **Step 2: Delete the folded-in pages.**

```bash
rm packages/aero-ui/resources/js/Pages/Core/AuditLogs/Security.jsx
rm packages/aero-ui/resources/js/Pages/Core/AuditLogs/Queues.jsx
```

- [ ] **Step 3: Confirm no stale imports reference the deleted pages.**

Run: `grep -rn "AuditLogs/Security\|AuditLogs/Queues" packages/aero-ui/resources/js` — expected: no matches (Inertia resolves pages by name string from the controller; the controller no longer renders them after Task 1).

- [ ] **Step 4: Verify the page compiles via the vite transform.**

With `cd c:/laragon/www/aeos365 && npm run dev` running, request the module through vite:

```bash
curl -s -o /dev/null -w "%{http_code}" "http://localhost:5173/packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx"
```

Expected: `200` (vite transformed the JSX with no syntax error). If the dev server proxies under the host, use the host's `public/hot` URL base instead.

- [ ] **Step 5: Commit.**

```bash
git add packages/aero-ui/resources/js/Pages/Core/AuditLogs/
git commit -m "Phase3/audit: unified 5-tab Audit Index (IndexPageLayout + KPI + per-tab columns/gating + Retry/Flush/Export); delete folded Security/Queues pages"
```

---

### Task 3: Collapse the Audit nav 3→1

**Files:**
- Modify: `packages/aero-core/config/module.php:371` (the `audit_logs` submodule)

**Interfaces:**
- Consumes: the `collapse_nav` flag honored by BOTH `AbstractModuleProvider::registerNavigation` and `AeroCoreServiceProvider::registerCoreNavigation` (last-wins registry — both must see it, which they do).

- [ ] **Step 1: Add the flag.**

In `config/module.php`, in the `audit_logs` submodule (the array with `'code' => 'audit_logs'`, ~line 372), add `'collapse_nav' => true,` as a sibling key, immediately after `'priority' => 6,`:

```php
        [
            'code' => 'audit_logs',
            'name' => 'Audit & Activity Logs',
            'description' => 'View system activity, user actions, and security events',
            'icon' => 'ClipboardDocumentListIcon',
            'route' => '/audit-logs',
            'priority' => 6,
            'collapse_nav' => true,

            'components' => [
```

(The collapsed leaf uses `'path' => $submodule['route']` = `/audit-logs`, `access` = `core.audit_logs` — admin visibility unchanged.)

- [ ] **Step 2: Clear config + app caches on the host so the change is served.**

```bash
cd c:/laragon/www/aeos365 && php artisan config:clear && php artisan cache:clear
```

Expected: both report cleared. (The `getNavigationProps` path reads `toFrontend` live, but clear anyway to match the Settings/Org procedure.)

- [ ] **Step 3: Verify the nav collapsed via the authenticated nav prop.**

Log in to `democorp.aeos365.test` as admin, then in the browser console / via Playwright read the page prop:

```js
JSON.parse(document.getElementById('app').dataset.page).props.navigation
```

Expected: the Audit entry has `childCount` 0 (no children array or empty), and exactly ONE nav link with `path` `/audit-logs`. No `/audit-logs/security` or `/audit-logs/queues` nav links.

- [ ] **Step 4: Commit.**

```bash
git add packages/aero-core/config/module.php
git commit -m "Phase3/audit: collapse Audit nav 3->1 (collapse_nav flag on audit_logs)"
```

---

### Task 4: Activity Feed — promote to a nav-visible core submodule + reconcile HRMAC gate

**Files:**
- Modify: `packages/aero-core/config/module.php` (remove `activity_feed` component from `comments_mentions` ~line 1148; add a new `activity_feed` submodule near the audit block)
- Modify: `packages/aero-core/routes/web.php:982-995` (gate codes)

**Interfaces:**
- Produces: a core submodule `activity_feed` with one component `feed` (actions `view`, `export`) → HRMAC codes `core.activity_feed.feed.view` / `core.activity_feed.feed.export`; nav access `core.activity_feed.feed` (single-component → direct page leaf in `registerCoreNavigation`).

- [ ] **Step 1: Remove the orphaned `activity_feed` component from `comments_mentions`.**

In `config/module.php`, delete the `activity_feed` component block (~lines 1148-1154) from the `comments_mentions` submodule's `components` array (the block `['code' => 'activity_feed', 'name' => 'Activity Feed', 'type' => 'page', 'route' => '/activity', 'actions' => [...]]`). Leave `comments` and `mentions_inbox` intact.

- [ ] **Step 2: Add a first-class `activity_feed` submodule.**

Add this submodule to the core `submodules` array, immediately after the `audit_logs` block (after its closing `],` ~line 416, before the `notifications` block ~line 423):

```php
        /*
        |--------------------------------------------------------------------------
        | 1.5b Activity Feed
        |--------------------------------------------------------------------------
        */
        [
            'code' => 'activity_feed',
            'name' => 'Activity Feed',
            'description' => 'Cross-module activity timeline of user and system actions',
            'icon' => 'ClockIcon',
            'route' => '/activity',
            'priority' => 7,
            'components' => [
                [
                    'code' => 'feed',
                    'name' => 'Activity Feed',
                    'type' => 'page',
                    'route' => '/activity',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Activity Feed'],
                        ['code' => 'export', 'name' => 'Export Activities'],
                    ],
                ],
            ],
        ],
```

(Single component → `registerCoreNavigation` emits one leaf link `name: Activity Feed`, `path: /activity`, `access: core.activity_feed.feed`.)

- [ ] **Step 3: Update the route gates to the reconciled 4-segment codes.**

In `routes/web.php`, the `core.activity.*` group (~982): change the three view gates from `hrmac:core.activity_feed.view` to `hrmac:core.activity_feed.feed.view`, and the export gate from `hrmac:core.activity_feed.export` to `hrmac:core.activity_feed.feed.export`:

```php
    Route::prefix('activity')->name('core.activity.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('index');
        Route::get('/{id}', [ActivityController::class, 'show'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('show');
        Route::get('/stats', [ActivityController::class, 'stats'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('stats');
        Route::get('/export', [ActivityController::class, 'export'])
            ->middleware('hrmac:core.activity_feed.feed.export')->name('export');
    });
```

- [ ] **Step 4: Sync the HRMAC hierarchy so the new permission nodes exist, then clear caches.**

```bash
cd c:/laragon/www/aeos365 && php artisan aero:sync-module --scope=tenant && php artisan config:clear && php artisan cache:clear
```

Expected: sync reports the core module synced (creates `activity_feed/feed/view` + `/export` nodes, prunes the old `comments_mentions/activity_feed`). No errors.

- [ ] **Step 5: Verify the nav home + access live.**

As admin on `democorp.aeos365.test`:
- Read `JSON.parse(document.getElementById('app').dataset.page).props.navigation` — expect a single "Activity Feed" link with `path` `/activity` present in the core group.
- Click that nav link (or visit `/activity`) — expect HTTP 200 (not 403), the page renders. (Super-admin bypasses gates; this confirms the route still resolves with the new code and the nav link exists.)

> **Fallback (only if Step 4/5 surfaces a real failure — e.g. sync errors or the gate 403s for admin):** revert the route-gate edits (Step 3) back to the 3-segment `core.activity_feed.view`/`.export`, keep the new submodule for nav visibility only, and log the gate-code mismatch to the systemic HRMAC-uniformity audit. Do NOT spend the iteration debugging HRMAC seeding — surface it and move on.

- [ ] **Step 6: Commit.**

```bash
git add packages/aero-core/config/module.php packages/aero-core/routes/web.php
git commit -m "Phase3/activity: promote activity_feed to a nav-visible core submodule + reconcile HRMAC gate to core.activity_feed.feed.*"
```

---

### Task 5: Redesign the Activity Feed Index + Show pages

**Files:**
- Modify (full rewrite): `packages/aero-ui/resources/js/Pages/Core/Activity/Index.jsx`
- Modify (full rewrite): `packages/aero-ui/resources/js/Pages/Core/Activity/Show.jsx`

**Interfaces:**
- Consumes: `Index` props `{ title, activities (paginator), stats: { total_activities, today_activities, week_activities }, filters: { module, action, ... } }`; `Show` props `{ title, activity }`. HRMAC export code `core.activity_feed.feed.export`.

- [ ] **Step 1: Rewrite `Activity/Index.jsx` onto the resource canon.**

```jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Input,
  Select,
  Text,
  Mono,
  Stat,
  EmptyState,
  Menu,
  useHRMAC,
} from '@aero/ui';
import { EllipsisHorizontalIcon } from '@heroicons/react/24/outline';
import App from '@/Pages/App.jsx';

const ACTION_INTENT = {
  created: 'success', updated: 'neutral', deleted: 'danger',
  login: 'success', logout: 'neutral', export: 'warning', import: 'warning',
};

const ACTION_OPTIONS = [
  { value: '', label: 'All Actions' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
];

const MODULE_OPTIONS = [
  { value: '', label: 'All Modules' },
  { value: 'users', label: 'Users' },
  { value: 'roles', label: 'Roles' },
  { value: 'tags', label: 'Tags' },
  { value: 'settings', label: 'Settings' },
];

export default function ActivityIndex({ activities, stats, filters }) {
  const canExport = useHRMAC('core.activity_feed.feed.export');

  const [module, setModule] = useState(filters?.module ?? '');
  const [action, setAction] = useState(filters?.action ?? '');

  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const reload = (next = {}) => {
    const params = { ...next };
    if (module) params.module = module;
    if (action) params.action = action;
    router.get(route('core.activity.index'), params, {
      preserveState: true, preserveScroll: true, only: ['activities', 'filters', 'stats'],
    });
  };

  const applyFilters = () => reload({ page: 1 });
  const resetFilters = () => {
    setModule(''); setAction('');
    router.get(route('core.activity.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['activities', 'filters', 'stats'],
    });
  };

  const exportFeed = () => window.open(route('core.activity.export', { module, action }), '_blank');

  const columns = [
    { key: 'actor', label: 'Actor', width: '18%',
      render: row => <Text size="sm">{row.user?.name || 'System'}</Text> },
    { key: 'description', label: 'Description', width: '34%',
      render: row => <Text size="sm">{row.description || '—'}</Text> },
    { key: 'action', label: 'Action', width: '14%',
      render: row => <Badge intent={ACTION_INTENT[row.action] ?? 'neutral'}>{row.action || '—'}</Badge> },
    { key: 'module', label: 'Module', width: '14%',
      render: row => row.module ? <Badge intent="indigo" size="sm">{row.module}</Badge> : <Text tone="tertiary" size="sm">—</Text> },
    { key: 'created_at', label: 'Time', width: '16%',
      render: row => <Mono size="sm">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</Mono> },
    { key: 'actions', label: '', width: '60px', align: 'right',
      render: row => (
        <Menu align="end"
          trigger={<Button intent="ghost" size="sm" aria-label="More actions"><EllipsisHorizontalIcon className="aeos-icon-sm" /></Button>}
          items={[{ label: 'View details', onClick: () => router.visit(route('core.activity.show', row.id)) }]} />
      ) },
  ];

  const rows = activities?.data ?? [];
  const hasFilter = !!(module || action);

  return (
    <IndexPageLayout
      title="Activity Feed"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Activity Feed' },
      ]}
      description="Cross-module timeline of user and system actions."
      actions={canExport && (
        <Button intent="ghost" type="button" leftIcon="download" onClick={exportFeed}>Export</Button>
      )}
      kpis={[
        <Stat key="total" title="Total activities" value={stats?.total_activities ?? 0} icon="document" />,
        <Stat key="today" title="Today"            value={stats?.today_activities ?? 0} icon="clock" />,
        <Stat key="week"  title="This week"        value={stats?.week_activities ?? 0} icon="calendar" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap>
          <Select value={module} onChange={e => setModule(e.target.value)} options={MODULE_OPTIONS} />
          <Select value={action} onChange={e => setAction(e.target.value)} options={ACTION_OPTIONS} />
          <Button intent="primary" type="button" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   type="button" onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        !tableLoading && rows.length === 0 ? (
          hasFilter ? (
            <EmptyState icon="filter" title="No matching activity"
              description="Try adjusting the module or action filter."
              action={<Button intent="ghost" type="button" onClick={resetFilters}>Reset filters</Button>} />
          ) : (
            <EmptyState icon="document" title="No activity yet"
              description="Activity will appear here as users interact with the system." />
          )
        ) : (
          <DataTable columns={columns} rows={rows} loading={tableLoading} />
        )
      }
      pagination={
        activities?.last_page > 1 && (
          <Pagination page={activities.current_page} total={activities.last_page}
            onChange={page => reload({ page })} />
        )
      }
    />
  );
}

ActivityIndex.layout = page => <App title="Activity Feed">{page}</App>;
```

- [ ] **Step 2: Rewrite `Activity/Show.jsx` onto the `App` layout (drop `DashboardLayout`/`Icon`-className).**

```jsx
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Mono,
  Badge,
  Button,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ACTION_INTENT = {
  created: 'success', updated: 'neutral', deleted: 'danger',
  login: 'success', logout: 'neutral', export: 'warning', import: 'warning',
};

function Field({ label, children }) {
  return (
    <VStack gap={1}>
      <Text tone="secondary" size="sm">{label}</Text>
      <Text size="sm">{children}</Text>
    </VStack>
  );
}

export default function ActivityShow({ activity }) {
  return (
    <IndexPageLayout
      title="Activity details"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Activity Feed', href: route('core.activity.index') },
        { label: 'Details' },
      ]}
      description={activity?.description || ''}
      actions={
        <Button intent="ghost" type="button" leftIcon="arrowLeft"
          onClick={() => router.visit(route('core.activity.index'))}>Back to feed</Button>
      }
      table={
        <VStack gap={4}>
          <Card>
            <CardContent>
              <HStack gap={2} align="center" wrap>
                <Text size="lg" weight={600}>{activity?.description || '—'}</Text>
                <Badge intent={ACTION_INTENT[activity?.action] ?? 'neutral'}>{activity?.action || '—'}</Badge>
                {activity?.module && <Badge intent="indigo" size="sm">{activity.module}</Badge>}
              </HStack>
              <Text tone="secondary" size="sm">
                {activity?.created_at ? new Date(activity.created_at).toLocaleString() : '—'}
              </Text>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight={600} size="md">Details</Text>
                <Field label="User">{activity?.user?.name || 'System'}</Field>
                {activity?.tenant && <Field label="Tenant">{activity.tenant.name}</Field>}
                {activity?.module && <Field label="Module">{activity.module}</Field>}
                {activity?.entity_type && <Field label="Entity type">{activity.entity_type}</Field>}
                {activity?.entity_id && <Field label="Entity ID"><Mono size="sm">{activity.entity_id}</Mono></Field>}
                <Field label="IP address"><Mono size="sm">{activity?.ip_address || 'N/A'}</Mono></Field>
                <Field label="User agent">{activity?.user_agent || 'N/A'}</Field>
                {activity?.metadata && Object.keys(activity.metadata).length > 0 && (
                  <VStack gap={1}>
                    <Text tone="secondary" size="sm">Metadata</Text>
                    <Card>
                      <CardContent>
                        <VStack gap={2}>
                          {Object.entries(activity.metadata).map(([k, v]) => (
                            <HStack key={k} gap={2}>
                              <Text tone="secondary" size="sm">{k}:</Text>
                              <Text size="sm">{String(v)}</Text>
                            </HStack>
                          ))}
                        </VStack>
                      </CardContent>
                    </Card>
                  </VStack>
                )}
              </VStack>
            </CardContent>
          </Card>
        </VStack>
      }
    />
  );
}

ActivityShow.layout = page => <App title="Activity details">{page}</App>;
```

- [ ] **Step 3: Verify both pages compile via the vite transform.**

With the dev server up:

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:5173/packages/aero-ui/resources/js/Pages/Core/Activity/Index.jsx"
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:5173/packages/aero-ui/resources/js/Pages/Core/Activity/Show.jsx"
```

Expected: `200` for both.

- [ ] **Step 4: Commit.**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Activity/
git commit -m "Phase3/activity: redesign Activity Feed Index + Show onto the resource canon (IndexPageLayout + KPI + filters + table; drop DashboardLayout)"
```

---

### Task 6: Live Playwright sweep + code review

**Files:** none (verification + fixes only).

- [ ] **Step 1: Bring up the dev server.**

```bash
cd c:/laragon/www/aeos365 && npm run dev
```

Confirm `public/hot` exists. Log in at `democorp.aeos365.test` as `admin@democorp.com` / `Aeos365!Admin`.

- [ ] **Step 2: Exercise the Audit page via the real nav link.**

Click the single "Audit & Activity Logs" nav link (confirm there is exactly one). For EACH tab (Activity / Model changes / Access / Security / Queue):
- Tab switches in place (URL `?tab=` updates, no full page reload, the page component does not unmount).
- 0 console errors and 0 console warnings (watch for the Icon "Unknown icon name" warning — any occurrence is a fail; fix the icon name).
- KPI strip reflects `stats` (non-placeholder numbers).
- Filter + Reset on the log tabs issue exactly ONE request each (check the network panel).
- Pagination (if `last_page > 1`) issues one request and preserves the active tab.

- [ ] **Step 3: Exercise the Queue actions.**

On the Queue tab, if any failed job exists: Retry fires exactly ONE POST and toasts; Flush-all (after the confirm) fires exactly ONE POST. Buttons disable while in-flight (no double-submit). If no failed jobs, confirm the "Queue is clean" empty state.

- [ ] **Step 4: Exercise the Activity Feed.**

Click the "Activity Feed" nav link (confirm it exists and resolves 200). Filter/Reset issue one request each; "View details" opens the Show page; Show renders cleanly (0 console errors, no unknown-icon warning); "Back to feed" returns.

- [ ] **Step 5: Theme Studio sweep.**

Open Theme Studio; toggle card-style (flat / glass / gradient-border) and a non-default accent. Confirm on both the Audit and Activity pages that card-style reaches the KPI cards AND the DataTable container surface, and the accent drives the active tab + primary buttons. Toggle density/radius/borders/motion and confirm no surface is left unstyled.

- [ ] **Step 6: Confirm nav shape via the authenticated prop.**

```js
JSON.parse(document.getElementById('app').dataset.page).props.navigation
```

Expect: Audit entry `childCount` 0 + one `/audit-logs` link; one `/activity` "Activity Feed" link; no `/audit-logs/security`, `/audit-logs/queues` nav links.

- [ ] **Step 7: Run code review and fix Critical/Important.**

Use `superpowers:requesting-code-review` over the branch diff (base = the commit before Task 1). Address every Critical and Important finding; re-verify any fixed file live. Record Minor/out-of-scope items in the SDD ledger.

- [ ] **Step 8: Final commit (if review fixes were made).**

```bash
git add -A
git commit -m "Phase3/audit-activity: address code-review findings"
```

---

## Self-Review

**Spec coverage:**
- Unified 5-tab Audit page (partial reloads, KPI, per-tab gating, columns, Retry/Flush/Export, skeleton, EmptyState) → Tasks 1+2. ✓
- Data-shape fix (full paginators; `date_from`/`date_to` param names) → Task 1 Steps 1-3 + Task 2 filter params. ✓
- Nav collapse 3→1 (both paths, cache clear, nav-prop verify) → Task 3. ✓
- Activity Feed redesign (Index + Show off DashboardLayout) → Task 5. ✓
- Activity Feed nav home + gate reconcile (submodule promote, route gates, sync, fallback) → Task 4. ✓
- Compliance (icons, no inline style, Inertia v2, theme, type=button) → Global Constraints + enforced in each page's code + Task 6 sweep. ✓
- Live verification (Playwright, nav prop) → Task 6. ✓

**Placeholder scan:** no TBD/TODO; every code step shows complete code; the icon registry is enumerated verbatim. ✓

**Type consistency:** controller produces full paginator `logs` with `.data/.current_page/.last_page/.total` (Task 1) — consumed as exactly those keys in Task 2 + Task 5 (`activities` paginator). HRMAC codes consistent: view `core.audit_logs.{activity_logs|security_logs|queue_monitor}.view`, queue `.retry/.flush`, activity `core.activity_feed.feed.{view|export}`. Route names hyphenated (`core.audit-logs.*`, `core.activity.*`), codes underscored. ✓

**Known nuance:** Export on the Activity/Security tabs uses the existing GET `core.audit-logs.export` (audit_logs, filtered by event_type/date) — shown only on the `business` and `security` tabs where that endpoint's data matches; hidden on `model`/`access` (no matching export endpoint) and `queues`. This is intentional and noted as a minor follow-up, not a gap.
