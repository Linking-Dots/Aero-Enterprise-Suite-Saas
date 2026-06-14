import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  Toggle,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader>
        <Text weight="semibold">{title}</Text>
      </CardHeader>
      <CardBody>
        <VStack gap={4}>
          {children}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function OidcConfig({ config }) {
  const toast = useToast();
  const canEdit = useHRMAC('auth.sso_identity.sso_oidc.update');

  const { data, setData, post, processing, errors } = useForm({
    is_enabled:     config?.is_enabled     ?? false,
    issuer_url:     config?.issuer_url     ?? '',
    client_id:      config?.client_id      ?? '',
    client_secret:  '',
    scopes:         config?.scopes         ?? 'openid profile email',
    auto_provision: config?.auto_provision ?? false,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.identity.oidc.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('OIDC configuration saved.'),
      onError:   () => toast.error('Failed to save OIDC configuration.'),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="OIDC / OAuth"
        breadcrumb={[
          { label: 'SSO & Identity', href: route('core.identity.oidc.index') },
          { label: 'OIDC / OAuth' },
        ]}
        description="Configure OpenID Connect / OAuth 2.0 single sign-on with your identity provider."
        actions={
          canEdit && (
            <Button
              type="submit"
              intent="primary"
              loading={processing}
            >
              Save Changes
            </Button>
          )
        }
      >
        <VStack gap={6}>
          <Section title="General">
            <Toggle
              label="Enable OIDC / OAuth SSO"
              checked={data.is_enabled}
              onChange={e => setData('is_enabled', e.target.checked)}
            />
          </Section>

          <Section title="Provider Settings">
            <Field label="Issuer URL" error={errors.issuer_url} required>
              <Input
                type="url"
                value={data.issuer_url}
                onChange={e => setData('issuer_url', e.target.value)}
                placeholder="https://accounts.example.com"
                leftIcon="link"
              />
            </Field>

            <Field label="Client ID" error={errors.client_id} required>
              <Input
                value={data.client_id}
                onChange={e => setData('client_id', e.target.value)}
                placeholder="your-client-id"
              />
            </Field>

            <Field
              label="Client Secret"
              error={errors.client_secret}
              hint={config?.client_secret ? 'A secret is already saved. Leave blank to keep it.' : undefined}
            >
              <Input
                type="password"
                value={data.client_secret}
                onChange={e => setData('client_secret', e.target.value)}
                placeholder="Leave blank to keep"
              />
            </Field>

            <Field
              label="Scopes"
              error={errors.scopes}
              hint="Space-separated list of OAuth scopes (e.g. openid profile email)."
            >
              <Input
                value={data.scopes}
                onChange={e => setData('scopes', e.target.value)}
                placeholder="openid profile email"
              />
            </Field>
          </Section>

          <Section title="Options">
            <Toggle
              label="Auto-provision new users on first login"
              checked={data.auto_provision}
              onChange={e => setData('auto_provision', e.target.checked)}
            />
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

OidcConfig.layout = page => (
  <App title="OIDC / OAuth">{page}</App>
);
