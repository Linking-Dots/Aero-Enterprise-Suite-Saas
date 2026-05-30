import { useState } from 'react';
import {
  Section, Container, PublicSectionHeader, Input,
  Card, VStack, HStack, Box, Text, Mono, Badge, Button, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const DOC_STATS = [
  { value: '500+', label: 'Articles' },
  { value: '40+',  label: 'Modules covered' },
  { value: 'REST', label: 'API docs' },
  { value: 'Live', label: 'Code samples' },
];

const DOC_CATEGORIES = [
  { badge: '01', title: 'Getting Started', desc: 'Install, configure, and launch your first tenant in under 15 minutes with our step-by-step onboarding guide.', articles: 24, accent: 'cyan', icon: 'cube' },
  { badge: '02', title: 'HRM Module', desc: 'Employee lifecycle, leave policies, payroll configuration, attendance tracking, and HR analytics.', articles: 86, accent: 'indigo', icon: 'users' },
  { badge: '03', title: 'CRM Module', desc: 'Pipeline management, deal tracking, contact management, activity logging, and sales automation.', articles: 62, accent: 'amber', icon: 'chart' },
  { badge: '04', title: 'Finance Module', desc: 'Chart of accounts, AP/AR, budgeting, multi-currency support, tax rules, and financial reporting.', articles: 74, accent: 'cyan', icon: 'database' },
  { badge: '05', title: 'Platform API', desc: 'REST references, authentication flows, rate limits, webhooks, and SDK documentation.', articles: 48, accent: 'indigo', icon: 'link' },
  { badge: '06', title: 'Integrations', desc: 'Connect aeos365 with Slack, Google Workspace, Zapier, QuickBooks, Salesforce, and 80+ services.', articles: 55, accent: 'amber', icon: 'puzzle' },
  { badge: '07', title: 'Security & Compliance', desc: 'Role-based access control, audit logs, GDPR data handling, SSO/SAML setup, and data residency.', articles: 38, accent: 'cyan', icon: 'shield' },
  { badge: '08', title: 'Billing & Plans', desc: 'Subscription management, plan upgrades, usage-based billing, invoice downloads, and payment methods.', articles: 29, accent: 'indigo', icon: 'document' },
];

const QUICK_START = [
  { step: '01', title: 'Create your workspace', body: 'Sign up and provision your tenant. Your isolated database is ready in under 60 seconds.' },
  { step: '02', title: 'Configure your modules', body: 'Enable the modules you need from the admin console. Each module has its own configuration panel.' },
  { step: '03', title: 'Invite your team', body: 'Use HRMAC to define roles, then invite users. They get access only to what they need.' },
  { step: '04', title: 'Connect your data', body: 'Import existing data via CSV or the REST API. Use webhooks to sync with external systems.' },
];

const POPULAR_ARTICLES = [
  { title: 'Setting up your first payroll run', module: 'HRM', readTime: '8 min' },
  { title: 'HRMAC: configuring the 4-level permission hierarchy', module: 'Platform', readTime: '12 min' },
  { title: 'Authenticating with the aeos365 REST API', module: 'API', readTime: '5 min' },
  { title: 'Multi-currency invoice configuration', module: 'Finance', readTime: '7 min' },
  { title: 'Setting up Slack notifications for leave approvals', module: 'Integrations', readTime: '4 min' },
  { title: 'Configuring the Kanban CRM pipeline', module: 'CRM', readTime: '6 min' },
];

const CHANGELOG = [
  { version: 'v2.14.0', date: 'Apr 28, 2026', note: 'AI Assist — payroll anomaly detection + leave pattern forecasting.', type: 'feature' },
  { version: 'v2.13.0', date: 'Apr 12, 2026', note: 'Supply chain module — goods receipt & inspection workflows.', type: 'feature' },
  { version: 'v2.12.1', date: 'Apr 5, 2026',  note: 'Bug fix — leave balance calculation for partial months.', type: 'fix' },
  { version: 'v2.12.0', date: 'Mar 28, 2026', note: 'API v2 — cursor pagination + improved rate limit headers.', type: 'feature' },
];

// ── Docs Hero ─────────────────────────────────────────────────────
function DocsHero() {
  const [query, setQuery] = useState('');

  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={5}>
          <p className="aeos-pub-label">aeos365 documentation</p>
          <h1 className="aeos-pub-h1">
            Everything you need to{' '}
            <span className="aeos-pub-gradient-text">build with aeos365.</span>
          </h1>
          <p className="aeos-pub-lead aeos-content-base">
            Guides, API references, quick-start tutorials, and integration recipes —
            organized by module so you can go from onboarding to production in hours, not days.
          </p>
          {/* Search — static UI */}
          <Box style={{ maxWidth: 560, width: '100%' }}>
            <Input
              type="search"
              leftIcon="search"
              placeholder="Search documentation, guides, and API references…"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
          </Box>
          <HStack gap={3}>
            <a href="/signup" className="aeos-pub-btn-primary">Get started free</a>
            <a href="/pricing" className="aeos-pub-btn-ghost">View pricing</a>
          </HStack>
          {/* Stat row */}
          <HStack gap={4} wrap>
            {DOC_STATS.map((s) => (
              <Card key={s.label} className="aeos-pub-trust-chip">
                <VStack gap={1} align="center">
                  <Mono>{s.value}</Mono>
                  <Text tone="tertiary" size="xs">{s.label}</Text>
                </VStack>
              </Card>
            ))}
          </HStack>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Doc Categories ────────────────────────────────────────────────
function DocCategories() {
  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Browse by category"
          title="Find docs for every module."
          lead="From core HR to enterprise API — every feature has its own dedicated documentation track with examples, code snippets, and migration guides."
          align="center"
        />
        <div className="aeos-pub-docs-grid">
          {DOC_CATEGORIES.map((cat) => (
            <Card key={cat.title}>
              <VStack gap={3}>
                <Icon name={cat.icon} size={24} className={`aeos-pub-accent-text--${cat.accent}`} />
                <h3 className="aeos-pub-h3">{cat.title}</h3>
                <Text tone="secondary">{cat.desc}</Text>
                <HStack gap={2} align="center" className="aeos-justify-between">
                  <Text tone="tertiary" size="xs">{cat.articles} articles</Text>
                  <a href="#" className={`aeos-pub-accent-text--${cat.accent}`}>→</a>
                </HStack>
              </VStack>
            </Card>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Quick Start ───────────────────────────────────────────────────
function QuickStart() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="Quick start"
          title="From zero to running in 4 steps."
          align="center"
        />
        <div className="aeos-pub-narrative-grid">
          {QUICK_START.map((step) => (
            <VStack key={step.step} gap={3}>
              <p className="aeos-pub-label aeos-pub-accent-text--cyan">{step.step}</p>
              <h3 className="aeos-pub-h3">{step.title}</h3>
              <Text tone="secondary">{step.body}</Text>
            </VStack>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Popular Articles ──────────────────────────────────────────────
function PopularArticles() {
  return (
    <Section size="md" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="Popular"
          title="Most-read documentation."
          align="center"
        />
        <VStack gap={2} style={{ maxWidth: 720, margin: '0 auto' }}>
          {POPULAR_ARTICLES.map((art) => (
            <Card key={art.title}>
              <HStack gap={4} align="center" className="aeos-justify-between">
                <VStack gap={1}>
                  <Text>{art.title}</Text>
                  <HStack gap={2}>
                    <Badge intent="neutral">{art.module}</Badge>
                    <Text tone="tertiary" size="xs">{art.readTime}</Text>
                  </HStack>
                </VStack>
                <Icon name="arrowRight" size={18} className="aeos-pub-accent-text--cyan" />
              </HStack>
            </Card>
          ))}
        </VStack>
      </Container>
    </Section>
  );
}

// ── Changelog ─────────────────────────────────────────────────────
function Changelog() {
  return (
    <Section size="md">
      <Container>
        <PublicSectionHeader
          eyebrow="Changelog"
          title="What's new in aeos365."
          align="center"
        />
        <VStack gap={3} style={{ maxWidth: 680, margin: '0 auto' }}>
          {CHANGELOG.map((entry) => (
            <Card key={entry.version}>
              <HStack gap={4} align="center">
                <Mono className="aeos-pub-accent-text--cyan">{entry.version}</Mono>
                <Text tone="tertiary" size="sm">{entry.date}</Text>
                <Badge intent={entry.type === 'fix' ? 'warning' : 'success'}>{entry.type}</Badge>
                <Text tone="secondary">{entry.note}</Text>
              </HStack>
            </Card>
          ))}
        </VStack>
      </Container>
    </Section>
  );
}

// ── Docs CTA ─────────────────────────────────────────────────────
function DocsCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Ready to build?</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Start your workspace today.
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow-2">
              Full documentation access comes with every account — no extra plan needed.
              Sign up and explore the platform in minutes.
            </p>
            <HStack gap={3}>
              <a href="/signup" className="aeos-pub-btn-primary">Create workspace →</a>
              <a href="/docs/api" className="aeos-pub-btn-ghost">API reference</a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Docs() {
  return (
    <>
      <DocsHero />
      <DocCategories />
      <QuickStart />
      <PopularArticles />
      <Changelog />
      <DocsCTA />
    </>
  );
}

Docs.layout = (page) => (
  <PublicLayout title="Documentation — aeos365">{page}</PublicLayout>
);
