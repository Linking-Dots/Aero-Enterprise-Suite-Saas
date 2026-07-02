import { motion } from 'framer-motion';
import { Container } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal, CountUp } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { IMG } from './home/livingData.js';

const STATS = [
  { value: 2019, suffix: '', decimals: 0, label: 'Founded' },
  { value: 40, suffix: '+', decimals: 0, label: 'Modules shipped' },
  { value: 120, suffix: '+', decimals: 0, label: 'Countries served' },
  { value: 98, suffix: '%', decimals: 0, label: 'Customer retention' },
];
const PRINCIPLES = [
  { label: 'Purpose-built', body: 'Every module is designed for real operational workflows, not adapted from generic frameworks.', accent: 'cyan' },
  { label: 'Human-centred', body: 'Decisions are made for the person doing the work, not the admin configuring the system.', accent: 'indigo' },
  { label: 'Transparent by default', body: 'No hidden limits, opaque pricing, or gotcha upgrade walls — ever.', accent: 'amber' },
];
const VALUES = [
  { badge: '01', title: 'Integrity in everything', body: 'We say what we mean and ship what we promise. No vaporware, no bait-and-switch, no hidden costs.', accent: 'cyan' },
  { badge: '02', title: 'Radical transparency', body: 'Our roadmap is public. Our pricing is plain English. Our uptime data is live.', accent: 'indigo' },
  { badge: '03', title: 'User-first design', body: 'Every interface decision is measured against one test: does this make the work faster and less stressful?', accent: 'amber' },
  { badge: '04', title: 'Continuous innovation', body: 'We ship meaningful improvements every sprint, informed by direct customer collaboration.', accent: 'cyan' },
  { badge: '05', title: 'Customer obsession', body: 'Retention, not acquisition, is our north star. We win when customers grow using the platform.', accent: 'indigo' },
  { badge: '06', title: 'Collective ownership', body: 'Every team member owns outcomes, not just tasks. Cross-functional collaboration ships great products.', accent: 'amber' },
];
const MILESTONES = [
  { year: '2019', quarter: 'Q3', title: 'Company founded', body: 'aeos365 is established to rebuild enterprise operations software from first principles — modular, multi-tenant, and genuinely usable.', accent: 'cyan' },
  { year: '2020', quarter: 'Q1', title: 'Core HR & Payroll live', body: 'The HRM module exits private beta with 12 design-partner customers. Employee lifecycle, leave, and payroll ship as a unified suite.', accent: 'indigo' },
  { year: '2020', quarter: 'Q4', title: 'Finance & CRM modules', body: 'AP/AR, budgeting, and a full CRM pipeline ship in one release, giving early adopters a true cross-functional platform.', accent: 'amber' },
  { year: '2021', quarter: 'Q2', title: 'Multi-tenant SaaS launch', body: 'Subdomain tenant isolation, subscription billing, and the platform admin console launch — aeos365 becomes a true public SaaS.', accent: 'cyan' },
  { year: '2022', quarter: 'Q1', title: '1,000-tenant milestone', body: 'The platform crosses 1,000 active tenants across 40+ countries. Advanced analytics, audit trails, and SSO ship.', accent: 'indigo' },
  { year: '2023', quarter: 'Q3', title: 'Enterprise tier introduced', body: 'A dedicated enterprise plan launches with 99.99% SLA options, multi-entity governance, and data residency controls.', accent: 'amber' },
  { year: '2024', quarter: 'Q2', title: 'AI-assisted operations', body: 'aeos365 Assist launches — embedded summaries, smart scheduling, finance anomaly detection, and predictive leave forecasting.', accent: 'cyan' },
  { year: '2025', quarter: 'Q1', title: '40+ module platform', body: 'The platform grows to 40+ modules — from supply chain to education and real estate — serving industries we never initially planned for.', accent: 'indigo' },
];

function Hero() {
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-photo" style={{ backgroundImage: `url(${IMG}/stock/render-3d.jpg)` }} />
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> About aeos365</span></Reveal>
          <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">We build the operating layer{' '}<span className="lv-h1-grad">modern businesses deserve.</span></h1></Reveal>
          <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">Founded on one conviction: every organization — from a fast-growing startup to a global enterprise — deserves software that unifies people, processes, and data without the complexity tax.</p></Reveal>
          <Reveal delay={0.18}>
            <div className="lv-hero-ctas" style={{ justifyContent: 'center' }}>
              <a href="/signup" className="lv-btn lv-btn--primary">Start for free</a>
              <a href="/pricing" className="lv-btn lv-btn--ghost">View plans</a>
            </div>
          </Reveal>
          <Reveal delay={0.24}>
            <div className="lv-about-stats">
              {STATS.map((s) => (
                <div key={s.label} className="lv-stat">
                  <span className="lv-stat-num"><CountUp value={s.value} decimals={s.decimals} suffix={s.suffix} /></span>
                  <span className="lv-stat-label">{s.label}</span>
                </div>
              ))}
            </div>
          </Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function MissionVision() {
  return (
    <section className="lv-split-sec">
      <Container>
        <div className="lv-split lv-split--even">
          <Reveal className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Our mission</span>
            <h2 className="lv-h2">Simplify the complexity that{' '}<span className="lv-h2-grad">slows organizations down.</span></h2>
            <p className="lv-lead">Too many businesses operate across a patchwork of disconnected tools — each team solving the same coordination problem in isolation. Our mission is to eliminate that friction with one coherent, modular platform that scales with the organization, not against it.</p>
          </Reveal>
          <Reveal delay={0.1} className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Our vision</span>
            <h2 className="lv-h2">Every organization running at its{' '}<span className="lv-h2-grad">true potential.</span></h2>
            <p className="lv-lead">We envision a future where the distance between a great idea and flawless execution is measured in hours, not months — where a ten-person team operates with the same clarity as a ten-thousand-person enterprise. By 2030 we aim to power the operational backbone of 10,000 organizations across six continents.</p>
          </Reveal>
        </div>
        <div className="lv-principles">
          {PRINCIPLES.map((p, i) => (
            <Reveal key={p.label} delay={i * 0.08}>
              <div className={`lv-arch-card lv-arch-card--${p.accent}`}>
                <span className={`lv-principle-label lv-accent--${p.accent}`}>{p.label}</span>
                <p className="lv-arch-card-body">{p.body}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Values() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Core values</span>
          <h2 className="lv-h2">What we stand for,{' '}<span className="lv-h2-grad">every single day.</span></h2>
          <p className="lv-lead lv-feat-lead">Values aren't posters on a wall — they're the criteria we use when making hard trade-offs in product, hiring, and customer commitments.</p>
        </Reveal>
        <div className="lv-arch-cards lv-arch-cards--3">
          {VALUES.map((v, i) => (
            <Reveal key={v.badge} delay={(i % 3) * 0.08} y={36}>
              <motion.article className={`lv-arch-card lv-arch-card--${v.accent}`} whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                <span className={`lv-value-num lv-accent--${v.accent}`}>{v.badge}</span>
                <h3 className="lv-arch-card-title">{v.title}</h3>
                <p className="lv-arch-card-body">{v.body}</p>
              </motion.article>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Timeline() {
  return (
    <section className="lv-tl-sec">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Our journey</span>
          <h2 className="lv-h2">From a bold idea to a{' '}<span className="lv-h2-grad">global platform.</span></h2>
        </Reveal>
        <div className="lv-timeline">
          {MILESTONES.map((m, i) => (
            <Reveal key={`${m.year}-${m.quarter}`} delay={(i % 2) * 0.06} className="lv-tl-item">
              <div className="lv-tl-rail"><span className={`lv-tl-dot lv-accent--${m.accent}`} /></div>
              <div className="lv-tl-card">
                <span className={`lv-tl-year lv-accent--${m.accent}`}>{m.year} · {m.quarter}</span>
                <h3 className="lv-tl-title">{m.title}</h3>
                <p className="lv-tl-body">{m.body}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

export default function About() {
  return (<><Hero /><MissionVision /><Values /><Timeline /><CtaLiving /></>);
}

About.layout = (page) => (
  <PublicLayout title="About — aeos365">{page}</PublicLayout>
);
