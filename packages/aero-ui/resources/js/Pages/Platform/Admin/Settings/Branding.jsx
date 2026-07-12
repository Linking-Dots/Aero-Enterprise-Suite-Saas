import { BrandStudio, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';

/**
 * Platform Brand Studio — the platform's OWN brand (what the admin shell,
 * public site and every unbranded tenant inherit). Same shared editor the
 * tenants use; the platform's floor is Meridian.
 */
export default function Branding({ branding }) {
  const canEdit = useHRMAC('system-settings.branding-settings.edit');

  return (
    <BrandStudio
      branding={branding ?? {}}
      updateUrl={route('platform.admin.settings.branding.save')}
      resetUrl={route('platform.admin.settings.branding.reset')}
      canEdit={canEdit}
      scopeLabel="platform"
    />
  );
}

Branding.layout = page => (
  <App title="Platform Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="branding">{page}</SettingsLayout>
  </App>
);
