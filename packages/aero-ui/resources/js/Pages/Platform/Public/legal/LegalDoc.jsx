// ─── Living data-OS — shared Legal document layout ───────────────────────────
import { Container } from '@aero/ui';
import { Reveal } from '../home/primitives.jsx';

export default function LegalDoc({ eyebrow = 'Legal', title, lead, updated, toc = [], sections = [] }) {
  return (
    <>
      <section className="lv-hero lv-hero--page lv-hero--center lv-hero--slim">
        <div className="lv-hero-bg" aria-hidden="true">
          <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
        </div>
        <Container>
          <div className="lv-hero-centered">
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> {eyebrow}</span></Reveal>
            <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">{title}</h1></Reveal>
            {lead && <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">{lead}</p></Reveal>}
            {updated && <Reveal delay={0.18}><p className="lv-eyebrow" style={{ opacity: .8 }}>Last updated · {updated}</p></Reveal>}
          </div>
        </Container>
        <div className="lv-hero-fade" aria-hidden="true" />
      </section>

      <section className="lv-legal">
        <Container>
          <div className="lv-legal-layout">
            <aside className="lv-toc lv-legal-toc">
              <p className="lv-eyebrow">Contents</p>
              {toc.map((t) => <a key={t.id} href={`#${t.id}`} className="lv-toc-link">{t.label}</a>)}
            </aside>
            <div className="lv-legal-main">
              {sections.map((s) => (
                <article key={s.id} id={s.id} className="lv-api-card lv-legal-section">
                  <h2 className="lv-api-h2">{s.title}</h2>
                  {(s.body || []).map((p, i) => <p key={i} className="lv-api-p">{p}</p>)}
                  {s.extra && <div className="lv-legal-extra">{s.extra}</div>}
                </article>
              ))}
            </div>
          </div>
        </Container>
      </section>
    </>
  );
}
