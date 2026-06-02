import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  Field, Input, Button,
  Toggle, Badge,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PROVIDERS = [
  { key: 'google',    label: 'Google',    intent: 'danger'  },
  { key: 'microsoft', label: 'Microsoft', intent: 'primary' },
  { key: 'github',    label: 'GitHub',    intent: 'neutral' },
  { key: 'apple',     label: 'Apple',     intent: 'neutral' },
];

function ProviderCard({ providerKey, label, intent, config }) {
  const toast = useToast();
  const canEdit = useHRMAC('auth.sso_identity.social_login.update');

  const [form, setForm] = useState({
    is_enabled:    config?.is_enabled    ?? false,
    client_id:     config?.client_id     ?? '',
    client_secret: '',
  });
  const [saving, setSaving] = useState(false);

  const setField = (field, value) => setForm(prev => ({ ...prev, [field]: value }));

  const handleSave = () => {
    setSaving(true);
    router.post(
      route('core.identity.social.update', { provider: providerKey }),
      form,
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => toast.success(`${label} settings saved.`),
        onError:   () => toast.error(`Failed to save ${label} settings.`),
        onFinish:  () => setSaving(false),
      }
    );
  };

  return (
    <Card>
      <CardHeader>
        <HStack gap={3} align="center">
          <Badge intent={intent}>{label}</Badge>
          <Text weight="semibold">{label}</Text>
          {config?.is_enabled && (
            <Badge intent="success">Enabled</Badge>
          )}
        </HStack>
      </CardHeader>
      <CardBody>
        <VStack gap={4}>
          <Toggle
            label={`Enable ${label} login`}
            checked={form.is_enabled}
            onChange={e => setField('is_enabled', e.target.checked)}
            disabled={!canEdit}
          />

          {form.is_enabled && (
            <>
              <Field label="Client ID" required>
                <Input
                  value={form.client_id}
                  onChange={e => setField('client_id', e.target.value)}
                  placeholder={`${label} OAuth App client ID`}
                  disabled={!canEdit}
                />
              </Field>

              <Field
                label="Client Secret"
                hint={config?.client_secret ? 'A secret is already saved. Leave blank to keep it.' : undefined}
              >
                <Input
                  type="password"
                  value={form.client_secret}
                  onChange={e => setField('client_secret', e.target.value)}
                  placeholder="Leave blank to keep"
                  disabled={!canEdit}
                />
              </Field>

              {config?.redirect_uri && (
                <Field
                  label="Redirect URI"
                  hint="Register this URI in your OAuth application settings."
                >
                  <Input
                    value={config.redirect_uri}
                    readOnly
                    rightIcon="link"
                  />
                </Field>
              )}
            </>
          )}

          {canEdit && (
            <HStack gap={2} justify="end">
              <Button
                intent="primary"
                loading={saving}
                onClick={handleSave}
              >
                Save {label}
              </Button>
            </HStack>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function SocialLogin({ providers }) {
  return (
    <IndexPageLayout
      title="Social Login"
      breadcrumb={[
        { label: 'SSO & Identity', href: route('core.identity.saml.index') },
        { label: 'Social Login' },
      ]}
      description="Enable social login providers to allow users to sign in with their existing accounts."
    >
      <VStack gap={4}>
        {PROVIDERS.map(({ key, label, intent }) => (
          <ProviderCard
            key={key}
            providerKey={key}
            label={label}
            intent={intent}
            config={providers?.[key]}
          />
        ))}
      </VStack>
    </IndexPageLayout>
  );
}

SocialLogin.layout = page => (
  <App title="Social Login">{page}</App>
);
