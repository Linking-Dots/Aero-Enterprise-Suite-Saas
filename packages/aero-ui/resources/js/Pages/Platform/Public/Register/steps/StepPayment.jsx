import { useState } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Box, Card, CardBody, Text, Mono, Eyebrow, Button, Alert, Divider, Badge } from '@aero/ui';
import { SR } from '../signupRoutes.js';

function CheckIcon() {
  return (
    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
      <path d="M2 6.5l3 3 6-6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function ReviewRow({ label, value, mono = false, accent = false, children }) {
  return (
    <HStack gap={3} align="flex-start" style={{ paddingTop: '.5rem', paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
      <Text tone="secondary" as="span" style={{ minWidth: '9rem', flexShrink: 0 }}>{label}</Text>
      <Box grow>
        {children ?? (
          mono
            ? <Mono size="sm" style={accent ? { color: 'var(--aeos-primary)' } : {}}>{value ?? '—'}</Mono>
            : <Text as="span" weight="medium" style={accent ? { color: 'var(--aeos-primary)' } : {}}>{value ?? '—'}</Text>
        )}
      </Box>
    </HStack>
  );
}

export default function StepPayment({
  trialDays     = 14,
  baseDomain    = '',
  plans         = [],
  modules       = [],
  modulePricing = {},
  savedData     = {},
}) {
  const [submitting, setSubmitting] = useState(false);

  const planData   = savedData?.plan    ?? {};
  const details    = savedData?.details ?? {};
  const account    = savedData?.account ?? {};
  const byoc       = savedData?.byoc    ?? {};

  const companyName  = details.name      ?? '';
  const email        = details.email     ?? '';
  const phone        = details.phone     ?? '';
  const subdomain    = details.subdomain ?? '';
  const billing      = planData.billing  ?? 'monthly';
  const selectedMods = planData.modules  ?? [];

  const selectedPlan          = plans.find(p => p.id === planData.plan_id);
  const selectedModuleObjects = modules.filter(m => selectedMods.includes(m.code));

  function getPrice(plan) {
    if (!plan) return 0;
    return billing === 'yearly'
      ? (plan.yearly_price ?? plan.monthly_price * 10)
      : (plan.monthly_price ?? 0);
  }

  function getModPrice(code) {
    const mp = modulePricing[code];
    if (!mp) return 0;
    return billing === 'yearly' ? (mp.yearly ?? mp.monthly * 10) : (mp.monthly ?? 0);
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

  const planPrice   = getPrice(selectedPlan);
  const modTotal    = selectedMods.reduce((s, c) => s + getModPrice(c), 0);
  const total       = planPrice + modTotal;
  const suffix      = billing === 'yearly' ? 'yr' : 'mo';
  const trialEnd    = formatDate(trialDays);

  function activate() {
    if (submitting) return;
    setSubmitting(true);
    const t = setTimeout(() => setSubmitting(false), 15000);
    router.post(SR.activateTrial, { accept_terms: true }, {
      onFinish: () => { clearTimeout(t); setSubmitting(false); },
      onError:  () => { clearTimeout(t); setSubmitting(false); },
    });
  }

  return (
    <VStack gap={5}>

      {/* Trial notice */}
      <Alert intent="info">
        <HStack gap={2} align="flex-start">
          <Text tone="success" style={{ flexShrink: 0 }}><CheckIcon /></Text>
          <Text>
            <strong>{trialDays}-day free trial.</strong>{' '}
            You won&apos;t be charged until your trial ends on {trialEnd}.
            Cancel any time.
          </Text>
        </HStack>
      </Alert>

      {/* ── Workspace details ── */}
      <Card>
        <CardBody>
          <VStack gap={0} align="stretch">
            <Eyebrow tone="primary" style={{ marginBottom: '.75rem' }}>Workspace</Eyebrow>

            <ReviewRow label="Account type">
              <Badge intent="neutral" style={{ textTransform: 'capitalize' }}>
                {account.type ?? 'Company'}
              </Badge>
            </ReviewRow>
            <ReviewRow label="Company name" value={companyName} />
            <ReviewRow label="Work email"   value={email} />
            {phone && <ReviewRow label="Phone" value={phone} />}
            <ReviewRow label="Workspace URL" mono>
              {subdomain
                ? <Mono size="sm" style={{ color: 'var(--aeos-primary)', wordBreak: 'break-all' }}>{subdomain}.{baseDomain}</Mono>
                : <Text tone="tertiary" as="span">—</Text>}
            </ReviewRow>

            {/* BYOC if enabled */}
            {byoc?.enabled && (
              <ReviewRow label="Database">
                <HStack gap={2} align="center">
                  <Badge intent="amber">BYOC</Badge>
                  <Text tone="secondary" as="span" size="sm">
                    {byoc.db_driver?.toUpperCase()} @ {byoc.db_host}
                  </Text>
                </HStack>
              </ReviewRow>
            )}
          </VStack>
        </CardBody>
      </Card>

      {/* ── Plan & add-ons ── */}
      <Card>
        <CardBody>
          <VStack gap={0} align="stretch">
            <Eyebrow tone="primary" style={{ marginBottom: '.75rem' }}>Plan & Add-ons</Eyebrow>

            <ReviewRow label="Plan" value={selectedPlan?.name ?? '—'} />
            <ReviewRow label="Billing cycle">
              <HStack gap={2} align="center">
                <Text as="span" weight="medium" style={{ textTransform: 'capitalize' }}>{billing}</Text>
                {billing === 'yearly' && <Badge intent="success">2 months free</Badge>}
              </HStack>
            </ReviewRow>
            <ReviewRow label="Plan price" value={selectedPlan ? `${formatPrice(planPrice)}/${suffix}` : '—'} accent />

            {/* Plan features — all visible */}
            {selectedPlan?.features?.length > 0 && (
              <div style={{ paddingTop: '.75rem', paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
                <Text tone="secondary" size="sm" style={{ marginBottom: '.5rem' }}>Plan includes:</Text>
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
                  gap: '4px 12px',
                }}>
                  {selectedPlan.features.map((f, i) => (
                    <HStack key={i} gap={2} align="flex-start">
                      <Text as="span" tone="success" style={{ flexShrink: 0 }}><CheckIcon /></Text>
                      <Text tone="secondary" as="span" size="sm">{f}</Text>
                    </HStack>
                  ))}
                </div>
              </div>
            )}

            {/* Add-ons — each one with its price */}
            {selectedModuleObjects.length > 0 && (
              <>
                <div style={{ paddingTop: '.75rem', marginBottom: '.25rem' }}>
                  <Eyebrow tone="secondary">Add-ons included</Eyebrow>
                </div>
                {selectedModuleObjects.map(mod => (
                  <ReviewRow key={mod.code} label={mod.name}>
                    <HStack gap={2} align="center">
                      <Text as="span" weight="medium">+{formatPrice(getModPrice(mod.code))}/{suffix}</Text>
                      {mod.description && (
                        <Text tone="tertiary" as="span" size="sm">· {mod.description.slice(0, 60)}{mod.description.length > 60 ? '…' : ''}</Text>
                      )}
                    </HStack>
                  </ReviewRow>
                ))}
              </>
            )}

            {/* Total row */}
            <Divider style={{ margin: '.75rem 0' }} />
            <HStack gap={3} align="center">
              <Text weight="semibold" as="span">Total after trial</Text>
              <Box grow />
              <Text
                weight="bold"
                as="span"
                size="xl"
                style={{ color: 'var(--aeos-primary)', fontFamily: 'var(--aeos-font-display)', letterSpacing: '-.02em' }}
              >
                {formatPrice(total)}/{suffix}
              </Text>
            </HStack>

            {/* Yearly savings callout */}
            {billing === 'monthly' && selectedPlan?.yearly_price && (
              <Text tone="secondary" size="sm" as="p" style={{ marginTop: '.5rem', textAlign: 'center' }}>
                Switch to yearly billing and save{' '}
                <strong style={{ color: 'var(--aeos-success)' }}>
                  {formatPrice(selectedPlan.monthly_price * 12 - selectedPlan.yearly_price)}
                </strong>.
              </Text>
            )}
          </VStack>
        </CardBody>
      </Card>

      {/* ── Trial timeline ── */}
      <Card>
        <CardBody>
          <VStack gap={0} align="stretch">
            <Eyebrow tone="primary" style={{ marginBottom: '.75rem' }}>Trial timeline</Eyebrow>
            <ReviewRow label="Trial starts"  value="Today" accent />
            <ReviewRow label="Trial ends"    value={trialEnd} />
            <ReviewRow label="First charge"  value={`${formatPrice(total)}/${suffix} on ${trialEnd}`} />
            <ReviewRow label="Cancellation"  value="Cancel any time before trial ends — no charge" />
          </VStack>
        </CardBody>
      </Card>

      {/* ── CTA ── */}
      <Button
        type="button"
        intent="primary"
        fullWidth
        size="lg"
        loading={submitting}
        rightIcon="arrowRight"
        onClick={activate}
      >
        Start Free Trial →
      </Button>

      <Text tone="tertiary" size="sm" as="p" style={{ textAlign: 'center' }}>
        By clicking Start Free Trial you agree to our{' '}
        <a href="/legal/terms" style={{ color: 'var(--aeos-text-secondary)', textDecoration: 'underline' }}>Terms of Service</a>
        {' '}and{' '}
        <a href="/legal/privacy" style={{ color: 'var(--aeos-text-secondary)', textDecoration: 'underline' }}>Privacy Policy</a>.
      </Text>

      <div className="rl-nav">
        <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.plan)}>
          Back to plans
        </Button>
      </div>
    </VStack>
  );
}
