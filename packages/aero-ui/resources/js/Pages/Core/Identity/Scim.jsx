import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  HStack, VStack,
  Text, Mono,
  Field,
  Toggle,
  Button,
  Badge,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ScimPage({ is_enabled, scim_url, has_token }) {
  const toast = useToast();
  const { props } = usePage();
  const flash = props.flash ?? {};

  const canEdit   = useHRMAC('auth.sso_identity.scim_provisioning.edit');
  const canRotate = useHRMAC('auth.sso_identity.scim_provisioning.rotate_token');

  const [copied, setCopied] = useState(false);
  const [tokenCopied, setTokenCopied] = useState(false);

  const form = useForm({ is_enabled: is_enabled ?? false });

  function handleSave(e) {
    e.preventDefault();
    form.post(route('core.identity.scim.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('SCIM settings saved.'),
      onError:   () => toast.error('Failed to save SCIM settings.'),
    });
  }

  function handleRotate() {
    if (!confirm('Rotate the SCIM bearer token? Any existing integrations using the current token will break.')) return;
    router.post(route('core.identity.scim.rotate-token'), {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Token rotated successfully.'),
      onError:   () => toast.error('Failed to rotate token.'),
    });
  }

  function copyUrl() {
    navigator.clipboard.writeText(scim_url ?? '').then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  }

  function copyToken() {
    navigator.clipboard.writeText(flash.scim_token ?? '').then(() => {
      setTokenCopied(true);
      setTimeout(() => setTokenCopied(false), 2000);
    });
  }

  return (
    <FormPageLayout
      title="SCIM Provisioning"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'SCIM' },
      ]}
      description="Automate user provisioning and deprovisioning via SCIM 2.0."
    >
      {flash.scim_token && (
        <Alert
          intent="warning"
          title="New SCIM Bearer Token — Copy Now"
        >
          <VStack gap={2}>
            <Text tone="secondary">
              This token will not be shown again. Copy it immediately and store it securely.
            </Text>
            <HStack gap={2} align="center">
              <Mono>{flash.scim_token}</Mono>
              <Button intent="soft" size="sm" onClick={copyToken}>
                {tokenCopied ? 'Copied!' : 'Copy Token'}
              </Button>
            </HStack>
          </VStack>
        </Alert>
      )}

      <form onSubmit={handleSave}>
        <VStack gap={4}>
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">SCIM Endpoint</Text>
                <Field label="Endpoint URL" htmlFor="scim_url" hint="Use this URL in your identity provider's SCIM configuration.">
                  <HStack gap={2} align="center">
                    <Mono>{scim_url ?? '—'}</Mono>
                    <Button intent="ghost" size="sm" type="button" onClick={copyUrl}>
                      {copied ? 'Copied!' : 'Copy'}
                    </Button>
                  </HStack>
                </Field>

                <Toggle
                  label="Enable SCIM provisioning"
                  checked={form.data.is_enabled}
                  onChange={e => form.setData('is_enabled', e.target.checked)}
                  disabled={!canEdit}
                />
                {form.errors.is_enabled && (
                  <Text tone="danger" size="sm">{form.errors.is_enabled}</Text>
                )}
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Bearer Token</Text>
                <HStack gap={3} align="center">
                  <Text tone="secondary">Status:</Text>
                  {has_token
                    ? <Badge intent="success">Configured</Badge>
                    : <Badge intent="neutral">Not set</Badge>
                  }
                </HStack>
                <Text tone="secondary" size="sm">
                  The bearer token is used to authenticate requests from your identity provider.
                  Rotating the token immediately invalidates the current one.
                </Text>
                {canRotate && (
                  <Button intent="danger" type="button" onClick={handleRotate}>
                    Rotate Token
                  </Button>
                )}
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end">
              <Button type="submit" intent="primary" loading={form.processing}>
                Save Changes
              </Button>
            </HStack>
          )}
        </VStack>
      </form>
    </FormPageLayout>
  );
}

ScimPage.layout = page => (
  <App title="SCIM Provisioning">{page}</App>
);
