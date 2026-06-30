/**
 * SettingsRail — Core (tenant) wrapper over the shared SettingsNavRail.
 * Supplies the Core section config; rail behaviour lives in
 * components/settings/SettingsNavRail.jsx (shared with Platform).
 * Kept at this path so the 9 Core section pages import it unchanged.
 */
import SettingsNavRail from '@/components/settings/SettingsNavRail.jsx';
import { SETTINGS_GROUPS } from './settingsSections.js';

export default function SettingsRail() {
  return <SettingsNavRail groups={SETTINGS_GROUPS} />;
}
