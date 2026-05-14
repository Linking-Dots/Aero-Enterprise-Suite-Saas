# AEOS365 Master Development Plan

## System Overview

AEOS365 is a dual-mode (SaaS + Standalone) modular ERP/HRMIS platform built on Laravel 12 + React/Inertia.js. Every feature must work identically in both modes unless explicitly noted.

**Monorepo:** `c:\laragon\www\Aero-Enterprise-Suite-Saas`  
**SaaS host:** `c:\laragon\www\aeos365`  
**Standalone host:** `c:\laragon\www\aeos365-standalone`

---

## Non-Negotiable Standards

Every plan, every task, every page, every controller **must** comply with:

| Standard | Document |
|----------|----------|
| Controller + Page contract | `docs/standards/inertia-standard.md` |
| HRMAC permission paths | `docs/standards/hrmac-convention.md` |
| Quality gate checklist | `docs/standards/done-definition.md` |
| PHPUnit + Playwright patterns | `docs/standards/test-standard.md` |

No feature is complete until it passes the quality gate in `done-definition.md`.

---

## Execution Model

All feature plans use **subagent-driven development**:
1. Each plan is a self-contained sprint (1 plan = 1 feature cluster)
2. Each task within a plan: implementer subagent → spec review → code quality review → mark done
3. Plans within the same phase can run sequentially (not in parallel — conflicts)
4. Standards docs are immutable during a sprint; only updated between phases

---

## Module Inventory & Sub-Plan Index

### Phase 0 — Foundation (prerequisite for all phases)

| Plan | File | Scope |
|------|------|-------|
| F-1 | `plans/phase-0/plan-f1-auth.md` | Login, registration, 2FA, social auth, device management, password reset |
| F-2 | `plans/phase-0/plan-f2-installation.md` | Installation wizard (SaaS + Standalone), post-install setup |
| F-3 | `plans/phase-0/plan-f3-tenant-shell.md` | Tenant dashboard, navigation, profile, settings, notifications |

### Phase 1 — HRM Module (flagship)

| Plan | File | Scope |
|------|------|-------|
| H-1 | `plans/phase-1-hrm/plan-h1-employees.md` | Employee CRUD, profile, documents, bank details, emergency contacts |
| H-2 | `plans/phase-1-hrm/plan-h2-org-structure.md` | Departments, designations, org chart, grades, work locations |
| H-3 | `plans/phase-1-hrm/plan-h3-leave.md` | Leave types, apply, approve, balance, calendar, accrual rules, settings |
| H-4 | `plans/phase-1-hrm/plan-h4-attendance.md` | Clock in/out, admin view, my attendance, overtime, timesheets, shift marketplace |
| H-5 | `plans/phase-1-hrm/plan-h5-payroll.md` | Salary structures, components, payroll run, payslips, tax setup, bulk |
| H-6 | `plans/phase-1-hrm/plan-h6-performance.md` | Reviews, templates, goals, 360 feedback, calibration, skill matrix, PIP |
| H-7 | `plans/phase-1-hrm/plan-h7-recruitment.md` | Jobs, applications, interviews, kanban, offers, onboarding |
| H-8 | `plans/phase-1-hrm/plan-h8-training.md` | Courses, categories, enrollments, materials, sessions, feedback |
| H-9 | `plans/phase-1-hrm/plan-h9-self-service.md` | Employee portal: profile, leaves, payslips, benefits, training, career |
| H-10 | `plans/phase-1-hrm/plan-h10-analytics.md` | HR analytics, AI insights, attrition predictions, DEI dashboard, pulse surveys |
| H-11 | `plans/phase-1-hrm/plan-h11-benefits.md` | Benefits catalog, enrollment, open enrollment, employee benefits |
| H-12 | `plans/phase-1-hrm/plan-h12-disciplinary.md` | Disciplinary cases, warnings, action types, exit interviews, grievances |
| H-13 | `plans/phase-1-hrm/plan-h13-safety.md` | Incidents, inspections, safety training, workplace safety |
| H-14 | `plans/phase-1-hrm/plan-h14-assets.md` | Asset catalog, categories, allocations |
| H-15 | `plans/phase-1-hrm/plan-h15-expenses.md` | Expense categories, claims, my expenses |
| H-16 | `plans/phase-1-hrm/plan-h16-events.md` | Events, sub-events, registrations, public event pages |
| H-17 | `plans/phase-1-hrm/plan-h17-succession.md` | Succession planning, career paths, talent mobility, workforce planning |
| H-18 | `plans/phase-1-hrm/plan-h18-settings.md` | HRM settings, leave settings, attendance settings, task templates |

### Phase 2 — Platform Admin (SaaS management)

| Plan | File | Scope |
|------|------|-------|
| P-1 | `plans/phase-2-platform/plan-p1-tenants.md` | Tenant list, detail, provision, health, bulk operations, purge |
| P-2 | `plans/phase-2-platform/plan-p2-plans-billing.md` | Plans CRUD, subscriptions, invoices, Stripe integration, trial management |
| P-3 | `plans/phase-2-platform/plan-p3-platform-dashboard.md` | Platform dashboard, stats, alerts, reports, quotas, rate limits |
| P-4 | `plans/phase-2-platform/plan-p4-platform-settings.md` | Platform settings, SEO, branding, newsletter, affiliates, leads |

### Phase 3 — Finance Module

| Plan | File | Scope |
|------|------|-------|
| FIN-1 | `plans/phase-3-finance/plan-fin1-accounts.md` | Chart of accounts, general ledger, journal entries, cost centers |
| FIN-2 | `plans/phase-3-finance/plan-fin2-ap-ar.md` | Bills, invoices, payments, vendor management, customer management |
| FIN-3 | `plans/phase-3-finance/plan-fin3-banking.md` | Bank accounts, transactions, reconciliation |
| FIN-4 | `plans/phase-3-finance/plan-fin4-assets.md` | Fixed assets, depreciation, maintenance |
| FIN-5 | `plans/phase-3-finance/plan-fin5-reports.md` | Budget management, financial reports, tax setup |

### Phase 4 — CRM Module

| Plan | File | Scope |
|------|------|-------|
| CRM-1 | `plans/phase-4-crm/plan-crm1-contacts.md` | Contacts, leads, lead sources |
| CRM-2 | `plans/phase-4-crm/plan-crm2-pipeline.md` | Pipelines, stages, deals, deal activities, products |
| CRM-3 | `plans/phase-4-crm/plan-crm3-analytics.md` | CRM analytics, competitor tracking, sales reports |

### Phase 5 — Project Module

| Plan | File | Scope |
|------|------|-------|
| PRJ-1 | `plans/phase-5-project/plan-prj1-projects.md` | Projects, milestones, members, budget, risks, issues |
| PRJ-2 | `plans/phase-5-project/plan-prj2-tasks.md` | Tasks, sprints, kanban, dependencies, time entries |
| PRJ-3 | `plans/phase-5-project/plan-prj3-boq.md` | BOQ items, measurements, progress tracking |

### Phase 6 — DMS + Compliance + RFI

| Plan | File | Scope |
|------|------|-------|
| DMS-1 | `plans/phase-6-dms/plan-dms1-documents.md` | Documents, folders, versions, sharing, approvals, templates |
| DMS-2 | `plans/phase-6-dms/plan-dms2-signatures.md` | Digital signatures, workflows |
| COMP-1 | `plans/phase-6-compliance/plan-comp1-core.md` | Policies, requirements, jurisdictions, training records |
| COMP-2 | `plans/phase-6-compliance/plan-comp2-audits.md` | Audits, findings, risk assessments, permit to work |
| RFI-1 | `plans/phase-6-rfi/plan-rfi1-field.md` | RFI submissions, site instructions, weather logs, progress photos |
| RFI-2 | `plans/phase-6-rfi/plan-rfi2-reports.md` | Chainage, labour, equipment, material consumption |

### Phase 7 — Inventory + Supply Chain + Workflow + Forms

| Plan | File | Scope |
|------|------|-------|
| IMS-1 | `plans/phase-7-ops/plan-ims1-inventory.md` | Items, categories, warehouses, stock movements, purchase orders |
| SCM-1 | `plans/phase-7-ops/plan-scm1-procurement.md` | Suppliers, procurement requests, logistics, customs |
| SCM-2 | `plans/phase-7-ops/plan-scm2-demand.md` | Demand forecasting, production plans, trade documents |
| WF-1 | `plans/phase-7-ops/plan-wf1-workflow.md` | Workflow builder, templates, instances, transitions |
| FORM-1 | `plans/phase-7-ops/plan-form1-forms.md` | Form builder, submissions, field types |
| NOTIF-1 | `plans/phase-7-ops/plan-notif1-notifications.md` | Notification templates, channels, preferences |

### Phase 8 — Commerce + POS

| Plan | File | Scope |
|------|------|-------|
| COM-1 | `plans/phase-8-commerce/plan-com1-catalog.md` | Products, variants, categories, brands, reviews |
| COM-2 | `plans/phase-8-commerce/plan-com2-orders.md` | Orders, payments, shipments, coupons, returns |
| COM-3 | `plans/phase-8-commerce/plan-com3-vendors.md` | Vendor management, commissions, payouts |
| POS-1 | `plans/phase-8-commerce/plan-pos1-pos.md` | POS terminal, sales, transactions, payment methods |

### Phase 9 — Vertical Modules

| Plan | File | Scope |
|------|------|-------|
| MFG-1 | `plans/phase-9-verticals/plan-mfg1-manufacturing.md` | BOM, routes, work orders, work centers |
| HLTH-1 | `plans/phase-9-verticals/plan-hlth1-healthcare.md` | Patients, appointments, medical records, billing |
| EDU-1 | `plans/phase-9-verticals/plan-edu1-education.md` | Students, courses, enrollments, grades, faculty |
| RE-1 | `plans/phase-9-verticals/plan-re1-real-estate.md` | Properties, listings, leases, inspections, agents |
| IOT-1 | `plans/phase-9-verticals/plan-iot1-iot.md` | Devices, sensors, telemetry, alerts, gateways |
| BLK-1 | `plans/phase-9-verticals/plan-blk1-blockchain.md` | Smart contracts, wallets, tokens, transactions |
| ASST-1 | `plans/phase-9-verticals/plan-asst1-assistant.md` | AI assistant, conversations, embeddings |
| I18N-1 | `plans/phase-9-verticals/plan-i18n1-i18n.md` | Translation editor, language management |

---

## Execution Order

```
Phase 0 (Foundation) — execute first, blocks everything
  └─ F-1 Auth → F-2 Installation → F-3 Tenant Shell

Phase 1 (HRM) — execute after Phase 0
  └─ H-1 → H-2 → H-3 → H-4 → H-5 → H-6 → H-7 → H-8 → H-9 → H-10 → H-11...H-18

Phase 2 (Platform) — can start after F-1 + F-3
  └─ P-1 → P-2 → P-3 → P-4

Phase 3–9 — execute after Phase 1+2, in order
```

---

## Per-Plan Template

Every plan file must follow this structure:
1. Header (goal, architecture, tech stack, prerequisites)
2. File map (all files to create or modify)
3. Tasks — each task produces one committed, tested unit of work
4. Self-review section

Plans are saved to: `docs/superpowers/plans/<phase>/<plan-file>.md`
