/**
 * Platform (admin) command-shell rail over the shared SettingsNavRail — the
 * platform twin of Core/Settings/SettingsRail.jsx. Supplies the platform section
 * config; rail behaviour is shared.
 */
import SettingsNavRail from '@/components/settings/SettingsNavRail.jsx';
import { PLATFORM_SETTINGS_GROUPS } from './settingsSections.js';

export default function SettingsRail() {
  return <SettingsNavRail groups={PLATFORM_SETTINGS_GROUPS} />;
}
