/**
 * Security Settings — require 2FA, session lifetime, lockout policy.
 *
 * Props: { settings: {} }
 *
 * Ported onto the unified SettingsLayout shell (Task 2). Violations fixed:
 *   P0-1: no style={}
 *   P0-2: no raw <button> / <input> / <select>
 *   P2-1: intent= not variant=
 *   HRMAC: canEdit now gates on the declared action core.settings.security.edit
 *          (was core.settings.security.update — Task 0 mismatch).
 */
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Toggle,
  Card, CardHeader, CardBody,
  VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

export default function SecuritySettings({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.security.edit');

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    require_2fa_admins:  settings.require_2fa_admins  ?? false,
    session_lifetime:    settings.session_lifetime    ?? 120,
    max_failed_attempts: settings.max_failed_attempts ?? 5,
    lockout_duration:    settings.lockout_duration     ?? 15,
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.security.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Security settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <SettingsSection
        title="Security"
        description="Configure two-factor authentication requirements, session policy, and lockout rules."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        {/* ── Authentication ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Authentication</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Toggle
                label="Require 2FA for administrators"
                checked={!!data.require_2fa_admins}
                onChange={e => setData('require_2fa_admins', e.target.checked)}
              />
            </VStack>
          </CardBody>
        </Card>

        {/* ── Session Policy ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Session Policy</Text></CardHeader>
          <CardBody>
            <Field
              label="Session Lifetime (minutes)"
              hint="How long an idle session remains valid before the user is logged out."
              error={errors.session_lifetime}
            >
              <Input
                type="number"
                value={data.session_lifetime}
                onChange={e => setData('session_lifetime', Number(e.target.value))}
                min={5}
                max={43200}
              />
            </Field>
          </CardBody>
        </Card>

        {/* ── Lockout Policy ── */}
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Account Lockout</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field
                label="Max Failed Login Attempts"
                hint="Account is locked after this many consecutive failures."
                error={errors.max_failed_attempts}
              >
                <Input
                  type="number"
                  value={data.max_failed_attempts}
                  onChange={e => setData('max_failed_attempts', Number(e.target.value))}
                  min={1}
                  max={20}
                />
              </Field>
              <Field
                label="Lockout Duration (minutes)"
                hint="How long a locked account must wait. Set 0 to require manual unlock."
                error={errors.lockout_duration}
              >
                <Input
                  type="number"
                  value={data.lockout_duration}
                  onChange={e => setData('lockout_duration', Number(e.target.value))}
                  min={0}
                  max={1440}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>
      </SettingsSection>
    </form>
  );
}

SecuritySettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="security">{page}</SettingsLayout>
  </App>
);
