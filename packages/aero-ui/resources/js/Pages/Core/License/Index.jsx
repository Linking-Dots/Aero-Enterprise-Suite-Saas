/**
 * License Management — status card with edition chip, status chip, expiry, domain,
 * and action buttons: Activate/Deactivate, View Features, View Updates, Renew.
 */
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  HStack, VStack,
  Text, Eyebrow, Mono,
  Button, Badge,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:   'success',
  expired:  'danger',
  invalid:  'danger',
  inactive: 'neutral',
};

export default function LicenseIndex({ license = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.license.manage');

  const {
    key_preview,
    edition,
    status,
    expires_at,
    domain,
    activated_at,
  } = license;

  const isActive = status === 'active';

  const handleActivate = () => {
    router.post(route('core.license.activate'), {}, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('License activated.'),
      onError:   () => toast.error('Failed to activate license.'),
    });
  };

  const handleDeactivate = () => {
    if (!confirm('Deactivate this license? The application will enter restricted mode.')) return;
    router.post(route('core.license.deactivate'), {}, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('License deactivated.'),
      onError:   () => toast.error('Failed to deactivate license.'),
    });
  };

  return (
    <IndexPageLayout
      title="License"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'License' },
      ]}
      description="View and manage your AEOS365 software license."
      actions={
        canEdit && (
          <HStack gap={2}>
            {isActive
              ? <Button intent="ghost" onClick={handleDeactivate}>Deactivate</Button>
              : <Button intent="primary" onClick={handleActivate}>Activate</Button>
            }
          </HStack>
        )
      }
    >
      <VStack gap={5}>

        {/* ── License Status Card ── */}
        <Card>
          <CardHeader>
            <Eyebrow>License Details</Eyebrow>
          </CardHeader>
          <CardBody>
            <VStack gap={4}>

              <HStack gap={4} align="center" wrap>
                {edition && (
                  <Badge intent="info">{edition}</Badge>
                )}
                {status && (
                  <Badge intent={STATUS_INTENT[status] ?? 'neutral'}>
                    {status.charAt(0).toUpperCase() + status.slice(1)}
                  </Badge>
                )}
              </HStack>

              <VStack gap={3}>
                {key_preview && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">License Key</Text>
                    <Mono>{key_preview}</Mono>
                  </HStack>
                )}

                {domain && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">Domain</Text>
                    <Mono>{domain}</Mono>
                  </HStack>
                )}

                {activated_at && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">Activated</Text>
                    <Text size="sm">{new Date(activated_at).toLocaleDateString()}</Text>
                  </HStack>
                )}

                {expires_at && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">Expires</Text>
                    <Text size="sm">{new Date(expires_at).toLocaleDateString()}</Text>
                  </HStack>
                )}
              </VStack>

            </VStack>
          </CardBody>
        </Card>

        {/* ── Quick Actions ── */}
        <Card>
          <CardHeader>
            <Eyebrow>Actions</Eyebrow>
          </CardHeader>
          <CardBody>
            <HStack gap={3} wrap>
              <Button intent="soft" onClick={() => router.get(route('core.license.features'))}>
                View Features
              </Button>
              <Button intent="soft" onClick={() => router.get(route('core.license.updates'))}>
                View Updates
              </Button>
              <Button intent="soft" onClick={() => router.get(route('core.license.renewal'))}>
                Renew License
              </Button>
              <Button intent="ghost" onClick={() => router.get(route('core.license.activation'))}>
                Activation Settings
              </Button>
            </HStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

LicenseIndex.layout = page => (
  <App title="License">{page}</App>
);
