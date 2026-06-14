import { useState } from 'react';
import { router } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  VStack, HStack,
  Text,
  Eyebrow,
  Field,
  Input,
  Toggle,
  Button,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function AccountRecoveryPage({ config = {} }) {
  const toast   = useToast();
  const { errors } = usePage().props;
  const canEdit = useHRMAC('auth.sso_identity.account_recovery.edit');

  const [form, setForm] = useState({
    recovery_codes_enabled:        config.recovery_codes_enabled        ?? true,
    recovery_codes_count:          config.recovery_codes_count          ?? 10,
    backup_email_enabled:          config.backup_email_enabled          ?? true,
    security_questions_enabled:    config.security_questions_enabled    ?? false,
    admin_override_enabled:        config.admin_override_enabled        ?? true,
    recovery_rate_limit_per_hour:  config.recovery_rate_limit_per_hour  ?? 5,
  });

  const [saving, setSaving] = useState(false);

  const setField = (field, value) => setForm(prev => ({ ...prev, [field]: value }));

  function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    router.post(route('core.identity.account-recovery.update'), form, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('Account recovery settings saved.'),
      onError:   () => toast.error('Failed to save account recovery settings.'),
      onFinish:  () => setSaving(false),
    });
  }

  return (
    <FormPageLayout
      title="Account Recovery"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Account Recovery' },
      ]}
      description="Configure how users can recover access to their accounts."
    >
      <form onSubmit={handleSave}>
        <VStack gap={4}>
          {/* Recovery Codes */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Recovery Codes</Eyebrow>

                <Toggle
                  label="Enable recovery codes"
                  checked={form.recovery_codes_enabled}
                  onChange={e => setField('recovery_codes_enabled', e.target.checked)}
                  disabled={!canEdit}
                />

                {form.recovery_codes_enabled && (
                  <Field
                    label="Number of recovery codes to generate"
                    htmlFor="recovery_codes_count"
                    hint="How many one-time recovery codes are issued to each user."
                    error={errors.recovery_codes_count}
                    required
                  >
                    <Input
                      id="recovery_codes_count"
                      type="number"
                      value={form.recovery_codes_count}
                      onChange={e => setField('recovery_codes_count', parseInt(e.target.value, 10))}
                      error={errors.recovery_codes_count}
                      min={4}
                      max={20}
                      disabled={!canEdit}
                    />
                  </Field>
                )}
              </VStack>
            </CardContent>
          </Card>

          {/* Backup Email */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Backup Email</Eyebrow>

                <Toggle
                  label="Allow backup email for account recovery"
                  checked={form.backup_email_enabled}
                  onChange={e => setField('backup_email_enabled', e.target.checked)}
                  disabled={!canEdit}
                />
              </VStack>
            </CardContent>
          </Card>

          {/* Security Questions */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Security Questions</Eyebrow>

                <Toggle
                  label="Enable security questions"
                  checked={form.security_questions_enabled}
                  onChange={e => setField('security_questions_enabled', e.target.checked)}
                  disabled={!canEdit}
                />

                {form.security_questions_enabled && (
                  <Alert
                    intent="warning"
                    title="Not recommended for high security environments"
                  >
                    <Text tone="secondary" size="sm">
                      Security questions are susceptible to social engineering and guessing attacks.
                      Consider using recovery codes or backup email instead.
                    </Text>
                  </Alert>
                )}
              </VStack>
            </CardContent>
          </Card>

          {/* Admin Override */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Admin Override</Eyebrow>

                <Toggle
                  label="Allow administrators to override account recovery"
                  checked={form.admin_override_enabled}
                  onChange={e => setField('admin_override_enabled', e.target.checked)}
                  disabled={!canEdit}
                />

                <Text tone="secondary" size="sm">
                  When enabled, users with the appropriate admin permission can reset account
                  access on behalf of other users via the admin panel.
                </Text>
              </VStack>
            </CardContent>
          </Card>

          {/* Rate Limiting */}
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Eyebrow>Rate Limiting</Eyebrow>

                <Field
                  label="Max recovery attempts per hour"
                  htmlFor="recovery_rate_limit_per_hour"
                  hint="Limits how many recovery attempts a single user can make within one hour."
                  error={errors.recovery_rate_limit_per_hour}
                  required
                >
                  <Input
                    id="recovery_rate_limit_per_hour"
                    type="number"
                    value={form.recovery_rate_limit_per_hour}
                    onChange={e => setField('recovery_rate_limit_per_hour', parseInt(e.target.value, 10))}
                    error={errors.recovery_rate_limit_per_hour}
                    min={1}
                    max={100}
                    disabled={!canEdit}
                  />
                </Field>
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end">
              <Button type="submit" intent="primary" loading={saving}>
                Save Changes
              </Button>
            </HStack>
          )}
        </VStack>
      </form>
    </FormPageLayout>
  );
}

AccountRecoveryPage.layout = page => (
  <App title="Account Recovery">{page}</App>
);
