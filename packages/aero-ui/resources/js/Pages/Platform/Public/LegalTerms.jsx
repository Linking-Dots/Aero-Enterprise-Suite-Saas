import { Link } from '@inertiajs/react';
import {
  Section, Container, VStack, HStack, Box, Text, Eyebrow, Card,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import LegalDoc from './legal/LegalDoc.jsx';

const TOC = [
  { id: 'acceptance',            label: 'Acceptance of Terms' },
  { id: 'account-responsibilities', label: 'Account Responsibilities' },
  { id: 'acceptable-use',        label: 'Acceptable Use' },
  { id: 'billing-subscription',  label: 'Billing & Subscription' },
  { id: 'intellectual-property', label: 'Intellectual Property' },
  { id: 'confidentiality-data',  label: 'Confidentiality & Data Handling' },
  { id: 'availability-disclaimer', label: 'Service Availability & Disclaimer' },
  { id: 'liability',             label: 'Limitation of Liability' },
  { id: 'termination',           label: 'Termination' },
  { id: 'governing-law-changes', label: 'Governing Law & Changes' },
  { id: 'contact',               label: 'Contact' },
];

const SECTIONS = [
  {
    id: 'acceptance',
    title: 'Acceptance of Terms',
    body: [
      'By accessing or using aeos365, you agree to these Terms and any policies incorporated by reference. If you use the platform on behalf of an organization, you represent that you are authorized to bind that organization.',
      'If you do not agree with these Terms, do not access or use the service.',
    ],
  },
  {
    id: 'account-responsibilities',
    title: 'Account Responsibilities',
    body: [
      'You are responsible for maintaining the confidentiality of account credentials and for activity under your account, including actions taken by invited users in your workspace.',
      'You must provide accurate account information, keep it current, and promptly notify us of any unauthorized access or suspected security incident.',
    ],
  },
  {
    id: 'acceptable-use',
    title: 'Acceptable Use',
    body: [
      'You may not use aeos365 to violate applicable law, infringe rights, interfere with platform operation, distribute malware, or attempt unauthorized access to systems or data.',
      'We may investigate suspected misuse and take reasonable protective actions, including temporary suspension, access restrictions, or data preservation where required by law.',
    ],
  },
  {
    id: 'billing-subscription',
    title: 'Billing & Subscription',
    body: [
      'Paid features are offered through subscription plans with pricing and limits disclosed at purchase or order form. Fees are due according to the selected billing cycle and are generally non-refundable except where required by law or contract.',
      'You authorize aeos365 and its payment processors to charge applicable fees, taxes, and renewals until cancellation. Plan upgrades, downgrades, and add-ons may affect current billing periods as described in your plan terms.',
    ],
  },
  {
    id: 'intellectual-property',
    title: 'Intellectual Property',
    body: [
      'aeos365 and its licensors retain all rights, title, and interest in the platform, software, documentation, branding, and related content. These Terms do not grant ownership rights to you.',
      'Subject to these Terms, we grant you a limited, non-exclusive, non-transferable right to access and use the service for your internal business operations.',
    ],
  },
  {
    id: 'confidentiality-data',
    title: 'Confidentiality & Data Handling',
    body: [
      'Each party may receive confidential information in connection with service use. The receiving party agrees to protect such information with reasonable care and use it only for permitted purposes.',
      'Customer data handling, security controls, and privacy rights are further described in our Privacy Policy and related data processing commitments.',
    ],
    extra: (
      <Text tone="secondary">
        Review the companion privacy commitments in our{' '}
        <Link href="/legal/privacy" className="aeos-pub-accent-text--cyan">privacy page</Link>{' '}
        and technical controls in{' '}
        <Link href="/docs" className="aeos-pub-accent-text--cyan">/docs</Link>.
      </Text>
    ),
  },
  {
    id: 'availability-disclaimer',
    title: 'Service Availability & Disclaimer',
    body: [
      'We aim to provide reliable and secure service, but availability may be affected by maintenance, network conditions, third-party dependencies, or events beyond our control.',
      'Except as expressly stated in a written agreement, the service is provided on an "as is" and "as available" basis without warranties of any kind, whether express or implied.',
    ],
  },
  {
    id: 'liability',
    title: 'Limitation of Liability',
    body: [
      'To the maximum extent permitted by law, aeos365 and its affiliates are not liable for indirect, incidental, special, consequential, exemplary, or punitive damages, or for loss of profits, revenue, goodwill, or data.',
      'Where liability cannot be fully excluded, aggregate liability is limited to amounts paid by you for the affected services during the twelve months preceding the claim.',
    ],
  },
  {
    id: 'termination',
    title: 'Termination',
    body: [
      'You may stop using the service at any time and may cancel subscriptions according to your plan terms. We may suspend or terminate access for material breach, security risk, non-payment, or unlawful activity.',
      'Upon termination, access rights end, and data retention or deletion follows applicable law, contractual obligations, and documented retention practices.',
    ],
  },
  {
    id: 'governing-law-changes',
    title: 'Governing Law & Changes',
    body: [
      'These Terms are governed by the laws specified in your order form or applicable service agreement. If not specified, governing law is determined by the contracting aeos365 entity and applicable conflict rules.',
      'We may update these Terms from time to time. Material updates will be posted with a revised effective date, and continued use after that date constitutes acceptance of the updated Terms.',
    ],
  },
  {
    id: 'contact',
    title: 'Contact',
    body: [
      'For legal notices, contractual questions, or policy clarifications, contact our team through the official contact channel with your organization and workspace details.',
      'For implementation guidance, onboarding, and product references, consult our documentation library.',
    ],
    extra: (
      <Text tone="secondary">
        Reach legal support via{' '}
        <Link href="/contact" className="aeos-pub-accent-text--cyan">/contact</Link>
        {' '}or create a trial workspace at{' '}
        <Link href="/signup" className="aeos-pub-accent-text--cyan">/signup</Link>.
      </Text>
    ),
  },
];

// ── Hero ──────────────────────────────────────────────────────────
function LegalHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={4}>
          <p className="aeos-pub-label">Legal</p>
          <h1 className="aeos-pub-h1">Terms of Service</h1>
          <p className="aeos-pub-lead aeos-content-base">
            These terms govern your access to and use of the aeos365 platform.
            Please read them carefully before using our services.
          </p>
          <p className="aeos-pub-label">Last updated: April 30, 2026</p>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Content ───────────────────────────────────────────────────────
function LegalContent() {
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
function LegalCTA() {
  return (
    <Section size="md" bg="surface">
      <Container>
        <HStack gap={4} align="center" className="aeos-justify-between">
          <VStack gap={2}>
            <p className="aeos-pub-label">Questions about these terms?</p>
            <Text tone="secondary">Our team is happy to clarify any policy questions before you commit.</Text>
          </VStack>
          <a href="/contact" className="aeos-pub-btn-primary">Contact us →</a>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function LegalTerms() {
  return (
    <LegalDoc
      eyebrow="Legal"
      title="Terms of Service"
      lead="The terms that govern your use of aeos365. Please read them carefully — they define the agreement between you and us."
      updated="April 30, 2026"
      toc={TOC}
      sections={SECTIONS}
    />
  );
}

LegalTerms.layout = (page) => (
  <PublicLayout title="Terms of Service — aeos365">{page}</PublicLayout>
);
