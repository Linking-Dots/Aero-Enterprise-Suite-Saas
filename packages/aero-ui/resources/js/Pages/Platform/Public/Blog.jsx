import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Container } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';

const CATEGORIES = [
  { id: 'all', label: 'All' }, { id: 'product', label: 'Product strategy' },
  { id: 'hr', label: 'HR & people' }, { id: 'tech', label: 'Technology' },
  { id: 'ops', label: 'Operations' }, { id: 'leadership', label: 'Leadership' },
];
const POSTS = [
  { id: 1, category: 'product', tag: 'Product Strategy', title: 'Multi-tenant database isolation without shared schemas', excerpt: 'Every tenant gets a fully isolated database. Why we made that architectural decision and what it means for your data.', author: 'Engineering Team', date: 'Apr 28, 2026', readTime: '8 min', accent: 'cyan' },
  { id: 2, category: 'hr', tag: 'HR & People', title: 'Running payroll across five countries without losing your mind', excerpt: 'A practical guide to configuring multi-jurisdiction payroll — tax rules, deduction tiers, and approval flows.', author: 'Product Team', date: 'Apr 22, 2026', readTime: '12 min', accent: 'indigo' },
  { id: 3, category: 'ops', tag: 'Operations', title: 'How Cascade Logistics onboarded 1,400 employees in three weeks', excerpt: 'A case study on leveraging HRMAC permission scoping to give each subsidiary manager exactly the right control.', author: 'Customer Success', date: 'Apr 15, 2026', readTime: '6 min', accent: 'amber' },
  { id: 4, category: 'tech', tag: 'Technology', title: 'Async-first: how background jobs keep your UI sub-100ms', excerpt: 'Payroll runs, bulk imports, and report generation — all queued. How we built the background job system.', author: 'Engineering Team', date: 'Apr 10, 2026', readTime: '10 min', accent: 'cyan' },
  { id: 5, category: 'leadership', tag: 'Leadership', title: 'The hidden cost of spreadsheet-based HR management', excerpt: 'A frank analysis of what disconnected tools actually cost — in time, errors, and team morale.', author: 'Founders', date: 'Apr 5, 2026', readTime: '7 min', accent: 'indigo' },
  { id: 6, category: 'product', tag: 'Product Strategy', title: 'Building the AI assistant that knows which module you are in', excerpt: 'Context-aware AI is harder than it sounds. How we designed aeos365 Assist to stay module-aware.', author: 'Product Team', date: 'Mar 28, 2026', readTime: '9 min', accent: 'amber' },
  { id: 7, category: 'ops', tag: 'Operations', title: 'Supply chain visibility: from procurement to delivery in one platform', excerpt: 'How unified SCM and inventory eliminate the blind spots that plague multi-tool supply chain setups.', author: 'Product Team', date: 'Mar 22, 2026', readTime: '8 min', accent: 'cyan' },
  { id: 8, category: 'hr', tag: 'HR & People', title: 'Digital Permit to Work: eliminating paper from your safety programme', excerpt: 'Moving PTW workflows to aeos365 HSE. What the process looks like, and what compliance teams say.', author: 'Customer Success', date: 'Mar 15, 2026', readTime: '5 min', accent: 'indigo' },
  { id: 9, category: 'tech', tag: 'Technology', title: 'REST API design decisions we made — and a few we regret', excerpt: 'A candid look at the aeos365 v2 API: what we got right, what we changed from v1, and what is coming in v3.', author: 'Engineering Team', date: 'Mar 8, 2026', readTime: '11 min', accent: 'amber' },
];
const FEATURED = POSTS[0];

function Hero() {
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> aeos insights</span></Reveal>
          <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">Field notes for teams scaling{' '}<span className="lv-h1-grad">faster than their playbook.</span></h1></Reveal>
          <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">Long-form strategy, practical templates, and real stories from operators building resilient organizations with aeos365.</p></Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Featured() {
  return (
    <section className="lv-feat" style={{ paddingBottom: '2rem' }}>
      <Container>
        <Reveal>
          <a href="#" className={`lv-featured lv-feat-card--${FEATURED.accent}`}>
            <div className="lv-featured-body">
              <span className={`lv-post-tag lv-accent--${FEATURED.accent}`}>Featured · {FEATURED.tag}</span>
              <h2 className="lv-h2">{FEATURED.title}</h2>
              <p className="lv-lead">{FEATURED.excerpt}</p>
              <span className="lv-post-meta">{FEATURED.author} · {FEATURED.date} · {FEATURED.readTime} read</span>
              <span className="lv-btn lv-btn--primary lv-featured-cta">Read article <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span>
            </div>
            <div className="lv-featured-art" aria-hidden="true"><span className="lv-featured-glow" /><span className="lv-featured-badge">aeos365</span></div>
          </a>
        </Reveal>
      </Container>
    </section>
  );
}

function Grid() {
  const [cat, setCat] = useState('all');
  const list = (cat === 'all' ? POSTS.slice(1) : POSTS.filter((p) => p.category === cat));
  return (
    <section className="lv-list-sec lv-list-sec--alt">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> All articles</span>
          <h2 className="lv-h2">Browse by topic.</h2>
        </Reveal>
        <div className="lv-mod-filters">
          {CATEGORIES.map((c) => (
            <button key={c.id} type="button" className={`lv-mod-chip ${cat === c.id ? 'is-active' : ''}`} onClick={() => setCat(c.id)}>{c.label}</button>
          ))}
        </div>
        <motion.div layout className="lv-post-grid">
          <AnimatePresence mode="popLayout">
            {list.map((p) => (
              <motion.a key={p.id} href="#" layout
                initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.96 }}
                transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }} whileHover={{ y: -5 }}
                className={`lv-post-card lv-feat-card--${p.accent}`}>
                <span className={`lv-post-tag lv-accent--${p.accent}`}>{p.tag}</span>
                <h3 className="lv-post-title">{p.title}</h3>
                <p className="lv-post-excerpt">{p.excerpt}</p>
                <span className="lv-post-meta">{p.date} · {p.readTime} read</span>
              </motion.a>
            ))}
          </AnimatePresence>
        </motion.div>
      </Container>
    </section>
  );
}

function Newsletter() {
  const [email, setEmail] = useState('');
  const [done, setDone] = useState(false);
  return (
    <section className="lv-cta">
      <div className="lv-cta-aura lv-cta-aura--1" aria-hidden="true" /><div className="lv-cta-aura lv-cta-aura--2" aria-hidden="true" /><div className="lv-cta-grid-bg" aria-hidden="true" />
      <Container>
        <Reveal className="lv-cta-inner">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Stay in the loop</span>
          <h2 className="lv-h2 lv-cta-title">New articles,{' '}<span className="lv-h2-grad">straight to your inbox.</span></h2>
          <p className="lv-lead lv-cta-lead">No noise — only high-signal pieces on ERP, operations, and the platform.</p>
          {done ? <p className="lv-cta-done">You're subscribed — we'll be in touch.</p> : (
            <form className="lv-cta-form" onSubmit={(e) => { e.preventDefault(); if (email) setDone(true); }} noValidate>
              <input type="email" required placeholder="Work email address" className="lv-cta-input" value={email} onChange={(e) => setEmail(e.target.value)} />
              <button type="submit" className="lv-btn lv-btn--primary">Subscribe</button>
            </form>
          )}
        </Reveal>
      </Container>
    </section>
  );
}

export default function Blog() {
  return (<><Hero /><Featured /><Grid /><Newsletter /></>);
}

Blog.layout = (page) => (
  <PublicLayout title="Blog — aeos365 Insights">{page}</PublicLayout>
);
