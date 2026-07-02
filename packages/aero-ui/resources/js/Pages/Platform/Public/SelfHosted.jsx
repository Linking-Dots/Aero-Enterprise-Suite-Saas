import { motion } from 'framer-motion';
import { Container } from '@aero/ui';
import { LockClosedIcon, ServerStackIcon, ArrowPathIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { IMG } from './home/livingData.js';

const WHY = [
  { icon: LockClosedIcon, accent: 'cyan', title: 'Total data sovereignty', body: 'Your data never leaves your infrastructure. Meet the strictest residency, air-gap, and regulatory requirements on your own terms.' },
  { icon: ServerStackIcon, accent: 'indigo', title: 'Run it your way', body: 'Docker images, Kubernetes manifests, and deployment runbooks. Deploy on-prem, in your private cloud, or a sovereign region.' },
  { icon: ShieldCheckIcon, accent: 'amber', title: 'Your security perimeter', body: 'Bring your own database, storage, SSO, and secrets. aeos365 slots into your existing controls instead of replacing them.' },
  { icon: ArrowPathIcon, accent: 'cyan', title: 'Predictable licensing', body: 'A flat annual licence for the whole edition — no per-seat metering, no surprise usage bills as your organisation grows.' },
];
const INCLUDED = [
  'All 17+ modules — the same codebase as the SaaS edition',
  'Signed Docker images + Kubernetes Helm chart',
  'Deployment runbooks & upgrade playbooks',
  'Bring-your-own database, storage & SSO (SAML/LDAP)',
  'Air-gapped installation support',
  'Priority security patches & release channel',
];
const COMPARE = [
  ['Deployment', 'Managed by aeos365', 'Your infrastructure'],
  ['Data residency', 'Regional tenant DB', 'Fully in your control'],
  ['Updates', 'Automatic', 'You choose the window'],
  ['SSO / SAML / LDAP', 'Enterprise plan', 'Included'],
  ['Air-gapped option', '—', 'Supported'],
  ['Licensing', 'Per-seat subscription', 'Flat annual licence'],
];

function Hero() {
  return (
    <section className="lv-hero lv-hero--page">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-photo" style={{ backgroundImage: `url(${IMG}/stock/shapes-3d.jpg)` }} />
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-grid-cols">
          <div className="lv-hero-copy">
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Self-hosted edition</span></Reveal>
            <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">aeos365, on{' '}<span className="lv-h1-grad">your infrastructure.</span></h1></Reveal>
            <Reveal delay={0.12}><p className="lv-lead">The full platform — every module — deployable inside your own perimeter. For organisations where data sovereignty, air-gapping, or regulatory control aren't negotiable.</p></Reveal>
            <Reveal delay={0.18}><div className="lv-hero-ctas"><a href="/contact" className="lv-btn lv-btn--primary">Talk to sales</a><a href="/docs" className="lv-btn lv-btn--ghost">Deployment docs</a></div></Reveal>
          </div>
          <Reveal delay={0.2} className="lv-hero-shot lv-hero-shot--page">
            <pre className="lv-code"><code>{`# Pull & launch the self-hosted edition\n$ docker pull registry.aeos365.com/aeos365:latest\n$ helm install aeos365 aeos365/aeos365 \\\n    --set db.host=$YOUR_DB \\\n    --set sso.saml.enabled=true\n\n✔ 17 modules provisioned\n✔ Running at https://erp.yourcompany.internal`}</code></pre>
          </Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Why() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Why self-host</span>
          <h2 className="lv-h2">Control, without{' '}<span className="lv-h2-grad">compromise.</span></h2>
        </Reveal>
        <div className="lv-arch-cards">
          {WHY.map((w, i) => {
            const Icon = w.icon;
            return (
              <Reveal key={w.title} delay={i * 0.08} y={36}>
                <motion.article className={`lv-arch-card lv-arch-card--${w.accent}`} whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                  <span className={`lv-arch-icon lv-icon--${w.accent}`}><Icon width={24} height={24} /></span>
                  <h3 className="lv-arch-card-title">{w.title}</h3>
                  <p className="lv-arch-card-body">{w.body}</p>
                </motion.article>
              </Reveal>
            );
          })}
        </div>
      </Container>
    </section>
  );
}

function Included() {
  return (
    <section className="lv-split-sec">
      <Container>
        <div className="lv-split">
          <Reveal className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> What's included</span>
            <h2 className="lv-h2">Everything in the box,{' '}<span className="lv-h2-grad">nothing held back.</span></h2>
            <p className="lv-lead">The self-hosted edition ships the identical codebase to our SaaS — every module, every feature — packaged for your infrastructure with the tooling your platform team expects.</p>
            <div className="lv-hero-ctas"><a href="/contact" className="lv-btn lv-btn--primary">Request a quote</a></div>
          </Reveal>
          <Reveal delay={0.1} className="lv-split-panel">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Package contents</span>
            <ul className="lv-check-list">
              {INCLUDED.map((t) => <li key={t}><span className="lv-check lv-accent--cyan">✓</span>{t}</li>)}
            </ul>
          </Reveal>
        </div>
      </Container>
    </section>
  );
}

function Compare() {
  return (
    <section className="lv-cmp">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> SaaS vs self-hosted</span>
          <h2 className="lv-h2">Choose the model that fits.</h2>
        </Reveal>
        <div className="lv-cmp-scroll" style={{ maxWidth: 820, margin: '0 auto' }}>
          <table className="lv-cmp-table">
            <thead><tr><th>Capability</th><th>SaaS (cloud)</th><th>Self-hosted</th></tr></thead>
            <tbody>{COMPARE.map(([a, b, c]) => <tr key={a}><td>{a}</td><td>{b}</td><td>{c}</td></tr>)}</tbody>
          </table>
        </div>
      </Container>
    </section>
  );
}

export default function SelfHosted() {
  return (<><Hero /><Why /><Included /><Compare /><CtaLiving /></>);
}

SelfHosted.layout = (page) => (
  <PublicLayout title="Self-Hosted Edition — aeos365 on Your Infrastructure">{page}</PublicLayout>
);
