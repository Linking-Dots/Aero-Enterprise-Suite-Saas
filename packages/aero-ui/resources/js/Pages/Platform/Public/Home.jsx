import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Section, Container, PublicSectionHeader, PublicFeatureCard,
  PublicStatCard, PublicTestimonialCard, Marquee,
  Card, VStack, HStack, Box, Text, Button, Field, Input,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import {
  FEATURES, STATS, TRUST_LOGOS, TESTIMONIALS, NARRATIVE_STEPS,
} from './data/pageData.js';

// ── Hero ──────────────────────────────────────────────────────────
function Hero() {
  return (
    <Section size="xl" className="aeos-pub-hero">
      <Container>
        <HStack gap={8} align="start">
          <VStack gap={5} className="aeos-pub-hero-copy">
            <p className="aeos-pub-label">Enterprise Resource Planning · SaaS</p>
            <h1 className="aeos-pub-h1">
              One platform.{' '}
              <span className="aeos-pub-gradient-text">Every module.</span>
            </h1>
            <p className="aeos-pub-lead">
              aeos365 unifies HR, payroll, finance, CRM, inventory, supply chain,
              projects, and 10+ more modules into one coherent multi-tenant platform.
              Built for organizations that demand real sovereignty over their data.
            </p>
            <HStack gap={3}>
              <a href="https://demo.aeos365.com" className="aeos-pub-btn-primary">
                Try the live demo →
              </a>
              <a href="/signup" className="aeos-pub-btn-ghost">
                Start free trial
              </a>
            </HStack>
            <p className="aeos-pub-label">
              No credit card · 14-day free trial · Isolated tenant database
            </p>
          </VStack>

          {/* KPI mockup card */}
          <Box grow>
            <Card>
              <VStack gap={4}>
                <p className="aeos-pub-label">LIVE PLATFORM KPIs</p>
                <HStack gap={3} wrap>
                  {STATS.map((s) => (
                    <PublicStatCard
                      key={s.label}
                      value={s.value}
                      suffix={s.suffix}
                      prefix={s.prefix}
                      label={s.label}
                      accent="cyan"
                    />
                  ))}
                </HStack>
              </VStack>
            </Card>
          </Box>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Trust bar ─────────────────────────────────────────────────────
function TrustBar() {
  return (
    <Section size="sm">
      <Container>
        <p className="aeos-pub-label aeos-text-center">
          Trusted by forward-thinking enterprises
        </p>
      </Container>
      <Marquee speed={30} pause>
        {TRUST_LOGOS.map((name) => (
          <Card key={name} className="aeos-pub-trust-chip">
            <Text tone="secondary">{name}</Text>
          </Card>
        ))}
      </Marquee>
    </Section>
  );
}

// ── Features bento ───────────────────────────────────────────────
function FeaturesSection() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="The Platform"
          title="17+ enterprise modules. One coherent system."
          lead="Every module is independently scoped yet shares a unified authentication and tenant context. Subscribe to what you need, scale into the rest."
          align="center"
        />
        <div className="aeos-pub-bento">
          {FEATURES.map((feat) => (
            <PublicFeatureCard
              key={feat.id}
              icon={feat.icon}
              title={feat.title}
              description={feat.description}
              stat={feat.stat}
              accent={feat.accent}
              className={feat.size === 'large' ? 'aeos-pub-bento-large' : ''}
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Narrative ────────────────────────────────────────────────────
function NarrativeSection() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="How it works"
          title="Designed for how modern enterprises actually operate."
          align="center"
        />
        <div className="aeos-pub-narrative-grid">
          {NARRATIVE_STEPS.map((step) => (
            <VStack key={step.tag} gap={3}>
              <p className="aeos-pub-label">{step.tag}</p>
              <h2 className="aeos-pub-h2">
                {step.title}{' '}
                <span className="aeos-pub-gradient-text">{step.highlight}</span>
              </h2>
              <p className="aeos-pub-lead">{step.body}</p>
            </VStack>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Stats ─────────────────────────────────────────────────────────
function StatsSection() {
  return (
    <Section size="md">
      <Container>
        <PublicSectionHeader
          eyebrow="By the numbers"
          title="Scale that speaks for itself."
          align="center"
        />
        <HStack gap={4} wrap align="center">
          {STATS.map((s) => (
            <PublicStatCard
              key={s.label}
              value={s.value}
              suffix={s.suffix}
              prefix={s.prefix}
              label={s.label}
              accent="cyan"
            />
          ))}
        </HStack>
      </Container>
    </Section>
  );
}

// ── CTA ───────────────────────────────────────────────────────────
function CTASection() {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  function handleSubmit(e) {
    e.preventDefault();
    if (!email) return;
    router.post('/waitlist', { email }, {
      preserveState: true,
      onSuccess: () => setSubmitted(true),
    });
  }

  return (
    <Section size="lg" bg="gradient">
      <Container>
        <Card>
          <VStack gap={5} align="center">
            <p className="aeos-pub-label">Get started today</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Ready to unify your enterprise?{' '}
              <span className="aeos-pub-gradient-text">Start in minutes.</span>
            </h2>
            <p className="aeos-pub-lead" style={{ textAlign: 'center', maxWidth: '560px' }}>
              Join 320+ enterprise clients who replaced their patchwork of tools with aeos365.
              14-day free trial, no credit card required.
            </p>
            {submitted ? (
              <Text tone="secondary">You are on the list — we will be in touch shortly.</Text>
            ) : (
              <form onSubmit={handleSubmit} noValidate>
                <HStack gap={2}>
                  <Field htmlFor="cta-email" label="">
                    <Input
                      id="cta-email"
                      type="email"
                      placeholder="Work email address"
                      leftIcon="mail"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                    />
                  </Field>
                  <Button intent="primary" type="submit" size="lg" rightIcon="arrowRight">
                    Start free trial
                  </Button>
                </HStack>
              </form>
            )}
            <HStack gap={4}>
              <a href="/pricing" className="aeos-pub-btn-ghost aeos-pub-btn-sm">
                View pricing
              </a>
              <a href="https://demo.aeos365.com" className="aeos-pub-btn-ghost aeos-pub-btn-sm">
                Live demo
              </a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Testimonials ─────────────────────────────────────────────────
function TestimonialsSection() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="Customer stories"
          title="Trusted by teams that demand real results."
          lead="From HR teams handling thousands of employees to CTOs rebuilding their operational stack — real outcomes from real customers."
          align="center"
        />
        <div className="aeos-pub-testimonials-grid">
          {TESTIMONIALS.map((t) => (
            <PublicTestimonialCard
              key={t.id}
              name={t.name}
              role={t.role}
              company={t.company}
              avatar={t.avatar}
              avatarBg={t.avatarColor}
              quote={t.quote}
              rating={t.rating}
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Home() {
  return (
    <>
      <Hero />
      <TrustBar />
      <FeaturesSection />
      <NarrativeSection />
      <StatsSection />
      <CTASection />
      <TestimonialsSection />
    </>
  );
}

Home.layout = (page) => (
  <PublicLayout title="Enterprise ERP Platform">{page}</PublicLayout>
);
