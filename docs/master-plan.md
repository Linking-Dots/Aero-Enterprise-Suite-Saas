# AEOS365 Master Development Plan

**Last updated:** 2026-05-14  
**Status key:** ✅ Done · 🟡 In Progress · ⬜ Pending

---

## System Overview

AEOS365 is a dual-mode (SaaS + Standalone) modular ERP/HRMIS platform built on Laravel 12 + React/Inertia.js.

| | |
|--|--|
| **Monorepo** | `c:\laragon\www\Aero-Enterprise-Suite-Saas` |
| **SaaS host** | `c:\laragon\www\aeos365` |
| **Standalone host** | `c:\laragon\www\aeos365-standalone` |
| **SaaS URL** | `http://{tenant}.aeos365.test` |
| **Standalone URL** | `http://aeos365-standalone.test` |

**Dual-mode law:** Every feature works identically in SaaS mode (stancl/tenancy, tenant subdomain) and Standalone mode (single install, no central DB) unless the feature is explicitly platform-only (e.g. tenant provisioning, billing).

---

## Non-Negotiable Standards

Every plan, every task, every controller, every page **must** comply with all six standards. No exceptions.

| Standard | Document | Covers |
|----------|----------|--------|
| Controller + Page contract | `docs/standards/inertia-standard.md` | Inertia render, props, filters, pagination, `useForm` |
| HRMAC permission paths | `docs/standards/hrmac-convention.md` | Permission naming, `config/module.php` registration, `useHRMAC` |
| Quality gate checklist | `docs/standards/done-definition.md` | What "done" means — backend, frontend, tests, security |
| PHPUnit + Playwright patterns | `docs/standards/test-standard.md` | Controller tests, smoke tests, factories |
| Zero-Trust security | `docs/standards/security-architecture.md` | Encryption, BYOC, immutable records, masking, session security |
| Audit trail | `docs/standards/audit-standard.md` | `AuditService` usage, event types, access logs, GDPR events |

No feature is complete until it passes the quality gate in `done-definition.md`.

---

## Security Architecture Requirements (All Plans)

These apply to every plan. They are not optional additions — they are part of the definition of done.

### Every Controller Must
- Call `AuditService::log()` for every business action (approve, reject, export, run, post)
- Call `AuditService::logAccess()` when exposing salary, bank details, national ID, or medical data
- Apply data masking based on HRMAC permission before passing sensitive values to Inertia

### Every Model With PII Must
- Use `EncryptedField::class` cast for: `account_number`, `routing_number`, `tax_id`, `national_id`, `medical_notes`, `byoc_db_password`, `byoc_db_username`
- Add `LogsActivity` trait (Spatie) for automatic change tracking

### Immutable Records
After finalization these records cannot be modified — enforced by `ImmutableRecordObserver`:
- `Payslip` → locked when `status = 'paid'`
- `JournalEntry` → locked when `status = 'posted'`
- `Invoice` → locked when `status = 'sent'`
- `PerformanceReview` → locked when `status = 'completed'`

### GDPR Data Model
Every model storing employee personal data must support:
- `anonymize()` method that nullifies PII fields while preserving aggregate data
- Export via the GDPR data export service (Plan GDPR-1)

---

## Execution Model

All feature plans use **subagent-driven development**:
1. Each plan = 1 feature cluster = 1 sprint
2. Per task: implementer → spec compliance review → code quality review → ✅ done
3. Plans in the same phase execute sequentially (no parallel — git conflicts)
4. Standards documents are read-only during a sprint; amended only between phases

---

## What Is Already Built

### Infrastructure (Plans A–R) ✅ COMPLETE
All 529 models migrated to `TenantModel`/`CentralModel`, `AbstractModuleProvider` in `aero-contracts`, `TenantModel`/`CentralModel` in `aero-contracts`, `AeroMode` resolver, HRMAC enforcement, security hardening (no eval/shell_exec), Octane safety, CI (deptrac + PHPStan + composer audit), `ModuleRegistryInterface`, `NavigationRegistryInterface`.

### Security Foundation ✅ COMPLETE
`EncryptionDriverInterface` + `LaravelEncryptionDriver`, `EncryptedField` cast, `AuditServiceInterface` + `AuditService`, `AuditEventType` enum, `audit_logs` + `access_logs` migrations with DB-level immutability triggers, `platform_audit_logs` + `platform_access_logs` on central DB, BYOC columns on `tenants` table (`byoc_enabled`, `byoc_db_*`, `encryption_key_id`).

---

## Phase 0 — Foundation

*Prerequisite for all other phases. Execute in order — each plan blocks the next.*

| Plan | File | Status | Scope |
|------|------|--------|-------|
| F-0 | *(implemented directly — no plan file)* | ✅ Done | Security foundation (see above) |
| F-1 | `phase-0/plan-f1-auth.md` | ✅ Done | Login (+device_id fix), 2FA setup/challenge, devices, sessions, invitations, shared login, password reset, email verify |
| F-2 | `phase-0/plan-f2-installation.md` | ✅ Done | SaaS install wizard (registration → BYOC step → provisioning → success), Standalone wizard (DB setup → admin → complete) |
| F-3 | `phase-0/plan-f3-tenant-shell.md` | ✅ Done | Tenant dashboard, sidebar navigation, user profile, settings (security, 2FA, sessions, devices), notifications, audit log viewer |

**Note on F-2 BYOC:** The installation wizard for SaaS must include an optional BYOC database credentials step between Plan selection and Provisioning. The `TenantProvisioner` already supports BYOC — F-2 builds the UI step.

---

## Phase 1 — HRM Module (flagship)

*Execute after Phase 0 is complete.*

Every HRM plan includes: HRMAC guards on all CRUD, `AuditService::log()` on all business actions, `EncryptedField` on PII fields, dual-mode tested, PHPUnit + Playwright.

| Plan | File | Status | Scope |
|------|------|--------|-------|
| H-1 | `phase-1-hrm/plan-h1-employees.md` | 🟡 Written | Employee CRUD (index, create, edit, show), profile photo, documents, bank details (encrypted), emergency contacts, employment history |
| H-2 | `phase-1-hrm/plan-h2-org-structure.md` | 🟡 Written | Departments (CRUD + org chart), designations, grades, work locations |
| H-3 | `phase-1-hrm/plan-h3-leave.md` | 🟡 Written | Leave types, apply, admin approval, leave balance, calendar, accrual rules, admin settings |
| H-4 | `phase-1-hrm/plan-h4-attendance.md` | 🟡 Written | Clock in/out, admin view (daily/monthly), my attendance, overtime requests + approval, timesheets, shift marketplace |
| H-5 | `phase-1-hrm/plan-h5-payroll.md` | 🟡 Written | Salary structures, pay components, payroll run (immutable payslips), bulk payroll, payslip viewer/print, tax setup |
| H-6 | `phase-1-hrm/plan-h6-performance.md` | 🟡 Written | Reviews, templates, goals (create/track), 360 feedback, calibration, skill matrix, PIP |
| H-7 | `phase-1-hrm/plan-h7-recruitment.md` | 🟡 Written | Jobs, application kanban, interviews, offers, onboarding wizard |
| H-8 | `phase-1-hrm/plan-h8-training.md` | 🟡 Written | Courses, categories, enrollments, sessions, materials, feedback |
| H-9 | `phase-1-hrm/plan-h9-self-service.md` | 🟡 Written | Employee portal: my profile, my leaves, my payslips, my benefits, my training, career path |
| H-10 | `phase-1-hrm/plan-h10-analytics.md` | 🟡 Written | HR analytics dashboard, AI insights, attrition predictions, DEI dashboard, pulse surveys, workforce planning |
| H-11 | `phase-1-hrm/plan-h11-benefits.md` | 🟡 Written | Benefits catalog, enrollment periods, open enrollment, employee benefits |
| H-12 | `phase-1-hrm/plan-h12-disciplinary.md` | 🟡 Written | Disciplinary cases, warnings, exit interviews, grievances |
| H-13 | `phase-1-hrm/plan-h13-safety.md` | 🟡 Written | Incidents, inspections, safety training, workplace safety dashboard |
| H-14 | `phase-1-hrm/plan-h14-assets.md` | 🟡 Written | Asset catalog, categories, allocations |
| H-15 | `phase-1-hrm/plan-h15-expenses.md` | 🟡 Written | Expense categories, claims (create + approve), my expenses |
| H-16 | `phase-1-hrm/plan-h16-events.md` | 🟡 Written | Events, sub-events, registrations, print token, public event pages |
| H-17 | `phase-1-hrm/plan-h17-succession.md` | 🟡 Written | Succession planning, career paths, talent mobility, workforce planning |
| H-18 | `phase-1-hrm/plan-h18-settings.md` | 🟡 Written | HRM general settings, leave settings, attendance settings, task templates, holidays |

---

## Phase 2 — Platform Admin (SaaS management)

*Can start in parallel with H-1 after F-1 + F-3 are done.*

Platform plans apply to the central (landlord) database. All platform admin actions write to `platform_audit_logs`. PII fields on `LandlordUser` use `EncryptedField`. Immutable: Subscription records after activation, Invoice records after payment.

| Plan | File | Status | Scope |
|------|------|--------|-------|
| P-1 | `phase-2-platform/plan-p1-tenants.md` | ⬜ | Tenant list + detail, provisioning status, BYOC credential management, health check, suspend, purge, bulk operations, impersonation |
| P-2 | `phase-2-platform/plan-p2-plans-billing.md` | ⬜ | Plan CRUD, subscriptions (immutable after activation), invoices (immutable after payment), Stripe integration, trial management, grace periods |
| P-3 | `phase-2-platform/plan-p3-platform-dashboard.md` | ⬜ | Platform dashboard (tenant stats, MRR, health), system alerts, quota management, rate limit config, reports |
| P-4 | `phase-2-platform/plan-p4-platform-settings.md` | ⬜ | Platform settings (branding, SEO, mail config, maintenance mode), landlord user management, roles, newsletter, affiliate tracking, leads |
| P-5 | `phase-2-platform/plan-p5-platform-audit.md` | ⬜ | Platform audit log viewer, access log viewer, security dashboard (failed logins, suspicious activity, IP blocks) |

---

## Phase 3 — Finance Module

*Execute after Phase 0. Finance immutability: Journal entries locked on `posted`, invoices locked on `sent`.*

| Plan | File | Status | Scope |
|------|------|--------|-------|
| FIN-1 | `phase-3-finance/plan-fin1-accounts.md` | ⬜ | Chart of accounts, general ledger, journal entries (immutable on post), cost centers, account groups |
| FIN-2 | `phase-3-finance/plan-fin2-ap-ar.md` | ⬜ | Bills (AP), invoices (AR, immutable on send), payments, vendor management, customer management |
| FIN-3 | `phase-3-finance/plan-fin3-banking.md` | ⬜ | Bank accounts, transactions, bank reconciliation |
| FIN-4 | `phase-3-finance/plan-fin4-assets.md` | ⬜ | Fixed assets, depreciation schedules, asset maintenance |
| FIN-5 | `phase-3-finance/plan-fin5-reports.md` | ⬜ | Budget management, financial reports (P&L, balance sheet, cash flow), tax setup |

---

## Phase 4 — CRM Module

| Plan | File | Status | Scope |
|------|------|--------|-------|
| CRM-1 | `phase-4-crm/plan-crm1-contacts.md` | ⬜ | Contacts, leads, lead sources, lead conversion |
| CRM-2 | `phase-4-crm/plan-crm2-pipeline.md` | ⬜ | Pipelines, stages, deals (kanban + table), deal activities, products, deal attachments |
| CRM-3 | `phase-4-crm/plan-crm3-analytics.md` | ⬜ | Sales analytics, funnel reports, competitor tracking, team leaderboard |

---

## Phase 5 — Project Module

| Plan | File | Status | Scope |
|------|------|--------|-------|
| PRJ-1 | `phase-5-project/plan-prj1-projects.md` | ⬜ | Projects (CRUD), milestones, members, budget, risks, issues, labels, watchers |
| PRJ-2 | `phase-5-project/plan-prj2-tasks.md` | ⬜ | Tasks (list + kanban), sprints, dependencies, time entries, comments |
| PRJ-3 | `phase-5-project/plan-prj3-boq.md` | ⬜ | BOQ items, measurements, chainage progress |

---

## Phase 6 — Documents, Compliance, RFI, GDPR

| Plan | File | Status | Scope |
|------|------|--------|-------|
| DMS-1 | `phase-6-dms/plan-dms1-documents.md` | ⬜ | Documents, folders, versions, sharing, approval workflows, templates |
| DMS-2 | `phase-6-dms/plan-dms2-signatures.md` | ⬜ | Digital signatures, signature workflows |
| COMP-1 | `phase-6-compliance/plan-comp1-core.md` | ⬜ | Policies, requirements, jurisdictions, compliance training records |
| COMP-2 | `phase-6-compliance/plan-comp2-audits.md` | ⬜ | Compliance audits, findings, risk assessments, permit to work, NCR |
| RFI-1 | `phase-6-rfi/plan-rfi1-field.md` | ⬜ | RFI submissions, site instructions, weather logs, progress photos, objections |
| RFI-2 | `phase-6-rfi/plan-rfi2-reports.md` | ⬜ | Chainage progress, labour deployment, equipment logs, material consumption |
| GDPR-1 | `phase-6-compliance/plan-gdpr1-tools.md` | ⬜ | Data subject export (employee full data pack as PDF/JSON), right to erasure (anonymize), consent management, data retention runner, breach log |

**Note on GDPR-1:** The data model is already GDPR-ready (BYOC key per tenant, `anonymize()` methods, audit events). GDPR-1 builds the UI: data export request flow, anonymization confirmation, consent tracking UI, and the retention policy scheduler.

---

## Phase 7 — Operations (IMS, SCM, Workflow, Forms, Notifications)

| Plan | File | Status | Scope |
|------|------|--------|-------|
| IMS-1 | `phase-7-ops/plan-ims1-inventory.md` | ⬜ | Items, categories, warehouses, stock movements, purchase orders, suppliers |
| SCM-1 | `phase-7-ops/plan-scm1-procurement.md` | ⬜ | Procurement requests, suppliers, logistics, customs declarations |
| SCM-2 | `phase-7-ops/plan-scm2-demand.md` | ⬜ | Demand forecasting, production plans, trade documents, return requests |
| WF-1 | `phase-7-ops/plan-wf1-workflow.md` | ⬜ | Workflow builder (visual), templates, instances, transitions, approval routing |
| FORM-1 | `phase-7-ops/plan-form1-forms.md` | ⬜ | Dynamic form builder, field types, submissions, integrations |
| NOTIF-1 | `phase-7-ops/plan-notif1-notifications.md` | ⬜ | Notification templates, channels (email/SMS/push), delivery preferences, notification center |
| I18N-1 | `phase-7-ops/plan-i18n1-i18n.md` | ⬜ | Translation editor, language management, locale preferences |

---

## Phase 8 — Commerce + POS

| Plan | File | Status | Scope |
|------|------|--------|-------|
| COM-1 | `phase-8-commerce/plan-com1-catalog.md` | ⬜ | Products, variants, categories, brands, reviews, attributes |
| COM-2 | `phase-8-commerce/plan-com2-orders.md` | ⬜ | Orders, payments, shipments, coupons, returns, shopping cart |
| COM-3 | `phase-8-commerce/plan-com3-vendors.md` | ⬜ | Vendor management, commissions, payouts |
| POS-1 | `phase-8-commerce/plan-pos1-pos.md` | ⬜ | POS terminal, sales, transactions, payment methods, customer groups |

---

## Phase 9 — Vertical Modules

*Activated per tenant based on subscribed modules. HIPAA-specific encryption activated when `aero-healthcare` is enabled.*

| Plan | File | Status | Scope |
|------|------|--------|-------|
| MFG-1 | `phase-9-verticals/plan-mfg1-manufacturing.md` | ⬜ | BOM, routes, work orders, work centers, production planning |
| HLTH-1 | `phase-9-verticals/plan-hlth1-healthcare.md` | ⬜ | Patients (ALL fields encrypted — HIPAA), appointments, medical records, prescriptions, billing |
| EDU-1 | `phase-9-verticals/plan-edu1-education.md` | ⬜ | Students, courses, enrollments, grades, transcripts, faculty |
| RE-1 | `phase-9-verticals/plan-re1-real-estate.md` | ⬜ | Properties, listings, leases, inspections, agents, rent payments |
| IOT-1 | `phase-9-verticals/plan-iot1-iot.md` | ⬜ | Devices, sensors, telemetry, alerts, gateways, firmware |
| BLK-1 | `phase-9-verticals/plan-blk1-blockchain.md` | ⬜ | Smart contracts, wallets, tokens, token transfers, analytics |
| ASST-1 | `phase-9-verticals/plan-asst1-assistant.md` | ⬜ | AI assistant, conversations, embeddings, usage logs |
| BOOK-1 | `phase-9-verticals/plan-book1-booking.md` | ⬜ | Booking management (if aero-booking package active) |

---

## Compliance Requirements by Phase

Every plan must address the compliance requirements relevant to its data:

| Phase | Key compliance requirements |
|-------|-----------------------------|
| 0 (Auth) | Session audit, login audit, MFA enforcement for privileged roles |
| 1 (HRM) | GDPR (employee PII), Labor law (immutable payslips, 7yr retention), salary masking |
| 2 (Platform) | SOC 2 (platform audit log), billing record immutability, GDPR for landlord users |
| 3 (Finance) | GAAP/IFRS (journal entry immutability), 7yr financial record retention |
| 4 (CRM) | GDPR (contact PII), consent tracking for marketing contacts |
| 5 (Project) | No special compliance beyond standard audit trail |
| 6 (Compliance) | Full GDPR tooling, ISO 27001 audit support, HIPAA if healthcare module enabled |
| 7+ (Ops/Verticals) | Module-specific: HIPAA for healthcare, PCI-DSS awareness for commerce |

---

## BYOC Requirements

BYOC (Bring Your Own Cloud) is a **launch requirement**. Affected plans:

| Plan | BYOC scope |
|------|-----------|
| F-2 (Installation) | SaaS registration wizard must include optional BYOC database credentials step |
| P-1 (Tenant management) | Admin UI to view/edit BYOC credentials, test connectivity, see BYOC status |
| F-3 (Tenant shell) | No BYOC-specific UI — BYOC is transparent to tenant users once provisioned |

BYOC uses any MySQL 8.0+ or PostgreSQL 14+ endpoint. Credentials stored encrypted (`EncryptedField`) on the `tenants` table. The `AerosTenantDatabaseManager` (in aero-platform) handles connection routing.

---

## Execution Order

```
✅ Infrastructure (Plans A–R) — DONE
✅ Security Foundation (F-0) — DONE

Phase 0 — Execute in order, each blocks the next:
  F-1 Auth ──→ F-2 Installation ──→ F-3 Tenant Shell

Phase 1 (HRM) — Execute after Phase 0:
  H-1 → H-2 → H-3 → H-4 → H-5 → H-6 → H-7 → H-8 → H-9 → H-10 → H-11...H-18

Phase 2 (Platform) — Can start alongside Phase 1 after F-1+F-3:
  P-1 → P-2 → P-3 → P-4 → P-5

Phase 3–9 — Execute after Phase 1+2, in priority order:
  FIN → CRM → PRJ → DMS/COMP/RFI/GDPR → IMS/SCM/WF/FORM/NOTIF/I18N → COM/POS → Verticals
```

---

## Per-Plan Template

Every plan file must follow this structure:
1. **Header** — goal, architecture summary, tech stack, prerequisites, standards references
2. **Security notes** — which fields are encrypted, which events are audited, which records are immutable
3. **File map** — all files to create or modify with exact paths
4. **Tasks** — each task produces one committed, tested, reviewed unit of work
5. **Self-review** — spec coverage check, placeholder scan, type consistency

Plans are saved to:
```
docs/superpowers/plans/<phase>/<plan-id>-<name>.md
```

---

## Plan Count Summary

| Phase | Plans | Status |
|-------|-------|--------|
| Phase 0 — Foundation | 3 (+F-0 done) | 1 written, 2 pending |
| Phase 1 — HRM | 18 | 0 written, 18 pending |
| Phase 2 — Platform | 5 | 0 written, 5 pending |
| Phase 3 — Finance | 5 | 0 written, 5 pending |
| Phase 4 — CRM | 3 | 0 written, 3 pending |
| Phase 5 — Project | 3 | 0 written, 3 pending |
| Phase 6 — DMS/Compliance/GDPR | 7 | 0 written, 7 pending |
| Phase 7 — Ops | 7 | 0 written, 7 pending |
| Phase 8 — Commerce/POS | 4 | 0 written, 4 pending |
| Phase 9 — Verticals | 8 | 0 written, 8 pending |
| **Total** | **63 plans** | **1 written, 62 pending** |
