/**
 * settingsSections — the CORE (tenant) settings-section config consumed by the
 * shared settings shell (components/settings/*). Permission codes are the
 * DECLARED config/module.php actions. The Platform cluster supplies its own
 * equivalent config; the generic shell + useVisibleSettingsGroups are shared.
 *
 * Icon note: the @aero/ui string-name Icon registry (packages/aero-ui/resources/js/icons/icons.jsx)
 * does not include `globe`, `photo`, `shield`, `key`, `lock`, or `puzzle` — only `cog`,
 * `mail`, and `document` from this set are registered. To avoid unknown-icon console.warn
 * for ANY item, every icon here is a heroicons-component reference (same pattern as
 * UsersRail.jsx), not a string name.
 */
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

// Filtering/href-resolution is provided generically by
// components/settings/useSettingsGroups.js (shared with the Platform cluster).
