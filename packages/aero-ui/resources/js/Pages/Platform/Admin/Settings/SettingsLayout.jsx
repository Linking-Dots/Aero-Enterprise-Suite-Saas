/**
 * Platform (admin) wrapper over the shared SettingsShell — the platform twin of
 * Core/Settings/SettingsLayout.jsx. Supplies the platform section config +
 * header; all shell behaviour lives in components/settings/SettingsShell.jsx
 * (shared with Core).
 */
import SettingsShell from '@/components/settings/SettingsShell.jsx';
import { PLATFORM_SETTINGS_GROUPS } from './settingsSections.js';

export default function SettingsLayout({ active, children }) {
  return (
    <SettingsShell
      active={active}
      groups={PLATFORM_SETTINGS_GROUPS}
      title="Platform Settings"
      description="Configure the platform's identity, communications and system behaviour."
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Settings' },
      ]}
    >
      {children}
    </SettingsShell>
  );
}
