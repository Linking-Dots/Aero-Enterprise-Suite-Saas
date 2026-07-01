# Next session — FYP demo push (HRM Act 3 + remaining acts)

Paste the block below to start.

---

Continue the **FYP demo prep** (Final Year Project presentation, **July 10 2026** — examiners judge scope, depth, working functionality, and polish). Work on **main in place** (vendor junctions, no worktree). Address me as **Boss**; show **tokens burned** per reply. Use the **SDD/superpowers workflow** (brainstorm → plan → execute). **Do NOT push** to origin unless I say so. **Screenshot every page you touch in BOTH shells** (standard sidebar + command mode) — console‑clean ≠ good UI.

**READ FIRST:** memory [[fyp-presentation-deadline]] (locked demo script + scope + all the HRM findings) + [[tenant-page-redesign-iteration]] + [[command-mode-pages]] + [[show-live-ui]] + [[plan-product-subscriptions]] + [[theme-consistency-all-pages]]; and the SDD ledger `.superpowers/sdd/progress.md` (Subscription/Billing + HRM sections).

**LOCKED DEMO SCRIPT (Boss‑confirmed):** (1) Onboarding: Landing→Pricing→Register→provision; (2) Tenant admin — Dashboard·Users·Roles&Access·Organization·Settings·Subscription+Add‑ons·Audit/Activity [ALREADY redesigned; just seed data + verify]; (3) **HRM module E2E** Employees→Attendance→Leave→Payroll [the "real ERP" proof]; (4) Differentiators: Theme Studio·command mode·standalone dual‑mode. Only redesign pages the demo shows; leave the ~22 off‑script sidebar pages alone (just not broken). Boss wants **MAX data to prove scalability**.

**DONE (this + prior sessions, all on main, NOT pushed):**
- **Subscription/Billing hub** — complete, reviewed READY‑TO‑MERGE (tabbed hub + add‑on management, plan⟂product separate subscriptions). Ready to push on my word.
- **HRM Employees** — demo‑ready: 250 employees, 13 pages, server‑side pagination, 0 console errors.
- **HRM Attendance (`/hrm/attendance/daily`)** — demo‑ready: 5,750 records, 0 errors, scalable.
- **3 backend fixes committed:** systemic HRMAC super‑admin gate + attendance date‑default 500 (`c4f765860`); HRM `deleted_at` migration (`b6d09ba34`).

**TEST TENANT:** `democorp.aeos365.test` — `admin@democorp.com` / `Aeos365!Admin` (role: Super Administrator; Starter plan + HRM Suite add‑on trialing; 250 employees + 5,750 attendance already seeded).

**REMAINING WORK (in order):**
1. **HRM Leave/Payroll data‑layer reconciliation (the drift — do this first, carefully).** The reference *seeders* disagree with the actual table *schemas*: `HrmGradeSeeder`→`grades.level` (missing), `HrmShiftScheduleSeeder`→`break_duration` (table has `break_duration_minutes`), `HrmSalaryComponentSeeder`→`default_amount` (table has `value`); and `HrmLeaveTypeSeeder` uses the `LeaveSetting` model → `leave_global_settings` (a settings table w/o `code`) but inserts leave‑TYPE fields — **leave types belong in `leave_types`** (real cols: name,code,color,days_per_year,is_paid,requires_approval,carry_forward,encashable,max_carry_forward,is_active). For each: decide the canonical side (align the seeder to the real columns, or add a migration), then seed **leave types + leave applications + a payroll run/payslips** on democorp. Payroll also needs structures + calc — verify the run computes.
2. **HRM redesign polish** — the E2E pages are on `IndexPageLayout` but lack a **KPI strip** and a **command‑mode rail**. Add per‑page rails (mirror `Pages/Core/Subscription/SubscriptionRail.jsx` / `UsersRail.jsx` — pass `rail` to `App` in `.layout`) + KPI `Stat` strips (controller supplies stats). Verify live at scale in BOTH shells.
3. **Onboarding flow** — Landing→Pricing→Register→tenant provisioning polish.
4. **Seed + verify the tenant‑admin spine** (already redesigned) so no page looks empty.
5. **Full dress rehearsal** — run the whole script in SaaS **and** standalone; fix anything that stutters.

**GOTCHAS / FACTS:**
- **Tenant‑context seeding:** bootstrap the host app, `tenancy()->initialize($tenant)`, then factories or bulk `DB::table()->insert` (attendance is keyed by `user_id`, not employee_id). Reusable scripts are in the session scratchpad; HRM has full factories under `packages/aero-hrm/database/factories`.
- **Run package unit tests from the HOST with an ABSOLUTE path** (no `packages/` junction in the host): `cd c:/laragon/www/aeos365 && vendor/bin/phpunit "c:/laragon/www/Aero-Enterprise-Suite-Saas/packages/aero-platform/tests/.../XTest.php"`.
- **HRMAC:** super‑admins now bypass both the route middleware AND the in‑controller `Gate::authorize('hrmac', …)` (fixed in `HRMACServiceProvider::registerHrmacGate`). If a new HRM page 500s/denies, check for another `$request->date($k, now())` misuse (2nd arg is a FORMAT) and central‑vs‑tenant connection.
- **Payroll route** `/hrm/payroll/runs` 404'd — find the real route via `route:list | grep hrm.*payroll`.
- Backend HRM edits land in `aero-hrm`; shared HRMAC in `aero-hrmac`. Every fix affects SaaS + standalone — don't break standalone boot.
- After each meaningful chunk: requesting‑code‑review, fix Critical/Important, update [[fyp-presentation-deadline]] + the SDD ledger.

THEN: offer finishing‑branch options. Ask me before pushing anything.
