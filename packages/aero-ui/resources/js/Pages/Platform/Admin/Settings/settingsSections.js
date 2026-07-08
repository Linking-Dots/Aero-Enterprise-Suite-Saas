/**
 * Platform (admin) settings-section config — the PLATFORM equivalent of
 * Core/Settings/settingsSections.js, consumed by the SAME shared settings shell
 * (components/settings/*). This is what makes the generalized shell serve both
 * contexts: Core supplies its groups, Platform supplies these.
 *
 * Icons are heroicon component references (not string names) to avoid the
 * unknown-icon console.warn from the @aero/ui string registry — same rule as Core.
 * Permission codes are the DECLARED config/module.php actions (system-settings.*).
 */
import {
  Cog8ToothIcon,
  PhotoIcon,
  GlobeAltIcon,
  EnvelopeIcon,
  ServerStackIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/react/24/outline';

export const PLATFORM_SETTINGS_GROUPS = [
  {
    group: 'General',
    items: [
      { key: 'general',      label: 'General',      routeName: 'platform.admin.settings.general',      icon: Cog8ToothIcon, permission: 'system-settings.general-settings.view' },
      { key: 'branding',     label: 'Branding',     routeName: 'platform.admin.settings.branding',     icon: PhotoIcon,     permission: 'system-settings.branding-settings.view' },
      { key: 'localization', label: 'Localization', routeName: 'platform.admin.settings.localization', icon: GlobeAltIcon,  permission: 'system-settings.localization-settings.view' },
    ],
  },
  {
    group: 'Communications',
    items: [
      { key: 'email', label: 'Email / SMTP', routeName: 'platform.admin.settings.email', icon: EnvelopeIcon, permission: 'system-settings.email-settings.view' },
    ],
  },
  {
    group: 'System',
    items: [
      { key: 'infrastructure', label: 'Infrastructure', routeName: 'platform.admin.settings.infrastructure', icon: ServerStackIcon,        permission: 'system-settings.infrastructure-settings.view' },
      { key: 'maintenance',    label: 'Maintenance',    routeName: 'platform.admin.settings.maintenance',    icon: WrenchScrewdriverIcon,  permission: 'system-settings.maintenance-settings.view' },
    ],
  },
];
