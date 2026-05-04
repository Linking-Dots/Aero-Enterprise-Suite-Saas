import { Head, Link } from '@inertiajs/react';
import { useTheme } from '../../theme/ThemeProvider.jsx';

/**
 * AuthLayout — centred full-viewport shell for all auth pages.
 *
 * Renders the AEOS gradient mesh background, a top brand bar with
 * dark/light mode toggle, a glass-strong centred card, and a footer.
 * All auth pages use this layout via the static `.layout` property pattern.
 *
 * @param {object}          props
 * @param {string}          props.title    - Page <title> and card heading
 * @param {string}          [props.eyebrow] - Mono overline above the title
 * @param {React.ReactNode} props.children - Form content
 */
export default function AuthLayout({ title, eyebrow, children }) {
  const theme = useTheme();
  const isDark = theme.isDark;

  return (
    <>
      <Head title={`${title} · aeos365`} />

      <div className="al-root">
        {/* Ambient gradient mesh */}
        <div className="al-mesh" aria-hidden="true" />

        {/* Brand header with theme toggle */}
        <header className="al-brand">
          <Link href="/" className="al-brand-link" aria-label="aeos365 home">
            <span className="al-logo-mark">
              <svg width="30" height="30" viewBox="0 0 30 30" fill="none" aria-hidden="true">
                <rect width="30" height="30" rx="8" fill="url(#al-grad)" />
                <path d="M9 21L15 9l6 12H9z" fill="white" fillOpacity=".92" />
                <defs>
                  <linearGradient id="al-grad" x1="0" y1="0" x2="30" y2="30">
                    <stop stopColor="var(--aeos-primary, #00E5FF)" />
                    <stop offset="1" stopColor="var(--aeos-tertiary, #6366F1)" />
                  </linearGradient>
                </defs>
              </svg>
            </span>
            <span className="aeos-logo-text">aeos365</span>
          </Link>

          <button
            type="button"
            className="al-theme-toggle"
            onClick={() => theme.setMode(isDark ? 'light' : 'dark')}
            aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
          >
            {isDark ? (
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="5" />
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
              </svg>
            ) : (
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
              </svg>
            )}
          </button>
        </header>

        {/* Card */}
        <main className="al-main">
          <div className="al-card aeos-glass-strong">
            {(eyebrow || title) && (
              <div className="al-card-head">
                {eyebrow && (
                  <span className="aeos-eyebrow aeos-eyebrow-primary">{eyebrow}</span>
                )}
                <h1 className="al-title">{title}</h1>
              </div>
            )}
            {children}
          </div>
        </main>

        {/* Footer */}
        <footer className="al-footer">
          <span className="aeos-text-xs aeos-text-tertiary">
            © {new Date().getFullYear()} aeos365
          </span>
        </footer>
      </div>

      <style>{`
        /* ── AuthLayout scoped styles ───────────────────────────── */
        .al-root {
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          align-items: center;
          background: var(--aeos-bg-page);
          position: relative;
          overflow: hidden;
        }
        /* Prevent shell grid from breaking centred layout.
           ThemeProvider still runs (no data-no-theme) so CSS variables
           like --aeos-bg-page update correctly for dark/light mode. */
        body[data-aeos-shell] { display: block !important; }
        .al-mesh {
          position: fixed; inset: 0; pointer-events: none; z-index: 0;
          background:
            radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,229,255,.12), transparent 65%),
            radial-gradient(ellipse 55% 50% at 90% 60%, rgba(99,102,241,.08), transparent 55%),
            radial-gradient(ellipse 40% 50% at 5%  75%, rgba(255,179,71,.05), transparent 55%);
        }
        .al-brand {
          width: 100%; max-width: 460px;
          padding: 2rem 1rem 0;
          position: relative; z-index: 1;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }
        .al-brand-link {
          display: inline-flex; align-items: center; gap: 10px;
          text-decoration: none;
        }
        .al-logo-mark {
          display: flex; align-items: center;
          filter: drop-shadow(0 0 14px rgba(0,229,255,.35));
        }
        .al-theme-toggle {
          display: inline-flex; align-items: center; justify-content: center;
          width: 36px; height: 36px; border-radius: 50%;
          border: 1px solid var(--aeos-divider);
          background: transparent;
          color: var(--aeos-text-secondary);
          cursor: pointer;
          transition: background .15s, color .15s, border-color .15s;
        }
        .al-theme-toggle:hover {
          background: rgba(0, 229, 255, .08);
          color: var(--aeos-primary);
          border-color: rgba(0, 229, 255, .25);
        }
        .al-main {
          flex: 1; display: flex; align-items: center; justify-content: center;
          padding: 2rem 1rem; width: 100%; position: relative; z-index: 1;
        }
        .al-card {
          width: 100%; max-width: 460px;
          border-radius: var(--aeos-r-2xl);
          padding: 2.5rem;
          box-shadow:
            0 0 0 1px rgba(0,229,255,.10),
            0 24px 64px rgba(0,0,0,.55),
            0 0 60px rgba(0,229,255,.04);
        }
        .al-card-head { margin-bottom: 2rem; }
        .al-title {
          font-family: var(--aeos-font-display);
          font-size: 1.65rem; font-weight: 700;
          line-height: 1.1; letter-spacing: -0.02em;
          color: var(--aeos-text-primary);
          margin: 0.35rem 0 0;
        }
        .al-footer {
          padding: 1.5rem 0 2rem;
          position: relative; z-index: 1;
        }
        /* Utility classes used inside auth forms */
        .al-form { display: flex; flex-direction: column; gap: 1.25rem; }
        .al-row  { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .al-links {
          margin-top: 1.5rem;
          display: flex; flex-direction: column; align-items: center;
          gap: 10px; font-size: 0.875rem; color: var(--aeos-text-secondary);
        }
        .al-link {
          color: var(--aeos-primary); text-decoration: none;
          font-weight: 500; transition: opacity .15s;
        }
        .al-link:hover { opacity: .8; text-decoration: underline; text-underline-offset: 3px; }
        .al-sep {
          display: flex; align-items: center; gap: 12px; margin: 1.5rem 0;
        }
        .al-sep-line { flex:1; height:1px; background:var(--aeos-divider); }
        .al-sep-text { font-size:.72rem; color:var(--aeos-text-tertiary); letter-spacing:.08em; white-space:nowrap; }
        /* OAuth provider grid */
        .al-oauth-grid { display: flex; gap: 8px; flex-wrap: wrap; }
        .al-oauth-grid > * { flex: 1; min-width: 80px; justify-content: center !important; font-size: 0.85rem; }
        /* OTP / mono code inputs */
        .al-otp-input { letter-spacing: 0.25em; font-family: var(--aeos-font-mono); font-size: 1.1rem; }
        @media (max-width: 480px) {
          .al-card { padding: 2rem 1.5rem; border-radius: var(--aeos-r-xl); }
          .al-brand { padding: 1.5rem 1rem 0; }
        }
      `}</style>
    </>
  );
}
