import { Head, Link } from '@inertiajs/react';
import { Steps, ThemeToggle, Text } from '@aero/ui';

export default function RegistrationLayout({ title, currentStep, steps = [], wide = false, children }) {
  return (
    <>
      <Head title={`${title} · AEOS365`} />

      <div className="rl-root">
        <div className="rl-mesh" aria-hidden="true" />

        <header className="rl-brand">
          <Link href="/" className="rl-brand-link" aria-label="AEOS365 home">
            <span className="rl-logo-mark">
              <svg width="30" height="30" viewBox="0 0 30 30" fill="none" aria-hidden="true">
                <rect width="30" height="30" rx="8" fill="url(#rl-grad)" />
                <path d="M9 21L15 9l6 12H9z" fill="white" fillOpacity=".92" />
                <defs>
                  <linearGradient id="rl-grad" x1="0" y1="0" x2="30" y2="30">
                    <stop stopColor="var(--aeos-primary, #00E5FF)" />
                    <stop offset="1" stopColor="var(--aeos-tertiary, #6366F1)" />
                  </linearGradient>
                </defs>
              </svg>
            </span>
            <span className="aeos-logo-text">aeos365</span>
          </Link>
          <div className="rl-brand-actions">
            <ThemeToggle />
          </div>
        </header>

        {steps.length > 0 && (
          <Steps steps={steps} currentStep={currentStep} className="rl-steps-override" />
        )}

        <main className="rl-main">
          <div className={`rl-card${wide ? ' rl-card-wide' : ''}`}>
            {title && <h1 className="rl-title">{title}</h1>}
            {children}
          </div>
        </main>

        <footer className="rl-footer">
          <Text tone="tertiary" size="xs">
            &copy; {new Date().getFullYear()} AEOS365 &middot; Enterprise Edition
          </Text>
        </footer>
      </div>

      <style>{`
        /* ── Shell ─────────────────────────────────────────────────── */
        body[data-aeos-shell] { display: block !important; }
        .aeos-theme-drawer-trigger { display: none !important; }

        .rl-root {
          min-height: 100vh; display: flex; flex-direction: column;
          align-items: center; background: var(--aeos-bg-page);
          position: relative; overflow-x: hidden;
        }
        .rl-mesh {
          position: fixed; inset: 0; pointer-events: none; z-index: 0;
          background:
            radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,229,255,.10), transparent 65%),
            radial-gradient(ellipse 55% 50% at 90% 60%, rgba(99,102,241,.07), transparent 55%),
            radial-gradient(ellipse 40% 50% at 5% 75%, rgba(255,179,71,.04), transparent 55%);
        }

        /* ── Brand ─────────────────────────────────────────────────── */
        .rl-brand { width: 100%; max-width: 900px; padding: 2rem 1.5rem 0; position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; }
        .rl-brand-link { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .rl-logo-mark { display: flex; align-items: center; filter: drop-shadow(0 0 14px rgba(0,229,255,.35)); }
        .rl-brand-actions { display: flex; align-items: center; gap: 12px; }

        /* ── Steps override (positioning only, styling from engine) ──── */
        .rl-steps-override { width: 100%; max-width: 900px; padding: 1.5rem 1.5rem 0; position: relative; z-index: 1; }

        /* ── Card shell ─────────────────────────────────────────────── */
        .rl-main { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 1.5rem 1rem 3rem; width: 100%; position: relative; z-index: 1; }
        .rl-card { width: 100%; max-width: 640px; background: var(--aeos-bg-surface); border-radius: var(--aeos-r-2xl); padding: 2.5rem; box-shadow: 0 0 0 1px rgba(0,229,255,.08), 0 24px 64px rgba(0,0,0,.10), 0 0 60px rgba(0,229,255,.03); }
        .rl-card-wide { max-width: 900px; }
        .rl-title { font-family: var(--aeos-font-display); font-size: 1.55rem; font-weight: 700; letter-spacing: -.02em; color: var(--aeos-text-primary); margin: 0 0 1.75rem; line-height: 1.15; }
        .rl-nav { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--aeos-divider); }
        .rl-footer { padding: 1.5rem 0 2rem; position: relative; z-index: 1; }

        /* ── Shared step utilities ──────────────────────────────────── */
        /* Selected card state — used by StepAccount and StepPlan */
        .rl-card-selected { border-color: var(--aeos-primary) !important; background: rgba(0,229,255,.04) !important; box-shadow: 0 0 0 3px rgba(0,229,255,.12) !important; }
        /* Icon container for type cards */
        .rl-type-icon { color: var(--aeos-primary); display: flex; }
        /* OTP / mono code input — used by StepVerifyEmail + StepVerifyPhone */
        .rl-otp-input { letter-spacing: 0.3em; font-family: var(--aeos-font-mono) !important; font-size: 1.2rem; text-align: center; }
        /* Subdomain live preview — used by StepDetails */
        .rl-subdomain-preview { margin-top: 6px; font-family: var(--aeos-font-mono); font-size: .82rem; color: var(--aeos-primary); letter-spacing: .01em; padding: 0 2px; }

        /* ── Plan step ──────────────────────────────────────────────── */
        .rl-plan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .rl-plan-grid > button { position: relative; text-align: left; }
        .rl-plan-badge { position: absolute; top: 10px; right: 10px; }
        .rl-plan-price-amount { font-family: var(--aeos-font-display); font-size: 1.75rem; font-weight: 700; color: var(--aeos-text-primary); letter-spacing: -.02em; }
        .rl-plan-price-per { font-size: .85rem; color: var(--aeos-text-tertiary); align-self: flex-end; padding-bottom: .2rem; }

        /* Module selection cards */
        .rl-module-grid { display: flex; flex-direction: column; gap: .75rem; }
        .rl-module-grid > button { text-align: left; }
        .rl-module-grid .rl-card-selected .rl-module-check { color: var(--aeos-primary); }

        /* Enhanced selected state for all interactive cards */
        .rl-card-selected { border-color: var(--aeos-primary) !important; background: rgba(0,229,255,.04) !important; box-shadow: 0 0 0 3px rgba(0,229,255,.12), 0 4px 12px rgba(0,229,255,.06) !important; }

        /* Option B: Split-screen plan layout */
        .rl-plan-split { display: grid; grid-template-columns: 1fr 280px; gap: 2rem; }
        @media (max-width: 860px) { .rl-plan-split { grid-template-columns: 1fr; } }
        .rl-plan-main { min-width: 0; }
        .rl-plan-sidebar { position: sticky; top: 1.5rem; align-self: start; }
        @media (max-width: 860px) { .rl-plan-sidebar { position: static; } }
        .rl-plan-grid-b { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        @media (max-width: 560px) { .rl-plan-grid-b { grid-template-columns: 1fr; } }
        .rl-module-grid-b { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
        @media (max-width: 560px) { .rl-module-grid-b { grid-template-columns: 1fr; } }
        .rl-summary-card { background: var(--aeos-bg-elevated); border: 1px solid rgba(0,229,255,.15); border-radius: var(--aeos-r-xl); padding: 1.5rem; display: flex; flex-direction: column; gap: .75rem; backdrop-filter: blur(12px); box-shadow: 0 16px 48px rgba(0,0,0,.55); }
        .rl-summary-row { display: flex; justify-content: space-between; align-items: center; font-size: .9rem; }
        .rl-summary-row .rl-label { color: var(--aeos-text-secondary); }
        .rl-summary-row .rl-value { font-weight: 600; }
        .rl-summary-divider { height: 1px; background: var(--aeos-divider); margin: .25rem 0; }
        .rl-summary-total .rl-value { font-family: var(--aeos-font-display); font-size: 1.5rem; font-weight: 800; color: var(--aeos-primary); }
        .rl-summary-btn { width: 100%; margin-top: .5rem; }
        .rl-plan-intro { margin-bottom: 1.5rem; }
        .rl-plan-billing { margin-bottom: 1.5rem; }
        .rl-plan-eyebrow { margin-top: 1.5rem; }
        .rl-card-selected:hover { background: rgba(0,229,255,.06) !important; box-shadow: 0 0 0 3px rgba(0,229,255,.15), 0 6px 16px rgba(0,229,255,.08) !important; }

        /* ── Provisioning step ──────────────────────────────────────── */
        @keyframes rl-spin      { to { transform: rotate(360deg); } }
        @keyframes rl-step-spin { to { transform: rotate(360deg); } }
        .rl-prov-icon-wrap { position: relative; width: 80px; height: 80px; }
        .rl-prov-icon-bg { position: absolute; inset: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .rl-prov-icon-bg--running   { background: rgba(0,229,255,.08);    border: 2px solid rgba(0,229,255,.15); }
        .rl-prov-icon-bg--completed { background: rgba(34,197,94,.10);   border: 2px solid rgba(34,197,94,.25); }
        .rl-prov-icon-bg--failed    { background: rgba(255,107,107,.10); border: 2px solid rgba(255,107,107,.25); }
        .rl-prov-spinner { position: absolute; inset: -2px; border-radius: 50%; border: 2px solid transparent; border-top-color: var(--aeos-primary); animation: rl-spin .8s linear infinite; }
        .rl-prov-bar-track { width: 100%; height: 6px; background: var(--aeos-divider); border-radius: 4px; overflow: hidden; }
        .rl-prov-bar-fill { height: 100%; border-radius: 4px; background: var(--aeos-grad-cyan); transition: width .5s ease; }
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
        .rl-prov-step-spinner { width: 10px; height: 10px; border: 1.5px solid transparent; border-top-color: var(--aeos-primary); border-radius: 50%; animation: rl-step-spin .6s linear infinite; }
        .rl-prov-step-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--aeos-text-tertiary); }

        /* ── Success step ───────────────────────────────────────────── */
        .rl-success-icon { filter: drop-shadow(0 0 20px rgba(34,197,94,.25)); }
        .rl-success-url { display: inline-flex; align-items: center; gap: 8px; padding: .5rem 1.25rem; background: rgba(0,229,255,.06); border: 1px solid rgba(0,229,255,.25); border-radius: var(--aeos-r-xl); color: var(--aeos-primary); text-decoration: none; transition: background .15s, box-shadow .15s; }
        .rl-success-url:hover { background: rgba(0,229,255,.1); box-shadow: 0 0 0 3px rgba(0,229,255,.12); }
        .rl-success-trial strong { color: var(--aeos-text-primary); }

        /* ── Mobile ─────────────────────────────────────────────────── */
        @media (max-width: 640px) {
          .rl-card { padding: 2rem 1.25rem; border-radius: var(--aeos-r-xl); }
          .rl-brand { padding: 1.5rem 1rem 0; }
          .rl-nav { flex-direction: column; align-items: stretch; }
          .rl-nav > * { width: 100%; justify-content: center; }
        }
      `}</style>
    </>
  );
}
