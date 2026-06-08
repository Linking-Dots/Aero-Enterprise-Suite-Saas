/**
 * AEOS UI — ShellTopbar primitive (Phase B)
 * Shared topbar chrome used by all shell variants.
 * Slots: start (left), center, end (right).
 * All sizing driven by --aeos-shell-topnav-h and density tokens.
 */
import { cx } from '../../components/Primitives.jsx';

/**
 * @param {{
 *   start?: React.ReactNode,
 *   center?: React.ReactNode,
 *   end?: React.ReactNode,
 *   sticky?: boolean,
 *   className?: string,
 * }} props
 */
export function ShellTopbar({ start, center, end, sticky = true, className }) {
  return (
    <div className={cx('aeos-shell-topbar', sticky && 'aeos-shell-topbar--sticky', className)}>
      {start && <div className="aeos-shell-topbar-start">{start}</div>}
      {center && <div className="aeos-shell-topbar-center">{center}</div>}
      <span className="aeos-shell-flex-spacer" aria-hidden="true" />
      {end && <div className="aeos-shell-topbar-end">{end}</div>}
    </div>
  );
}

export default ShellTopbar;
