import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Field,
  Textarea,
  Toggle,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Badge,
  Alert,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';
import SettingsSection from '@/components/settings/SettingsSection.jsx';

export default function Maintenance({ maintenance }) {
  const toast     = useToast();
  const canToggle = useHRMAC('system-settings.maintenance-settings.toggle');

  const [enabled,  setEnabled]  = useState(!!maintenance?.enabled);
  const [message,  setMessage]  = useState(maintenance?.message ?? '');
  const [saving,   setSaving]   = useState(false);

  function apply() {
    setSaving(true);
    router.post(
      route('platform.admin.settings.maintenance.toggle'),
      { enable: !enabled, message },
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => {
          setEnabled(prev => !prev);
          toast.success(enabled ? 'Maintenance mode disabled.' : 'Maintenance mode enabled.');
        },
        onError:  () => toast.error('Failed to toggle maintenance mode.'),
        onFinish: () => setSaving(false),
      }
    );
  }

  return (
    <SettingsSection
      title="Maintenance"
      description="Put the platform in maintenance mode to block tenant access during upgrades."
    >

        {enabled && (
          <Alert
            intent="danger"
            title="Maintenance mode is currently ENABLED — tenants cannot access the platform."
          />
        )}

        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3} align="center">
                <Eyebrow>Status</Eyebrow>
                <Badge intent={enabled ? 'danger' : 'success'}>
                  {enabled ? 'ENABLED' : 'DISABLED'}
                </Badge>
              </HStack>

              {maintenance?.enabled_at && (
                <Text tone="secondary">
                  Enabled since: {maintenance.enabled_at}
                </Text>
              )}

              <Toggle
                label="Enable maintenance mode"
                checked={enabled}
                onChange={() => setEnabled(prev => !prev)}
              />

              <Field
                label="Maintenance Message"
                htmlFor="maint_message"
                hint="Displayed to tenants while maintenance mode is active."
              >
                <Textarea
                  id="maint_message"
                  value={message}
                  onChange={e => setMessage(e.target.value)}
                  placeholder="We are performing scheduled maintenance. We will be back shortly."
                  rows={4}
                />
              </Field>

              {canToggle && (
                <HStack gap={3}>
                  <Button
                    type="button"
                    intent={enabled ? 'danger' : 'primary'}
                    onClick={apply}
                    loading={saving}
                    disabled={saving}
                  >
                    {enabled ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode'}
                  </Button>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

    </SettingsSection>
  );
}

Maintenance.layout = page => (
  <App title="Platform Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="maintenance">{page}</SettingsLayout>
  </App>
);
