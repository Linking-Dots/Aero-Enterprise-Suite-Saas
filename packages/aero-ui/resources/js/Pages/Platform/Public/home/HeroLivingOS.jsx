// ─── Living data-OS — Hero ────────────────────────────────────────────────────
import { useRef } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';
import { Container } from '@aero/ui';
import { CountUp, useTilt, ThemedShot, ExtractedCard, Reveal } from './primitives.jsx';
import { HERO_KPIS, HERO_CARDS, CONSTELLATION, SHOTS, IMG } from './livingData.js';

// Orbiting module constellation around a glowing core
function Constellation() {
  const inner = CONSTELLATION.filter((n) => n.ring === 0);
  const outer = CONSTELLATION.filter((n) => n.ring === 1);

  const Ring = ({ nodes, radius, dur, reverse }) => (
    <div
      className={`lv-orbit ${reverse ? 'lv-orbit--rev' : ''}`}
      style={{ '--orbit-dur': `${dur}s`, width: radius * 2, height: radius * 2 }}
    >
      <span className="lv-orbit-path" aria-hidden="true" />
      {nodes.map((n) => {
        const Icon = n.icon;
        const rad = (n.angle * Math.PI) / 180;
        const x = Math.cos(rad) * radius;
        const y = Math.sin(rad) * radius;
        return (
          <div
            key={n.id}
            className={`lv-node lv-node--${n.accent}`}
            style={{ transform: `translate(-50%,-50%) translate(${x}px, ${y}px)` }}
          >
            <span className="lv-node-spin">
              <Icon width={20} height={20} />
            </span>
          </div>
        );
      })}
    </div>
  );

  return (
    <div className="lv-constellation" aria-hidden="true">
      <Ring nodes={outer} radius={190} dur={64} reverse />
      <Ring nodes={inner} radius={110} dur={44} />
      <div className="lv-core">
        <span className="lv-core-glyph">365</span>
      </div>
    </div>
  );
}

export default function HeroLivingOS() {
  const heroRef = useRef(null);
  const { scrollYProgress } = useScroll({
    target: heroRef,
    offset: ['start start', 'end start'],
  });
  const visualY = useTransform(scrollYProgress, [0, 1], [0, 90]);
  const bgY = useTransform(scrollYProgress, [0, 1], [0, 140]);
  const shotScale = useTransform(scrollYProgress, [0, 1], [1, 1.04]);
  const tilt = useTilt({ max: 7 });

  return (
    <section className="lv-hero" ref={heroRef}>
      {/* Background layers */}
      <motion.div className="lv-hero-bg" style={{ y: bgY }} aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" />
        <div className="lv-hero-aura lv-hero-aura--2" />
        <div
          className="lv-hero-photo"
          style={{ backgroundImage: `url(${IMG}/stock/grad-3d.jpg)` }}
        />
        <div className="lv-hero-grid" />
      </motion.div>

      <Container>
        <div className="lv-hero-grid-cols">
          {/* Copy */}
          <div className="lv-hero-copy">
            <Reveal delay={0}>
              <span className="lv-eyebrow">
                <span className="lv-eyebrow-dot" />
                Enterprise Resource Planning · Multi-tenant SaaS
              </span>
            </Reveal>
            <Reveal delay={0.06}>
              <h1 className="lv-h1">
                One platform.<br />
                <span className="lv-h1-grad">Every module, alive.</span>
              </h1>
            </Reveal>
            <Reveal delay={0.12}>
              <p className="lv-lead">
                aeos365 unifies HR, payroll, finance, CRM, inventory, supply chain,
                projects and 10+ more into one coherent system — with a database of
                your own and sovereignty over every record.
              </p>
            </Reveal>
            <Reveal delay={0.18}>
              <div className="lv-hero-ctas">
                <a href="https://demo.aeos365.com" className="lv-btn lv-btn--primary">
                  Try the live demo
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
                <a href="/signup" className="lv-btn lv-btn--ghost">Start free trial</a>
              </div>
            </Reveal>
            <Reveal delay={0.24}>
              <div className="lv-hero-kpis">
                {HERO_KPIS.map((k) => (
                  <div key={k.label} className="lv-kpi">
                    <span className="lv-kpi-num">
                      <CountUp value={k.value} decimals={k.decimals} suffix={k.suffix} />
                    </span>
                    <span className="lv-kpi-label">{k.label}</span>
                  </div>
                ))}
              </div>
            </Reveal>
          </div>

          {/* Visual */}
          <motion.div className="lv-hero-visual" style={{ y: visualY }}>
            <Constellation />
            <motion.div
              ref={tilt.ref}
              className="lv-hero-shot"
              onMouseMove={tilt.onMove}
              onMouseLeave={tilt.onLeave}
              style={{ rotateX: tilt.rotateX, rotateY: tilt.rotateY, scale: shotScale }}
              initial={{ opacity: 0, y: 40 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}
            >
              <ThemedShot shot={SHOTS.dashboard} alt="aeos365 platform dashboard" />
            </motion.div>

            {/* Floating extracted UI cards (legible, theme-reactive) */}
            <motion.div
              className="lv-hero-card lv-hero-card--tl"
              initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.7, delay: 0.6, ease: [0.16, 1, 0.3, 1] }}
            >
              <ExtractedCard {...HERO_CARDS[0]} />
            </motion.div>
            <motion.div
              className="lv-hero-card lv-hero-card--br"
              initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.7, delay: 0.78, ease: [0.16, 1, 0.3, 1] }}
            >
              <ExtractedCard {...HERO_CARDS[2]} />
            </motion.div>
          </motion.div>
        </div>
      </Container>

      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}
