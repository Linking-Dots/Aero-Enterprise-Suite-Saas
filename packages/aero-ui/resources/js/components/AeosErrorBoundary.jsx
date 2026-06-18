/**
 * @aero/ui Engine — Error Boundary
 * ─────────────────────────────────────────────────────────────────────────
 * Catches render/runtime errors in the React tree and shows them VISIBLY
 * instead of letting one throw blank the whole SPA. It NEVER silences:
 *   • logs the error + component stack to console (devtools / log shipping)
 *   • renders a styled panel with the message (+ stack in dev) and recovery
 *
 * Inertia-standard: subscribes to the router 'navigate' event so a successful
 * navigation clears a contained error and the shell self-heals — you can click
 * away from a broken page without a hard reload.
 *
 * Placement (two levels):
 *   • scope="app"  — wraps the whole Inertia <App/> in app.jsx (last resort;
 *                    catches shell crashes + public pages with no shell).
 *   • scope="page" — wraps the shell content slot so a page-content error is
 *                    contained: the sidebar/topbar stay alive and usable.
 * ─────────────────────────────────────────────────────────────────────────
 */
import { Component } from 'react';
import { router } from '@inertiajs/react';

/* ── Presentational panel (also used as the missing-page fallback) ───────── */
export function EngineErrorPanel({ error, componentStack, scope = 'page', onRetry }) {
  const isDev = Boolean(import.meta.env?.DEV);
  const message = error?.message || String(error ?? 'Unknown error');

  return (
    <div className="aeos-error-boundary" role="alert" aria-live="assertive" data-scope={scope}>
      <div className="aeos-error-boundary-card">
        <div className="aeos-error-boundary-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </div>

        <h2 className="aeos-error-boundary-title">
          {scope === 'app' ? 'The application hit an error' : 'This page hit an error'}
        </h2>

        <p className="aeos-error-boundary-message">{message}</p>

        {isDev && componentStack && (
          <details className="aeos-error-boundary-details" open>
            <summary>Component stack (dev only)</summary>
            <pre className="aeos-error-boundary-stack">{componentStack}</pre>
          </details>
        )}

        <div className="aeos-error-boundary-actions">
          {onRetry && (
            <button type="button" className="aeos-error-boundary-btn aeos-error-boundary-btn--primary" onClick={onRetry}>
              Try again
            </button>
          )}
          <button
            type="button"
            className="aeos-error-boundary-btn aeos-error-boundary-btn--ghost"
            onClick={() => { if (typeof window !== 'undefined') window.location.reload(); }}
          >
            Reload page
          </button>
        </div>
      </div>
    </div>
  );
}

/* ── Error boundary ──────────────────────────────────────────────────────── */
export class AeosErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null, componentStack: null };
    this._stopNavigate = null;
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  componentDidCatch(error, info) {
    // Never silence — surface to console for devtools + log shipping.
    // eslint-disable-next-line no-console
    console.error('[AeosErrorBoundary] Uncaught render error:', error, info?.componentStack);
    this.setState({ componentStack: info?.componentStack ?? null });
  }

  componentDidMount() {
    // Inertia-standard self-heal: clear a contained error once the user
    // navigates to another page so the shell doesn't stay wedged.
    this._stopNavigate = router.on('navigate', () => {
      if (this.state.error) this.setState({ error: null, componentStack: null });
    });
  }

  componentWillUnmount() {
    this._stopNavigate?.();
  }

  handleRetry = () => this.setState({ error: null, componentStack: null });

  render() {
    if (!this.state.error) return this.props.children;
    return (
      <EngineErrorPanel
        error={this.state.error}
        componentStack={this.state.componentStack}
        scope={this.props.scope ?? 'page'}
        onRetry={this.handleRetry}
      />
    );
  }
}

export default AeosErrorBoundary;
