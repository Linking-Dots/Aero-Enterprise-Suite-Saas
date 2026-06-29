# Tenant Settings Cluster — Redesign Design (Settings Standard)

**Date:** 2026-06-29
**Author:** Claude (Opus 4.8) with Boss (Emam Hosen)
**Status:** Approved (design) — pending spec review
**Scope:** Priority #3 of the tenant-page redesign iteration. The tenant **Core settings cluster** (9 pages) in `packages/aero-ui/resources/js/Pages/Core/Settings/`.

This page-cluster establishes the canonical **"settings standard"** the rest of the settings pages (Platform/Admin, HRM/Settings, future product settings) will follow — the way the Users page (`Pages/Core/Users/Index.jsx`) set the canonical **"list standard."** Out of scope: `Platform/Admin/Settings/*` and `HRM/Settings/*` clusters (do NOT touch).

---

## 1. Decision: Fork B — Unified `SettingsLayout`

Settled with the Boss. Of the two forks:

- **Fork A** — 9 separate routes + 9 nav links, each rebuilt on a shared form shell; section switching = full page nav.
- **Fork B (chosen)** — one nav link + a persistent left section-rail with in-place section switching, while **each section keeps its own route / controller / save endpoint / HRMAC gate**. Only the *presentation* is unified via a persistent Inertia layout.

Rationale: canonical settings pattern (GitHub/Stripe/Linear), declutters nav 9→1, no god-controller (per-section routes + HRMAC gates stay intact), gives a home for the right-side command-shell `SettingsRail`.

---

## 2. Current state (verified against code)

- **9 section pages** in `Pages/Core/Settings/`: `SystemSettings.jsx` (General), `Security.jsx`, `Localization.jsx`, `Branding.jsx`, `Mail.jsx`, `Integrations.jsx`, `PasswordPolicy.jsx`, `IpWhitelist.jsx`, `EmailTemplates.jsx`.
- **Controllers** in `aero-core/src/Http/Controllers/Settings/`: `SystemSettingController`, `SecuritySettingsController`, `LocalizationSettingsController`, `BrandingSettingsController`, `MailSettingsController`, `IntegrationsController`, `PasswordPolicyController`, `IpWhitelistController`, `EmailTemplateController`.
- **Routes**: `aero-core/routes/web.php:626–724` (`core.settings.*` group) + `:1316–1320` (`core.settings.integrations.*`). Each section has its own GET (view-gated) and save endpoint (edit/update-gated). Special endpoints: `mail.test`, `password-policy.test`, `ip-whitelist.add-ip/remove-ip/test-ip`, `email-templates.{store,update,destroy,preview}`, `system.test-email`/`system.test-sms`.
- **Nav**: `config/module.php:504–619` — Settings group (`code: settings`, `priority: 99`, route `/settings/system`) with **9 child components**, each with its own `route` + HRMAC `actions`.
- **Inconsistency to fix**: `SystemSettings.jsx` is a stale "hub" (button-tabs that navigate away + a raw key→value dump). `Security.jsx` is already a proper `FormPageLayout` form. No shared standard, no rail, no skeletons.

**Building blocks available** (`@aero/ui`): `IndexPageLayout`/`FormPageLayout`/`DetailPageLayout`/`PageHeader`/`Tabs`; `Field, Input, Textarea, Select, Toggle` (`Switch` = alias of `Toggle`), `Checkbox, FileInput, DatePicker`; `Card/CardHeader/CardBody` (`CardContent` = alias of `CardBody`); `Button, Drawer, Menu, EmptyState, Skeleton, useToast, useHRMAC`; `App` shell with `rail={}` (the `UsersRail`/`RolesRail` pattern).

---

## 3. Architecture

### 3.1 `SettingsLayout.jsx` (new, persistent Inertia sub-layout)

`packages/aero-ui/resources/js/Pages/Core/Settings/SettingsLayout.jsx`. Used as the `.layout` by all 9 section pages so it **stays mounted across section switches** (Inertia persistent layout). Composition:

- **PageHeader** — breadcrumb `Dashboard › Settings`, title `Settings`, description ("Manage your organization's configuration").
- **Left section rail** (in-content, ~220px) — grouped section links, active item highlighted from the current URL, **HRMAC-filtered** (a user only sees sections they can `view`). Items are Inertia `<Link>`s → the section route; because the layout persists, switching feels in-place. The active section's panel renders in the content slot.
- **Content slot** — `{children}` = the active section's form sections.
- A `pageTitle`/`activeSection` prop (or URL-derived) drives the active highlight + the per-section sub-title in the content header.

Each section page declares:
```js
SectionPage.layout = page => (
  <App title="…" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="security">{page}</SettingsLayout>
  </App>
);
```

### 3.2 `settingsSections.js` (new, single source of truth)

`packages/aero-ui/resources/js/Pages/Core/Settings/settingsSections.js` — one array describing each section: `{ key, label, routeName, icon (registered name), permission, group }`. Consumed by both `SettingsLayout` (rail) and `SettingsRail` (command-shell rail), filtered with `useHRMAC(permission)`. Groups:

- **GENERAL** → General (`core.settings.general.view`), Localization, Branding
- **SECURITY** → Security, Password Policy, IP Access
- **COMMUNICATIONS** → Mail (SMTP), Email Templates, Integrations

### 3.3 `SettingsRail.jsx` (new, command-shell right rail)

Mirrors `UsersRail.jsx` / `RolesRail.jsx`. Shown only by the command shell (`App rail={}`). Content: current section name, a "jump to" quick list of the HRMAC-visible sections, and a save-state hint. Decoupled from page state (uses `usePage().props` + `<Link>`, same pattern as the existing rails).

### 3.4 Section page anatomy (per-section standard)

Each section page = `useForm()` + `router.*` (Inertia v2), rendered inside `SettingsLayout`:

- **Form sections** = `Card`/`CardContent` blocks with a small section header, built **only** from `@aero/ui` form components.
- **Sticky section action bar**: `[Reset] [Save changes]`.
  - `Save` disabled until **dirty** (`useForm().isDirty`).
  - **saving** state via `processing`.
  - **saved** → `toast.success` on success; `form.reset()`/snapshot clears dirty.
  - **validation** → inline `Field error={errors.x}`; `toast.error("Please fix the errors below.")`.
- **Loading skeleton** on section entry: subscribe to `router.on('start'/'finish')` (mirrors Users) → render `Skeleton` rows while the partial reload is in flight.
- **Permission-gated** save controls via `useHRMAC(...edit/update)` (read-only when absent).

### 3.5 Special section shapes (preserved, fitted into the standard)

- **General** (was the hub): rebuilt as a real form section (org/system fields + existing `system.test-email`/`system.test-sms` actions). The old button-tab nav + raw key-value dump are removed.
- **Mail (SMTP)**: SMTP form + **"Send test email"** (`mail.test`).
- **Password Policy**: policy form + **"Test policy"** (`password-policy.test`).
- **IP Access**: policy toggle + **managed IP list** (add/remove rows via `add-ip`/`remove-ip`) + **"Test IP"** (`test-ip`).
- **Email Templates**: a small **managed list inside the shell** (template list + edit via `Drawer`; `store`/`update`/`destroy`/`preview`), not its own top-level page.
- **Branding**: brand fields + **logo upload** (`FileInput`).
- **Localization / Integrations / Security**: straight form sections.

---

## 4. Theme + standards compliance

Per [[theme-consistency-all-pages]] (non-negotiable):

- Every surface — section rail, form cards, sticky action bar, drawers — must be a **card-style-aware surface** responding to `body[data-card-style="X"]`, not only `.aeos-card-auto`. The section-rail container and content panels are card surfaces.
- **Tokens only** (`var(--aeos-*)`); **zero** inline `style={}`; **`@aero/ui` components only**; **registered icon names only** (unknown names log a `console.warn` — must be 0 warnings).
- All theme dimensions (mode / card-style / density / radius / borders / motion / accent) must visibly affect every settings surface.

---

## 5. Backend / nav changes (minimal)

- **`config/module.php`** Settings group: keep the 9 `components` (their HRMAC `actions` gate the rail items) but ensure the **nav renders a single "Settings" entry** → `/settings/system` instead of 9 child links. Confirm how the nav generator turns `components` into links and adjust so only the parent shows (e.g. group-level nav, children not emitted as nav links).
- **No controller/route signature changes.** Each section keeps its own GET + save endpoint + HRMAC middleware. The redundant `integrations.index` currently points at `SystemSettingController@index` — verify it serves the Integrations page (it has its own `IntegrationsController` for `update`); align the GET if needed.
- The old `SystemSettings` hub component is replaced by the **General** section page.

---

## 6. Increment / commit plan (one commit each, NO push)

Each increment is verified **live** at `democorp.aeos365.test/settings/*` (login `admin@democorp.com` / `Aeos365!Admin`) with **0 console errors/warnings** before its commit. Vite: `cd c:\laragon\www\aeos365 && npm run dev` (binds `aeos365.test:5173`, writes `public/hot`).

1. **Foundation + General**: `SettingsLayout` + `settingsSections.js` + `SettingsRail` + General section (replaces hub). Verify live.
2. **Security cluster**: Security + Password Policy + IP Access.
3. **General cluster rest**: Localization + Branding.
4. **Communications cluster**: Mail + Email Templates + Integrations.
5. **Nav collapse**: `config/module.php` nav 9→1 + redirect/cleanup of the old hub + final live sweep.

---

## 7. Success criteria

- All 9 settings sections reachable via the unified shell; one "Settings" nav link.
- Persistent left rail, HRMAC-filtered, active-section highlight; section switching does not unmount the rail.
- Every section: dirty/saving/saved/validation states + toasts + loading skeleton.
- Right-side `SettingsRail` shows in the command shell.
- Special actions (mail test, password test, IP add/remove/test, template CRUD, logo upload) all work.
- 0 console errors/warnings on every section; every Theme Studio dimension visibly affects every surface.
- No `style={}`, no non-`@aero/ui` components, no unregistered icons.
- `Platform/Admin/Settings/*` and `HRM/Settings/*` untouched.

---

## 8. Open items to confirm during implementation (not blockers)

- Exact nav-generator behavior for collapsing children (read the nav builder before editing `config/module.php`).
- Whether `Integrations` GET should move off `SystemSettingController@index` to a dedicated `IntegrationsController@index`.
- Exact field sets per section (read each current page + controller during the plan).
