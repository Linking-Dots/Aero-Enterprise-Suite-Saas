import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Card, CardContent,
  Button, Badge, Toggle,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function DeviceTypeLabel({ type }) {
  const labels = { mobile: 'Mobile', tablet: 'Tablet', desktop: 'Desktop', unknown: 'Unknown' };
  const glyphs  = { mobile: '📱', tablet: '📲', desktop: '💻', unknown: '🖥️' };
  const glyph   = glyphs[type] ?? glyphs.unknown;
  const label   = labels[type] ?? labels.unknown;
  return (
    <span className="device-icon" aria-label={label}>{glyph}</span>
  );
}

export default function Devices({ devices, singleDeviceEnabled }) {
  const toast = useToast();

  const deactivate = (deviceId) => {
    if (!confirm('Deactivate this device? You will be logged out from it.')) return;
    router.delete(route('core.devices.deactivate', deviceId), {
      preserveScroll: true,
      onSuccess: () => toast.success('Device deactivated.'),
      onError:   () => toast.error('Failed to deactivate device.'),
    });
  };

  const toggleSingleDevice = () => {
    router.post(route('core.devices.admin.toggle', { userId: 'me' }), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success(
        singleDeviceEnabled
          ? 'Multi-device login enabled.'
          : 'Single-device login enabled.',
      ),
    });
  };

  return (
    <DetailPageLayout title="Trusted Devices">
      <VStack gap={4}>
        <Card className="devices-settings-card">
          <CardContent>
            <HStack justify="between" align="center">
              <VStack gap={0}>
                <Text weight="semibold">Single-device login</Text>
                <Text tone="secondary" size="sm">
                  When enabled, only the most recently active device can log in.
                  All other devices will be signed out automatically.
                </Text>
              </VStack>
              <Toggle checked={singleDeviceEnabled} onChange={toggleSingleDevice} />
            </HStack>
          </CardContent>
        </Card>

        <VStack gap={3}>
          {devices.length === 0 ? (
            <Card>
              <CardContent>
                <Text tone="secondary">No trusted devices found.</Text>
              </CardContent>
            </Card>
          ) : devices.map(device => (
            <Card key={device.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <HStack gap={3} align="center">
                    <DeviceTypeLabel type={device.device_type} />
                    <VStack gap={0}>
                      <HStack gap={2} align="center">
                        <Text weight="semibold">{device.device_name ?? 'Unknown device'}</Text>
                        {device.is_current && <Badge intent="primary">This device</Badge>}
                        {!device.is_active && <Badge intent="neutral">Inactive</Badge>}
                      </HStack>
                      <Text size="sm" tone="secondary">{device.browser} · {device.platform}</Text>
                      <Mono size="xs" tone="tertiary">{device.ip_address} · Last used {device.last_used_at}</Mono>
                    </VStack>
                  </HStack>
                  {!device.is_current && device.is_active && (
                    <Button size="sm" intent="danger" onClick={() => deactivate(device.id)}>
                      Deactivate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}
        </VStack>
      </VStack>

      <style>{`
        .device-icon {
          font-size: 1.25rem;
          line-height: 1;
          flex-shrink: 0;
        }
      `}</style>
    </DetailPageLayout>
  );
}

Devices.layout = page => (
  <App title="Trusted Devices">{page}</App>
);
