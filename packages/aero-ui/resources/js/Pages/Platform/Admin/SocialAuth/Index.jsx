import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Toggle,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PROVIDER_META = {
  google:   { label: 'Google',   description: 'Sign in with a Google account via OAuth 2.0.' },
  github:   { label: 'GitHub',   description: 'Sign in with a GitHub account.' },
  facebook: { label: 'Facebook', description: 'Sign in with a Facebook / Meta account.' },
  linkedin: { label: 'LinkedIn', description: 'Sign in with a LinkedIn account.' },
};

function providerLabel(code) {
  return PROVIDER_META[code]?.label ?? code;
}

function providerDescription(code) {
  return PROVIDER_META[code]?.description ?? '';
}

export default function SocialAuthIndex({ providers }) {
  const toast       = useToast();
  const canConfigure = useHRMAC('social-authentication.social-providers.configure');

  const [configTarget, setConfigTarget] = useState(null);
  const [configOpen,   setConfigOpen]   = useState(false);
  const [submitting,   setSubmitting]   = useState(false);

  const [configForm, setConfigForm] = useState({
    client_id:     '',
    client_secret: '',
    scopes:        '',
    callback_url:  '',
  });

  function openConfig(provider) {
    setConfigTarget(provider);
    setConfigForm({
      client_id:     provider.client_id     ?? '',
      client_secret: provider.client_secret ?? '',
      scopes:        Array.isArray(provider.scopes)
        ? provider.scopes.join(', ')
        : (provider.scopes ?? ''),
      callback_url:  provider.callback_url  ?? '',
    });
    setConfigOpen(true);
  }

  function doSaveConfig() {
    setSubmitting(true);
    router.put(
      route('platform.admin.social-auth.configure', configTarget.code),
      {
        client_id:     configForm.client_id,
        client_secret: configForm.client_secret,
        scopes:        configForm.scopes.split(',').map(s => s.trim()).filter(Boolean),
        callback_url:  configForm.callback_url,
      },
      {
        onSuccess: () => { setConfigOpen(false); toast.success(`${providerLabel(configTarget.code)} configured.`); },
        onError:   () => toast.error('Failed to save provider configuration.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doToggle(provider) {
    router.post(
      route('platform.admin.social-auth.toggle', provider.code),
      {},
      {
        onSuccess: () =>
          toast.success(`${providerLabel(provider.code)} ${provider.is_active ? 'disabled' : 'enabled'}.`),
        onError: () => toast.error('Failed to toggle provider.'),
        preserveState: true,
      }
    );
  }

  return (
    <IndexPageLayout
      title="Social Auth Providers"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Social Auth' },
      ]}
      description="Configure OAuth providers for social login. Set client credentials and manage linked accounts."
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('platform.admin.social-auth.accounts.index'))}
        >
          Linked Accounts
        </Button>
      }
    >
      <VStack gap={4}>

        {(providers ?? []).length === 0 && (
          <Text tone="secondary">No providers configured.</Text>
        )}

        {(providers ?? []).map(provider => (
          <Card key={provider.code}>
            <CardBody>
              <VStack gap={4}>
                <HStack gap={3}>
                  <VStack gap={0}>
                    <Eyebrow>{providerLabel(provider.code)}</Eyebrow>
                    <Text tone="secondary">{providerDescription(provider.code)}</Text>
                  </VStack>
                  <Badge intent={provider.is_active ? 'success' : 'neutral'}>
                    {provider.is_active ? 'Enabled' : 'Disabled'}
                  </Badge>
                </HStack>

                <HStack gap={4} wrap>
                  {provider.client_id ? (
                    <VStack gap={0}>
                      <Text tone="secondary">Client ID</Text>
                      <Mono tone="secondary">
                        {provider.client_id.length > 20
                          ? provider.client_id.slice(0, 20) + '…'
                          : provider.client_id}
                      </Mono>
                    </VStack>
                  ) : (
                    <Text tone="secondary">No credentials configured.</Text>
                  )}
                  {provider.callback_url && (
                    <VStack gap={0}>
                      <Text tone="secondary">Callback URL</Text>
                      <Mono tone="secondary">{provider.callback_url}</Mono>
                    </VStack>
                  )}
                  {provider.linked_accounts_count != null && (
                    <VStack gap={0}>
                      <Text tone="secondary">Linked Accounts</Text>
                      <Text>{provider.linked_accounts_count}</Text>
                    </VStack>
                  )}
                </HStack>

                {canConfigure && (
                  <HStack gap={2}>
                    <Toggle
                      label={`Enable ${providerLabel(provider.code)}`}
                      checked={!!provider.is_active}
                      onChange={() => doToggle(provider)}
                    />
                    <Button intent="soft" leftIcon="cog" onClick={() => openConfig(provider)}>
                      Configure
                    </Button>
                  </HStack>
                )}
              </VStack>
            </CardBody>
          </Card>
        ))}

      </VStack>

      {/* Configure Provider Modal */}
      <Modal
        open={configOpen}
        onClose={() => setConfigOpen(false)}
        title={`Configure ${configTarget ? providerLabel(configTarget.code) : ''}`}
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setConfigOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doSaveConfig}>
              Save Configuration
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field
            label="Client ID"
            htmlFor="cfg_client_id"
            hint="OAuth application Client ID from the provider dashboard."
            required
          >
            <Input
              id="cfg_client_id"
              value={configForm.client_id}
              onChange={e => setConfigForm({ ...configForm, client_id: e.target.value })}
              placeholder="your-client-id"
            />
          </Field>

          <Field
            label="Client Secret"
            htmlFor="cfg_client_secret"
            hint="Stored encrypted. Leave blank to keep the existing secret."
          >
            <Input
              id="cfg_client_secret"
              type="password"
              value={configForm.client_secret}
              onChange={e => setConfigForm({ ...configForm, client_secret: e.target.value })}
              placeholder="••••••••"
            />
          </Field>

          <Field
            label="Scopes"
            htmlFor="cfg_scopes"
            hint="Comma-separated OAuth scopes (e.g. email, profile, openid)."
          >
            <Input
              id="cfg_scopes"
              value={configForm.scopes}
              onChange={e => setConfigForm({ ...configForm, scopes: e.target.value })}
              placeholder="email, profile"
            />
          </Field>

          <Field
            label="Callback URL"
            htmlFor="cfg_callback_url"
            hint="Register this URL in the provider's OAuth app settings."
          >
            <Input
              id="cfg_callback_url"
              type="url"
              leftIcon="link"
              value={configForm.callback_url}
              onChange={e => setConfigForm({ ...configForm, callback_url: e.target.value })}
              placeholder="https://aeos365.com/auth/callback/google"
            />
          </Field>
        </VStack>
      </Modal>

    </IndexPageLayout>
  );
}

SocialAuthIndex.layout = page => <App title="Social Auth Providers">{page}</App>;
