import {
  Section, Container, PublicSectionHeader, PublicStatCard,
  Card, VStack, HStack, Box, Text, Eyebrow,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const ABOUT_STATS = [
  { value: 2019, suffix: '', label: 'Founded' },
  { value: 40,   suffix: '+', label: 'Modules' },
  { value: 120,  suffix: '+', label: 'Countries served' },
  { value: 98,   suffix: '%', label: 'Customer retention' },
];

const VALUES = [
  {
    badge: '01',
    title: 'Integrity in everything',
    body: 'We say what we mean and ship what we promise. No vaporware, no bait-and-switch features, no hidden costs buried in the fine print.',
    accent: 'cyan',
  },
  {
    badge: '02',
    title: 'Radical transparency',
    body: 'Our roadmap is public. Our pricing is plain English. Our uptime data is live. Customers deserve to make informed decisions.',
    accent: 'indigo',
  },
  {
    badge: '03',
    title: 'User-first design',
    body: 'Every interface decision is measured against a simple test: does this make the person doing the actual work faster and less stressed?',
    accent: 'amber',
  },
  {
    badge: '04',
    title: 'Continuous innovation',
    body: 'We ship meaningful improvements every sprint, informed by direct customer collaboration and emerging technology research.',
    accent: 'cyan',
  },
  {
    badge: '05',
    title: 'Customer obsession',
    body: 'Retention, not acquisition, is our north star. We win when customers grow their businesses using our platform — not when they sign a contract.',
    accent: 'indigo',
  },
  {
    badge: '06',
    title: 'Collective ownership',
    body: 'Every team member owns outcomes, not just tasks. Collaboration across engineering, design, and customer success is how great products ship.',
    accent: 'amber',
  },
];

const MILESTONES = [
  { year: '2019', quarter: 'Q3', title: 'Company founded', body: 'aeos365 is established with a charter to rebuild enterprise operations software from first principles — modular, multi-tenant, and genuinely usable.', accent: 'cyan' },
  { year: '2020', quarter: 'Q1', title: 'Core HR & Payroll live', body: 'The HRM module exits private beta with 12 design-partner customers. Employee lifecycle, leave management, and payroll automation ship as a unified suite.', accent: 'indigo' },
  { year: '2020', quarter: 'Q4', title: 'Finance & CRM modules', body: 'Accounts payable/receivable, budgeting, and a full CRM pipeline ship in a single release, giving early adopters a true cross-functional platform.', accent: 'amber' },
  { year: '2021', quarter: 'Q2', title: 'Multi-tenant SaaS launch', body: 'Full subdomain-based tenant isolation, subscription billing, and the platform admin console launch — making aeos365 a true SaaS product available to the public.', accent: 'cyan' },
  { year: '2022', quarter: 'Q1', title: '1,000-tenant milestone', body: 'The platform crosses 1,000 active tenants across 40+ countries. Advanced analytics, audit trails, and SSO support launch to meet growing compliance demands.', accent: 'indigo' },
  { year: '2023', quarter: 'Q3', title: 'Enterprise tier introduced', body: 'A dedicated enterprise plan launches with 99.99% SLA options, multi-entity governance, data residency controls, and a dedicated customer success team.', accent: 'amber' },
  { year: '2024', quarter: 'Q2', title: 'AI-assisted operations', body: 'aeos365 Assist launches — embedded AI summaries, smart scheduling, anomaly detection in finance, and predictive leave forecasting across the HRM suite.', accent: 'cyan' },
  { year: '2025', quarter: 'Q1', title: '40+ module platform', body: 'The platform grows to 40+ modules — from IoT operations and supply chain to education management and real estate — serving industries we never initially planned for.', accent: 'indigo' },
];

// ── Hero ──────────────────────────────────────────────────────────
function AboutHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={5}>
          <p className="aeos-pub-label">About aeos365</p>
          <h1 className="aeos-pub-h1">
            We build the operating layer{' '}
            <span className="aeos-pub-gradient-text">modern businesses deserve.</span>
          </h1>
          <p className="aeos-pub-lead" style={{ maxWidth: 680 }}>
            aeos365 was founded with a single conviction: that every organization —
            from a fast-growing startup to a global enterprise — deserves software
            that unifies people, processes, and data without the complexity tax.
            Five years on, we are still building toward that promise.
          </p>
          <HStack gap={3}>
            <a href="/signup" className="aeos-pub-btn-primary">Start for free</a>
            <a href="/pricing" className="aeos-pub-btn-ghost">View plans</a>
          </HStack>
          {/* Stat row */}
          <HStack gap={4} wrap>
            {ABOUT_STATS.map((s) => (
              <PublicStatCard
                key={s.label}
                value={s.value}
                suffix={s.suffix}
                label={s.label}
                accent="cyan"
              />
            ))}
          </HStack>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Mission & Vision ─────────────────────────────────────────────
function MissionVision() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <p className="aeos-pub-label aeos-text-center">Mission & Vision</p>
        <HStack gap={8} align="start">
          <VStack gap={4}>
            <Eyebrow tone="primary">Our Mission</Eyebrow>
            <h2 className="aeos-pub-h2">
              Simplify the complexity that slows organizations down.
            </h2>
            <p className="aeos-pub-body">
              Too many businesses operate across a patchwork of disconnected tools —
              each team solving the same coordination problem in isolation. Our mission
              is to eliminate that friction by providing one coherent, modular platform
              that scales with the organization, not against it.
            </p>
            <p className="aeos-pub-body">
              We believe software should remove barriers, not create them — which is why
              every design decision at aeos365 starts with the question: "Does this make
              the user's working day clearer?"
            </p>
          </VStack>
          <VStack gap={4}>
            <Eyebrow tone="primary">Our Vision</Eyebrow>
            <h2 className="aeos-pub-h2">
              A world where every organization runs at its true potential.
            </h2>
            <p className="aeos-pub-body">
              We envision a future where the distance between a great idea and flawless
              execution is measured in hours, not months. Where a ten-person team can
              operate with the same process clarity as a ten-thousand-person enterprise.
            </p>
            <p className="aeos-pub-body">
              By 2030, we aim to power the operational backbone of 10,000 organizations
              across six continents — helping them move faster, govern better, and grow
              with confidence.
            </p>
          </VStack>
        </HStack>
        {/* Principle cards */}
        <HStack gap={4} wrap style={{ marginTop: '3rem' }}>
          {[
            { label: 'Purpose-built', body: 'Every module is designed for real operational workflows, not adapted from generic frameworks.' },
            { label: 'Human-centred', body: 'Decisions are made for the person doing the work, not the IT admin configuring the system.' },
            { label: 'Transparent by default', body: 'No hidden limits, opaque pricing, or gotcha upgrade walls — ever.' },
          ].map((item) => (
            <Box key={item.label} grow>
              <Card>
                <VStack gap={2}>
                  <p className="aeos-pub-label aeos-pub-accent-text--cyan">{item.label}</p>
                  <Text tone="secondary">{item.body}</Text>
                </VStack>
              </Card>
            </Box>
          ))}
        </HStack>
      </Container>
    </Section>
  );
}

// ── Values ────────────────────────────────────────────────────────
function Values() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="Core values"
          title="What we stand for, every single day."
          lead="Values aren't posters on a wall. They're the criteria we use when making hard trade-offs — in product decisions, hiring, and customer commitments."
          align="center"
        />
        <div className="aeos-pub-pillar-grid">
          {VALUES.map((v) => (
            <Card key={v.badge}>
              <VStack gap={3}>
                <p className={`aeos-pub-label aeos-pub-accent-text--${v.accent}`}>{v.badge}</p>
                <h3 className="aeos-pub-h3">{v.title}</h3>
                <Text tone="secondary">{v.body}</Text>
              </VStack>
            </Card>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Timeline ─────────────────────────────────────────────────────
function Timeline() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Our journey"
          title="From a bold idea to a global platform."
          lead="Six years of focused building, honest customer relationships, and a relentless commitment to shipping software that actually works."
          align="center"
        />
        <div className="aeos-pub-narrative-grid">
          {MILESTONES.map((m) => (
            <Card key={`${m.year}-${m.quarter}`}>
              <VStack gap={3}>
                <HStack gap={2}>
                  <p className={`aeos-pub-label aeos-pub-accent-text--${m.accent}`}>{m.year}</p>
                  <Text tone="tertiary">{m.quarter}</Text>
                </HStack>
                <h3 className="aeos-pub-h3">{m.title}</h3>
                <Text tone="secondary">{m.body}</Text>
              </VStack>
            </Card>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── About CTA ─────────────────────────────────────────────────────
function AboutCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Join us</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Build with a team that{' '}
              <span className="aeos-pub-gradient-text">means it.</span>
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow">
              We are hiring across engineering, design, product, and customer success.
              If you want to work on software that matters to real organizations — come build with us.
            </p>
            <HStack gap={3}>
              <a href="/contact" className="aeos-pub-btn-primary">Get in touch →</a>
              <a href="/pricing" className="aeos-pub-btn-ghost">View pricing</a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function About() {
  return (
    <>
      <AboutHero />
      <MissionVision />
      <Values />
      <Timeline />
      <AboutCTA />
    </>
  );
}

About.layout = (page) => (
  <PublicLayout title="About — aeos365">{page}</PublicLayout>
);
