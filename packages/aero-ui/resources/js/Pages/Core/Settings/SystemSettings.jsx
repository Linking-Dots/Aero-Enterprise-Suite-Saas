/**
 * General settings — organization identity. First section of the unified
 * Settings shell (replaces the old navigation-hub).
 */
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Card, CardHeader, CardBody, VStack, Text, useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function SystemSettings({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.general.edit');

  const { data, setData, put, processing, errors, reset, isDirty } = useForm({
    app_name:      settings.app_name      ?? '',
    app_url:       settings.app_url       ?? '',
    support_email: settings.support_email ?? '',
  });

  function handleSave(e) {
    e.preventDefault();
    put(route('core.settings.system.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('General settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <SettingsSection
        title="General"
        description="Your organization's name, URL, and support contact."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Organization</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Application Name" error={errors.app_name}>
                <Input value={data.app_name} onChange={e => setData('app_name', e.target.value)} placeholder="My Company" />
              </Field>
              <Field label="Application URL" error={errors.app_url}>
                <Input value={data.app_url} onChange={e => setData('app_url', e.target.value)} placeholder="https://company.example.com" />
              </Field>
              <Field label="Support Email" error={errors.support_email}>
                <Input type="email" value={data.support_email} onChange={e => setData('support_email', e.target.value)} placeholder="support@company.com" leftIcon="mail" />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

SystemSettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="general">{page}</SettingsLayout>
  </App>
);
