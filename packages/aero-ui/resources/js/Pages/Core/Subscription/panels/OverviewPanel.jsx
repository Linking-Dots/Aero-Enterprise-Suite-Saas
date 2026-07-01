import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Badge } from '@aero/ui';
import UsagePanel from './UsagePanel.jsx';

function money(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

export default function OverviewPanel({ summary, plan, usage, products }) {
  const s = summary ?? {};
  const prods = products ?? [];
  const features = plan?.features ?? [];

  return (
    <VStack gap={4}>
      <Card>
        <CardBody>
          <VStack gap={3}>
            <Eyebrow>Current Plan</Eyebrow>
            <HStack gap={2} align="baseline">
              <Text size="lg">{s.plan_name ?? '—'}</Text>
              {s.price != null && <Text tone="secondary" size="sm">{money(s.price, s.currency)} / {s.interval}</Text>}
              {s.status && <Badge intent={s.status === 'active' ? 'success' : 'warning'}>{s.status}</Badge>}
            </HStack>
            {s.days_left != null && <Text tone="secondary" size="sm">Trial: {s.days_left} days left</Text>}
          </VStack>
        </CardBody>
      </Card>

      <UsagePanel usage={usage} />

      <Card>
        <CardBody>
          <VStack gap={3}>
            <Eyebrow>Active Products</Eyebrow>
            {prods.length > 0 ? (
              <VStack gap={2}>
                {prods.map(p => (
                  <HStack key={p.id} gap={2} align="center">
                    <Box grow><Text size="sm">{p.name ?? '—'}</Text></Box>
                    <Text tone="secondary" size="sm">{money(p.price, p.currency)}</Text>
                    <Badge intent={p.status === 'active' ? 'success' : 'neutral'} size="sm">{p.status}</Badge>
                  </HStack>
                ))}
              </VStack>
            ) : (
              <Text tone="secondary" size="sm">No add-on products.</Text>
            )}
          </VStack>
        </CardBody>
      </Card>

      {features.length > 0 && (
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Plan Features</Eyebrow>
              <VStack gap={2}>
                {features.map((f, i) => (
                  <HStack key={i} gap={2} align="center">
                    <Badge intent="success" size="sm">✓</Badge>
                    <Text size="sm">{f}</Text>
                  </HStack>
                ))}
              </VStack>
            </VStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}
