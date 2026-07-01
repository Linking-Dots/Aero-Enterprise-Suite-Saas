import { Card, CardBody, VStack, HStack, Box, Text, Heading, Eyebrow, Badge, Button } from '@aero/ui';
import { money } from '../money.js';

function PlanCard({ plan, isCurrent, onChange, busy }) {
  return (
    <Card className={isCurrent ? 'aeos-plan-current' : undefined}>
      <CardBody>
        <VStack gap={4}>
          <VStack gap={1}>
            <HStack gap={2} align="center">
              <Box grow><Eyebrow>{plan.name}</Eyebrow></Box>
              {isCurrent && <Badge intent="success" size="sm">Current</Badge>}
            </HStack>
            <HStack gap={1} align="baseline">
              <Heading size="lg">{money(plan.price, plan.currency)}</Heading>
              <Text size="sm" tone="secondary">/ {plan.interval ?? 'month'}</Text>
            </HStack>
          </VStack>

          {Array.isArray(plan.features) && plan.features.length > 0 && (
            <VStack gap={2}>
              {plan.features.map((f, i) => (
                <HStack key={i} gap={2} align="center">
                  <Badge intent="success" size="sm">✓</Badge>
                  <Text size="sm">{f}</Text>
                </HStack>
              ))}
            </VStack>
          )}

          {isCurrent ? (
            <Button intent="ghost" type="button" fullWidth disabled>Current plan</Button>
          ) : (
            <Button intent="primary" type="button" fullWidth loading={busy} onClick={() => onChange(plan.id)}>
              Switch to {plan.name}
            </Button>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function PlansPanel({ plans, currentPlanId, onChangePlan, onCancel, changingId, cancelling, canCancel }) {
  const list = plans ?? [];

  return (
    <VStack gap={4}>
      {list.length === 0 ? (
        <Card><CardBody><Text tone="secondary">No plans are available right now.</Text></CardBody></Card>
      ) : (
        <div className="aeos-billing-grid">
          {list.map(plan => (
            <PlanCard
              key={plan.id}
              plan={plan}
              isCurrent={plan.id === currentPlanId}
              onChange={onChangePlan}
              busy={changingId === plan.id}
            />
          ))}
        </div>
      )}

      {canCancel && (
        <Card>
          <CardBody>
            <HStack gap={3} align="center" wrap>
              <Box grow>
                <VStack gap={1}>
                  <Text>Cancel subscription</Text>
                  <Text size="sm" tone="secondary">Your plan stays active until the end of the current billing period.</Text>
                </VStack>
              </Box>
              <Button intent="danger" type="button" loading={cancelling} disabled={cancelling} onClick={onCancel}>
                Cancel subscription
              </Button>
            </HStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}
