import { useState } from 'react';
import {
  Section, Container, PublicSectionHeader, PublicPricingCard,
  Accordion, Card, VStack, HStack, Text, Button, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import {
  PRICING_PLANS, COMPARISON_CATEGORIES, PRICING_FAQ,
} from './data/pageData.js';

// ── Pricing Hero ─────────────────────────────────────────────────
function PricingHero({ isAnnual, onToggle }) {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <PublicSectionHeader
          eyebrow="Pricing"
          title="Simple, transparent pricing."
          lead="Every plan starts with a 14-day free trial. No credit card required. Your data lives in an isolated database — regardless of plan."
          align="center"
        />
        {/* Billing toggle */}
        <HStack gap={3} align="center" style={{ justifyContent: 'center', marginTop: '1rem' }}>
          <Button
            intent={!isAnnual ? 'primary' : 'soft'}
            size="sm"
            onClick={() => onToggle(false)}
          >
            Monthly
          </Button>
          <Button
            intent={isAnnual ? 'primary' : 'soft'}
            size="sm"
            onClick={() => onToggle(true)}
          >
            Annual
            <Text tone="secondary" size="xs"> — save ~20%</Text>
          </Button>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Pricing Plans ─────────────────────────────────────────────────
function PricingPlansSection({ plans, isAnnual }) {
  return (
    <Section size="md">
      <Container>
        <div className="aeos-pub-pricing-grid">
          {plans.map((plan) => (
            <PublicPricingCard
              key={plan.id}
              name={plan.name}
              tagline={plan.tagline}
              monthlyPrice={plan.monthlyPrice}
              annualPrice={plan.annualPrice}
              isAnnual={isAnnual}
              badge={plan.badge}
              highlighted={plan.highlight}
              perks={plan.perks}
              cta={plan.cta}
              ctaHref={plan.id === 'enterprise' ? '/contact' : '/signup'}
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Comparison Table ──────────────────────────────────────────────
function ComparisonTable() {
  function renderCell(val) {
    if (val === true) {
      return (
        <Icon name="check" size={18} className="aeos-pub-accent-text--cyan" />
      );
    }
    if (val === false) {
      return <Text tone="tertiary">—</Text>;
    }
    return <Text>{val}</Text>;
  }

  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Compare plans"
          title="What's included in every plan."
          align="center"
        />
        <div className="aeos-overflow-x-auto">
          <table className="aeos-pub-comparison-table">
            <thead>
              <tr>
                <th style={{ width: '34%' }}>Feature</th>
                <th>Starter</th>
                <th>Professional</th>
                <th>Business</th>
                <th>Enterprise</th>
              </tr>
            </thead>
            <tbody>
              {COMPARISON_CATEGORIES.map((cat) => (
                <>
                  <tr key={`cat-${cat.name}`} className="aeos-pub-comparison-cat">
                    <td colSpan={5}>{cat.name}</td>
                  </tr>
                  {cat.rows.map((row) => (
                    <tr key={`${cat.name}-${row.feature}`}>
                      <td>{row.feature}</td>
                      <td>{renderCell(row.starter)}</td>
                      <td>{renderCell(row.professional)}</td>
                      <td>{renderCell(row.business)}</td>
                      <td>{renderCell(row.enterprise)}</td>
                    </tr>
                  ))}
                </>
              ))}
            </tbody>
          </table>
        </div>
      </Container>
    </Section>
  );
}

// ── FAQ ───────────────────────────────────────────────────────────
function PricingFAQ() {
  const items = PRICING_FAQ.map((item) => ({
    question: item.q,
    answer: item.a,
  }));

  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="FAQ"
          title="Common questions about pricing."
          align="center"
        />
        <VStack gap={0} className="aeos-content-extra-wide">
          <Accordion items={items} />
        </VStack>
      </Container>
    </Section>
  );
}

// ── Pricing CTA ───────────────────────────────────────────────────
function PricingCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Start today</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Not sure which plan is right?{' '}
              <span className="aeos-pub-gradient-text">Talk to us.</span>
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow">
              Our team can help you pick the right plan and walk you through the platform.
              No pressure, no sales scripts — just honest answers.
            </p>
            <HStack gap={3}>
              <a href="/contact" className="aeos-pub-btn-primary">
                Talk to sales →
              </a>
              <a href="https://demo.aeos365.com" className="aeos-pub-btn-ghost">
                Try live demo
              </a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Pricing({ plans = [] }) {
  const [isAnnual, setIsAnnual] = useState(false);
  const activePlans = plans.length > 0 ? plans : PRICING_PLANS;

  return (
    <>
      <PricingHero isAnnual={isAnnual} onToggle={setIsAnnual} />
      <PricingPlansSection plans={activePlans} isAnnual={isAnnual} />
      <ComparisonTable />
      <PricingFAQ />
      <PricingCTA />
    </>
  );
}

Pricing.layout = (page) => (
  <PublicLayout title="Pricing — Simple, Transparent Plans">{page}</PublicLayout>
);
