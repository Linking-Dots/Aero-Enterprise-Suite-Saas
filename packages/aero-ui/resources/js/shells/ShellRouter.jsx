/**
 * AEOS UI — ShellRouter  (Overhaul v2)
 * ─────────────────────────────────────────────────────────────────────────
 * Single entry point that resolves the active shell variant (from props,
 * then ThemeProvider context, then DEFAULTS) and renders it.
 *
 * v2 additions
 * ────────────
 * • Wraps every shell in React.Suspense so lazy-loaded page content
 *   doesn't break the chrome.
 * • Forwards unknown props without spreading — explicit allow-list keeps
 *   the shell API surface clean and documented.
 * • Emits `aeos:shell-change` CustomEvent on variant switch so analytics
 *   and integrations can subscribe without coupling to React.
 * • Memoised — only re-renders when the resolved variant or forwarded
 *   props identity changes.
 * ─────────────────────────────────────────────────────────────────────────
 */

import {
  memo,
  Suspense,
  useEffect,
  useRef,
} from 'react';

import { useTheme } from '../theme/ThemeProvider.jsx';
import { AeosErrorBoundary } from '../components/AeosErrorBoundary.jsx';

import {
  SidebarShell,
  TopNavShell,
  FloatingShell,
  CommandShell,
} from './Shells.jsx';

/* ── Shell registry ─────────────────────────────────────────────────────── */

const SHELL_MAP = {
  sidebar:  SidebarShell,
  topnav:   TopNavShell,
  floating: FloatingShell,
  command:  CommandShell,
};

const DEFAULT_VARIANT = 'sidebar';

/* ── Analytics emitter ──────────────────────────────────────────────────── */

function emitShellChange(next, prev) {
  if (typeof window === 'undefined') return;
  window.dispatchEvent(
    new CustomEvent('aeos:shell-change', {
      bubbles:    false,
      cancelable: false,
      detail:     { next, prev },
    }),
  );
}

/* ── Suspense fallback ──────────────────────────────────────────────────── */

function ShellSuspenseFallback() {
  return (
    <div
      style={{
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        gap: 'var(--aeos-gap-card)',
        padding: 'var(--aeos-page-pad-y) var(--aeos-page-pad-x)',
      }}
      aria-busy="true"
      aria-label="Loading page content"
    >
      {[80, 280, 180].map((w, i) => (
        <div
          key={i}
          className="aeos-anim-shimmer"
          style={{
            height:        '20px',
            maxWidth:      `${w}px`,
            borderRadius:  'var(--aeos-r-md)',
            background:    'var(--aeos-bg-tint)',
          }}
        />
      ))}
    </div>
  );
}

/* ── ShellRouter ────────────────────────────────────────────────────────── */

function ShellRouterInner({
  variant,
  brand,
  nav,
  topbar,
  actions,
  subbar,
  rail,
  railTitle,
  railWidth,
  footer,
  hideTopbar,
  maxWidth,
  expanded,
  onExpandedChange,
  suspenseFallback,
  children,
}) {
  const theme = useTheme();

  const resolved = SHELL_MAP[variant ?? theme.shell]
    ? (variant ?? theme.shell)
    : DEFAULT_VARIANT;

  const ShellComponent = SHELL_MAP[resolved];

  const prevVariantRef = useRef(resolved);
  useEffect(() => {
    if (prevVariantRef.current !== resolved) {
      emitShellChange(resolved, prevVariantRef.current);
      prevVariantRef.current = resolved;
    }
  }, [resolved]);

  const sharedProps = {
    brand, nav, topbar, actions, footer,
    hideTopbar, maxWidth, expanded, onExpandedChange,
    // Page-scope boundary: a content-render error is contained here so the
    // shell chrome (sidebar/topbar) stays alive and the error shows in-place.
    children: <AeosErrorBoundary scope="page">{children}</AeosErrorBoundary>,
  };

  const variantProps = {};
  if (resolved === 'topnav')   variantProps.subbar = subbar;
  if (resolved === 'command') {
    variantProps.rail      = rail;
    variantProps.railTitle = railTitle;
    variantProps.railWidth = railWidth;
  }

  return (
    <Suspense fallback={suspenseFallback ?? <ShellSuspenseFallback />}>
      <ShellComponent {...sharedProps} {...variantProps} />
    </Suspense>
  );
}

export const ShellRouter = memo(ShellRouterInner);
export default ShellRouter;
