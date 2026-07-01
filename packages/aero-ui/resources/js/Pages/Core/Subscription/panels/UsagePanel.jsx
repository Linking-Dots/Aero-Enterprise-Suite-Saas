import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Mono } from '@aero/ui';
import UsageMeter from './UsageMeter.jsx';

export default function UsagePanel({ usage }) {
  const u = usage ?? {};
  const users = u.users ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };
  const metrics = Object.entries(u.metrics ?? {});

  return (
    <div className="aeos-billing-split">
      <Card>
        <CardBody>
          <VStack gap={4}>
            <Eyebrow>Resource usage</Eyebrow>
            <UsageMeter label="Users" used={users.used} limit={users.limit} />
            <UsageMeter label="Storage" used={storage.used_gb} limit={storage.limit_gb} unit=" GB" />
          </VStack>
        </CardBody>
      </Card>

      <Card>
        <CardBody>
          <VStack gap={3}>
            <Eyebrow>Metered usage</Eyebrow>
            {metrics.length > 0 ? (
              <VStack gap={3}>
                {metrics.map(([name, qty]) => (
                  <HStack key={name} gap={2} align="center">
                    <Box grow><Text size="sm">{name}</Text></Box>
                    <Mono size="sm" tone="secondary">{qty}</Mono>
                  </HStack>
                ))}
              </VStack>
            ) : (
              <Text size="sm" tone="secondary">No metered usage recorded this billing period.</Text>
            )}
          </VStack>
        </CardBody>
      </Card>
    </div>
  );
}
