import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Container, Icon } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';

const CATS = [
  { id: 'all', label: 'All' }, { id: 'identity', label: 'Identity & SSO' },
  { id: 'comms', label: 'Communication' }, { id: 'finance', label: 'Finance' },
  { id: 'productivity', label: 'Productivity' }, { id: 'automation', label: 'Automation' },
  { id: 'data', label: 'Data & BI' },
];
const CONNECTORS = [
  { name: 'Okta', cat: 'identity', desc: 'SSO & SCIM user provisioning', accent: 'cyan', badge: 'Ok' },
  { name: 'Microsoft Entra ID', cat: 'identity', desc: 'Azure AD SSO + directory sync', accent: 'indigo', badge: 'Az' },
  { name: 'Google Workspace', cat: 'identity', desc: 'SSO, calendar & directory', accent: 'amber', badge: 'G' },
  { name: 'OneLogin', cat: 'identity', desc: 'SAML single sign-on', accent: 'cyan', badge: '1' },
  { name: 'Slack', cat: 'comms', desc: 'Approvals & notifications', accent: 'indigo', badge: 'Sl' },
  { name: 'Microsoft Teams', cat: 'comms', desc: 'Channel alerts & bots', accent: 'cyan', badge: 'Te' },
  { name: 'Twilio', cat: 'comms', desc: 'SMS & OTP delivery', accent: 'amber', badge: 'Tw' },
  { name: 'QuickBooks', cat: 'finance', desc: 'Ledger & invoice sync', accent: 'cyan', badge: 'QB' },
  { name: 'Xero', cat: 'finance', desc: 'Accounting sync', accent: 'indigo', badge: 'Xe' },
  { name: 'Stripe', cat: 'finance', desc: 'Payments & subscriptions', accent: 'amber', badge: 'St' },
  { name: 'SSLCommerz', cat: 'finance', desc: 'Regional payment gateway', accent: 'cyan', badge: 'SC' },
  { name: 'Salesforce', cat: 'productivity', desc: 'CRM two-way sync', accent: 'indigo', badge: 'Sf' },
  { name: 'HubSpot', cat: 'productivity', desc: 'Marketing & contacts', accent: 'amber', badge: 'Hs' },
  { name: 'Notion', cat: 'productivity', desc: 'Docs & knowledge base', accent: 'cyan', badge: 'No' },
  { name: 'Google Sheets', cat: 'productivity', desc: 'Live data export', accent: 'indigo', badge: 'Sh' },
  { name: 'Zapier', cat: 'automation', desc: '6,000+ app automations', accent: 'amber', badge: 'Zp' },
  { name: 'Make', cat: 'automation', desc: 'Visual workflow builder', accent: 'cyan', badge: 'Mk' },
  { name: 'Webhooks', cat: 'automation', desc: 'Real-time event delivery', accent: 'indigo', badge: '{}' },
  { name: 'Power BI', cat: 'data', desc: 'Dashboards & reporting', accent: 'amber', badge: 'Bi' },
  { name: 'Snowflake', cat: 'data', desc: 'Data warehouse export', accent: 'cyan', badge: 'Sn' },
  { name: 'Metabase', cat: 'data', desc: 'Open-source analytics', accent: 'indigo', badge: 'Mb' },
  { name: 'REST API', cat: 'data', desc: 'Full programmatic access', accent: 'amber', badge: 'v2' },
];

function Hero() {
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Integrations</span></Reveal>
          <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">Connect aeos365 to your{' '}<span className="lv-h1-grad">entire stack.</span></h1></Reveal>
          <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">Native connectors, SSO, webhooks, and a versioned REST API. Keep the tools your teams love — aeos365 becomes the operational core that ties them together.</p></Reveal>
          <Reveal delay={0.18}>
            <div className="lv-about-stats">
              {[['80+', 'Connectors'], ['REST', 'v2 API'], ['SCIM', 'Provisioning'], ['Webhooks', 'Real-time']].map(([v, l]) => (
                <div key={l} className="lv-stat"><span className="lv-stat-num" style={{ fontSize: '1.6rem' }}>{v}</span><span className="lv-stat-label">{l}</span></div>
              ))}
            </div>
          </Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Marketplace() {
  const [cat, setCat] = useState('all');
  const list = cat === 'all' ? CONNECTORS : CONNECTORS.filter((c) => c.cat === cat);
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Marketplace</span>
          <h2 className="lv-h2">Browse the{' '}<span className="lv-h2-grad">connector library.</span></h2>
        </Reveal>
        <div className="lv-mod-filters">
          {CATS.map((c) => <button key={c.id} type="button" className={`lv-mod-chip ${cat === c.id ? 'is-active' : ''}`} onClick={() => setCat(c.id)}>{c.label}</button>)}
        </div>
        <motion.div layout className="lv-int-market">
          <AnimatePresence mode="popLayout">
            {list.map((c) => (
              <motion.div key={c.name} layout initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.96 }} transition={{ duration: 0.3 }} whileHover={{ y: -4 }} className={`lv-conn-card lv-feat-card--${c.accent}`}>
                <span className={`lv-conn-logo lv-icon--${c.accent}`}>{c.badge}</span>
                <div className="lv-conn-body">
                  <span className="lv-conn-name">{c.name}</span>
                  <span className="lv-conn-desc">{c.desc}</span>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </motion.div>
      </Container>
    </section>
  );
}

function BuildYourOwn() {
  return (
    <section className="lv-split-sec">
      <Container>
        <div className="lv-split">
          <Reveal className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Developer platform</span>
            <h2 className="lv-h2">Don't see it?{' '}<span className="lv-h2-grad">Build it in an afternoon.</span></h2>
            <p className="lv-lead">Every module exposes a versioned REST API and webhook events. Sanctum-protected tokens, module-scoped permissions, and official SDKs for PHP, Node, and Python mean you can wire aeos365 into anything.</p>
            <div className="lv-hero-ctas"><a href="/docs/api" className="lv-btn lv-btn--primary">Read the API docs</a><a href="/docs" className="lv-btn lv-btn--ghost">Browse guides</a></div>
          </Reveal>
          <Reveal delay={0.1} className="lv-split-panel">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> What you get</span>
            <ul className="lv-check-list">
              {['Versioned REST API (v2) with cursor pagination', 'Webhooks for every business event', 'SCIM 2.0 user provisioning', 'Module-scoped OAuth tokens', 'SDKs: PHP · Node · Python · Laravel'].map((t) => (
                <li key={t}><span className="lv-check lv-accent--cyan"><Icon name="checkCircle" size={14} /></span>{t}</li>
              ))}
            </ul>
          </Reveal>
        </div>
      </Container>
    </section>
  );
}

export default function Integrations() {
  return (<><Hero /><Marketplace /><BuildYourOwn /><CtaLiving /></>);
}

Integrations.layout = (page) => (
  <PublicLayout title="Integrations — Connect Your Stack">{page}</PublicLayout>
);
