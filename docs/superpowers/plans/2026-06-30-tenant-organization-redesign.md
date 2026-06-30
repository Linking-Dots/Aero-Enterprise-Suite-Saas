# Tenant Organization Cluster — Settings-Shell Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the 5-section Organization cluster onto the shared settings-shell standard and collapse its nav 5→1, presentation-only (no endpoint/field/validation changes).

**Architecture:** Reuse the generic `components/settings/` shell (SettingsShell/SettingsSection/SettingsNavRail/useSettingsGroups) via new Core/Organization wrappers + an `ORG_GROUPS` config, exactly as Core/Settings does. Each section page becomes a `<form onSubmit>` wrapping `SettingsSection`. A `collapse_nav` config flag reduces the nav to one link.

**Tech Stack:** React 18, Inertia v2 (`useForm`/`router.*`), `@aero/ui`, heroicons component refs, Laravel 12 config.

## Global Constraints
- `@aero/ui` components only; NO inline `style={}`; single centralized `<style>` (provided by SettingsShell).
- Inertia v2 only (`useForm`, `router.*`); all Organization saves are **POST** → `.post()`.
- Save button is `type="submit"` inside `SettingsSection`; NO `onClick` save handler (double-submit).
- Icons: heroicon component references only (not string `leftIcon`/`Icon` names).
- Rail per-section visibility gates on each item's OWN `.view` code; save gates unchanged (already correct in pages).
- Endpoints, HTTP verbs, fields, validation: UNCHANGED. Theme consistency: rail+cards+sticky bar respond to `body[data-card-style]` + accent (inherited from shared shell).

---

### Task 1: Organization shell wrappers + rail config

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/organizationSections.js`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/OrganizationLayout.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/OrganizationRail.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/OrganizationSection.jsx`

**Interfaces:**
- Produces: `ORG_GROUPS` (default export of section config); `OrganizationLayout({active, children})`; `OrganizationRail()`; `OrganizationSection` (re-export of shared SettingsSection — props: `title, description, canEdit, dirty, processing, onReset, onSave, footerExtra, children`).

- [ ] **Step 1: organizationSections.js**

```jsx
/**
 * organizationSections — the CORE (tenant) Organization section config consumed
 * by the shared settings shell (components/settings/*). Permission codes are the
 * DECLARED config/module.php .view actions per component. Icons are heroicon
 * component refs (the @aero/ui string-name Icon registry does not include these).
 */
import {
  BuildingOffice2Icon,
  IdentificationIcon,
  CalendarDaysIcon,
  MapPinIcon,
  UsersIcon,
} from '@heroicons/react/24/outline';

export const ORG_GROUPS = [
  {
    group: 'Company',
    items: [
      { key: 'profile',  label: 'Profile',              routeName: 'core.organization.profile',     icon: BuildingOffice2Icon, permission: 'core.organization.org_profile.view' },
      { key: 'identity', label: 'Tax / Legal Identity', routeName: 'core.organization.identity',     icon: IdentificationIcon,  permission: 'core.organization.org_identity.view' },
    ],
  },
  {
    group: 'Operations',
    items: [
      { key: 'fiscal',   label: 'Fiscal Year',          routeName: 'core.organization.fiscal-year', icon: CalendarDaysIcon,    permission: 'core.organization.fiscal_year.view' },
    ],
  },
  {
    group: 'Directory',
    items: [
      { key: 'addresses', label: 'Addresses',           routeName: 'core.organization.addresses',   icon: MapPinIcon,          permission: 'core.organization.org_addresses.view' },
      { key: 'contacts',  label: 'Contacts',            routeName: 'core.organization.contacts',     icon: UsersIcon,           permission: 'core.organization.org_contacts.view' },
    ],
  },
];
```

- [ ] **Step 2: OrganizationLayout.jsx**

```jsx
/**
 * OrganizationLayout — Core (tenant) wrapper over the shared SettingsShell.
 * Supplies the Organization section config + header/breadcrumb; all shell
 * behaviour lives in components/settings/SettingsShell.jsx.
 */
import SettingsShell from '@/components/settings/SettingsShell.jsx';
import { ORG_GROUPS } from './organizationSections.js';

export default function OrganizationLayout({ active, children }) {
  return (
    <SettingsShell
      active={active}
      groups={ORG_GROUPS}
      title="Organization"
      description="Manage your company profile, identity, locations, and contacts."
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Organization' },
      ]}
    >
      {children}
    </SettingsShell>
  );
}
```

- [ ] **Step 3: OrganizationRail.jsx**

```jsx
/**
 * OrganizationRail — Core (tenant) wrapper over the shared SettingsNavRail.
 */
import SettingsNavRail from '@/components/settings/SettingsNavRail.jsx';
import { ORG_GROUPS } from './organizationSections.js';

export default function OrganizationRail() {
  return <SettingsNavRail groups={ORG_GROUPS} />;
}
```

- [ ] **Step 4: OrganizationSection.jsx**

```jsx
/**
 * OrganizationSection — re-export of the shared generic section wrapper
 * (components/settings/SettingsSection.jsx). Kept at this path so the
 * Organization section pages import it locally, mirroring Core/Settings.
 */
export { default } from '@/components/settings/SettingsSection.jsx';
```

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Organization/{organizationSections.js,OrganizationLayout.jsx,OrganizationRail.jsx,OrganizationSection.jsx}
git commit -m "Phase3/org: add Organization settings-shell wrappers + rail config"
```

---

### Task 2: Port single-form sections (Profile, Identity, Fiscal Year)

**Files:**
- Modify: `packages/aero-ui/resources/js/Pages/Core/Organization/Profile.jsx`
- Modify: `packages/aero-ui/resources/js/Pages/Core/Organization/Identity.jsx`
- Modify: `packages/aero-ui/resources/js/Pages/Core/Organization/FiscalYear.jsx`

**Interfaces:**
- Consumes: `OrganizationLayout`, `OrganizationRail`, `OrganizationSection` from Task 1.
- Pattern (apply to each): replace `FormPageLayout` + actions + breadcrumb with `<form onSubmit><OrganizationSection .../></form>`; add `isDirty` from `useForm`; `.layout` = `<App title railTitle rail={<OrganizationRail/>}><OrganizationLayout active="KEY">{page}</OrganizationLayout></App>`.

- [ ] **Step 1: Profile.jsx** — keep fields (company_name, legal_name, registration_number, industry, company_size, website, phone, email), INDUSTRIES/COMPANY_SIZES, `post(route('core.organization.profile.update'))`, `useHRMAC('core.organization.org_profile.update')`. Replace the local `Section` helper usage with `Card/CardHeader/CardBody` directly inside `OrganizationSection`. Body:

```jsx
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Select, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import OrganizationLayout from './OrganizationLayout.jsx';
import OrganizationRail from './OrganizationRail.jsx';
import OrganizationSection from './OrganizationSection.jsx';

const INDUSTRIES = ['Technology','Finance','Healthcare','Manufacturing','Retail','Education','Government','Non-profit','Other'];
const COMPANY_SIZES = ['1-10','11-50','51-200','201-500','500+'];

export default function OrganizationProfile({ org }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_profile.update');
  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    company_name: org?.company_name ?? '', legal_name: org?.legal_name ?? '',
    registration_number: org?.registration_number ?? '', industry: org?.industry ?? '',
    company_size: org?.company_size ?? '', website: org?.website ?? '',
    phone: org?.phone ?? '', email: org?.email ?? '',
  });
  function handleSave(e) {
    e.preventDefault();
    post(route('core.organization.profile.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Organization profile updated.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }
  return (
    <form onSubmit={handleSave}>
      <OrganizationSection title="Profile" description="Your company identity and contact details."
        canEdit={canEdit} dirty={isDirty} processing={processing} onReset={() => reset()} onSave={handleSave}>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Company Identity</Text></CardHeader>
          <CardBody><VStack gap={4}>
            <Field label="Company Name" error={errors.company_name} required>
              <Input value={data.company_name} onChange={e => setData('company_name', e.target.value)} placeholder="Enter company name" />
            </Field>
            <Field label="Legal Name" error={errors.legal_name}>
              <Input value={data.legal_name} onChange={e => setData('legal_name', e.target.value)} placeholder="Registered legal name" />
            </Field>
            <Field label="Registration Number" error={errors.registration_number}>
              <Input value={data.registration_number} onChange={e => setData('registration_number', e.target.value)} placeholder="Company registration number" />
            </Field>
            <HStackPlaceholder />
          </VStack></CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Contact Information</Text></CardHeader>
          <CardBody><VStack gap={4}>
            <Field label="Website" error={errors.website}>
              <Input type="url" value={data.website} onChange={e => setData('website', e.target.value)} placeholder="https://example.com" />
            </Field>
          </CardBody></Card>
      </OrganizationSection>
    </form>
  );
}
OrganizationProfile.layout = page => (
  <App title="Organization" railTitle="Organization" rail={<OrganizationRail />}>
    <OrganizationLayout active="profile">{page}</OrganizationLayout>
  </App>
);
```
> NOTE: import `HStack` too; the two-up rows (Industry+Company Size, Phone+Email) use `<HStack gap={4}>` exactly as the original. The `HStackPlaceholder` token above is shorthand — implementer copies the original Industry/Company-Size `HStack` block and the Phone/Email `HStack` block verbatim (fields/Select options unchanged). No field is dropped.

- [ ] **Step 2: Identity.jsx** — keep fields (tax_id password input, vat_number, country, currency), `post(route('core.organization.identity.update'))`, `useHRMAC('core.organization.org_identity.update')`. One `OrganizationSection title="Tax / Legal Identity" description="Tax ID is stored encrypted at rest."` containing the existing single Card (rename CardHeader to `<Text size="sm" tone="secondary">Identity</Text>`). Add `isDirty`; `.layout` active="identity". Keep `tax_id` as `type="password"` and the country/currency `.toUpperCase()` handlers + maxLength.

- [ ] **Step 3: FiscalYear.jsx** — keep fields (fiscal_year_start, fiscal_year_end, timezone, date_format), DATE_FORMAT_OPTIONS, defaults (01-01/12-31/UTC/DD/MM/YYYY), `post(route('core.organization.fiscal-year.update'))`, `useHRMAC('core.organization.fiscal_year.manage')`. Two Cards ("Fiscal Calendar" with the start/end HStack; "Regional" with timezone + date_format) inside one `OrganizationSection title="Fiscal Year"`. Add `isDirty`; `.layout` active="fiscal".

- [ ] **Step 4: Compile-verify** — confirm vite transforms each page without error (dev server returns 200 for the module). Expected: no transform error in the vite terminal.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Organization/{Profile,Identity,FiscalYear}.jsx
git commit -m "Phase3/org: port Profile/Identity/FiscalYear onto OrganizationSection shell"
```

---

### Task 3: Port list sections (Addresses, Contacts)

**Files:**
- Modify: `packages/aero-ui/resources/js/Pages/Core/Organization/Addresses.jsx`
- Modify: `packages/aero-ui/resources/js/Pages/Core/Organization/Contacts.jsx`

**Interfaces:**
- Consumes: Task 1 wrappers. List pattern: `onSave` = Save All (POST); `footerExtra` = "Add row" `Button type="button"`; per-row Remove kept; NO `onReset`.

- [ ] **Step 1: Addresses.jsx** — keep emptyAddress(), ADDRESS_TYPE_OPTIONS, updateAddress/addRow/removeRow, full per-row fields (type, country, line1, line2, city, state, postal_code, is_primary Checkbox with `e.target.checked`), `post(route('core.organization.addresses.update'))`, `useHRMAC('core.organization.org_addresses.manage')`. Wrap rows in `OrganizationSection title="Addresses" description="Billing, shipping, and office addresses." canEdit dirty={isDirty} processing onSave={handleSave} footerExtra={canEdit && <Button type="button" intent="soft" onClick={addRow}>Add address</Button>}`. Add `isDirty`. `.layout` active="addresses". Keep the empty-state Card.

- [ ] **Step 2: Contacts.jsx** — same shape: keep emptyContact(), updateContact/addRow/removeRow, per-row fields (name, role, email, phone, is_primary Checkbox), `post(route('core.organization.contacts.update'))`, `useHRMAC('core.organization.org_contacts.manage')`. `OrganizationSection title="Contacts" description="Primary contacts for this tenant." footerExtra={canEdit && <Button type="button" intent="soft" onClick={addRow}>Add contact</Button>} onSave={handleSave}`. Add `isDirty`; `.layout` active="contacts". Keep empty-state Card.

- [ ] **Step 3: Compile-verify** — vite transforms both pages without error.

- [ ] **Step 4: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Organization/{Addresses,Contacts}.jsx
git commit -m "Phase3/org: port Addresses/Contacts onto OrganizationSection shell (list pattern)"
```

---

### Task 4: Collapse Organization nav 5 → 1

**Files:**
- Modify: `packages/aero-core/config/module.php` (~line 634, `organization` submodule)

- [ ] **Step 1: Add the flag** — add `'collapse_nav' => true,` as a sibling key of `'code' => 'organization'` (alongside name/description/icon/route/priority), before `'components' => [`.

- [ ] **Step 2: Clear config cache + verify served nav** — clear the tenant/app caches the host uses, then read the authenticated navigation prop. Expected: the `organization` nav item has `childCount` 0 (no children array) and exactly one `/organization` link appears in the main nav.

- [ ] **Step 3: Commit**

```bash
git add packages/aero-core/config/module.php
git commit -m "Phase3/org: collapse Organization nav 5->1 (collapse_nav flag)"
```

---

### Task 5: Live verification + code review

- [ ] **Step 1: Ensure vite dev running** (`cd c:/laragon/www/aeos365 && npm run dev`; `public/hot` present).
- [ ] **Step 2: Playwright** — login `democorp.aeos365.test` (admin@democorp.com / Aeos365!Admin); click all 5 sections via the in-page rail. Expected: 0 console errors/warnings each; correct active highlight.
- [ ] **Step 3: Theme Studio** — toggle card-style (flat/glass/gradient-border) + accent. Expected: rail + cards + (active rail link accent) all respond.
- [ ] **Step 4: Single-submit** — Save one section (e.g. Profile). Expected: exactly ONE POST (303 → GET), no double fire.
- [ ] **Step 5: Nav prop** — parse `#app` `data-page` navigation. Expected: Organization childCount 0; exactly one `/organization` link in main nav.
- [ ] **Step 6: Backend regression** — run `OrganizationProfileControllerTest` (endpoints unchanged, must still pass).
- [ ] **Step 7: Code review** — superpowers:requesting-code-review over the branch diff; fix Critical/Important.

## Self-Review
- **Spec coverage:** wrappers (T1), single-form ports (T2), list ports (T3), nav collapse (T4), verification+review (T5) — all spec sections covered.
- **Placeholder scan:** `HStackPlaceholder` in T2/Step1 is explicitly annotated as copy-the-original shorthand (the original two HStack blocks are verbatim-preserved) — not a real placeholder; the other two single-form pages are described field-for-field.
- **Type consistency:** `ORG_GROUPS`, `OrganizationLayout/Rail/Section`, active keys (profile/identity/fiscal/addresses/contacts) consistent across tasks and match the spec table.
