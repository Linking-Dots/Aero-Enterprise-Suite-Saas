import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardBody,
  VStack, HStack, Box,
  Text, Eyebrow, Heading,
  Badge, Button,
  Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function PlanCard({ plan, isCurrent, onUpgrade, upgrading }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={4}>
          {/* Header */}
          <HStack gap={2} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>{plan.name}</Eyebrow>
                <HStack gap={1} align="baseline">
                  <Heading size="lg">${plan.price}</Heading>
                  <Text tone="secondary" size="sm">/ {plan.interval ?? 'month'}</Text>
                </HStack>
              </VStack>
            </Box>
            {isCurrent && <Badge intent="success">Current Plan</Badge>}
          </HStack>

          {/* Features */}
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

          {/* Action */}
          {isCurrent ? (
            <Button intent="ghost" disabled fullWidth>
              Active Plan
            </Button>
          ) : (
            <Button
              intent="primary"
              fullWidth
              loading={upgrading}
              onClick={() => onUpgrade(plan.id)}
            >
              Upgrade to {plan.name}
            </Button>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function PlansIndex({ plans, current_plan_id }) {
  const toast = useToast();
  const [upgradingId, setUpgradingId] = useState(null);
  const [cancelled, setCancelled]     = useState(false);

  const handleUpgrade = planId => {
    if (!confirm('Switch to this plan?')) return;
    setUpgradingId(planId);
    router.post(
      route('core.subscription.change-plan'),
      { plan_id: planId },
      {
        preserveState: true,
        onSuccess: () => toast.success('Plan updated successfully.'),
        onError:   () => toast.error('Failed to change plan.'),
        onFinish:  () => setUpgradingId(null),
      },
    );
  };

  const handleCancel = () => {
    if (!confirm('Cancel your subscription? This cannot be undone until the billing cycle ends.')) return;
    router.post(
      route('core.subscription.cancel'),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          setCancelled(true);
          toast.success('Subscription cancelled.');
        },
        onError: () => toast.error('Failed to cancel subscription.'),
      },
    );
  };

  return (
    <IndexPageLayout
      title="Plans"
      breadcrumb={[
        { label: 'Dashboard',     href: route('core.dashboard') },
        { label: 'Subscription',  href: route('core.subscription.index') },
        { label: 'Plans' },
      ]}
      description="Compare plans and upgrade at any time."
      actions={
        <Button
          intent="ghost"
          leftIcon="arrowLeft"
          onClick={() => router.get(route('core.subscription.index'))}
        >
          Back to Subscription
        </Button>
      }
      table={
        <VStack gap={4}>
          {cancelled && (
            <Alert
              intent="warning"
              title="Subscription cancellation scheduled. Your plan remains active until the end of the current billing period."
            />
          )}

          {(!plans || plans.length === 0) ? (
            <Text tone="secondary">No plans available.</Text>
          ) : (
            <HStack gap={4} align="start" wrap>
              {plans.map(plan => (
                <Box key={plan.id} grow>
                  <PlanCard
                    plan={plan}
                    isCurrent={plan.id === current_plan_id}
                    onUpgrade={handleUpgrade}
                    upgrading={upgradingId === plan.id}
                  />
                </Box>
              ))}
            </HStack>
          )}

          {/* Cancel link */}
          <HStack gap={2} justify="end">
            <Button
              intent="danger"
              size="sm"
              onClick={handleCancel}
              disabled={cancelled}
            >
              Cancel Subscription
            </Button>
          </HStack>
        </VStack>
      }
    />
  );
}

PlansIndex.layout = page => (
  <App title="Plans">{page}</App>
);
