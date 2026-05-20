# Plan P-10: Infrastructure & Security

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Landlord-side infrastructure & security ops — white-label per tenant (custom domains, SSL, branding, DKIM), backup & restore, status page & incident management, platform security (RBAC, impersonation audit, staff MFA/SSO, IP allowlist), and security center (pentests, security incidents, bug bounty).
**Architecture:** All code in `packages/aero-platform/`. Models extend `Aero\Contracts\Models\CentralModel`. Routes under `landlord` guard in `packages/aero-platform/routes/admin.php`. Inertia pages under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`. DKIM private keys encrypted via `EncryptedField`.
**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, @aero/ui, PHPUnit 11

---

## 1. HRMAC Hierarchy

```
white-label.custom-domains.view / .add / .verify / .remove
white-label.ssl-provisioning.view / .provision / .renew
white-label.tenant-branding.view / .manage
white-label.custom-css.view / .edit
white-label.tenant-email-branding.view / .configure / .verify

backup-restore.backup-dashboard.view
backup-restore.backup-schedules.view / .create / .update / .delete
backup-restore.manual-backups.view / .create
backup-restore.restore.view / .restore / .pitr
backup-restore.backup-storage.view / .configure
backup-restore.retention-policies.view / .manage

status-incidents.status-page.view / .configure
status-incidents.service-components.view / .manage / .set-status
status-incidents.incidents.view / .create / .update / .resolve
status-incidents.postmortems.view / .create / .publish
status-incidents.sla-reporting.view / .export
status-incidents.uptime-monitoring.view / .configure

platform-security.landlord-roles.view / .create / .update / .delete / .assign
platform-security.impersonation.view / .start / .end / .audit
platform-security.staff-sessions.view / .force-logout
platform-security.staff-mfa.view / .enforce / .reset
platform-security.staff-sso.view / .configure
platform-security.ip-allowlist.view / .manage

security-center.security-dashboard.view
security-center.security-incidents.view / .create / .notify
security-center.pentest-reports.view / .upload / .share
```

---

## 2. Data Model

`packages/aero-platform/src/Models/Infra/` — all extend `Aero\Contracts\Models\CentralModel`.

| Model | Key Columns |
|-------|-------------|
| `TenantCustomDomain` | `tenant_id` FK, `domain`, `status` (pending/verified/failed), `ssl_status` (none/provisioning/active/expired), `verified_at`, `ssl_expires_at`, `dns_txt_record` |
| `TenantBranding` | `tenant_id` FK (unique), `logo_path` nullable, `favicon_path` nullable, `primary_color`, `secondary_color`, `custom_css_path` nullable, `email_from_name` nullable, `email_from_address` nullable, `dkim_selector` nullable, `dkim_private_key` (encrypted) nullable |
| `BackupSchedule` | `tenant_id` FK nullable, `frequency` (daily/weekly/monthly), `time_of_day`, `retention_days`, `storage_backend_code`, `is_active`, `last_run_at` |
| `BackupRecord` | `tenant_id` FK, `schedule_id` FK nullable, `size_bytes`, `storage_path`, `storage_backend`, `status` (running/completed/failed), `completed_at` |
| `StatusPageComponent` | `name`, `description`, `status` (operational/degraded/partial_outage/major_outage), `order_index` |
| `StatusIncident` | `title`, `status` (investigating/identified/monitoring/resolved), `impact` (minor/major/critical), `component_ids` json, `started_at`, `resolved_at`, `updates` json |
| `StatusPagePostmortem` | `incident_id` FK, `title`, `content_md`, `published_at` nullable, `created_by` FK |
| `SecurityIncident` | `title`, `severity` (low/medium/high/critical), `description`, `status` (open/investigating/resolved), `affected_tenants` json nullable, `resolved_at`, `notified_tenants_at` nullable |
| `PentestReport` | `title`, `conducted_by`, `conducted_at`, `severity_summary` json, `file_path`, `is_public`, `shared_with_tenants` json nullable |
| `StaffIpAllowlist` | `ip_cidr`, `label`, `created_by` FK, `is_active` |

Encryption: `tenant_branding.dkim_private_key` uses `EncryptedField`.

---

## 3. Services

`packages/aero-platform/src/Services/Infra/`

- **`CustomDomainService`** — `addDomain`, `verifyDns(domain)` (TXT lookup), `provisionSsl` (Let's Encrypt via background job), `renewSsl`, `removeDomain`.
- **`TenantBrandingService`** — `getForTenant`, `update`, `uploadLogo`, `configureDkim`, `verifyDkim`.
- **`BackupService`** — `createSchedule`, `runManual(tenant)`, `restore(backup, targetTenant)`, `listRestorePoints`, `configureStorage`.
- **`StatusPageService`** — `getComponents`, `setComponentStatus`, `createIncident`, `updateIncident`, `resolveIncident`, `createPostmortem`.
- **`PlatformSecurityService`** — `listSessions`, `forceLogout`, `getMfaStatus`, `enforceMfa`, `resetMfa`, `configureSso`, `manageIpAllowlist`.
- **`SecurityCenterService`** — `createIncident`, `notifyTenants(incident)`, `uploadPentest`, `shareReport(report, tenants)`.

All inject `AuditServiceInterface`; mutations in `DB::transaction()`. Impersonation start/end logs to audit with full session metadata.

---

## 4. Controllers

`packages/aero-platform/src/Http/Controllers/Admin/Infra/`

| Controller | Actions |
|------------|---------|
| `WhiteLabelController` | `domains` (index/add/verify/remove), `ssl` (provision/renew), `branding` (show/update), `css` (show/update), `emailBranding` (configure/verify) |
| `BackupController` | `dashboard`, `schedules` (CRUD), `manualBackup`, `restore`, `storage` (show/configure), `retention` (show/manage) |
| `StatusPageController` | `index` (config), `components` (CRUD/setStatus), `incidents` (CRUD/updates/resolve), `postmortems` (CRUD/publish), `sla`, `uptime` |
| `PlatformSecurityController` | `roles` (CRUD/assign), `impersonation` (audit log/force-end), `sessions` (list/forceLogout), `mfa` (status/enforce/reset), `sso` (show/configure), `ipAllowlist` (CRUD) |
| `SecurityCenterController` | `dashboard`, `incidents` (CRUD/notify), `pentests` (list/upload/share), `vulnerabilities`, `bugBounty` |

---

## 5. Routes

`packages/aero-platform/routes/admin.php`:

```php
Route::middleware(['auth:landlord'])->prefix('admin')->name('admin.')->group(function () {
    // White-label
    Route::middleware('hrmac:white-label.custom-domains.view')->get('white-label/domains', [WhiteLabelController::class, 'domainsIndex'])->name('wl.domains');
    Route::middleware('hrmac:white-label.custom-domains.verify')->post('white-label/domains/{domain}/verify', [WhiteLabelController::class, 'verifyDomain'])->name('wl.domains.verify');
    Route::middleware('hrmac:white-label.ssl-provisioning.provision')->post('white-label/domains/{domain}/ssl', [WhiteLabelController::class, 'provisionSsl'])->name('wl.ssl.provision');
    // ... branding, css, email-branding

    // Backup
    Route::middleware('hrmac:backup-restore.backup-dashboard.view')->get('backup', [BackupController::class, 'dashboard'])->name('backup.dashboard');
    Route::middleware('hrmac:backup-restore.manual-backups.create')->post('backup/manual', [BackupController::class, 'manualBackup'])->name('backup.manual');
    Route::middleware('hrmac:backup-restore.restore.restore')->post('backup/{backup}/restore', [BackupController::class, 'restore'])->name('backup.restore');
    // ... schedules, storage, retention

    // Status / Incidents
    Route::middleware('hrmac:status-incidents.incidents.create')->post('status/incidents', [StatusPageController::class, 'storeIncident'])->name('status.incidents.store');
    // ... components, postmortems, sla, uptime

    // Platform Security
    Route::middleware('hrmac:platform-security.staff-sessions.force-logout')->post('security/sessions/{session}/logout', [PlatformSecurityController::class, 'forceLogout'])->name('security.sessions.logout');
    Route::middleware('hrmac:platform-security.ip-allowlist.manage')->post('security/ip-allowlist', [PlatformSecurityController::class, 'addIp'])->name('security.ip.add');
    // ... roles, impersonation, mfa, sso

    // Security Center
    Route::middleware('hrmac:security-center.security-incidents.notify')->post('security-center/incidents/{incident}/notify', [SecurityCenterController::class, 'notifyTenants'])->name('sc.incidents.notify');
    Route::middleware('hrmac:security-center.pentest-reports.upload')->post('security-center/pentests', [SecurityCenterController::class, 'uploadPentest'])->name('sc.pentests.upload');
});
```

---

## 6. React Pages

`packages/aero-ui/resources/js/Pages/Platform/Admin/` (depth 4 — `App` from `'../../../App.jsx'`, `useHRMAC` from `'../../../../hooks/useHRMAC.js'`).

1. `WhiteLabel/Domains.jsx` — domain table with DNS verification status, SSL badge, renew button.
2. `WhiteLabel/Branding.jsx` — per-tenant branding picker (logo upload, color pickers, CSS editor).
3. `Backup/Index.jsx` — dashboard with schedules list, manual trigger, restore-point list, PITR slider.
4. `StatusPage/Index.jsx` — component status grid with incident list sidebar.
5. `StatusPage/Incidents.jsx` — incident timeline with update thread, resolve button, postmortem link.
6. `PlatformSecurity/Sessions.jsx` — active staff session table with force-logout action.
7. `PlatformSecurity/Security.jsx` — tabs: `MFA enforcement | SSO config | IP allowlist`.
8. `SecurityCenter/Dashboard.jsx` — open incidents, recent pentests, security health score gauge.
9. `SecurityCenter/Incidents.jsx` — incident table with severity badges, notify-tenants action.
10. `SecurityCenter/Pentests.jsx` — report list with share-with-tenant multiselect.

All UI from `@aero/ui`. No inline styles. No `window.confirm`.

---

## 7. Tests

`packages/aero-platform/tests/Feature/Admin/Infra/`. `Gate::before(fn () => true)` in setUp.

- `CustomDomainServiceTest::dnsVerificationLogicCorrect` — mock DNS resolver; verify TXT match.
- `CustomDomainServiceTest::sslProvisioningDispatchesBackgroundJob` — `Queue::assertPushed(ProvisionSslJob::class)`.
- `BackupServiceTest::scheduleCreatesCorrectNextRunTime` — daily/weekly/monthly cadence math.
- `StatusPageServiceTest::incidentCreatesNotificationToSubscribers` — `Notification::assertSentTo(...)`.
- `IpAllowlistMiddlewareTest::blocksRequestsOutsideCidr` — request from outside CIDR yields 403.
- `ImpersonationServiceTest::logsCompleteAuditTrail` — start + end both audited with session id.

---

## 8. Tasks (execution order)

1. Add HRMAC hierarchy to `packages/aero-platform/config/module.php`.
2. Create migrations for 10 infra tables.
3. Create models with `EncryptedField` on `dkim_private_key`.
4. Build `CustomDomainService` + SSL provisioning job + tests.
5. Build `TenantBrandingService` + DKIM verify + tests.
6. Build `BackupService` + storage backends + scheduled job + tests.
7. Build `StatusPageService` + subscriber notifications + tests.
8. Build `PlatformSecurityService` (roles, sessions, MFA, SSO, IP allowlist) + middleware + tests.
9. Build `SecurityCenterService` + tenant notification + tests.
10. Build 5 controllers + Form Requests.
11. Register routes.
12. Build 10 React pages.
13. Full test suite + HRMAC verification.

---

## 9. Out of Scope

- Wildcard SSL purchase / commercial CA integration (Let's Encrypt only in v1).
- Cross-region replication (covered in P-11 multi-region).
- Real-time log streaming UI (covered in P-11 observability).
- Tenant-facing status page subscriber portal UI (separate marketing site).
- Bug bounty payout processing (manual workflow in v1).
