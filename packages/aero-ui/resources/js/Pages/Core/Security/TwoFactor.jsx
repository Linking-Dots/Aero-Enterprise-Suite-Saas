import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardContent,
  HStack, VStack,
  Text, Mono,
  Button,
  Badge,
  Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function TwoFactorPage({ two_factor_enabled, has_recovery_codes, confirmed_at }) {
  const toast = useToast();
  const [disabling, setDisabling] = useState(false);
  const [regenerating, setRegenerating] = useState(false);

  function disable2FA() {
    if (!confirm('Disable Two-Factor Authentication? This will reduce your account security.')) return;
    setDisabling(true);
    router.delete('/auth/two-factor', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Two-factor authentication disabled.'),
      onError: () => toast.error('Failed to disable two-factor authentication.'),
      onFinish: () => setDisabling(false),
    });
  }

  function regenerateCodes() {
    if (!confirm('Regenerate recovery codes? Your existing codes will be invalidated.')) return;
    setRegenerating(true);
    router.post('/auth/two-factor/recovery-codes', {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Recovery codes regenerated.'),
      onError: () => toast.error('Failed to regenerate recovery codes.'),
      onFinish: () => setRegenerating(false),
    });
  }

  return (
    <IndexPageLayout
      title="Two-Factor Authentication"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Security', href: route('core.profile.security') },
        { label: 'Two-Factor Authentication' },
      ]}
      description="Add an extra layer of security to your account with two-factor authentication."
    >
      <VStack gap={4}>
        {/* Status card */}
        <Card>
          <CardContent>
            <HStack justify="between" align="center">
              <VStack gap={1}>
                <Text weight="semibold">2FA Status</Text>
                <Text tone="secondary" size="sm">
                  {two_factor_enabled
                    ? 'Two-factor authentication is active on your account.'
                    : 'Two-factor authentication is not enabled.'}
                </Text>
              </VStack>
              <Badge intent={two_factor_enabled ? 'success' : 'neutral'}>
                {two_factor_enabled ? 'Enabled' : 'Disabled'}
              </Badge>
            </HStack>
          </CardContent>
        </Card>

        {/* Not enabled — prompt to enable */}
        {!two_factor_enabled && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <VStack gap={1}>
                  <Text weight="semibold">Enable Two-Factor Authentication</Text>
                  <Text tone="secondary" size="sm">
                    Protect your account by requiring a verification code in addition to your password when signing in.
                  </Text>
                </VStack>
                <HStack>
                  <Button
                    intent="primary"
                    leftIcon="shieldCheck"
                    onClick={() => router.get('/auth/two-factor')}
                  >
                    Enable 2FA
                  </Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        {/* Enabled — management options */}
        {two_factor_enabled && (
          <>
            {/* Confirmed at */}
            {confirmed_at && (
              <Card>
                <CardContent>
                  <HStack justify="between" align="center">
                    <VStack gap={0}>
                      <Text weight="semibold">Last Confirmed</Text>
                      <Text tone="secondary" size="sm">
                        Two-factor was last verified on this date.
                      </Text>
                    </VStack>
                    <Mono size="sm" tone="tertiary">
                      {new Date(confirmed_at).toLocaleString()}
                    </Mono>
                  </HStack>
                </CardContent>
              </Card>
            )}

            {/* Recovery codes */}
            <Card>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={1}>
                    <Text weight="semibold">Recovery Codes</Text>
                    <Text tone="secondary" size="sm">
                      Recovery codes allow you to access your account if you lose your authenticator device.
                    </Text>
                  </VStack>
                  <HStack gap={2} align="center">
                    <Badge intent={has_recovery_codes ? 'success' : 'warning'}>
                      {has_recovery_codes ? 'Available' : 'Not Generated'}
                    </Badge>
                    <Button
                      intent="soft"
                      size="sm"
                      leftIcon="refresh"
                      loading={regenerating}
                      disabled={regenerating}
                      onClick={regenerateCodes}
                    >
                      Regenerate
                    </Button>
                  </HStack>
                </HStack>
              </CardContent>
            </Card>

            {!has_recovery_codes && (
              <Alert
                intent="warning"
                title="No recovery codes available"
              >
                You have not generated recovery codes. If you lose access to your authenticator app, you will be locked out of your account. Generate recovery codes now.
              </Alert>
            )}

            {/* Disable 2FA */}
            <Card>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={1}>
                    <Text weight="semibold">Disable Two-Factor Authentication</Text>
                    <Text tone="secondary" size="sm">
                      Removing 2FA will make your account less secure. You will only need your password to sign in.
                    </Text>
                  </VStack>
                  <Button
                    intent="danger"
                    size="sm"
                    loading={disabling}
                    disabled={disabling}
                    onClick={disable2FA}
                  >
                    Disable 2FA
                  </Button>
                </HStack>
              </CardContent>
            </Card>
          </>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

TwoFactorPage.layout = page => (
  <App title="Two-Factor Authentication">{page}</App>
);
