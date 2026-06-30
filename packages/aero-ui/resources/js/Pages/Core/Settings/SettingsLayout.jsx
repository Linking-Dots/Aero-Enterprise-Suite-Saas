/**
 * SettingsLayout — Core (tenant) wrapper over the shared SettingsShell.
 * Supplies the Core section config + header/breadcrumb; all shell behaviour
 * lives in components/settings/SettingsShell.jsx (shared with Platform).
 * Kept at this path so the 9 Core section pages import it unchanged.
 */
import SettingsShell from '@/components/settings/SettingsShell.jsx';
import { SETTINGS_GROUPS } from './settingsSections.js';

export default function SettingsLayout({ active, children }) {
  return (
    <SettingsShell
      active={active}
      groups={SETTINGS_GROUPS}
      title="Settings"
      description="Manage your organization's configuration."
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Settings' },
      ]}
    >
      {children}
    </SettingsShell>
  );
}
