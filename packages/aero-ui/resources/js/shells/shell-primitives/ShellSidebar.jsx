/**
 * AEOS UI — ShellSidebar primitive (Phase B)
 * Shared sidebar rail used by Sidebar + Floating shells.
 * Handles: icon-only mode, expanded mode, mobile overlay, sticky positioning.
 * Collapse state is persisted to localStorage by the parent shell.
 */
import { cx } from '../../components/Primitives.jsx';

/**
 * @param {{
 *   brand?: React.ReactNode,
 *   children: React.ReactNode,
 *   expanded?: boolean,
 *   variant?: 'sidebar' | 'floating',
 *   className?: string,
 * }} props
 */
export function ShellSidebar({ brand, children, expanded = false, variant = 'sidebar', className }) {
  return (
    <aside
      className={cx(
        'aeos-shell-sidebar',
        `aeos-shell-sidebar--${variant}`,
        expanded && 'aeos-shell-sidebar--expanded',
        className
      )}
      aria-label="Side navigation"
    >
      {brand && (
        <div className="aeos-shell-sidebar-brand">
          {brand}
        </div>
      )}
      <div className="aeos-shell-sidebar-nav">
        {children}
      </div>
    </aside>
  );
}

export default ShellSidebar;
