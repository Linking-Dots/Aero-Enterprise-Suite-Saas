import { Link } from '@inertiajs/react';
import {
  Section, Container, VStack, HStack, Box, Text, Card,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import LegalDoc from './legal/LegalDoc.jsx';

const TOC = [
  { id: 'what-cookies-are',    label: 'What Cookies Are' },
  { id: 'cookie-categories',   label: 'Cookie Categories' },
  { id: 'how-we-use-cookies',  label: 'How We Use Cookies' },
  { id: 'third-party-cookies', label: 'Third-Party Cookies' },
  { id: 'managing-preferences', label: 'Managing Preferences' },
  { id: 'browser-controls',    label: 'Browser Controls' },
  { id: 'updates',             label: 'Updates' },
  { id: 'contact',             label: 'Contact' },
];

const SECTIONS = [
  {
    id: 'what-cookies-are',
    title: 'What Cookies Are',
    body: [
      'Cookies are small text files stored in your browser when you visit a website. They help services remember session state, preferences, and interactions across requests.',
      'In this policy, "cookies" also includes similar browser storage technologies used for functionality, analytics, and security signals.',
    ],
  },
  {
    id: 'cookie-categories',
    title: 'Cookie Categories',
    body: [
      'Essential cookies are required for core platform behavior such as login continuity, tenant routing, and security protections. Without these, key services may not function.',
      'Performance, analytics, and preference cookies help us measure reliability, improve onboarding flows, and remember your non-sensitive settings between visits.',
    ],
  },
  {
    id: 'how-we-use-cookies',
    title: 'How We Use Cookies',
    body: [
      'We use cookies to keep users signed in, preserve language and interface preferences, route requests correctly, and detect abuse or unusual activity.',
      'We also use aggregate cookie-backed metrics to understand feature adoption and improve documentation, support quality, and product performance.',
    ],
  },
  {
    id: 'third-party-cookies',
    title: 'Third-Party Cookies',
    body: [
      'Some pages may load third-party services for analytics, support tooling, or embedded content. These providers can set their own cookies according to their policies.',
      'We only enable third-party integrations where needed for service delivery, performance monitoring, and customer support operations.',
    ],
  },
  {
    id: 'managing-preferences',
    title: 'Managing Preferences',
    body: [
      'Where available, you can manage cookie preferences through on-site controls and account settings to adjust non-essential tracking behavior.',
      'Your choices may be stored in a cookie so preferences persist. Clearing browser storage can reset those choices.',
    ],
    extra: (
      <Text tone="secondary">
        Looking for setup guidance? Visit{' '}
        <Link href="/docs" className="aeos-pub-accent-text--cyan">/docs</Link>
        {' '}for implementation references.
      </Text>
    ),
  },
  {
    id: 'browser-controls',
    title: 'Browser Controls',
    body: [
      'Most browsers let you block, delete, or limit cookies through privacy settings. You can also configure alerts when a site tries to set cookies.',
      'If you disable essential cookies, sign-in sessions and some tenant-specific workflows may not work as expected.',
    ],
  },
  {
    id: 'updates',
    title: 'Updates',
    body: [
      'We may update this Cookie Policy when legal requirements, product capabilities, or third-party integrations change.',
      'When material changes are made, we update the "Last updated" date and publish the revised policy on this page.',
    ],
  },
  {
    id: 'contact',
    title: 'Contact',
    body: [
      'If you have questions about cookie use, privacy controls, or compliance requests, contact us with your organization and workspace context.',
      'For implementation and technical behavior details, review our product documentation and setup references.',
    ],
    extra: (
      <Text tone="secondary">
        Reach our team via{' '}
        <Link href="/contact" className="aeos-pub-accent-text--cyan">/contact</Link>
        {' '}or create a workspace at{' '}
        <Link href="/signup" className="aeos-pub-accent-text--cyan">/signup</Link>.
      </Text>
    ),
  },
];

// ── Hero ──────────────────────────────────────────────────────────
function CookiesHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={4}>
          <p className="aeos-pub-label">Legal</p>
          <h1 className="aeos-pub-h1">Cookie Policy</h1>
          <p className="aeos-pub-lead aeos-content-base">
            This policy explains how aeos365 uses cookies and similar technologies,
            what choices you have, and how to manage your preferences.
          </p>
          <p className="aeos-pub-label">Last updated: April 30, 2026</p>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Content ───────────────────────────────────────────────────────
function CookiesContent() {
  return (
    <Section size="lg">
      <Container>
        <div className="aeos-pub-legal-layout">
          {/* TOC */}
          <Box className="aeos-pub-legal-toc">
            <VStack gap={1}>
              <p className="aeos-pub-label">Contents</p>
              {TOC.map((item) => (
                <a key={item.id} href={`#${item.id}`} className="aeos-pub-toc-link">
                  {item.label}
                </a>
              ))}
            </VStack>
          </Box>

          {/* Sections */}
          <VStack gap={4}>
            {SECTIONS.map((sec) => (
              <Card key={sec.id} id={sec.id} className="aeos-pub-legal-section">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">{sec.title}</h2>
                  {sec.body.map((para, i) => (
                    <Text key={i} tone="secondary">{para}</Text>
                  ))}
                  {sec.extra && sec.extra}
                </VStack>
              </Card>
            ))}
          </VStack>
        </div>
      </Container>
    </Section>
  );
}

// ── CTA ───────────────────────────────────────────────────────────
function CookiesCTA() {
  return (
    <Section size="md" bg="surface">
      <Container>
        <HStack gap={4} align="center" className="aeos-justify-between">
          <VStack gap={2}>
            <p className="aeos-pub-label">Cookie questions?</p>
            <Text tone="secondary">
              Reach our privacy team if you have questions about how we use cookies or your options.
            </Text>
          </VStack>
          <a href="/contact" className="aeos-pub-btn-primary">Contact us →</a>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function LegalCookies() {
  return (
    <LegalDoc
      eyebrow="Legal"
      title="Cookie Policy"
      lead="What cookies and similar technologies we use, why we use them, and how you can control them."
      updated="April 30, 2026"
      toc={TOC}
      sections={SECTIONS}
    />
  );
}

LegalCookies.layout = (page) => (
  <PublicLayout title="Cookie Policy — aeos365">{page}</PublicLayout>
);
