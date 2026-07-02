import { useState } from 'react';
import { motion } from 'framer-motion';
import { Container, Icon } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';

const STATS = [{ value: '500+', label: 'Articles' }, { value: '40+', label: 'Modules covered' }, { value: 'REST', label: 'API docs' }, { value: 'Live', label: 'Code samples' }];
const CATEGORIES = [
  { badge: '01', title: 'Getting started', desc: 'Install, configure, and launch your first tenant in under 15 minutes.', articles: 24, accent: 'cyan', icon: 'cube' },
  { badge: '02', title: 'HRM module', desc: 'Employee lifecycle, leave policies, payroll config, attendance, and HR analytics.', articles: 86, accent: 'indigo', icon: 'users' },
  { badge: '03', title: 'CRM module', desc: 'Pipeline management, deal tracking, contacts, activity logging, and sales automation.', articles: 62, accent: 'amber', icon: 'chart' },
  { badge: '04', title: 'Finance module', desc: 'Chart of accounts, AP/AR, budgeting, multi-currency, tax rules, and reporting.', articles: 74, accent: 'cyan', icon: 'database' },
  { badge: '05', title: 'Platform API', desc: 'REST references, authentication flows, rate limits, webhooks, and SDKs.', articles: 48, accent: 'indigo', icon: 'link' },
  { badge: '06', title: 'Integrations', desc: 'Connect with Slack, Google Workspace, Zapier, QuickBooks, Salesforce, and 80+ services.', articles: 55, accent: 'amber', icon: 'puzzle' },
  { badge: '07', title: 'Security & compliance', desc: 'RBAC, audit logs, GDPR handling, SSO/SAML setup, and data residency.', articles: 38, accent: 'cyan', icon: 'shield' },
  { badge: '08', title: 'Billing & plans', desc: 'Subscription management, plan upgrades, usage-based billing, and invoices.', articles: 29, accent: 'indigo', icon: 'document' },
];
const QUICK = [
  { step: '01', title: 'Create your workspace', body: 'Sign up and provision your tenant. Your isolated database is ready in under 60 seconds.', accent: 'cyan' },
  { step: '02', title: 'Configure your modules', body: 'Enable the modules you need from the admin console — each has its own configuration panel.', accent: 'indigo' },
  { step: '03', title: 'Invite your team', body: 'Use HRMAC to define roles, then invite users. They get access only to what they need.', accent: 'amber' },
  { step: '04', title: 'Connect your data', body: 'Import via CSV or the REST API, and use webhooks to sync with external systems.', accent: 'cyan' },
];
const ARTICLES = [
  { title: 'Setting up your first payroll run', module: 'HRM', readTime: '8 min' },
  { title: 'HRMAC: configuring the 4-level permission hierarchy', module: 'Platform', readTime: '12 min' },
  { title: 'Authenticating with the aeos365 REST API', module: 'API', readTime: '5 min' },
  { title: 'Multi-currency invoice configuration', module: 'Finance', readTime: '7 min' },
  { title: 'Slack notifications for leave approvals', module: 'Integrations', readTime: '4 min' },
  { title: 'Configuring the Kanban CRM pipeline', module: 'CRM', readTime: '6 min' },
];
const CHANGELOG = [
  { version: 'v2.14.0', date: 'Apr 28, 2026', note: 'AI Assist — payroll anomaly detection + leave pattern forecasting.', type: 'feature' },
  { version: 'v2.13.0', date: 'Apr 12, 2026', note: 'Supply chain module — goods receipt & inspection workflows.', type: 'feature' },
  { version: 'v2.12.1', date: 'Apr 5, 2026', note: 'Bug fix — leave balance calculation for partial months.', type: 'fix' },
  { version: 'v2.12.0', date: 'Mar 28, 2026', note: 'API v2 — cursor pagination + improved rate-limit headers.', type: 'feature' },
];

function Hero() {
  const [q, setQ] = useState('');
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> aeos365 documentation</span></Reveal>
          <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">Everything you need to{' '}<span className="lv-h1-grad">build with aeos365.</span></h1></Reveal>
          <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">Guides, API references, quick-start tutorials, and integration recipes — organized by module so you can go from onboarding to production in hours, not days.</p></Reveal>
          <Reveal delay={0.18}>
            <div className="lv-docsearch">
              <Icon name="search" size={18} />
              <input type="search" placeholder="Search documentation, guides, and API references…" value={q} onChange={(e) => setQ(e.target.value)} />
            </div>
          </Reveal>
          <Reveal delay={0.24}>
            <div className="lv-about-stats">
              {STATS.map((s) => <div key={s.label} className="lv-stat"><span className="lv-stat-num" style={{ fontSize: '1.6rem' }}>{s.value}</span><span className="lv-stat-label">{s.label}</span></div>)}
            </div>
          </Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Categories() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Browse by category</span>
          <h2 className="lv-h2">Docs for{' '}<span className="lv-h2-grad">every module.</span></h2>
          <p className="lv-lead lv-feat-lead">From core HR to enterprise API — every feature has its own documentation track with examples, snippets, and migration guides.</p>
        </Reveal>
        <div className="lv-doc-grid">
          {CATEGORIES.map((c, i) => (
            <Reveal key={c.title} delay={(i % 4) * 0.06}>
              <motion.a href="#" className={`lv-arch-card lv-arch-card--${c.accent} lv-doc-card`} whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                <span className={`lv-arch-icon lv-icon--${c.accent}`}><Icon name={c.icon} size={22} /></span>
                <h3 className="lv-arch-card-title">{c.title}</h3>
                <p className="lv-arch-card-body">{c.desc}</p>
                <span className="lv-doc-foot"><span className="lv-doc-count">{c.articles} articles</span><span className={`lv-accent--${c.accent}`}>→</span></span>
              </motion.a>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function QuickStart() {
  return (
    <section className="lv-split-sec">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Quick start</span>
          <h2 className="lv-h2">From zero to running in{' '}<span className="lv-h2-grad">four steps.</span></h2>
        </Reveal>
        <div className="lv-arch-cards">
          {QUICK.map((s, i) => (
            <Reveal key={s.step} delay={i * 0.08} y={36}>
              <div className={`lv-arch-card lv-arch-card--${s.accent}`}>
                <span className={`lv-value-num lv-accent--${s.accent}`}>{s.step}</span>
                <h3 className="lv-arch-card-title">{s.title}</h3>
                <p className="lv-arch-card-body">{s.body}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Popular() {
  return (
    <section className="lv-list-sec">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Popular</span>
          <h2 className="lv-h2">Most-read documentation.</h2>
        </Reveal>
        <div className="lv-list">
          {ARTICLES.map((a, i) => (
            <Reveal key={a.title} delay={(i % 3) * 0.05}>
              <a href="#" className="lv-list-row">
                <span className="lv-list-main">
                  <span className="lv-list-title">{a.title}</span>
                  <span className="lv-list-meta"><span className="lv-list-tag">{a.module}</span> · {a.readTime}</span>
                </span>
                <Icon name="arrowRight" size={18} className="lv-accent--cyan" />
              </a>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function Changelog() {
  return (
    <section className="lv-list-sec lv-list-sec--alt">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Changelog</span>
          <h2 className="lv-h2">What's new in aeos365.</h2>
        </Reveal>
        <div className="lv-list">
          {CHANGELOG.map((e, i) => (
            <Reveal key={e.version} delay={(i % 3) * 0.05}>
              <div className="lv-list-row">
                <span className="lv-list-main">
                  <span className="lv-list-title"><span className="lv-accent--cyan" style={{ fontFamily: 'var(--aeos-font-mono)' }}>{e.version}</span> — {e.note}</span>
                  <span className="lv-list-meta">{e.date}</span>
                </span>
                <span className={`lv-clog-badge lv-clog-badge--${e.type}`}>{e.type}</span>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

export default function Docs() {
  return (<><Hero /><Categories /><QuickStart /><Popular /><Changelog /><CtaLiving /></>);
}

Docs.layout = (page) => (
  <PublicLayout title="Documentation — aeos365">{page}</PublicLayout>
);
