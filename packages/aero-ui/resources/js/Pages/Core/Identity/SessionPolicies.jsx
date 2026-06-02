import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Card, CardContent,
  VStack, HStack,
  Text,
  Field,
  Input,
  Toggle,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function SessionPoliciesPage({ policy }) {
  const toast   = useToast();
  const canEdit = useHRMAC('auth.sso_identity.session_policies.edit');

  const form = useForm({
    session_lifetime_minutes:         policy?.session_lifetime_minutes          ?? 480,
    single_session_per_user:          policy?.single_session_per_user           ?? false,
    max_concurrent_sessions:          policy?.max_concurrent_sessions           ?? 5,
    force_logout_on_password_change:  policy?.force_logout_on_password_change   ?? true,
    require_fresh_auth_for_sensitive: policy?.require_fresh_auth_for_sensitive  ?? true,
    idle_timeout_minutes:             policy?.idle_timeout_minutes              ?? null,
  });

  function handleSubmit(e) {
    e.preventDefault();
    form.post(route('core.identity.session-policies.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Session policies saved.'),
      onError:   () => toast.error('Failed to save session policies.'),
    });
  }

  return (
    <FormPageLayout
      title="Session Policies"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'Session Policies' },
      ]}
      description="Control session lifetime, concurrency limits, and re-authentication requirements."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4}>
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Session Lifetime</Text>

                <Field
                  label="Session lifetime (minutes)"
                  htmlFor="session_lifetime_minutes"
                  hint="How long a session remains active from the last authentication. E.g. 480 = 8 hours."
                  error={form.errors.session_lifetime_minutes}
                  required
                >
                  <Input
                    id="session_lifetime_minutes"
                    type="number"
                    value={form.data.session_lifetime_minutes}
                    onChange={e => form.setData('session_lifetime_minutes', parseInt(e.target.value, 10))}
                    error={form.errors.session_lifetime_minutes}
                    min={5}
                    max={43200}
                    disabled={!canEdit}
                  />
                </Field>

                <Field
                  label="Idle timeout (minutes)"
                  htmlFor="idle_timeout_minutes"
                  hint="Terminate sessions idle for this long. Leave blank to disable idle timeout."
                  error={form.errors.idle_timeout_minutes}
                >
                  <Input
                    id="idle_timeout_minutes"
                    type="number"
                    value={form.data.idle_timeout_minutes ?? ''}
                    onChange={e => {
                      const val = e.target.value === '' ? null : parseInt(e.target.value, 10);
                      form.setData('idle_timeout_minutes', val);
                    }}
                    error={form.errors.idle_timeout_minutes}
                    min={1}
                    max={1440}
                    placeholder="Disabled"
                    disabled={!canEdit}
                  />
                </Field>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Concurrency</Text>

                <Toggle
                  label="Limit to one session per user"
                  checked={form.data.single_session_per_user}
                  onChange={e => form.setData('single_session_per_user', e.target.checked)}
                  disabled={!canEdit}
                />

                {!form.data.single_session_per_user && (
                  <Field
                    label="Max concurrent sessions"
                    htmlFor="max_concurrent_sessions"
                    hint="Maximum number of active sessions allowed simultaneously per user."
                    error={form.errors.max_concurrent_sessions}
                  >
                    <Input
                      id="max_concurrent_sessions"
                      type="number"
                      value={form.data.max_concurrent_sessions}
                      onChange={e => form.setData('max_concurrent_sessions', parseInt(e.target.value, 10))}
                      error={form.errors.max_concurrent_sessions}
                      min={1}
                      max={100}
                      disabled={!canEdit}
                    />
                  </Field>
                )}
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Security Controls</Text>

                <Toggle
                  label="Force logout all sessions on password change"
                  checked={form.data.force_logout_on_password_change}
                  onChange={e => form.setData('force_logout_on_password_change', e.target.checked)}
                  disabled={!canEdit}
                />

                <Toggle
                  label="Require fresh authentication for sensitive operations"
                  checked={form.data.require_fresh_auth_for_sensitive}
                  onChange={e => form.setData('require_fresh_auth_for_sensitive', e.target.checked)}
                  disabled={!canEdit}
                />
                <Text tone="secondary" size="sm">
                  When enabled, users accessing billing, security settings, or API keys will be
                  prompted to re-enter their credentials even during an active session.
                </Text>
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

SessionPoliciesPage.layout = page => (
  <App title="Session Policies">{page}</App>
);
