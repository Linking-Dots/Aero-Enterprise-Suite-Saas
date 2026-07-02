import { Link } from '@inertiajs/react';
import {
  Section, Container, VStack, HStack, Box, Text, Card,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import LegalDoc from './legal/LegalDoc.jsx';

const TOC = [
  { id: 'intro',             label: 'Introduction' },
  { id: 'data-collection',   label: 'Data We Collect' },
  { id: 'usage',             label: 'How We Use Data' },
  { id: 'sharing',           label: 'Data Sharing' },
  { id: 'retention-security', label: 'Retention & Security' },
  { id: 'rights-choices',    label: 'Your Rights & Choices' },
  { id: 'cookies',           label: 'Cookies' },
  { id: 'contact',           label: 'Contact' },
];

const SECTIONS = [
  {
    id: 'intro',
    title: 'Introduction',
    body: [
      'aeos365 provides business software for operations, collaboration, and reporting across enterprise teams. This Privacy Policy applies to our websites, public product pages, and services that reference this policy.',
      'By using aeos365, you acknowledge this policy and understand that we process information to deliver secure and reliable service.',
    ],
  },
  {
    id: 'data-collection',
    title: 'Data We Collect',
    body: [
      'We collect account data such as name, work email, organization, and role details when you register or are invited to a workspace.',
      'We also collect usage data including page interactions, feature usage, logs, and device metadata needed to maintain performance, auditability, and security.',
    ],
  },
  {
    id: 'usage',
    title: 'How We Use Data',
    body: [
      'We use data to operate and improve the platform, authenticate users, support tenant-level configuration, prevent abuse, and provide technical support.',
      'We may use aggregated and de-identified analytics to improve product quality, documentation, and onboarding experiences.',
    ],
  },
  {
    id: 'sharing',
    title: 'Data Sharing',
    body: [
      'We do not sell personal information. Data may be shared with trusted processors (such as infrastructure, email, and support providers) only as needed to deliver services under contractual safeguards.',
      'We may disclose information where required by law, legal process, or to protect the rights, safety, and security of customers, users, and aeos365.',
    ],
  },
  {
    id: 'retention-security',
    title: 'Retention & Security',
    body: [
      'We retain data for as long as needed to provide services, satisfy legal obligations, and resolve disputes. Retention windows vary by data type and tenant configuration.',
      'Security controls include encryption in transit, access controls, activity monitoring, and periodic security reviews. No method of transmission or storage is completely risk-free.',
    ],
  },
  {
    id: 'rights-choices',
    title: 'Your Rights & Choices',
    body: [
      'Depending on your region, you may have rights to access, correct, export, delete, or restrict processing of your personal data.',
      'Workspace administrators can manage many account-level controls directly. You can also contact us for request handling and verification support.',
    ],
    extra: (
      <Text tone="secondary">
        Need account self-service options? Visit{' '}
        <Link href="/signup" className="aeos-pub-accent-text--cyan">/signup</Link>
        {' '}to create a workspace, or review guides in{' '}
        <Link href="/docs" className="aeos-pub-accent-text--cyan">/docs</Link>.
      </Text>
    ),
  },
  {
    id: 'cookies',
    title: 'Cookies',
    body: [
      'We use cookies and similar technologies for authentication, session continuity, preferences, and analytics related to product reliability.',
      'You can manage cookie settings in your browser. Disabling certain cookies may impact parts of the service.',
    ],
  },
  {
    id: 'contact',
    title: 'Contact',
    body: [
      'For privacy requests, legal questions, or data protection concerns, contact our team through our contact page. Please include your organization and workspace details so we can process your request quickly.',
      'For technical integration or implementation guidance, our documentation contains setup, API, and operational references.',
    ],
    extra: (
      <Text tone="secondary">
        Privacy-specific questions can be sent through{' '}
        <Link href="/contact" className="aeos-pub-accent-text--cyan">/contact</Link>.
      </Text>
    ),
  },
];

// ── Hero ──────────────────────────────────────────────────────────
function PrivacyHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={4}>
          <p className="aeos-pub-label">Legal</p>
          <h1 className="aeos-pub-h1">Privacy Policy</h1>
          <p className="aeos-pub-lead aeos-content-base">
            We take your privacy seriously. This policy describes what data we collect,
            how we use it, and what rights you have over your information.
          </p>
          <p className="aeos-pub-label">Last updated: April 30, 2026</p>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Content ───────────────────────────────────────────────────────
function PrivacyContent() {
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
function PrivacyCTA() {
  return (
    <Section size="md" bg="surface">
      <Container>
        <HStack gap={4} align="center" className="aeos-justify-between">
          <VStack gap={2}>
            <p className="aeos-pub-label">Privacy questions?</p>
            <Text tone="secondary">
              Contact our data protection team and we will respond within 5 business days.
            </Text>
          </VStack>
          <a href="/contact" className="aeos-pub-btn-primary">Contact us →</a>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function LegalPrivacy() {
  return (
    <LegalDoc
      eyebrow="Legal"
      title="Privacy Policy"
      lead="We take your privacy seriously. This policy describes what data we collect, how we use it, and the rights you have over your information."
      updated="April 30, 2026"
      toc={TOC}
      sections={SECTIONS}
    />
  );
}

LegalPrivacy.layout = (page) => (
  <PublicLayout title="Privacy Policy — aeos365">{page}</PublicLayout>
);
