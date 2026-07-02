import { motion } from 'framer-motion';
import { Container } from '@aero/ui';
import { UserGroupIcon, CurrencyDollarIcon, CubeIcon, BeakerIcon, ChartBarIcon } from '@heroicons/react/24/outline';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal, ThemedShot, useTilt } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { SHOTS } from './home/livingData.js';
import { MODULES } from './data/pageData.js';

const DOMAINS = [
  { id: 'people', icon: UserGroupIcon, accent: 'cyan', title: 'People & HR', outcome: 'Hire, pay, and grow your workforce in one auditable system — from onboarding to performance.' },
  { id: 'finance', icon: CurrencyDollarIcon, accent: 'indigo', title: 'Finance', outcome: 'Close the books faster with a real-time general ledger, multi-currency, and automated payroll.' },
  { id: 'operations', icon: CubeIcon, accent: 'amber', title: 'Operations', outcome: 'Run projects, inventory, supply chain, and point of sale end to end — no blind spots.' },
  { id: 'quality', icon: BeakerIcon, accent: 'cyan', title: 'Quality & Safety', outcome: 'Ship to spec with inspections, HSE permits, RFI workflows, and a complete audit trail.' },
  { id: 'intelligence', icon: ChartBarIcon, accent: 'amber', title: 'Intelligence', outcome: 'Turn every module into board-ready, AI-assisted insight with role-gated live dashboards.' },
];

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
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Solutions</span></Reveal>
            <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">One platform,{' '}<span className="lv-h1-grad">every team's job to be done.</span></h1></Reveal>
            <Reveal delay={0.12}><p className="lv-lead">Whether you lead people, finance, operations, or the whole company — aeos365 assembles the exact modules your team needs on one shared tenant, one login, one source of truth.</p></Reveal>
            <Reveal delay={0.18}><div className="lv-hero-ctas"><a href="/signup" className="lv-btn lv-btn--primary">Start for free</a><a href="/features" className="lv-btn lv-btn--ghost">See all modules</a></div></Reveal>
          </div>
          <motion.div ref={tilt.ref} className="lv-hero-shot lv-hero-shot--page" onMouseMove={tilt.onMove} onMouseLeave={tilt.onLeave}
            style={{ rotateX: tilt.rotateX, rotateY: tilt.rotateY }} initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}>
            <ThemedShot shot={SHOTS.employees} alt="aeos365 people module" />
          </motion.div>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Domains() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> By domain</span>
          <h2 className="lv-h2">Solutions for{' '}<span className="lv-h2-grad">every function.</span></h2>
          <p className="lv-lead lv-feat-lead">Each domain bundles purpose-built modules that share one authentication and tenant context — subscribe to a domain today, expand into the rest without a migration.</p>
        </Reveal>
        <div className="lv-solution-list">
          {DOMAINS.map((d, i) => {
            const Icon = d.icon;
            const mods = MODULES.filter((m) => m.category === d.id);
            return (
              <Reveal key={d.id} delay={(i % 2) * 0.06}>
                <div className={`lv-solution-card lv-feat-card--${d.accent}`}>
                  <div className="lv-solution-head">
                    <span className={`lv-arch-icon lv-icon--${d.accent}`}><Icon width={24} height={24} /></span>
                    <div>
                      <h3 className="lv-solution-title">{d.title}</h3>
                      <p className="lv-solution-outcome">{d.outcome}</p>
                    </div>
                  </div>
                  <div className="lv-solution-mods">
                    {mods.map((m) => {
                      const MIcon = m.icon;
                      return (
                        <span key={m.id} className="lv-solution-chip">
                          <MIcon width={15} height={15} /> {m.label}
                        </span>
                      );
                    })}
                  </div>
                </div>
              </Reveal>
            );
          })}
        </div>
      </Container>
    </section>
  );
}

export default function Solutions() {
  return (<><Hero /><Domains /><CtaLiving /></>);
}

Solutions.layout = (page) => (
  <PublicLayout title="Solutions — For Every Team">{page}</PublicLayout>
);
