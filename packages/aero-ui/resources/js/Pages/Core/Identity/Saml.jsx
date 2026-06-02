import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
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

export default function SamlConfig({ config, metadata_url }) {
  const toast = useToast();
  const canEdit = useHRMAC('auth.sso_identity.sso_saml.update');
  const canTest = useHRMAC('auth.sso_identity.sso_saml.test');

  const [testing, setTesting] = useState(false);

  const { data, setData, post, processing, errors } = useForm({
    is_enabled:     config?.is_enabled     ?? false,
    entity_id:      config?.entity_id      ?? '',
    sso_url:        config?.sso_url        ?? '',
    slo_url:        config?.slo_url        ?? '',
    certificate:    config?.certificate    ?? '',
    sign_requests:  config?.sign_requests  ?? false,
    auto_provision: config?.auto_provision ?? false,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.identity.saml.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('SAML configuration saved.'),
      onError:   () => toast.error('Failed to save SAML configuration.'),
    });
  };

  const handleTest = () => {
    setTesting(true);
    router.post(route('core.identity.saml.test'), {}, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('SAML connection test passed.'),
      onError:   () => toast.error('SAML connection test failed.'),
      onFinish:  () => setTesting(false),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="SAML 2.0"
        breadcrumb={[
          { label: 'SSO & Identity', href: route('core.identity.saml.index') },
          { label: 'SAML 2.0' },
        ]}
        description="Configure SAML 2.0 single sign-on with your identity provider."
        actions={
          <HStack gap={3}>
            {canTest && (
              <Button
                type="button"
                intent="soft"
                loading={testing}
                onClick={handleTest}
              >
                Test Connection
              </Button>
            )}
            {canEdit && (
              <Button
                type="submit"
                intent="primary"
                loading={processing}
              >
                Save Changes
              </Button>
            )}
          </HStack>
        }
      >
        <VStack gap={6}>
          <Section title="Service Provider Metadata">
            <Field label="SP Metadata URL" hint="Share this URL with your identity provider.">
              <Input
                value={metadata_url ?? ''}
                readOnly
                rightIcon="link"
              />
            </Field>
          </Section>

          <Section title="General">
            <Toggle
              label="Enable SAML 2.0 SSO"
              checked={data.is_enabled}
              onChange={e => setData('is_enabled', e.target.checked)}
            />
          </Section>

          <Section title="Identity Provider Settings">
            <Field label="Entity ID (IdP Issuer)" error={errors.entity_id} required>
              <Input
                value={data.entity_id}
                onChange={e => setData('entity_id', e.target.value)}
                placeholder="https://idp.example.com/metadata"
              />
            </Field>

            <Field label="SSO URL (IdP Sign-In Endpoint)" error={errors.sso_url} required>
              <Input
                type="url"
                value={data.sso_url}
                onChange={e => setData('sso_url', e.target.value)}
                placeholder="https://idp.example.com/sso"
              />
            </Field>

            <Field label="SLO URL (IdP Sign-Out Endpoint)" error={errors.slo_url}>
              <Input
                type="url"
                value={data.slo_url}
                onChange={e => setData('slo_url', e.target.value)}
                placeholder="https://idp.example.com/slo"
              />
            </Field>

            <Field label="X.509 Certificate" error={errors.certificate} required>
              <textarea
                className="aeos-input"
                value={data.certificate}
                onChange={e => setData('certificate', e.target.value)}
                placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
                rows={6}
              />
            </Field>
          </Section>

          <Section title="Options">
            <Toggle
              label="Sign authentication requests"
              checked={data.sign_requests}
              onChange={e => setData('sign_requests', e.target.checked)}
            />
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

SamlConfig.layout = page => (
  <App title="SAML 2.0">{page}</App>
);
