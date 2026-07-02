import { motion } from 'framer-motion';
import { Container, Accordion } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal, CountUp, ThemedShot, useTilt } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { SHOTS, IMG } from './home/livingData.js';

const PROOF = [
  '99.99% uptime SLA options for mission-critical workloads',
  'Multi-entity governance with auditable enterprise controls',
  'Role-based approvals across HR, finance, operations & procurement',
  'Deployment patterns for regional compliance & data residency',
];
const CAPABILITIES = [
  { badge: 'GL', title: 'Global operating model', body: 'Standardize regional operations while preserving local compliance across countries, entities, and shared services.', accent: 'cyan' },
  { badge: 'BI', title: 'Board-ready reporting', body: 'Surface real-time financial, workforce, and operational insights with executive dashboards and drill-down analytics.', accent: 'indigo' },
  { badge: 'WF', title: 'Cross-functional workflows', body: 'Connect HR, finance, procurement, projects, and customer operations through integrated approvals and automation.', accent: 'amber' },
  { badge: 'MX', title: 'Modular transformation', body: 'Adopt high-impact modules first, then scale incrementally without disrupting your existing technology estate.', accent: 'cyan' },
  { badge: 'TS', title: 'Tenant-secure isolation', body: 'Protect sensitive enterprise data with strict boundary controls, role scopes, and auditable access trails.', accent: 'indigo' },
  { badge: 'API', title: 'Enterprise extensibility', body: 'Use APIs, webhooks, and integration patterns to orchestrate your ERP backbone across legacy and modern stacks.', accent: 'amber' },
];
const GOVERNANCE = [
  'Policy-based access and approval routing by entity and region',
  'Traceable audit history across workflows and user actions',
  'Data residency alignment through tenant and domain boundaries',
  'Built-in segregation principles for sensitive processes',
];
const OUTCOMES = [
  { value: 38, suffix: '%', label: 'faster cross-team cycle times', detail: 'Through integrated approvals, shared workflows, and automation-first patterns.', accent: 'cyan' },
  { value: 45, suffix: '%', label: 'improvement in process visibility', detail: 'Unified metrics spanning workforce, finance, operations, and customer execution.', accent: 'indigo' },
  { value: 62, suffix: '%', label: 'reduction in manual reporting', detail: 'Replacing spreadsheet-heavy routines with live dashboards and governed pipelines.', accent: 'amber' },
  { value: 3, suffix: 'x', label: 'faster rollout of new initiatives', detail: 'Enabled by composable modules, consistent controls, and repeatable onboarding.', accent: 'cyan' },
];
const INTEGRATIONS = [
  { name: 'Slack', category: 'Communication' }, { name: 'Google Workspace', category: 'Productivity' },
  { name: 'Salesforce', category: 'CRM' }, { name: 'QuickBooks', category: 'Finance' },
  { name: 'Zapier', category: 'Automation' }, { name: 'Microsoft Entra ID', category: 'Identity' },
  { name: 'Okta', category: 'SSO' }, { name: 'Stripe', category: 'Payments' },
];
const FAQ = [
  { question: 'Can we adopt aeos365 without replacing everything at once?', answer: 'Yes. Most enterprise customers start with priority modules and connect existing systems through phased integration and data synchronization.' },
  { question: 'How does aeos365 support governance and audit readiness?', answer: 'The platform includes role-scoped workflows, approval traceability, and centralized policy controls designed for enterprise accountability needs.' },
  { question: 'Do you support multi-entity and multi-region operations?', answer: 'Yes. The architecture supports multiple entities, localized processes, and tenant-safe boundaries to align with regional operating models.' },
  { question: 'What does implementation support look like?', answer: 'Enterprise onboarding includes discovery, pilot deployment, rollout planning, enablement sessions, and iterative optimization support.' },
];

function CheckIcon() {
  return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>;
}

function Hero() {
  const tilt = useTilt({ max: 6 });
  return (
    <section className="lv-hero lv-hero--page">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-grid-cols">
          <div className="lv-hero-copy">
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Enterprise platform</span></Reveal>
            <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">The operating system for{' '}<span className="lv-h1-grad">modern execution.</span></h1></Reveal>
            <Reveal delay={0.12}><p className="lv-lead">aeos365 helps complex organizations unify departments, standardize controls, and scale with confidence — connecting strategy to execution across business units with one modular platform built for governance and performance.</p></Reveal>
            <Reveal delay={0.18}>
              <ul className="lv-check-list">
                {PROOF.map((p) => <li key={p}><span className="lv-check lv-accent--cyan"><CheckIcon /></span>{p}</li>)}
              </ul>
            </Reveal>
            <Reveal delay={0.24}>
              <div className="lv-hero-ctas">
                <a href="/contact" className="lv-btn lv-btn--primary">Request executive demo</a>
                <a href="/pricing" className="lv-btn lv-btn--ghost">Enterprise plans</a>
              </div>
            </Reveal>
          </div>
          <motion.div ref={tilt.ref} className="lv-hero-shot lv-hero-shot--page" onMouseMove={tilt.onMove} onMouseLeave={tilt.onLeave}
            style={{ rotateX: tilt.rotateX, rotateY: tilt.rotateY }}
            initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}>
            <ThemedShot shot={SHOTS.subscription} alt="aeos365 subscription & billing" />
          </motion.div>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Capabilities() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Capabilities</span>
          <h2 className="lv-h2">Built for high-complexity{' '}<span className="lv-h2-grad">enterprise environments.</span></h2>
          <p className="lv-lead lv-feat-lead">From governance to execution, each capability reduces process friction, improves visibility, and accelerates decision-making across the organization.</p>
        </Reveal>
        <div className="lv-arch-cards lv-arch-cards--3">
          {CAPABILITIES.map((c, i) => (
            <Reveal key={c.title} delay={(i % 3) * 0.08} y={36}>
              <motion.article className={`lv-arch-card lv-arch-card--${c.accent}`} whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                <span className={`lv-cap-badge lv-icon--${c.accent}`}>{c.badge}</span>
                <h3 className="lv-arch-card-title">{c.title}</h3>
                <p className="lv-arch-card-body">{c.body}</p>
              </motion.article>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Governance() {
  return (
    <section className="lv-split-sec">
      <Container>
        <div className="lv-split">
          <Reveal className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Governance & compliance</span>
            <h2 className="lv-h2">Confidence in{' '}<span className="lv-h2-grad">regulated environments.</span></h2>
            <p className="lv-lead">Enterprise teams need more than features — they need control architecture that scales with risk, regulatory change, and stakeholder accountability. aeos365 embeds governance directly into operational workflows.</p>
          </Reveal>
          <Reveal delay={0.1} className="lv-split-panel">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Control architecture</span>
            <ul className="lv-check-list">
              {GOVERNANCE.map((g) => <li key={g}><span className="lv-check lv-accent--amber"><CheckIcon /></span>{g}</li>)}
            </ul>
          </Reveal>
        </div>
      </Container>
    </section>
  );
}

function Outcomes() {
  return (
    <section className="lv-stats">
      <div className="lv-stats-bg" style={{ backgroundImage: `url(${IMG}/stock/grad-blue.jpg)` }} aria-hidden="true" />
      <div className="lv-stats-aura" aria-hidden="true" />
      <Container>
        <Reveal className="lv-stats-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Customer outcomes</span>
          <h2 className="lv-h2">Measurable impact where it matters.</h2>
        </Reveal>
        <div className="lv-outcome-grid">
          {OUTCOMES.map((o, i) => (
            <Reveal key={o.label} delay={i * 0.08} className={`lv-outcome-card lv-feat-card--${o.accent}`}>
              <span className={`lv-outcome-num lv-accent--${o.accent}`}><CountUp value={o.value} suffix={o.suffix} /></span>
              <h3 className="lv-outcome-label">{o.label}</h3>
              <p className="lv-outcome-detail">{o.detail}</p>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Integrations() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Integrations</span>
          <h2 className="lv-h2">Connects with your existing stack.</h2>
          <p className="lv-lead lv-feat-lead">REST APIs, webhooks, and pre-built connectors make the integration story seamless with the tools your teams already rely on.</p>
        </Reveal>
        <div className="lv-int-grid">
          {INTEGRATIONS.map((it, i) => (
            <Reveal key={it.name} delay={(i % 4) * 0.05}>
              <div className="lv-int-chip">
                <span className="lv-int-name">{it.name}</span>
                <span className="lv-int-cat">{it.category}</span>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function FaqSec() {
  return (
    <section className="lv-faq">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> FAQ</span>
          <h2 className="lv-h2">Common enterprise questions.</h2>
        </Reveal>
        <div className="lv-faq-inner"><Accordion items={FAQ} /></div>
      </Container>
    </section>
  );
}

export default function Enterprise() {
  return (
    <>
      <Hero /><Capabilities /><Governance /><Outcomes /><Integrations /><FaqSec /><CtaLiving />
    </>
  );
}

Enterprise.layout = (page) => (
  <PublicLayout title="Enterprise — Mission-Critical ERP">{page}</PublicLayout>
);
