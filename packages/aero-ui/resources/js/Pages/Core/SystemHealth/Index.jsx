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
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const CHECK_LABELS = {
  db:      'Database',
  cache:   'Cache',
  queue:   'Queue',
  storage: 'Storage',
  mail:    'Mail',
};

const CHECK_ICONS = {
  db:      'circleStack',
  cache:   'bolt',
  queue:   'queueList',
  storage: 'folderOpen',
  mail:    'envelope',
};

const STATUS_INTENT = {
  healthy:  'success',
  degraded: 'warning',
  down:     'danger',
};

function HealthCheckCard({ name, check }) {
  const label  = CHECK_LABELS[name]  ?? name;
  const intent = STATUS_INTENT[check?.status] ?? 'neutral';

  return (
    <Card>
      <CardBody>
        <VStack gap={2}>
          <HStack gap={2} align="center">
            <Eyebrow>{label}</Eyebrow>
            <Badge intent={intent}>
              {check?.status ?? 'unknown'}
            </Badge>
          </HStack>
          {check?.message && (
            <Text size="sm" tone="secondary">{check.message}</Text>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function SystemHealthIndex({ checks }) {
  const toast     = useToast();
  const [running, setRunning] = useState(false);

  const runChecks = () => {
    setRunning(true);
    router.post(route('core.system-health.run-checks'), {}, {
      preserveState: false,
      onSuccess: () => toast.success('Health checks complete.'),
      onError:   () => toast.error('Health check run failed.'),
      onFinish:  () => setRunning(false),
    });
  };

  const checkKeys = checks ? Object.keys(checks) : ['db', 'cache', 'queue', 'storage', 'mail'];

  return (
    <IndexPageLayout
      title="System Health"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Health' },
      ]}
      description="Live status of all platform sub-systems."
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('core.system-health.performance'))}
          >
            Performance
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('core.system-health.cache'))}
          >
            Cache
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('core.system-health.scheduled-tasks'))}
          >
            Scheduled Tasks
          </Button>
          <Button
            intent="primary"
            loading={running}
            onClick={runChecks}
            leftIcon="arrowPath"
          >
            Run Checks
          </Button>
        </HStack>
      }
      table={
        <VStack gap={3}>
          {checkKeys.map(key => (
            <HealthCheckCard key={key} name={key} check={checks?.[key]} />
          ))}
        </VStack>
      }
    />
  );
}

SystemHealthIndex.layout = page => (
  <App title="System Health">{page}</App>
);
