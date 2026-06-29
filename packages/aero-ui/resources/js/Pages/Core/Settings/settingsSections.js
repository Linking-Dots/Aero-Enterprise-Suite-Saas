/**
 * settingsSections — single source of truth for the Settings shell.
 * Both SettingsLayout (in-content rail) and SettingsRail (command-shell rail)
 * read from here. Permission codes are the DECLARED config/module.php actions.
 *
 * Icon note: the @aero/ui string-name Icon registry (packages/aero-ui/resources/js/icons/icons.jsx)
 * does not include `globe`, `photo`, `shield`, `key`, `lock`, or `puzzle` — only `cog`,
 * `mail`, and `document` from this set are registered. To avoid unknown-icon console.warn
 * for ANY item, every icon here is a heroicons-component reference (same pattern as
 * UsersRail.jsx), not a string name.
 */
import { useHRMAC } from '@aero/ui';
import {
  Cog8ToothIcon,
  GlobeAltIcon,
  PhotoIcon,
  ShieldCheckIcon,
  KeyIcon,
  LockClosedIcon,
  EnvelopeIcon,
  DocumentTextIcon,
  PuzzlePieceIcon,
} from '@heroicons/react/24/outline';

export const SETTINGS_GROUPS = [
  {
    group: 'General',
    items: [
      { key: 'general',      label: 'General',      routeName: 'core.settings.system',                  icon: Cog8ToothIcon,    permission: 'core.settings.general.view' },
      { key: 'localization', label: 'Localization', routeName: 'core.settings.localization',            icon: GlobeAltIcon,     permission: 'core.settings.localization.view' },
      { key: 'branding',     label: 'Branding',     routeName: 'core.settings.branding',                icon: PhotoIcon,        permission: 'core.settings.branding.view' },
    ],
  },
  {
    group: 'Security',
    items: [
      { key: 'security',  label: 'Security',        routeName: 'core.settings.security',         icon: ShieldCheckIcon,  permission: 'core.settings.security.view' },
      { key: 'password',  label: 'Password Policy', routeName: 'core.settings.password-policy',  icon: KeyIcon,          permission: 'core.settings.password_policy.view' },
      { key: 'ip',        label: 'IP Access',       routeName: 'core.settings.ip-whitelist',     icon: LockClosedIcon,   permission: 'core.settings.ip_whitelist.view' },
    ],
  },
  {
    group: 'Communications',
    items: [
      { key: 'mail',         label: 'Email / SMTP',    routeName: 'core.settings.mail',                 icon: EnvelopeIcon,      permission: 'core.settings.mail_settings.view' },
      { key: 'templates',    label: 'Email Templates', routeName: 'core.settings.email-templates.index', icon: DocumentTextIcon, permission: 'core.settings.email_templates.view' },
      { key: 'integrations', label: 'Integrations',    routeName: 'core.settings.integrations.index',   icon: PuzzlePieceIcon,  permission: 'core.settings.integrations.view' },
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
