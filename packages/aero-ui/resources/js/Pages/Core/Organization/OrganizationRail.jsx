/**
 * OrganizationRail — Core (tenant) wrapper over the shared SettingsNavRail.
 * Supplies the Organization section config; rail behaviour lives in
 * components/settings/SettingsNavRail.jsx. Mirrors Core/Settings/SettingsRail.jsx.
 */
import SettingsNavRail from '@/components/settings/SettingsNavRail.jsx';
import { ORG_GROUPS } from './organizationSections.js';

export default function OrganizationRail() {
  return <SettingsNavRail groups={ORG_GROUPS} />;
}
