import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Heading, Badge, Button } from '@aero/ui';

function money(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

function PlanCard({ plan, isCurrent, onChange, busy }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={4}>
          <HStack gap={2} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>{plan.name}</Eyebrow>
                <HStack gap={1} align="baseline">
                  <Heading size="lg">{money(plan.price, plan.currency)}</Heading>
                  <Text tone="secondary" size="sm">/ {plan.interval ?? 'month'}</Text>
                </HStack>
              </VStack>
            </Box>
            {isCurrent && <Badge intent="success">Current Plan</Badge>}
          </HStack>
          {Array.isArray(plan.features) && plan.features.length > 0 && (
            <VStack gap={2}>
              {plan.features.map((feat, i) => (
                <HStack key={i} gap={2} align="center">
                  <Badge intent="success" size="sm">✓</Badge>
                  <Text size="sm">{feat}</Text>
                </HStack>
              ))}
            </VStack>
          )}
          {isCurrent ? (
            <Button intent="ghost" disabled fullWidth type="button">Active Plan</Button>
          ) : (
            <Button intent="primary" fullWidth type="button" loading={busy} onClick={() => onChange(plan.id)}>
              Switch to {plan.name}
            </Button>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function PlansPanel({ plans, currentPlanId, onChangePlan, onCancel, changingId, canCancel }) {
  const list = plans ?? [];
  return (
    <VStack gap={4}>
      {list.length === 0 ? (
        <Text tone="secondary">No plans available.</Text>
      ) : (
        <HStack gap={4} align="start" wrap>
          {list.map(plan => (
            <Box key={plan.id} grow>
              <PlanCard plan={plan} isCurrent={plan.id === currentPlanId}
                onChange={onChangePlan} busy={changingId === plan.id} />
            </Box>
          ))}
        </HStack>
      )}
      {canCancel && (
        <HStack gap={2} justify="end">
          <Button intent="danger" size="sm" type="button" onClick={onCancel}>Cancel Subscription</Button>
        </HStack>
      )}
    </VStack>
  );
}
