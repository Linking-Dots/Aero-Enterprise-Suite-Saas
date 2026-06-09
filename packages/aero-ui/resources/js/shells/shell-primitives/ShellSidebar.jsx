/**
 * AEOS UI — Shell Primitives  (Overhaul v2)
 * ─────────────────────────────────────────────────────────────────────────
 * ShellSidebar  — shared sidebar rail (used by SidebarShell + FloatingShell)
 * ShellTopbar   — shared topbar chrome (used by all 4 shell variants)
 *
 * v2 changes
 * ──────────
 * • ShellSidebar: variant modifier class removed — host shell sets the full
 *   CSS context via [data-aeos-shell] attribute; primitives only add state
 *   modifier classes (--expanded).
 * • ShellTopbar:  start/center/end slots; `sticky` → CSS class, not inline
 *   style; adds `as` prop for semantic override (header, div, nav…).
 * • Both: aria-label always set; no inline styles; all sizing via tokens.
 * ─────────────────────────────────────────────────────────────────────────
 */

import { forwardRef } from 'react';
import { cx } from '../../components/Primitives.jsx';

/* ─── ShellSidebar ──────────────────────────────────────────────────────── */

export const ShellSidebar = forwardRef(function ShellSidebar(
  {
    brand,
    children,
    expanded = false,
    className,
    style,
    'aria-label': ariaLabel = 'Side navigation',
    ...rest
  },
  ref,
) {
  return (
    <aside
      ref={ref}
      className={cx(
        'aeos-shell-sidebar',
        expanded && 'aeos-shell-sidebar--expanded',
        className,
      )}
      aria-label={ariaLabel}
      style={style}
      {...rest}
    >
      {brand && (
        <div className="aeos-shell-sidebar-brand">
          {brand}
        </div>
      )}

      <nav className="aeos-shell-sidebar-nav" aria-label={ariaLabel}>
        {children}
      </nav>
    </aside>
  );
});

ShellSidebar.displayName = 'ShellSidebar';

/* ─── ShellTopbar ───────────────────────────────────────────────────────── */

export const ShellTopbar = forwardRef(function ShellTopbar(
  {
    as: Tag = 'div',
    start,
    center,
    end,
    sticky = true,
    blurred = true,
    className,
    style,
    'aria-label': ariaLabel = 'Toolbar',
    children,
    ...rest
  },
  ref,
) {
  return (
    <Tag
      ref={ref}
      className={cx(
        'aeos-shell-topbar',
        sticky  && 'aeos-shell-topbar--sticky',
        blurred && 'aeos-shell-topbar--blurred',
        className,
      )}
      role={Tag === 'div' ? 'toolbar' : undefined}
      aria-label={ariaLabel}
      style={style}
      {...rest}
    >
      {children ?? (
        <>
          {start && (
            <div className="aeos-shell-topbar-start">
              {start}
            </div>
          )}

          {center && (
            <>
              <span className="aeos-shell-flex-spacer" aria-hidden="true" />
              <div className="aeos-shell-topbar-center">
                {center}
              </div>
            </>
          )}

          <span className="aeos-shell-flex-spacer" aria-hidden="true" />

          {end && (
            <div className="aeos-shell-topbar-end">
              {end}
            </div>
          )}
        </>
      )}
    </Tag>
  );
});

ShellTopbar.displayName = 'ShellTopbar';
