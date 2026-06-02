import { router } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent, Button, Badge,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function AdminUserDevices({ user, devices }) {
  const toast = useToast();

  const deactivate = (deviceId) => {
    if (!confirm('Deactivate this device?')) return;
    router.delete(route('core.devices.admin.deactivate', { userId: user.id, deviceId }), {
      preserveScroll: true,
      onSuccess: () => toast.success('Device deactivated.'),
    });
  };

  const resetAll = () => {
    if (!confirm(`Reset all devices for ${user.name}? They will need to log in again.`)) return;
    router.post(route('core.devices.admin.reset', { userId: user.id }), {}, {
      onSuccess: () => toast.success('All devices reset.'),
    });
  };

  return (
    <App>
      <IndexPageLayout
        title={`Devices — ${user.name}`}
        actions={<Button intent="ghost" tone="danger" onClick={resetAll}>Reset All Devices</Button>}
      >
        <VStack gap={3}>
          {devices.length === 0 ? (
            <Card><CardContent><Text tone="secondary">No registered devices.</Text></CardContent></Card>
          ) : devices.map(d => (
            <Card key={d.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={0}>
                    <HStack gap={2}>
                      <Text weight="semibold">{d.device_name ?? 'Unknown device'}</Text>
                      {!d.is_active && <Badge intent="neutral">Inactive</Badge>}
                    </HStack>
                    <Text size="sm" tone="secondary">{d.browser} · {d.platform}</Text>
                    <Mono size="xs" tone="tertiary">{d.ip_address} · Last used {d.last_used_at}</Mono>
                  </VStack>
                  {d.is_active && (
                    <Button size="sm" intent="ghost" tone="danger" onClick={() => deactivate(d.id)}>
                      Deactivate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
