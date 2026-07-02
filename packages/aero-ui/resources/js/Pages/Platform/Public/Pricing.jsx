import { Fragment, useState } from 'react';
import { motion } from 'framer-motion';
import { Container, Accordion, Icon } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';
import { PRICING_PLANS, COMPARISON_CATEGORIES, PRICING_FAQ } from './data/pageData.js';

function accentOf(color = '') {
  if (color.includes('indigo')) return 'indigo';
  if (color.includes('amber')) return 'amber';
  return 'cyan';
}

// Drop trailing .00 and add thousands separators
function fmtPrice(n) {
  const v = Number(n);
  if (Number.isNaN(v)) return n;
  return Number.isInteger(v) ? v.toLocaleString('en-US') : v.toString();
}

// ── Hero + billing toggle ────────────────────────────────────────
function PricingHero({ isAnnual, onToggle }) {
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" />
        <div className="lv-hero-aura lv-hero-aura--2" />
        <div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Pricing</span></Reveal>
          <Reveal delay={0.06}>
            <h1 className="lv-h1 lv-h1--page">Simple, transparent{' '}<span className="lv-h1-grad">pricing.</span></h1>
          </Reveal>
          <Reveal delay={0.12}>
            <p className="lv-lead lv-hero-centered-lead">
              Every plan starts with a 14-day free trial — no credit card required. Your data
              lives in its own isolated database, regardless of plan.
            </p>
          </Reveal>
          <Reveal delay={0.18}>
            <div className="lv-billing-toggle" role="tablist" aria-label="Billing period">
              <button role="tab" aria-selected={!isAnnual} className={`lv-billing-opt ${!isAnnual ? 'is-active' : ''}`} onClick={() => onToggle(false)}>Monthly</button>
              <button role="tab" aria-selected={isAnnual} className={`lv-billing-opt ${isAnnual ? 'is-active' : ''}`} onClick={() => onToggle(true)}>
                Annual <span className="lv-billing-save">save ~20%</span>
              </button>
            </div>
          </Reveal>
        </div>
      </Container>
    </section>
  );
}

// ── Plans ─────────────────────────────────────────────────────────
function Plans({ plans, isAnnual }) {
  return (
    <section className="lv-price">
      <Container>
        <div className="lv-price-grid">
          {plans.map((p, i) => {
            const accent = accentOf(p.accentColor);
            const price = isAnnual ? p.annualPrice : p.monthlyPrice;
            const href = p.id === 'enterprise' ? '/contact' : '/signup';
            return (
              <Reveal key={p.id} delay={i * 0.06}>
                <motion.article
                  className={`lv-price-card ${p.highlight ? 'lv-price-card--hl' : ''} lv-feat-card--${accent}`}
                  whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 22 }}
                >
                  {p.badge && <span className="lv-price-badge">{p.badge}</span>}
                  <span className="lv-price-name">{p.name}</span>
                  <p className="lv-price-tagline">{p.tagline}</p>
                  <div className="lv-price-amount">
                    {price == null ? (
                      <span className="lv-price-custom">Custom</span>
                    ) : (
                      <>
                        <span className="lv-price-cur">{Number(price) === 0 ? '' : p.currency}</span>
                        <span className="lv-price-num">{Number(price) === 0 ? 'Free' : fmtPrice(price)}</span>
                        {Number(price) !== 0 && <span className="lv-price-per">/mo</span>}
                      </>
                    )}
                  </div>
                  <span className="lv-price-billed">
                    {price == null ? 'Tailored to your scale' : isAnnual ? 'billed annually' : 'billed monthly'}
                  </span>
                  <span className="lv-price-meta">{p.users} · {p.subsidiaries}</span>
                  <a href={href} className={`lv-btn ${p.highlight ? 'lv-btn--primary' : 'lv-btn--ghost'} lv-price-cta`}>{p.cta}</a>
                  <ul className="lv-price-perks">
                    {p.perks.map((perk) => (
                      <li key={perk}><span className={`lv-mod-tick lv-accent--${accent}`}>✓</span>{perk}</li>
                    ))}
                  </ul>
                </motion.article>
              </Reveal>
            );
          })}
        </div>
      </Container>
    </section>
  );
}

// ── Comparison table ─────────────────────────────────────────────
function Comparison() {
  const cell = (v) => (v === true
    ? <Icon name="check" size={17} className="lv-accent--cyan" />
    : v === false ? <span className="lv-cmp-dash">—</span> : <span>{v}</span>);
  return (
    <section className="lv-cmp">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Compare plans</span>
          <h2 className="lv-h2">What's included, side by side.</h2>
        </Reveal>
        <div className="lv-cmp-scroll">
          <table className="lv-cmp-table">
            <thead>
              <tr><th>Feature</th><th>Starter</th><th>Professional</th><th>Business</th><th>Enterprise</th></tr>
            </thead>
            <tbody>
              {COMPARISON_CATEGORIES.map((cat) => (
                <Fragment key={cat.name}>
                  <tr className="lv-cmp-cat"><td colSpan={5}>{cat.name}</td></tr>
                  {cat.rows.map((row) => (
                    <tr key={`${cat.name}-${row.feature}`}>
                      <td>{row.feature}</td>
                      <td>{cell(row.starter)}</td>
                      <td>{cell(row.professional)}</td>
                      <td>{cell(row.business)}</td>
                      <td>{cell(row.enterprise)}</td>
                    </tr>
                  ))}
                </Fragment>
              ))}
            </tbody>
          </table>
        </div>
      </Container>
    </section>
  );
}

// ── FAQ ──────────────────────────────────────────────────────────
function FAQ() {
  const items = PRICING_FAQ.map((i) => ({ question: i.q, answer: i.a }));
  return (
    <section className="lv-faq">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> FAQ</span>
          <h2 className="lv-h2">Common questions about pricing.</h2>
        </Reveal>
        <div className="lv-faq-inner">
          <Accordion items={items} />
        </div>
      </Container>
    </section>
  );
}

export default function Pricing({ plans = [] }) {
  const [isAnnual, setIsAnnual] = useState(false);
  // Prefer DB plans only when they carry full marketing copy; otherwise use the
  // rich static tiers (consistent with the comparison table below).
  const dbRich = plans.length > 0 && (plans[0]?.perks?.length ?? 0) >= 4;
  const activePlans = dbRich ? plans : PRICING_PLANS;
  return (
    <>
      <PricingHero isAnnual={isAnnual} onToggle={setIsAnnual} />
      <Plans plans={activePlans} isAnnual={isAnnual} />
      <Comparison />
      <FAQ />
      <CtaLiving />
    </>
  );
}

Pricing.layout = (page) => (
  <PublicLayout title="Pricing — Simple, Transparent Plans">{page}</PublicLayout>
);
