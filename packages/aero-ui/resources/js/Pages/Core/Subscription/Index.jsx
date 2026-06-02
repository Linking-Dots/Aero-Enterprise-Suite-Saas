import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardBody,
  VStack, HStack, Box,
  Text, Eyebrow, Mono,
  Badge, Progress, Button,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function UsageBar({ label, used, limit, unit = '' }) {
  const pct   = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0;
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

export default function SubscriptionIndex({ plan, usage }) {
  const p = plan   ?? {};
  const u = usage  ?? {};

  const users   = u.users   ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };
  const modules = u.modules ?? [];

  return (
    <IndexPageLayout
      title="Subscription"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Subscription' },
      ]}
      description="Manage your current plan, usage, and billing."
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            leftIcon="creditCard"
            onClick={() => router.get(route('core.subscription.invoices'))}
          >
            Manage Billing
          </Button>
          <Button
            intent="primary"
            leftIcon="arrowUp"
            onClick={() => router.get(route('core.subscription.plans'))}
          >
            Upgrade Plan
          </Button>
        </HStack>
      }
      kpis={[
        <Stat
          key="plan"
          title="Current Plan"
          value={p.name ?? '—'}
          icon="sparkles"
          iconTone="indigo"
        />,
        <Stat
          key="price"
          title="Billing"
          value={p.price != null ? `$${p.price}` : '—'}
          description={p.interval ? `per ${p.interval}` : undefined}
          icon="currencyDollar"
          iconTone="success"
        />,
        <Stat
          key="users"
          title="Users"
          value={`${users.used} / ${users.limit}`}
          icon="users"
          iconTone={users.used / users.limit >= 0.9 ? 'danger' : 'amber'}
        />,
        <Stat
          key="storage"
          title="Storage"
          value={`${storage.used_gb} / ${storage.limit_gb} GB`}
          icon="server"
          iconTone={storage.used_gb / storage.limit_gb >= 0.9 ? 'danger' : 'amber'}
        />,
      ]}
      table={
        <VStack gap={4}>
          {/* Usage */}
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Resource Usage</Eyebrow>
                <UsageBar
                  label="Users"
                  used={users.used}
                  limit={users.limit}
                />
                <UsageBar
                  label="Storage"
                  used={storage.used_gb}
                  limit={storage.limit_gb}
                  unit=" GB"
                />
              </VStack>
            </CardBody>
          </Card>

          {/* Enabled Modules */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Enabled Modules</Eyebrow>
                {modules.length > 0 ? (
                  <HStack gap={2} wrap>
                    {modules.map((mod, i) => (
                      <Badge key={i} intent="neutral">{mod}</Badge>
                    ))}
                  </HStack>
                ) : (
                  <Text tone="secondary" size="sm">No modules enabled.</Text>
                )}
              </VStack>
            </CardBody>
          </Card>

          {/* Plan features */}
          {Array.isArray(p.features) && p.features.length > 0 && (
            <Card>
              <CardBody>
                <VStack gap={3}>
                  <Eyebrow>Plan Features</Eyebrow>
                  <VStack gap={2}>
                    {p.features.map((feat, i) => (
                      <HStack key={i} gap={2} align="center">
                        <Badge intent="success" size="sm">✓</Badge>
                        <Text size="sm">{feat}</Text>
                      </HStack>
                    ))}
                  </VStack>
                </VStack>
              </CardBody>
            </Card>
          )}
        </VStack>
      }
    />
  );
}

SubscriptionIndex.layout = page => (
  <App title="Subscription">{page}</App>
);
