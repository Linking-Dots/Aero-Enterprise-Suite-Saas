import { useState } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Box, Card, Text, Eyebrow, Button, Badge } from '@aero/ui';
import { SR } from '../signupRoutes.js';

function CheckIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
      <path d="M2.5 7l3 3 6-6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export default function StepPlan({ plans = [], modules = [], modulePricing = {}, savedData = {} }) {
  const [billing,         setBilling]         = useState(savedData?.plan?.billing ?? 'monthly');
  const [selectedPlanId,  setSelectedPlanId]  = useState(savedData?.plan?.plan_id ?? null);
  const [selectedModules, setSelectedModules] = useState(savedData?.plan?.modules ?? []);
  const [submitting,      setSubmitting]      = useState(false);

  const selectedPlan = plans.find(p => p.id === selectedPlanId);

  // Filter out core module from display (it's auto-included and hidden)
  const displayModules = modules.filter(m => m.code !== 'core');

  // Auto-include core module in selection (hidden from UI)
  const modulesWithCore = ['core', ...selectedModules.filter(m => m !== 'core')];

  function toggleModule(code) {
    setSelectedModules(prev => prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]);
  }

  function proceed() {
    // Both plan and at least one product (excluding core) are required
    if (!selectedPlanId || selectedModules.length === 0 || submitting) return;
    setSubmitting(true);
    router.post(SR.storePlan, { plan_id: selectedPlanId, modules: selectedModules, billing_cycle: billing }, { onFinish: () => setSubmitting(false) });
  }

  function getPrice(plan) {
    return billing === 'yearly'
      ? (plan.yearly_price ?? plan.monthly_price * 10)
      : (plan.monthly_price ?? 0);
  }

  function formatPrice(value) {
    if (value == null) return '$0';
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
  }

  return (
    <VStack gap={5}>
      {/* Billing toggle */}
      <HStack gap={2} align="center">
        <Button type="button" intent={billing === 'monthly' ? 'primary' : 'soft'} size="sm" onClick={() => setBilling('monthly')}>
          Monthly
        </Button>
        <Button type="button" intent={billing === 'yearly' ? 'primary' : 'soft'} size="sm" onClick={() => setBilling('yearly')}>
          Yearly
        </Button>
        {billing === 'monthly' && (
          <Text tone="secondary" as="span">Switch to yearly and save 2 months.</Text>
        )}
        {billing === 'yearly' && <Badge intent="success">2 months free</Badge>}
      </HStack>

      {/* Plan cards grid */}
      {plans.length === 0 && (
        <Text tone="secondary">No subscription plans are configured yet. You can still select modules below to get started.</Text>
      )}
      <div className="rl-plan-grid">
        {plans.map(plan => {
          const isSelected = plan.id === selectedPlanId;
          const price      = getPrice(plan);
          return (
            <Card
              key={plan.id}
              as="button"
              interactive
              type="button"
              onClick={() => {
                setSelectedPlanId(plan.id);
                // Modules are now independent of plan selection
              }}
              aria-pressed={isSelected}
              className={isSelected ? 'rl-card-selected' : ''}
            >
              {/* Badge: Selected or Popular */}
              {isSelected && (
                <div className="rl-plan-badge"><Badge intent="success">Selected</Badge></div>
              )}
              {plan.popular && !isSelected && (
                <div className="rl-plan-badge"><Badge intent="amber">Popular</Badge></div>
              )}

              <VStack gap={2}>
                <Text weight="semibold" size="lg" as="span">{plan.name}</Text>
                {plan.description && <Text tone="secondary" as="span" size="sm">{plan.description}</Text>}

                {/* Price */}
                <HStack gap={1} align="baseline">
                  <Text as="span" className="rl-plan-price-amount">{formatPrice(price)}</Text>
                  <Text tone="tertiary" as="span" className="rl-plan-price-per">/{billing === 'yearly' ? 'yr' : 'mo'}</Text>
                </HStack>

                {/* Feature list */}
                {plan.features?.length > 0 && (
                  <VStack gap={1}>
                    {plan.features.map((feat, i) => (
                      <HStack key={i} gap={2} align="center">
                        <Text as="span" tone="success"><CheckIcon /></Text>
                        <Text tone="secondary" as="span" size="sm">{feat}</Text>
                      </HStack>
                    ))}
                  </VStack>
                )}
              </VStack>
            </Card>
          );
        })}
      </div>

      {/* Module selection */}
      {displayModules.length > 0 && (
        <VStack gap={3}>
          <Eyebrow tone="primary">Select Products</Eyebrow>
          <VStack gap={2} className="rl-module-grid">
            {displayModules.map(mod => {
              const isChecked = selectedModules.includes(mod.code);
              const price     = modulePricing[mod.code]?.[billing];
              return (
                <Card
                  key={mod.code}
                  as="button"
                  interactive
                  type="button"
                  onClick={() => toggleModule(mod.code)}
                  aria-pressed={isChecked}
                  className={isChecked ? 'rl-card-selected' : ''}
                >
                  <HStack gap={3} align="center">
                    <Box className="rl-module-check">
                      {isChecked ? (
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                          <rect width="18" height="18" rx="4" fill="var(--aeos-primary)" />
                          <path d="M4 9l3 3 7-7" stroke="#0a0a0a" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      ) : (
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                          <rect x=".75" y=".75" width="16.5" height="16.5" rx="3.25" stroke="var(--aeos-divider)" strokeWidth="1.5" />
                        </svg>
                      )}
                    </Box>
                    <Box grow>
                      <VStack gap={0}>
                        <Text as="span">{mod.name}</Text>
                        {mod.description && <Text tone="tertiary" as="span" size="sm">{mod.description}</Text>}
                      </VStack>
                    </Box>
                    {price != null && (
                      <Text tone="secondary" as="span" size="sm">+{formatPrice(price)}/{billing === 'yearly' ? 'yr' : 'mo'}</Text>
                    )}
                  </HStack>
                </Card>
              );
            })}
          </VStack>
        </VStack>
      )}

      {/* Navigation */}
      <div className="rl-nav">
        <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.verifyPhone)}>
          Back
        </Button>
        <Button type="button" intent="primary" rightIcon="arrowRight" loading={submitting} disabled={!selectedPlanId || selectedModules.length === 0} onClick={proceed}>
          Continue
        </Button>
      </div>
    </VStack>
  );
}
