# aero-time-tracking — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current state:** **STUB** — 2 files, 0 migrations, 0 controllers, 0 models, 0 routes, 0 tests.
**Current score:** 2/10
**Target score:** 10/10 OR **REMOVED**
**Estimated effort:** 0.5d (remove) OR 6-9d (implement)

**Goal:** Universal Time Tracking declares the intent (time entries, timesheets, approval, project tracking, billing integration) but ships nothing. Note: aero-hrm already has Attendance/Timesheet surface — verify overlap before implementing.

**Architecture (if implement):** TimeEntry → Timesheet → Approval pipeline. Cross-module: HRM (attendance), Project (project time), EAM (asset maintenance time). Billing integration: bill-by-hours feeds Finance invoice generation.

**Tech Stack:** Laravel 12, Inertia v2, TenantModel, polymorphic relations.

**Prerequisite:** Phase 0 wiring + aero-hrm overlap audit.

---

## Decision Branch

### Branch A — Remove (RECOMMENDED if HRM Attendance covers it)

Per Phase 1 audit, aero-hrm config/module.php declares `attendance`, `leaves`, `overtime` sub-modules. If those cover the time-tracking needs, this package is redundant.

- [ ] **Step 1: Audit overlap with aero-hrm Attendance/Timesheet**
- [ ] **Step 2: If covered → delete `packages/aero-time-tracking/`**
- [ ] **Step 3: Remove from monorepo**
- [ ] **Step 4: Commit**

```bash
git commit -am "chore: remove aero-time-tracking stub (aero-hrm Attendance covers the surface)"
```

### Branch B — Implement (only if HRM coverage insufficient)

## File Structure (Branch B)

| File | Responsibility |
|---|---|
| `config/module.php` | Declare components: entries, timesheets, approval, projects, reports |
| `database/migrations/*_create_time_entries_table.php` | Single time entry (start, stop, duration, project_id, billable) |
| `database/migrations/*_create_timesheets_table.php` | Weekly aggregation + approval status |
| `src/Models/TimeEntry.php`, `Timesheet.php` | TenantModels |
| `src/Services/TimeEntryService.php` | Start/stop/edit |
| `src/Services/TimesheetService.php` | Aggregate + approval flow |
| `src/Services/BillingExportService.php` | Hand off billable hours to aero-finance Invoice |
| `src/Http/Controllers/TimeEntryController.php`, `TimesheetController.php`, `TimesheetApprovalController.php`, `TimeReportController.php` |  |
| `src/Policies/*Policy.php` |  |
| `routes/web.php` | Resource routes + HRMAC |
| `tests/Feature/TimeTracking/*Test.php` | Per-controller + approval flow |

## Tasks (Branch B)

1. Migrations + models with polymorphic `timeable` (project/asset/ticket)
2. TimeEntryService (start/stop with overlap detection)
3. TimesheetService (weekly aggregation, locking on approval)
4. Approval flow integration with aero-workflow
5. Billing export to aero-finance
6. Controllers + Form Requests + Policies
7. Audit trail
8. Tests (concurrency on start/stop, overlap, immutability after approval)
9. Inertia pages
10. Final verification

---

## Recommendation

**Branch A (remove)** if aero-hrm covers attendance/timesheet. Universal time tracking across HRM/Project/EAM is a real need, but **build it once HRM is at 10/10** and the gap is clear — not before.
