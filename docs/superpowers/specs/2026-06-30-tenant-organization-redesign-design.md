# Tenant Organization Cluster — Settings-Shell Redesign (Design)

**Date:** 2026-06-30 · **Priority:** #4 (Organization) · **Branch:** main (in-place; live host consumes `@aero/ui` via vendor junctions)

## Goal
Apply the canonical "settings-shell standard" (established by the Settings cluster, commits `7c315ade1..7589f421d`) to the **Organization** cluster, and collapse its nav from 5 links to 1. Presentation-only change: endpoints, HTTP verbs, fields, and validation stay exactly as they are.

## Reuse decision
Reuse the shared generic shell **as-is** from `packages/aero-ui/resources/js/components/settings/`:
`SettingsShell`, `SettingsSection`, `SettingsNavRail`, `useSettingsGroups`. They are already parameterized by a `groups` config (no Core/Platform coupling). Renaming the folder would churn 12 Settings imports + Platform plans for zero behavior gain. The `.settings-*` CSS classes are presentational and harmless on Organization.

## New Core wrappers (mirror `Pages/Core/Settings/`)
- `Pages/Core/Organization/organizationSections.js` — `ORG_GROUPS` rail config (heroicon component refs, per-section `.view` permission).
- `Pages/Core/Organization/OrganizationLayout.jsx` — wraps `SettingsShell` with `ORG_GROUPS`, title "Organization", description, breadcrumb Dashboard › Organization.
- `Pages/Core/Organization/OrganizationRail.jsx` — wraps `SettingsNavRail` with `ORG_GROUPS`.
- `Pages/Core/Organization/OrganizationSection.jsx` — re-export of shared `SettingsSection`.

## Rail grouping (best-practice, evolving)
- **Company** → Profile, Tax / Legal Identity
- **Operations** → Fiscal Year
- **Directory** → Addresses, Contacts

## Sections (port the 5 existing pages)
Each page: drop `FormPageLayout` + per-page action buttons + per-page breadcrumb; wrap content in `<form onSubmit={handleSave}>` + `SettingsSection` (sticky dirty/saving Save bar; Save is `type=submit`, no `onClick`). Add `isDirty` from `useForm`. `.layout` static = `<App title railTitle rail={<OrganizationRail/>}><OrganizationLayout active="key">{page}</OrganizationLayout></App>`.

| Section | Page file | active key | GET route | POST route | rail `.view` gate | save gate |
|--|--|--|--|--|--|--|
| Profile | `Profile.jsx` | `profile` | `core.organization.profile` | `core.organization.profile.update` | `core.organization.org_profile.view` | `core.organization.org_profile.update` |
| Tax / Legal Identity | `Identity.jsx` | `identity` | `core.organization.identity` | `core.organization.identity.update` | `core.organization.org_identity.view` | `core.organization.org_identity.update` |
| Fiscal Year | `FiscalYear.jsx` | `fiscal` | `core.organization.fiscal-year` | `core.organization.fiscal-year.update` | `core.organization.fiscal_year.view` | `core.organization.fiscal_year.manage` |
| Addresses | `Addresses.jsx` | `addresses` | `core.organization.addresses` | `core.organization.addresses.update` | `core.organization.org_addresses.view` | `core.organization.org_addresses.manage` |
| Contacts | `Contacts.jsx` | `contacts` | `core.organization.contacts` | `core.organization.contacts.update` | `core.organization.org_contacts.view` | `core.organization.org_contacts.manage` |

All saves are **POST** → `useForm().post()`. Save gate codes already correct in current pages.

**List-shaped sections** (Addresses, Contacts): Save All as `onSave`; "Add row" in `SettingsSection` `footerExtra` (`type=button`); per-row Remove kept; no Reset. Adding a row mutates form data → `isDirty` true → Save enabled.

## Nav collapse 5 → 1
Add `'collapse_nav' => true` to the `organization` submodule in `packages/aero-core/config/module.php` (~line 634). Flag is honored in BOTH registration paths (`AbstractModuleProvider::registerNavigation` + `AeroCoreServiceProvider::registerCoreNavigation` — last-wins; that was the Settings root cause). Collapsed leaf keeps `access 'core.organization'`, path `/organization/profile`. Result: ONE "Organization" nav link; the in-page rail owns the 5 sections.

## Gotchas (carried from Settings)
No double-submit (Save `type=submit`, no `onClick`); toggles use `e.target.checked` (n/a here — only Checkboxes, already correct); registered icon names only (we use heroicon component refs, not string `leftIcon`/`Icon`, so registry irrelevant); `@aero/ui` only, no inline `style={}`, Inertia v2 (`router.*`/`useForm`); single centralized `<style>` (provided by `SettingsShell`); theme consistency (rail + cards + sticky bar respond to `body[data-card-style]` + accent).

## Verification
Vite dev (`public/hot`). Login `democorp.aeos365.test` (admin@democorp.com / Aeos365!Admin). Click all 5 sections via in-page rail: 0 console errors/warnings each; Theme Studio card-style + accent reach every surface; Save one section → exactly ONE POST; confirm via authenticated HTTP nav prop that Organization shows childCount 0 and main nav has exactly one `/organization` link.

## Out of scope
No backend/endpoint/route changes (except the `collapse_nav` config flag). No field/validation changes. No new sections.
