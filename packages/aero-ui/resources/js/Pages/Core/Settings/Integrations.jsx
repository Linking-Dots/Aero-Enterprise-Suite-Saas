/**
 * Settings — Integrations
 * 4 integration cards: Slack, Google Workspace, Microsoft 365, Zapier.
 * Each card saves independently via router.post(). Ported onto the unified
 * SettingsLayout shell (Task 4) — no single SettingsSection save bar; each
 * card is self-saving.
 *
 * HRMAC: core.settings.integrations.edit → core.settings.integrations.configure (Task 0 fix)
 */
import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Heading,
  Card, CardHeader, CardBody,
  Field, Input, Toggle,
  Button,
  HStack, VStack,
  Text, Eyebrow,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';

function IntegrationCard({ title, description, icon, integrationKey, fields, initialData, onSave, saving }) {
  const [form, setForm] = useState(initialData ?? {});
  const [saved, setSaved] = useState(false);

  const handleChange = (key, value) => {
    setSaved(false);
    setForm(prev => ({ ...prev, [key]: value }));
  };

  const handleSave = () => {
    onSave(integrationKey, form, () => setSaved(true));
  };

  return (
    <Card>
      <CardHeader>
        <HStack gap={3} align="center">
          <VStack gap={1}>
            <Eyebrow>{title}</Eyebrow>
            {description && <Text tone="secondary" size="sm">{description}</Text>}
          </VStack>
        </HStack>
      </CardHeader>
      <CardBody>
        <VStack gap={4}>
          <Toggle
            label="Enable integration"
            checked={Boolean(form.enabled)}
            onChange={e => handleChange('enabled', e.target.checked)}
          />

          {form.enabled && fields.map(field => (
            <Field key={field.key} label={field.label} htmlFor={`${integrationKey}_${field.key}`} hint={field.hint}>
              <Input
                id={`${integrationKey}_${field.key}`}
                type={field.type ?? 'text'}
                value={form[field.key] ?? ''}
                onChange={e => handleChange(field.key, e.target.value)}
                placeholder={field.placeholder ?? ''}
              />
            </Field>
          ))}

          {saved && (
            <Alert intent="success" title="Settings saved successfully." />
          )}

          <HStack gap={3} justify="end">
            <Button intent="primary" loading={saving} onClick={handleSave}>
              Save
            </Button>
          </HStack>
        </VStack>
      </CardBody>
    </Card>
  );
}

const INTEGRATIONS_CONFIG = [
  {
    key:         'slack',
    title:       'Slack',
    description: 'Send notifications and alerts to Slack channels.',
    fields: [
      { key: 'webhook_url', label: 'Webhook URL',    placeholder: 'https://hooks.slack.com/services/…', hint: 'Incoming webhook URL from your Slack app configuration' },
      { key: 'channel',     label: 'Default Channel', placeholder: '#general', hint: 'Channel to send messages to (e.g. #hr-notifications)' },
    ],
  },
  {
    key:         'google_workspace',
    title:       'Google Workspace',
    description: 'Enable Google Workspace SSO and directory sync.',
    fields: [
      { key: 'client_id', label: 'Client ID',  placeholder: '…apps.googleusercontent.com', hint: 'OAuth 2.0 Client ID from Google Cloud Console' },
      { key: 'domain',    label: 'Domain',     placeholder: 'yourcompany.com',              hint: 'Your Google Workspace domain' },
    ],
  },
  {
    key:         'microsoft_365',
    title:       'Microsoft 365',
    description: 'Enable Microsoft 365 SSO and Azure AD integration.',
    fields: [
      { key: 'tenant_id', label: 'Tenant ID',  placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', hint: 'Azure Active Directory Tenant ID' },
      { key: 'client_id', label: 'Client ID',  placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', hint: 'Azure App Registration Client ID' },
    ],
  },
  {
    key:         'zapier',
    title:       'Zapier',
    description: 'Connect AEOS to thousands of apps via Zapier automations.',
    fields: [
      { key: 'api_key', label: 'API Key', placeholder: 'zap_…', hint: 'Your Zapier API key — keep this secret' },
    ],
  },
];

export default function Integrations({ integrations = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.integrations.configure');
  const [savingKey, setSavingKey] = useState(null);

  const handleSave = (integrationKey, formData, onDone) => {
    if (!canEdit) return;
    setSavingKey(integrationKey);
    router.post(
      route('core.settings.integrations.update', integrationKey),
      formData,
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`${integrationKey.replace(/_/g, ' ')} settings saved.`);
          onDone();
        },
        onError:  () => toast.error('Failed to save integration settings.'),
        onFinish: () => setSavingKey(null),
      },
    );
  };

  return (
    <VStack gap={5}>
      <VStack gap={1}>
        <Heading level={3}>Integrations</Heading>
        <Text size="sm" tone="secondary">Configure third-party integrations for Slack, Google Workspace, Microsoft 365, and Zapier.</Text>
      </VStack>

      <VStack gap={5}>
        {INTEGRATIONS_CONFIG.map(cfg => (
          <IntegrationCard
            key={cfg.key}
            title={cfg.title}
            description={cfg.description}
            integrationKey={cfg.key}
            fields={cfg.fields}
            initialData={integrations[cfg.key] ?? {}}
            onSave={handleSave}
            saving={savingKey === cfg.key}
          />
        ))}
      </VStack>
    </VStack>
  );
}

Integrations.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="integrations">{page}</SettingsLayout>
  </App>
);
