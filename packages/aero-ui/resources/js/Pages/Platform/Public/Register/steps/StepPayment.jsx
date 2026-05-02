import { useState } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Box, Card, Text, Mono, Eyebrow, Button, Alert } from '@aero/ui';
import { SR } from '../signupRoutes.js';

export default function StepPayment({
  trialDays    = 14,
  baseDomain   = '',
  plans        = [],
  modules      = [],
  modulePricing = {},
  savedData    = {},
}) {
  const [submitting, setSubmitting] = useState(false);

  const planData   = savedData?.plan    ?? {};
  const details    = savedData?.details ?? {};

  const companyName  = details.name      ?? '';
  const email        = details.email     ?? '';
  const subdomain    = details.subdomain ?? '';
  const billing      = planData.billing  ?? 'monthly';
  const selectedMods = planData.modules  ?? [];

  const selectedPlan          = plans.find(p => p.id === planData.plan_id);
  const selectedModuleObjects = modules.filter(m => selectedMods.includes(m.code));

  function getPrice(plan) {
    if (!plan) return 0;
    return billing === 'yearly' ? (plan.yearly_price ?? plan.monthly_price * 10) : (plan.monthly_price ?? 0);
  }

  function formatPrice(value) {
    if (value == null) return '$0';
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
  }

  function formatDate(daysFromNow) {
    const d = new Date();
    d.setDate(d.getDate() + daysFromNow);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function activate() {
    if (submitting) return;
    setSubmitting(true);
    const timer = setTimeout(() => setSubmitting(false), 15000);
    router.post(SR.activateTrial, { accept_terms: true }, {
      onFinish: () => { clearTimeout(timer); setSubmitting(false); },
      onError: () => { clearTimeout(timer); setSubmitting(false); },
    });
  }

  const planPrice    = getPrice(selectedPlan);
  const moduleTotal  = selectedMods.reduce((sum, code) => sum + (modulePricing[code]?.[billing] ?? 0), 0);
  const totalMonthly = planPrice + moduleTotal;
  const trialEndDate = formatDate(trialDays);

  function SummaryRow({ label, value, highlight }) {
    return (
      <HStack gap={3} align="center">
        <Text tone="secondary" as="span">{label}</Text>
        <Box grow />
        <Text as="span" className={highlight ? 'aeos-text-primary' : ''}>{value}</Text>
      </HStack>
    );
  }

  return (
    <VStack gap={5}>
      <Alert intent="info" title="No credit card required">
        Your {trialDays}-day free trial starts today. You won&apos;t be charged until your trial ends.
      </Alert>

      <Card>
        <VStack gap={3}>
          {/* Workspace section */}
          <Eyebrow tone="primary">Workspace</Eyebrow>
          <SummaryRow label="Company" value={companyName || '—'} />
          <SummaryRow label="Email"   value={email       || '—'} />
          <HStack gap={3} align="center">
            <Text tone="secondary" as="span">URL</Text>
            <Box grow />
            <Mono size="sm">{subdomain ? `${subdomain}.${baseDomain}` : '—'}</Mono>
          </HStack>

          {/* Plan section */}
          <Eyebrow tone="primary">Plan</Eyebrow>
          <SummaryRow label="Plan"    value={selectedPlan?.name ?? '—'} />
          <SummaryRow label="Billing" value={billing === 'yearly' ? 'Yearly' : 'Monthly'} />
          <SummaryRow
            label="Plan price"
            value={selectedPlan ? `${formatPrice(planPrice)}/${billing === 'yearly' ? 'yr' : 'mo'}` : '—'}
          />

          {/* Add-ons section */}
          {selectedModuleObjects.length > 0 && (
            <>
              <Eyebrow tone="primary">Add-ons</Eyebrow>
              {selectedModuleObjects.map(mod => (
                <SummaryRow
                  key={mod.code}
                  label={mod.name}
                  value={modulePricing[mod.code]?.[billing] != null ? `+${formatPrice(modulePricing[mod.code][billing])}/${billing === 'yearly' ? 'yr' : 'mo'}` : 'Included'}
                />
              ))}
            </>
          )}

          {/* Trial section */}
          <Eyebrow tone="primary">Trial</Eyebrow>
          <SummaryRow label="Free trial" value={`${trialDays} days`} highlight />
          <SummaryRow label="Trial ends" value={trialEndDate} />
          <SummaryRow
            label="After trial"
            value={selectedPlan ? `${formatPrice(totalMonthly)}/mo` : '—'}
          />
        </VStack>
      </Card>

      <Button type="button" intent="primary" fullWidth size="lg" loading={submitting} rightIcon="arrowRight" onClick={activate}>
        Start Free Trial
      </Button>

      <div className="rl-nav">
        <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.plan)}>
          Back to plans
        </Button>
      </div>
    </VStack>
  );
}
