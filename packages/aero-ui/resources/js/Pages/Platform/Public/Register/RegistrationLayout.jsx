import { Head, Link } from '@inertiajs/react';
import { Steps, ThemeToggle, Text, BrandLockup } from '@aero/ui';

export default function RegistrationLayout({ title, currentStep, steps = [], wide = false, children }) {
  return (
    <>
      <Head title={`${title} · aeos365`} />

      <div className="rl-root">
        <div className="rl-mesh" aria-hidden="true" />

        {/* ── Brand header ── */}
        <header className="rl-brand">
          <Link href="/" className="rl-brand-link" aria-label="aeos365 home">
            {/* Full lockup image — wide surface, never composed mark + text */}
            <BrandLockup className="rl-brand-logo" />
          </Link>
          <div className="rl-brand-actions">
            <ThemeToggle />
          </div>
        </header>

        {/* ── Step indicator — adapts per breakpoint ── */}
        {steps.length > 0 && (
          <>
            {/* Desktop: full labels via the design-system Steps component */}
            <div className="rl-steps-desktop">
              <Steps steps={steps} currentStep={currentStep} className="rl-steps-override" />
            </div>

            {/* Tablet: abbreviated text + dots */}
            <div className="rl-steps-tablet" aria-hidden="true">
              <div className="rl-steps-abbr-row">
                {steps.map((step, i) => {
                  const allKeys   = steps.map(s => s.key ?? s.value ?? s.id ?? s);
                  const curIdx    = allKeys.indexOf(currentStep);
                  const stepKey   = step.key ?? step.value ?? step.id ?? step;
                  const stepIdx   = allKeys.indexOf(stepKey);
                  const isDone    = stepIdx < curIdx;
                  const isActive  = stepKey === currentStep;
                  const label     = step.shortLabel ?? step.label ?? (typeof step === 'string' ? step : '');
                  const abbr      = label.length > 6 ? label.slice(0, 5) + '…' : label;
                  return (
                    <div key={stepKey ?? i} className="rl-abbr-step">
                      <div className={`rl-abbr-dot ${isDone ? 'rl-abbr-dot--done' : isActive ? 'rl-abbr-dot--active' : 'rl-abbr-dot--idle'}`}>
                        {isDone ? (
                          <svg width="8" height="8" viewBox="0 0 8 8" fill="none" aria-hidden="true">
                            <path d="M1.5 4l2 2 3-3" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
                          </svg>
                        ) : stepIdx + 1}
                      </div>
                      <span className={`rl-abbr-label ${isActive ? 'rl-abbr-label--active' : ''}`}>{abbr}</span>
                      {i < steps.length - 1 && <div className="rl-abbr-line" />}
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Mobile: dots only */}
            <div className="rl-steps-mobile" aria-label="Registration progress" role="progressbar">
              <div className="rl-dots-row">
                {steps.map((step, i) => {
                  const allKeys  = steps.map(s => s.key ?? s.value ?? s.id ?? s);
                  const curIdx   = allKeys.indexOf(currentStep);
                  const stepKey  = step.key ?? step.value ?? step.id ?? step;
                  const stepIdx  = allKeys.indexOf(stepKey);
                  const isDone   = stepIdx < curIdx;
                  const isActive = stepKey === currentStep;
                  return (
                    <div key={stepKey ?? i} className={`rl-dot ${isDone ? 'rl-dot--done' : isActive ? 'rl-dot--active' : 'rl-dot--idle'}`} />
                  );
                })}
              </div>
              {/* Step fraction label: "Step 3 of 7" */}
              {(() => {
                const allKeys = steps.map(s => s.key ?? s.value ?? s.id ?? s);
                const curIdx  = allKeys.indexOf(currentStep);
                const label   = steps[curIdx]?.label ?? steps[curIdx]?.shortLabel ?? '';
                return (
                  <div className="rl-step-fraction">
                    Step {curIdx + 1} of {steps.length}{label ? ` · ${label}` : ''}
                  </div>
                );
              })()}
            </div>
          </>
        )}

        {/* ── Main card ── */}
        <main className="rl-main">
          <div className={`rl-card${wide ? ' rl-card-wide' : ''}`}>
            {title && <h1 className="rl-title">{title}</h1>}
            {children}
          </div>
        </main>

        <footer className="rl-footer">
          <Text tone="tertiary" size="xs">
            &copy; {new Date().getFullYear()} aeos365
          </Text>
        </footer>
      </div>

      <style>{`
        /* ── Shell reset ───────────────────────────────────────────────
         * The app-shell ([data-aeos-shell]) locks <body> to a 100dvh,
         * overflow:hidden grid for in-app internal-scroll layouts. ThemeProvider
         * sets body.dataset.aeosShell globally, so signup (a document-scroll page)
         * inherits that lock and CANNOT SCROLL. Release it here (scoped to while
         * this layout is mounted) so the page scrolls normally. */
        body[data-aeos-shell] {
          display: block !important;
          overflow: visible !important;
          max-height: none !important;
          height: auto !important;
        }
        html { overflow-y: auto !important; }
        .aeos-theme-drawer-trigger { display: none !important; }

        /* ── Root ──────────────────────────────────────────────────── */
        .rl-root {
          min-height: 100dvh;
          display: flex;
          flex-direction: column;
          align-items: center;
          background: var(--aeos-bg-page);
          position: relative;
          overflow-x: hidden;
        }

        /* ── Ambient mesh background ───────────────────────────────── */
        .rl-mesh {
          position: fixed; inset: 0; pointer-events: none; z-index: 0;
          background:
            radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,229,255,.10), transparent 65%),
            radial-gradient(ellipse 55% 50% at 90% 60%, rgba(99,102,241,.07), transparent 55%),
            radial-gradient(ellipse 40% 50% at 5% 75%,  rgba(255,179,71,.04),  transparent 55%);
        }

        /* ── Brand header ──────────────────────────────────────────── */
        .rl-brand {
          width: 100%;
          max-width: 960px;
          padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 4vw, 1.5rem) 0;
          position: relative; z-index: 1;
          display: flex; align-items: center; justify-content: space-between;
        }
        .rl-brand-link {
          display: inline-flex; align-items: center; gap: 10px;
          text-decoration: none;
        }
        .rl-logo-mark {
          display: flex; align-items: center;
          color: var(--aeos-text-primary);
          filter: drop-shadow(0 0 14px rgba(0,229,255,.35));
        }
        .rl-brand-name {
          font-family: var(--aeos-font-display);
          font-weight: 700; font-size: 1.1rem;
          color: var(--aeos-text-primary);
        }
        .rl-brand-logo {
          display: block; max-height: 34px; max-width: 180px; object-fit: contain;
        }
        .rl-brand-actions { display: flex; align-items: center; gap: 12px; }

        /* ── Step indicators ── */

        /* Desktop full labels (visible ≥ 768px) */
        .rl-steps-desktop {
          width: 100%; max-width: 960px;
          padding: 1.5rem clamp(1rem, 4vw, 1.5rem) 0;
          position: relative; z-index: 1;
        }
        .rl-steps-override { width: 100%; }

        /* Tablet abbreviated (visible 480–767px) */
        .rl-steps-tablet {
          width: 100%; max-width: 960px;
          padding: 1rem clamp(1rem, 4vw, 1.5rem) 0;
          position: relative; z-index: 1;
        }
        .rl-steps-abbr-row {
          display: flex; align-items: center; width: 100%; overflow: hidden;
        }
        .rl-abbr-step {
          display: flex; align-items: center; gap: 0; flex: 1; min-width: 0;
        }
        .rl-abbr-dot {
          width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
          display: flex; align-items: center; justify-content: center;
          font-size: 10px; font-weight: 600; font-family: var(--aeos-font-display);
          transition: background var(--aeos-dur-base), border-color var(--aeos-dur-base);
        }
        .rl-abbr-dot--done   { background: rgba(34,197,94,.15);  border: 1.5px solid rgba(34,197,94,.4);  color: var(--aeos-success); }
        .rl-abbr-dot--active { background: rgba(0,229,255,.18);  border: 1.5px solid rgba(0,229,255,.55); color: var(--aeos-primary); }
        .rl-abbr-dot--idle   { background: transparent; border: 1.5px solid var(--aeos-border); color: var(--aeos-text-tertiary); }
        .rl-abbr-label {
          font-size: 10px; color: var(--aeos-text-tertiary);
          margin-left: 5px; white-space: nowrap; overflow: hidden;
          text-overflow: ellipsis; max-width: 50px;
          font-family: var(--aeos-font-body);
        }
        .rl-abbr-label--active { color: var(--aeos-text-primary); font-weight: 500; }
        .rl-abbr-line {
          flex: 1; height: 1px; background: var(--aeos-divider);
          margin: 0 4px; min-width: 6px;
        }

        /* Mobile dots (visible < 480px) */
        .rl-steps-mobile {
          width: 100%; max-width: 960px;
          padding: .85rem 1rem 0;
          position: relative; z-index: 1;
          display: flex; flex-direction: column; align-items: center; gap: .4rem;
        }
        .rl-dots-row { display: flex; align-items: center; gap: 6px; }
        .rl-dot {
          border-radius: 9999px;
          transition: all var(--aeos-dur-base) var(--aeos-ease-out);
        }
        .rl-dot--done   { width: 8px;  height: 8px;  background: var(--aeos-success); opacity: .7; }
        .rl-dot--active { width: 22px; height: 8px;  background: var(--aeos-primary);  box-shadow: 0 0 8px rgba(0,229,255,.4); }
        .rl-dot--idle   { width: 8px;  height: 8px;  background: var(--aeos-border); }
        .rl-step-fraction {
          font-size: .72rem; color: var(--aeos-text-tertiary);
          font-family: var(--aeos-font-body); letter-spacing: .01em;
        }

        /* Visibility per breakpoint */
        .rl-steps-desktop { display: none; }
        .rl-steps-tablet  { display: none; }
        .rl-steps-mobile  { display: flex; }

        @media (min-width: 480px) {
          .rl-steps-mobile  { display: none; }
          .rl-steps-tablet  { display: block; }
        }
        @media (min-width: 768px) {
          .rl-steps-tablet  { display: none; }
          .rl-steps-desktop { display: block; }
        }

        /* ── Card shell ─────────────────────────────────────────────── */
        .rl-main {
          flex: 1;
          display: flex;
          align-items: flex-start;
          justify-content: center;
          padding: clamp(.75rem, 2vw, 1.5rem) clamp(0px, 3vw, 1rem) clamp(2rem, 5vw, 4rem);
          width: 100%;
          position: relative; z-index: 1;
        }

        /*
         * Mobile-first card:
         *   < 480px  → no card chrome, edge-to-edge, blends with page bg
         *   480–767px → visible card, compact padding
         *   768px+   → full card, generous padding
         */
        .rl-card {
          width: 100%;
          max-width: min(640px, 100%);
          background: transparent; /* mobile: no chrome */
          padding: clamp(1rem, 4vw, 1.25rem) clamp(.75rem, 4vw, 1rem);
          border-radius: 0;
          box-shadow: none;
          transition: background var(--aeos-dur-base), box-shadow var(--aeos-dur-base),
                      padding var(--aeos-dur-base), border-radius var(--aeos-dur-base);
          animation: rl-fadein var(--aeos-dur-slow) var(--aeos-ease-out) both;
        }

        @media (min-width: 480px) {
          .rl-card {
            background: var(--aeos-bg-surface, var(--aeos-bg-card));
            border-radius: var(--aeos-r-xl);
            padding: clamp(1.5rem, 4vw, 2rem) clamp(1.25rem, 4vw, 1.75rem);
            box-shadow:
              0 0 0 1px rgba(0,229,255,.07),
              0 16px 48px rgba(0,0,0,.12),
              0 0 60px rgba(0,229,255,.03);
          }
        }

        @media (min-width: 768px) {
          .rl-card {
            padding: 2.5rem;
            border-radius: var(--aeos-r-2xl);
            box-shadow:
              0 0 0 1px rgba(0,229,255,.08),
              0 24px 64px rgba(0,0,0,.12),
              0 0 60px rgba(0,229,255,.03);
          }
        }

        .rl-card-wide { max-width: min(940px, 100%); }

        /* ── Title ──────────────────────────────────────────────────── */
        .rl-title {
          font-family: var(--aeos-font-display);
          font-size: clamp(1.25rem, 4vw, 1.6rem);
          font-weight: 700;
          letter-spacing: -.02em;
          color: var(--aeos-text-primary);
          margin: 0 0 clamp(1.25rem, 3vw, 1.75rem);
          line-height: 1.15;
        }

        /* ── Nav row (Back / Continue) ─────────────────────────────── */
        .rl-nav {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 12px;
          margin-top: clamp(1.25rem, 3vw, 2rem);
          padding-top: clamp(1rem, 2vw, 1.5rem);
          border-top: 1px solid var(--aeos-divider);
        }
        /* On mobile: nav buttons stack full-width */
        @media (max-width: 479px) {
          .rl-nav {
            flex-direction: column-reverse;
            align-items: stretch;
          }
          .rl-nav > * { width: 100% !important; justify-content: center; }
        }

        /* ── Footer ─────────────────────────────────────────────────── */
        .rl-footer {
          padding: 1.5rem 0 2rem;
          position: relative; z-index: 1;
        }

        /* ── Step mount animation ────────────────────────────────────── */
        @keyframes rl-fadein {
          from { opacity: 0; transform: translateY(10px); }
          to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
          .rl-card { animation: none; }
        }

        /* ═══════════════════════════════════════════════════════════════
         * SHARED STEP UTILITIES
         * ═══════════════════════════════════════════════════════════════ */

        /* Selected card state */
        .rl-card-selected {
          border-color: var(--aeos-primary) !important;
          background: rgba(0,229,255,.04) !important;
          box-shadow: 0 0 0 3px rgba(0,229,255,.12), 0 4px 12px rgba(0,229,255,.06) !important;
        }
        .rl-card-selected:hover {
          background: rgba(0,229,255,.06) !important;
          box-shadow: 0 0 0 3px rgba(0,229,255,.15), 0 6px 16px rgba(0,229,255,.08) !important;
        }

        /* Type icon container */
        .rl-type-icon { color: var(--aeos-primary); display: flex; }

        /* ── OTP input — fluid width ─────────────────────────────────── */
        /*
         * Override any fixed-width styling on the OtpInput component.
         * We target the inner input elements so they flex to fill the row.
         */
        .rl-otp-wrap {
          display: flex;
          gap: clamp(6px, 1.5vw, 10px);
          width: 100%;
        }
        .rl-otp-wrap input,
        .rl-otp-wrap > div,
        .rl-otp-wrap > span {
          flex: 1 !important;
          min-width: 0 !important;
          max-width: none !important;
          width: auto !important;
        }
        /* Fallback: target aeos OTP boxes if they use a specific class */
        .aeos-otp-box, [class*="otp-box"], [class*="OtpBox"] {
          flex: 1 !important;
          min-width: 0 !important;
        }
        .rl-otp-input {
          letter-spacing: 0.3em;
          font-family: var(--aeos-font-mono) !important;
          font-size: clamp(.9rem, 3vw, 1.2rem);
          text-align: center;
        }

        /* Subdomain live preview */
        .rl-subdomain-preview {
          margin-top: 6px;
          font-family: var(--aeos-font-mono);
          font-size: .82rem;
          color: var(--aeos-primary);
          letter-spacing: .01em;
          padding: 0 2px;
          word-break: break-all;
        }

        /* ═══════════════════════════════════════════════════════════════
         * PLAN STEP
         * ═══════════════════════════════════════════════════════════════ */

        /* Desktop: two-column split */
        .rl-plan-split {
          display: grid;
          grid-template-columns: 1fr 300px;
          gap: 2rem;
          align-items: start;
        }
        .rl-plan-main  { min-width: 0; }
        .rl-plan-sidebar {
          position: sticky;
          top: 1.5rem;
          align-self: start;
        }

        /* Plan grid: 2 cols on ≥ 480px, 1 col on mobile */
        .rl-plan-grid-b {
          display: grid;
          grid-template-columns: 1fr;
          gap: 1rem;
          margin-bottom: 1rem;
        }
        @media (min-width: 480px) {
          .rl-plan-grid-b { grid-template-columns: repeat(2, 1fr); }
        }

        /* Module list */
        .rl-module-grid-b {
          display: grid;
          grid-template-columns: 1fr;
          gap: .75rem;
        }

        /* Plan price */
        .rl-plan-price-amount {
          font-family: var(--aeos-font-display);
          font-size: clamp(1.4rem, 4vw, 1.75rem);
          font-weight: 700;
          color: var(--aeos-text-primary);
          letter-spacing: -.02em;
        }
        .rl-plan-price-per {
          font-size: .85rem;
          color: var(--aeos-text-tertiary);
          align-self: flex-end;
          padding-bottom: .2rem;
        }

        /* Summary card (sidebar) */
        .rl-summary-card {
          background: var(--aeos-bg-elevated);
          border: 1px solid rgba(0,229,255,.15);
          border-radius: var(--aeos-r-xl);
          padding: 1.5rem;
          display: flex; flex-direction: column; gap: .75rem;
          backdrop-filter: blur(12px);
          box-shadow: 0 16px 48px rgba(0,0,0,.45);
        }
        .rl-summary-row { display: flex; justify-content: space-between; align-items: center; font-size: .88rem; }
        .rl-summary-row .rl-label { color: var(--aeos-text-secondary); }
        .rl-summary-row .rl-value { font-weight: 600; }
        .rl-summary-divider { height: 1px; background: var(--aeos-divider); margin: .25rem 0; }
        .rl-summary-total .rl-value {
          font-family: var(--aeos-font-display);
          font-size: 1.5rem; font-weight: 800;
          color: var(--aeos-primary);
        }
        .rl-summary-btn { width: 100%; margin-top: .5rem; }

        /*
         * Mobile plan layout:
         *   - 1 column (sidebar collapses below main)
         *   - Sticky bottom bar shows total + CTA
         */
        @media (max-width: 767px) {
          .rl-plan-split {
            grid-template-columns: 1fr;
            gap: 1.5rem;
          }
          .rl-plan-sidebar { position: static; }

          /* Hide the full sidebar summary card on mobile — sticky bar replaces it */
          .rl-plan-sidebar-full { display: none; }

          /* Show the compact sidebar below plan list on mobile */
          .rl-plan-sidebar-mobile { display: block !important; }

          /* Sticky bottom bar */
          .rl-plan-sticky-bar {
            display: flex !important;
          }
        }
        @media (min-width: 768px) {
          .rl-plan-sidebar-mobile { display: none !important; }
          .rl-plan-sidebar-full   { display: block !important; }
          .rl-plan-sticky-bar     { display: none !important; }
        }

        /* The sticky bottom summary bar (mobile) */
        .rl-plan-sticky-bar {
          display: none; /* shown via media query above */
          position: sticky;
          bottom: 0;
          left: 0; right: 0;
          z-index: var(--aeos-z-sticky);
          background: var(--aeos-bg-elevated);
          border-top: 1px solid rgba(0,229,255,.15);
          padding: .75rem 0 .5rem;
          margin: 1rem -2.5rem -2.5rem; /* bleed past card padding */
          /* On mobile (<480px) card has no chrome so no negative margin needed */
          align-items: center;
          gap: .75rem;
          flex-wrap: wrap;
          backdrop-filter: blur(16px);
        }
        @media (max-width: 479px) {
          .rl-plan-sticky-bar {
            margin: 1rem -1rem -1rem;
            padding: .75rem 1rem .5rem;
          }
        }
        @media (min-width: 480px) {
          .rl-plan-sticky-bar {
            margin: 1rem -1.75rem -2rem;
            padding: .75rem 1.75rem .5rem;
            border-radius: 0 0 var(--aeos-r-xl) var(--aeos-r-xl);
          }
        }
        .rl-plan-sticky-total {
          display: flex; flex-direction: column; gap: 1px; flex: 1;
        }
        .rl-plan-sticky-total-label {
          font-size: .72rem; color: var(--aeos-text-tertiary); letter-spacing: .03em; text-transform: uppercase;
        }
        .rl-plan-sticky-total-amount {
          font-family: var(--aeos-font-display);
          font-size: 1.3rem; font-weight: 700;
          color: var(--aeos-primary);
        }
        .rl-plan-sticky-total-breakdown {
          font-size: .75rem; color: var(--aeos-text-tertiary);
        }
        .rl-plan-sticky-cta { flex-shrink: 0; min-width: 140px; }

        /* Module grid on tablet */
        @media (min-width: 480px) and (max-width: 767px) {
          .rl-module-grid-b { grid-template-columns: repeat(2, 1fr); }
        }

        /* Plan section spacing helpers */
        .rl-plan-billing  { margin-bottom: 1.25rem; }
        .rl-plan-eyebrow  { margin-top: 1.5rem; }

        /* ═══════════════════════════════════════════════════════════════
         * PROVISIONING STEP
         * ═══════════════════════════════════════════════════════════════ */
        @keyframes rl-spin      { to { transform: rotate(360deg); } }
        @keyframes rl-step-spin { to { transform: rotate(360deg); } }

        .rl-prov-icon-wrap   { position: relative; width: 80px; height: 80px; }
        .rl-prov-icon-bg     { position: absolute; inset: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .rl-prov-icon-bg--running   { background: rgba(0,229,255,.08);    border: 2px solid rgba(0,229,255,.15); }
        .rl-prov-icon-bg--completed { background: rgba(34,197,94,.10);   border: 2px solid rgba(34,197,94,.25); }
        .rl-prov-icon-bg--failed    { background: rgba(255,107,107,.10); border: 2px solid rgba(255,107,107,.25); }
        .rl-prov-spinner { position: absolute; inset: -2px; border-radius: 50%; border: 2px solid transparent; border-top-color: var(--aeos-primary); animation: rl-spin .8s linear infinite; }
        .rl-prov-bar-track { width: 100%; height: 6px; background: var(--aeos-divider); border-radius: 4px; overflow: hidden; }
        .rl-prov-bar-fill  { height: 100%; border-radius: 4px; background: var(--aeos-grad-cyan); transition: width .5s ease; }
        .rl-prov-bar-fill--failed { background: rgba(255,107,107,.6); }
        .rl-prov-step { padding: .6rem 1rem; border-bottom: 1px solid var(--aeos-divider); }
        .rl-prov-step:last-child { border-bottom: none; }
        .rl-prov-step-icon { width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .rl-prov-step--done    .rl-prov-step-icon { color: var(--aeos-success); }
        .rl-prov-step--running .rl-prov-step-icon { color: var(--aeos-primary); }
        .rl-prov-step--failed  .rl-prov-step-icon { color: var(--aeos-destructive); }
        .rl-prov-step--pending .rl-prov-step-icon { color: var(--aeos-text-tertiary); }
        .rl-prov-step--done    { opacity: .85; }
        .rl-prov-step--pending { opacity: .5; }
        .rl-prov-step-spinner  { width: 10px; height: 10px; border: 1.5px solid transparent; border-top-color: var(--aeos-primary); border-radius: 50%; animation: rl-step-spin .6s linear infinite; }
        .rl-prov-step-dot      { width: 6px; height: 6px; border-radius: 50%; background: var(--aeos-text-tertiary); }

        /* ═══════════════════════════════════════════════════════════════
         * SUCCESS STEP
         * ═══════════════════════════════════════════════════════════════ */
        .rl-success-icon { filter: drop-shadow(0 0 20px rgba(34,197,94,.25)); }
        .rl-success-url {
          display: inline-flex; align-items: center; gap: 8px;
          padding: .5rem 1.25rem;
          background: rgba(0,229,255,.06);
          border: 1px solid rgba(0,229,255,.25);
          border-radius: var(--aeos-r-xl);
          color: var(--aeos-primary);
          text-decoration: none;
          transition: background .15s, box-shadow .15s;
          word-break: break-all;
          text-align: center;
        }
        .rl-success-url:hover {
          background: rgba(0,229,255,.10);
          box-shadow: 0 0 0 3px rgba(0,229,255,.12);
        }
        .rl-success-trial strong { color: var(--aeos-text-primary); }
      `}</style>
    </>
  );
}
