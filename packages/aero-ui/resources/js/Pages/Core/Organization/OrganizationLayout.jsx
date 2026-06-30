/**
 * OrganizationLayout — Core (tenant) wrapper over the shared SettingsShell.
 * Supplies the Organization section config + header/breadcrumb; all shell
 * behaviour lives in components/settings/SettingsShell.jsx (shared with
 * Settings + Platform). Mirrors Core/Settings/SettingsLayout.jsx.
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
