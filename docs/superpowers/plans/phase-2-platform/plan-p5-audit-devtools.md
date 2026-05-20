# Plan P-5 — Platform Admin: Audit & Developer Tools

**Phase:** 2 — Platform Admin
**Package:** `packages/aero-platform/`
**Status:** Pending

---

## 1. Scope

Read-only viewers and lightweight operational tooling for landlord-side observability:

- **Platform audit log viewer** — browse and export entries from `platform_audit_logs`.
- **PII access log viewer** — browse and export entries from `platform_access_logs`, with a dedicated PII tab gated by its own permission.
- **Error log management** — list/show/resolve/bulk-resolve and delete entries in `error_logs`, plus aggregated statistics.
- **Developer tools** — cache management (clear by tag, view stats), queue management (counts, retry failed), system log viewer (list files, tail, download), maintenance settings toggle.

All features run under the `landlord` guard and operate against the central database.

---

## 2. Architecture

- **Package**: `packages/aero-platform/`
- **Models**: extend `Aero\Contracts\Models\CentralModel`
- **Auth guard**: `landlord`
- **Routes file**: `packages/aero-platform/routes/admin.php`
- **Inertia pages**: `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`
- **HRMAC**: `hrmac:{submodule}.{component}.{action}` (3 levels)
- **Audit**: `$this->audit->log(event:, action:, subject:, description:)` via `Aero\Contracts\AuditServiceInterface`
- **All writes**: wrapped in `DB::transaction()`
- **React**: `@aero/ui` only, no inline styles, no `window.confirm`
- **Import depths** (depth 4): `App=` `'../../../App.jsx'`, `useHRMAC=` `'../../../../hooks/useHRMAC.js'`
- **Tests**: `Gate::before(fn () => true)`

---

## 3. HRMAC Codes

```
audit-logs.audit-log-list.view
audit-logs.audit-log-list.export

access-logs.access-log-list.view
access-logs.access-log-list.export
access-logs.pii-access.view
access-logs.pii-access.export

error-monitoring.error-log-list.view
error-monitoring.error-log-list.resolve
error-monitoring.error-log-list.delete
error-monitoring.error-analytics.view

developer-tools.cache-management.view
developer-tools.cache-management.clear
developer-tools.queue-management.view
developer-tools.queue-management.manage
developer-tools.log-viewer.view
developer-tools.log-viewer.download
developer-tools.maintenance-settings.view
developer-tools.maintenance-settings.toggle
```

These must be registered in `packages/aero-platform/config/module.php` under the `audit-logs`, `access-logs`, `error-monitoring`, and `developer-tools` submodules.

---

## 4. Data Model

All models are **read-only viewers** for tables that already exist (populated by `AuditService`, `ErrorReportingService`, etc.). No new migrations required.

### PlatformAuditLog (read-only)
- Table: `platform_audit_logs`
- Columns: `id`, `tenant_id` nullable, `user_id`, `event`, `action`, `subject_type`, `subject_id`, `description`, `ip_address`, `user_agent`, `created_at`

### PlatformAccessLog (read-only)
- Table: `platform_access_logs`
- Columns: `id`, `tenant_id` nullable, `user_id`, `resource_type`, `resource_id`, `field_accessed`, `ip_address`, `created_at`

### ErrorLog (read-only for viewer; resolve/delete mutate status)
- Table: `error_logs`
- Columns: `id`, `tenant_id` nullable, `level`, `message`, `context` (json), `file`, `line`, `stack_trace`, `status` (open/resolved), `resolved_at`, `resolved_by`, `created_at`

---

## 5. Services

### `PlatformAuditLogService`
- `list(array $filters)` — paginated; filters: `tenant_id`, `user_id`, `event`, `date_from`, `date_to`
- `show(int $id)`
- `export(array $filters)` — streamed CSV

### `PlatformAccessLogService`
- `list(array $filters)` — filters: `tenant_id`, `resource_type`, `date_from`, `date_to`
- `show(int $id)`
- `piiAccess(array $filters)` — restricted subset where `field_accessed` is in the PII whitelist
- `export(array $filters)`

### `ErrorLogAdminService`
- `list(array $filters)` — filters: `level`, `status`, `tenant_id`
- `show(int $id)`
- `resolve(int $id, int $resolverId)`
- `bulkResolve(array $ids, int $resolverId)`
- `delete(int $id)`
- `statistics()` — counts by level / status / 24h trend

### `DeveloperToolsService`
- `clearCache(?array $tags = null)`
- `queueStats()` — pending, processing, failed counts per queue
- `clearFailedJobs()`
- `retryFailedJobs(?array $ids = null)`
- `getLogFiles()` — list `storage/logs/*.log` with size + mtime
- `downloadLog(string $filename)`
- `tailLog(string $filename, int $lines = 200)`

All mutating service methods wrap writes in `DB::transaction()` and emit `audit->log()`.

---

## 6. Controllers

### `PlatformAuditLogController`
- `index` — paginated list + filter UI props
- `show($id)`
- `export(Request)` — returns streamed CSV

### `PlatformAccessLogController`
- `index`, `show`
- `piiIndex` — PII-only listing
- `export`

### `ErrorLogController`
- `index`, `show`
- `resolve($id)`, `bulkResolve(Request)`
- `destroy($id)`
- `statistics`

### `DeveloperToolsController`
- `cacheIndex`, `cacheClear`
- `queueIndex`, `queueRetry`
- `logIndex`, `logDownload`

Controllers stay thin — delegate to services, validate via Form Requests.

---

## 7. Routes (`packages/aero-platform/routes/admin.php`)

```
GET    /audit-logs                       hrmac:audit-logs.audit-log-list.view
GET    /audit-logs/{id}                  hrmac:audit-logs.audit-log-list.view
GET    /audit-logs/export                hrmac:audit-logs.audit-log-list.export

GET    /access-logs                      hrmac:access-logs.access-log-list.view
GET    /access-logs/pii                  hrmac:access-logs.pii-access.view
GET    /access-logs/export               hrmac:access-logs.access-log-list.export

GET    /error-logs                       hrmac:error-monitoring.error-log-list.view
GET    /error-logs/{id}                  hrmac:error-monitoring.error-log-list.view
POST   /error-logs/{id}/resolve          hrmac:error-monitoring.error-log-list.resolve
POST   /error-logs/bulk-resolve          hrmac:error-monitoring.error-log-list.resolve
DELETE /error-logs/{id}                  hrmac:error-monitoring.error-log-list.delete

GET    /developer/cache                  hrmac:developer-tools.cache-management.view
POST   /developer/cache/clear            hrmac:developer-tools.cache-management.clear
GET    /developer/queues                 hrmac:developer-tools.queue-management.view
POST   /developer/queues/retry-failed    hrmac:developer-tools.queue-management.manage
GET    /developer/logs                   hrmac:developer-tools.log-viewer.view
GET    /developer/logs/download          hrmac:developer-tools.log-viewer.download
```

All routes registered under the `landlord` auth guard with the platform admin prefix (e.g. `/admin/platform/`).

---

## 8. React Pages

Located at `packages/aero-ui/resources/js/Pages/Platform/Admin/`:

1. **`AuditLogs/Index.jsx`** — filterable table (event, user, tenant, date range), row click opens detail.
2. **`AuditLogs/Show.jsx`** — full log entry detail with formatted payload.
3. **`AccessLogs/Index.jsx`** — access events table with PII tab toggle and resource type filter.
4. **`ErrorLogs/Index.jsx`** — error table with level badges, status filter, multi-select + bulk resolve.
5. **`ErrorLogs/Show.jsx`** — full stack trace, context viewer, resolve button.
6. **`Developer/Tools.jsx`** — tabbed interface: **Cache** (clear button + stats), **Queues** (job counts + retry failed), **Logs** (file list + tail + download).

Import depths (depth 4):
- `App` → `'../../../App.jsx'`
- `useHRMAC` → `'../../../../hooks/useHRMAC.js'`

All UI components sourced from `@aero/ui`. Confirmations use `<ConfirmDialog>` from `@aero/ui` — never `window.confirm`.

---

## 9. Tests (`packages/aero-platform/tests/`)

All Feature tests use `Gate::before(fn () => true)` to bypass policy gates for permission-focused tests.

- `PlatformAuditLogTest` — can list audit logs with date-range filter; export returns CSV stream.
- `PlatformAccessLogTest` — PII access tab requires `access-logs.pii-access.view` permission (403 without).
- `ErrorLogTest` — can bulk resolve error logs; resolve transitions status to `resolved` and stamps `resolved_by`.
- `DeveloperToolsTest` — cache clear returns success; queue retry-failed re-enqueues failed jobs; log download streams correct file.

---

## 10. Acceptance / Done Definition

- HRMAC entries added to `packages/aero-platform/config/module.php` and resolvable.
- All routes guarded by `landlord` auth + correct HRMAC middleware.
- All mutating actions emit audit log entries.
- React pages render under platform admin layout, no inline styles, all confirmations via `@aero/ui`.
- PHPUnit Feature tests pass with `Gate::before(fn () => true)`.
- Master plan updated to mark P-5 complete.
