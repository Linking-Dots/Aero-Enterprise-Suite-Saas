import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  VStack, HStack,
  Text,
  Field,
  Input,
  Textarea,
  Toggle,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function MagicLinkPage({ config }) {
  const toast    = useToast();
  const canEdit  = useHRMAC('auth.sso_identity.magic_link.edit');

  const form = useForm({
    is_enabled:      config?.is_enabled      ?? false,
    expiry_minutes:  config?.expiry_minutes  ?? 15,
    allowed_domains: config?.allowed_domains ?? '',
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.post(route('core.identity.magic-link.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Magic Link settings saved.'),
      onError:   () => toast.error('Failed to save Magic Link settings.'),
    });
  }

  return (
    <FormPageLayout
      title="Magic Link"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Magic Link' },
      ]}
      description="Allow users to sign in with a one-time link sent to their email."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4}>
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">General</Text>

                <Toggle
                  label="Enable Magic Link login"
                  checked={form.data.is_enabled}
                  onChange={e => form.setData('is_enabled', e.target.checked)}
                  disabled={!canEdit}
                />

                <Field
                  label="Link expiry (minutes)"
                  htmlFor="expiry_minutes"
                  hint="How long a magic link remains valid. Between 5 and 1440 minutes."
                  error={form.errors.expiry_minutes}
                  required
                >
                  <Input
                    id="expiry_minutes"
                    type="number"
                    value={form.data.expiry_minutes}
                    onChange={e => form.setData('expiry_minutes', parseInt(e.target.value, 10))}
                    error={form.errors.expiry_minutes}
                    min={5}
                    max={1440}
                    disabled={!canEdit}
                  />
                </Field>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Allowed Domains</Text>
                <Text tone="secondary" size="sm">
                  Restrict magic link sign-in to specific email domains. Enter one domain per line or comma-separated.
                  Leave blank to allow all verified email addresses.
                </Text>
                <Field
                  label="Allowed domains"
                  htmlFor="allowed_domains"
                  hint="Example: acme.com, partner.org"
                  error={form.errors.allowed_domains}
                >
                  <Textarea
                    id="allowed_domains"
                    value={form.data.allowed_domains}
                    onChange={e => form.setData('allowed_domains', e.target.value)}
                    rows={4}
                    placeholder="acme.com&#10;partner.org"
                    disabled={!canEdit}
                  />
                </Field>
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

MagicLinkPage.layout = page => (
  <App title="Magic Link">{page}</App>
);
