import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Container } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal, ThemedShot, useTilt } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { SHOTS, IMG } from './home/livingData.js';
import { MODULES, PLATFORM_PILLARS, MODULE_CATEGORIES } from './data/pageData.js';

function accentOf(color = '') {
  if (color.includes('indigo')) return 'indigo';
  if (color.includes('amber')) return 'amber';
  return 'cyan';
}

// ── Hero ─────────────────────────────────────────────────────────
function FeaturesHero() {
  const tilt = useTilt({ max: 6 });
  return (
    <section className="lv-hero lv-hero--page">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" />
        <div className="lv-hero-aura lv-hero-aura--2" />
        <div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-grid-cols">
          <div className="lv-hero-copy">
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> The platform</span></Reveal>
            <Reveal delay={0.06}>
              <h1 className="lv-h1 lv-h1--page">
                Every module your{' '}
                <span className="lv-h1-grad">enterprise needs.</span>
              </h1>
            </Reveal>
            <Reveal delay={0.12}>
              <p className="lv-lead">
                17+ purpose-built modules across every operational domain — HR, payroll, finance,
                supply chain, quality, and AI assistance. One platform, one tenant context,
                infinite configurations.
              </p>
            </Reveal>
            <Reveal delay={0.18}>
              <div className="lv-hero-ctas">
                <a href="https://demo.aeos365.com" className="lv-btn lv-btn--primary">Try the live demo</a>
                <a href="/pricing" className="lv-btn lv-btn--ghost">View pricing</a>
              </div>
            </Reveal>
          </div>
          <motion.div
            ref={tilt.ref} className="lv-hero-shot lv-hero-shot--page"
            onMouseMove={tilt.onMove} onMouseLeave={tilt.onLeave}
            style={{ rotateX: tilt.rotateX, rotateY: tilt.rotateY }}
            initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}
          >
            <ThemedShot shot={SHOTS.leave} alt="aeos365 leave calendar module" />
          </motion.div>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

// ── Module explorer (filterable) ─────────────────────────────────
function ModuleExplorer() {
  const [cat, setCat] = useState('all');
  const list = cat === 'all' ? MODULES : MODULES.filter((m) => m.category === cat);

  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> All modules</span>
          <h2 className="lv-h2">Explore the whole suite.</h2>
          <p className="lv-lead lv-feat-lead">Filter by domain. Every module ships with HRMAC access control, audit trails, and its own isolated data.</p>
        </Reveal>

        <div className="lv-mod-filters">
          {MODULE_CATEGORIES.map((c) => (
            <button
              key={c.id} type="button"
              className={`lv-mod-chip ${cat === c.id ? 'is-active' : ''}`}
              onClick={() => setCat(c.id)}
            >
              {c.label}
            </button>
          ))}
        </div>

        <motion.div layout className="lv-mod-grid">
          <AnimatePresence mode="popLayout">
            {list.map((m) => {
              const Icon = m.icon;
              const accent = accentOf(m.accentColor);
              return (
                <motion.article
                  key={m.id} layout
                  initial={{ opacity: 0, scale: 0.96 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.96 }}
                  transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
                  whileHover={{ y: -5 }}
                  className={`lv-mod-card lv-feat-card--${accent}`}
                >
                  <div className="lv-mod-top">
                    <span className={`lv-feat-icon lv-icon--${accent}`}><Icon width={22} height={22} /></span>
                    {m.stat && <span className={`lv-mod-stat lv-accent--${accent}`}>{m.stat.value} <i>{m.stat.label}</i></span>}
                  </div>
                  <h3 className="lv-feat-name">{m.label}</h3>
                  <p className="lv-feat-desc">{m.tagline}</p>
                  <ul className="lv-mod-highlights">
                    {m.highlights.slice(0, 4).map((h) => (
                      <li key={h}><span className={`lv-mod-tick lv-accent--${accent}`}>✓</span>{h}</li>
                    ))}
                  </ul>
                </motion.article>
              );
            })}
          </AnimatePresence>
        </motion.div>
      </Container>
    </section>
  );
}

// ── Platform pillars ─────────────────────────────────────────────
function Pillars() {
  return (
    <section className="lv-arch lv-arch--flat">
      <div className="lv-arch-photo" style={{ backgroundImage: `url(${IMG}/stock/shapes-3d.jpg)` }} aria-hidden="true" />
      <div className="lv-arch-grid-bg" aria-hidden="true" />
      <Container>
        <Reveal className="lv-arch-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Architecture</span>
          <h2 className="lv-h2 lv-arch-title">Principles that{' '}<span className="lv-h2-grad">scale with you.</span></h2>
          <p className="lv-lead lv-arch-lead">The architecture isn't an afterthought — it's the product. Six pillars that make every module trustworthy, extensible, and enterprise-ready.</p>
        </Reveal>
        <div className="lv-arch-cards lv-arch-cards--3">
          {PLATFORM_PILLARS.map((p, i) => {
            const Icon = p.icon; const accent = accentOf(p.accentColor);
            return (
              <Reveal key={p.title} delay={(i % 3) * 0.08} y={36}>
                <motion.article className={`lv-arch-card lv-arch-card--${accent}`} whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                  <span className={`lv-arch-icon lv-icon--${accent}`}><Icon width={24} height={24} /></span>
                  <h3 className="lv-arch-card-title">{p.title}</h3>
                  <p className="lv-arch-card-body">{p.body}</p>
                </motion.article>
              </Reveal>
            );
          })}
        </div>
      </Container>
    </section>
  );
}

export default function Features() {
  return (
    <>
      <FeaturesHero />
      <ModuleExplorer />
      <Pillars />
      <CtaLiving />
    </>
  );
}

Features.layout = (page) => (
  <PublicLayout title="Features — 17+ Enterprise Modules">{page}</PublicLayout>
);
