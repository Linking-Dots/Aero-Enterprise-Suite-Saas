import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Mono, Badge, Progress } from '@aero/ui';

function UsageBar({ label, used, limit, unit = '' }) {
  const pct = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0;
  const intent = limit === 0 ? 'neutral' : pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success';
  return (
    <VStack gap={1}>
      <HStack gap={2} align="center">
        <Box grow><Text size="sm">{label}</Text></Box>
        <Mono size="sm" tone="secondary">{used}{unit} / {limit === 0 ? '∞' : `${limit}${unit}`}</Mono>
        <Badge intent={intent} size="sm">{limit === 0 ? '—' : `${pct}%`}</Badge>
      </HStack>
      <Progress value={pct} intent={intent} />
    </VStack>
  );
}

export default function UsagePanel({ usage }) {
  const u = usage ?? {};
  const users = u.users ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };
  const metrics = Object.entries(u.metrics ?? {});

  return (
    <VStack gap={4}>
      <Card>
        <CardBody>
          <VStack gap={4}>
            <Eyebrow>Resource Usage</Eyebrow>
            <UsageBar label="Users" used={users.used} limit={users.limit} />
            <UsageBar label="Storage" used={storage.used_gb} limit={storage.limit_gb} unit=" GB" />
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
                  <Box grow><Text size="sm">{name}</Text></Box>
                  <Mono size="sm" tone="secondary">{qty}</Mono>
                </HStack>
              ))}
            </VStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}
