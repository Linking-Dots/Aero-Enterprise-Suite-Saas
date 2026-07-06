# Guided Live Demo — demo.aeos365.com

**Date:** 2026-07-06
**Author:** Emam Hosen (Boss) + Claude
**Scope:** `packages/aero-*` (tenant demo login, tour engine, tour content, reset job) + landing CTA
**Status:** Approved (design), pending implementation → then full prod deploy
**Deadline context:** FYP examiner demo (see [[fyp-presentation-deadline]])

---

## 1. Goal

Turn `demo.aeos365.com` (the democorp flagship tenant) into a **self-explaining, self-serve live demo**: a visitor arrives from the landing page, logs in with exposed credentials, and is walked through the product by an **interactive, industry-standard tooltip/coach-mark tour**. The demo stays clean via nightly auto-reset.

Grounded in 2025 onboarding best practice (Appcues, Userpilot, Whatfix, UX Design Institute): short tours to the "aha moment" (3–5 steps; completion collapses past 7), interactive > passive, 1–2 sentence second-person tooltip copy, progressive disclosure, modal-greeting → tooltip-nav → action-tooltip pattern, visible progress.

## 2. Decisions (locked)

| Decision | Choice |
|---|---|
| Interaction model | Self-serve interactive tooltip tour, auto-starting after login |
| Entry | Landing "See Demo" → `demo.aeos365.com/login` with credentials exposed + prefilled + one-click "Enter the demo" |
| Tour engine | `driver.js`, themed with `--aeos-*` tokens, wrapped in AEOS `useTour` (Inertia-aware, motion-gated) |
| Trigger | Auto-start once per **browser** (localStorage) on the demo tenant — shared login means per-user "seen" fails; Skip always visible; "Restart tour" in Help |
| Tour length | One ~5-step grand tour to the aha moment; per-area mini-tours deferred (YAGNI) |
| Demo integrity | **Nightly (+ pre-presentation) auto-reset** re-seeds democorp + landlord demo; demo-tenant guardrails (no real email/SMS, protect demo admin) |
| Deploy | Build + verify locally, then single full deploy: code + fresh builds (both hosts) + full DB mirror (landlord + demo tenant) to prod |

## 3. Components

### 3.1 Demo login page (demo tenant only)
- Detect the demo tenant (subdomain `demo` / a `is_demo` flag on the tenant). On the login page, render a **demo banner** component (`@aero/ui`, theme-tokened) showing email + password, **prefill** the form, and a primary **"Enter the demo"** button that submits.
- Non-demo tenants: unchanged login.

### 3.2 Tour engine — `useTour` + `TourProvider`
- Thin wrapper over `driver.js`. Responsibilities:
  - Accept an ordered **step list** (`{ element, popover:{title, description}, page? }`).
  - **Inertia-aware**: when a step targets another page, `router.visit(page)` then resume at the correct step on `finish`/navigation (persist current step index in sessionStorage).
  - **Motion-gated**: read `useTheme().motion`; `reduced`/`off` → disable driver animation/smooth-scroll.
  - **Trigger policy**: `useTour({ autoStartKey })` — on demo tenant, auto-start if `localStorage[autoStartKey]` unset, then set it. Expose `start()`/`stop()` for the Help "Restart tour".
  - Theme: a small CSS file mapping driver.js classes to `--aeos-*` tokens (popover bg/border/radius/text, overlay, buttons) so it obeys mode/accent/radius/borders.
- Progress: driver.js `showProgress` ("Step X of Y").

### 3.3 Tour content (grand tour, ~5 steps)
1. **Welcome modal** (centered, no target): "You're in a live demo of AEOS365 — click around freely, nothing here is permanent."
2. **Dashboard KPIs** — "Your command center: headcount, attendance, payroll at a glance."
3. **Employees** (navigate to Employees) — "Your people directory — search, filter, drill into anyone."
4. **Approve a Leave** (navigate to Leave; highlight a pending request's Approve) — "Approve time-off in one click — try it."
5. **Payroll** (navigate to Payroll) — "Run or preview payroll here." → **Finish card**: "That's the tour — explore freely, or restart anytime from Help."
- Elements get stable `data-tour="..."` anchors on the target pages (dashboard KPI strip, employees table, a pending-leave Approve button, payroll run button).

### 3.4 Demo integrity
- **Reset job**: an artisan command `demo:reset` that (a) re-runs `HrmDemoSeeder` for democorp within tenant context and (b) re-runs `PlatformDemoSeeder` for landlord demo rows. Idempotent seeders already support re-run. Scheduled nightly (Asia/Dhaka) + runnable on demand before the presentation.
- **Guardrails** (demo tenant only, gated by `is_demo`): mail/SMS transport swallowed or routed to log; block deleting the demo admin user; keep writes safe (reset heals anything else).

### 3.5 Landing CTA
- Wire the marketing "See Demo" / "Live Demo" button to `https://demo.aeos365.com/login` (prod) / local equivalent.

## 4. Data flow
Visitor → landing CTA → demo login (banner+prefill) → submit → tenant dashboard → `TourProvider` auto-starts grand tour → steps navigate via Inertia across HRM pages → finish → free exploration → nightly `demo:reset` restores state.

## 5. Testing / acceptance
- Local (democorp at `http://democorp.aeos365.test` or the demo subdomain): tour auto-starts once per browser, advances across Dashboard→Employees→Leave→Payroll, Skip/Restart work, progress shows, **0 console errors**, theme-reactive (mode/accent/radius/borders), motion=reduced disables animation, responsive 390/768/1440.
- `php artisan demo:reset` returns democorp + landlord to seeded state (verify row counts).
- Guardrails: no real email/SMS sent from demo; demo admin cannot be deleted.
- Then the full deploy (code + DB mirror), re-confirmed before the destructive prod import.

## 6. Out of scope (YAGNI)
Per-area mini-tours; AI-personalized flows; per-visitor sandboxed tenants; analytics on tour completion. Non-demo tenants keep tours opt-in only.

## 7. Risks
- Cross-page tour resume is the trickiest bit (Inertia nav mid-tour) — keep step→page mapping explicit and persist the index.
- driver.js theming must cover light+dark; verify both.
- Full prod DB mirror is destructive — re-confirm with Boss immediately before import; treat prod as demo-only (no real signups to preserve, per Boss).
