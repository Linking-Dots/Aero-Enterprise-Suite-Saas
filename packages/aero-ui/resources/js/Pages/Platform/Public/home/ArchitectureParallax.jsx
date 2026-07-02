// ─── Living data-OS — Signature §2: parallax architecture pillars ─────────────
import { useRef } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';
import { Container } from '@aero/ui';
import { Reveal } from './primitives.jsx';
import { ARCH_PILLARS, IMG } from './livingData.js';

export default function ArchitectureParallax() {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ['start end', 'end start'],
  });
  const gridY = useTransform(scrollYProgress, [0, 1], ['-6%', '10%']);
  const photoY = useTransform(scrollYProgress, [0, 1], ['-12%', '14%']);
  const auraY = useTransform(scrollYProgress, [0, 1], ['12%', '-12%']);

  return (
    <section className="lv-arch" ref={ref}>
      {/* Parallax background layers (each moves at a different rate = depth) */}
      <motion.div
        className="lv-arch-photo"
        style={{ y: photoY, backgroundImage: `url(${IMG}/stock/cyber-net.jpg)` }}
        aria-hidden="true"
      />
      <motion.div className="lv-arch-grid-bg" style={{ y: gridY }} aria-hidden="true" />
      <motion.div className="lv-arch-aura" style={{ y: auraY }} aria-hidden="true" />

      <Container>
        <Reveal className="lv-arch-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> The architecture</span>
          <h2 className="lv-h2 lv-arch-title">
            Not a monolith bolted together.{' '}
            <span className="lv-h2-grad">An operating system.</span>
          </h2>
          <p className="lv-lead lv-arch-lead">
            Four foundations carry every module in aeos365 — the same guarantees whether
            you run one entity or a thousand tenants.
          </p>
        </Reveal>

        <div className="lv-arch-flow" aria-hidden="true">
          <svg viewBox="0 0 1200 40" preserveAspectRatio="none" width="100%" height="40">
            <defs>
              <linearGradient id="lvFlow" x1="0" x2="1" y1="0" y2="0">
                <stop offset="0%" stopColor="var(--aeos-primary)" stopOpacity="0" />
                <stop offset="50%" stopColor="var(--aeos-primary)" stopOpacity="0.9" />
                <stop offset="100%" stopColor="var(--aeos-tertiary,#6366F1)" stopOpacity="0" />
              </linearGradient>
            </defs>
            <line x1="0" y1="20" x2="1200" y2="20" stroke="var(--aeos-divider)" strokeWidth="1" />
            <line className="lv-flow-pulse" x1="0" y1="20" x2="1200" y2="20" stroke="url(#lvFlow)" strokeWidth="2" />
          </svg>
        </div>

        <div className="lv-arch-cards">
          {ARCH_PILLARS.map((p, i) => {
            const Icon = p.icon;
            return (
              <Reveal key={p.id} delay={i * 0.1} y={40}>
                <motion.article
                  className={`lv-arch-card lv-arch-card--${p.accent}`}
                  whileHover={{ y: -6 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 22 }}
                >
                  <span className={`lv-arch-icon lv-icon--${p.accent}`}>
                    <Icon width={24} height={24} />
                  </span>
                  <h3 className="lv-arch-card-title">{p.title}</h3>
                  <p className="lv-arch-card-body">{p.body}</p>
                  <span className="lv-arch-card-index">0{i + 1}</span>
                </motion.article>
              </Reveal>
            );
          })}
        </div>
      </Container>
    </section>
  );
}
