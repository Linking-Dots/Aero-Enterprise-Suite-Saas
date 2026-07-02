// ─── Living data-OS — Features (module bento showcase) ───────────────────────
import { motion } from 'framer-motion';
import { Container } from '@aero/ui';
import { Reveal } from './primitives.jsx';
import { FEATURES } from '../data/pageData.js';

export default function FeaturesLiving() {
  return (
    <section className="lv-feat">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> The platform</span>
          <h2 className="lv-h2 lv-feat-title">
            17+ enterprise modules.{' '}
            <span className="lv-h2-grad">One coherent system.</span>
          </h2>
          <p className="lv-lead lv-feat-lead">
            Every module is independently scoped yet shares one authentication and tenant
            context. Subscribe to what you need today, scale into the rest without a migration.
          </p>
        </Reveal>

        <div className="lv-feat-bento">
          {FEATURES.map((f, i) => {
            const Icon = f.icon;
            return (
              <Reveal
                key={f.id}
                delay={(i % 4) * 0.06}
                className={`lv-feat-cell ${f.size === 'large' ? 'lv-feat-cell--lg' : ''}`}
              >
                <motion.article
                  className={`lv-feat-card lv-feat-card--${f.accent}`}
                  whileHover={{ y: -6 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 22 }}
                >
                  <span className={`lv-feat-icon lv-icon--${f.accent}`}>
                    <Icon width={22} height={22} />
                  </span>
                  <div className="lv-feat-body">
                    <span className="lv-feat-label">{f.label}</span>
                    <h3 className="lv-feat-name">{f.title}</h3>
                    <p className="lv-feat-desc">{f.description}</p>
                  </div>
                  {f.stat && <span className={`lv-feat-stat lv-accent--${f.accent}`}>{f.stat}</span>}
                  <span className="lv-feat-glow" aria-hidden="true" />
                </motion.article>
              </Reveal>
            );
          })}
        </div>

        <Reveal className="lv-feat-foot" delay={0.1}>
          <a href="/features" className="lv-btn lv-btn--ghost">
            Explore all modules
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
          </a>
        </Reveal>
      </Container>
    </section>
  );
}
