import { Head, Link } from '@inertiajs/react';
import { Box, Text } from '@aero/ui';
import { useTheme } from '../../theme/ThemeProvider.jsx';
import { BrandLockup } from '../../components/AppChrome.jsx';

/**
 * InstallLayout — fully-responsive wizard shell for all installation pages.
 *
 * Breakpoints (matching aeos system):
 *   xs  < 480px   — mobile portrait
 *   sm  480–767px — mobile landscape / small tablet
 *   md  768–1023px — tablet
 *   lg  ≥ 1024px   — desktop
 *
 * Key changes vs previous version:
 *   - Step indicator always shows "Step N of M · Label" centred text (never hidden)
 *   - Dot row uses icon + label at ≥ 640px, icon-only below
 *   - Card max-width bumped to 860px with fluid horizontal padding
 *   - All spacing uses clamp() for fluid scaling
 *   - Focus-visible ring on every interactive element
 */
export default function InstallLayout({ title, step, steps = [], mode, children }) {
  const theme  = useTheme();
  const isDark = theme.isDark;

  const totalSteps = steps.length;
  const pct        = totalSteps > 0 ? Math.round((step / totalSteps) * 100) : 0;
  const currentLabel = steps[step - 1] ?? '';

  return (
    <>
      <Head title={`${title} · aeos365 Setup`} />

      {/* ── Root viewport ─────────────────────────────────────────── */}
      <Box
        flex dir="column" align="center"
        style={{
          minHeight: '100vh',
          background: 'var(--aeos-bg-page)',
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        {/* Ambient mesh — purely decorative, pointer-events none */}
        <div aria-hidden="true" style={{
          position: 'fixed', inset: 0, pointerEvents: 'none', zIndex: 0,
          background: `
            radial-gradient(ellipse 80% 60% at 50% -5%,  rgba(0,163,184,.07),  transparent 65%),
            radial-gradient(ellipse 55% 50% at 90% 65%,  rgba(99,102,241,.04), transparent 55%),
            radial-gradient(ellipse 40% 50% at 5%  75%,  rgba(180,83,9,.03),   transparent 55%)`,
        }} />

        {/* ── Brand header ─────────────────────────────────────────── */}
        <header className="il-header">
          <Link href="/" className="il-brand-link" aria-label="aeos365 home">
            {/* Full lockup image — wide surface, never composed mark + text */}
            <BrandLockup className="il-brand-logo" />
          </Link>

          <div className="il-header-right">
            {mode && (
              <span className="aeos-badge aeos-badge-mono il-mode-badge">
                {mode === 'saas' ? 'SaaS Mode' : 'Standalone'}
              </span>
            )}
            <button
              type="button"
              className="il-theme-toggle"
              onClick={() => theme.setMode(isDark ? 'light' : 'dark')}
              aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            >
              {isDark ? (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="5" /><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                </svg>
              ) : (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                </svg>
              )}
            </button>
          </div>
        </header>

        {/* ── Step progress (always rendered when steps provided) ─── */}
        {totalSteps > 0 && (
          <div className="il-progress-shell" role="navigation" aria-label="Installation progress">
            {/* Dot + line row */}
            <div className="il-step-row" role="list">
              {steps.map((s, i) => {
                const done    = i + 1 < step;
                const current = i + 1 === step;
                return (
                  <div key={i} className="il-step-item" role="listitem">
                    <div
                      className={`il-dot ${done ? 'il-dot--done' : current ? 'il-dot--current' : 'il-dot--pending'}`}
                      aria-label={`${done ? 'Completed' : current ? 'Current' : 'Upcoming'}: ${s}`}
                    >
                      {done ? (
                        <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
                          <path d="M2 5l2 2 4-4" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" />
                        </svg>
                      ) : (
                        <span className="il-dot-num">{i + 1}</span>
                      )}
                    </div>
                    {/* Label — only visible at ≥ 640px */}
                    <span className={`il-step-lbl ${current ? 'il-step-lbl--current' : ''}`} aria-hidden="true">{s}</span>
                    {/* Connector line */}
                    {i < steps.length - 1 && (
                      <div className={`il-connector ${done ? 'il-connector--done' : ''}`} aria-hidden="true" />
                    )}
                  </div>
                );
              })}
            </div>

            {/* Always-visible centred "Step N of M · Label" — the mobile-first indicator */}
            <p className="il-step-caption" aria-live="polite">
              Step {step} of {totalSteps}
              {currentLabel ? <span className="il-step-caption-label"> · {currentLabel}</span> : null}
            </p>

            {/* Progress bar */}
            <div
              className="il-progress-bar"
              role="progressbar"
              aria-valuenow={pct}
              aria-valuemin={0}
              aria-valuemax={100}
              aria-label={`Installation progress: ${pct}%`}
            >
              <div className="il-progress-fill" style={{ width: `${pct}%` }} />
            </div>
          </div>
        )}

        {/* ── Wizard card ──────────────────────────────────────────── */}
        <main className="il-card-wrap">
          <div className="il-card">
            {children}
          </div>
        </main>

        {/* Footer */}
        <footer className="il-footer">
          <Text as="span" size="xs" tone="tertiary">aeos365 · Setup Wizard</Text>
        </footer>
      </Box>

      {/* ── Scoped styles ─────────────────────────────────────────── */}
      <style>{`
        /* Reset shell grid to avoid breaking centered wizard layout */
        body[data-aeos-shell] { display: block !important; }
        .aeos-theme-drawer-trigger { display: none !important; }

        /* ── Header ───────────────────────────────────────────────── */
        .il-header {
          width: 100%;
          max-width: 900px;
          padding: clamp(.75rem, 3vw, 1.5rem) clamp(1rem, 4vw, 1.5rem) 0;
          position: relative;
          z-index: 1;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }
        .il-brand-link {
          display: inline-flex;
          align-items: center;
          gap: 10px;
          text-decoration: none;
        }
        .il-logo-mark {
          display: flex;
          align-items: center;
          filter: drop-shadow(0 0 8px rgba(0,163,184,.25));
        }
        .il-brand-logo {
          display: block; max-height: 34px; max-width: 180px; object-fit: contain;
        }
        .il-header-right {
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .il-mode-badge {
          display: none; /* hide on xs to save header space */
        }
        @media (min-width: 480px) {
          .il-mode-badge { display: inline-block; }
        }
        .il-theme-toggle {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 36px; height: 36px;
          border-radius: 50%;
          border: 1px solid var(--aeos-divider);
          background: transparent;
          color: var(--aeos-text-secondary);
          cursor: pointer;
          transition: background .15s, color .15s, border-color .15s;
          flex-shrink: 0;
        }
        .il-theme-toggle:hover {
          background: rgba(0,229,255,.08);
          color: var(--aeos-primary);
          border-color: rgba(0,229,255,.25);
        }
        .il-theme-toggle:focus-visible {
          outline: 2px solid var(--aeos-primary);
          outline-offset: 2px;
        }

        /* ── Progress shell ───────────────────────────────────────── */
        .il-progress-shell {
          width: 100%;
          max-width: 900px;
          padding: clamp(.75rem, 2vw, 1.25rem) clamp(1rem, 4vw, 1.5rem) 0;
          position: relative;
          z-index: 1;
        }

        /* ── Step dots row ────────────────────────────────────────── */
        .il-step-row {
          display: flex;
          align-items: center;
          gap: 0;
          margin-bottom: .5rem;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          scrollbar-width: none;
        }
        .il-step-row::-webkit-scrollbar { display: none; }

        .il-step-item {
          display: flex;
          align-items: center;
          flex: 1;
          min-width: 0;
          gap: 4px;
        }

        /* Dot */
        .il-dot {
          width: 22px; height: 22px;
          border-radius: 50%;
          flex-shrink: 0;
          display: flex; align-items: center; justify-content: center;
          transition: all .2s var(--aeos-ease-out, ease);
          cursor: default;
        }
        .il-dot--done {
          background: rgba(0,163,184,.08);
          border: 1px solid rgba(0,163,184,.3);
          color: var(--aeos-primary, #00B8D4);
        }
        .il-dot--current {
          background: var(--aeos-primary, #00B8D4);
          border: 1px solid var(--aeos-primary, #00B8D4);
          color: #fff;
          box-shadow: 0 0 10px rgba(0,163,184,.3);
        }
        .il-dot--pending {
          background: var(--aeos-bg-card, transparent);
          border: 1px solid var(--aeos-divider);
          color: var(--aeos-text-tertiary);
        }
        .il-dot-num {
          font-size: .6rem;
          font-family: var(--aeos-font-mono);
          line-height: 1;
        }

        /* Step label — hidden on small screens, shown from 640px */
        .il-step-lbl {
          display: none;
          font-size: .65rem;
          text-transform: uppercase;
          letter-spacing: .07em;
          white-space: nowrap;
          color: var(--aeos-text-tertiary);
          overflow: hidden;
          text-overflow: ellipsis;
          max-width: 72px;
        }
        .il-step-lbl--current { color: var(--aeos-primary, #00B8D4); }
        @media (min-width: 640px) {
          .il-step-lbl { display: block; }
        }

        /* Connector line */
        .il-connector {
          flex: 1;
          height: 1px;
          background: var(--aeos-divider);
          margin: 0 4px;
          flex-shrink: 0;
          min-width: 8px;
        }
        .il-connector--done { background: rgba(0,163,184,.2); }

        /* ── Always-visible caption line ──────────────────────────── */
        .il-step-caption {
          text-align: center;
          font-size: clamp(.7rem, 2vw, .78rem);
          color: var(--aeos-text-secondary);
          margin-bottom: .5rem;
          line-height: 1.4;
        }
        .il-step-caption-label {
          font-weight: 500;
          color: var(--aeos-text-primary);
        }

        /* ── Progress bar ─────────────────────────────────────────── */
        .il-progress-bar {
          height: 3px;
          background: var(--aeos-divider);
          border-radius: 2px;
          overflow: hidden;
        }
        .il-progress-fill {
          height: 100%;
          background: var(--aeos-grad-cyan, linear-gradient(90deg,#00E5FF,#6366F1));
          border-radius: 2px;
          transition: width .45s var(--aeos-ease-out, ease);
        }

        /* ── Wizard card ──────────────────────────────────────────── */
        .il-card-wrap {
          width: 100%;
          max-width: 900px;
          padding: clamp(1rem, 3vw, 1.5rem) clamp(1rem, 4vw, 1.5rem) clamp(2rem, 5vw, 3rem);
          position: relative;
          z-index: 1;
          flex: 1;
        }
        .il-card {
          border-radius: var(--aeos-r-2xl, 20px);
          padding: clamp(1.25rem, 5vw, 2.5rem);
          background: var(--aeos-bg-card, var(--aeos-bg-surface));
          box-shadow: 0 0 0 1px rgba(0,0,0,.06), 0 20px 48px rgba(0,0,0,.10);
        }

        /* ── Footer ───────────────────────────────────────────────── */
        .il-footer {
          padding-bottom: 2rem;
          position: relative;
          z-index: 1;
        }

        /* ── Wizard typography helpers (used by page components) ──── */
        .il-title {
          font-family: var(--aeos-font-display);
          font-size: clamp(1.25rem, 4vw, 1.6rem);
          font-weight: 700;
          letter-spacing: -.018em;
          color: var(--aeos-text-primary);
          margin: 0 0 .35rem;
          line-height: 1.2;
        }
        .il-desc {
          font-size: clamp(.82rem, 2.5vw, .92rem);
          color: var(--aeos-text-secondary);
          margin: 0 0 clamp(1rem, 4vw, 1.75rem);
          line-height: 1.65;
        }

        /* ── Navigation row (Back / Continue buttons) ─────────────── */
        .il-nav {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 10px;
          margin-top: clamp(1.25rem, 4vw, 2rem);
          padding-top: clamp(1rem, 3vw, 1.5rem);
          border-top: 1px solid var(--aeos-divider);
          flex-wrap: wrap;
        }
        /* On very small screens stack nav buttons full-width */
        @media (max-width: 360px) {
          .il-nav { flex-direction: column; align-items: stretch; }
          .il-nav > * { width: 100% !important; justify-content: center; }
        }

        /* ── Requirements check rows ──────────────────────────────── */
        .il-check {
          display: flex; align-items: flex-start;
          gap: 10px; padding: 10px 0;
        }
        .il-check + .il-check { border-top: 1px solid var(--aeos-divider); }
        .il-check-icon {
          flex-shrink: 0;
          width: 22px; height: 22px;
          border-radius: 50%;
          display: flex; align-items: center; justify-content: center;
          font-size: .7rem;
          margin-top: 1px;
        }
        .il-check-pass { background: rgba(34,197,94,.12);   color: var(--aeos-success); }
        .il-check-fail { background: rgba(255,107,107,.12); color: var(--aeos-destructive); }
        .il-check-warn { background: rgba(255,179,71,.12);  color: var(--aeos-secondary); }

        /* ── Review table ─────────────────────────────────────────── */
        .il-review-section { margin-bottom: 1.25rem; }
        .il-review-section:last-child { margin-bottom: 0; }
        .il-review-label {
          font-size: .65rem; letter-spacing: .12em;
          text-transform: uppercase; color: var(--aeos-text-tertiary);
          margin-bottom: .4rem;
        }
        .il-review-row {
          display: flex; gap: 10px;
          padding: 7px 0;
          border-bottom: 1px solid var(--aeos-divider);
          font-size: clamp(.8rem, 2vw, .875rem);
          flex-wrap: wrap;
        }
        .il-review-row:last-child { border-bottom: none; }
        .il-review-key {
          color: var(--aeos-text-secondary);
          flex: 0 0 clamp(100px, 25%, 160px);
          min-width: 0;
        }
        .il-review-val {
          color: var(--aeos-text-primary);
          font-family: var(--aeos-font-mono);
          font-size: clamp(.75rem, 2vw, .82rem);
          word-break: break-all;
          flex: 1;
          min-width: 0;
        }

        /* ── Section divider ──────────────────────────────────────── */
        .il-section-divider {
          display: flex; align-items: center; gap: 10px;
          margin: clamp(.75rem, 3vw, 1.25rem) 0 clamp(.5rem, 2vw, .75rem);
        }
        .il-section-divider-line {
          flex: 1; height: 1px;
          background: var(--aeos-divider);
        }
        .il-section-divider-label {
          font-size: .65rem; letter-spacing: .1em;
          text-transform: uppercase;
          color: var(--aeos-text-tertiary);
          white-space: nowrap;
        }

        /* ── Advisory / info box ──────────────────────────────────── */
        .il-advisory {
          display: flex; gap: 10px; align-items: flex-start;
          padding: .75rem 1rem;
          border-radius: var(--aeos-r-lg, 12px);
          border: 1px solid rgba(255,179,71,.25);
          background: rgba(255,179,71,.06);
          font-size: clamp(.78rem, 2vw, .85rem);
          color: var(--aeos-text-secondary);
          line-height: 1.55;
        }
        .il-advisory code {
          font-family: var(--aeos-font-mono);
          font-size: .8em;
          background: rgba(0,0,0,.06);
          padding: 0 4px;
          border-radius: 3px;
          color: var(--aeos-secondary, #FFB347);
        }
        .il-advisory-icon { flex-shrink: 0; color: var(--aeos-secondary, #FFB347); margin-top: 1px; }

        /* ── Copyable mono block ──────────────────────────────────── */
        .il-copy-block {
          display: flex; align-items: center; gap: 8px;
          background: rgba(0,0,0,.04);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md, 8px);
          padding: .5rem .75rem;
          font-family: var(--aeos-font-mono);
          font-size: clamp(.72rem, 2vw, .82rem);
          color: var(--aeos-text-primary);
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }
        .il-copy-btn {
          flex-shrink: 0;
          background: transparent;
          border: 1px solid var(--aeos-divider);
          border-radius: 6px;
          padding: 2px 8px;
          font-size: .72rem;
          color: var(--aeos-text-secondary);
          cursor: pointer;
          transition: background .15s;
          white-space: nowrap;
        }
        .il-copy-btn:hover { background: rgba(0,163,184,.08); color: var(--aeos-primary); }
        .il-copy-btn:focus-visible { outline: 2px solid var(--aeos-primary); outline-offset: 2px; }

        /* ── Password strength meter ──────────────────────────────── */
        .il-strength-bar {
          display: flex; gap: 4px;
          margin-top: 6px;
        }
        .il-strength-seg {
          flex: 1; height: 3px;
          border-radius: 2px;
          background: var(--aeos-divider);
          transition: background .25s;
        }
        .il-strength-seg.on-weak   { background: #FF6B6B; }
        .il-strength-seg.on-fair   { background: #FFB347; }
        .il-strength-seg.on-good   { background: #60D394; }
        .il-strength-seg.on-strong { background: var(--aeos-primary, #00B8D4); }
        .il-strength-text {
          font-size: .72rem;
          margin-top: 4px;
          color: var(--aeos-text-tertiary);
          transition: color .25s;
        }

        /* ── Accordion (Review & Settings) ───────────────────────── */
        .il-accordion { border: 1px solid var(--aeos-divider); border-radius: var(--aeos-r-xl, 16px); overflow: hidden; }
        .il-accordion + .il-accordion { margin-top: .5rem; }
        .il-accordion-head {
          display: flex; align-items: center; justify-content: space-between;
          padding: .75rem clamp(.75rem, 3vw, 1.25rem);
          cursor: pointer;
          user-select: none;
          gap: 10px;
        }
        .il-accordion-head:focus-visible { outline: 2px solid var(--aeos-primary); outline-offset: -2px; }
        .il-accordion-head-left {
          display: flex; align-items: center; gap: 10px;
          font-size: clamp(.82rem, 2.5vw, .9rem);
          font-weight: 600;
          color: var(--aeos-text-primary);
          min-width: 0;
        }
        .il-accordion-head-right {
          display: flex; align-items: center; gap: 8px;
          flex-shrink: 0;
        }
        .il-accordion-chevron {
          transition: transform .2s var(--aeos-ease-out, ease);
        }
        .il-accordion-chevron.open { transform: rotate(180deg); }
        .il-accordion-body {
          border-top: 1px solid var(--aeos-divider);
          padding: .75rem clamp(.75rem, 3vw, 1.25rem);
          overflow: hidden;
          transition: max-height .25s var(--aeos-ease-out, ease);
        }

        /* ── Processing step chips ───────────────────────────────── */
        .il-proc-list {
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-lg, 12px);
          overflow: hidden;
          max-height: 260px;
          overflow-y: auto;
        }
        .il-proc-item {
          display: flex; align-items: center; gap: 10px;
          padding: clamp(.4rem, 1.5vw, .55rem) clamp(.75rem, 3vw, 1rem);
          border-bottom: 1px solid var(--aeos-divider);
          font-size: clamp(.78rem, 2vw, .85rem);
        }
        .il-proc-item:last-child { border-bottom: none; }
        .il-proc-item--running { background: rgba(0,163,184,.04); }
        .il-proc-dot {
          width: 20px; height: 20px;
          border-radius: 50%;
          flex-shrink: 0;
          display: flex; align-items: center; justify-content: center;
          font-size: .65rem; font-family: var(--aeos-font-mono);
        }
        .il-proc-dot--done    { background: rgba(34,197,94,.12);   color: var(--aeos-success); }
        .il-proc-dot--running { background: rgba(0,163,184,.12);   color: var(--aeos-primary); }
        .il-proc-dot--failed  { background: rgba(255,107,107,.12); color: var(--aeos-destructive); }
        .il-proc-dot--pending { background: var(--aeos-bg-muted, rgba(0,0,0,.04)); color: var(--aeos-text-tertiary); }
        .il-proc-status {
          flex-shrink: 0;
          font-size: .68rem; font-family: var(--aeos-font-mono);
        }
        .il-proc-status--done    { color: var(--aeos-success); }
        .il-proc-status--running { color: var(--aeos-primary); }
        .il-proc-status--failed  { color: var(--aeos-destructive); }
        .il-proc-status--pending { color: var(--aeos-text-tertiary); }

        /* ── Expandable log tail ─────────────────────────────────── */
        .il-log-tail {
          margin-top: .5rem;
          background: rgba(0,0,0,.04);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-lg, 12px);
          padding: .5rem .75rem;
          max-height: 140px;
          overflow-y: auto;
          font-family: var(--aeos-font-mono);
          font-size: clamp(.68rem, 1.8vw, .75rem);
          color: var(--aeos-text-secondary);
          line-height: 1.6;
        }

        /* ── Connection badge ────────────────────────────────────── */
        .il-conn-badge {
          display: inline-flex; align-items: center; gap: 5px;
          font-size: .75rem;
          font-family: var(--aeos-font-mono);
          padding: 2px 8px;
          border-radius: 99px;
          border: 1px solid rgba(34,197,94,.3);
          background: rgba(34,197,94,.08);
          color: var(--aeos-success);
        }
        .il-conn-badge::before {
          content: '';
          width: 6px; height: 6px;
          border-radius: 50%;
          background: currentColor;
          display: inline-block;
          animation: il-pulse 1.5s ease-in-out infinite;
        }
        @keyframes il-pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: .4; }
        }

        /* ── Spinner ─────────────────────────────────────────────── */
        @keyframes il-spin { to { transform: rotate(360deg); } }

        /* ── Quick action cards (Complete step) ──────────────────── */
        .il-quick-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr));
          gap: .75rem;
        }
        .il-quick-card {
          display: flex; flex-direction: column; align-items: flex-start;
          gap: 6px;
          padding: clamp(.75rem, 3vw, 1rem);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-lg, 12px);
          background: var(--aeos-bg-card, transparent);
          text-decoration: none;
          transition: border-color .15s, background .15s;
          cursor: pointer;
        }
        .il-quick-card:hover {
          border-color: rgba(0,163,184,.4);
          background: rgba(0,163,184,.04);
        }
        .il-quick-card:focus-visible {
          outline: 2px solid var(--aeos-primary);
          outline-offset: 2px;
        }
        .il-quick-card-icon {
          width: 32px; height: 32px;
          border-radius: 8px;
          background: rgba(0,163,184,.1);
          display: flex; align-items: center; justify-content: center;
          color: var(--aeos-primary);
        }
        .il-quick-card-title {
          font-size: clamp(.82rem, 2.5vw, .88rem);
          font-weight: 600;
          color: var(--aeos-text-primary);
        }
        .il-quick-card-sub {
          font-size: clamp(.72rem, 2vw, .78rem);
          color: var(--aeos-text-secondary);
          line-height: 1.45;
        }
      `}</style>
    </>
  );
}
