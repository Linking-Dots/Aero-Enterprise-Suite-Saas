// ─── Living data-OS — remaining Home sections ────────────────────────────────
import { useState } from 'react';
import { router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Container, Marquee } from '@aero/ui';
import { CountUp, Reveal } from './primitives.jsx';
import { IMG } from './livingData.js';
import { TRUST_LOGOS, STATS, TESTIMONIALS } from '../data/pageData.js';

const decimalsOf = (v) => (Number.isInteger(v) ? 0 : String(v).split('.')[1]?.length || 0);

// ── Trust bar ────────────────────────────────────────────────────────────────
export function TrustBarLiving() {
  return (
    <section className="lv-trust">
      <Container>
        <p className="lv-trust-label">Trusted by forward-thinking enterprises</p>
      </Container>
      <Marquee speed={34} pause>
        {TRUST_LOGOS.map((name) => (
          <span key={name} className="lv-trust-chip">{name}</span>
        ))}
      </Marquee>
    </section>
  );
}

// ── Stats band ───────────────────────────────────────────────────────────────
export function StatsBand() {
  return (
    <section className="lv-stats">
      <div className="lv-stats-bg" style={{ backgroundImage: `url(${IMG}/stock/render-3d.jpg)` }} aria-hidden="true" />
      <div className="lv-stats-aura" aria-hidden="true" />
      <Container>
        <Reveal className="lv-stats-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> By the numbers</span>
          <h2 className="lv-h2">Scale that speaks for itself.</h2>
        </Reveal>
        <div className="lv-stats-grid">
          {STATS.map((s, i) => (
            <Reveal key={s.label} delay={i * 0.08} className="lv-stat">
              <span className="lv-stat-num">
                <CountUp value={s.value} decimals={decimalsOf(s.value)} prefix={s.prefix} suffix={s.suffix} />
              </span>
              <span className="lv-stat-label">{s.label}</span>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

// ── Testimonials ─────────────────────────────────────────────────────────────
export function TestimonialsLiving() {
  return (
    <section className="lv-tst">
      <Container>
        <Reveal className="lv-tst-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Customer stories</span>
          <h2 className="lv-h2">Teams that demand{' '}<span className="lv-h2-grad">real results.</span></h2>
          <p className="lv-lead lv-tst-lead">
            From HR teams handling thousands of employees to CTOs rebuilding their operational
            stack — outcomes from organisations running on aeos365.
          </p>
        </Reveal>
        <div className="lv-tst-grid">
          {TESTIMONIALS.map((t, i) => (
            <Reveal key={t.id} delay={(i % 2) * 0.08}>
              <motion.figure className="lv-tst-card" whileHover={{ y: -5 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}>
                <div className="lv-tst-stars" aria-label={`${t.rating} out of 5`}>
                  {Array.from({ length: t.rating }).map((_, k) => (
                    <svg key={k} width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3 6.5 7 .9-5 4.8 1.3 7L12 17.8 5.4 21.2 6.7 14.2l-5-4.8 7-.9z" /></svg>
                  ))}
                </div>
                <blockquote className="lv-tst-quote">"{t.quote}"</blockquote>
                <figcaption className="lv-tst-attr">
                  <span className="lv-tst-avatar" style={{ background: t.avatarColor }}>{t.avatar}</span>
                  <span className="lv-tst-who">
                    <span className="lv-tst-name">{t.name}</span>
                    <span className="lv-tst-role">{t.role} · {t.company}</span>
                  </span>
                </figcaption>
              </motion.figure>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

// ── Final CTA ────────────────────────────────────────────────────────────────
export function CtaLiving() {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  function handleSubmit(e) {
    e.preventDefault();
    if (!email) return;
    router.post('/waitlist', { email }, { preserveState: true, onSuccess: () => setSubmitted(true) });
  }

  return (
    <section className="lv-cta">
      <div className="lv-cta-aura lv-cta-aura--1" aria-hidden="true" />
      <div className="lv-cta-aura lv-cta-aura--2" aria-hidden="true" />
      <div className="lv-cta-grid-bg" aria-hidden="true" />
      <Container>
        <Reveal className="lv-cta-inner">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Get started today</span>
          <h2 className="lv-h2 lv-cta-title">
            Ready to unify your enterprise?{' '}
            <span className="lv-h2-grad">Start in minutes.</span>
          </h2>
          <p className="lv-lead lv-cta-lead">
            Join 320+ enterprise clients who replaced a patchwork of tools with one coherent
            platform. 14-day free trial, no credit card required.
          </p>

          {submitted ? (
            <p className="lv-cta-done">You're on the list — we'll be in touch shortly.</p>
          ) : (
            <form className="lv-cta-form" onSubmit={handleSubmit} noValidate>
              <input
                type="email" placeholder="Work email address" aria-label="Work email address"
                className="lv-cta-input" value={email} onChange={(e) => setEmail(e.target.value)}
              />
              <button type="submit" className="lv-btn lv-btn--primary">
                Start free trial
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
              </button>
            </form>
          )}
          <div className="lv-cta-links">
            <a href="/pricing" className="lv-cta-link">View pricing</a>
            <a href="https://demo.aeos365.com" className="lv-cta-link">Live demo</a>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}
