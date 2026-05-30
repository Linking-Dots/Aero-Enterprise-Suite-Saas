import {
  Section, Container, VStack, HStack, Box, Text, Card, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const TOC = [
  { id: 'overview',                label: 'Security Overview' },
  { id: 'iso-27001',               label: 'ISO 27001 & Certifications' },
  { id: 'encryption',              label: 'Data Encryption' },
  { id: 'access-control',          label: 'Access Control' },
  { id: 'incident-response',       label: 'Incident Response' },
  { id: 'data-residency',          label: 'Data Residency' },
  { id: 'penetration-testing',     label: 'Penetration Testing' },
  { id: 'vulnerability-disclosure', label: 'Vulnerability Disclosure' },
];

const SECTIONS = [
  {
    id: 'overview',
    title: 'Security Overview',
    body: [
      'We take the security of your data seriously. This policy describes the technical and organizational measures we implement to protect your information.',
      'Our security program is continuously reviewed and updated to address evolving threats, regulatory requirements, and the expanding scope of our platform capabilities.',
    ],
  },
  {
    id: 'iso-27001',
    title: 'ISO 27001 & Certifications',
    body: [
      'Our platform is designed to align with ISO/IEC 27001 information security management standards. We regularly review and update our security controls to maintain compliance with industry best practices and regulatory requirements.',
      'We assess our security posture against recognized frameworks to ensure our controls meet the expectations of enterprise customers operating in regulated industries.',
    ],
  },
  {
    id: 'encryption',
    title: 'Data Encryption',
    body: [
      'We apply encryption across all data layers — both in transit and at rest — using industry-standard algorithms and key management practices.',
    ],
    list: [
      'All data in transit is encrypted using TLS 1.2 or higher',
      'Data at rest is encrypted using AES-256',
      'Database credentials and secrets are stored in encrypted vaults',
      'Backups are encrypted before storage',
    ],
  },
  {
    id: 'access-control',
    title: 'Access Control',
    body: [
      'Access to platform resources is controlled through a layered model that enforces the principle of least privilege across all systems and user roles.',
    ],
    list: [
      'Role-based access control (RBAC) for all platform resources',
      'Multi-factor authentication (MFA) available for all accounts',
      'Principle of least privilege enforced across all systems',
      'Automated session expiry and token rotation',
      'Audit logs for all administrative actions',
    ],
  },
  {
    id: 'incident-response',
    title: 'Incident Response',
    body: [
      'Our incident response plan includes: detection and analysis, containment, eradication and recovery, and post-incident review. We commit to notifying affected customers within 72 hours of discovering a security breach that may affect their data.',
      'Our security team conducts regular tabletop exercises and simulation drills to ensure readiness and to continuously improve response capabilities.',
    ],
  },
  {
    id: 'data-residency',
    title: 'Data Residency',
    body: [
      'Customer data is stored in the region selected at the time of account creation. We do not transfer data outside the selected region without explicit consent, except where required by law.',
      'Tenants can view their configured data region from within their workspace settings. Region changes require a formal request and may involve data migration procedures.',
    ],
  },
  {
    id: 'penetration-testing',
    title: 'Penetration Testing',
    body: [
      'We conduct regular penetration testing through qualified third-party security firms. Results are reviewed by our security team and critical findings are remediated within 30 days of discovery.',
      'Penetration test summaries are available to enterprise customers upon request under NDA as part of our security review process.',
    ],
  },
  {
    id: 'vulnerability-disclosure',
    title: 'Vulnerability Disclosure',
    body: [
      'If you discover a security vulnerability, please report it to security@aeos365.com. We follow responsible disclosure practices and ask that you give us a reasonable timeframe to remediate before public disclosure.',
      'We do not take legal action against researchers who report vulnerabilities in good faith. We aim to acknowledge reports within 5 business days and provide remediation timelines as findings are validated.',
    ],
    extra: (
      <Text tone="secondary">
        To report a vulnerability, email{' '}
        <a href="mailto:security@aeos365.com" className="aeos-pub-accent-text--cyan">
          security@aeos365.com
        </a>.
      </Text>
    ),
  },
];

// ── Hero ──────────────────────────────────────────────────────────
function SecurityHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={4}>
          <p className="aeos-pub-label">Legal</p>
          <h1 className="aeos-pub-h1">Security Policy</h1>
          <p className="aeos-pub-lead aeos-content-base">
            How aeos365 protects your data — from encryption at rest to incident response,
            penetration testing, and vulnerability disclosure.
          </p>
          <p className="aeos-pub-label">Last updated: April 30, 2026</p>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Content ───────────────────────────────────────────────────────
function SecurityContent() {
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
                  {sec.list && (
                    <VStack gap={2}>
                      {sec.list.map((item) => (
                        <HStack key={item} gap={2} align="start">
                          <Text className="aeos-pub-accent-text--cyan">▸</Text>
                          <Text tone="secondary">{item}</Text>
                        </HStack>
                      ))}
                    </VStack>
                  )}
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
function SecurityCTA() {
  return (
    <Section size="md" bg="surface">
      <Container>
        <HStack gap={4} align="center" className="aeos-justify-between">
          <VStack gap={2}>
            <p className="aeos-pub-label">Security questions?</p>
            <Text tone="secondary">
              Enterprise customers can request penetration test summaries, SLA details,
              and security review documentation under NDA.
            </Text>
          </VStack>
          <a href="/contact" className="aeos-pub-btn-primary">Contact security team →</a>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function LegalSecurity() {
  return (
    <>
      <SecurityHero />
      <SecurityContent />
      <SecurityCTA />
    </>
  );
}

LegalSecurity.layout = (page) => (
  <PublicLayout title="Security Policy — aeos365">{page}</PublicLayout>
);
