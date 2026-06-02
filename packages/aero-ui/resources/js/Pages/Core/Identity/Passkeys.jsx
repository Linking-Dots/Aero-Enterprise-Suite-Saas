import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  VStack, HStack,
  Text,
  Toggle,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function PasskeysPage({ config }) {
  const toast   = useToast();
  const canEdit = useHRMAC('auth.sso_identity.passkeys.edit');

  const form = useForm({
    is_enabled:           config?.is_enabled           ?? false,
    allow_as_sole_factor: config?.allow_as_sole_factor ?? false,
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.post(route('core.identity.passkeys.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Passkeys settings saved.'),
      onError:   () => toast.error('Failed to save passkeys settings.'),
    });
  }

  return (
    <FormPageLayout
      title="Passkeys"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Passkeys' },
      ]}
      description="Allow users to sign in with FIDO2 / WebAuthn passkeys instead of passwords."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4}>
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Passkey Login</Text>
                <Text tone="secondary" size="sm">
                  Passkeys use device biometrics or hardware security keys for passwordless authentication.
                </Text>

                <Toggle
                  label="Enable passkey login"
                  checked={form.data.is_enabled}
                  onChange={e => form.setData('is_enabled', e.target.checked)}
                  disabled={!canEdit}
                />
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Authentication Policy</Text>

                <Toggle
                  label="Allow passkey as the only login method"
                  checked={form.data.allow_as_sole_factor}
                  onChange={e => form.setData('allow_as_sole_factor', e.target.checked)}
                  disabled={!canEdit || !form.data.is_enabled}
                />
                <Text tone="secondary" size="sm">
                  When enabled, users who register a passkey may log in without a password or second factor.
                  Disable to require passkeys alongside a password or MFA.
                </Text>

                {form.errors.allow_as_sole_factor && (
                  <Text tone="danger" size="sm">{form.errors.allow_as_sole_factor}</Text>
                )}
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end">
              <Button type="button" intent="ghost" onClick={() => form.reset()} disabled={form.processing}>
                Reset
              </Button>
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

PasskeysPage.layout = page => (
  <App title="Passkeys">{page}</App>
);
