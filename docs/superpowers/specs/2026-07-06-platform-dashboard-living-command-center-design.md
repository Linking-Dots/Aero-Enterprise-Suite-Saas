# Platform-Admin Dashboard — "Living Command Center" Redesign

**Date:** 2026-07-06
**Author:** Emam Hosen (Boss) + Claude
**Scope:** `packages/aero-platform` (backend data) + `packages/aero-ui` (Dashboard page & widgets)
**Route:** `admin.aeos365.test/dashboard` (`platform.admin.dashboard`)
**Status:** Approved (design), pending implementation

---

## 1. Goal

Replace the current bespoke widget-grid dashboard with a **100/100, best-in-class platform-admin command center** that *feels alive* — metrics count up, trends breathe via sparklines, system status pulses, the activity stream flows — while being demo-perfect for the FYP examiner (July 10 2026).

Non-negotiables:
- **Theme Studio compliance (hard gate):** every element reacts to `mode / accent / radius / density / borders / card-style / motion`. No hardcoded colors, no `style={}` for theme-derived values, all motion gated on the reduce-motion setting.
- **Responsive bar:** bitwise-perfect at 390 / 768 / 1440 / ultrawide — no clip, overlap, crop, or horizontal body scroll.
- **0 console errors.**
- Renders in **both Sidebar and Command shells.**
- **No empty states** (data seeded via `PlatformDemoSeeder`).

## 2. Decisions (locked)

| Decision | Choice | Rationale |
|---|---|---|
| Structure | Command-center IA, ground-up (not list-page `IndexPageLayout`) | A landing dashboard is an overview, not a table page. |
| Auto-refresh | Gentle ~30s poll, **visibility-gated**, only "live" props, no full reload / layout shift | Genuinely living without demo risk. |
| Layout | **Fixed curated responsive** (drop drag-and-drop) | Eliminates persistence + recharts re-mount flicker; guarantees pixel-perfection. "Evolving" delivered via living content. |
| Motion | Central `useReducedMotion()` from theme motion setting gates ALL animation | Accessibility + theme compliance. |

## 3. Information Architecture (top → bottom)

1. **Command strip** — greeting, live clock, system-pulse dot (severity-reactive heartbeat), Alerts(n), Refresh, ⌘K search, Settings.
2. **North-star KPI band** — 5 hero metrics, each with **count-up value + 30-day sparkline + delta chip vs previous period**: MRR, ARR, Active tenants, Net-new MRR (30d), Churn (30d).
3. **Revenue intelligence** (left) — MRR/ARR/Plan-vs-Product bar trend + footer (Plan MRR, Product MRR, ARPT, MoM growth).
4. **Tenant lifecycle** (right) — Signup→Trial→Active funnel + pipeline status counts (active/trial/pending/provisioning/suspended/failed/archived).
5. **Operations pulse** (left) — system-health heartbeat (DB/cache/queue/storage/mail/search) + 24h error trend + severity-ranked alerts.
6. **Growth & acquisition** (right) — new signups (30d), leads by status, top sources, trial→paid conversion.
7. **Engagement** — module-adoption bars + feature-usage intensity (from `feature_usage_events`).
8. **Live stream** (left) — streaming activity/audit feed (newest fades+slides in).
9. **Quick actions + Recent tenants** (right) — action grid + recent-tenants table with `<Avatar>` + status badges.

## 4. "Living/breathing" mechanics (all motion-gated)

- **Count-up** hero numbers on mount (0→value, ~600ms ease-out); instant if reduce-motion.
- **Sparkline** under every hero KPI (30-day series from `platform_stats_daily`).
- **System-pulse dot** — soft heartbeat; color/severity from real health + alerts.
- **Streaming activity feed** — light poll (~30s) refreshes only live props; new items animate in.
- **Delta chips** vs previous period, semantic up/down color.
- **Skeleton shimmer** on first paint instead of `—`.

## 5. Backend (data plumbing)

`PlatformDashboardService` + widgets supply **eager** props (critical above-the-fold data is NOT permanently-empty lazy). A small "live" subset stays reloadable for the poll.

New/changed data:
- `heroKpis`: for each of MRR/ARR/active/net-new/churn → `{ value, deltaPct, spark: number[30] }` (spark from `platform_stats_daily`; metrics from `platform_metrics_daily`).
- `revenueTrend`: existing `BillingOverviewWidget.trends` (already correct) + plan/product split.
- `lifecycleFunnel`: signup→trial→active counts + pipeline status buckets.
- `growth`: new signups, leads-by-status, top sources, trial→paid rate.
- `engagement`: module adoption + feature-usage intensity.
- `liveStream`: recent `platform_audit_logs` / activity (the polled prop).

Fixes already applied (kept): `growth()` decimal→float cast; `RecentActivityWidget` route name; mount-time hydration (superseded by eager props).

All decimals cast to float at the boundary (MySQL returns strings). All timestamps Asia/Dhaka.

## 6. Frontend structure

`Pages/Platform/Admin/Dashboard/Index.jsx` → curated responsive grid (CSS grid + container queries in `dashboard.css`), no `DraggableDashboard`.

New/upgraded widget components under `Dashboard/widgets/`:
- `CommandStrip`, `HeroKpiBand` + `HeroKpiCard` (count-up + `Sparkline`), `RevenueTrend` (from MrrTrend, animation off, memoized), `LifecycleFunnel`, `OpsPulse` (health heartbeat + error trend), `GrowthPanel`, `EngagementPanel`, `LiveStream`, `QuickActions`, `RecentTenants`.
- Shared: `useCountUp`, `useReducedMotion`, `usePolling(visibilityGated)`, `Sparkline`, `PulseDot`, `DeltaChip`.

All visuals via `@aero/ui` primitives + CSS tokens (`--aeos-*`). Charts: recharts with `isAnimationActive={false}`, memoized data, CSS-var fills.

## 7. Theme Studio compliance checklist (verify live)

Toggle each in the drawer and confirm the whole dashboard responds:
mode (light/dark) · accent · radius · density · borders on/off · card-style (flat/bordered/elevated/etc.) · motion (full/reduced → animations stop).

## 8. Responsive plan

- **1440+/ultrawide:** 2-col main region (60/40), north-star band 5-up; ultrawide caps max-width so it doesn't sprawl.
- **768:** band wraps to 2–3 per row; two-col regions stack to one col where cramped.
- **390:** single column; band 1-up scroll-stack; tables in `.aeos-table-wrap` (scroll, never clip); no horizontal body scroll.

## 9. Out of scope

Real server metrics (CPU/mem) — remain N/A/derived. No new DB migrations (uses seeded tables). No changes to other admin pages (separate STEP 2 passes).

## 10. Acceptance

Live screenshots in **both shells** at 390/768/1440/ultrawide, all populated, 0 console errors, theme-drawer toggles all reflected, poll updates the stream without layout shift, reduce-motion kills animation.
