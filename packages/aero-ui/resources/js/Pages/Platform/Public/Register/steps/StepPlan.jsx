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

function ChevronRight() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
      strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M9 18l6-6-6-6" />
    </svg>
  );
}

function ChevronDown() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
      strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M6 9l6 6 6-6" />
    </svg>
  );
}

function CheckboxIcon({ checked }) {
  if (checked) {
    return (
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
        <rect width="18" height="18" rx="4" fill="var(--aeos-primary)" />
        <path d="M4 9l3 3 7-7" stroke="#0a0a0a" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    );
  }
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
      <rect x=".75" y=".75" width="16.5" height="16.5" rx="3.25" stroke="var(--aeos-divider)" strokeWidth="1.5" />
    </svg>
  );
}

export default function StepPlan({ plans = [], modules = [], modulePricing = {}, savedData = {} }) {
  const [billing,         setBilling]         = useState(savedData?.plan?.billing ?? 'monthly');
  const [selectedPlanId,  setSelectedPlanId]  = useState(savedData?.plan?.plan_id ?? null);
  const [selectedModules, setSelectedModules] = useState(savedData?.plan?.modules ?? []);
  const [expandedModule,  setExpandedModule]  = useState(null);
  const [submitting,      setSubmitting]      = useState(false);

  const selectedPlan   = plans.find(p => p.id === selectedPlanId);
  const displayModules = modules.filter(m => m.code !== 'core');

  function toggleModule(code) {
    setSelectedModules(prev =>
      prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]
    );
  }

  function toggleExpand(code) {
    setExpandedModule(prev => (prev === code ? null : code));
  }

  function proceed() {
    if (!selectedPlanId || selectedModules.length === 0 || submitting) return;
    setSubmitting(true);
    router.post(
      SR.storePlan,
      { plan_id: selectedPlanId, modules: selectedModules, billing_cycle: billing },
      { onFinish: () => setSubmitting(false) }
    );
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
    const body = text.replace(/^.+?\b(including|:)\s*/i, '');
    const parts = body.split(/[,;+]|\band\b/);
    return parts.map(s => s.trim().replace(/^[\s:–-]+|[\s:–-]+$/g, '')).filter(s => s.length > 2);
  }

  const planPrice = selectedPlan ? getPrice(selectedPlan) : 0;
  const suffix    = billing === 'yearly' ? 'yr' : 'mo';

  const modulesPrice = selectedModules.reduce((sum, code) => {
    const mp = modulePricing[code];
    if (!mp) return sum;
    return sum + (billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0));
  }, 0);

  const total = planPrice + modulesPrice;

  const yearlySavings = useMemo(() => {
    if (!selectedPlan || billing !== 'monthly') return 0;
    const pM = selectedPlan.monthly_price ?? 0;
    const pY = selectedPlan.yearly_price  ?? pM * 10;
    let savings = pM * 12 - pY;
    selectedModules.forEach(code => {
      const mp = modulePricing[code];
      if (!mp) return;
      savings += (mp.monthly ?? 0) * 12 - (mp.yearly ?? (mp.monthly ?? 0) * 10);
    });
    return savings;
  }, [selectedPlan, selectedModules, modulePricing, billing]);

  /* ── Shared order summary content (used in sidebar + mobile compact card) ── */
  function OrderSummaryContent() {
    return (
      <VStack gap={3} align="stretch">
        <Text weight="semibold" size="lg">Order Summary</Text>

        <HStack gap={3} align="center">
          <Text tone="secondary" as="span" size="sm">{selectedPlan?.name ?? 'No plan selected'}</Text>
          <Flex1 />
          <Text as="span" size="sm">{formatPrice(planPrice)}/{suffix}</Text>
        </HStack>

        {selectedModules.length > 0 && (
          <VStack gap={2} align="stretch">
            {selectedModules.map(code => {
              const mod   = displayModules.find(m => m.code === code);
              const mp    = modulePricing[code];
              const mPrice = mp
                ? (billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0))
                : 0;
              return (
                <HStack gap={3} align="center" key={code}>
                  <Text tone="secondary" as="span" size="sm">{mod?.name ?? code}</Text>
                  <Flex1 />
                  <Text as="span" size="sm">+{formatPrice(mPrice)}/{suffix}</Text>
                </HStack>
              );
            })}
          </VStack>
        )}

        <Divider />

        <HStack gap={3} align="center">
          <Text weight="semibold" as="span">Total</Text>
          <Flex1 />
          <Text weight="bold" as="span" size="lg" style={{ color: 'var(--aeos-primary)' }}>
            {formatPrice(total)}/{suffix}
          </Text>
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
    );
  }

  return (
    <div className="rl-plan-split">

      {/* ── LEFT: plan picker + modules ── */}
      <div className="rl-plan-main">

        {/* Intro */}
        <Text as="p" tone="secondary">
          Pick a base plan, then add products. Pricing updates instantly.
        </Text>

        {/* Billing toggle */}
        <HStack gap={2} align="center" className="rl-plan-billing">
          <Button
            type="button"
            intent={billing === 'monthly' ? 'primary' : 'soft'}
            size="sm"
            onClick={() => setBilling('monthly')}
          >
            Monthly
          </Button>
          <Button
            type="button"
            intent={billing === 'yearly' ? 'primary' : 'soft'}
            size="sm"
            onClick={() => setBilling('yearly')}
          >
            Yearly
          </Button>
          {billing === 'monthly' && (
            <Text tone="secondary" as="span" size="sm">Switch to yearly — save 2 months.</Text>
          )}
          {billing === 'yearly' && <Badge intent="success">2 months free</Badge>}
        </HStack>

        {/* No plans notice */}
        {plans.length === 0 && (
          <Text tone="secondary" className="aeos-mt-lg">
            No subscription plans are configured yet. You can still select modules below.
          </Text>
        )}

        {/* Plans grid */}
        {plans.length > 0 && (
          <>
            <Eyebrow tone="primary" className="rl-plan-eyebrow">Subscription Plans</Eyebrow>
            <div className="rl-plan-grid-b" style={{ marginTop: '.75rem' }}>
              {plans.map(plan => {
                const isSelected = plan.id === selectedPlanId;
                const price      = getPrice(plan);
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
                    {/* Badges */}
                    <div style={{ position: 'absolute', top: 10, right: 10, display: 'flex', gap: 4 }}>
                      {isSelected && <Badge intent="success">Selected</Badge>}
                      {plan.popular && !isSelected && <Badge intent="amber">Popular</Badge>}
                    </div>

                    <VStack gap={2} align="stretch">
                      <Text weight="semibold" size="lg" as="span">{plan.name}</Text>

                      {plan.description && (
                        <Text tone="secondary" as="span" size="sm">{plan.description}</Text>
                      )}

                      <HStack gap={1} align="baseline">
                        <Text as="span" className="rl-plan-price-amount">{formatPrice(price)}</Text>
                        <Text tone="tertiary" as="span" className="rl-plan-price-per">/{suffix}</Text>
                      </HStack>

                      {/* All features — fully visible */}
                      {plan.features?.length > 0 && (
                        <VStack gap={1} align="stretch" style={{ marginTop: '.25rem' }}>
                          {plan.features.map((feat, i) => (
                            <HStack key={i} gap={2} align="flex-start">
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
          </>
        )}

        {/* Modules list */}
        {displayModules.length > 0 && (
          <>
            <Eyebrow tone="primary" className="rl-plan-eyebrow">
              Add-on Products
            </Eyebrow>
            <VStack gap={3} align="stretch" style={{ marginTop: '.75rem' }}>
              {displayModules.map(mod => {
                const isChecked  = selectedModules.includes(mod.code);
                const isExpanded = expandedModule === mod.code;
                const mp         = modulePricing[mod.code];
                const price      = mp ? (billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0)) : null;
                const feats      = extractFeatures(mod.description ?? '');

                return (
                  <Card
                    key={mod.code}
                    className={isChecked ? 'rl-card-selected' : ''}
                    style={{ position: 'relative' }}
                  >
                    {/* Header row */}
                    <HStack gap={3} align="center">
                      {/* Checkbox */}
                      <button
                        type="button"
                        onClick={e => { e.stopPropagation(); toggleModule(mod.code); }}
                        aria-pressed={isChecked}
                        aria-label={isChecked ? `Deselect ${mod.name}` : `Select ${mod.name}`}
                        style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', display: 'flex', alignItems: 'center', flexShrink: 0 }}
                      >
                        <CheckboxIcon checked={isChecked} />
                      </button>

                      {/* Name + expand */}
                      <Box
                        grow
                        style={{ minWidth: 0, cursor: 'pointer' }}
                        onClick={() => toggleExpand(mod.code)}
                      >
                        <VStack gap={0} align="stretch">
                          <Text weight="semibold" as="span">{mod.name}</Text>
                          {/* Short description always visible */}
                          {mod.description && !isExpanded && (
                            <Text tone="secondary" as="span" size="sm" style={{
                              overflow: 'hidden',
                              display: '-webkit-box',
                              WebkitLineClamp: 2,
                              WebkitBoxOrient: 'vertical',
                            }}>
                              {mod.description}
                            </Text>
                          )}
                        </VStack>
                      </Box>

                      {/* Price */}
                      {price != null && (
                        <Text tone="secondary" as="span" size="sm" style={{ flexShrink: 0 }}>
                          +{formatPrice(price)}/{suffix}
                        </Text>
                      )}

                      {/* Expand chevron */}
                      <button
                        type="button"
                        onClick={e => { e.stopPropagation(); toggleExpand(mod.code); }}
                        aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
                        aria-expanded={isExpanded}
                        style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', color: 'var(--aeos-text-secondary)', display: 'flex', alignItems: 'center', flexShrink: 0 }}
                      >
                        {isExpanded ? <ChevronDown /> : <ChevronRight />}
                      </button>
                    </HStack>

                    {/* Expandable body — full description + features */}
                    {isExpanded && (
                      <Box style={{ paddingTop: '1rem', borderTop: '1px solid var(--aeos-divider)', marginTop: '1rem' }}>
                        <VStack gap={3} align="stretch">
                          {/* Full description */}
                          <Text tone="secondary" as="p" size="sm">{mod.description}</Text>

                          {/* Feature list extracted from description */}
                          {feats.length > 0 && (
                            <VStack gap={1} align="stretch">
                              <Text weight="semibold" as="p" size="sm">Includes:</Text>
                              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: '4px 12px' }}>
                                {feats.map((feat, i) => (
                                  <HStack key={i} gap={2} align="flex-start">
                                    <Text as="span" tone="success" style={{ flexShrink: 0 }}><CheckIcon /></Text>
                                    <Text tone="secondary" as="span" size="sm">{feat}</Text>
                                  </HStack>
                                ))}
                              </div>
                            </VStack>
                          )}

                          {/* Pricing breakdown */}
                          {mp && (
                            <HStack gap={4} align="center" style={{ paddingTop: '.25rem', borderTop: '1px solid var(--aeos-divider)' }}>
                              {mp.monthly != null && (
                                <VStack gap={0}>
                                  <Text tone="tertiary" as="span" size="sm">Monthly</Text>
                                  <Text weight="semibold" as="span">{formatPrice(mp.monthly)}/mo</Text>
                                </VStack>
                              )}
                              {mp.yearly != null && (
                                <VStack gap={0}>
                                  <Text tone="tertiary" as="span" size="sm">Yearly</Text>
                                  <Text weight="semibold" as="span">{formatPrice(mp.yearly)}/yr</Text>
                                  {mp.monthly != null && (
                                    <Text tone="success" as="span" size="sm">
                                      Save {formatPrice(mp.monthly * 12 - mp.yearly)}
                                    </Text>
                                  )}
                                </VStack>
                              )}
                              <Flex1 />
                              <Button
                                type="button"
                                intent={isChecked ? 'soft' : 'primary'}
                                size="sm"
                                onClick={e => { e.stopPropagation(); toggleModule(mod.code); }}
                              >
                                {isChecked ? 'Remove' : 'Add to plan'}
                              </Button>
                            </HStack>
                          )}
                        </VStack>
                      </Box>
                    )}
                  </Card>
                );
              })}
            </VStack>
          </>
        )}

        {/* Mobile compact summary card (shown below modules, < 768px) */}
        <div className="rl-plan-sidebar-mobile" style={{ marginTop: '1.5rem', display: 'none' }}>
          <Card>
            <CardBody>
              <OrderSummaryContent />
            </CardBody>
          </Card>
        </div>

        {/* Mobile sticky bottom bar */}
        <div className="rl-plan-sticky-bar" style={{ display: 'none' }}>
          <div className="rl-plan-sticky-total">
            <span className="rl-plan-sticky-total-label">Total</span>
            <span className="rl-plan-sticky-total-amount">{formatPrice(total)}/{suffix}</span>
            {selectedPlan && (
              <span className="rl-plan-sticky-total-breakdown">
                {selectedPlan.name}
                {selectedModules.length > 0 && ` + ${selectedModules.length} add-on${selectedModules.length > 1 ? 's' : ''}`}
              </span>
            )}
          </div>
          <div className="rl-plan-sticky-cta">
            <Button
              type="button"
              intent="primary"
              fullWidth
              onClick={proceed}
              disabled={!selectedPlanId || selectedModules.length === 0 || submitting}
              loading={submitting}
            >
              Continue →
            </Button>
          </div>
        </div>

        {/* Back button */}
        <HStack gap={3} align="center" style={{ marginTop: '2rem', paddingTop: '1.5rem', borderTop: '1px solid var(--aeos-divider)' }}>
          <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.verifyPhone)}>
            Back
          </Button>
        </HStack>
      </div>

      {/* ── RIGHT: order summary sidebar (desktop only) ── */}
      <div className="rl-plan-sidebar rl-plan-sidebar-full" style={{ display: 'none' }}>
        <Card>
          <CardBody>
            <OrderSummaryContent />
          </CardBody>
        </Card>
      </div>

    </div>
  );
}
