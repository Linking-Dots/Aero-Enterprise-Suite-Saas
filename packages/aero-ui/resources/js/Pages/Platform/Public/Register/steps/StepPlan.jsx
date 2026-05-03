import { useState, useMemo } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Box, Card, CardBody, Text, Eyebrow, Button, Badge, Divider, Flex1 } from '@aero/ui';
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
  const [expandedModule,  setExpandedModule]  = useState(null);
  const [submitting,      setSubmitting]      = useState(false);

  const selectedPlan = plans.find(p => p.id === selectedPlanId);

  // Filter out core module from display (it's auto-included and hidden)
  const displayModules = modules.filter(m => m.code !== 'core');

  function toggleModule(code) {
    setSelectedModules(prev => prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]);
  }

  function toggleExpand(code) {
    setExpandedModule(prev => prev === code ? null : code);
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

  function extractFeatures(text) {
    if (!text) return [];
    // Strip heading before "including" or ":" — keep only the feature list portion
    const body = text.replace(/^.+?\b(including|:)\s*/i, '');
    const parts = body.split(/[,;+]|\band\b/);
    return parts.map(s => s.trim().replace(/^[\s:–-]+|[\s:–-]+$/g, '')).filter(s => s.length > 2);
  }

  const planPrice = selectedPlan ? getPrice(selectedPlan) : 0;
  const suffix = billing === 'yearly' ? 'yr' : 'mo';

  const modulesPrice = selectedModules.reduce((sum, code) => {
    const mp = modulePricing[code];
    if (!mp) return sum;
    return sum + (billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0));
  }, 0);

  const total = planPrice + modulesPrice;

  const yearlySavings = useMemo(() => {
    if (!selectedPlan || billing !== 'monthly') return 0;
    const planMonthly = selectedPlan.monthly_price ?? 0;
    const planYearly = selectedPlan.yearly_price ?? planMonthly * 10;
    let savings = planMonthly * 12 - planYearly;
    selectedModules.forEach(code => {
      const mp = modulePricing[code];
      if (!mp) return;
      const mMonthly = mp.monthly ?? 0;
      const mYearly = mp.yearly ?? mMonthly * 10;
      savings += mMonthly * 12 - mYearly;
    });
    return savings;
  }, [selectedPlan, selectedModules, modulePricing, billing]);

  return (
    <div className="rl-plan-split">
      <div className="rl-plan-main">
        <Text as="p" tone="secondary">
          Pick a base plan, then add products. Pricing updates instantly.
        </Text>

        <HStack gap={2} align="center" style={{ marginTop: '1.5rem' }}>
          <Button type="button" intent={billing === 'monthly' ? 'primary' : 'soft'} size="sm" onClick={() => setBilling('monthly')}>Monthly</Button>
          <Button type="button" intent={billing === 'yearly' ? 'primary' : 'soft'} size="sm" onClick={() => setBilling('yearly')}>Yearly</Button>
          {billing === 'monthly' && (
            <Text tone="secondary" as="span" size="sm">Switch to yearly and save 2 months.</Text>
          )}
          {billing === 'yearly' && <Badge intent="success">2 months free</Badge>}
        </HStack>

        {plans.length === 0 && (
          <Text tone="secondary" style={{ marginTop: '1.5rem' }}>
            No subscription plans are configured yet. You can still select modules below to get started.
          </Text>
        )}

        <Eyebrow tone="primary" style={{ marginTop: '1.5rem' }}>Subscription Plans</Eyebrow>
        <div className="rl-plan-grid-b">
          {plans.map(plan => {
            const isSelected = plan.id === selectedPlanId;
            const price = getPrice(plan);
            return (
              <Card
                key={plan.id}
                as="button"
                interactive
                type="button"
                onClick={() => setSelectedPlanId(plan.id)}
                aria-pressed={isSelected}
                className={isSelected ? 'rl-card-selected' : ''}
                style={{ position: 'relative', textAlign: 'left' }}
              >
                {isSelected && (
                  <div className="rl-plan-badge"><Badge intent="success">Selected</Badge></div>
                )}
                {plan.popular && !isSelected && (
                  <div className="rl-plan-badge"><Badge intent="amber">Popular</Badge></div>
                )}
                <VStack gap={2} align="stretch">
                  <Text weight="semibold" size="lg" as="span">{plan.name}</Text>
                  {plan.description && <Text tone="secondary" as="span" size="sm">{plan.description}</Text>}
                  <HStack gap={1} align="baseline">
                    <Text as="span" className="rl-plan-price-amount">{formatPrice(price)}</Text>
                    <Text tone="tertiary" as="span" className="rl-plan-price-per">/{suffix}</Text>
                  </HStack>
                  {plan.features?.length > 0 && (
                    <VStack gap={1} align="stretch">
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

        {displayModules.length > 0 && (
          <>
            <Eyebrow tone="primary" style={{ marginTop: '1.5rem' }}>Add-on Products</Eyebrow>
            <VStack gap={3} align="stretch">
              {displayModules.map(mod => {
                const isChecked = selectedModules.includes(mod.code);
                const isExpanded = expandedModule === mod.code;
                const price = modulePricing[mod.code]?.[billing];
                return (
                  <Card
                    key={mod.code}
                    interactive
                    className={isChecked ? 'rl-card-selected' : ''}
                    style={{ position: 'relative', textAlign: 'left' }}
                  >
                    {/* Header row: checkbox, name, price, chevron */}
                    <HStack gap={3} align="center">
                      {/* Checkbox toggles selection only */}
                      <button
                        type="button"
                        className="rl-module-check"
                        onClick={(e) => { e.stopPropagation(); toggleModule(mod.code); }}
                        aria-pressed={isChecked}
                        aria-label={isChecked ? `Deselect ${mod.name}` : `Select ${mod.name}`}
                        style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', display: 'flex', alignItems: 'center' }}
                      >
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
                      </button>

                      {/* Clickable title area toggles expansion */}
                      <Box
                        grow
                        style={{ minWidth: 0, cursor: 'pointer' }}
                        onClick={() => toggleExpand(mod.code)}
                      >
                        <VStack gap={0} align="stretch">
                          <Text weight="semibold" as="span">{mod.name}</Text>
                          
                        </VStack>
                      </Box>

                      {price != null && (
                        <Text tone="secondary" as="span" size="sm">+{formatPrice(price)}/{suffix}</Text>
                      )}

                      {/* Chevron toggles expansion */}
                      <button
                        type="button"
                        onClick={(e) => { e.stopPropagation(); toggleExpand(mod.code); }}
                        aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
                        style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', color: 'var(--aeos-text-secondary)', display: 'flex', alignItems: 'center' }}
                      >
                        {isExpanded ? (
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" />
                          </svg>
                        ) : (
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" />
                          </svg>
                        )}
                      </button>
                    </HStack>

                    {/* Expandable body: description + features list */}
                    {isExpanded && (
                      <Box style={{ paddingTop: '1rem', borderTop: '1px solid var(--aeos-divider)', marginTop: '1rem' }}>
                        <VStack gap={2} align="stretch">
                          <Text weight="semibold" as="p" size="sm">Description</Text>
                          <Text tone="secondary" as="p" size="sm">{mod.description}</Text>
                          {(() => {
                            const feats = extractFeatures(mod.description);
                            if (feats.length === 0) return null;
                            return (
                              <>
                                <Text weight="semibold" as="p" size="sm" style={{ marginTop: '0.5rem' }}>features:</Text>
                                <VStack gap={1} align="stretch">
                                  {feats.map((feat, i) => (
                                    <HStack key={i} gap={2} align="flex-start">
                                      <Text as="span" size="sm">{i + 1}.</Text>
                                      <Text tone="secondary" as="span" size="sm">{feat}</Text>
                                    </HStack>
                                  ))}
                                </VStack>
                              </>
                            );
                          })()}
                        </VStack>
                      </Box>
                    )}
                  </Card>
                );
              })}
            </VStack>
          </>
        )}

        <HStack gap={3} align="center" style={{ marginTop: '2rem', paddingTop: '1.5rem', borderTop: '1px solid var(--aeos-divider)' }}>
          <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.verifyPhone)}>Back</Button>
        </HStack>
      </div>

      <div className="rl-plan-sidebar">
        <Card>
          <CardBody>
            <VStack gap={3} align="stretch">
              <Text weight="semibold" size="lg">Order Summary</Text>

              <HStack gap={3} align="center">
                <Text tone="secondary" as="span" size="sm">{selectedPlan?.name ?? 'No plan selected'}</Text>
                <Flex1 />
                <Text as="span" size="sm">{formatPrice(planPrice)}/{suffix}</Text>
              </HStack>

              {selectedModules.map(code => {
                const mod = displayModules.find(m => m.code === code);
                const mp = modulePricing[code];
                const mPrice = mp ? (billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0)) : 0;
                return (
                  <HStack gap={3} align="center" key={code}>
                    <Text tone="secondary" as="span" size="sm">{mod?.name ?? code}</Text>
                    <Flex1 />
                    <Text as="span" size="sm">+{formatPrice(mPrice)}/{suffix}</Text>
                  </HStack>
                );
              })}

              <Divider />

              <HStack gap={3} align="center">
                <Text weight="semibold" as="span">Total</Text>
                <Flex1 />
                <Text weight="bold" as="span" size="lg" style={{ color: 'var(--aeos-primary)' }}>{formatPrice(total)}/{suffix}</Text>
              </HStack>

              {billing === 'monthly' && yearlySavings > 0 && (
                <Text tone="success" size="sm" as="p" style={{ textAlign: 'center' }}>
                  Switch to yearly and save {formatPrice(yearlySavings)}
                </Text>
              )}

              <Button
                type="button"
                intent="primary"
                onClick={proceed}
                disabled={!selectedPlanId || selectedModules.length === 0 || submitting}
                loading={submitting}
              >
                Continue to Payment
              </Button>
            </VStack>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
