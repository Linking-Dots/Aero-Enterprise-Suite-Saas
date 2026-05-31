import {
  Section, Container, PublicSectionHeader, PublicStatCard,
  PublicFeatureCard, Accordion,
  Card, VStack, HStack, Box, Text, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const CAPABILITIES = [
  { badge: 'GL', title: 'Global operating model', body: 'Standardize regional operations while preserving local compliance needs across countries, entities, and shared services.', accent: 'cyan' },
  { badge: 'BI', title: 'Board-ready reporting', body: 'Surface real-time financial, workforce, and operational insights with executive dashboards and drill-down analytics.', accent: 'indigo' },
  { badge: 'WF', title: 'Cross-functional workflows', body: 'Connect HR, finance, procurement, projects, and customer operations through integrated approvals and automation.', accent: 'amber' },
  { badge: 'MX', title: 'Modular transformation', body: 'Adopt high-impact modules first, then scale incrementally without disrupting your existing technology estate.', accent: 'cyan' },
  { badge: 'TS', title: 'Tenant-secure isolation', body: 'Protect sensitive enterprise data with strict boundary controls, role scopes, and auditable access trails.', accent: 'indigo' },
  { badge: 'API', title: 'Enterprise-grade extensibility', body: 'Use APIs, webhooks, and integration patterns to orchestrate your ERP backbone across legacy and modern stacks.', accent: 'amber' },
];

const GOVERNANCE_CONTROLS = [
  'Policy-based access and approval routing by entity and region',
  'Traceable audit history across workflows and user actions',
  'Data residency alignment through tenant and domain boundaries',
  'Built-in segregation principles for sensitive processes',
];

const OUTCOME_STATS = [
  { value: 38,  suffix: '%', label: 'faster cross-team cycle times', detail: 'Through integrated approvals, shared workflows, and automation-first operating patterns.' },
  { value: 45,  suffix: '%', label: 'improvement in process visibility', detail: 'With unified metrics spanning workforce, finance, operations, and customer execution.' },
  { value: 62,  suffix: '%', label: 'reduction in manual reporting effort', detail: 'By replacing spreadsheet-heavy routines with live dashboards and governed data pipelines.' },
  { value: '3', suffix: 'x', label: 'faster rollout of new operational initiatives', detail: 'Enabled by composable modules, consistent controls, and repeatable onboarding frameworks.' },
];

const INTEGRATIONS = [
  { name: 'Slack', category: 'Communication' },
  { name: 'Google Workspace', category: 'Productivity' },
  { name: 'Salesforce', category: 'CRM' },
  { name: 'QuickBooks', category: 'Finance' },
  { name: 'Zapier', category: 'Automation' },
  { name: 'Microsoft Azure AD', category: 'Identity' },
  { name: 'Okta', category: 'SSO' },
  { name: 'Stripe', category: 'Payments' },
];

const ENTERPRISE_FAQ = [
  { question: 'Can we adopt aeos365 without replacing everything at once?', answer: 'Yes. Most enterprise customers start with priority modules and connect existing systems through phased integration and data synchronization.' },
  { question: 'How does aeos365 support governance and audit readiness?', answer: 'The platform includes role-scoped workflows, approval traceability, and centralized policy controls designed for enterprise accountability needs.' },
  { question: 'Do you support multi-entity and multi-region operations?', answer: 'Yes. The architecture supports multiple entities, localized processes, and tenant-safe boundaries to align with regional operating models.' },
  { question: 'What does implementation support look like?', answer: 'Enterprise onboarding includes discovery, pilot deployment, rollout planning, enablement sessions, and iterative optimization support.' },
];

// ── Hero ──────────────────────────────────────────────────────────
function EnterpriseHero() {
  const proofPoints = [
    '99.99% uptime SLA options for mission-critical workloads',
    'Multi-entity governance with auditable enterprise controls',
    'Role-based approvals across HR, finance, operations, and procurement',
    'Deployment patterns for regional compliance and data residency',
  ];

  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <HStack gap={8} align="start">
          <VStack gap={5}>
            <p className="aeos-pub-label">Enterprise platform</p>
            <h1 className="aeos-pub-h1">
              The operating system for{' '}
              <span className="aeos-pub-gradient-text">modern enterprise execution.</span>
            </h1>
            <p className="aeos-pub-lead" style={{ maxWidth: 560 }}>
              aeos365 helps complex organizations unify departments, standardize controls,
              and scale with confidence. Connect strategy to execution across business units
              with one modular platform built for governance, performance, and continuous adaptation.
            </p>
            <HStack gap={3}>
              <a href="/signup" className="aeos-pub-btn-primary">Request Executive Demo</a>
              <a href="/pricing" className="aeos-pub-btn-ghost">Review Enterprise Plans</a>
            </HStack>
            <p className="aeos-pub-label">
              Global readiness · Tenant-safe architecture · API-first integration
            </p>
          </VStack>

          <Box grow>
            <Card>
              <VStack gap={4}>
                <p className="aeos-pub-label aeos-pub-accent-text--cyan">Enterprise proof points</p>
                {proofPoints.map((pt) => (
                  <HStack key={pt} gap={3} align="start">
                    <Icon name="checkCircle" size={18} className="aeos-pub-accent-text--cyan" />
                    <Text tone="secondary">{pt}</Text>
                  </HStack>
                ))}
              </VStack>
            </Card>
          </Box>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Capabilities ─────────────────────────────────────────────────
function Capabilities() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Capabilities"
          title="Built for high-complexity enterprise environments."
          lead="From governance to execution, each capability is designed to reduce process friction, improve visibility, and accelerate decision-making across the organization."
          align="center"
        />
        <div className="aeos-pub-pillar-grid">
          {CAPABILITIES.map((cap) => (
            <PublicFeatureCard
              key={cap.title}
              title={cap.title}
              description={cap.body}
              accent={cap.accent}
              size="md"
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Governance ────────────────────────────────────────────────────
function Governance() {
  return (
    <Section size="lg">
      <Container>
        <HStack gap={8} align="start">
          <VStack gap={4} className="aeos-flex-1">
            <p className="aeos-pub-label aeos-pub-accent-text--amber">Governance & compliance</p>
            <h2 className="aeos-pub-h2">
              Operate with confidence in regulated environments.
            </h2>
            <p className="aeos-pub-lead">
              Enterprise teams need more than features. They need control architecture
              that scales with risk, regulatory change, and stakeholder accountability.
              aeos365 embeds governance patterns directly into operational workflows.
            </p>
          </VStack>
          <Box grow>
            <Card>
              <VStack gap={3}>
                <p className="aeos-pub-label aeos-pub-accent-text--amber">Control architecture</p>
                {GOVERNANCE_CONTROLS.map((ctrl) => (
                  <HStack key={ctrl} gap={3} align="start">
                    <Icon name="shield" size={16} className="aeos-pub-accent-text--amber" />
                    <Text tone="secondary">{ctrl}</Text>
                  </HStack>
                ))}
              </VStack>
            </Card>
          </Box>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Outcomes ──────────────────────────────────────────────────────
function Outcomes() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Customer outcomes"
          title="Measurable impact where it matters most."
          align="center"
        />
        <div className="aeos-pub-narrative-grid">
          {OUTCOME_STATS.map((s) => (
            <Card key={s.label}>
              <VStack gap={2}>
                <p className="aeos-pub-h2 aeos-pub-accent-text--cyan">
                  {s.value}{s.suffix}
                </p>
                <h3 className="aeos-pub-h3">{s.label}</h3>
                <Text tone="secondary">{s.detail}</Text>
              </VStack>
            </Card>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Integrations ─────────────────────────────────────────────────
function Integrations() {
  return (
    <Section size="md">
      <Container>
        <PublicSectionHeader
          eyebrow="Integrations"
          title="Connects with your existing stack."
          lead="aeos365 integrates with the tools enterprise teams already rely on. REST API, webhooks, and pre-built connectors make the integration story seamless."
          align="center"
        />
        <HStack gap={3} wrap align="center" className="aeos-justify-center">
          {INTEGRATIONS.map((i) => (
            <Card key={i.name} className="aeos-pub-trust-chip">
              <VStack gap={1} align="center">
                <Text>{i.name}</Text>
                <Text tone="tertiary" size="xs">{i.category}</Text>
              </VStack>
            </Card>
          ))}
        </HStack>
      </Container>
    </Section>
  );
}

// ── FAQ ───────────────────────────────────────────────────────────
function EnterpriseFAQSection() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="FAQ"
          title="Common enterprise questions, answered."
          align="center"
        />
        <VStack gap={0} className="aeos-content-extra-wide">
          <Accordion items={ENTERPRISE_FAQ} />
        </VStack>
      </Container>
    </Section>
  );
}

// ── CTA ───────────────────────────────────────────────────────────
function EnterpriseCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Get started</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Ready to transform your enterprise operations?
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow">
              Our enterprise team is standing by to understand your requirements and
              help you design the right deployment model.
            </p>
            <HStack gap={3}>
              <a href="/contact" className="aeos-pub-btn-primary">Contact enterprise sales →</a>
              <a href="/pricing" className="aeos-pub-btn-ghost">View enterprise plan</a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Enterprise() {
  return (
    <>
      <EnterpriseHero />
      <Capabilities />
      <Governance />
      <Outcomes />
      <Integrations />
      <EnterpriseFAQSection />
      <EnterpriseCTA />
    </>
  );
}

Enterprise.layout = (page) => (
  <PublicLayout title="Enterprise — Mission-Critical ERP">{page}</PublicLayout>
);
