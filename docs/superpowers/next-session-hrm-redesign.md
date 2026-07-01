# Next session — FYP HRM redesign (continue)

Continue the FYP demo prep (Final Year Project examiner demo, **July 10 2026** — judged on scope, depth, working functionality, and **bitwise-perfect** polish). Work on `main` in place (vendor junctions, no worktree). Address me as **Boss**; show tokens burned per reply. Screenshot every page you touch, live, in the sidebar shell (both shells if quick). **Do NOT push unless I say so.**

READ FIRST (memory): [[hrm-v2-consolidation]] (the load-bearing one — v1/v2 dup, tenant route-model binding gotcha, the redesign pattern + per-page UI-QA bar), [[fyp-presentation-deadline]] (locked demo script), [[command-mode-pages]], [[show-live-ui]], [[theme-consistency-all-pages]]. Also `.superpowers/sdd/progress.md`.

## Architecture facts (do not re-derive)
- **HRM relates to `Employee` only** (1 User ↔ 1 Employee). `User` is core auth/access only. Display person names via `employee.user.name`. Never wire HRM data to `user_id`.
- **Tenant route-model binding is broken app-wide** (SubstituteBindings runs before tenancy). In HRM v2 controllers resolve models manually: `Model::findOrFail(request()->route('param'))` — do NOT type-hint the model (the `{tenant}` domain param leaks into a scalar arg).
- Inertia serializes relations as snake_case (`leaveType` → `leave_type`).
- Nav routes live in `packages/aero-hrm/config/module.php`; HRM pages in `packages/aero-ui/resources/js/Pages/HRM`; controllers in `packages/aero-hrm/src/Http/Controllers`.

## Redesign pattern (one pass per page = make functional + redesign together; Boss rejected two-iteration approach)
1. Controller `index()` supplies `stats` (grouped counts / sums).
2. Page: `IndexPageLayout kpis={[<Stat title value icon iconTone/>]}` — **max 3 KPIs on rail pages** (4 icon+title+value cards collide in the rail-narrowed row; 4th metric goes in the rail). Valid `icon` names: alertCircle alertTriangle arrowUp/Down/Left/Right arrowPath bell calendar chartBar check checkCircle clock command document download external filter folder home inbox info layout link mail menu minus moon pencil phone pin plus search settings sort sparkles star sun trash trending upload user users x. `iconTone`: cyan(default)/amber/indigo/success.
3. `<Avatar name={...} size={28}/>` on every person cell.
4. A per-page rail (mirror the 4 built ones: `LeaveRail`/`EmployeesRail`/`AttendanceRail`/`PayrollRail`) passed via `<App railTitle rail={<XRail/>}>`.
5. **Per-page UI-QA (Boss requirement, bitwise-perfect): no overflow/clip/overlap, responsive at all sizes (check ~390px), scrollable, all data present (no "—" where data exists), 0 console errors.**

## DONE (committed + pushed on main: `65aa2dfa8` backend, `d444c25ec` frontend)
- Consolidated Payroll+Leave to **v2**, retired legacy payroll routes, fixed ~10 functional bugs (binding, Payslip Spatie-trait 500, audit signature, prop mismatches).
- Seeded democorp: 8 leave types, 297 leave apps, 1,896 balances, 3 payroll runs × 237 payslips, 7 pay components, 5 salary structures, tax brackets; backfilled 5,750 attendance `employee_id`.
- **4 main Act-3 pages redesigned** (KPI + rail + avatars, verified): Employees, Attendance/Daily, Leave/Applications, Payroll/Runs.

## REMAINING (in order)
1. **HRM sub-pages** (same one-pass pattern): Leave **Types**, Leave **Balance**, Leave **Calendar**, Leave **Applications/Show**; Payroll **Structures**, **Components**, **Settings/Tax**, **Runs/Show** polish. Watch for the same untested-page bugs (snake relation keys, `employee.user.name`, prop-name mismatches, empty lists) on each.
2. **Payslip header nits** (`Payroll/Payslips/Show.jsx`): name shows "Employee" (read `employee_name`/snapshot), period shows blank (read `period_start/period_end`).
3. **Attendance data realism** (optional but Boss-flagged): reseed punch times to ~9am-in / ~6pm-out, ~12% late (currently ~3pm-in / night-out, ~60% late).
4. **Cleanup**: delete dead v1 controllers/methods (Employee/PayrollController payroll methods; LeaveController `index2`/`HRM/LeavesAdmin`); drop `HrmGradeSeeder` + `HrmSalaryComponentSeeder` from `HrmDemoSeeder` chain.
5. Onboarding-flow polish → seed+verify the spine → full dress rehearsal (SaaS + standalone).

## Gotchas
- Tenant seeding: `tenancy()->initialize($tenant)` then factories/bulk-insert via a scratchpad script run with `php artisan tinker <file>` from the host `c:\laragon\www\aeos365` (Tenant model = `Aero\Platform\Models\Tenant`; democorp domain `democorp.aeos365.test`). Vite dev server is up (HMR) so JSX edits hot-reload — no build needed.
- Playwright MCP profile can stick on a stale lock; kill `chrome.exe` procs matching the `mcp-chrome-*` profile + remove `Singleton*` files, then retry.
- Screenshots land in the monorepo root as `*.png` — don't commit them.
- After each page: code-review, fix Critical/Important, update [[hrm-v2-consolidation]] + `.superpowers/sdd/progress.md`.

TEST TENANT: `democorp.aeos365.test` — `admin@democorp.com` / `Aeos365!Admin` (Super Administrator).
