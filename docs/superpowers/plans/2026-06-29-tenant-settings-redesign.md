# Tenant Settings Cluster Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unify the 9 tenant Core settings pages into one persistent `SettingsLayout` shell (grouped left section-rail + in-place section switching), establishing the canonical "settings standard."

**Architecture:** A persistent Inertia sub-layout (`SettingsLayout`) wraps every section page and renders a HRMAC-filtered grouped section rail + a shared `PageHeader`. Each section page provides its form via a shared `SettingsSection` wrapper (sub-header + cards + sticky dirty/saving/save action bar). Each section keeps its own route/controller/save endpoint/HRMAC gate — only presentation is unified. A `SettingsRail` mirrors `UsersRail`/`RolesRail` for the command-shell right rail.

**Tech Stack:** Laravel 12 + React 18 + Inertia v2, `@aero/ui` design system, HRMAC (`useHRMAC`), Vite (`aeos365.test:5173`).

## Global Constraints

- **`@aero/ui` only** — no other component libs; import from `@aero/ui`.
- **No inline `style={}`** — tokens only (`var(--aeos-*)`); a single centralized `<style>` block (in `SettingsLayout`) is the only stylesheet, using tokens.
- **Registered icon names only** — unknown names log `console.warn`; verification requires **0** console errors **and** warnings.
- **Inertia v2** — `router.*` / `useForm()`; never v1 `Inertia.*`.
- **Theme consistency ([[theme-consistency-all-pages]])** — every surface (section rail, cards, sticky bar, drawers/modals) must respond to `body[data-card-style="X"]`, not only `.aeos-card-auto`; all Theme Studio dimensions must visibly affect every settings surface.
- **HRMAC** — gate save controls with `useHRMAC(...)` using the **declared** action codes (see Task 0 mismatch table); read-only when absent.
- **Scope** — only `Pages/Core/Settings/*` + its `config/module.php` Settings nav block. Do NOT touch `Platform/Admin/Settings/*` or `HRM/Settings/*`.
- **Dual-mode** — no hardcoded central-DB assumptions; works in SaaS + Standalone.
- **No push** — commit each increment locally only.
- **Live verification** — vite running (`cd c:\laragon\www\aeos365 && npm run dev`, writes `public/hot`); drive real clicks at `democorp.aeos365.test/settings/*` (login `admin@democorp.com` / `Aeos365!Admin`); 0 console errors/warnings before each commit.

---

## Task 0 (reference): Pre-existing HRMAC mismatches to fix during the port

These current pages gate `canEdit` on action codes that do **not** match `config/module.php:512–618`. Fix each as its section is ported (use the **Declared** code):

| Section | Current `useHRMAC` (wrong) | Declared action (use this) |
|---|---|---|
| Security | `core.settings.security.update` | `core.settings.security.edit` |
| Localization | `core.settings.localization.edit` | `core.settings.localization.edit` ✓ (ok) |
| Mail | `core.settings.mail.update` | `core.settings.mail_settings.update` |
| Password Policy | `core.settings.password-policy.update` | `core.settings.password_policy.edit` |
| IP Access | `core.settings.ip-whitelist.update` | `core.settings.ip_whitelist.edit` |
| Integrations | `core.settings.integrations.edit` | `core.settings.integrations.configure` |
| Branding | `core.settings.branding.update` | `core.settings.branding.update` ✓ (ok) |
| Email Templates | `...email-templates.{create,edit,delete}` | `...email_templates.{create,edit,delete}` (underscore) |
| General | (none today) | view `core.settings.general.view`, edit `core.settings.general.edit` |

> Note the underscore convention in declared codes (`mail_settings`, `password_policy`, `ip_whitelist`, `email_templates`). The route param names use hyphens; the HRMAC action codes use underscores.

---

## Task 1: Shell infrastructure + General section (foundation increment)

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Settings/settingsSections.js`
- Create: `packages/aero-ui/resources/js/Pages/Core/Settings/SettingsLayout.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Settings/SettingsSection.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Settings/SettingsRail.jsx`
- Modify (full rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/SystemSettings.jsx`

**Interfaces:**
- Produces `settingsSections.js`: `export const SETTINGS_GROUPS` = ordered array of `{ group: string, items: Array<{ key, label, routeName, icon, permission }> }`. `export function useVisibleSettingsGroups()` → same shape filtered by `useHRMAC(permission)`.
- Produces `SettingsLayout.jsx`: default export `SettingsLayout({ active, children })` — renders `PageHeader` (Dashboard › Settings) + grouped rail (from `useVisibleSettingsGroups`, `active` highlights current `key`) + content slot. Also exports the shared `<style>`.
- Produces `SettingsSection.jsx`: default export `SettingsSection({ title, description, canEdit, dirty, processing, onReset, onSave, footerExtra, children })` — section sub-header + `{children}` (the cards) + sticky action bar (`[Reset] [Save changes]`, Save disabled unless `dirty`, `loading={processing}`).
- Produces `SettingsRail.jsx`: default export `SettingsRail()` — command-shell right rail; lists visible sections as `<Link>`s + highlights active by URL.
- Consumed by every section page via `.layout = page => <App ... rail={<SettingsRail/>}><SettingsLayout active="...">{page}</SettingsLayout></App>`.

- [ ] **Step 1: Create `settingsSections.js`**

```js
/**
 * settingsSections — single source of truth for the Settings shell.
 * Both SettingsLayout (in-content rail) and SettingsRail (command-shell rail)
 * read from here. Permission codes are the DECLARED config/module.php actions.
 * Icons must be registered @aero/ui icon names (unknown names log console.warn).
 */
import { useHRMAC } from '@aero/ui';

export const SETTINGS_GROUPS = [
  {
    group: 'General',
    items: [
      { key: 'general',      label: 'General',      routeName: 'core.settings.system',                  icon: 'cog',        permission: 'core.settings.general.view' },
      { key: 'localization', label: 'Localization', routeName: 'core.settings.localization',            icon: 'globe',      permission: 'core.settings.localization.view' },
      { key: 'branding',     label: 'Branding',     routeName: 'core.settings.branding',                icon: 'photo',      permission: 'core.settings.branding.view' },
    ],
  },
  {
    group: 'Security',
    items: [
      { key: 'security',  label: 'Security',        routeName: 'core.settings.security',         icon: 'shield',  permission: 'core.settings.security.view' },
      { key: 'password',  label: 'Password Policy', routeName: 'core.settings.password-policy',  icon: 'key',     permission: 'core.settings.password_policy.view' },
      { key: 'ip',        label: 'IP Access',       routeName: 'core.settings.ip-whitelist',     icon: 'lock',    permission: 'core.settings.ip_whitelist.view' },
    ],
  },
  {
    group: 'Communications',
    items: [
      { key: 'mail',         label: 'Email / SMTP',    routeName: 'core.settings.mail',                 icon: 'mail',   permission: 'core.settings.mail_settings.view' },
      { key: 'templates',    label: 'Email Templates', routeName: 'core.settings.email-templates.index', icon: 'document', permission: 'core.settings.email_templates.view' },
      { key: 'integrations', label: 'Integrations',    routeName: 'core.settings.integrations.index',   icon: 'puzzle', permission: 'core.settings.integrations.view' },
    ],
  },
];

function resolveHref(routeName) {
  try { return route(routeName); } catch { return null; }
}

/** Visible groups with hrefs, filtered by HRMAC view permission + resolvable route. */
export function useVisibleSettingsGroups() {
  // Hooks must run unconditionally: compute a permission map for every item first.
  const flat = SETTINGS_GROUPS.flatMap(g => g.items);
  const allow = {};
  for (const item of flat) {
    // eslint-disable-next-line react-hooks/rules-of-hooks
    allow[item.key] = useHRMAC(item.permission);
  }
  return SETTINGS_GROUPS
    .map(g => ({
      group: g.group,
      items: g.items
        .map(it => ({ ...it, href: resolveHref(it.routeName) }))
        .filter(it => it.href && allow[it.key]),
    }))
    .filter(g => g.items.length > 0);
}
```

> **Icon check before commit:** confirm `cog, globe, photo, shield, key, lock, mail, document, puzzle` are registered `@aero/ui` icon names (grep the icon registry — see Step 8). Swap any unregistered name for a registered equivalent; unknown names log `console.warn` and fail verification.

- [ ] **Step 2: Create `SettingsSection.jsx`**

```jsx
/**
 * SettingsSection — the per-section content standard: a sub-header + the
 * section's cards (children) + a sticky action bar with dirty/saving/save.
 * Used by every form section inside SettingsLayout.
 */
import { Box, HStack, VStack, Heading, Text, Button } from '@aero/ui';

export default function SettingsSection({
  title,
  description,
  canEdit = true,
  dirty = false,
  processing = false,
  onReset,
  onSave,
  footerExtra = null,
  children,
}) {
  return (
    <VStack gap={5} className="settings-section">
      <VStack gap={1}>
        <Heading size="md">{title}</Heading>
        {description && <Text size="sm" tone="secondary">{description}</Text>}
      </VStack>

      <VStack gap={5}>{children}</VStack>

      {canEdit && (onReset || onSave) && (
        <HStack gap={3} align="center" className="settings-actionbar">
          {footerExtra}
          <Box grow />
          {onReset && (
            <Button type="button" intent="soft" onClick={onReset} disabled={processing || !dirty}>
              Reset
            </Button>
          )}
          {onSave && (
            <Button type="submit" intent="primary" loading={processing} disabled={!dirty} onClick={onSave}>
              Save changes
            </Button>
          )}
        </HStack>
      )}
    </VStack>
  );
}
```

- [ ] **Step 3: Create `SettingsLayout.jsx`** (persistent shell + centralized CSS)

```jsx
/**
 * SettingsLayout — persistent shell for the unified tenant Settings cluster.
 * Renders the page header + a grouped, HRMAC-filtered section rail; the active
 * section's content renders in the slot. Because all 9 section pages render
 * <App><SettingsLayout> with the same component types, Inertia keeps the rail
 * mounted across section switches (in-place feel).
 */
import { Link, usePage } from '@inertiajs/react';
import { Box, HStack, VStack, Text, Icon, PageHeader } from '@aero/ui';
import { useVisibleSettingsGroups } from './settingsSections.js';

export default function SettingsLayout({ active, children }) {
  const groups = useVisibleSettingsGroups();

  return (
    <Box className="settings-shell">
      <PageHeader
        title="Settings"
        description="Manage your organization's configuration."
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Settings' },
        ]}
      />

      <HStack gap={6} align="start" className="settings-body">
        <Box className="settings-rail aeos-card-auto">
          <VStack gap={5}>
            {groups.map(g => (
              <VStack gap={2} key={g.group}>
                <Text size="xs" tone="tertiary" mono>{g.group.toUpperCase()}</Text>
                <VStack gap={1}>
                  {g.items.map(it => (
                    <Link
                      key={it.key}
                      href={it.href}
                      className={`settings-rail-link${active === it.key ? ' is-active' : ''}`}
                      aria-current={active === it.key ? 'page' : undefined}
                    >
                      <Icon name={it.icon} className="aeos-icon-sm" />
                      <span>{it.label}</span>
                    </Link>
                  ))}
                </VStack>
              </VStack>
            ))}
          </VStack>
        </Box>

        <Box grow className="settings-content">
          {children}
        </Box>
      </HStack>

      <style>{`
        .settings-body { width: 100%; }
        .settings-rail {
          flex: 0 0 220px;
          width: 220px;
          padding: var(--aeos-space-4, 16px);
          position: sticky;
          top: var(--aeos-space-4, 16px);
        }
        .settings-rail-link {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 8px 10px;
          border-radius: var(--aeos-r-sm);
          color: var(--aeos-text-secondary);
          text-decoration: none;
          font-size: 0.875rem;
          transition: background var(--aeos-motion-fast, 120ms), color var(--aeos-motion-fast, 120ms);
        }
        .settings-rail-link:hover { background: var(--aeos-surface-hover); color: var(--aeos-text); }
        .settings-rail-link.is-active {
          background: var(--aeos-accent-soft, var(--aeos-surface-hover));
          color: var(--aeos-accent, var(--aeos-text));
          font-weight: 600;
        }
        .settings-content { min-width: 0; }
        .settings-actionbar {
          position: sticky;
          bottom: 0;
          padding: var(--aeos-space-3, 12px) 0;
          background: var(--aeos-bg);
          border-top: 1px solid var(--aeos-divider);
        }
        .branding-color-swatch {
          width: 40px; height: 36px; padding: 2px;
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-sm); cursor: pointer; background: none;
        }
        .branding-preview-img { max-width: 160px; max-height: 80px; border-radius: var(--aeos-r-sm); border: 1px solid var(--aeos-divider); }
        .branding-preview-favicon { width: 32px; height: 32px; border-radius: var(--aeos-r-sm); border: 1px solid var(--aeos-divider); }
        .email-template-body { font-family: var(--aeos-font-mono); font-size: 0.8125rem; }
      `}</style>
    </Box>
  );
}
```

> **Verify before relying on it:** `PageHeader` and `Icon` are exported from `@aero/ui` (`PageHeader` is in `components/Navigation.jsx`). Confirm `Icon` export name — grep `export ... Icon` in `packages/aero-ui/resources/js/index.js`. If the export is different (e.g. icons are referenced by `<Stat icon="..."/>` string only), replace the `<Icon name=.../>` with the project's icon-render pattern (e.g. a heroicon import as in `UsersRail`). Falling back to heroicons (`@heroicons/react/24/outline`) is acceptable and avoids the warn entirely.

- [ ] **Step 4: Create `SettingsRail.jsx`** (command-shell right rail, mirrors `UsersRail`)

```jsx
/**
 * SettingsRail — per-page context panel for the command shell's right rail.
 * Mirrors UsersRail/RolesRail. Lists the HRMAC-visible settings sections as a
 * quick "jump to" list and highlights the active one by URL.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, Text } from '@aero/ui';
import { useVisibleSettingsGroups } from './settingsSections.js';

export default function SettingsRail() {
  const { url } = usePage();
  const groups = useVisibleSettingsGroups();
  const current = url ?? (typeof window !== 'undefined' ? window.location.pathname : '');

  return (
    <VStack gap={5} className="dash-rail">
      {groups.map(g => (
        <VStack gap={2} key={g.group}>
          <Text size="xs" tone="tertiary" mono>{g.group.toUpperCase()}</Text>
          <VStack gap={1}>
            {g.items.map(it => {
              const active = current.startsWith(new URL(it.href, 'http://x').pathname);
              return (
                <Link key={it.key} href={it.href} className={`dash-rail-link${active ? ' is-active' : ''}`}>
                  <span>{it.label}</span>
                </Link>
              );
            })}
          </VStack>
        </VStack>
      ))}
    </VStack>
  );
}
```

- [ ] **Step 5: Rewrite `SystemSettings.jsx` as the General section**

Replace the entire file (removes the stale button-tab hub + raw key→value dump). Fields per `SystemSettingController@update`: `app_name`, `app_url`, `support_email` (timezone/date/time live in Localization).

```jsx
/**
 * General settings — organization identity. First section of the unified
 * Settings shell (replaces the old navigation-hub).
 */
import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function SystemSettings({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.general.edit');

  const { data, setData, put, processing, errors, reset, isDirty } = useForm({
    app_name:      settings.app_name      ?? '',
    app_url:       settings.app_url       ?? '',
    support_email: settings.support_email ?? '',
  });

  function handleSave(e) {
    e.preventDefault();
    put(route('core.settings.system.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('General settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <SettingsSection
        title="General"
        description="Your organization's name, URL, and support contact."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Organization</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Application Name" error={errors.app_name}>
                <Input value={data.app_name} onChange={e => setData('app_name', e.target.value)} placeholder="My Company" />
              </Field>
              <Field label="Application URL" error={errors.app_url}>
                <Input value={data.app_url} onChange={e => setData('app_url', e.target.value)} placeholder="https://company.example.com" />
              </Field>
              <Field label="Support Email" error={errors.support_email}>
                <Input type="email" value={data.support_email} onChange={e => setData('support_email', e.target.value)} placeholder="support@company.com" leftIcon="mail" />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

SystemSettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="general">{page}</SettingsLayout>
  </App>
);
```

- [ ] **Step 6: Start vite (if not running) and load General**

Run (background): `cd c:/laragon/www/aeos365 && npm run dev`
Confirm `c:/laragon/www/aeos365/public/hot` exists.
Navigate (Playwright MCP) to `http://democorp.aeos365.test/settings/system`, logging in as `admin@democorp.com` / `Aeos365!Admin` if redirected.
Expected: General form renders inside the shell; left rail shows 3 groups; "General" active.

- [ ] **Step 7: Verify 0 console errors/warnings + theme response**

Run: Playwright `browser_console_messages`.
Expected: no `error`, no `warn` (especially no "unknown icon" warn — if present, fix the icon name in `settingsSections.js`/`SettingsLayout` per Step 3 note).
Open the Theme Studio drawer, switch **card style** (e.g. flat → glass) and **accent**; confirm the rail container + cards + sticky bar all change. (This validates [[theme-consistency-all-pages]] for the shell — once here covers all sections.)

- [ ] **Step 8: Confirm icon names are registered**

Run (Grep): search the icon registry for each used name.
`Grep pattern:"cog|globe|photo|shield|key|lock|mail|document|puzzle" path:packages/aero-ui/resources/js glob:*.jsx` (locate the icon map; e.g. `components/*Icon*` or the `Icon` component's `icons` object).
Expected: each name present. Replace any missing name and re-verify Step 7.

- [ ] **Step 9: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Settings/settingsSections.js \
        packages/aero-ui/resources/js/Pages/Core/Settings/SettingsLayout.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/SettingsSection.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/SettingsRail.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/SystemSettings.jsx
git commit -m "Phase3/settings: unified SettingsLayout shell + General section (settings standard)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 2: Security cluster — Security, Password Policy, IP Access

**Files:**
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/Security.jsx`
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/PasswordPolicy.jsx`
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/IpWhitelist.jsx`

**Interfaces:**
- Consumes `SettingsLayout`, `SettingsSection`, `SettingsRail` from Task 1.
- Each page's `.layout` uses `active="security" | "password" | "ip"` respectively.

**The repeatable port recipe (apply to every remaining section):**
1. Remove `FormPageLayout`/`IndexPageLayout` import + wrapper, and its `title`/`breadcrumb`/`description`/`actions` props (the shell's `PageHeader` + `SettingsSection` header replace them).
2. Wrap the existing `<Card>`s in `<SettingsSection title=… description=… canEdit dirty={isDirty} processing onReset onSave>`.
3. Add `isDirty` to the `useForm` destructure; change the action bar to be driven by `SettingsSection` (delete the old inline `actions` HStack).
4. Fix the `useHRMAC` code per Task 0 table.
5. Change `.layout` to `App rail={<SettingsRail/>}` wrapping `<SettingsLayout active="…">`.
6. Move any inline `<style>` block out (already centralized in `SettingsLayout`); delete the local `<style>`.
7. Keep section-specific actions (test buttons, lists) as extra cards inside `SettingsSection`.

- [ ] **Step 1: Rewrite `Security.jsx`**

Keep its 3 cards (Authentication / Session Policy / Account Lockout) and fields (`require_2fa_admins`, `session_lifetime`, `max_failed_attempts`, `lockout_duration`). Changes: `canEdit = useHRMAC('core.settings.security.edit')`; `const { data, setData, post, processing, errors, reset, isDirty } = useForm({...})`; wrap cards in `SettingsSection`; new `.layout`.

```jsx
import { useForm } from '@inertiajs/react';
import { Field, Input, Toggle, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function SecuritySettings({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.security.edit');
  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    require_2fa_admins:  settings.require_2fa_admins  ?? false,
    session_lifetime:    settings.session_lifetime    ?? 120,
    max_failed_attempts: settings.max_failed_attempts ?? 5,
    lockout_duration:    settings.lockout_duration    ?? 15,
  });
  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.security.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Security settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }
  return (
    <form onSubmit={handleSave}>
      <SettingsSection title="Security" description="Two-factor requirements, session policy, and lockout rules."
        canEdit={canEdit} dirty={isDirty} processing={processing} onReset={() => reset()} onSave={handleSave}>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Authentication</Text></CardHeader>
          <CardBody>
            <Toggle label="Require 2FA for administrators" checked={!!data.require_2fa_admins}
              onChange={e => setData('require_2fa_admins', e.target.checked)} />
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Session Policy</Text></CardHeader>
          <CardBody>
            <Field label="Session Lifetime (minutes)" hint="How long an idle session remains valid." error={errors.session_lifetime}>
              <Input type="number" min={5} max={43200} value={data.session_lifetime}
                onChange={e => setData('session_lifetime', Number(e.target.value))} />
            </Field>
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Account Lockout</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Max Failed Login Attempts" hint="Account locks after this many consecutive failures." error={errors.max_failed_attempts}>
                <Input type="number" min={1} max={20} value={data.max_failed_attempts}
                  onChange={e => setData('max_failed_attempts', Number(e.target.value))} />
              </Field>
              <Field label="Lockout Duration (minutes)" hint="0 = manual unlock required." error={errors.lockout_duration}>
                <Input type="number" min={0} max={1440} value={data.lockout_duration}
                  onChange={e => setData('lockout_duration', Number(e.target.value))} />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

SecuritySettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="security">{page}</SettingsLayout>
  </App>
);
```

- [ ] **Step 2: Rewrite `PasswordPolicy.jsx`**

Same recipe. `canEdit = useHRMAC('core.settings.password_policy.edit')`. Keep 3 cards (Length / Character Requirements / Expiry & History) + fields. Add `isDirty`. Section-specific extra: a "Test policy" affordance is optional — the route `password-policy.test` exists; add a `footerExtra` button only if `PasswordPolicyController@test` returns JSON usable inline. If unsure, OMIT the test button this pass (YAGNI) and note it. `.layout active="password"`.

```jsx
import { useForm } from '@inertiajs/react';
import { Field, Input, Toggle, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function PasswordPolicy({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.password_policy.edit');
  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    min_length: settings.min_length ?? 8,
    require_uppercase: settings.require_uppercase ?? true,
    require_lowercase: settings.require_lowercase ?? true,
    require_numbers:   settings.require_numbers   ?? true,
    require_symbols:   settings.require_symbols   ?? false,
    max_age_days:  settings.max_age_days  ?? 0,
    history_count: settings.history_count ?? 5,
  });
  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.password-policy.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Password policy saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }
  return (
    <form onSubmit={handleSave}>
      <SettingsSection title="Password Policy" description="Complexity, expiry, and reuse rules for all user passwords."
        canEdit={canEdit} dirty={isDirty} processing={processing} onReset={() => reset()} onSave={handleSave}>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Length</Text></CardHeader>
          <CardBody>
            <Field label="Minimum Length" hint="Passwords cannot be shorter than this." error={errors.min_length}>
              <Input type="number" min={6} max={128} value={data.min_length}
                onChange={e => setData('min_length', Number(e.target.value))} />
            </Field>
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Character Requirements</Text></CardHeader>
          <CardBody>
            <VStack gap={3}>
              <Toggle label="Require uppercase letters (A–Z)" checked={!!data.require_uppercase} onChange={e => setData('require_uppercase', e.target.checked)} />
              <Toggle label="Require lowercase letters (a–z)" checked={!!data.require_lowercase} onChange={e => setData('require_lowercase', e.target.checked)} />
              <Toggle label="Require numbers (0–9)"           checked={!!data.require_numbers}   onChange={e => setData('require_numbers', e.target.checked)} />
              <Toggle label="Require symbols (!@#$%^&*)"      checked={!!data.require_symbols}   onChange={e => setData('require_symbols', e.target.checked)} />
            </VStack>
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Expiry & History</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Max Password Age (days)" hint="0 disables expiry." error={errors.max_age_days}>
                <Input type="number" min={0} max={730} value={data.max_age_days} onChange={e => setData('max_age_days', Number(e.target.value))} />
              </Field>
              <Field label="Password History Count" hint="Prevent reuse of the last N passwords. 0 disables." error={errors.history_count}>
                <Input type="number" min={0} max={24} value={data.history_count} onChange={e => setData('history_count', Number(e.target.value))} />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

PasswordPolicy.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="password">{page}</SettingsLayout>
  </App>
);
```

- [ ] **Step 3: Rewrite `IpWhitelist.jsx`**

Same recipe. `canEdit = useHRMAC('core.settings.ip_whitelist.edit')`. Keep 3 cards (Allowed / Blocked / Geo) + fields (`allowed_ips`, `blocked_ips`, `geo_blocking`). Add `isDirty`. `.layout active="ip"`. (The add-ip/remove-ip/test-ip endpoints back a richer UI; this pass preserves the existing textarea model — do NOT expand scope.)

```jsx
import { useForm } from '@inertiajs/react';
import { Field, Textarea, Toggle, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function IpWhitelist({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.ip_whitelist.edit');
  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    allowed_ips:  settings.allowed_ips  ?? '',
    blocked_ips:  settings.blocked_ips  ?? '',
    geo_blocking: settings.geo_blocking ?? false,
  });
  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.ip-whitelist.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('IP access settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }
  return (
    <form onSubmit={handleSave}>
      <SettingsSection title="IP Access Control" description="Allowed and blocked IP ranges (one per line). Empty = allow all."
        canEdit={canEdit} dirty={isDirty} processing={processing} onReset={() => reset()} onSave={handleSave}>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Allowed IPs (Whitelist)</Text></CardHeader>
          <CardBody>
            <Field label="Allowed IPs" hint="One IP/CIDR per line. Only these may access the system." error={errors.allowed_ips}>
              <Textarea rows={6} value={data.allowed_ips} onChange={e => setData('allowed_ips', e.target.value)} placeholder={"192.168.1.0/24\n10.0.0.1"} />
            </Field>
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Blocked IPs (Blocklist)</Text></CardHeader>
          <CardBody>
            <Field label="Blocked IPs" hint="One IP/CIDR per line. Always denied." error={errors.blocked_ips}>
              <Textarea rows={6} value={data.blocked_ips} onChange={e => setData('blocked_ips', e.target.value)} placeholder={"198.51.100.0/24\n192.0.2.1"} />
            </Field>
          </CardBody>
        </Card>
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Geo Blocking</Text></CardHeader>
          <CardBody>
            <VStack gap={3}>
              <Toggle label="Enable geo-based blocking" checked={!!data.geo_blocking} onChange={e => setData('geo_blocking', e.target.checked)} />
              {data.geo_blocking && (
                <Text size="sm" tone="secondary">Requests from blocked regions will be denied. Configure countries in your firewall/CDN.</Text>
              )}
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

IpWhitelist.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="ip">{page}</SettingsLayout>
  </App>
);
```

- [ ] **Step 4: Verify live**

Navigate to `/settings/security`, `/settings/password-policy`, `/settings/ip-whitelist`. For each: rail shows active item; edit a field → Save enables; Save → success toast; `browser_console_messages` shows 0 errors/warnings. Confirm clicking between rail items keeps the rail mounted (no full-page flash).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Settings/Security.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/PasswordPolicy.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/IpWhitelist.jsx
git commit -m "Phase3/settings: port Security/Password/IP sections onto SettingsLayout + fix HRMAC codes

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 3: General cluster rest — Localization, Branding

**Files:**
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/Localization.jsx`
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/Branding.jsx`

**Interfaces:** Consumes Task 1 components. `.layout active="localization" | "branding"`.

- [ ] **Step 1: Rewrite `Localization.jsx`**

Apply the recipe. Keep the option constants (`TIMEZONE_OPTIONS`, `DATE_FORMAT_OPTIONS`, `TIME_FORMAT_OPTIONS`, `LANGUAGE_OPTIONS`, `CURRENCY_OPTIONS`) verbatim and the 2 cards (Regional / Date & Time). `canEdit = useHRMAC('core.settings.localization.edit')` (already correct). Add `isDirty`. Replace wrapper with `SettingsSection title="Localization"`. `.layout active="localization"`. Keep `post(route('core.settings.localization.update'))`.

- [ ] **Step 2: Rewrite `Branding.jsx`**

Apply the recipe. Keep 5 cards (Identity / Brand Color / Sidebar Theme / Logo / Favicon) + fields (`app_name`, `primary_color`, `sidebar_theme`, `logo`, `favicon`) + the native color input with `className="branding-color-swatch"` + `FileInput`s + the `colorPreview` state. `canEdit = useHRMAC('core.settings.branding.update')` (already correct). Save keeps `forceFormData: true` + `encType="multipart/form-data"`. Add `isDirty`. **Delete the local `<style>` block** (its 3 classes now live in `SettingsLayout`). For Reset, keep the color-preview reset: pass `onReset={() => { reset(); setColorPreview(branding.primary_color ?? '#0f172a'); }}`. `.layout active="branding"`.

> Branding dirtiness: file inputs + `useForm.isDirty` — selecting a file sets `logo`/`favicon` (non-null) → `isDirty` true. Changing color via `setData` → dirty. Good.

- [ ] **Step 3: Verify live**

`/settings/localization` and `/settings/branding`: forms render in shell; Localization selects change + save; Branding color picker + logo/favicon upload render; current logo/favicon preview shows if present; 0 console errors/warnings. Toggle card-style in Theme Studio on Branding — confirm cards + swatch border respond.

- [ ] **Step 4: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Settings/Localization.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/Branding.jsx
git commit -m "Phase3/settings: port Localization/Branding sections onto SettingsLayout

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 4: Communications cluster — Mail, Email Templates, Integrations

**Files:**
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/Mail.jsx`
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/EmailTemplates.jsx`
- Modify (rewrite): `packages/aero-ui/resources/js/Pages/Core/Settings/Integrations.jsx`

**Interfaces:** Consumes Task 1 components. `.layout active="mail" | "templates" | "integrations"`.

- [ ] **Step 1: Rewrite `Mail.jsx`**

Apply the recipe. Keep 5 cards (Mail Driver / SMTP Server / Authentication / Sender Identity / Test Connection) + fields + the `axios`-based `handleTest` + `testEmail`/`testLoading`/`testResult` state + the `Alert`. `canEdit = useHRMAC('core.settings.mail_settings.update')` (FIX). Add `isDirty`. The "Test Connection" card stays a normal card inside `SettingsSection` (it has its own button, independent of the Save bar). `.layout active="mail"`. Keep `post(route('core.settings.mail.update'))` and `route('core.settings.mail.test')`.

- [ ] **Step 2: Rewrite `EmailTemplates.jsx`** (managed list inside the shell)

This section is a list+modal, not a single form. Render it inside `SettingsLayout` but WITHOUT `SettingsSection`'s save bar (it has per-row actions + a create button). Structure:
- Section sub-header via a lightweight inline header (`Heading` "Email Templates" + description + a right-aligned `New Template` button gated by `canCreate`).
- Keep the `DataTable` (columns: Name/Slug/Category/Active/actions) inside a `Card`.
- Keep the create/edit `Modal` + form (fields `name, slug, subject, body_html, category, is_active`) and `handleSubmit`/`handleDelete`/`openPreview`.
- Fix HRMAC codes to underscores: `canCreate = useHRMAC('core.settings.email_templates.create')`, `canEdit = useHRMAC('core.settings.email_templates.edit')`, `canDelete = useHRMAC('core.settings.email_templates.delete')`.
- Delete the local `<style>` (`.email-template-body` now in `SettingsLayout`).
- `.layout active="templates"`.

Header block to use in place of `SettingsSection` (since there is no single save):

```jsx
import { Heading, HStack, VStack, Box, Text, Button } from '@aero/ui';
// ...inside render, above the Card:
<VStack gap={5}>
  <HStack align="center">
    <VStack gap={1}>
      <Heading size="md">Email Templates</Heading>
      <Text size="sm" tone="secondary">Manage transactional and system email templates.</Text>
    </VStack>
    <Box grow />
    {canCreate && <Button intent="primary" onClick={openCreate} leftIcon="plus">New Template</Button>}
  </HStack>
  {/* Card with DataTable ... */}
</VStack>
```

- [ ] **Step 3: Rewrite `Integrations.jsx`**

Apply the recipe minus the single save bar (each integration card self-saves via `router.post`). Keep `IntegrationCard`, `INTEGRATIONS_CONFIG`, `handleSave`, `savingKey`. **Fix** `canEdit = useHRMAC('core.settings.integrations.configure')` (was `.edit`). Replace the `IndexPageLayout` wrapper with a lightweight header (`Heading` "Integrations" + description) + the `VStack` of `IntegrationCard`s. `.layout active="integrations"`. Keep `route('core.settings.integrations.update', integrationKey)`.

> Verify the GET route serving this page: `core.settings.integrations.index` currently points at `SystemSettingController@index` (`web.php:646`) which renders `Core/Settings/SystemSettings`, NOT `Core/Settings/Integrations`. Confirm which controller actually renders the Integrations page in the running app (there is a dedicated `IntegrationsController`). If `integrations.index` renders the wrong component, repoint the GET at `IntegrationsController@index` (backend change, aero-core). Capture findings; fix only if broken.

- [ ] **Step 4: Verify live**

`/settings/mail` (save + send test email shows Alert), `/settings/email-templates` (table renders, New Template modal opens/saves, preview opens), `/settings/integrations` (4 cards, toggle reveals fields, per-card save toast). 0 console errors/warnings on each.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Settings/Mail.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/EmailTemplates.jsx \
        packages/aero-ui/resources/js/Pages/Core/Settings/Integrations.jsx
git commit -m "Phase3/settings: port Mail/Email-Templates/Integrations onto SettingsLayout + fix HRMAC codes

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 5: Nav collapse + cleanup + final sweep

**Files:**
- Modify: `packages/aero-core/config/module.php` (Settings group, lines ~504–619)
- Investigate: the nav generator (how `components` become nav links) — likely `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php` or a navigation builder service.
- Possibly modify: `packages/aero-core/routes/web.php` (integrations GET repoint, if Task 4 Step 3 found it broken).

**Interfaces:** No new JS interfaces. Outcome: a single "Settings" nav link → `/settings/system`; the 9 child links no longer render as nav entries (HRMAC `actions` for the 9 components remain — they gate the rail items).

- [ ] **Step 1: Find how the nav renders the Settings children**

Run (Grep): locate the nav/menu builder that reads `config/module.php` `components` and emits nav links.
`Grep pattern:"components" path:packages/aero-core/src glob:*.php` and inspect the navigation service. Determine whether children of a module group are emitted as sub-links (so collapsing means: stop emitting `type:page` settings components as nav links, keep the parent group link).

- [ ] **Step 2: Collapse the nav to one entry**

Based on Step 1, make the Settings group render only the parent link (`/settings/system`). Preferred non-destructive approach: in the nav builder, treat the `settings` module group as a single link (don't expand its `components`), OR add a flag the builder honors. Do NOT delete the `components` array (its `actions` define the HRMAC permissions the rail relies on). Keep change minimal and localized.

- [ ] **Step 3: Verify the old hub no longer appears / redirects**

`/settings/system` now renders the General section (done in Task 1). Confirm no remaining link points to a now-nonexistent hub behavior. The old button-tab hub is gone (file rewritten), so no redirect is needed — confirm nothing else imports/links to a removed export.

- [ ] **Step 4: Full live sweep (all 9 sections)**

With vite running, click through the single "Settings" nav link → lands on General. Then click every rail item (General, Localization, Branding, Security, Password Policy, IP Access, Mail, Email Templates, Integrations). For each: page renders in shell, active highlight correct, `browser_console_messages` = 0 errors/warnings. Switch Theme Studio card-style + accent once and confirm all surfaces respond. Confirm the command-shell `SettingsRail` shows in command-shell mode.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-core/config/module.php packages/aero-core/routes/web.php
git commit -m "Phase3/settings: collapse Settings nav to a single link (unified shell)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 6: Update memory**

Update [[tenant-page-redesign-iteration]]: Settings DONE (unified SettingsLayout standard); queue continues Organization → Audit/Activity → Subscription/Billing. Add a one-line pointer if a new "settings-standard" memory is warranted.

---

## Self-Review (completed by plan author)

- **Spec coverage:** SettingsLayout (T1) ✓; settingsSections single source (T1) ✓; SettingsRail (T1) ✓; per-section standard w/ dirty/saving/saved/validation + toasts (SettingsSection, T1–T4) ✓; loading skeleton — see note below; theme consistency (T1 Step 7, swept T5) ✓; special shapes mail-test/template-CRUD/branding-logo/IP-list/integrations (T2–T4) ✓; nav collapse (T5) ✓; HRMAC mismatch fixes (T0 applied across T1–T4) ✓; scope guard (Global Constraints) ✓.
- **Loading skeleton note:** the spec called for a `router.on('start'/'finish')` skeleton like Users. Because section switching swaps whole pages under the persistent layout (not partial `only:[]` reloads of one dataset), a per-field skeleton adds little; the standard relies on Inertia's page transition. If a visible skeleton is wanted, add it to `SettingsSection` later as an enhancement. Flagged, not silently dropped.
- **Placeholder scan:** no TBD/TODO; every code step shows full code; recipe sections name exact fields/codes. The two "investigate/confirm" steps (icon registry, nav builder, integrations GET) are genuine codebase-discovery steps with exact grep commands + fallback instructions, not placeholders.
- **Type consistency:** `SettingsSection` prop names (`canEdit, dirty, processing, onReset, onSave, footerExtra`) used identically in T1–T4; `useVisibleSettingsGroups()` shape (`{group, items:[{key,label,href,icon}]}`) consumed identically by `SettingsLayout` + `SettingsRail`; `active` keys (`general/localization/branding/security/password/ip/mail/templates/integrations`) match `settingsSections.js` `key`s.
- **Open confirmations (non-blocking, with fallbacks):** `Icon` export name + registered icon names (T1 S3/S8); nav builder behavior (T5 S1); integrations GET controller (T4 S3).
