# AEOS365 — Granular UAT Test Matrix

> **Atomic, end-to-end scenario checklist for 100% system coverage.** Each row is ONE
> observable action with a clear expected result. Executed **live via Playwright MCP**
> (visible browser) against the seeded UAT state — fast, no global-setup churn.
>
> **Status:** ⬜ not run · ✅ pass · ❌ fail (→ log a B-/finding in UAT-TRACKER.md) · ⏭ skip (blocked, note why) · 🔁 covered by automated spec
> **Mode:** A = standalone (`aeos365-standalone.test`) · S = SaaS tenant (`uatco.aeos365.test`) · P = platform/admin (`aeos365.test` / `admin.aeos365.test`) · B = both A+S
> **Roles (seeded, pw `Password123!`):** SA `superadmin@uatco.test` · HR `hr@uatco.test` · EMP `employee@uatco.test` · LL `landlord@aeos365.test`
>
> **Execution mode:** drive HRM CRUD/workflows primarily on **A** as HR (same package code as S; UI identical), spot-check **S** for tenancy-specific bits (encrypted PII, per-tenant storage). Auth/anti-enum + platform run where mode-specific.

Last updated: 2026-06-03

---

## 0. Auth & access (foundation)

| ID | Scenario (atomic) | Steps | Expected | Mode | Status |
|----|-------------------|-------|----------|------|--------|
| A-01 | Login page renders | goto `/login` | email+password+submit visible; `device_id` hidden field gets a UUID v4 | B | 🔁 P1.3 |
| A-02 | Valid login | submit SA creds | lands off `/login` (dashboard) | B | 🔁 P1.3 |
| A-03 | Invalid password | submit SA + wrong pw | stays `/login`, generic error | B | 🔁 P1.3 |
| A-04 | Unknown email (anti-enum) | submit random email | same generic error as A-03 | B | 🔁 P1.3 |
| A-05 | Logout | login → logout | session cleared; `/dashboard` → `/login` | B | 🔁 P1.3 |
| A-06 | Session expiry | clear cookies → `/dashboard` | → `/login` | B | 🔁 P1.3 |
| A-07 | Password-reset request | `/forgot-password` submit | uniform response, no error | B | 🔁 P1.3 |
| A-08 | HR reaches employees | HR → `/hrm/employees` | 200, list renders | B | ✅ live (P1.4 was false-pass; B-38 fixed) |
| A-09 | HR reaches payroll | HR → `/hrm/payroll` | 200 | B | 🔁 P1.4 |
| A-10 | EMP denied employees | EMP → `/hrm/employees` | redirected to `/dashboard` | B | 🔁 P1.4 |
| A-11 | EMP denied payroll | EMP → `/hrm/payroll` | redirected away | B | 🔁 P1.4 |
| A-12 | EMP self-service allowed | EMP → `/hrm/self-service` | 200 (or no-profile) | B | 🔁 P1.4 |
| A-13 | MFA setup/challenge | enable MFA, re-login | QR/secret + challenge | B | ⏭ feature/P4 |
| A-14 | Impersonation (LL→tenant) | LL start → banner → exit | banner shown; exit restores | P | ⏭ B-34/P4 |
| A-15 | Installer guarded post-install | A → `/install` | "Already Installed" page | A | 🔁 P1.1 |
| A-16 | SaaS signup renders | `aeos365.test/signup` | "Create your account" | P | 🔁 P1.2 |

## 1. Dashboard & navigation

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| D-01 | HR dashboard renders | HR → `/dashboard` | renders, no error | B | ✅ live |
| D-02 | Nav menu populated (HR) | inspect sidebar | full HRM menu listed (B-22 confirmed) | B | ✅ live |
| D-03 | Nav menu scoped (EMP) | EMP sidebar | only self-service/dashboard items | B | ⬜ |
| D-04 | Announcements widget | dashboard | announcements list renders (no SQL error) | B | ⬜ |
| D-05 | Wellbeing/SQL-agg widget | dashboard | aggregated numbers render | B | ⬜ |

## 2. Employees (`/hrm/employees`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| E-01 | List renders | goto index | employees table renders (after B-38 fix) | B | ✅ live |
| E-02 | Search/filter | type in search | list filters | B | ⬜ |
| E-03 | Create form opens | click Create | form with dept/designation dropdowns populated | B | ⬜ |
| E-04 | Create valid | fill + submit | success toast; appears in list | B | ⬜ |
| E-05 | Create validation | submit empty | field errors shown | B | ⬜ |
| E-06 | Create duplicate code/email | reuse existing | rejected | B | ⬜ |
| E-07 | View profile | open a record | profile renders (tabs) | B | ⬜ |
| E-08 | Edit | change a field, save | persisted; toast | B | ⬜ |
| E-09 | Delete | delete a record | removed from list | B | ⬜ |
| E-10 | Encrypted PII masked | view national_id/tax_id | masked in UI (not plaintext) | B | ⬜ |
| E-11 | PII encrypted at rest | DB check national_id | ciphertext, not plaintext | B | ⬜ |
| E-12 | Document upload | upload a doc | stored; listed | B | ⬜ |
| E-13 | Avatar upload (per-tenant) | upload image | lands in tenant storage path | S | ⬜ |
| E-14 | EMP self-service profile | EMP → my profile | own profile editable | B | ⬜ |

## 3. Departments & Designations

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| DD-01 | Departments list | `/hrm/departments` | seeded depts render | B | ⬜ |
| DD-02 | Department create | create | added; in list | B | ⬜ |
| DD-03 | Department edit | edit | persisted | B | ⬜ |
| DD-04 | Department delete | delete (unused) | removed | B | ⬜ |
| DD-05 | Designations list | `/hrm/designations` | seeded render | B | ⬜ |
| DD-06 | Designation CRUD | create/edit/delete | works | B | ⬜ |
| DD-07 | New dept appears in employee form | create dept → employee create | dropdown includes it | B | ⬜ |

## 4. Attendance & Overtime

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| AT-01 | Attendance list | `/hrm/attendance` | renders | B | ⬜ |
| AT-02 | Clock-in | clock in | recorded | B | ⬜ |
| AT-03 | Clock-out | clock out | recorded; hours computed | B | ⬜ |
| AT-04 | Double clock-in idempotent | clock-in twice | no duplicate | B | ⬜ |
| AT-05 | Daily attendance view | `/hrm/attendance/daily` | renders | B | ⬜ |
| AT-06 | Timesheet | timesheet page | renders | B | ⬜ |
| AT-07 | Overtime request | `/hrm/overtime` create | submitted | B | ⬜ |
| AT-08 | Overtime approve | HR approve | status approved | B | ⬜ |
| AT-09 | Shift listing | shifts page | seeded shifts render | B | ⬜ |

## 5. Leave (`/hrm/leave`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| L-01 | Leave types list | `/hrm/leave/settings` | seeded types (AL/SL/CL/UL) | B | ⬜ |
| L-02 | Leave type CRUD | create/edit/delete | works | B | ⬜ |
| L-03 | EMP apply for leave | EMP apply | submitted (pending) | B | ⬜ |
| L-04 | HR approve | HR approve | status approved | B | ⬜ |
| L-05 | HR reject | HR reject another | status rejected | B | ⬜ |
| L-06 | Balance decremented | after approve | balance reduced | B | ⬜ |
| L-07 | Calendar shows approved | `/hrm/leave/calendar` | approved leave visible | B | ⬜ |
| L-08 | Bulk action | select + bulk approve | works | B | ⬜ |

## 6. Payroll & Salary (`/hrm/payroll`, `/hrm/salary-structure`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| P-01 | Pay components list | components page | seeded components | B | ⬜ |
| P-02 | Pay component CRUD | create/edit/delete | works | B | ⬜ |
| P-03 | Salary structure list | `/hrm/salary-structure` | renders | B | ⬜ |
| P-04 | Salary structure CRUD | create/assign | works | B | ⬜ |
| P-05 | Payroll run | run payroll | payslips generated | B | ⬜ |
| P-06 | Payslip view | open payslip | renders; bank last-4 only | B | ⬜ |
| P-07 | Finalized payslip immutable | edit/delete finalized | blocked (403/disabled) | B | ⬜ |

## 7. Recruitment (`/hrm/recruitment`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| R-01 | Jobs list | recruitment/jobs | renders | B | ⬜ |
| R-02 | Job create/publish/close | CRUD + status | works | B | ⬜ |
| R-03 | Application apply | create application | submitted | B | ⬜ |
| R-04 | Move stage | advance stage | status updated | B | ⬜ |
| R-05 | Reject application | reject | rejected | B | ⬜ |
| R-06 | Interview schedule/update | schedule (de-duped flat route) | created/updated | B | ⬜ |
| R-07 | Offer | create offer | works | B | ⬜ |
| R-08 | Onboarding | onboarding flow | works | B | ⬜ |

## 8. Training (`/hrm/training`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| T-01 | Courses list | training/courses | renders | B | ⬜ |
| T-02 | Course CRUD | create/edit/delete | works | B | ⬜ |
| T-03 | Categories | categories CRUD | works | B | ⬜ |
| T-04 | Sessions | session create | works | B | ⬜ |
| T-05 | Enrollment | enroll (de-duped flat route) | enrolled | B | ⬜ |
| T-06 | Feedback | submit feedback | recorded | B | ⬜ |

## 9. Performance (`/hrm/performance`, goals/competencies/feedback-360)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| PF-01 | Reviews list | performance reviews | renders | B | ⬜ |
| PF-02 | Review cycle CRUD | create cycle | works | B | ⬜ |
| PF-03 | Goals CRUD | `/hrm/goals` | works | B | ⬜ |
| PF-04 | Competencies CRUD | `/hrm/competencies` | works | B | ⬜ |
| PF-05 | Calibration | calibration view/manage | renders/acts | B | ⬜ |
| PF-06 | PIP create/list/show | improvement-plans (consolidated route) | works | B | ⬜ |
| PF-07 | 360 feedback | `/hrm/feedback-360` | works | B | ⬜ |
| PF-08 | Skill matrix | skill matrix | renders | B | ⬜ |

## 10. Disciplinary (`/hrm/disciplinary`, cases/action-types/warnings/grievances)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| DS-01 | Cases list | `/hrm/cases` | renders | B | ⬜ |
| DS-02 | Case create→investigate→close | lifecycle | status transitions | B | ⬜ |
| DS-03 | Action-types CRUD | `/hrm/action-types` | works | B | ⬜ |
| DS-04 | Warnings | `/hrm/warnings` CRUD | works | B | ⬜ |
| DS-05 | Grievance create→investigate→resolve | `/hrm/grievances` (de-duped route) | transitions | B | ⬜ |

## 11. Safety (`/hrm/safety`, incidents/inspections)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| SF-01 | Incidents list | `/hrm/incidents` | renders | B | ⬜ |
| SF-02 | Incident report→resolve | lifecycle | transitions | B | ⬜ |
| SF-03 | Inspections | `/hrm/inspections` CRUD | works | B | ⬜ |
| SF-04 | Safety training | safety training | works | B | ⬜ |

## 12. Assets (`/hrm/assets`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| AS-01 | Inventory list | `/hrm/assets` (canonical Hrm* route) | renders | B | ⬜ |
| AS-02 | Asset CRUD | create/edit/delete | works | B | ⬜ |
| AS-03 | Categories | asset categories CRUD | works | B | ⬜ |
| AS-04 | Allocate→return | allocate to emp, return | status transitions | B | ⬜ |

## 13. Expenses (`/hrm/expenses`, claims/categories)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| EX-01 | Claims list | `/hrm/claims` | renders | B | ⬜ |
| EX-02 | Claim submit | EMP submit | pending | B | ⬜ |
| EX-03 | Claim approve | HR approve | approved | B | ⬜ |
| EX-04 | Categories CRUD | expense categories | works | B | ⬜ |

## 14. Benefits (`/hrm/benefits`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| BN-01 | Catalog list | benefits | renders | B | ⬜ |
| BN-02 | Benefit CRUD | create/edit/delete | works | B | ⬜ |
| BN-03 | Enrollment | enroll employee | works | B | ⬜ |
| BN-04 | Open-enrollment period | `/hrm/open-enrollment` | create/manage | B | ⬜ |

## 15. Succession (`/hrm/succession-planning`, career-paths, talent-marketplace)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| SC-01 | Talent pools | succession planning | renders | B | ⬜ |
| SC-02 | Candidates | add candidate | works | B | ⬜ |
| SC-03 | Career paths | `/hrm/career-paths` CRUD | works | B | ⬜ |
| SC-04 | Mobility / talent-marketplace | `/hrm/talent-marketplace` | renders | B | ⬜ |

## 16. Misc HRM (events, announcements, planning, exit interviews)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| M-01 | Events list + register | `/hrm/events` | render + register | B | ⬜ |
| M-02 | Announcements CRUD | `/hrm/announcements` | works | B | ⬜ |
| M-03 | Wellbeing dashboard | wellbeing | SQL-agg renders | B | ⬜ |
| M-04 | Workforce planning | `/hrm/workforce-planning` | renders | B | ⬜ |
| M-05 | Compensation planning | `/hrm/compensation-planning` | renders | B | ⬜ |
| M-06 | Exit interviews | `/hrm/exit-interviews` | CRUD | B | ⬜ |
| M-07 | Org structure | `/hrm/org-structure` | renders | B | ⬜ |
| M-08 | Pulse surveys | `/hrm/pulse-surveys` | renders | B | ⬜ |

## 17. Self-service portal (`/hrm/self-service`, `/hrm/my/*`)

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| SS-01 | My dashboard | EMP self-service | renders | B | ⬜ |
| SS-02 | My leaves (apply) | EMP apply | works | B | ⬜ |
| SS-03 | My payslips (view) | EMP payslips | view-only | B | ⬜ |
| SS-04 | My profile (edit) | EMP edit profile | persisted | B | ⬜ |
| SS-05 | My training | EMP training | renders | B | ⬜ |

## 18. Platform admin (SaaS, `admin.aeos365.test`) — needs B-34 P4 landlord login

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| PL-01 | Landlord login | LL login at admin | dashboard | P | ⏭ B-34/P4 |
| PL-02 | Tenant list | admin tenants | uatco listed | P | ⏭ B-34/P4 |
| PL-03 | Tenant create/show | create tenant | provisions | P | ⏭ B-34/P4 |
| PL-04 | Tenant suspend → 403 | suspend uatco-throwaway | web 403 page; API 403 JSON | P | ⏭ B-34/P4 |
| PL-05 | GDPR-forget | forget throwaway | row + DB dropped | P | ⏭ B-34/P4 |
| PL-06 | Catalog: plans/products/modules/pricing | CRUD | works | P | ⏭ B-34/P4 |
| PL-07 | Observability: audit-log viewer | open | entries render | P | ⏭ B-34/P4 |
| PL-08 | Observability: access-log viewer | open | denials render | P | ⏭ B-34/P4 |
| PL-09 | Feature flags / maintenance | open | render | P | ⏭ B-34/P4 |
| PL-10 | Landlord users & roles CRUD | manage | works | P | ⏭ B-34/P4 |

## 19. Billing (SaaS, `@billing` — Stripe test keys) — gated

| ID | Scenario | Steps | Expected | Mode | Status |
|----|----------|-------|----------|------|--------|
| BL-01 | Invoices view/PDF | invoices | render/download | P | ⏭ Stripe/P4 |
| BL-02 | Payment methods | add test card | saved | P | ⏭ Stripe/P4 |
| BL-03 | Renewal run | trigger | invoice created | P | ⏭ Stripe/P4 |
| BL-04 | Dunning | simulate fail | dunning state | P | ⏭ Stripe/P4 |
| BL-05 | Refund / credit note | issue | recorded | P | ⏭ Stripe/P4 |

---

### Execution log
- _(append run notes here: date, batch, pass/fail, findings → UAT-TRACKER.md)_
