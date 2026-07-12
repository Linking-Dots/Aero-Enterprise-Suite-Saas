# White-Label & Branding 100/100 — Design Spec

**Date:** 2026-07-12 · **Status:** Approved direction (shared engine + platform extras)
**Goal:** Branding/white-labeling feature-complete on BOTH ends; every setting actually
consumed at runtime. Meridian = platform default brand and universal fallback.

## Verified current state

- Tenant branding editor saves (name, 1 color, logo, favicon) but **nothing consumes it**
  (shell renders hardcoded `AeosLogo`; dark-logo/login-bg/removal code dead in
  `BrandingSettingsController`).
- Platform white-label module (domains/SSL/branding/CSS/DKIM) has routes + HRMAC +
  services + `TenantBranding` model, but `/white-label/css` and `/white-label/email`
  render **missing pages**; nothing injects branding at tenant runtime.
- `Aero\Notifications\Contracts\BrandingResolver` is **never bound** — all mail unbranded.
- Meridian set shipped in `branding/` (4fe468f81) but unwired.

## Architecture — shared engine, platform additions

### Shared (one implementation, both ends)

| Layer | Piece | Home |
|---|---|---|
| Contract | `BrandingPayload` (name, tagline, logo_light, logo_dark, favicon, login_background, primary_color, accent_color, email_from_name, email_from_address) + `BrandingRepository` interface | aero-kernel (context-free, HRMAC pattern) |
| Resolution | Fallback chain: tenant override → platform default → Meridian baked-in | resolver in aero-kernel; bindings per end |
| Editor UI | `<BrandStudio>` — asset tiles (upload/replace/remove), colors + AA contrast check, identity, email sender, live previews (shell/login/email/tab), reset-to-default | @aero/ui component, mounted by both ends |
| Service | `BrandingService` — media collections, validation, removal, reset, audit | interface shared; core binds over tenant `SystemSetting`, platform over `PlatformSetting` + `TenantBranding` |
| Runtime | Blade `<head>` partial (favicon/title/meta), `AppBrand` consumes shared `branding` prop, primary/accent → `--aeos-*` theme tokens | @aero/ui + middleware share (both `HandleInertiaRequests`) |
| Email | `BrandingResolver` implementation over the same chain | aero-core binding; consumed by aero-notifications |
| Defaults | Meridian mark/lockups/favicons bundled | @aero/ui assets |

### Tenant-only
- Entitlement gate: plan feature `white_label`; without it Brand Studio shows locked
  upsell state (Aeon-quota pattern). Enforced server-side on update routes.

### Platform-only (White-Label Command Center, `/white-label`)
- KPI band: branded tenants, custom domains (verified), SSL expiring ≤30d, DKIM verified.
- Tabs: **Domains & SSL** (add/verify DNS/provision/renew/revoke + expiry watch),
  **Branding** (per-tenant `<BrandStudio>` in drawer + force-reset),
  **Custom CSS** (editor, size guard, kill-switch disable),
  **Email / DKIM** (configure/verify/rotate),
  **Defaults** (platform's own brand — same `<BrandStudio>`).
- Workbench-kit tables, row drawer, filters, JSON-mode fetches, full audit.
- Build the 2 missing pages; retire `Platform/Admin/Settings/Branding.jsx` into Defaults tab.

## Build order

1. **Phase 1 — Pipeline:** kernel contract + resolver chain + per-end bindings; runtime
   injection (Inertia share, blade head, theme tokens, `AppBrand`); `BrandingResolver`
   bound; Meridian wired as platform default (AeosLogo → Meridian mark, host favicons).
2. **Phase 2 — Tenant Brand Studio:** full editor + previews + CRUD + reset + entitlement
   gate + audit; controller rewritten (dead paths become real: dark logo, login bg,
   removals).
3. **Phase 3 — Platform command center:** `/white-label` rebuilt (5 tabs, KPIs, drawer);
   missing pages built; per-tenant branding actually injected on tenant requests
   (custom-domain requests included).
4. **Phase 4 — Documents & polish:** notifications templates consume branding; invoice/PDF
   headers; UAT sweep both ends with live screenshots.

## Acceptance

- Change any Brand Studio setting → visible in shell, login, tab, email, invoice without
  code. Reset returns tenant to platform default (Meridian) cleanly.
- Plan without `white_label` → editor locked + upsell; server rejects updates (403).
- All white-label routes render real pages; domain/SSL/DKIM actions round-trip with
  honest status. Everything audited. Dual-mode: standalone uses the same engine with
  platform tier absent (tenant → Meridian).
