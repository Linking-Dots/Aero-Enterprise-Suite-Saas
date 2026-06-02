import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardBody,
  Button,
  Badge,
  VStack,
  HStack,
  Text,
  Eyebrow,
  Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const DRIVER_INTENT = {
  redis:   'success',
  file:    'neutral',
  array:   'warning',
  memcached: 'success',
};

function StatRow({ label, value }) {
  return (
    <HStack gap={3}>
      <Text size="sm" tone="secondary">{label}</Text>
      <Mono size="sm">{value ?? '—'}</Mono>
    </HStack>
  );
}

export default function SystemHealthCache({ driver, stats }) {
  const toast      = useToast();
  const [clearing, setClearing] = useState(false);

  const clearCache = () => {
    if (!confirm('Clear all application cache? This may temporarily slow response times.')) return;
    setClearing(true);
    router.post(route('core.system-health.cache.clear'), {}, {
      preserveState: false,
      onSuccess: () => toast.success('Cache cleared successfully.'),
      onError:   () => toast.error('Failed to clear cache.'),
      onFinish:  () => setClearing(false),
    });
  };

  const driverIntent = DRIVER_INTENT[driver?.toLowerCase()] ?? 'neutral';

  return (
    <IndexPageLayout
      title="Cache Management"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Health', href: route('core.system-health.index') },
        { label: 'Cache' },
      ]}
      description="Inspect cache driver statistics and flush cached data."
      actions={
        <Button
          intent="danger"
          loading={clearing}
          onClick={clearCache}
          leftIcon="trash"
        >
          Clear All Cache
        </Button>
      }
      table={
        <VStack gap={3}>
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={2} align="center">
                  <Eyebrow>Cache Driver</Eyebrow>
                  <Badge intent={driverIntent}>
                    {driver ?? 'unknown'}
                  </Badge>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          {stats && Object.keys(stats).length > 0 && (
            <Card>
              <CardBody>
                <VStack gap={3}>
                  <Eyebrow>Driver Statistics</Eyebrow>
                  <VStack gap={2}>
                    {stats.hit_rate !== undefined && (
                      <StatRow
                        label="Hit Rate"
                        value={typeof stats.hit_rate === 'number'
                          ? `${stats.hit_rate.toFixed(1)}%`
                          : stats.hit_rate}
                      />
                    )}
                    {stats.memory !== undefined && (
                      <StatRow label="Memory Used" value={stats.memory} />
                    )}
                    {stats.keys !== undefined && (
                      <StatRow
                        label="Keys Stored"
                        value={String(stats.keys)}
                      />
                    )}
                    {stats.uptime !== undefined && (
                      <StatRow label="Uptime" value={stats.uptime} />
                    )}
                    {stats.connected_clients !== undefined && (
                      <StatRow
                        label="Connected Clients"
                        value={String(stats.connected_clients)}
                      />
                    )}
                    {stats.evicted_keys !== undefined && (
                      <StatRow
                        label="Evicted Keys"
                        value={String(stats.evicted_keys)}
                      />
                    )}
                  </VStack>
                </VStack>
              </CardBody>
            </Card>
          )}

          {(!stats || Object.keys(stats).length === 0) && (
            <Card>
              <CardBody>
                <Text tone="secondary">
                  No statistics available for the current cache driver.
                </Text>
              </CardBody>
            </Card>
          )}
        </VStack>
      }
    />
  );
}

SystemHealthCache.layout = page => (
  <App title="Cache Management">{page}</App>
);
