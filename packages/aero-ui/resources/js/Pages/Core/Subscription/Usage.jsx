import {
  IndexPageLayout,
  Card, CardBody,
  VStack, HStack, Box,
  Text, Eyebrow, Mono,
  Badge, Progress,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function UsageBar({ label, used, limit, unit = '' }) {
  const pct    = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0;
  const intent = pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success';

  return (
    <VStack gap={1}>
      <HStack gap={2} align="center">
        <Box grow>
          <Text size="sm">{label}</Text>
        </Box>
        <Mono size="sm" tone="secondary">
          {used}{unit} / {limit}{unit}
        </Mono>
        <Badge intent={intent} size="sm">{pct}%</Badge>
      </HStack>
      <Progress value={pct} intent={intent} />
    </VStack>
  );
}

export default function SubscriptionUsage({ plan, usage }) {
  const p = plan  ?? {};
  const u = usage ?? {};

  const users   = u.users   ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };

  // Any additional metrics returned by resolveUsage() (keyed by metric_name).
  const reserved = new Set(['users', 'storage', 'modules']);
  const metrics  = Object.entries(u).filter(([k]) => !reserved.has(k));

  return (
    <IndexPageLayout
      title="Usage"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Subscription', href: route('core.subscription.index') },
        { label: 'Usage' },
      ]}
      description="Resource consumption for the current billing period."
      kpis={[
        <Stat
          key="plan"
          title="Current Plan"
          value={p.name ?? '—'}
          icon="sparkles"
          iconTone="indigo"
        />,
        <Stat
          key="users"
          title="Users"
          value={`${users.used} / ${users.limit}`}
          icon="users"
          iconTone={users.limit > 0 && users.used / users.limit >= 0.9 ? 'danger' : 'amber'}
        />,
        <Stat
          key="storage"
          title="Storage"
          value={`${storage.used_gb} / ${storage.limit_gb} GB`}
          icon="server"
          iconTone={storage.limit_gb > 0 && storage.used_gb / storage.limit_gb >= 0.9 ? 'danger' : 'amber'}
        />,
      ]}
      table={
        <VStack gap={4}>
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Resource Usage</Eyebrow>
                <UsageBar label="Users" used={users.used} limit={users.limit} />
                <UsageBar
                  label="Storage"
                  used={storage.used_gb}
                  limit={storage.limit_gb}
                  unit=" GB"
                />
              </VStack>
            </CardBody>
          </Card>

          {metrics.length > 0 && (
            <Card>
              <CardBody>
                <VStack gap={3}>
                  <Eyebrow>Metered Usage</Eyebrow>
                  {metrics.map(([name, qty]) => (
                    <HStack key={name} gap={2} align="center">
                      <Box grow>
                        <Text size="sm">{name}</Text>
                      </Box>
                      <Mono size="sm" tone="secondary">{qty}</Mono>
                    </HStack>
                  ))}
                </VStack>
              </CardBody>
            </Card>
          )}
        </VStack>
      }
    />
  );
}

SubscriptionUsage.layout = page => (
  <App title="Usage">{page}</App>
);
